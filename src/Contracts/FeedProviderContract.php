<?php

/**
 * FeedProviderContract.
 *
 * Interface for consumer-supplied RSS/Atom feed entry sources.
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

use ArtisanPackUI\SEO\DTOs\FeedEntryDTO;
use Illuminate\Support\Collection;

/**
 * FeedProviderContract interface.
 *
 * Consumers implement this to feed entries from any source (blog archive,
 * per-type collections, external data, etc.) into the FeedGenerator.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
interface FeedProviderContract
{
	/**
	 * Get the entries for this feed.
	 *
	 * @since 1.4.0
	 *
	 * @return Collection<int, FeedEntryDTO>
	 */
	public function getEntries(): Collection;

	/**
	 * Get the feed title.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function getTitle(): string;

	/**
	 * Get the feed description.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function getDescription(): string;

	/**
	 * Get the canonical HTML page URL the feed represents.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function getLink(): string;

	/**
	 * Get the absolute URL where the feed itself is served.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function getFeedUrl(): string;
}
