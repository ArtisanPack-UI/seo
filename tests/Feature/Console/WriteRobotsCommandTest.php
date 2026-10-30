<?php

/**
 * WriteRobotsCommand Tests.
 *
 * Verifies the static robots.txt file is written, refreshed and removed
 * with the settings, and that a hand-written file is never lost.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.9.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\RobotsService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

beforeEach( function (): void {
	$this->publicPath = sys_get_temp_dir() . '/seo-robots-' . uniqid();
	$this->robotsPath = $this->publicPath . '/robots.txt';

	File::ensureDirectoryExists( $this->publicPath );
	$this->app->usePublicPath( $this->publicPath );

	config( [
		'app.url'                   => 'https://example.com',
		'seo.robots.write_static'   => true,
		'seo.robots.disallow'       => [ '/admin' ],
		'seo.robots.allow'          => [],
		'seo.robots.rules'          => [],
		'seo.robots.sitemap_url'    => null,
		'seo.sitemap.route_path'    => 'sitemap.xml',
		'seo.sitemap.route_enabled' => true,
	] );

	URL::forceRootUrl( 'https://example.com' );
	URL::forceScheme( 'https' );

	$this->app->forgetInstance( RobotsService::class );
} );

afterEach( function (): void {
	File::deleteDirectory( $this->publicPath );
} );

describe( 'seo:write-robots', function (): void {

	it( 'writes the generated rules to public/robots.txt', function (): void {
		$this->artisan( 'seo:write-robots' )->assertSuccessful();

		$content = File::get( $this->robotsPath );

		expect( $content )->toStartWith( RobotsService::STATIC_FILE_MARKER )
			->and( $content )->toContain( 'Disallow: /admin' )
			->and( $content )->toContain( 'Sitemap: https://example.com/sitemap.xml' );
	} );

	it( 'refreshes its own file when the rules change', function (): void {
		$this->artisan( 'seo:write-robots' );

		config( [ 'seo.robots.disallow' => [ '/private' ] ] );
		$this->app->forgetInstance( RobotsService::class );

		$this->artisan( 'seo:write-robots' )->assertSuccessful();

		expect( File::get( $this->robotsPath ) )->toContain( 'Disallow: /private' )
			->and( File::get( $this->robotsPath ) )->not->toContain( 'Disallow: /admin' );
	} );

	it( 'reports when the file is already current', function (): void {
		$this->artisan( 'seo:write-robots' );

		expect( $this->app->make( RobotsService::class )->syncStaticFile() )->toBe( 'unchanged' );
	} );

	it( 'removes its own file when turned off', function (): void {
		$this->artisan( 'seo:write-robots' );

		config( [ 'seo.robots.write_static' => false ] );

		$this->artisan( 'seo:write-robots' )->assertSuccessful();

		expect( File::exists( $this->robotsPath ) )->toBeFalse();
	} );

	it( 'removes its own file when robots.txt is disabled', function (): void {
		$this->artisan( 'seo:write-robots' );

		config( [ 'seo.robots.enabled' => false ] );

		expect( $this->app->make( RobotsService::class )->syncStaticFile() )->toBe( 'removed' )
			->and( File::exists( $this->robotsPath ) )->toBeFalse();
	} );

	it( 'leaves a hand-written file alone when turned off', function (): void {
		File::put( $this->robotsPath, "User-agent: *\n" );
		config( [ 'seo.robots.write_static' => false ] );

		$this->artisan( 'seo:write-robots' )->assertSuccessful();

		expect( File::get( $this->robotsPath ) )->toBe( "User-agent: *\n" );
	} );

	it( 'does nothing when off and there is no file', function (): void {
		config( [ 'seo.robots.write_static' => false ] );

		expect( $this->app->make( RobotsService::class )->syncStaticFile() )->toBe( 'disabled' )
			->and( File::exists( $this->robotsPath ) )->toBeFalse();
	} );

	it( 'does not overwrite a hand-written file', function (): void {
		File::put( $this->robotsPath, "User-agent: *\n" );

		$this->artisan( 'seo:write-robots' )
			->expectsOutputToContain( 'left alone' )
			->assertFailed();

		expect( File::get( $this->robotsPath ) )->toBe( "User-agent: *\n" );
	} );

	it( 'overwrites a hand-written file with --force', function (): void {
		File::put( $this->robotsPath, "User-agent: *\n" );

		$this->artisan( 'seo:write-robots', [ '--force' => true ] )->assertSuccessful();

		expect( File::get( $this->robotsPath ) )->toStartWith( RobotsService::STATIC_FILE_MARKER );
	} );

	it( 'fails cleanly when the file cannot be written', function (): void {
		File::deleteDirectory( $this->publicPath );

		$this->artisan( 'seo:write-robots' )->assertFailed();
	} );

	it( 'fails when its own file cannot be removed', function (): void {
		$this->artisan( 'seo:write-robots' );

		config( [ 'seo.robots.write_static' => false ] );

		File::partialMock()->shouldReceive( 'delete' )->once()->andReturn( false );

		$this->artisan( 'seo:write-robots' )
			->expectsOutputToContain( 'Could not delete' )
			->assertFailed();
	} );

	it( 'runs as part of optimize', function (): void {
		expect( Artisan::all() )->toHaveKey( 'seo:write-robots' );

		$commands = ( new ReflectionProperty( Illuminate\Support\ServiceProvider::class, 'optimizeCommands' ) )->getValue();

		expect( $commands )->toContain( 'seo:write-robots' );
	} );

} );

describe( 'RobotsController static file', function (): void {

	it( 'never writes the file from a web request', function (): void {
		$this->get( '/robots.txt', [ 'Host' => 'attacker.example' ] )->assertOk();

		expect( File::exists( $this->robotsPath ) )->toBeFalse();
	} );

} );
