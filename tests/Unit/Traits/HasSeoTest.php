<?php

/**
 * HasSeo Trait Tests.
 *
 * Unit tests for the HasSeo trait functionality.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses( RefreshDatabase::class );

/**
 * Test model that uses the HasSeo trait.
 */
class TestPage extends Model
{
	use HasSeo;

	protected $table = 'test_pages';

	protected $fillable = [
		'title',
		'slug',
		'content',
		'excerpt',
		'description',
		'featured_image',
	];
}

beforeEach( function (): void {
	// Create the test pages table
	Schema::create( 'test_pages', function ( Blueprint $table ): void {
		$table->id();
		$table->string( 'title' )->nullable();
		$table->string( 'slug' )->nullable();
		$table->text( 'content' )->nullable();
		$table->text( 'excerpt' )->nullable();
		$table->text( 'description' )->nullable();
		$table->string( 'featured_image' )->nullable();
		$table->timestamps();
	} );

	// Run the SEO meta migration
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

afterEach( function (): void {
	Schema::dropIfExists( 'test_pages' );
} );

describe( 'HasSeo Trait - Relationship', function (): void {

	it( 'defines seoMeta morphOne relationship', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		expect( $page->seoMeta() )->toBeInstanceOf( Illuminate\Database\Eloquent\Relations\MorphOne::class );
	} );

	it( 'can create seo meta through relationship', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$seoMeta = $page->seoMeta()->create( [
			'meta_title'       => 'SEO Title',
			'meta_description' => 'SEO Description',
		] );

		expect( $seoMeta )->toBeInstanceOf( SeoMeta::class )
			->and( $seoMeta->meta_title )->toBe( 'SEO Title' )
			->and( $seoMeta->seoable_type )->toBe( TestPage::class )
			->and( $seoMeta->seoable_id )->toBe( $page->id );
	} );

	it( 'can access seo meta through model', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [
			'meta_title' => 'SEO Title',
		] );

		$page->refresh();

		expect( $page->seoMeta )->toBeInstanceOf( SeoMeta::class )
			->and( $page->seoMeta->meta_title )->toBe( 'SEO Title' );
	} );

} );

describe( 'HasSeo Trait - getOrCreateSeoMeta', function (): void {

	it( 'creates seo meta if it does not exist', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		expect( $page->seoMeta )->toBeNull();

		$seoMeta = $page->getOrCreateSeoMeta();

		expect( $seoMeta )->toBeInstanceOf( SeoMeta::class )
			->and( $seoMeta->seoable_type )->toBe( TestPage::class )
			->and( $seoMeta->seoable_id )->toBe( $page->id );
	} );

	it( 'returns existing seo meta if it exists', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [
			'meta_title' => 'Existing Title',
		] );

		$page->refresh();

		$seoMeta = $page->getOrCreateSeoMeta();

		expect( $seoMeta->meta_title )->toBe( 'Existing Title' );
	} );

} );

describe( 'HasSeo Trait - updateSeoMeta', function (): void {

	it( 'updates existing seo meta', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [
			'meta_title' => 'Original Title',
		] );

		$page->refresh();

		$seoMeta = $page->updateSeoMeta( [
			'meta_title'       => 'Updated Title',
			'meta_description' => 'New Description',
		] );

		expect( $seoMeta->meta_title )->toBe( 'Updated Title' )
			->and( $seoMeta->meta_description )->toBe( 'New Description' );
	} );

	it( 'creates seo meta if it does not exist when updating', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$seoMeta = $page->updateSeoMeta( [
			'meta_title' => 'New Title',
		] );

		expect( $seoMeta )->toBeInstanceOf( SeoMeta::class )
			->and( $seoMeta->meta_title )->toBe( 'New Title' );
	} );

} );

describe( 'HasSeo Trait - getSeoTitle', function (): void {

	it( 'returns meta_title when set', function (): void {
		$page = TestPage::create( [ 'title' => 'Page Title' ] );

		$page->seoMeta()->create( [
			'meta_title' => 'SEO Meta Title',
		] );

		$page->refresh();

		expect( $page->getSeoTitle() )->toBe( 'SEO Meta Title' );
	} );

	it( 'falls back to model title when meta_title is not set', function (): void {
		$page = TestPage::create( [ 'title' => 'Page Title' ] );

		expect( $page->getSeoTitle() )->toBe( 'Page Title' );
	} );

	it( 'falls back to app name when no title available', function (): void {
		$page = TestPage::create( [] );

		config( [ 'app.name' => 'Test Application' ] );

		expect( $page->getSeoTitle() )->toBe( 'Test Application' );
	} );

	it( 'provides meta_title attribute accessor', function (): void {
		$page = TestPage::create( [ 'title' => 'Page Title' ] );

		expect( $page->meta_title )->toBe( 'Page Title' );
	} );

} );

describe( 'HasSeo Trait - getSeoDescription', function (): void {

	it( 'returns meta_description when set', function (): void {
		$page = TestPage::create( [ 'description' => 'Page Description' ] );

		$page->seoMeta()->create( [
			'meta_description' => 'SEO Meta Description',
		] );

		$page->refresh();

		expect( $page->getSeoDescription() )->toBe( 'SEO Meta Description' );
	} );

	it( 'falls back to excerpt when meta_description is not set', function (): void {
		$page = TestPage::create( [ 'excerpt' => 'This is the page excerpt.' ] );

		expect( $page->getSeoDescription() )->toBe( 'This is the page excerpt.' );
	} );

	it( 'falls back to description when excerpt is not set', function (): void {
		$page = TestPage::create( [ 'description' => 'This is the page description.' ] );

		expect( $page->getSeoDescription() )->toBe( 'This is the page description.' );
	} );

	it( 'falls back to content when description is not set', function (): void {
		$page = TestPage::create( [ 'content' => 'This is the page content.' ] );

		expect( $page->getSeoDescription() )->toBe( 'This is the page content.' );
	} );

	it( 'truncates long content to 160 characters', function (): void {
		$longContent = str_repeat( 'This is a long content. ', 20 );
		$page        = TestPage::create( [ 'content' => $longContent ] );

		$description = $page->getSeoDescription();

		expect( strlen( $description ) )->toBeLessThanOrEqual( 163 ); // 160 + '...'
	} );

	it( 'strips HTML tags from content', function (): void {
		$page = TestPage::create( [ 'content' => '<p>This is <strong>HTML</strong> content.</p>' ] );

		expect( $page->getSeoDescription() )->toBe( 'This is HTML content.' );
	} );

	it( 'provides meta_description attribute accessor', function (): void {
		$page = TestPage::create( [ 'excerpt' => 'Page Excerpt' ] );

		expect( $page->meta_description )->toBe( 'Page Excerpt' );
	} );

} );

describe( 'HasSeo Trait - getSeoImage', function (): void {

	it( 'returns og_image from seo meta when set', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [
			'og_image' => 'https://example.com/seo-image.jpg',
		] );

		$page->refresh();

		expect( $page->getSeoImage() )->toBe( 'https://example.com/seo-image.jpg' );
	} );

	it( 'falls back to featured_image when og_image is not set', function (): void {
		$page = TestPage::create( [
			'title'          => 'Test Page',
			'featured_image' => 'https://example.com/featured.jpg',
		] );

		expect( $page->getSeoImage() )->toBe( 'https://example.com/featured.jpg' );
	} );

	it( 'provides og_image attribute accessor', function (): void {
		$page = TestPage::create( [
			'title'          => 'Test Page',
			'featured_image' => 'https://example.com/featured.jpg',
		] );

		expect( $page->og_image )->toBe( 'https://example.com/featured.jpg' );
	} );

} );

describe( 'HasSeo Trait - Focus Keyword', function (): void {

	it( 'provides focus_keyword attribute accessor', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [
			'focus_keyword' => 'test keyword',
		] );

		$page->refresh();

		expect( $page->focus_keyword )->toBe( 'test keyword' );
	} );

	it( 'can set focus keyword', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->setFocusKeyword( 'new keyword' );

		$page->refresh();

		expect( $page->seoMeta->focus_keyword )->toBe( 'new keyword' );
	} );

	it( 'returns null when no focus keyword is set', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		expect( $page->focus_keyword )->toBeNull();
	} );

} );

describe( 'HasSeo Trait - Indexing and Following', function (): void {

	it( 'returns true for shouldBeIndexed when no_index is false', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [ 'no_index' => false ] );
		$page->refresh();

		expect( $page->shouldBeIndexed() )->toBeTrue();
	} );

	it( 'returns false for shouldBeIndexed when no_index is true', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [ 'no_index' => true ] );
		$page->refresh();

		expect( $page->shouldBeIndexed() )->toBeFalse();
	} );

	it( 'returns true for shouldBeFollowed when no_follow is false', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [ 'no_follow' => false ] );
		$page->refresh();

		expect( $page->shouldBeFollowed() )->toBeTrue();
	} );

	it( 'returns false for shouldBeFollowed when no_follow is true', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [ 'no_follow' => true ] );
		$page->refresh();

		expect( $page->shouldBeFollowed() )->toBeFalse();
	} );

	it( 'defaults to indexable when no seo meta exists', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		expect( $page->shouldBeIndexed() )->toBeTrue();
	} );

	it( 'defaults to followable when no seo meta exists', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		expect( $page->shouldBeFollowed() )->toBeTrue();
	} );

} );

describe( 'HasSeo Trait - Sitemap', function (): void {

	it( 'returns true for shouldBeInSitemap when not excluded and indexable', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [
			'exclude_from_sitemap' => false,
			'no_index'             => false,
		] );

		$page->refresh();

		expect( $page->shouldBeInSitemap() )->toBeTrue();
	} );

	it( 'returns false for shouldBeInSitemap when excluded', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [
			'exclude_from_sitemap' => true,
			'no_index'             => false,
		] );

		$page->refresh();

		expect( $page->shouldBeInSitemap() )->toBeFalse();
	} );

	it( 'returns false for shouldBeInSitemap when not indexable', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [
			'exclude_from_sitemap' => false,
			'no_index'             => true,
		] );

		$page->refresh();

		expect( $page->shouldBeInSitemap() )->toBeFalse();
	} );

	it( 'returns sitemap priority from seo meta', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [
			'sitemap_priority' => 0.8,
		] );

		$page->refresh();

		expect( $page->getSitemapPriority() )->toBe( 0.8 );
	} );

	it( 'returns default sitemap priority when not set', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		config( [ 'seo.sitemap.default_priority' => 0.5 ] );

		expect( $page->getSitemapPriority() )->toBe( 0.5 );
	} );

	it( 'returns sitemap changefreq from seo meta', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [
			'sitemap_changefreq' => 'daily',
		] );

		$page->refresh();

		expect( $page->getSitemapChangefreq() )->toBe( 'daily' );
	} );

	it( 'returns default sitemap changefreq when not set', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		config( [ 'seo.sitemap.default_frequency' => 'weekly' ] );

		expect( $page->getSitemapChangefreq() )->toBe( 'weekly' );
	} );

} );

describe( 'HasSeo Trait - Robots Meta', function (): void {

	it( 'returns robots meta from seo meta', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [
			'no_index'  => true,
			'no_follow' => true,
		] );

		$page->refresh();

		expect( $page->robots_meta )->toBe( 'noindex, nofollow' );
	} );

	it( 'returns default robots when no seo meta exists', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		config( [ 'seo.defaults.robots' => 'index, follow' ] );

		expect( $page->robots_meta )->toBe( 'index, follow' );
	} );

} );

describe( 'HasSeo Trait - getSeoData', function (): void {

	it( 'returns complete seo data array', function (): void {
		$page = TestPage::create( [
			'title'   => 'Test Page',
			'slug'    => 'test-page',
			'content' => 'This is the page content.',
		] );

		$page->seoMeta()->create( [
			'meta_title'       => 'Custom SEO Title',
			'meta_description' => 'Custom SEO Description',
			'og_type'          => 'article',
			'focus_keyword'    => 'test keyword',
		] );

		$page->refresh();

		$seoData = $page->getSeoData();

		expect( $seoData )->toBeArray()
			->and( $seoData['title'] )->toBe( 'Custom SEO Title' )
			->and( $seoData['description'] )->toBe( 'Custom SEO Description' )
			->and( $seoData['focus_keyword'] )->toBe( 'test keyword' )
			->and( $seoData['open_graph'] )->toBeArray()
			->and( $seoData['open_graph']['type'] )->toBe( 'article' )
			->and( $seoData['twitter'] )->toBeArray()
			->and( $seoData['schema'] )->toBeArray()
			->and( $seoData['sitemap'] )->toBeArray();
	} );

	it( 'returns sitemap data in getSeoData', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [
			'sitemap_priority'   => 0.9,
			'sitemap_changefreq' => 'daily',
		] );

		$page->refresh();

		$seoData = $page->getSeoData();

		expect( $seoData['sitemap']['priority'] )->toBe( 0.9 )
			->and( $seoData['sitemap']['changefreq'] )->toBe( 'daily' )
			->and( $seoData['sitemap']['include'] )->toBeTrue();
	} );

} );

describe( 'HasSeo Trait - Hreflang', function (): void {

	it( 'returns hreflang from seo meta', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$hreflang = [
			'en' => 'https://example.com/en/page',
			'es' => 'https://example.com/es/page',
		];

		$page->seoMeta()->create( [
			'hreflang' => $hreflang,
		] );

		$page->refresh();

		expect( $page->hreflang )->toBe( $hreflang );
	} );

	it( 'can set hreflang', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$hreflang = [
			'en' => 'https://example.com/en/page',
			'fr' => 'https://example.com/fr/page',
		];

		$page->setHreflang( $hreflang );

		$page->refresh();

		expect( $page->seoMeta->hreflang )->toBe( $hreflang );
	} );

	it( 'returns empty array when no hreflang is set', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		expect( $page->hreflang )->toBe( [] );
	} );

} );

describe( 'HasSeo Trait - Schema', function (): void {

	it( 'returns schema type from seo meta', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->seoMeta()->create( [
			'schema_type' => 'Article',
		] );

		$page->refresh();

		expect( $page->getSchemaType() )->toBe( 'Article' );
	} );

	it( 'can set schema type', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		$page->setSchemaType( 'WebPage' );

		$page->refresh();

		expect( $page->seoMeta->schema_type )->toBe( 'WebPage' );
	} );

	it( 'returns null when no schema type is set', function (): void {
		$page = TestPage::create( [ 'title' => 'Test Page' ] );

		expect( $page->getSchemaType() )->toBeNull();
	} );

} );

describe( 'HasSeo Trait - Scopes', function (): void {

	beforeEach( function (): void {
		// Create test pages with different SEO settings
		$page1 = TestPage::create( [ 'title' => 'Page 1' ] );
		$page1->seoMeta()->create( [
			'no_index'             => false,
			'exclude_from_sitemap' => false,
			'focus_keyword'        => 'keyword one',
		] );

		$page2 = TestPage::create( [ 'title' => 'Page 2' ] );
		$page2->seoMeta()->create( [
			'no_index'             => true,
			'exclude_from_sitemap' => false,
			'focus_keyword'        => 'keyword two',
		] );

		$page3 = TestPage::create( [ 'title' => 'Page 3' ] );
		$page3->seoMeta()->create( [
			'no_index'             => false,
			'exclude_from_sitemap' => true,
			'focus_keyword'        => 'keyword one',
		] );

		TestPage::create( [ 'title' => 'Page 4 - No SEO Meta' ] );
	} );

	it( 'filters pages for sitemap correctly', function (): void {
		$pages = TestPage::forSitemap()->get();

		// Should include pages without seoMeta or with seoMeta where both exclude_from_sitemap and no_index are false
		expect( $pages )->toHaveCount( 2 );
		expect( $pages->pluck( 'title' )->toArray() )->toContain( 'Page 1', 'Page 4 - No SEO Meta' );
	} );

	it( 'filters pages with specific focus keyword', function (): void {
		$pages = TestPage::withFocusKeyword( 'keyword one' )->get();

		expect( $pages )->toHaveCount( 2 )
			->and( $pages->pluck( 'title' )->toArray() )->toContain( 'Page 1', 'Page 3' );
	} );

} );
