<?php

/**
 * Redirect Middleware Tests.
 *
 * Feature tests for the HandleRedirects middleware.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Http\Middleware\HandleRedirects;
use ArtisanPackUI\SEO\Models\Redirect;
use ArtisanPackUI\SEO\Services\RedirectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	// Run migrations
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	// Configure defaults
	config( [ 'seo.redirects.enabled' => true ] );
	config( [ 'seo.redirects.middleware_enabled' => true ] );
	config( [ 'seo.redirects.track_hits' => true ] );
	config( [ 'seo.redirects.cache_enabled' => false ] ); // Disable cache for testing
	config( [ 'seo.redirects.max_chain_depth' => 5 ] );
	config( [ 'seo.redirects.preserve_query_string' => true ] );

	// Clear any cached redirects
	app( RedirectService::class )->clearCache();

	// Set up test routes with middleware
	Route::middleware( HandleRedirects::class )->group( function (): void {
		Route::get( '/test-page', function () {
			return 'Test Page Content';
		} )->name( 'test.page' );

		Route::get( '/destination', function () {
			return 'Destination Content';
		} )->name( 'destination' );

		Route::get( '/new-page', function () {
			return 'New Page Content';
		} )->name( 'new.page' );

		Route::get( '/final-destination', function () {
			return 'Final Destination';
		} )->name( 'final.destination' );

		Route::get( '/posts/{id}', function ( $id ) {
			return "Post {$id}";
		} )->name( 'posts.show' );

		Route::post( '/form-submit', function () {
			return 'Form Submitted';
		} )->name( 'form.submit' );

		// Fallback route to catch all GET requests and let middleware handle redirects.
		// This ensures the middleware runs even for URLs that don't have explicit routes.
		Route::fallback( function (): void {
			abort( 404 );
		} );
	} );
} );

describe( 'Redirect Middleware Basic Functionality', function (): void {

	it( 'redirects when exact match found', function (): void {
		Redirect::create( [
			'from_path'   => '/old-page',
			'to_path'     => '/new-page',
			'status_code' => 301,
			'match_type'  => 'exact',
		] );

		$response = $this->get( '/old-page' );

		$response->assertRedirect( '/new-page' )
			->assertStatus( 301 );
	} );

	it( 'passes through when no redirect match', function (): void {
		$response = $this->get( '/test-page' );

		$response->assertStatus( 200 )
			->assertSee( 'Test Page Content' );
	} );

	it( 'uses correct status code for 302 redirect', function (): void {
		Redirect::create( [
			'from_path'   => '/temp-moved',
			'to_path'     => '/destination',
			'status_code' => 302,
			'match_type'  => 'exact',
		] );

		$response = $this->get( '/temp-moved' );

		$response->assertRedirect( '/destination' )
			->assertStatus( 302 );
	} );

	it( 'uses correct status code for 307 redirect', function (): void {
		Redirect::create( [
			'from_path'   => '/temp-307',
			'to_path'     => '/destination',
			'status_code' => 307,
			'match_type'  => 'exact',
		] );

		$response = $this->get( '/temp-307' );

		$response->assertRedirect( '/destination' )
			->assertStatus( 307 );
	} );

	it( 'uses correct status code for 308 redirect', function (): void {
		Redirect::create( [
			'from_path'   => '/perm-308',
			'to_path'     => '/destination',
			'status_code' => 308,
			'match_type'  => 'exact',
		] );

		$response = $this->get( '/perm-308' );

		$response->assertRedirect( '/destination' )
			->assertStatus( 308 );
	} );

	it( 'skips inactive redirects', function (): void {
		Redirect::create( [
			'from_path'   => '/test-page',
			'to_path'     => '/destination',
			'status_code' => 301,
			'match_type'  => 'exact',
			'is_active'   => false,
		] );

		$response = $this->get( '/test-page' );

		$response->assertStatus( 200 )
			->assertSee( 'Test Page Content' );
	} );

} );

describe( 'Redirect Middleware Hit Tracking', function (): void {

	it( 'increments hit counter on redirect', function (): void {
		$redirect = Redirect::create( [
			'from_path'   => '/tracked-page',
			'to_path'     => '/destination',
			'status_code' => 301,
		] );

		expect( $redirect->hits )->toBe( 0 );

		$this->get( '/tracked-page' );

		$redirect->refresh();
		expect( $redirect->hits )->toBe( 1 );
	} );

	it( 'updates last_hit_at timestamp', function (): void {
		$redirect = Redirect::create( [
			'from_path'   => '/timestamp-test',
			'to_path'     => '/destination',
			'status_code' => 301,
		] );

		expect( $redirect->last_hit_at )->toBeNull();

		$this->get( '/timestamp-test' );

		$redirect->refresh();
		expect( $redirect->last_hit_at )->not->toBeNull();
	} );

	it( 'does not track hits when disabled', function (): void {
		config( [ 'seo.redirects.track_hits' => false ] );

		$redirect = Redirect::create( [
			'from_path'   => '/no-tracking',
			'to_path'     => '/destination',
			'status_code' => 301,
		] );

		$this->get( '/no-tracking' );

		$redirect->refresh();
		expect( $redirect->hits )->toBe( 0 );
	} );

} );

describe( 'Redirect Middleware Pattern Matching', function (): void {

	it( 'handles wildcard redirects', function (): void {
		Redirect::create( [
			'from_path'   => '/blog/*',
			'to_path'     => '/posts/*',
			'status_code' => 301,
			'match_type'  => 'wildcard',
		] );

		$response = $this->get( '/blog/my-article' );

		$response->assertRedirect( '/posts/my-article' )
			->assertStatus( 301 );
	} );

	it( 'handles regex redirects', function (): void {
		Redirect::create( [
			'from_path'   => '/article/(\d+)',
			'to_path'     => '/posts/$1',
			'status_code' => 301,
			'match_type'  => 'regex',
		] );

		$response = $this->get( '/article/123' );

		$response->assertRedirect( '/posts/123' )
			->assertStatus( 301 );
	} );

	it( 'handles regex with multiple capture groups', function (): void {
		Redirect::create( [
			'from_path'   => '/archive/(\d{4})/(\d{2})/(.+)',
			'to_path'     => '/blog/$1-$2/$3',
			'status_code' => 301,
			'match_type'  => 'regex',
		] );

		$response = $this->get( '/archive/2024/01/my-post' );

		$response->assertRedirect( '/blog/2024-01/my-post' )
			->assertStatus( 301 );
	} );

} );

describe( 'Redirect Middleware Request Handling', function (): void {

	it( 'skips POST requests by default', function (): void {
		Redirect::create( [
			'from_path'   => '/form-submit',
			'to_path'     => '/destination',
			'status_code' => 301,
		] );

		$response = $this->post( '/form-submit' );

		$response->assertStatus( 200 )
			->assertSee( 'Form Submitted' );
	} );

	it( 'preserves query string when configured', function (): void {
		config( [ 'seo.redirects.preserve_query_string' => true ] );

		Redirect::create( [
			'from_path'   => '/old-search',
			'to_path'     => '/new-page',
			'status_code' => 301,
		] );

		$response = $this->get( '/old-search?q=test&page=2' );

		$response->assertStatus( 301 );

		// Check that the redirect contains both query parameters (order may vary)
		$location = $response->headers->get( 'Location' );
		expect( $location )->toContain( '/new-page?' );
		expect( $location )->toContain( 'q=test' );
		expect( $location )->toContain( 'page=2' );
	} );

	it( 'does not modify query string when destination has one', function (): void {
		Redirect::create( [
			'from_path'   => '/legacy-search',
			'to_path'     => '/new-page?source=legacy',
			'status_code' => 301,
		] );

		$response = $this->get( '/legacy-search?q=test' );

		// Should not append additional query params when destination already has them
		$response->assertRedirect( '/new-page?source=legacy' );
	} );

} );

describe( 'Redirect Middleware Configuration', function (): void {

	it( 'can be disabled via config', function (): void {
		config( [ 'seo.redirects.enabled' => false ] );

		Redirect::create( [
			'from_path'   => '/test-page',
			'to_path'     => '/destination',
			'status_code' => 301,
		] );

		$response = $this->get( '/test-page' );

		$response->assertStatus( 200 )
			->assertSee( 'Test Page Content' );
	} );

	it( 'can disable middleware specifically via config', function (): void {
		config( [ 'seo.redirects.enabled' => true ] );
		config( [ 'seo.redirects.middleware_enabled' => false ] );

		Redirect::create( [
			'from_path'   => '/test-page',
			'to_path'     => '/destination',
			'status_code' => 301,
		] );

		$response = $this->get( '/test-page' );

		$response->assertStatus( 200 )
			->assertSee( 'Test Page Content' );
	} );

} );

describe( 'Redirect Chain Prevention', function (): void {

	it( 'prevents redirect to same URL', function (): void {
		// This shouldn't be possible to create, but test the middleware handles it
		$redirect = new Redirect( [
			'from_path'   => '/self-loop',
			'to_path'     => '/self-loop',
			'status_code' => 301,
			'match_type'  => 'exact',
			'is_active'   => true,
		] );
		$redirect->saveQuietly(); // Skip validation

		$response = $this->get( '/self-loop' );

		// Should not cause infinite loop - middleware should detect and let request continue
		// Since there's no actual route for /self-loop, it returns 404
		$response->assertStatus( 404 );
	} );

	it( 'prevents chain exceeding max depth', function (): void {
		config( [ 'seo.redirects.max_chain_depth' => 2 ] );

		// Create a chain of redirects: a -> b -> c -> d
		Redirect::create( [
			'from_path' => '/chain-a',
			'to_path'   => '/chain-b',
		] );

		Redirect::create( [
			'from_path' => '/chain-b',
			'to_path'   => '/chain-c',
		] );

		Redirect::create( [
			'from_path' => '/chain-c',
			'to_path'   => '/final-destination',
		] );

		// When chain depth exceeds max, middleware lets the request continue
		// instead of redirecting, preventing a potential infinite chain
		$response = $this->get( '/chain-a' );

		// Chain detection kicks in and lets request continue (404 since no route exists)
		$response->assertStatus( 404 );
	} );

} );

describe( 'Redirect Middleware Edge Cases', function (): void {

	it( 'handles redirect to external URL', function (): void {
		Redirect::create( [
			'from_path'   => '/external',
			'to_path'     => 'https://example.com/page',
			'status_code' => 301,
		] );

		$response = $this->get( '/external' );

		$response->assertRedirect( 'https://example.com/page' )
			->assertStatus( 301 );
	} );

	it( 'normalizes paths with trailing slashes', function (): void {
		Redirect::create( [
			'from_path'   => '/old-page/',
			'to_path'     => '/new-page',
			'status_code' => 301,
		] );

		// Request without trailing slash should still match
		$response = $this->get( '/old-page' );

		$response->assertRedirect( '/new-page' )
			->assertStatus( 301 );
	} );

	it( 'handles redirects with encoded characters', function (): void {
		Redirect::create( [
			'from_path'   => '/old%20page',
			'to_path'     => '/new-page',
			'status_code' => 301,
		] );

		$response = $this->get( '/old%20page' );

		$response->assertRedirect( '/new-page' )
			->assertStatus( 301 );
	} );

} );
