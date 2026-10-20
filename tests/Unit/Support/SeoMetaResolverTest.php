<?php

/**
 * SeoMetaResolver Tests.
 *
 * Unit tests for resolving a model's SeoMeta relationship, including
 * relations bound at runtime with Model::resolveRelationUsing().
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.7.1
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoAnalysisCache;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Observers\SeoObserver;
use ArtisanPackUI\SEO\Services\AnalysisService;
use ArtisanPackUI\SEO\Services\HreflangService;
use ArtisanPackUI\SEO\Services\SeoService;
use ArtisanPackUI\SEO\Support\SeoMetaResolver;
use ArtisanPackUI\SEO\Traits\HasSeo;
use ArtisanPackUI\SEO\View\Components\MetaTags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses( RefreshDatabase::class );

/**
 * Test model whose seoMeta relation is bound with resolveRelationUsing().
 */
class ResolverDynamicPost extends Model
{
	protected $table = 'resolver_test_posts';

	protected $guarded = [];
}

/**
 * Test model that declares seoMeta through the HasSeo trait.
 */
class ResolverTraitPost extends Model
{
	use HasSeo;

	protected $table = 'resolver_test_posts';

	protected $guarded = [];
}

/**
 * Test model with no seoMeta relation at all.
 */
class ResolverPlainPost extends Model
{
	protected $table = 'resolver_test_posts';

	protected $guarded = [];
}

beforeEach( function (): void {
	Schema::create( 'resolver_test_posts', function ( Blueprint $table ): void {
		$table->id();
		$table->string( 'title' )->nullable();
		$table->string( 'slug' )->nullable();
		$table->text( 'content' )->nullable();
		$table->timestamps();
	} );

	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	ResolverDynamicPost::resolveRelationUsing(
		'seoMeta',
		fn ( Model $model ) => $model->morphOne( SeoMeta::class, 'seoable' ),
	);
} );

afterEach( function (): void {
	Schema::dropIfExists( 'resolver_test_posts' );
} );

/**
 * Create a dynamic-relation post with a persisted SeoMeta row.
 */
function createDynamicPostWithMeta( array $meta = [] ): ResolverDynamicPost
{
	$post = ResolverDynamicPost::create( [
		'title'   => 'Dynamic Post',
		'slug'    => 'dynamic-post',
		'content' => '<h1>Dynamic Post</h1><p>' . str_repeat( 'Some readable content here. ', 40 ) . '</p>',
	] );

	SeoMeta::create( array_merge( [
		'seoable_type'     => $post->getMorphClass(),
		'seoable_id'       => $post->getKey(),
		'meta_title'       => 'A Persisted Meta Title For The Dynamic Post',
		'meta_description' => str_repeat( 'a', 150 ),
	], $meta ) );

	return $post->fresh();
}

describe( 'SeoMetaResolver::hasRelation()', function (): void {

	it( 'detects a relation bound with resolveRelationUsing()', function (): void {
		$post = new ResolverDynamicPost();

		expect( method_exists( $post, 'seoMeta' ) )->toBeFalse()
			->and( SeoMetaResolver::hasRelation( $post ) )->toBeTrue();
	} );

	it( 'detects a relation declared by the HasSeo trait', function (): void {
		expect( SeoMetaResolver::hasRelation( new ResolverTraitPost() ) )->toBeTrue();
	} );

	it( 'returns false for a model without a seoMeta relation', function (): void {
		expect( SeoMetaResolver::hasRelation( new ResolverPlainPost() ) )->toBeFalse();
	} );

} );

describe( 'SeoMetaResolver::resolve()', function (): void {

	it( 'resolves SeoMeta through a dynamic relation', function (): void {
		$post = createDynamicPostWithMeta();

		expect( SeoMetaResolver::resolve( $post ) )->toBeInstanceOf( SeoMeta::class )
			->and( SeoMetaResolver::resolve( $post )->meta_description )->toBe( str_repeat( 'a', 150 ) );
	} );

	it( 'resolves SeoMeta through the HasSeo trait', function (): void {
		$post = ResolverTraitPost::create( [ 'title' => 'Trait Post' ] );
		$post->seoMeta()->create( [ 'meta_title' => 'Trait Title' ] );

		expect( SeoMetaResolver::resolve( $post->fresh() )?->meta_title )->toBe( 'Trait Title' );
	} );

	it( 'returns null when the dynamic relation has no row', function (): void {
		$post = ResolverDynamicPost::create( [ 'title' => 'No Meta' ] );

		expect( SeoMetaResolver::resolve( $post ) )->toBeNull();
	} );

	it( 'returns null for a model without a seoMeta relation', function (): void {
		$post = ResolverPlainPost::create( [ 'title' => 'Plain' ] );

		expect( SeoMetaResolver::resolve( $post ) )->toBeNull();
	} );

	it( 'is used by the seoMeta() helper', function (): void {
		$post = createDynamicPostWithMeta();

		expect( seoMeta( $post ) )->toBeInstanceOf( SeoMeta::class );
	} );

} );

describe( 'Dynamic seoMeta relation integration', function (): void {

	it( 'feeds persisted SeoMeta into content analysis', function (): void {
		$post   = createDynamicPostWithMeta();
		$result = app( AnalysisService::class )->analyze( $post, null, false );

		$messages = array_column( $result->issues, 'message' );

		expect( $messages )->not->toContain( 'Meta description is missing.' );
	} );

	it( 'falls back to the persisted focus keyword during analysis', function (): void {
		$post   = createDynamicPostWithMeta( [ 'focus_keyword' => 'readable content' ] );
		$result = app( AnalysisService::class )->analyze( $post, null, false );

		expect( $result->focusKeyword )->toBe( 'readable content' );
	} );

	it( 'caches analysis results against the resolved SeoMeta', function (): void {
		$post    = createDynamicPostWithMeta();
		$seoMeta = SeoMetaResolver::resolve( $post );

		app( AnalysisService::class )->analyze( $post );

		expect( SeoAnalysisCache::where( 'seo_meta_id', $seoMeta->id )->exists() )->toBeTrue();
	} );

	it( 'returns SeoMeta from SeoService::getSeoMeta()', function (): void {
		$post = createDynamicPostWithMeta();

		expect( app( SeoService::class )->getSeoMeta( $post ) )->toBeInstanceOf( SeoMeta::class );
	} );

	it( 'updates SeoMeta through SeoService::updateSeoMeta()', function (): void {
		$post = ResolverDynamicPost::create( [ 'title' => 'Update Me' ] );

		$seoMeta = app( SeoService::class )->updateSeoMeta( $post, [ 'meta_title' => 'Updated Title' ] );

		expect( $seoMeta->seoable_id )->toBe( $post->getKey() )
			->and( SeoMetaResolver::resolve( $post->fresh() )?->meta_title )->toBe( 'Updated Title' );
	} );

	it( 'refreshes the loaded relation after SeoService::updateSeoMeta() creates a row', function (): void {
		$post = ResolverDynamicPost::create( [ 'title' => 'Update Me' ] );

		expect( SeoMetaResolver::resolve( $post ) )->toBeNull();

		app( SeoService::class )->updateSeoMeta( $post, [ 'meta_title' => 'Created Title' ] );

		expect( SeoMetaResolver::resolve( $post )?->meta_title )->toBe( 'Created Title' );
	} );

	it( 'rejects SeoService::updateSeoMeta() for a model without the relation', function (): void {
		$post = ResolverPlainPost::create( [ 'title' => 'Plain' ] );

		app( SeoService::class )->updateSeoMeta( $post, [ 'meta_title' => 'Nope' ] );
	} )->throws( InvalidArgumentException::class );

	it( 'renders persisted meta in the MetaTags component', function (): void {
		$post      = createDynamicPostWithMeta();
		$component = new MetaTags( model: $post );

		expect( $component->description )->toBe( str_repeat( 'a', 150 ) );
	} );

	it( 'resolves SeoMeta in HreflangService', function (): void {
		$post = createDynamicPostWithMeta( [ 'hreflang' => [ 'fr' => 'https://example.com/fr' ] ] );

		expect( app( HreflangService::class )->getHreflangCount( $post ) )->toBe( 1 );
	} );

	it( 'deletes SeoMeta from the observer on delete', function (): void {
		$post    = createDynamicPostWithMeta();
		$seoMeta = SeoMetaResolver::resolve( $post );

		app( SeoObserver::class )->deleted( $post );

		expect( SeoMeta::find( $seoMeta->id ) )->toBeNull();
	} );

} );
