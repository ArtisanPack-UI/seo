<?php

/**
 * OgImageRendererContract.
 *
 * Interface for image backends that render OG social-share cards.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Contracts;

use ArtisanPackUI\SEO\DTOs\OgImageTemplate;

/**
 * OgImageRendererContract interface.
 *
 * Implement this interface to plug in an alternative image backend
 * (Imagick, headless Chromium, etc.). The default implementation
 * shipped with the package is {@see \ArtisanPackUI\SEO\Services\OgImage\GdOgImageRenderer}.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
interface OgImageRendererContract
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
	public function render( OgImageTemplate $template, string $title, ?string $subtitle = null ): string;
}
