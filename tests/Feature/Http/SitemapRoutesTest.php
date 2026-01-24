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

use ArtisanPackUI\SEO\Models\SitemapEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

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
