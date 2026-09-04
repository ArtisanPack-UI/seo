<?php

/**
 * GenerateSitemapCommand Tests.
 *
 * Verifies the sitemap generation command primes the cache with fresh
 * content instead of leaving stale entries behind.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Services\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	config( [
		'seo.sitemap.cache_enabled' => true,
		'seo.cache.prefix'          => 'seo',
	] );
} );

it( 'clears stale cached sitemap XML before regenerating', function (): void {
	$service   = app( SitemapService::class );
	$outputDir = sys_get_temp_dir() . '/seo-sitemap-' . uniqid();

	SitemapEntry::create( [
		'sitemapable_type' => 'App\\Models\\Page',
		'sitemapable_id'   => 1,
		'url'              => 'https://example.com/original',
		'type'             => 'page',
	] );

	// Prime the cache with the original snapshot.
	$service->generate( 'page' );
	expect( Cache::get( 'seo:sitemap:standard:page' ) )->toContain( 'https://example.com/original' );

	// Add a fresh entry directly - the cache is now stale.
	SitemapEntry::create( [
		'sitemapable_type' => 'App\\Models\\Page',
		'sitemapable_id'   => 2,
		'url'              => 'https://example.com/added',
		'type'             => 'page',
	] );

	try {
		$this->artisan( 'seo:generate-sitemap', [ '--output' => $outputDir ] )->assertSuccessful();

		// The cache must reflect the current state, not the pre-run snapshot.
		$primed = Cache::get( 'seo:sitemap:standard:page' );
		expect( $primed )
			->toBeString()
			->toContain( 'https://example.com/original' )
			->toContain( 'https://example.com/added' );
	} finally {
		if ( is_dir( $outputDir ) ) {
			array_map( 'unlink', glob( $outputDir . '/*' ) ?: [] );
			rmdir( $outputDir );
		}
	}
} );

it( 'leaves the cache untouched when --no-cache is set', function (): void {
	$outputDir = sys_get_temp_dir() . '/seo-sitemap-' . uniqid();

	SitemapEntry::create( [
		'sitemapable_type' => 'App\\Models\\Page',
		'sitemapable_id'   => 1,
		'url'              => 'https://example.com/kept',
		'type'             => 'page',
	] );

	Cache::put( 'seo:sitemap:standard:page', '<sentinel/>', 3600 );

	try {
		$this->artisan( 'seo:generate-sitemap', [
			'--no-cache' => true,
			'--output'   => $outputDir,
		] )->assertSuccessful();

		// Without cache use, the command must not disturb existing entries so
		// callers who intentionally set --no-cache do not incur a cold cache.
		expect( Cache::get( 'seo:sitemap:standard:page' ) )->toBe( '<sentinel/>' );
	} finally {
		if ( is_dir( $outputDir ) ) {
			array_map( 'unlink', glob( $outputDir . '/*' ) ?: [] );
			rmdir( $outputDir );
		}
	}
} );
