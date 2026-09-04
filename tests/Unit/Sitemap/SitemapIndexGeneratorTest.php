<?php

/**
 * SitemapIndexGenerator Tests.
 *
 * Unit tests for the SitemapIndexGenerator class.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Sitemap\Generators\SitemapIndexGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
	config( [ 'app.url' => 'https://example.com' ] );
	config( [ 'seo.sitemap.route_path' => 'sitemap.xml' ] );
} );

describe( 'SitemapIndexGenerator', function (): void {

	it( 'generates sitemap index XML', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$generator = new SitemapIndexGenerator( 'https://example.com' );
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<?xml version="1.0" encoding="UTF-8"?>' )
			->and( $xml )->toContain( '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' )
			->and( $xml )->toContain( '</sitemapindex>' )
			->and( $xml )->toContain( '<sitemap>' )
			->and( $xml )->toContain( '<loc>' );
	} );

	it( 'includes sitemap for each type', function (): void {
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

		$generator = new SitemapIndexGenerator( 'https://example.com' );
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<loc>https://example.com/sitemap-page-1.xml</loc>' )
			->and( $xml )->toContain( '<loc>https://example.com/sitemap-post-1.xml</loc>' )
			->and( $xml )->not->toContain( '<loc>https://example.com/sitemap-page.xml</loc>' )
			->and( $xml )->not->toContain( '<loc>https://example.com/sitemap-post.xml</loc>' );
	} );

	it( 'includes the page number for every page of a typed sitemap', function (): void {
		for ( $i = 1; $i <= 5; $i++ ) {
			SitemapEntry::create( [
				'sitemapable_type' => 'App\\Models\\Page',
				'sitemapable_id'   => $i,
				'url'              => "https://example.com/page-{$i}",
				'type'             => 'page',
			] );
		}

		$generator = new SitemapIndexGenerator( 'https://example.com', 2 );
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<loc>https://example.com/sitemap-page-1.xml</loc>' )
			->and( $xml )->toContain( '<loc>https://example.com/sitemap-page-2.xml</loc>' )
			->and( $xml )->toContain( '<loc>https://example.com/sitemap-page-3.xml</loc>' )
			->and( $xml )->not->toContain( '<loc>https://example.com/sitemap-page.xml</loc>' );
	} );

	it( 'includes the page number for page one of the main sitemap', function (): void {
		for ( $i = 1; $i <= 3; $i++ ) {
			SitemapEntry::create( [
				'sitemapable_type' => 'App\\Models\\Page',
				'sitemapable_id'   => $i,
				'url'              => "https://example.com/page-{$i}",
				'type'             => 'page',
			] );
		}

		$generator = new SitemapIndexGenerator( 'https://example.com', 2 );
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<loc>https://example.com/sitemap-1.xml</loc>' )
			->and( $xml )->toContain( '<loc>https://example.com/sitemap-2.xml</loc>' )
			->and( $xml )->not->toContain( '<loc>https://example.com/sitemap.xml</loc>' );
	} );

	it( 'does not reference itself when the main sitemap fits on one page', function (): void {
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

		$generator = new SitemapIndexGenerator( 'https://example.com' );
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<loc>https://example.com/sitemap-1.xml</loc>' )
			->and( $xml )->not->toContain( '<loc>https://example.com/sitemap.xml</loc>' );
	} );

	it( 'includes the page number for provider sitemaps', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$provider = Mockery::mock( SitemapProviderContract::class );
		$provider->shouldReceive( 'getUrls' )->andReturn( collect( [
			[ 'loc' => 'https://example.com/custom-1' ],
		] ) );
		$provider->shouldReceive( 'getChangeFrequency' )->andReturn( 'weekly' );
		$provider->shouldReceive( 'getPriority' )->andReturn( 0.5 );
		$provider->shouldReceive( 'getType' )->andReturn( 'custom' );

		$generator = new SitemapIndexGenerator( 'https://example.com' );
		$generator->setProviders( collect( [ 'custom' => $provider ] ) );
		$xml = $generator->generate();

		expect( $xml )->toContain( '<loc>https://example.com/sitemap-custom-1.xml</loc>' )
			->and( $xml )->not->toContain( '<loc>https://example.com/sitemap-custom.xml</loc>' );
	} );

	it( 'checks if index is needed with multiple types', function (): void {
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

		$generator = new SitemapIndexGenerator( 'https://example.com' );

		expect( $generator->needsIndex() )->toBeTrue();
	} );

	it( 'does not need index with single type and single page', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$generator = new SitemapIndexGenerator( 'https://example.com', 10000 );

		expect( $generator->needsIndex() )->toBeFalse();
	} );

	it( 'needs index when a provider is registered even with a single database type', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$provider = Mockery::mock( SitemapProviderContract::class );

		$generator = new SitemapIndexGenerator( 'https://example.com', 10000 );
		$generator->setProviders( collect( [ 'custom' => $provider ] ) );

		expect( $generator->needsIndex() )->toBeTrue();
	} );

	it( 'needs index when pagination required', function (): void {
		// Create enough entries to require multiple pages
		for ( $i = 1; $i <= 5; $i++ ) {
			SitemapEntry::create( [
				'sitemapable_type' => 'App\\Models\\Page',
				'sitemapable_id'   => $i,
				'url'              => "https://example.com/page-{$i}",
				'type'             => 'page',
			] );
		}

		$generator = new SitemapIndexGenerator( 'https://example.com', 2 );

		expect( $generator->needsIndex() )->toBeTrue();
	} );

	it( 'includes lastmod in sitemap entries', function (): void {
		$lastmod = now()->subDay();

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'last_modified'    => $lastmod,
		] );

		$generator = new SitemapIndexGenerator( 'https://example.com' );
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<lastmod>' );
	} );

	it( 'includes image sitemap when enabled', function (): void {
		config( [ 'seo.sitemap.types.image' => true ] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'images'           => [ [ 'loc' => 'https://example.com/image.jpg' ] ],
		] );

		$generator = new SitemapIndexGenerator( 'https://example.com' );
		$xml       = $generator->generate();

		expect( $xml )->toContain( 'sitemap-images.xml' );
	} );

	it( 'includes video sitemap when enabled', function (): void {
		config( [ 'seo.sitemap.types.video' => true ] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'videos'           => [ [
				'thumbnail_loc' => 'https://example.com/thumb.jpg',
				'title'         => 'Test Video',
				'description'   => 'Test description',
			] ],
		] );

		$generator = new SitemapIndexGenerator( 'https://example.com' );
		$xml       = $generator->generate();

		expect( $xml )->toContain( 'sitemap-videos.xml' );
	} );

	it( 'includes news sitemap when enabled', function (): void {
		config( [ 'seo.sitemap.types.news' => true ] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Article',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/article-1',
			'type'             => 'article',
		] );

		$generator = new SitemapIndexGenerator( 'https://example.com' );
		$xml       = $generator->generate();

		expect( $xml )->toContain( 'sitemap-news.xml' );
	} );

} );
