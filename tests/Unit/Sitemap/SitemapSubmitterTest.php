<?php

/**
 * SitemapSubmitter Tests.
 *
 * Unit tests for the SitemapSubmitter class.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Sitemap\SitemapSubmitter;
use Illuminate\Support\Facades\Http;

beforeEach( function (): void {
	config( [ 'app.url' => 'https://example.com' ] );
	config( [ 'seo.sitemap.route_path' => 'sitemap.xml' ] );
} );

describe( 'SitemapSubmitter', function (): void {

	it( 'creates instance with default sitemap URL', function (): void {
		$submitter = new SitemapSubmitter();

		expect( $submitter->getSitemapUrl() )->toBe( 'https://example.com/sitemap.xml' );
	} );

	it( 'creates instance with custom sitemap URL', function (): void {
		$submitter = new SitemapSubmitter( 'https://custom.com/custom-sitemap.xml' );

		expect( $submitter->getSitemapUrl() )->toBe( 'https://custom.com/custom-sitemap.xml' );
	} );

	it( 'has default search engines', function (): void {
		$submitter = new SitemapSubmitter();
		$engines   = $submitter->getSearchEngines();

		// Note: Google's ping URL was deprecated in 2023 and is no longer included by default.
		// Users should submit to Google via Search Console or robots.txt instead.
		expect( $engines )->toHaveKey( 'bing' )
			->and( $engines )->not->toHaveKey( 'google' );
	} );

	it( 'allows adding custom search engine', function (): void {
		$submitter = new SitemapSubmitter();
		$submitter->addSearchEngine( 'custom', 'https://custom.com/ping?sitemap=%s' );

		expect( $submitter->getSearchEngines() )->toHaveKey( 'custom' );
	} );

	it( 'allows removing search engine', function (): void {
		$submitter = new SitemapSubmitter();
		$submitter->removeSearchEngine( 'bing' );

		expect( $submitter->getSearchEngines() )->not->toHaveKey( 'bing' );
	} );

	it( 'allows setting sitemap URL fluently', function (): void {
		$submitter = new SitemapSubmitter();
		$result    = $submitter->setSitemapUrl( 'https://new.com/sitemap.xml' );

		expect( $result )->toBeInstanceOf( SitemapSubmitter::class )
			->and( $submitter->getSitemapUrl() )->toBe( 'https://new.com/sitemap.xml' );
	} );

	it( 'submits to all search engines successfully', function (): void {
		Http::fake( [
			'www.bing.com/*' => Http::response( '', 200 ),
		] );

		$submitter = new SitemapSubmitter();
		$results   = $submitter->submit();

		expect( $results )->toHaveCount( 1 )
			->and( $submitter->allSuccessful() )->toBeTrue()
			->and( $submitter->anySuccessful() )->toBeTrue();
	} );

	it( 'handles failed submissions', function (): void {
		Http::fake( [
			'www.bing.com/*'   => Http::response( '', 200 ),
			'custom.com/*'     => Http::response( '', 500 ),
		] );

		$submitter = new SitemapSubmitter();
		$submitter->addSearchEngine( 'custom', 'https://custom.com/ping?sitemap=%s' );
		$results = $submitter->submit();

		expect( $submitter->allSuccessful() )->toBeFalse()
			->and( $submitter->anySuccessful() )->toBeTrue()
			->and( $results['bing']['success'] )->toBeTrue()
			->and( $results['custom']['success'] )->toBeFalse();
	} );

	it( 'handles connection errors', function (): void {
		Http::fake( function (): void {
			throw new Exception( 'Connection failed' );
		} );

		$submitter = new SitemapSubmitter();

		// Should not throw exception
		$results = $submitter->submit();

		expect( $submitter->allSuccessful() )->toBeFalse()
			->and( $results->every( fn ( $r ) => false === $r['success'] ) )->toBeTrue();
	} );

	it( 'submits to specific search engine', function (): void {
		Http::fake( [
			'www.bing.com/*' => Http::response( '', 200 ),
		] );

		$submitter = new SitemapSubmitter();
		$result    = $submitter->submitTo( 'bing' );

		expect( $result )->not->toBeNull()
			->and( $result['success'] )->toBeTrue();

		Http::assertSentCount( 1 );
	} );

	it( 'returns null for unknown search engine', function (): void {
		$submitter = new SitemapSubmitter();
		$result    = $submitter->submitTo( 'unknown' );

		expect( $result )->toBeNull();
	} );

	it( 'tracks response times', function (): void {
		Http::fake( [
			'*' => Http::response( '', 200 ),
		] );

		$submitter = new SitemapSubmitter();
		$results   = $submitter->submit();

		foreach ( $results as $result ) {
			expect( $result )->toHaveKey( 'response_time' )
				->and( $result['response_time'] )->toBeGreaterThanOrEqual( 0 );
		}
	} );

	it( 'includes status codes in results', function (): void {
		Http::fake( [
			'www.bing.com/*' => Http::response( '', 200 ),
			'custom.com/*'   => Http::response( '', 404 ),
		] );

		$submitter = new SitemapSubmitter();
		$submitter->addSearchEngine( 'custom', 'https://custom.com/ping?sitemap=%s' );
		$results = $submitter->submit();

		expect( $results['bing']['status_code'] )->toBe( 200 )
			->and( $results['custom']['status_code'] )->toBe( 404 );
	} );

	it( 'gets failed submissions', function (): void {
		Http::fake( [
			'www.bing.com/*' => Http::response( '', 200 ),
			'custom.com/*'   => Http::response( '', 500 ),
		] );

		$submitter = new SitemapSubmitter();
		$submitter->addSearchEngine( 'custom', 'https://custom.com/ping?sitemap=%s' );
		$submitter->submit();

		$failed = $submitter->getFailedSubmissions();

		expect( $failed )->toHaveCount( 1 )
			->and( $failed )->toHaveKey( 'custom' );
	} );

	it( 'gets successful submissions', function (): void {
		Http::fake( [
			'www.bing.com/*' => Http::response( '', 200 ),
			'custom.com/*'   => Http::response( '', 500 ),
		] );

		$submitter = new SitemapSubmitter();
		$submitter->addSearchEngine( 'custom', 'https://custom.com/ping?sitemap=%s' );
		$submitter->submit();

		$successful = $submitter->getSuccessfulSubmissions();

		expect( $successful )->toHaveCount( 1 )
			->and( $successful )->toHaveKey( 'bing' );
	} );

	it( 'returns empty results when not submitted', function (): void {
		$submitter = new SitemapSubmitter();

		expect( $submitter->getResults() )->toBeEmpty()
			->and( $submitter->allSuccessful() )->toBeFalse()
			->and( $submitter->anySuccessful() )->toBeFalse();
	} );

	it( 'encodes sitemap URL in ping request', function (): void {
		Http::fake( [
			'*' => Http::response( '', 200 ),
		] );

		$submitter = new SitemapSubmitter( 'https://example.com/sitemap.xml?param=value' );
		$submitter->submit();

		Http::assertSent( function ( $request ) {
			return str_contains( $request->url(), urlencode( 'https://example.com/sitemap.xml?param=value' ) );
		} );
	} );

} );
