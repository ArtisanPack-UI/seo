<?php

/**
 * Open Graph Type Inference Tests.
 *
 * Verifies a saved seo_meta row no longer blocks og:type inference, and that
 * the migration clears the old default.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.9.0
 */

declare( strict_types=1 );

namespace Tests\Unit\Services\OgTypeInference;

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\SocialMetaService;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses( RefreshDatabase::class );

/**
 * Model that inference treats as an article.
 */
class Post extends Model
{
	use HasSeo;

	protected $table = 'og_type_posts';

	protected $guarded = [];
}

/**
 * Model that inference treats as a website.
 */
class Page extends Post
{
}

beforeEach( function (): void {
	Schema::create( 'og_type_posts', function ( Blueprint $table ): void {
		$table->id();
		$table->string( 'title' )->nullable();
		$table->timestamps();
	} );

	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	$this->migration = require __DIR__ . '/../../../database/migrations/2026_10_30_000001_make_og_type_nullable_on_seo_meta_table.php';
} );

describe( 'og:type inference', function (): void {

	it( 'infers article for a post with no seo_meta row', function (): void {
		expect( app( SocialMetaService::class )->generateOpenGraph( new Post() )->type )->toBe( 'article' );
	} );

	it( 'infers article for a post with a seo_meta row', function (): void {
		$post = Post::create( [ 'title' => 'Post' ] );

		$seoMeta = $post->getOrCreateSeoMeta();

		expect( $seoMeta->og_type )->toBeNull()
			->and( app( SocialMetaService::class )->generateOpenGraph( $post, $seoMeta )->type )->toBe( 'article' )
			->and( $post->getSeoData()['open_graph']['type'] )->toBe( 'article' );
	} );

	it( 'infers from an empty saved type', function (): void {
		expect( app( SocialMetaService::class )->resolveOgType( new Post(), new SeoMeta( [ 'og_type' => '' ] ) ) )->toBe( 'article' );
	} );

	it( 'uses the configured default for other models', function (): void {
		config( [ 'seo.open_graph.type' => 'profile' ] );

		$page = Page::create( [ 'title' => 'Page' ] );

		expect( app( SocialMetaService::class )->generateOpenGraph( $page, $page->getOrCreateSeoMeta() )->type )->toBe( 'profile' );
	} );

	it( 'keeps an explicit type', function (): void {
		$post = Post::create( [ 'title' => 'Post' ] );

		$seoMeta = $post->updateSeoMeta( [ 'og_type' => 'website' ] );

		expect( app( SocialMetaService::class )->generateOpenGraph( $post, $seoMeta )->type )->toBe( 'website' )
			->and( $post->fresh()->getSeoData()['open_graph']['type'] )->toBe( 'website' );
	} );

} );

describe( 'og_type migration', function (): void {

	/**
	 * Insert a seo_meta row with a raw og_type.
	 */
	function insertSeoMetaRow( int $id, ?string $ogType ): void
	{
		DB::table( 'seo_meta' )->insert( [
			'seoable_type' => Post::class,
			'seoable_id'   => $id,
			'og_type'      => $ogType,
		] );
	}

	it( 'clears the old default and keeps other types', function (): void {
		$this->migration->down();

		insertSeoMetaRow( 1, 'website' );
		insertSeoMetaRow( 2, 'article' );

		$this->migration->up();

		expect( DB::table( 'seo_meta' )->where( 'seoable_id', 1 )->value( 'og_type' ) )->toBeNull()
			->and( DB::table( 'seo_meta' )->where( 'seoable_id', 2 )->value( 'og_type' ) )->toBe( 'article' );
	} );

	it( 'has no column default after running', function (): void {
		DB::table( 'seo_meta' )->insert( [ 'seoable_type' => Post::class, 'seoable_id' => 1 ] );

		expect( DB::table( 'seo_meta' )->value( 'og_type' ) )->toBeNull();
	} );

	it( 'keeps the unique seoable index', function (): void {
		$indexes = collect( Schema::getIndexes( 'seo_meta' ) )
			->where( 'columns', [ 'seoable_type', 'seoable_id' ] );

		expect( $indexes->contains( 'unique', true ) )->toBeTrue();
	} );

	it( 'restores the default on rollback', function (): void {
		insertSeoMetaRow( 1, null );

		$this->migration->down();

		DB::table( 'seo_meta' )->insert( [ 'seoable_type' => Post::class, 'seoable_id' => 2 ] );

		expect( DB::table( 'seo_meta' )->pluck( 'og_type' )->unique()->all() )->toBe( [ 'website' ] );
	} );

} );
