<?php

/**
 * SitemapGenerator Tests.
 *
 * Unit tests for the SitemapGenerator class.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Sitemap\Generators\SitemapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

describe( 'SitemapGenerator', function (): void {

	it( 'generates empty sitemap when no entries exist', function (): void {
		$generator = new SitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<?xml version="1.0" encoding="UTF-8"?>' )
			->and( $xml )->toContain( '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' )
			->and( $xml )->toMatch( '/<\/urlset>|<urlset[^>]*\/>/' )
			->and( $xml )->not->toContain( '<url>' );
	} );

	it( 'generates sitemap with entries', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'priority'         => 0.8,
			'changefreq'       => 'daily',
		] );

		$generator = new SitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<url>' )
			->and( $xml )->toContain( '<loc>https://example.com/page-1</loc>' )
			->and( $xml )->toContain( '<priority>0.8</priority>' )
			->and( $xml )->toContain( '<changefreq>daily</changefreq>' );
	} );

	it( 'filters entries by type', function (): void {
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

		$generator = new SitemapGenerator();
		$xml       = $generator->generate( 'page' );

		expect( $xml )->toContain( 'https://example.com/page-1' )
			->and( $xml )->not->toContain( 'https://example.com/post-1' );
	} );

	it( 'excludes non-indexable entries', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'is_indexable'     => true,
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 2,
			'url'              => 'https://example.com/page-2',
			'type'             => 'page',
			'is_indexable'     => false,
		] );

		$generator = new SitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( 'https://example.com/page-1' )
			->and( $xml )->not->toContain( 'https://example.com/page-2' );
	} );

	it( 'includes lastmod when available', function (): void {
		$lastmod = now()->subDay();

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'last_modified'    => $lastmod,
		] );

		$generator = new SitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<lastmod>' );
	} );

	it( 'paginates results correctly', function (): void {
		// Create more entries than one page
		for ( $i = 1; $i <= 5; $i++ ) {
			SitemapEntry::create( [
				'sitemapable_type' => 'App\\Models\\Page',
				'sitemapable_id'   => $i,
				'url'              => "https://example.com/page-{$i}",
				'type'             => 'page',
			] );
		}

		$generator = new SitemapGenerator( 2 ); // 2 URLs per page

		$page1 = $generator->generate( null, 1 );
		$page2 = $generator->generate( null, 2 );
		$page3 = $generator->generate( null, 3 );

		expect( substr_count( $page1, '<url>' ) )->toBe( 2 )
			->and( substr_count( $page2, '<url>' ) )->toBe( 2 )
			->and( substr_count( $page3, '<url>' ) )->toBe( 1 );
	} );

	it( 'calculates total pages correctly', function (): void {
		for ( $i = 1; $i <= 5; $i++ ) {
			SitemapEntry::create( [
				'sitemapable_type' => 'App\\Models\\Page',
				'sitemapable_id'   => $i,
				'url'              => "https://example.com/page-{$i}",
				'type'             => 'page',
			] );
		}

		$generator = new SitemapGenerator( 2 );

		expect( $generator->getTotalPages() )->toBe( 3 )
			->and( $generator->getTotalPages( 'page' ) )->toBe( 3 );
	} );

	it( 'generates from provider', function (): void {
		$provider = Mockery::mock( SitemapProviderContract::class );
		$provider->shouldReceive( 'getUrls' )->andReturn( collect( [
			[
				'loc'        => 'https://example.com/custom-1',
				'lastmod'    => now()->toIso8601String(),
				'changefreq' => 'weekly',
				'priority'   => 0.7,
			],
		] ) );
		$provider->shouldReceive( 'getChangeFrequency' )->andReturn( 'weekly' );
		$provider->shouldReceive( 'getPriority' )->andReturn( 0.5 );

		$generator = new SitemapGenerator();
		$xml       = $generator->generateFromProvider( $provider );

		expect( $xml )->toContain( 'https://example.com/custom-1' )
			->and( $xml )->toContain( '<changefreq>weekly</changefreq>' )
			->and( $xml )->toContain( '<priority>0.7</priority>' );
	} );

	it( 'returns max urls setting', function (): void {
		$generator = new SitemapGenerator( 5000 );

		expect( $generator->getMaxUrls() )->toBe( 5000 );
	} );

} );
