<?php

/**
 * OgImageTemplate.
 *
 * Data Transfer Object describing the template used to render an OG image.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\DTOs;

/**
 * OgImageTemplate class.
 *
 * Represents a resolved template for the OG image generator. The service
 * merges a site-wide default template with per-page overrides into an
 * instance of this DTO before handing it to the renderer.
 *
 * All values are captured on construction so the signature can be hashed
 * deterministically for caching (see {@see signature()}).
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
readonly class OgImageTemplate
{
	/**
	 * Create a new OgImageTemplate instance.
	 *
	 * @since 1.4.0
	 *
	 * @param  int          $width               Image width in pixels.
	 * @param  int          $height              Image height in pixels.
	 * @param  string       $backgroundColor     Background color (hex, e.g. "#0f172a").
	 * @param  string       $textColor           Primary text color (hex).
	 * @param  string       $subtitleColor       Subtitle text color (hex).
	 * @param  string|null  $backgroundImagePath Absolute path to an optional background image.
	 * @param  string|null  $logoPath            Absolute path to an optional logo overlay.
	 * @param  int          $logoWidth           Logo target width in pixels (aspect preserved).
	 * @param  string|null  $fontPath            Absolute path to a TTF font, or null for GD bitmap fallback.
	 * @param  int          $titleFontSize       Title font size in points (TTF) or 1-5 (bitmap fallback).
	 * @param  int          $subtitleFontSize    Subtitle font size in points (TTF) or 1-5 (bitmap fallback).
	 * @param  int          $padding             Inner padding in pixels.
	 */
	public function __construct(
		public int $width = 1200,
		public int $height = 630,
		public string $backgroundColor = '#0f172a',
		public string $textColor = '#ffffff',
		public string $subtitleColor = '#94a3b8',
		public ?string $backgroundImagePath = null,
		public ?string $logoPath = null,
		public int $logoWidth = 160,
		public ?string $fontPath = null,
		public int $titleFontSize = 56,
		public int $subtitleFontSize = 28,
		public int $padding = 80,
	) {
	}

	/**
	 * Build a template from a config array, applying defaults for missing keys.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<string, mixed>  $config    The template config (typically from config('seo.og_image.template')).
	 * @param  array<string, mixed>  $overrides Per-page overrides that win over the config values.
	 *
	 * @return self
	 */
	public static function fromConfig( array $config, array $overrides = [] ): self
	{
		$merged  = array_merge( $config, $overrides );
		$default = new self();

		return new self(
			width: (int) ( $merged['width'] ?? $default->width ),
			height: (int) ( $merged['height'] ?? $default->height ),
			backgroundColor: (string) ( $merged['background_color'] ?? $default->backgroundColor ),
			textColor: (string) ( $merged['text_color'] ?? $default->textColor ),
			subtitleColor: (string) ( $merged['subtitle_color'] ?? $default->subtitleColor ),
			backgroundImagePath: $merged['background_image_path'] ?? $default->backgroundImagePath,
			logoPath: $merged['logo_path'] ?? $default->logoPath,
			logoWidth: (int) ( $merged['logo_width'] ?? $default->logoWidth ),
			fontPath: $merged['font_path'] ?? $default->fontPath,
			titleFontSize: (int) ( $merged['title_font_size'] ?? $default->titleFontSize ),
			subtitleFontSize: (int) ( $merged['subtitle_font_size'] ?? $default->subtitleFontSize ),
			padding: (int) ( $merged['padding'] ?? $default->padding ),
		);
	}

	/**
	 * Return a deterministic signature for this template.
	 *
	 * Used by the service to build the cache key so that a template change
	 * invalidates previously generated images without any manual step.
	 *
	 * @since 1.4.0
	 *
	 * @return string A short hex digest of the template's fields.
	 */
	public function signature(): string
	{
		return substr( hash( 'sha256', (string) json_encode( [
			$this->width,
			$this->height,
			$this->backgroundColor,
			$this->textColor,
			$this->subtitleColor,
			$this->backgroundImagePath,
			$this->logoPath,
			$this->logoWidth,
			$this->fontPath,
			$this->titleFontSize,
			$this->subtitleFontSize,
			$this->padding,
		] ) ), 0, 12 );
	}
}
