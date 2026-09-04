<?php

/**
 * GenerateSitemapCommand Tests.
 *
 * Verifies the sitemap generation command primes the cache with fresh
 * content instead of leaving stale entries behind and that alternate
 * invocations preserve existing cache warmth.
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

uses( RefreshDatabase::class );

beforeEach( function (): void {
	config( [
		'seo.sitemap.cache_enabled' => true,
		'seo.cache.prefix'          => 'seo',
	] );

	$this->cleanupPaths = [];
} );

afterEach( function (): void {
	foreach ( $this->cleanupPaths ?? [] as $path ) {
		if ( is_dir( $path ) ) {
			array_map( 'unlink', glob( $path . '/*' ) ?: [] );
			rmdir( $path );
		}
	}
} );

/**
 * Reserve a temporary output directory that is cleaned up in `afterEach`.
 */
function tempSitemapOutputDir( object $test ): string
{
	$path                  = sys_get_temp_dir() . '/seo-sitemap-' . uniqid();
	$test->cleanupPaths[]  = $path;

	return $path;
}

it( 'clears stale cached sitemap XML before regenerating', function (): void {
	$service   = app( SitemapService::class );
	$outputDir = tempSitemapOutputDir( $this );

	SitemapEntry::create( [
		'sitemapable_type' => 'App\\Models\\Page',
		'sitemapable_id'   => 1,
		'url'              => 'https://example.com/original',
		'type'             => 'page',
	] );

	// Prime the cache with the original snapshot.
	$primed = $service->generate( 'page' );
	expect( $primed )->toContain( 'https://example.com/original' );

	// Add a fresh entry directly - the cache is now stale.
	SitemapEntry::create( [
		'sitemapable_type' => 'App\\Models\\Page',
		'sitemapable_id'   => 2,
		'url'              => 'https://example.com/added',
		'type'             => 'page',
	] );

	// A cache-only read would return the stale snapshot before the command runs.
	$staleRead = $service->generate( 'page' );
	expect( $staleRead )->not->toContain( 'https://example.com/added' );

	$this->artisan( 'seo:generate-sitemap', [ '--output' => $outputDir ] )->assertSuccessful();

	// The next call must see the current state, not the pre-run snapshot.
	$fresh = $service->generate( 'page' );
	expect( $fresh )
		->toContain( 'https://example.com/original' )
		->toContain( 'https://example.com/added' );
} );

it( 'leaves the cache untouched when --no-cache is set', function (): void {
	$service   = app( SitemapService::class );
	$outputDir = tempSitemapOutputDir( $this );

	SitemapEntry::create( [
		'sitemapable_type' => 'App\\Models\\Page',
		'sitemapable_id'   => 1,
		'url'              => 'https://example.com/kept',
		'type'             => 'page',
	] );

	// Prime the cache under the current generation.
	$service->generate( 'page' );

	// Insert a new entry without going through the observer so the primed
	// cache is intentionally stale — a --no-cache run must not bust it.
	SitemapEntry::create( [
		'sitemapable_type' => 'App\\Models\\Page',
		'sitemapable_id'   => 2,
		'url'              => 'https://example.com/uncached',
		'type'             => 'page',
	] );

	$this->artisan( 'seo:generate-sitemap', [
		'--no-cache' => true,
		'--output'   => $outputDir,
	] )->assertSuccessful();

	// The command's --no-cache flag flips the shared service singleton to
	// cacheEnabled=false; re-enable so the assertion actually reads through
	// the cache instead of regenerating fresh from the DB.
	$service->setCacheEnabled( true );

	// The primed snapshot should still be served because the command did
	// not bump the cache generation.
	$afterRun = $service->generate( 'page' );
	expect( $afterRun )
		->toContain( 'https://example.com/kept' )
		->not->toContain( 'https://example.com/uncached' );
} );

it( 'does not clear the cache on the statistics-only path', function (): void {
	$service = app( SitemapService::class );

	SitemapEntry::create( [
		'sitemapable_type' => 'App\\Models\\Page',
		'sitemapable_id'   => 1,
		'url'              => 'https://example.com/primed',
		'type'             => 'page',
	] );

	// Prime the cache.
	$service->generate( 'page' );

	// Add a fresh entry outside the observer so the cache is intentionally
	// stale — a stats-only run wipes a warm cache with no priming to
	// follow, so it must leave the generation counter alone.
	SitemapEntry::create( [
		'sitemapable_type' => 'App\\Models\\Page',
		'sitemapable_id'   => 2,
		'url'              => 'https://example.com/uncached-stats',
		'type'             => 'page',
	] );

	$this->artisan( 'seo:generate-sitemap' )->assertSuccessful();

	// The stats-only invocation must preserve the primed snapshot.
	$afterRun = $service->generate( 'page' );
	expect( $afterRun )
		->toContain( 'https://example.com/primed' )
		->not->toContain( 'https://example.com/uncached-stats' );
} );
