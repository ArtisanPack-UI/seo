<?php

/**
 * MorphModelResolver Tests.
 *
 * Unit tests for resolving morph type strings that are not class names
 * through the ap.seo.resolveMorphModel filter.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.8.0
 */

declare( strict_types=1 );

use ArtisanPackUI\Hooks\Facades\Filter;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Support\MorphModelResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses( RefreshDatabase::class );

/**
 * Test model shared by several tables, with the table in its morph class.
 */
class PartitionedResolverRecord extends Model
{
	protected $guarded = [];

	public function getMorphClass(): string
	{
		return static::class . ':' . $this->getTable();
	}
}

/**
 * Test model with a plain class-name morph type.
 */
class PlainResolverRecord extends Model
{
	protected $table = 'resolver_products';

	protected $guarded = [];
}

beforeEach( function (): void {
	foreach ( [ 'resolver_products', 'resolver_events' ] as $tableName ) {
		Schema::create( $tableName, function ( Blueprint $table ): void {
			$table->id();
			$table->string( 'title' )->nullable();
			$table->timestamps();
		} );
	}

	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	Filter::removeAll( MorphModelResolver::FILTER );

	Filter::add( MorphModelResolver::FILTER, function ( ?Model $model, string $type ): ?Model {
		$prefix = PartitionedResolverRecord::class . ':';

		if ( ! str_starts_with( $type, $prefix ) ) {
			return $model;
		}

		return ( new PartitionedResolverRecord() )->setTable( substr( $type, strlen( $prefix ) ) );
	} );

	// Same primary key in both tables, so a wrong-table lookup would return the other title.
	DB::table( 'resolver_products' )->insert( [ 'id' => 1, 'title' => 'Widget' ] );
	DB::table( 'resolver_events' )->insert( [ 'id' => 1, 'title' => 'Launch Party' ] );
} );

afterEach( function (): void {
	Filter::removeAll( MorphModelResolver::FILTER );
} );

/**
 * Create a SeoMeta row pointing at a partitioned record.
 */
function partitionedSeoMeta( string $table, int $id = 1 ): SeoMeta
{
	return SeoMeta::create( [
		'seoable_type' => PartitionedResolverRecord::class . ':' . $table,
		'seoable_id'   => $id,
	] );
}

describe( 'MorphModelResolver', function (): void {

	it( 'lazy loads seoable from the table in the morph type', function (): void {
		$seoMeta = SeoMeta::find( partitionedSeoMeta( 'resolver_events' )->id );

		expect( $seoMeta->seoable )->toBeInstanceOf( PartitionedResolverRecord::class )
			->and( $seoMeta->seoable->getTable() )->toBe( 'resolver_events' )
			->and( $seoMeta->seoable->title )->toBe( 'Launch Party' );
	} );

	it( 'eager loads seoable across tables', function (): void {
		partitionedSeoMeta( 'resolver_products' );
		partitionedSeoMeta( 'resolver_events' );

		$titles = SeoMeta::with( 'seoable' )->orderBy( 'id' )->get()
			->map( fn ( SeoMeta $seoMeta ): ?string => $seoMeta->seoable?->title )
			->all();

		expect( $titles )->toBe( [ 'Widget', 'Launch Party' ] );
	} );

	it( 'lazy and eager loads sitemapable', function (): void {
		foreach ( [ 'resolver_products', 'resolver_events' ] as $table ) {
			SitemapEntry::create( [
				'sitemapable_type' => PartitionedResolverRecord::class . ':' . $table,
				'sitemapable_id'   => 1,
				'url'              => 'https://example.com/' . $table,
			] );
		}

		$lazy  = SitemapEntry::orderBy( 'id' )->first()->sitemapable;
		$eager = SitemapEntry::with( 'sitemapable' )->orderBy( 'id' )->get()
			->map( fn ( SitemapEntry $entry ): ?string => $entry->sitemapable?->title )
			->all();

		expect( $lazy->title )->toBe( 'Widget' )
			->and( $eager )->toBe( [ 'Widget', 'Launch Party' ] );
	} );

	it( 'returns null for a resolved type whose record is missing', function (): void {
		$seoMeta = partitionedSeoMeta( 'resolver_products', 99 );

		expect( $seoMeta->seoable )->toBeNull();
	} );

	it( 'leaves class-name morph types to Eloquent', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => PlainResolverRecord::class,
			'seoable_id'   => 1,
		] );

		expect( $seoMeta->seoable )->toBeInstanceOf( PlainResolverRecord::class )
			->and( $seoMeta->seoable->title )->toBe( 'Widget' );
	} );

	it( 'ignores filter results that are not models', function (): void {
		Filter::add( MorphModelResolver::FILTER, fn (): string => 'not a model', 20 );

		expect( MorphModelResolver::resolve( PartitionedResolverRecord::class . ':resolver_events' ) )->toBeNull();
	} );

	it( 'reports which morph types can be resolved', function (): void {
		expect( MorphModelResolver::canResolve( PartitionedResolverRecord::class . ':resolver_events' ) )->toBeTrue()
			->and( MorphModelResolver::canResolve( PlainResolverRecord::class ) )->toBeTrue()
			->and( MorphModelResolver::canResolve( 'App\\Models\\Missing' ) )->toBeFalse();
	} );
} );
