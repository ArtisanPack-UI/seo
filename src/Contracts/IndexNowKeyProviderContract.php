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
	 * @since 1.4.0
	 *
	 * @return string|null
	 */
	public function getKeyLocation(): ?string;
}
