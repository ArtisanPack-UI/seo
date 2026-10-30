<?php

/**
 * Sitemap Routes Tests.
 *
 * Feature tests for sitemap HTTP routes.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Collection;

uses( RefreshDatabase::class );

class SitemapRoutesTestCustomProvider implements SitemapProviderContract
{
	public function getUrls(): Collection
	{
		return collect( [
			[ 'loc' => 'https://example.com/custom/1' ],
			[ 'loc' => 'https://example.com/custom/2' ],
		] );
	}

	public function getChangeFrequency(): string
	{
		return 'weekly';
	}

	public function getPriority(): float
	{
		return 0.5;
	}

	public function getType(): string
	{
		return 'custom';
	}
}

class SitemapRoutesTestEmptyProvider extends SitemapRoutesTestCustomProvider
{
	public function getUrls(): Collection
	{
		return collect();
	}

	public function getType(): string
	{
		return 'empty';
	}
}

/**
 * Create indexable sitemap entries of a type.
 *
 * @param  string  $type   The sitemap type.
 * @param  int     $count  How many entries to create.
 *
 * @return array<int, string> The created URLs.
 */
function createSitemapRouteEntries( string $type, int $count ): array
{
	$urls = [];

	for ( $i = 1; $i <= $count; $i++ ) {
		$urls[] = "https://example.com/{$type}/{$i}";

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\' . $type,
			'sitemapable_id'   => $i,
			'url'              => "https://example.com/{$type}/{$i}",
			'type'             => $type,
		] );
	}

	return $urls;
}

/**
 * Collect every URL listed by the children of a sitemap index.
 *
 * @param  object  $test  The running test.
 *
 * @return array<int, string>
 */
function getUrlsFromSitemapChildren( object $test ): array
{
	$urls = [];

	foreach ( getSitemapIndexLocs( $test->get( '/sitemap.xml' )->assertStatus( 200 )->getContent() ) as $loc ) {
		$child = simplexml_load_string( $test->get( parse_url( $loc, PHP_URL_PATH ) )->assertStatus( 200 )->getContent() );

		foreach ( $child->url as $url ) {
			$urls[] = (string) $url->loc;
		}
	}

	return $urls;
}

/**
 * Extract every `<loc>` from a sitemap index document.
 *
 * @param  string  $xml  The sitemap index XML.
 *
 * @return array<int, string>
 */
function getSitemapIndexLocs( string $xml ): array
{
	$document = simplexml_load_string( $xml );

	expect( $document )->not->toBeFalse()
		->and( $document->getName() )->toBe( 'sitemapindex' );

	$locs = [];
	foreach ( $document->sitemap as $sitemap ) {
		$locs[] = (string) $sitemap->loc;
	}

	return $locs;
}

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
	config( [ 'app.url' => 'https://example.com' ] );
	config( [ 'seo.sitemap.route_enabled' => true ] );
	config( [ 'seo.sitemap.cache_enabled' => false ] );
} );

describe( 'Sitemap Routes', function (): void {

	it( 'serves main sitemap at configured route', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$response = $this->get( '/sitemap.xml' );

		$response->assertStatus( 200 )
			->assertHeader( 'Content-Type', 'application/xml; charset=UTF-8' );

		$content = $response->getContent();
		expect( $content )->toContain( '<?xml version="1.0" encoding="UTF-8"?>' )
			->and( $content )->toContain( '<urlset' )
			->and( $content )->toContain( 'https://example.com/page-1' );
	} );

	it( 'includes X-Robots-Tag noindex header', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$response = $this->get( '/sitemap.xml' );

		$response->assertStatus( 200 )
			->assertHeader( 'X-Robots-Tag', 'noindex' );
	} );

	it( 'serves sitemap index when multiple types exist', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Post',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/post-1',
			'type'             => 'post',
		] );

		$response = $this->get( '/sitemap.xml' );

		$response->assertStatus( 200 );

		$content = $response->getContent();
		expect( $content )->toContain( '<sitemapindex' );
	} );

	it( 'serves every child sitemap listed in the index for multiple types', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Post',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/post-1',
			'type'             => 'post',
		] );

		$locs = getSitemapIndexLocs( $this->get( '/sitemap.xml' )->assertStatus( 200 )->getContent() );

		expect( $locs )->toContain( 'https://example.com/sitemap-page-1.xml' )
			->and( $locs )->toContain( 'https://example.com/sitemap-post-1.xml' )
			->and( $locs )->not->toContain( 'https://example.com/sitemap.xml' );

		foreach ( $locs as $loc ) {
			$child = $this->get( parse_url( $loc, PHP_URL_PATH ) );

			$child->assertStatus( 200 );
			expect( $child->getContent() )->toContain( '<urlset' );
		}
	} );

	it( 'serves every child sitemap listed in the index for a paginated type', function (): void {
		config( [ 'seo.sitemap.max_urls_per_file' => 2 ] );

		for ( $i = 1; $i <= 5; $i++ ) {
			SitemapEntry::create( [
				'sitemapable_type' => 'App\\Models\\Page',
				'sitemapable_id'   => $i,
				'url'              => "https://example.com/page-{$i}",
				'type'             => 'page',
			] );
		}

		$locs = getSitemapIndexLocs( $this->get( '/sitemap.xml' )->assertStatus( 200 )->getContent() );

		expect( $locs )->toContain( 'https://example.com/sitemap-page-1.xml' )
			->and( $locs )->toContain( 'https://example.com/sitemap-page-3.xml' )
			->and( $locs )->not->toContain( 'https://example.com/sitemap-1.xml' )
			->and( $locs )->not->toContain( 'https://example.com/sitemap.xml' );

		foreach ( $locs as $loc ) {
			$child = $this->get( parse_url( $loc, PHP_URL_PATH ) );

			$child->assertStatus( 200 );
			expect( $child->getContent() )->toContain( '<urlset' );
		}
	} );

	it( 'serves every child sitemap listed in the index for a registered provider', function (): void {
		config( [ 'seo.sitemap.providers' => [ 'custom' => SitemapRoutesTestCustomProvider::class ] ] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$locs = getSitemapIndexLocs( $this->get( '/sitemap.xml' )->assertStatus( 200 )->getContent() );

		expect( $locs )->toContain( 'https://example.com/sitemap-custom-1.xml' )
			->and( $locs )->not->toContain( 'https://example.com/sitemap-custom.xml' );

		foreach ( $locs as $loc ) {
			$child = $this->get( parse_url( $loc, PHP_URL_PATH ) );

			$child->assertStatus( 200 );
			expect( $child->getContent() )->toContain( '<urlset' );
		}
	} );

	it( 'serves paginated sitemap for type', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$response = $this->get( '/sitemap-page-1.xml' );

		$response->assertStatus( 200 );

		$content = $response->getContent();
		expect( $content )->toContain( '<urlset' )
			->and( $content )->toContain( 'https://example.com/page-1' );
	} );

	it( 'serves paginated main sitemap', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$response = $this->get( '/sitemap-1.xml' );

		$response->assertStatus( 200 );

		$content = $response->getContent();
		expect( $content )->toContain( '<urlset' );
	} );

	it( 'includes cache control header', function (): void {
		config( [ 'seo.sitemap.cache_ttl' => 7200 ] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$response = $this->get( '/sitemap.xml' );

		$response->assertStatus( 200 );

		// Check that cache header contains the expected values
		$cacheControl = $response->headers->get( 'Cache-Control' );
		expect( $cacheControl )->toContain( 'max-age=7200' )
			->and( $cacheControl )->toContain( 'public' );
	} );

} );

describe( 'Image Sitemap Routes', function (): void {

	it( 'returns 404 for image sitemap when disabled', function (): void {
		config( [ 'seo.sitemap.types.image' => false ] );

		$response = $this->get( '/sitemap-images.xml' );

		$response->assertStatus( 404 );
	} );

} );

describe( 'Video Sitemap Routes', function (): void {

	it( 'returns 404 for video sitemap when disabled', function (): void {
		config( [ 'seo.sitemap.types.video' => false ] );

		$response = $this->get( '/sitemap-videos.xml' );

		$response->assertStatus( 404 );
	} );

} );

describe( 'News Sitemap Routes', function (): void {

	it( 'returns 404 for news sitemap when disabled', function (): void {
		config( [ 'seo.sitemap.types.news' => false ] );

		$response = $this->get( '/sitemap-news.xml' );

		$response->assertStatus( 404 );
	} );

} );

describe( 'Sitemap Index Coverage', function (): void {

	it( 'lists every indexable URL in exactly one child across pages and types', function (): void {
		config( [ 'seo.sitemap.max_urls_per_file' => 2 ] );

		$urls = [
			...createSitemapRouteEntries( 'page', 5 ),
			...createSitemapRouteEntries( 'post', 3 ),
		];

		expect( getUrlsFromSitemapChildren( $this ) )->toEqualCanonicalizing( $urls );
	} );

	it( 'serves the child for a snake_case type', function (): void {
		$urls = [
			...createSitemapRouteEntries( 'page', 1 ),
			...createSitemapRouteEntries( 'blog_post', 2 ),
		];

		expect( getUrlsFromSitemapChildren( $this ) )->toEqualCanonicalizing( $urls );
	} );

	it( 'keeps stored entries when a provider serves their type', function (): void {
		config( [ 'seo.sitemap.providers' => [ 'custom' => SitemapRoutesTestCustomProvider::class ] ] );

		$urls = createSitemapRouteEntries( 'custom', 1 );

		createSitemapRouteEntries( 'page', 1 );

		$content = $this->get( '/sitemap-custom-1.xml' )->assertStatus( 200 )->getContent();

		expect( $content )->toContain( $urls[0] )
			->and( $content )->toContain( 'https://example.com/custom/2' );
	} );

	it( 'lists a URL once when a provider repeats a stored entry', function (): void {
		config( [ 'seo.sitemap.providers' => [ 'custom' => SitemapRoutesTestCustomProvider::class ] ] );

		createSitemapRouteEntries( 'custom', 1 );

		$content = $this->get( '/sitemap-custom-1.xml' )->assertStatus( 200 )->getContent();

		expect( substr_count( $content, '<loc>https://example.com/custom/1</loc>' ) )->toBe( 1 );
	} );

	it( 'only lists provider URLs on the first page of a shared type', function (): void {
		config( [
			'seo.sitemap.max_urls_per_file' => 1,
			'seo.sitemap.providers'         => [ 'custom' => SitemapRoutesTestCustomProvider::class ],
		] );

		createSitemapRouteEntries( 'custom', 2 );

		expect( $this->get( '/sitemap-custom-2.xml' )->assertStatus( 200 )->getContent() )
			->toContain( 'https://example.com/custom/' )
			->not->toContain( '<loc>https://example.com/custom/1</loc>' );
	} );

	it( 'keeps a plain urlset for a single-type, single-page site', function (): void {
		createSitemapRouteEntries( 'page', 2 );

		expect( $this->get( '/sitemap.xml' )->assertStatus( 200 )->getContent() )
			->toContain( '<urlset' )
			->not->toContain( '<sitemapindex' );
	} );

	it( 'still serves the all-types sitemap for URLs already submitted', function (): void {
		$urls = [
			...createSitemapRouteEntries( 'page', 1 ),
			...createSitemapRouteEntries( 'post', 1 ),
		];

		$content = $this->get( '/sitemap-1.xml' )->assertStatus( 200 )->getContent();

		expect( $content )->toContain( $urls[0] )
			->and( $content )->toContain( $urls[1] );
	} );

} );

describe( 'Empty Sitemap Children', function (): void {

	it( 'returns 404 for a type with no entries', function (): void {
		createSitemapRouteEntries( 'page', 1 );

		$this->get( '/sitemap-nonsense-1.xml' )->assertNotFound();
	} );

	it( 'returns 404 for a page past the last one', function (): void {
		createSitemapRouteEntries( 'page', 1 );

		$this->get( '/sitemap-page-2.xml' )->assertNotFound();
	} );

	it( 'returns 404 for a type with only non-indexable entries', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/hidden',
			'type'             => 'hidden',
			'is_indexable'     => false,
		] );

		$this->get( '/sitemap-hidden-1.xml' )->assertNotFound();
	} );

	it( 'returns an empty set for a provider with no URLs', function (): void {
		config( [ 'seo.sitemap.providers' => [ 'empty' => SitemapRoutesTestEmptyProvider::class ] ] );

		expect( $this->get( '/sitemap-empty-1.xml' )->assertStatus( 200 )->getContent() )->toContain( '<urlset' );
	} );

	it( 'returns 404 for a provider page past its stored entries', function (): void {
		config( [ 'seo.sitemap.providers' => [ 'custom' => SitemapRoutesTestCustomProvider::class ] ] );

		$this->get( '/sitemap-custom-2.xml' )->assertNotFound();
	} );

	it( 'keeps a stored type that shares a name with a specialized sitemap', function (): void {
		config( [ 'seo.sitemap.types.news' => true ] );

		app( 'router' )->setRoutes( new RouteCollection() );
		require __DIR__ . '/../../../routes/web.php';

		$urls = createSitemapRouteEntries( 'news', 1 );

		expect( $this->get( '/sitemap-news-1.xml' )->assertStatus( 200 )->getContent() )->toContain( $urls[0] );
	} );

	it( 'lists a provider whose stored rows are all non-indexable', function (): void {
		config( [ 'seo.sitemap.providers' => [ 'custom' => SitemapRoutesTestCustomProvider::class ] ] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/hidden',
			'type'             => 'custom',
			'is_indexable'     => false,
		] );

		expect( getSitemapIndexLocs( $this->get( '/sitemap.xml' )->assertStatus( 200 )->getContent() ) )
			->toContain( 'https://example.com/sitemap-custom-1.xml' );
	} );

	it( 'routes a paged image sitemap to the image sitemap, not a type', function (): void {
		config( [ 'seo.sitemap.types.image' => true ] );

		app( 'router' )->setRoutes( new RouteCollection() );
		require __DIR__ . '/../../../routes/web.php';

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'images'           => [ [ 'loc' => 'https://example.com/image.jpg' ] ],
		] );

		expect( $this->get( '/sitemap-images-1.xml' )->assertStatus( 200 )->getContent() )
			->toContain( 'https://example.com/image.jpg' );
	} );

} );
