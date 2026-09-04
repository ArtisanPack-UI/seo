<?php

/**
 * ConfigIndexNowKeyProvider.
 *
 * Default IndexNow key provider that reads from package config.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\IndexNow;

use ArtisanPackUI\SEO\Contracts\IndexNowKeyProviderContract;
use RuntimeException;

/**
 * ConfigIndexNowKeyProvider class.
 *
 * Reads the IndexNow key and optional key location from
 * `seo.indexnow.key` / `seo.indexnow.key_location`. Consumers that
 * need dynamic key management (per-tenant, rotating, DB-backed) bind
 * their own {@see IndexNowKeyProviderContract} implementation in the
 * container.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
class ConfigIndexNowKeyProvider implements IndexNowKeyProviderContract
{
	/**
	 * Get the IndexNow key from config.
	 *
	 * @since 1.4.0
	 *
	 * @throws RuntimeException When `seo.indexnow.key` is unset or empty.
	 *
	 * @return string
	 */
	public function getKey(): string
	{
		$key = config( 'seo.indexnow.key' );

		if ( ! is_string( $key ) || '' === trim( $key ) ) {
			throw new RuntimeException(
				'IndexNow key is not configured. Set seo.indexnow.key (env SEO_INDEXNOW_KEY) or bind a custom IndexNowKeyProviderContract.',
			);
		}

		return $key;
	}

	/**
	 * Get the key location URL from config, if configured.
	 *
	 * @since 1.4.0
	 *
	 * @return string|null
	 */
	public function getKeyLocation(): ?string
	{
		$location = config( 'seo.indexnow.key_location' );

		if ( ! is_string( $location ) || '' === trim( $location ) ) {
			return null;
		}

		return $location;
	}
}
