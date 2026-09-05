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
use Illuminate\Support\Facades\Log;
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
	 * When a batch is supplied, the configured location is validated
	 * against every URL in the batch: the host must match and the
	 * location's path must be a prefix of every URL path. If the
	 * configured location cannot cover the batch, returns `null` so the
	 * submitter falls back to the default per-host location; a warning is
	 * logged so operators can spot mis-scoped configuration.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<int, string>|null  $urlBatch  Optional batch of URLs the
	 *                                              location must cover.
	 *
	 * @return string|null
	 */
	public function getKeyLocation( ?array $urlBatch = null ): ?string
	{
		$location = config( 'seo.indexnow.key_location' );

		if ( ! is_string( $location ) || '' === trim( $location ) ) {
			return null;
		}

		if ( null === $urlBatch || [] === $urlBatch ) {
			return $location;
		}

		$parsedLocation = parse_url( $location );

		if ( false === $parsedLocation || ! isset( $parsedLocation['host'] ) ) {
			return null;
		}

		$locationHost = strtolower( $parsedLocation['host'] );
		$locationPath = $parsedLocation['path'] ?? '/';
		// Drop the filename so the comparison is against the directory
		// prefix — a keyLocation of https://a.com/foo/key.txt covers
		// /foo/... URLs, not just /foo/key.txt.
		$locationDir = rtrim( substr( $locationPath, 0, strrpos( $locationPath, '/' ) + 1 ), '/' );

		foreach ( $urlBatch as $url ) {
			$parsed = parse_url( $url );

			if ( false === $parsed || ! isset( $parsed['host'] ) ) {
				Log::warning( 'IndexNow: skipping keyLocation because a URL in the batch could not be parsed.', [
					'keyLocation' => $location,
					'url'         => $url,
				] );

				return null;
			}

			if ( strtolower( $parsed['host'] ) !== $locationHost ) {
				Log::warning( 'IndexNow: skipping keyLocation because it does not cover the batch host.', [
					'keyLocation' => $location,
					'batchHost'   => $parsed['host'],
				] );

				return null;
			}

			$urlPath = $parsed['path'] ?? '/';

			if ( '' !== $locationDir && ! str_starts_with( $urlPath, $locationDir . '/' ) && $urlPath !== $locationDir ) {
				Log::warning( 'IndexNow: skipping keyLocation because it does not cover the batch URL path.', [
					'keyLocation' => $location,
					'urlPath'     => $urlPath,
				] );

				return null;
			}
		}

		return $location;
	}
}
