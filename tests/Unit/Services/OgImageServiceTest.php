<?php

/**
 * OgImageService Tests.
 *
 * Unit tests for the OgImageService (deterministic paths + caching).
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\OgImageRendererContract;
use ArtisanPackUI\SEO\DTOs\OgImageTemplate;
use ArtisanPackUI\SEO\Services\OgImageService;
use Illuminate\Support\Facades\Storage;

class RecordingOgImageRenderer implements OgImageRendererContract
{
	public int $calls = 0;

	public function render( OgImageTemplate $template, string $title, ?string $subtitle = null ): string
	{
		$this->calls++;

		return 'PNG:' . $title . '|' . ( $subtitle ?? '' ) . '|' . $template->signature();
	}
}

beforeEach( function (): void {
	Storage::fake( 'public' );

	config( [
		'seo.og_image.disk'     => 'public',
		'seo.og_image.path'     => 'og-images',
		'seo.og_image.template' => [
			'width'            => 1200,
			'height'           => 630,
			'background_color' => '#0f172a',
			'text_color'       => '#ffffff',
			'subtitle_color'   => '#94a3b8',
			'padding'          => 80,
		],
	] );

	$this->renderer = new RecordingOgImageRenderer();
	$this->service  = new OgImageService( $this->renderer );
} );

describe( 'OgImageService path resolution', function (): void {

	it( 'produces a deterministic path under the configured directory', function (): void {
		$path1 = $this->service->path( 'Hello world', 'Subtitle' );
		$path2 = $this->service->path( 'Hello world', 'Subtitle' );

		expect( $path1 )->toBe( $path2 );
		expect( $path1 )->toStartWith( 'og-images/' );
		expect( $path1 )->toEndWith( '.png' );
	} );

	it( 'changes the path when the title changes', function (): void {
		$a = $this->service->path( 'Title A', 'Subtitle' );
		$b = $this->service->path( 'Title B', 'Subtitle' );

		expect( $a )->not->toBe( $b );
	} );

	it( 'changes the path when the subtitle changes', function (): void {
		$a = $this->service->path( 'Title', 'One' );
		$b = $this->service->path( 'Title', 'Two' );

		expect( $a )->not->toBe( $b );
	} );

	it( 'changes the path when the template changes', function (): void {
		$defaultPath = $this->service->path( 'Title', 'Sub' );

		config( [ 'seo.og_image.template.background_color' => '#ff0000' ] );

		$rebrandedPath = $this->service->path( 'Title', 'Sub' );

		expect( $defaultPath )->not->toBe( $rebrandedPath );
	} );

	it( 'honors a custom path directory from config', function (): void {
		config( [ 'seo.og_image.path' => 'custom/dir' ] );

		expect( $this->service->path( 'Title' ) )->toStartWith( 'custom/dir/' );
	} );

} );

describe( 'OgImageService caching behavior', function (): void {

	it( 'renders once and reuses the cached file on subsequent calls', function (): void {
		$this->service->generate( 'Hello world', 'A subtitle' );
		$this->service->generate( 'Hello world', 'A subtitle' );
		$this->service->generate( 'Hello world', 'A subtitle' );

		expect( $this->renderer->calls )->toBe( 1 );
	} );

	it( 'writes the rendered image to the configured disk', function (): void {
		$this->service->generate( 'Hello world' );

		$path = $this->service->path( 'Hello world' );

		Storage::disk( 'public' )->assertExists( $path );
	} );

	it( 're-renders when forceRegenerate is true', function (): void {
		$this->service->generate( 'Hello world' );
		$this->service->generate( 'Hello world', null, [], true );

		expect( $this->renderer->calls )->toBe( 2 );
	} );

	it( 're-renders after forget() clears the cached file', function (): void {
		$this->service->generate( 'Hello world' );

		$deleted = $this->service->forget( 'Hello world' );

		expect( $deleted )->toBeTrue();

		$this->service->generate( 'Hello world' );

		expect( $this->renderer->calls )->toBe( 2 );
	} );

	it( 'forget() returns false when no cached file exists', function (): void {
		expect( $this->service->forget( 'Never rendered' ) )->toBeFalse();
	} );

	it( 'per-page overrides win over the site-wide template', function (): void {
		$sitewide = $this->service->resolveTemplate();
		$override = $this->service->resolveTemplate( [ 'background_color' => '#123456' ] );

		expect( $sitewide->backgroundColor )->toBe( '#0f172a' );
		expect( $override->backgroundColor )->toBe( '#123456' );
	} );

	it( 'overrides that change the template cause a fresh render', function (): void {
		$this->service->generate( 'Same title' );
		$this->service->generate( 'Same title', null, [ 'background_color' => '#ff00ff' ] );

		expect( $this->renderer->calls )->toBe( 2 );
	} );

} );
