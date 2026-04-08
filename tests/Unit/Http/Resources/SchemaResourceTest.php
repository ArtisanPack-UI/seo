<?php

/**
 * SchemaResource Tests.
 *
 * Unit tests for SchemaResource.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Http\Resources\SchemaResource;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

uses( RefreshDatabase::class );

/**
 * Test model for SchemaResource tests.
 */
class SchemaResourceTestPage extends Model
{
	use HasSeo;

	protected $table = 'schema_resource_test_pages';

	protected $fillable = [ 'title' ];
}

beforeEach( function (): void {
	Schema::create( 'schema_resource_test_pages', function ( Blueprint $table ): void {
		$table->id();
		$table->string( 'title' )->nullable();
		$table->timestamps();
	} );

	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../../database/migrations' ) ] );
} );

afterEach( function (): void {
	Schema::dropIfExists( 'schema_resource_test_pages' );
} );

describe( 'SchemaResource', function (): void {

	it( 'serializes schema configuration with generated data', function (): void {
		$page    = SchemaResourceTestPage::create( [ 'title' => 'Test' ] );
		$seoMeta = $page->seoMeta()->create( [
			'schema_type'   => 'Article',
			'schema_markup' => [ 'author' => 'John' ],
		] );

		$generated = [ '@type' => 'Article', 'name' => 'Test' ];
		$types     = [ 'Article', 'WebPage', 'Product' ];

		$resource = new SchemaResource( $seoMeta, $generated, $types );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['schema_type'] )->toBe( 'Article' )
			->and( $result['schema_markup'] )->toBe( [ 'author' => 'John' ] )
			->and( $result['generated'] )->toBe( $generated )
			->and( $result['available_types'] )->toBe( $types );
	} );

	it( 'handles null resource', function (): void {
		$resource = new SchemaResource( null );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['schema_type'] )->toBeNull()
			->and( $result['schema_markup'] )->toBeNull()
			->and( $result['generated'] )->toBeNull()
			->and( $result['available_types'] )->toBe( [] );
	} );
} );
