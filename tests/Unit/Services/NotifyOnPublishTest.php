<?php

/**
 * SitemapService::notifyOnPublish Tests.
 *
 * Verifies IndexNow + sitemap ping config gating.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\IndexNowKeyProviderContract;
use ArtisanPackUI\SEO\IndexNow\ConfigIndexNowKeyProvider;
use ArtisanPackUI\SEO\Services\SitemapService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

beforeEach( function (): void {
	config( [
		'app.url'                    => 'https://example.com',
		'seo.sitemap.route_path'     => 'sitemap.xml',
		'seo.indexnow.enabled'       => false,
		'seo.indexnow.key'           => 'abcdef1234567890',
		'seo.indexnow.endpoint'      => 'https://api.indexnow.org/IndexNow',
		'seo.sitemap.submit_enabled' => false,
		'seo.sitemap.search_engines' => [
			'bing' => 'https://www.bing.com/ping?sitemap=%s',
		],
	] );

	app()->bind( IndexNowKeyProviderContract::class, ConfigIndexNowKeyProvider::class );
} );

describe( 'SitemapService::notifyOnPublish', function (): void {

	it( 'sends nothing when both mechanisms are disabled', function (): void {
		Http::fake();

		$results = ( new SitemapService() )->notifyOnPublish( 'https://example.com/one' );

		expect( $results['indexnow']->isEmpty() )->toBeTrue()
			->and( $results['sitemap']->isEmpty() )->toBeTrue();

		Http::assertNothingSent();
	} );

	it( 'submits to IndexNow when only IndexNow is enabled', function (): void {
		config( [ 'seo.indexnow.enabled' => true ] );

		Http::fake( [
			'api.indexnow.org/*' => Http::response( '', 200 ),
			'bing.com/*'         => Http::response( '', 200 ),
		] );

		$results = ( new SitemapService() )->notifyOnPublish( 'https://example.com/one' );

		expect( $results['indexnow']->isNotEmpty() )->toBeTrue()
			->and( $results['sitemap']->isEmpty() )->toBeTrue();

		Http::assertSent( fn ( HttpRequest $r ): bool => str_contains( $r->url(), 'api.indexnow.org' ) );
		Http::assertNotSent( fn ( HttpRequest $r ): bool => str_contains( $r->url(), 'bing.com' ) );
	} );

	it( 'pings the sitemap when only sitemap submission is enabled', function (): void {
		config( [ 'seo.sitemap.submit_enabled' => true ] );

		Http::fake( [
			'api.indexnow.org/*' => Http::response( '', 200 ),
			'bing.com/*'         => Http::response( '', 200 ),
		] );

		$results = ( new SitemapService() )->notifyOnPublish( 'https://example.com/one' );

		expect( $results['indexnow']->isEmpty() )->toBeTrue()
			->and( $results['sitemap']->isNotEmpty() )->toBeTrue();

		Http::assertSent( fn ( HttpRequest $r ): bool => str_contains( $r->url(), 'bing.com' ) );
		Http::assertNotSent( fn ( HttpRequest $r ): bool => str_contains( $r->url(), 'api.indexnow.org' ) );
	} );

	it( 'returns empty results without throwing when IndexNow is enabled but no valid URLs are supplied', function (): void {
		config( [ 'seo.indexnow.enabled' => true ] );

		Http::fake();

		$results = ( new SitemapService() )->notifyOnPublish( [ 'not-a-url', '' ] );

		expect( $results['indexnow']->isEmpty() )->toBeTrue();

		Http::assertNothingSent();
	} );

	it( 'fires both mechanisms when both are enabled', function (): void {
		config( [
			'seo.indexnow.enabled'       => true,
			'seo.sitemap.submit_enabled' => true,
		] );

		Http::fake( [
			'api.indexnow.org/*' => Http::response( '', 200 ),
			'bing.com/*'         => Http::response( '', 200 ),
		] );

		$results = ( new SitemapService() )->notifyOnPublish( 'https://example.com/one' );

		expect( $results['indexnow']->isNotEmpty() )->toBeTrue()
			->and( $results['sitemap']->isNotEmpty() )->toBeTrue();
	} );
} );
