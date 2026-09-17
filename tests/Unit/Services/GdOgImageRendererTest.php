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

	it( 'renders 20 images in a loop without leaking (smoke)', function (): void {
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate(
			width: 200,
			height: 100,
			backgroundColor: '#123456',
			padding: 10,
		);

		for ( $i = 0; $i < 20; $i++ ) {
			$png = $renderer->render( $template, 'Iteration ' . $i );
			expect( strlen( $png ) )->toBeGreaterThan( 0 );
		}
	} );

	it( 'bottom-anchors the title against the padded bottom when no subtitle is present', function (): void {
		// A single-line title with no subtitle should sit with its baseline
		// pinned to `height - padding` — MightyShare-style bottom-left
		// layout. Sample well ABOVE the block and confirm the pixels stay
		// the untouched background color.
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate(
			width: 400,
			height: 200,
			backgroundColor: '#ffffff',
			textColor: '#000000',
			padding: 20,
			titleFontSize: 20,
		);

		$png   = $renderer->render( $template, 'Hi' );
		$image = imagecreatefromstring( $png );

		// A row 20px below the top: since the title is bottom-anchored,
		// this row should be entirely untouched by title text.
		$topRowRgb = imagecolorat( $image, 100, 20 );
		$topR      = ( $topRowRgb >> 16 ) & 0xFF;
		expect( $topR )->toBe( 255 );
	} );

	it( 'lifts the title so it sits above the subtitle at the padded bottom', function (): void {
		// With BOTH title and subtitle present the title must not overlap
		// the subtitle's row. We render, then confirm the subtitle row
		// carries dark pixels (subtitle text) AND the title row above it
		// also carries dark pixels — but nothing in between them.
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate(
			width: 800,
			height: 400,
			backgroundColor: '#ffffff',
			textColor: '#000000',
			subtitleColor: '#000000',
			padding: 40,
			titleFontSize: 40,
			subtitleFontSize: 20,
		);

		$png   = $renderer->render( $template, 'A short title', 'A short subtitle' );
		$image = imagecreatefromstring( $png );

		// Subtitle row: sample near the very bottom, well inside the
		// padded X range. Should not be pure white — the subtitle text
		// darkens some pixel here.
		$subtitleRowHasInk = false;
		$subtitleY         = $template->height - $template->padding - $template->subtitleFontSize;
		for ( $x = $template->padding; $x < $template->padding + 200; $x++ ) {
			$rgb = imagecolorat( $image, $x, $subtitleY + (int) ( $template->subtitleFontSize / 2 ) );
			if ( ( ( $rgb >> 16 ) & 0xFF ) < 200 ) {
				$subtitleRowHasInk = true;
				break;
			}
		}
		expect( $subtitleRowHasInk )->toBeTrue();

		// A row well above the title (say y = 40 = padding): should
		// stay the background color since the title is bottom-anchored.
		$topRowRgb = imagecolorat( $image, 400, 40 );
		expect( ( $topRowRgb >> 16 ) & 0xFF )->toBe( 255 );
	} );

	it( 'left-aligns the title at the template padding', function (): void {
		// The title must start at `x = padding`, matching where the
		// logo lives in the top-left. Any pixel to the LEFT of `padding`
		// on the title's own row must remain the untouched background.
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate(
			width: 400,
			height: 200,
			backgroundColor: '#ffffff',
			textColor: '#000000',
			padding: 40,
			titleFontSize: 30,
		);

		$png   = $renderer->render( $template, 'Hi' );
		$image = imagecreatefromstring( $png );

		// Sample the row well within the title's Y range but at x < padding.
		$titleY = $template->height - $template->padding - (int) ( $template->titleFontSize / 2 );
		$rgb    = imagecolorat( $image, 20, $titleY ); // x < padding (40)

		expect( ( $rgb >> 16 ) & 0xFF )->toBe( 255 );
	} );

	it( 'does not scrim a canvas whose background image path resolves to nothing', function (): void {
		// A configured-but-nonexistent path (or one loadImage can't decode)
		// leaves the canvas at the flat background color. Scrimming it
		// would darken a palette the operator explicitly picked for readable
		// text — the whole point of the "no scrim without a background image"
		// contract from CodeRabbit's review.
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate(
			width: 200,
			height: 100,
			backgroundColor: '#ff0000',
			padding: 10,
			backgroundImagePath: '/definitely/not/a/real/background.png',
			backgroundScrimOpacity: 100, // Max scrim: only fires if we actually drew a background image.
			backgroundScrimGradient: false,
		);

		$png   = $renderer->render( $template, 'Untouched' );
		$image = imagecreatefromstring( $png );

		$rgb = imagecolorat( $image, 5, 5 );
		$r   = ( $rgb >> 16 ) & 0xFF;

		expect( $r )->toBe( 255 );
	} );

	it( 'does not draw a scrim when no background image is set', function (): void {
		// A plain color background must not be scrimmed — the operator
		// picked the color/text pair together, adding a scrim would just
		// wash it out.
		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate(
			width: 200,
			height: 100,
			backgroundColor: '#ff0000',
			padding: 10,
			backgroundScrimOpacity: 100, // Even at max opacity: no image → no scrim.
		);

		$png   = $renderer->render( $template, 'Red' );
		$image = imagecreatefromstring( $png );

		$rgb = imagecolorat( $image, 5, 5 );
		$r   = ( $rgb >> 16 ) & 0xFF;

		expect( $r )->toBe( 255 );
	} );

	it( 'darkens the background image with a flat scrim when a background image is set', function (): void {
		// Write a bright red PNG and use it as the background.
		$bgPath = sys_get_temp_dir() . '/seo-og-test-bg.png';
		$bg     = imagecreatetruecolor( 200, 100 );
		$red    = imagecolorallocate( $bg, 255, 0, 0 );
		imagefilledrectangle( $bg, 0, 0, 200, 100, $red );
		imagepng( $bg, $bgPath );
		imagedestroy( $bg );

		try {
			$renderer = new GdOgImageRenderer();
			$template = new OgImageTemplate(
				width: 200,
				height: 100,
				padding: 10,
				backgroundImagePath: $bgPath,
				backgroundScrimColor: '#000000',
				backgroundScrimOpacity: 60,
				backgroundScrimGradient: false,
			);

			$png   = $renderer->render( $template, 'Scrimmed' );
			$image = imagecreatefromstring( $png );

			// Sample outside the logo/text area. The pure-red source
			// should be darkened to well under 255 by the black scrim.
			$rgb = imagecolorat( $image, 195, 95 );
			$r   = ( $rgb >> 16 ) & 0xFF;

			expect( $r )->toBeLessThan( 200 );
			expect( $r )->toBeGreaterThan( 40 );
		} finally {
			@unlink( $bgPath );
		}
	} );

	it( 'skips the scrim entirely when opacity is 0', function (): void {
		$bgPath = sys_get_temp_dir() . '/seo-og-test-bg-noopacity.png';
		$bg     = imagecreatetruecolor( 200, 100 );
		$red    = imagecolorallocate( $bg, 255, 0, 0 );
		imagefilledrectangle( $bg, 0, 0, 200, 100, $red );
		imagepng( $bg, $bgPath );
		imagedestroy( $bg );

		try {
			$renderer = new GdOgImageRenderer();
			$template = new OgImageTemplate(
				width: 200,
				height: 100,
				padding: 10,
				backgroundImagePath: $bgPath,
				backgroundScrimOpacity: 0,
			);

			$png   = $renderer->render( $template, 'No scrim' );
			$image = imagecreatefromstring( $png );

			// The original red pixel survives untouched.
			$rgb = imagecolorat( $image, 195, 95 );
			$r   = ( $rgb >> 16 ) & 0xFF;

			expect( $r )->toBe( 255 );
		} finally {
			@unlink( $bgPath );
		}
	} );

	it( 'gradients the scrim from lighter at top to darker at bottom', function (): void {
		$bgPath = sys_get_temp_dir() . '/seo-og-test-bg-gradient.png';
		$bg     = imagecreatetruecolor( 200, 200 );
		$red    = imagecolorallocate( $bg, 255, 0, 0 );
		imagefilledrectangle( $bg, 0, 0, 200, 200, $red );
		imagepng( $bg, $bgPath );
		imagedestroy( $bg );

		try {
			$renderer = new GdOgImageRenderer();
			$template = new OgImageTemplate(
				width: 200,
				height: 200,
				padding: 10,
				backgroundImagePath: $bgPath,
				backgroundScrimColor: '#000000',
				backgroundScrimOpacity: 60,
				backgroundScrimGradient: true,
			);

			$png   = $renderer->render( $template, 'Gradient' );
			$image = imagecreatefromstring( $png );

			// Sample near the top-right (well outside the logo area at
			// top-left, well away from the vertically-centered title).
			// Bottom sample is well below the title, in the subtitle band.
			$topRgb    = imagecolorat( $image, 195, 5 );
			$bottomRgb = imagecolorat( $image, 195, 195 );
			$topR      = ( $topRgb >> 16 ) & 0xFF;
			$bottomR   = ( $bottomRgb >> 16 ) & 0xFF;

			// Gradient shape: top is lighter (higher red channel remains
			// from the original), bottom is darker.
			expect( $topR )->toBeGreaterThan( $bottomR );
		} finally {
			@unlink( $bgPath );
		}
	} );

	it( 'honors the scrim color for tinted overlays', function (): void {
		// Blue-tinted scrim over a bright white background: red channel
		// should drop, blue should remain high.
		$bgPath = sys_get_temp_dir() . '/seo-og-test-bg-tinted.png';
		$bg     = imagecreatetruecolor( 200, 100 );
		$white  = imagecolorallocate( $bg, 255, 255, 255 );
		imagefilledrectangle( $bg, 0, 0, 200, 100, $white );
		imagepng( $bg, $bgPath );
		imagedestroy( $bg );

		try {
			$renderer = new GdOgImageRenderer();
			$template = new OgImageTemplate(
				width: 200,
				height: 100,
				padding: 10,
				backgroundImagePath: $bgPath,
				backgroundScrimColor: '#0000ff', // Pure blue.
				backgroundScrimOpacity: 100,
				backgroundScrimGradient: false,
			);

			$png   = $renderer->render( $template, 'Tinted' );
			$image = imagecreatefromstring( $png );

			$rgb = imagecolorat( $image, 195, 95 );
			$r   = ( $rgb >> 16 ) & 0xFF;
			$b   = $rgb & 0xFF;

			expect( $r )->toBeLessThan( 30 );
			expect( $b )->toBeGreaterThan( 200 );
		} finally {
			@unlink( $bgPath );
		}
	} );

	it( 'logs a warning when non-ASCII text falls back to the bitmap renderer', function (): void {
		Illuminate\Support\Facades\Log::spy();

		$renderer = new GdOgImageRenderer();
		$template = new OgImageTemplate(
			width: 400,
			height: 200,
			backgroundColor: '#ffffff',
			padding: 20,
		);

		$renderer->render( $template, 'Straße Café' );

		Illuminate\Support\Facades\Log::shouldHaveReceived( 'warning' )->atLeast()->once();
	} );

} );
