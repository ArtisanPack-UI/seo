<?php

/**
 * GdOgImageRenderer Tests.
 *
 * Unit tests for the GD-based OG image renderer.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\OgImageTemplate;
use ArtisanPackUI\SEO\Services\OgImage\GdOgImageRenderer;

beforeEach( function (): void {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		$this->markTestSkipped( 'The GD PHP extension is not available.' );
	}
} );

describe( 'GdOgImageRenderer', function (): void {

	it( 'renders a valid PNG at the requested dimensions', function (): void {
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate( width: 1200, height: 630 );

		$png = $renderer->render( $template, 'Hello world' );

		expect( $png )->not->toBe( '' );
		expect( substr( $png, 0, 8 ) )->toBe( "\x89PNG\r\n\x1a\n" );

		$image = imagecreatefromstring( $png );

		expect( $image )->not->toBeFalse();
		expect( imagesx( $image ) )->toBe( 1200 );
		expect( imagesy( $image ) )->toBe( 630 );
	} );

	it( 'renders custom dimensions when the template overrides the defaults', function (): void {
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate( width: 800, height: 400 );

		$png   = $renderer->render( $template, 'Custom size' );
		$image = imagecreatefromstring( $png );

		expect( $image )->not->toBeFalse();
		expect( imagesx( $image ) )->toBe( 800 );
		expect( imagesy( $image ) )->toBe( 400 );
	} );

	it( 'paints the configured background color', function (): void {
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate(
			width: 200,
			height: 100,
			backgroundColor: '#ff0000',
			padding: 10,
		);

		$png   = $renderer->render( $template, 'Red' );
		$image = imagecreatefromstring( $png );

		// Sample a corner pixel (well outside any text region).
		$rgb = imagecolorat( $image, 5, 5 );
		$r   = ( $rgb >> 16 ) & 0xFF;
		$g   = ( $rgb >> 8 ) & 0xFF;
		$b   = $rgb & 0xFF;

		expect( $r )->toBe( 255 );
		expect( $g )->toBe( 0 );
		expect( $b )->toBe( 0 );
	} );

	it( 'renders without a subtitle', function (): void {
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate();

		$png = $renderer->render( $template, 'Only a title', null );

		expect( imagecreatefromstring( $png ) )->not->toBeFalse();
	} );

	it( 'renders with a subtitle', function (): void {
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate();

		$png = $renderer->render( $template, 'A title', 'A subtitle line' );

		expect( imagecreatefromstring( $png ) )->not->toBeFalse();
	} );

	it( 'wraps long titles across multiple lines without erroring', function (): void {
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate( padding: 40 );

		$png = $renderer->render(
			$template,
			'This is a deliberately long title designed to exceed the maximum line width and force the wrapper to break it into several lines',
		);

		expect( imagecreatefromstring( $png ) )->not->toBeFalse();
	} );

	it( 'accepts a malformed background color and still produces a PNG', function (): void {
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate( backgroundColor: 'not-a-color' );

		$png = $renderer->render( $template, 'Fallback color' );

		expect( imagecreatefromstring( $png ) )->not->toBeFalse();
	} );

	it( 'silently skips a missing logo path', function (): void {
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate( logoPath: '/definitely/not/a/real/logo.png' );

		$png = $renderer->render( $template, 'Missing logo' );

		expect( imagecreatefromstring( $png ) )->not->toBeFalse();
	} );

	it( 'draws a logo when one is provided', function (): void {
		$logoPath = sys_get_temp_dir() . '/seo-og-test-logo.png';
		$logo     = imagecreatetruecolor( 40, 40 );
		$green    = imagecolorallocate( $logo, 0, 255, 0 );
		imagefilledrectangle( $logo, 0, 0, 40, 40, $green );
		imagepng( $logo, $logoPath );

		try {
			$renderer = new GdOgImageRenderer();
			$template = new OgImageTemplate(
				width: 400,
				height: 200,
				backgroundColor: '#000000',
				logoPath: $logoPath,
				logoWidth: 40,
				padding: 20,
			);

			$png   = $renderer->render( $template, 'With logo' );
			$image = imagecreatefromstring( $png );

			// Sample inside the drawn logo region.
			$rgb = imagecolorat( $image, 30, 30 );
			$g   = ( $rgb >> 8 ) & 0xFF;

			expect( $g )->toBe( 255 );
		} finally {
			@unlink( $logoPath );
		}
	} );

} );
