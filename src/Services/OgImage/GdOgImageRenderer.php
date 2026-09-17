<?php

/**
 * GdOgImageRenderer.
 *
 * GD-based renderer for OG social-share cards.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Services\OgImage;

use ArtisanPackUI\SEO\Contracts\OgImageRendererContract;
use ArtisanPackUI\SEO\DTOs\OgImageTemplate;
use GdImage;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * GdOgImageRenderer class.
 *
 * Renders OG images using PHP's bundled GD extension. When a TTF font path
 * is configured on the template, text is drawn with imagettftext() for
 * high-quality anti-aliased output. Without a font path we fall back to
 * imagestring() bitmap text — usable in CI without shipping a font file.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
class GdOgImageRenderer implements OgImageRendererContract
{
	/**
	 * Render an OG image to raw PNG binary.
	 *
	 * @since 1.4.0
	 *
	 * @param  OgImageTemplate  $template  The resolved template.
	 * @param  string           $title     The primary title to render.
	 * @param  string|null      $subtitle  An optional subtitle.
	 *
	 * @return string Raw PNG binary data.
	 */
	public function render( OgImageTemplate $template, string $title, ?string $subtitle = null ): string
	{
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			throw new RuntimeException( 'The GD PHP extension is required to render OG images with the GD backend.' );
		}

		$image = imagecreatetruecolor( $template->width, $template->height );

		if ( false === $image ) {
			throw new RuntimeException( 'GD failed to allocate the OG image canvas.' );
		}

		// Enable alpha preservation so a transparent PNG logo composites
		// onto the canvas without producing black halos (P1-7).
		imagesavealpha( $image, true );

		try {
			$this->fillBackground( $image, $template );
			$this->drawBackgroundImage( $image, $template );
			$this->drawBackgroundScrim( $image, $template );
			$this->drawLogo( $image, $template );
			$this->drawTitle( $image, $template, $title );

			if ( null !== $subtitle && '' !== trim( $subtitle ) ) {
				$this->drawSubtitle( $image, $template, $subtitle );
			}

			ob_start();
			imagepng( $image );
			$png = (string) ob_get_clean();

			return $png;
		} finally {
			// Release GD resources — queue workers rendering many images
			// otherwise grow their memory footprint unboundedly (P1-6).
			if ( $image instanceof GdImage ) {
				imagedestroy( $image );
			}
		}
	}

	/**
	 * Fill the canvas with the template background color.
	 *
	 * @since 1.4.0
	 *
	 * @param  GdImage         $image    The image resource.
	 * @param  OgImageTemplate $template The template.
	 *
	 * @return void
	 */
	protected function fillBackground( GdImage $image, OgImageTemplate $template ): void
	{
		[ $r, $g, $b ] = $this->hexToRgb( $template->backgroundColor );
		$color         = imagecolorallocate( $image, $r, $g, $b );

		if ( false === $color ) {
			return;
		}

		imagefilledrectangle( $image, 0, 0, $template->width, $template->height, $color );
	}

	/**
	 * Draw the optional background image, stretched to fill the canvas.
	 *
	 * @since 1.4.0
	 *
	 * @param  GdImage         $image    The image resource.
	 * @param  OgImageTemplate $template The template.
	 *
	 * @return void
	 */
	protected function drawBackgroundImage( GdImage $image, OgImageTemplate $template ): void
	{
		$path = $template->backgroundImagePath;

		if ( null === $path || ! is_file( $path ) ) {
			return;
		}

		$bg = $this->loadImage( $path );

		if ( null === $bg ) {
			return;
		}

		try {
			imagecopyresampled(
				$image,
				$bg,
				0,
				0,
				0,
				0,
				$template->width,
				$template->height,
				imagesx( $bg ),
				imagesy( $bg ),
			);
		} finally {
			imagedestroy( $bg );
		}
	}

	/**
	 * Composite a semi-transparent scrim over the background image so
	 * the title / subtitle text has usable contrast on top of any real
	 * photo (#97). No-op when {@see OgImageTemplate::$backgroundImagePath}
	 * is null — plain-colored backgrounds don't need a scrim (the operator
	 * already picked the text and background colors together), and adding
	 * one there would just wash their palette out.
	 *
	 * Opacity is exposed as a percentage 0-100. Internally we convert
	 * to GD's inverted alpha range (0 = opaque, 127 = fully transparent).
	 * `backgroundScrimGradient = true` fades the scrim from ~25% of the
	 * configured opacity at the top down to full opacity at the bottom —
	 * the title sits center-canvas and the subtitle sits near the bottom,
	 * so concentrating the darkening in the lower half preserves the most
	 * visible background image while still giving the text a dark backdrop.
	 * A flat scrim is drawn as a single filled rectangle for cheapness.
	 *
	 * The scrim uses `alphablending = true` so it composites correctly
	 * onto the RGB background; we restore whatever alphablending mode was
	 * active before returning so the subsequent PNG-with-alpha logo draw
	 * behaves as {@see drawLogo()} expects.
	 *
	 * @since 1.6.0
	 *
	 * @param  GdImage         $image    The image resource.
	 * @param  OgImageTemplate $template The template.
	 *
	 * @return void
	 */
	protected function drawBackgroundScrim( GdImage $image, OgImageTemplate $template ): void
	{
		// Guard clauses: no background image → no scrim needed;
		// opacity <= 0 → operator opted out.
		if ( null === $template->backgroundImagePath ) {
			return;
		}

		$opacityPercent = max( 0, min( 100, $template->backgroundScrimOpacity ) );

		if ( 0 === $opacityPercent ) {
			return;
		}

		[ $r, $g, $b ] = $this->hexToRgb( $template->backgroundScrimColor );

		// Percentage → GD alpha. GD's alpha channel is inverted:
		// 0 = fully opaque, 127 = fully transparent. `intval` because
		// alpha must be an int for `imagecolorallocatealpha`.
		$maxAlpha = (int) round( 127 - ( $opacityPercent * 127 / 100 ) );

		// Enable alpha blending so the scrim composites onto the
		// background image rather than replacing it pixel-for-pixel.
		// Turn savealpha off during the composite so the blended pixel
		// values overwrite the canvas alpha (which was preserved from
		// the logo pass); we'll re-enable savealpha before returning.
		imagealphablending( $image, true );
		imagesavealpha( $image, false );

		try {
			if ( $template->backgroundScrimGradient ) {
				$this->drawScrimGradient( $image, $template, $r, $g, $b, $maxAlpha );
			} else {
				$this->drawScrimFlat( $image, $template, $r, $g, $b, $maxAlpha );
			}
		} finally {
			imagesavealpha( $image, true );
		}
	}

	/**
	 * Draw the scrim as a single flat rectangle at the configured
	 * opacity. Cheapest, safest path — used when
	 * `backgroundScrimGradient = false`.
	 *
	 * @since 1.6.0
	 */
	protected function drawScrimFlat( GdImage $image, OgImageTemplate $template, int $r, int $g, int $b, int $alpha ): void
	{
		$color = imagecolorallocatealpha( $image, $r, $g, $b, $alpha );

		if ( false === $color ) {
			return;
		}

		imagefilledrectangle( $image, 0, 0, $template->width, $template->height, $color );
	}

	/**
	 * Draw the scrim as a vertical gradient of horizontal 1-pixel
	 * bands, fading from ~25% of the configured opacity at the top to
	 * full opacity at the bottom. The exact top-of-range multiplier
	 * (0.25) is a taste call — enough to keep the top of the image
	 * darker where the logo sits, not so much that the sky/highlights
	 * of a bright photo swallow the logo.
	 *
	 * Bands are 4 pixels tall rather than 1 to keep the per-render
	 * cost bounded for 1200x630 canvases (that's 158 filled rectangles
	 * instead of 630). At OG image size the seams aren't visible.
	 *
	 * @since 1.6.0
	 */
	protected function drawScrimGradient( GdImage $image, OgImageTemplate $template, int $r, int $g, int $b, int $maxAlpha ): void
	{
		$bandHeight = 4;
		$topAlpha   = 127 - (int) round( ( 127 - $maxAlpha ) * 0.25 );
		$height     = max( 1, $template->height );

		for ( $y = 0; $y < $height; $y += $bandHeight ) {
			$ratio = $y / max( 1, ( $height - 1 ) );
			$alpha = (int) round( $topAlpha + ( ( $maxAlpha - $topAlpha ) * $ratio ) );
			$alpha = max( 0, min( 127, $alpha ) );

			$color = imagecolorallocatealpha( $image, $r, $g, $b, $alpha );

			if ( false === $color ) {
				continue;
			}

			$y2 = min( $height - 1, $y + $bandHeight - 1 );
			imagefilledrectangle( $image, 0, $y, $template->width, $y2, $color );
		}
	}

	/**
	 * Draw the optional logo in the top-left corner, respecting padding.
	 *
	 * @since 1.4.0
	 *
	 * @param  GdImage         $image    The image resource.
	 * @param  OgImageTemplate $template The template.
	 *
	 * @return void
	 */
	protected function drawLogo( GdImage $image, OgImageTemplate $template ): void
	{
		$path = $template->logoPath;

		if ( null === $path || ! is_file( $path ) ) {
			return;
		}

		$logo = $this->loadImage( $path );

		if ( null === $logo ) {
			return;
		}

		try {
			$sourceWidth  = imagesx( $logo );
			$sourceHeight = imagesy( $logo );

			if ( 0 === $sourceWidth || 0 === $sourceHeight ) {
				return;
			}

			$targetWidth  = $template->logoWidth;
			$targetHeight = (int) round( $sourceHeight * ( $targetWidth / $sourceWidth ) );

			imagealphablending( $image, true );
			imagecopyresampled(
				$image,
				$logo,
				$template->padding,
				$template->padding,
				0,
				0,
				$targetWidth,
				$targetHeight,
				$sourceWidth,
				$sourceHeight,
			);
		} finally {
			imagedestroy( $logo );
		}
	}

	/**
	 * Draw the title, wrapped to fit the available width.
	 *
	 * The title sits above the vertical center so a subtitle drawn below
	 * (see {@see drawSubtitle()}) doesn't overlap it.
	 *
	 * @since 1.4.0
	 *
	 * @param  GdImage         $image    The image resource.
	 * @param  OgImageTemplate $template The template.
	 * @param  string          $title    The title text.
	 *
	 * @return void
	 */
	protected function drawTitle( GdImage $image, OgImageTemplate $template, string $title ): void
	{
		[ $r, $g, $b ] = $this->hexToRgb( $template->textColor );
		$color         = imagecolorallocate( $image, $r, $g, $b );

		if ( false === $color ) {
			return;
		}

		$maxWidth = $template->width - ( 2 * $template->padding );
		$lines    = $this->wrapText( $title, $template->fontPath, $template->titleFontSize, $maxWidth );
		$lineGap  = (int) round( $template->titleFontSize * 0.4 );
		$lineH    = $template->titleFontSize + $lineGap;

		$blockHeight = ( count( $lines ) * $lineH ) - $lineGap;
		$startY      = (int) round( ( $template->height / 2 ) - ( $blockHeight / 2 ) );

		foreach ( $lines as $index => $line ) {
			$this->drawTextLine(
				$image,
				$template->fontPath,
				$template->titleFontSize,
				$template->padding,
				$startY + ( $index * $lineH ),
				$color,
				$line,
			);
		}
	}

	/**
	 * Draw the subtitle beneath the title block.
	 *
	 * @since 1.4.0
	 *
	 * @param  GdImage         $image    The image resource.
	 * @param  OgImageTemplate $template The template.
	 * @param  string          $subtitle The subtitle text.
	 *
	 * @return void
	 */
	protected function drawSubtitle( GdImage $image, OgImageTemplate $template, string $subtitle ): void
	{
		[ $r, $g, $b ] = $this->hexToRgb( $template->subtitleColor );
		$color         = imagecolorallocate( $image, $r, $g, $b );

		if ( false === $color ) {
			return;
		}

		$y = $template->height - $template->padding - $template->subtitleFontSize;

		$this->drawTextLine(
			$image,
			$template->fontPath,
			$template->subtitleFontSize,
			$template->padding,
			$y,
			$color,
			$subtitle,
		);
	}

	/**
	 * Draw a single line of text using TTF when available, otherwise bitmap.
	 *
	 * @since 1.4.0
	 *
	 * @param  GdImage       $image    The image resource.
	 * @param  string|null   $fontPath Path to a TTF font, or null for bitmap.
	 * @param  int           $size     Font size in points (TTF) or 1-5 (bitmap).
	 * @param  int           $x        Left X coordinate for the text baseline (TTF) or top-left (bitmap).
	 * @param  int           $y        Top Y coordinate for the text.
	 * @param  int           $color    Allocated GD color.
	 * @param  string        $text     The text to draw.
	 *
	 * @return void
	 */
	protected function drawTextLine(
		GdImage $image,
		?string $fontPath,
		int $size,
		int $x,
		int $y,
		int $color,
		string $text,
	): void {
		if ( null !== $fontPath && is_file( $fontPath ) && function_exists( 'imagettftext' ) ) {
			imagettftext( $image, $size, 0, $x, $y + $size, $color, $fontPath, $text );
			return;
		}

		$bitmapFont = $this->bitmapFontFor( $size );
		imagestring( $image, $bitmapFont, $x, $y, $text, $color );
	}

	/**
	 * Wrap text into an array of lines that fit within a max pixel width.
	 *
	 * @since 1.4.0
	 *
	 * @param  string       $text     The text to wrap.
	 * @param  string|null  $fontPath Path to a TTF font, or null.
	 * @param  int          $size     Font size in points (TTF) or 1-5 (bitmap).
	 * @param  int          $maxWidth Maximum line width in pixels.
	 *
	 * @return array<int, string> The wrapped lines.
	 */
	protected function wrapText( string $text, ?string $fontPath, int $size, int $maxWidth ): array
	{
		$words = preg_split( '/\s+/', trim( $text ) ) ?: [ $text ];
		$lines = [];
		$line  = '';

		foreach ( $words as $word ) {
			$candidate = '' === $line ? $word : $line . ' ' . $word;
			$width     = $this->measureWidth( $candidate, $fontPath, $size );

			if ( $width > $maxWidth && '' !== $line ) {
				$lines[] = $line;
				$line    = $word;
				continue;
			}

			$line = $candidate;
		}

		if ( '' !== $line ) {
			$lines[] = $line;
		}

		return $lines;
	}

	/**
	 * Measure the rendered width of a piece of text.
	 *
	 * @since 1.4.0
	 *
	 * @param  string       $text     The text to measure.
	 * @param  string|null  $fontPath Path to a TTF font, or null.
	 * @param  int          $size     Font size in points (TTF) or 1-5 (bitmap).
	 *
	 * @return int Width in pixels.
	 */
	protected function measureWidth( string $text, ?string $fontPath, int $size ): int
	{
		if ( null !== $fontPath && is_file( $fontPath ) && function_exists( 'imagettfbbox' ) ) {
			$box = imagettfbbox( $size, 0, $fontPath, $text );

			if ( is_array( $box ) ) {
				return (int) abs( $box[2] - $box[0] );
			}
		}

		$bitmapFont = $this->bitmapFontFor( $size );

		// Use mb_strlen so multibyte characters are counted once each; the
		// bitmap fallback still renders only Latin-1, so warn the caller
		// when the text is not ASCII.
		if ( 1 !== preg_match( '/^[\x20-\x7E]*$/', $text ) ) {
			Log::warning( 'OG image bitmap fallback rendering non-ASCII text will produce mojibake; provide a TTF font path.', [
				'text_length' => mb_strlen( $text, 'UTF-8' ),
			] );
		}

		return imagefontwidth( $bitmapFont ) * mb_strlen( $text, 'UTF-8' );
	}

	/**
	 * Map a requested font size to a GD bitmap font index (1-5).
	 *
	 * @since 1.4.0
	 *
	 * @param  int $size Requested size.
	 *
	 * @return int A GD bitmap font index in the range 1-5.
	 */
	protected function bitmapFontFor( int $size ): int
	{
		if ( $size >= 40 ) {
			return 5;
		}

		if ( $size >= 24 ) {
			return 4;
		}

		if ( $size >= 16 ) {
			return 3;
		}

		if ( $size >= 10 ) {
			return 2;
		}

		return 1;
	}

	/**
	 * Load an image from disk as a GD resource, handling PNG/JPEG/GIF/WEBP.
	 *
	 * @since 1.4.0
	 *
	 * @param  string $path Absolute path to the source image.
	 *
	 * @return GdImage|null The GD image resource, or null on failure.
	 */
	protected function loadImage( string $path ): ?GdImage
	{
		$info = @getimagesize( $path );

		if ( false === $info ) {
			return null;
		}

		$loader = match ( $info[2] ) {
			IMAGETYPE_PNG   => 'imagecreatefrompng',
			IMAGETYPE_JPEG  => 'imagecreatefromjpeg',
			IMAGETYPE_GIF   => 'imagecreatefromgif',
			IMAGETYPE_WEBP  => 'imagecreatefromwebp',
			default         => null,
		};

		if ( null === $loader || ! function_exists( $loader ) ) {
			return null;
		}

		$resource = @$loader( $path );

		if ( ! $resource instanceof GdImage ) {
			return null;
		}

		// Preserve PNG alpha channel so a transparent logo doesn't
		// composite onto a black rectangle (P1-7).
		if ( IMAGETYPE_PNG === $info[2] || IMAGETYPE_WEBP === $info[2] ) {
			imagealphablending( $resource, false );
			imagesavealpha( $resource, true );
		}

		return $resource;
	}

	/**
	 * Convert a "#rrggbb" or "#rgb" hex color to an RGB triple.
	 *
	 * Invalid input falls back to black so the renderer never throws
	 * on a malformed template color — the image still comes out.
	 *
	 * @since 1.4.0
	 *
	 * @param  string $hex The hex color.
	 *
	 * @return array{0:int,1:int,2:int}
	 */
	protected function hexToRgb( string $hex ): array
	{
		$hex = ltrim( trim( $hex ), '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) || 1 !== preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
			Log::warning( 'Malformed hex color supplied to OG image renderer; defaulting to white to avoid black-on-black cards.', [
				'value' => $hex,
			] );

			return [ 255, 255, 255 ];
		}

		return [
			(int) hexdec( substr( $hex, 0, 2 ) ),
			(int) hexdec( substr( $hex, 2, 2 ) ),
			(int) hexdec( substr( $hex, 4, 2 ) ),
		];
	}
}
