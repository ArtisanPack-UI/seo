<?php

/**
 * HasPublicUrl.
 *
 * Optional per-model contract for declaring where a model lives on the
 * public site, so canonical tags, Open Graph, schema and sitemaps all
 * report the same URL.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.9.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Contracts;

/**
 * HasPublicUrl interface.
 *
 * Models implementing this contract are the second tier of
 * {@see \ArtisanPackUI\SEO\Support\PublicUrlResolver}, after a saved
 * canonical URL. Don't read `canonical_url` from the implementation, because
 * that accessor runs the resolver and would call this method again.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.9.0
 */
interface HasPublicUrl
{
	/**
	 * Return the model's absolute public URL.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function getPublicUrl(): string;
}
