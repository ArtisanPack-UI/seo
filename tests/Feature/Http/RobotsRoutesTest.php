<?php

/**
 * Robots.txt Routes Tests.
 *
 * Feature tests for robots.txt HTTP routes.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

beforeEach( function (): void {
	config( [ 'app.url' => 'https://example.com' ] );
	config( [ 'seo.robots.route_enabled' => true ] );
	config( [ 'seo.robots.disallow' => [ '/admin', '/api' ] ] );
	config( [ 'seo.robots.allow' => [] ] );
	config( [ 'seo.robots.rules' => [] ] );
	config( [ 'seo.robots.sitemap_url' => null ] );
	config( [ 'seo.sitemap.route_enabled' => true ] );
	config( [ 'seo.sitemap.route_path' => 'sitemap.xml' ] );
} );

describe( 'Robots.txt Routes', function (): void {

	it( 'serves robots.txt at configured route', function (): void {
		$response = $this->get( '/robots.txt' );

		$response->assertStatus( 200 )
			->assertHeader( 'Content-Type', 'text/plain; charset=UTF-8' );
	} );

	it( 'includes User-agent directive', function (): void {
		$response = $this->get( '/robots.txt' );

		$response->assertStatus( 200 );

		$content = $response->getContent();
		expect( $content )->toContain( 'User-agent: *' );
	} );

	it( 'includes Disallow directives from config', function (): void {
		$response = $this->get( '/robots.txt' );

		$response->assertStatus( 200 );

		$content = $response->getContent();
		expect( $content )->toContain( 'Disallow: /admin' )
			->and( $content )->toContain( 'Disallow: /api' );
	} );

	it( 'includes Sitemap URL', function (): void {
		$response = $this->get( '/robots.txt' );

		$response->assertStatus( 200 );

		$content = $response->getContent();
		expect( $content )->toContain( 'Sitemap:' )
			->and( $content )->toContain( 'sitemap.xml' );
	} );

	it( 'includes cache control header', function (): void {
		config( [ 'seo.robots.cache_ttl' => 7200 ] );

		$response = $this->get( '/robots.txt' );

		$response->assertStatus( 200 );

		$cacheControl = $response->headers->get( 'Cache-Control' );
		expect( $cacheControl )->toContain( 'max-age=7200' )
			->and( $cacheControl )->toContain( 'public' );
	} );

} );

describe( 'Bot-Specific Rules', function (): void {

	it( 'includes bot-specific rules from config', function (): void {
		config( [ 'seo.robots.rules' => [
			'GPTBot' => [
				'disallow' => [ '/' ],
			],
		] ] );

		// Service needs to be recreated after config change
		$this->app->forgetInstance( 'ArtisanPackUI\SEO\Services\RobotsService' );

		$response = $this->get( '/robots.txt' );

		$response->assertStatus( 200 );

		$content = $response->getContent();
		expect( $content )->toContain( 'User-agent: GPTBot' )
			->and( $content )->toContain( 'Disallow: /' );
	} );

	it( 'includes crawl delay for specific bot', function (): void {
		config( [ 'seo.robots.rules' => [
			'Googlebot' => [
				'crawl_delay' => 2,
			],
		] ] );

		$this->app->forgetInstance( 'ArtisanPackUI\SEO\Services\RobotsService' );

		$response = $this->get( '/robots.txt' );

		$response->assertStatus( 200 );

		$content = $response->getContent();
		expect( $content )->toContain( 'User-agent: Googlebot' )
			->and( $content )->toContain( 'Crawl-delay: 2' );
	} );

	it( 'supports multiple bot-specific rule sets', function (): void {
		config( [ 'seo.robots.rules' => [
			'GPTBot' => [
				'disallow' => [ '/' ],
			],
			'CCBot' => [
				'disallow' => [ '/' ],
			],
			'Googlebot' => [
				'allow'       => [ '/api/public' ],
				'crawl_delay' => 1,
			],
		] ] );

		$this->app->forgetInstance( 'ArtisanPackUI\SEO\Services\RobotsService' );

		$response = $this->get( '/robots.txt' );

		$response->assertStatus( 200 );

		$content = $response->getContent();
		expect( $content )->toContain( 'User-agent: GPTBot' )
			->and( $content )->toContain( 'User-agent: CCBot' )
			->and( $content )->toContain( 'User-agent: Googlebot' )
			->and( $content )->toContain( 'Allow: /api/public' )
			->and( $content )->toContain( 'Crawl-delay: 1' );
	} );

} );
