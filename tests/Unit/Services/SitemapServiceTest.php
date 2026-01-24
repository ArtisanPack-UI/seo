<?php

/**
 * SitemapService Tests.
 *
 * Unit tests for the SitemapService class.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Services\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
	config( [ 'app.url' => 'https://example.com' ] );
	config( [ 'seo.sitemap.cache_enabled' => false ] ); // Disable cache for tests
} );

describe( 'SitemapService', function (): void {

	it( 'generates standard sitemap', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$service = new SitemapService();
		$xml     = $service->generate();

		expect( $xml )->toContain( '<?xml version="1.0" encoding="UTF-8"?>' )
			->and( $xml )->toContain( '<urlset' )
			->and( $xml )->toContain( 'https://example.com/page-1' );
	} );

	it( 'generates sitemap for specific type', function (): void {
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

		$service = new SitemapService();
		$xml     = $service->generate( 'page' );

		expect( $xml )->toContain( 'page-1' )
			->and( $xml )->not->toContain( 'post-1' );
	} );

	it( 'generates sitemap index', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$service = new SitemapService();
		$xml     = $service->generateIndex();

		expect( $xml )->toContain( '<sitemapindex' )
			->and( $xml )->toContain( '<sitemap>' );
	} );

	it( 'generates image sitemap', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'images'           => [ [ 'loc' => 'https://example.com/image.jpg' ] ],
		] );

		$service = new SitemapService();
		$xml     = $service->generateImages();

		expect( $xml )->toContain( 'xmlns:image' )
			->and( $xml )->toContain( '<image:image>' );
	} );

	it( 'generates video sitemap', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'videos'           => [ [
				'thumbnail_loc' => 'https://example.com/thumb.jpg',
				'title'         => 'Test',
				'description'   => 'Test desc',
			] ],
		] );

		$service = new SitemapService();
		$xml     = $service->generateVideos();

		expect( $xml )->toContain( 'xmlns:video' )
			->and( $xml )->toContain( '<video:video>' );
	} );

	it( 'generates news sitemap structure', function (): void {
		// Even without entries, the news sitemap should have proper XML structure
		$service = new SitemapService();
		$xml     = $service->generateNews();

		// Should contain the proper news sitemap namespace
		expect( $xml )->toContain( '<?xml version="1.0" encoding="UTF-8"?>' )
			->and( $xml )->toContain( 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"' )
			->and( $xml )->toContain( '<urlset' );
	} );

	it( 'caches sitemap when enabled', function (): void {
		config( [ 'seo.sitemap.cache_enabled' => true ] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$service = new SitemapService();
		$service->setCacheEnabled( true );

		// First call should cache
		$xml1 = $service->generate();

		// Create new entry
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 2,
			'url'              => 'https://example.com/page-2',
			'type'             => 'page',
		] );

		// Second call should return cached version
		$xml2 = $service->generate();

		expect( $xml1 )->toBe( $xml2 );
	} );

	it( 'clears cache', function (): void {
		config( [ 'seo.sitemap.cache_enabled' => true ] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$service = new SitemapService();
		$service->setCacheEnabled( true );

		// Cache the sitemap
		$xml1 = $service->generate();

		// Clear cache
		$service->clearCache();

		// Add new entry
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 2,
			'url'              => 'https://example.com/page-2',
			'type'             => 'page',
		] );

		// Should get fresh data
		$xml2 = $service->generate();

		expect( $xml2 )->toContain( 'page-2' );
	} );

	it( 'registers custom provider', function (): void {
		$provider = Mockery::mock( SitemapProviderContract::class );
		$provider->shouldReceive( 'getUrls' )->andReturn( collect( [
			[ 'loc' => 'https://example.com/custom-1' ],
		] ) );
		$provider->shouldReceive( 'getChangeFrequency' )->andReturn( 'weekly' );
		$provider->shouldReceive( 'getPriority' )->andReturn( 0.5 );
		$provider->shouldReceive( 'getType' )->andReturn( 'custom' );

		$service = new SitemapService();
		$service->registerProvider( 'custom', $provider );

		expect( $service->getProviders() )->toHaveKey( 'custom' );
	} );

	it( 'generates from registered provider', function (): void {
		$provider = Mockery::mock( SitemapProviderContract::class );
		$provider->shouldReceive( 'getUrls' )->andReturn( collect( [
			[ 'loc' => 'https://example.com/custom-1' ],
		] ) );
		$provider->shouldReceive( 'getChangeFrequency' )->andReturn( 'weekly' );
		$provider->shouldReceive( 'getPriority' )->andReturn( 0.5 );

		$service = new SitemapService();
		$service->registerProvider( 'custom', $provider );

		$xml = $service->generate( 'custom' );

		expect( $xml )->toContain( 'https://example.com/custom-1' );
	} );

	it( 'submits sitemap to search engines', function (): void {
		Http::fake( [
			'*' => Http::response( '', 200 ),
		] );

		$service = new SitemapService();
		$results = $service->submit();

		// Only Bing is included by default (Google's ping URL was deprecated in 2023)
		expect( $results )->toHaveCount( 1 );
	} );

	it( 'gets available types', function (): void {
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

		$service = new SitemapService();
		$types   = $service->getTypes();

		expect( $types )->toContain( 'page' )
			->and( $types )->toContain( 'post' );
	} );

	it( 'checks if sitemap index is needed', function (): void {
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

		$service = new SitemapService();

		expect( $service->needsIndex() )->toBeTrue();
	} );

	it( 'gets total pages for type', function (): void {
		for ( $i = 1; $i <= 5; $i++ ) {
			SitemapEntry::create( [
				'sitemapable_type' => 'App\\Models\\Page',
				'sitemapable_id'   => $i,
				'url'              => "https://example.com/page-{$i}",
				'type'             => 'page',
			] );
		}

		$service = new SitemapService();
		$service->setMaxUrls( 2 );

		expect( $service->getTotalPages( 'page' ) )->toBe( 3 );
	} );

	it( 'allows setting cache TTL', function (): void {
		$service = new SitemapService();
		$result  = $service->setCacheTtl( 7200 );

		expect( $result )->toBeInstanceOf( SitemapService::class );
	} );

	it( 'allows enabling and disabling cache', function (): void {
		$service = new SitemapService();

		$service->setCacheEnabled( true );
		expect( $service->isCacheEnabled() )->toBeTrue();

		$service->setCacheEnabled( false );
		expect( $service->isCacheEnabled() )->toBeFalse();
	} );

	it( 'allows setting max URLs', function (): void {
		$service = new SitemapService();
		$service->setMaxUrls( 5000 );

		expect( $service->getMaxUrls() )->toBe( 5000 );
	} );

	it( 'checks specialized sitemap status', function (): void {
		config( [ 'seo.sitemap.types.image' => true ] );
		config( [ 'seo.sitemap.types.video' => false ] );
		config( [ 'seo.sitemap.types.news' => true ] );

		$service = new SitemapService();

		expect( $service->isImageSitemapEnabled() )->toBeTrue()
			->and( $service->isVideoSitemapEnabled() )->toBeFalse()
			->and( $service->isNewsSitemapEnabled() )->toBeTrue()
			->and( $service->hasSpecializedSitemaps() )->toBeTrue();
	} );

} );
