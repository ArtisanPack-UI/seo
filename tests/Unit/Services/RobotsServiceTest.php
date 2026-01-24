<?php

/**
 * RobotsService Tests.
 *
 * Unit tests for the RobotsService class.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\RobotsService;

beforeEach( function (): void {
	config( [ 'app.url' => 'https://example.com' ] );
	config( [ 'seo.robots.enabled' => true ] );
	config( [ 'seo.robots.route_enabled' => true ] );
	config( [ 'seo.robots.disallow' => [] ] );
	config( [ 'seo.robots.allow' => [] ] );
	config( [ 'seo.robots.rules' => [] ] );
	config( [ 'seo.robots.sitemap_url' => null ] );
	config( [ 'seo.robots.sitemaps' => [] ] );
	config( [ 'seo.robots.host' => null ] );
	config( [ 'seo.sitemap.route_enabled' => false ] ); // Disable auto-sitemap
} );

describe( 'RobotsService', function (): void {

	it( 'generates basic robots.txt', function (): void {
		$service = new RobotsService();
		$content = $service->generate();

		expect( $content )->toContain( 'User-agent:' );
	} );

	it( 'adds disallow rules', function (): void {
		$service = new RobotsService();
		$service->disallow( '/admin' );
		$service->disallow( '/api' );

		$content = $service->generate();

		expect( $content )->toContain( 'User-agent: *' )
			->and( $content )->toContain( 'Disallow: /admin' )
			->and( $content )->toContain( 'Disallow: /api' );
	} );

	it( 'adds allow rules', function (): void {
		$service = new RobotsService();
		$service->allow( '/api/public' );

		$content = $service->generate();

		expect( $content )->toContain( 'Allow: /api/public' );
	} );

	it( 'adds sitemap URL', function (): void {
		$service = new RobotsService();
		$service->addSitemap( 'https://example.com/sitemap.xml' );

		$content = $service->generate();

		expect( $content )->toContain( 'Sitemap: https://example.com/sitemap.xml' );
	} );

	it( 'adds multiple sitemap URLs', function (): void {
		$service = new RobotsService();
		$service->addSitemap( 'https://example.com/sitemap.xml' );
		$service->addSitemap( 'https://example.com/sitemap-images.xml' );

		$content = $service->generate();

		expect( $content )->toContain( 'Sitemap: https://example.com/sitemap.xml' )
			->and( $content )->toContain( 'Sitemap: https://example.com/sitemap-images.xml' );
	} );

	it( 'does not duplicate sitemap URLs', function (): void {
		$service = new RobotsService();
		$service->addSitemap( 'https://example.com/sitemap.xml' );
		$service->addSitemap( 'https://example.com/sitemap.xml' );

		$urls = $service->getSitemapUrls();

		expect( $urls )->toHaveCount( 1 );
	} );

	it( 'sets host directive', function (): void {
		$service = new RobotsService();
		$service->setHost( 'https://example.com' );

		$content = $service->generate();

		expect( $content )->toContain( 'Host: https://example.com' );
	} );

	it( 'sets crawl delay', function (): void {
		$service = new RobotsService();
		$service->crawlDelay( 5 );

		$content = $service->generate();

		expect( $content )->toContain( 'Crawl-delay: 5' );
	} );

	it( 'supports bot-specific rules', function (): void {
		$service = new RobotsService();
		$service->disallow( '/', 'GPTBot' );
		$service->disallow( '/', 'CCBot' );

		$content = $service->generate();

		expect( $content )->toContain( 'User-agent: GPTBot' )
			->and( $content )->toContain( 'User-agent: CCBot' );
	} );

	it( 'sets crawl delay for specific bot', function (): void {
		$service = new RobotsService();
		$service->crawlDelay( 2, 'Googlebot' );

		$content = $service->generate();

		expect( $content )->toContain( 'User-agent: Googlebot' )
			->and( $content )->toContain( 'Crawl-delay: 2' );
	} );

	it( 'clears all rules', function (): void {
		$service = new RobotsService();
		$service->disallow( '/admin' );
		$service->addSitemap( 'https://example.com/sitemap.xml' );
		$service->setHost( 'https://example.com' );

		$service->clearRules();

		expect( $service->getUserAgents() )->toBeEmpty()
			->and( $service->getSitemapUrls() )->toBeEmpty()
			->and( $service->getHost() )->toBeNull();
	} );

	it( 'removes specific user agent', function (): void {
		$service = new RobotsService();
		$service->disallow( '/', 'GPTBot' );
		$service->disallow( '/', 'CCBot' );

		$service->removeUserAgent( 'GPTBot' );

		expect( $service->getUserAgents() )->not->toContain( 'GPTBot' )
			->and( $service->getUserAgents() )->toContain( 'CCBot' );
	} );

	it( 'gets rules for specific user agent', function (): void {
		$service = new RobotsService();
		$service->disallow( '/admin', 'Googlebot' );
		$service->allow( '/api/public', 'Googlebot' );
		$service->crawlDelay( 1, 'Googlebot' );

		$rules = $service->getRulesForUserAgent( 'Googlebot' );

		expect( $rules['disallow'] )->toContain( '/admin' )
			->and( $rules['allow'] )->toContain( '/api/public' )
			->and( $rules['crawl_delay'] )->toBe( 1 );
	} );

	it( 'returns empty array for non-existent user agent', function (): void {
		$service = new RobotsService();

		$rules = $service->getRulesForUserAgent( 'NonExistentBot' );

		expect( $rules )->toBeEmpty();
	} );

	it( 'checks if enabled', function (): void {
		config( [ 'seo.robots.enabled' => true ] );
		$service = new RobotsService();

		expect( $service->isEnabled() )->toBeTrue();

		config( [ 'seo.robots.enabled' => false ] );

		expect( $service->isEnabled() )->toBeFalse();
	} );

	it( 'checks if route is enabled', function (): void {
		config( [ 'seo.robots.route_enabled' => true ] );
		$service = new RobotsService();

		expect( $service->isRouteEnabled() )->toBeTrue();

		config( [ 'seo.robots.route_enabled' => false ] );

		expect( $service->isRouteEnabled() )->toBeFalse();
	} );

	it( 'returns fluent interface for chaining', function (): void {
		$service = new RobotsService();

		$result = $service
			->disallow( '/admin' )
			->allow( '/api/public' )
			->crawlDelay( 1 )
			->addSitemap( 'https://example.com/sitemap.xml' )
			->setHost( 'https://example.com' );

		expect( $result )->toBeInstanceOf( RobotsService::class );
	} );

	it( 'removes duplicate disallow rules', function (): void {
		$service = new RobotsService();
		$service->disallow( '/admin' );
		$service->disallow( '/admin' );
		$service->disallow( '/api' );

		$content = $service->generate();

		// Count occurrences of Disallow: /admin
		$count = substr_count( $content, 'Disallow: /admin' );

		expect( $count )->toBe( 1 );
	} );

	it( 'removes duplicate allow rules', function (): void {
		$service = new RobotsService();
		$service->allow( '/api/public' );
		$service->allow( '/api/public' );

		$content = $service->generate();

		$count = substr_count( $content, 'Allow: /api/public' );

		expect( $count )->toBe( 1 );
	} );

} );

describe( 'RobotsService Config Loading', function (): void {

	it( 'loads disallow rules from config', function (): void {
		config( [ 'seo.robots.disallow' => [ '/admin', '/api' ] ] );

		$service = new RobotsService();
		$content = $service->generate();

		expect( $content )->toContain( 'Disallow: /admin' )
			->and( $content )->toContain( 'Disallow: /api' );
	} );

	it( 'loads allow rules from config', function (): void {
		config( [ 'seo.robots.allow' => [ '/api/public' ] ] );

		$service = new RobotsService();
		$content = $service->generate();

		expect( $content )->toContain( 'Allow: /api/public' );
	} );

	it( 'loads bot-specific rules from config', function (): void {
		config( [ 'seo.robots.rules' => [
			'GPTBot' => [
				'disallow' => [ '/' ],
			],
			'Googlebot' => [
				'allow'       => [ '/api/public' ],
				'crawl_delay' => 2,
			],
		] ] );

		$service = new RobotsService();
		$content = $service->generate();

		expect( $content )->toContain( 'User-agent: GPTBot' )
			->and( $content )->toContain( 'User-agent: Googlebot' )
			->and( $content )->toContain( 'Crawl-delay: 2' );
	} );

	it( 'loads sitemap URL from config', function (): void {
		config( [ 'seo.robots.sitemap_url' => 'https://example.com/custom-sitemap.xml' ] );

		$service = new RobotsService();
		$content = $service->generate();

		expect( $content )->toContain( 'Sitemap: https://example.com/custom-sitemap.xml' );
	} );

	it( 'loads additional sitemaps from config', function (): void {
		config( [ 'seo.robots.sitemaps' => [
			'https://example.com/sitemap-images.xml',
			'https://example.com/sitemap-videos.xml',
		] ] );

		$service = new RobotsService();
		$content = $service->generate();

		expect( $content )->toContain( 'Sitemap: https://example.com/sitemap-images.xml' )
			->and( $content )->toContain( 'Sitemap: https://example.com/sitemap-videos.xml' );
	} );

	it( 'loads host from config', function (): void {
		config( [ 'seo.robots.host' => 'https://example.com' ] );

		$service = new RobotsService();
		$content = $service->generate();

		expect( $content )->toContain( 'Host: https://example.com' );
	} );

	it( 'auto-generates sitemap URL from sitemap config', function (): void {
		config( [ 'seo.robots.sitemap_url' => null ] );
		config( [ 'seo.sitemap.route_enabled' => true ] );
		config( [ 'seo.sitemap.route_path' => 'sitemap.xml' ] );

		$service = new RobotsService();
		$content = $service->generate();

		expect( $content )->toContain( 'Sitemap:' )
			->and( $content )->toContain( 'sitemap.xml' );
	} );

} );
