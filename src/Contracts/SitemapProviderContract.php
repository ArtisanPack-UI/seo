<?php

/**
 * SitemapProviderContract.
 *
 * Interface for custom sitemap content providers.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 * @copyright  2026 Jacob Martella
 * @license    MIT
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Contracts;

use Illuminate\Support\Collection;

/**
 * SitemapProviderContract interface.
 *
 * Implement this interface to create custom sitemap content providers
 * that can be registered with the sitemap service.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
interface SitemapProviderContract
{
	/**
	 * Get all URLs for this sitemap provider.
	 *
	 * Each item in the collection should be an array or object containing:
	 * - loc: The URL (required)
	 * - lastmod: Last modification date (optional)
	 * - changefreq: Change frequency (optional)
	 * - priority: Priority 0.0-1.0 (optional)
	 * - images: Array of images (optional, for image sitemaps)
	 * - videos: Array of videos (optional, for video sitemaps)
	 *
	 * @since 1.0.0
	 *
	 * @return Collection<int, array<string, mixed>>
	 */
	public function getUrls(): Collection;

	/**
	 * Get the default change frequency for this provider.
	 *
	 * Valid values: always, hourly, daily, weekly, monthly, yearly, never
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getChangeFrequency(): string;

	/**
	 * Get the default priority for this provider.
	 *
	 * @since 1.0.0
	 *
	 * @return float Value between 0.0 and 1.0
	 */
	public function getPriority(): float;

	/**
	 * Get the type identifier for this provider.
	 *
	 * This is used to group URLs and create separate sitemap files.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getType(): string;
}
