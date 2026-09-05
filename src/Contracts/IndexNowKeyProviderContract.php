<?php

/**
 * IndexNowKeyProviderContract.
 *
 * Interface for IndexNow key management.
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

/**
 * IndexNowKeyProviderContract interface.
 *
 * Implement this to plug in custom IndexNow key management — e.g.
 * generating a per-site key on install, rotating it, or serving the
 * verification file from a non-standard location.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
interface IndexNowKeyProviderContract
{
	/**
	 * Get the IndexNow key.
	 *
	 * Must be a hex string 8–128 characters long per the IndexNow spec.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function getKey(): string;

	/**
	 * Get the fully-qualified URL where the key's verification file is hosted.
	 *
	 * Return `null` to let IndexNow assume the default location
	 * (`https://{host}/{key}.txt`).
	 *
	 * When the submitter groups a submission across multiple hosts, it
	 * passes the URL batch so the provider can validate that the
	 * configured keyLocation actually covers every URL in that payload
	 * (per the IndexNow spec, the keyLocation host must match the payload
	 * host and its path must be a prefix of every URL's path). Providers
	 * that can safely return the same location for any batch may ignore
	 * the argument; providers that cannot must return `null` when the
	 * configured location is out of scope for the batch, so the submitter
	 * falls back to the default per-host `/{key}.txt` location.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<int, string>|null  $urlBatch  Optional batch of URLs the
	 *                                              location must cover. `null`
	 *                                              means "no batch scope" — the
	 *                                              provider may return its
	 *                                              default location.
	 *
	 * @return string|null
	 */
	public function getKeyLocation( ?array $urlBatch = null ): ?string;
}
