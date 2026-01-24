<?php

/**
 * PackageDetector.
 *
 * Utility class for detecting the presence of optional ArtisanPack UI packages.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Support;

/**
 * PackageDetector class.
 *
 * Provides static methods to check if optional packages are installed.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class PackageDetector
{
	/**
	 * Check if the media library package is installed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if media library is available.
	 */
	public static function hasMediaLibrary(): bool
	{
		return class_exists( \ArtisanPackUI\MediaLibrary\Models\Media::class );
	}

	/**
	 * Check if the hooks package is installed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if hooks package is available.
	 */
	public static function hasHooks(): bool
	{
		return function_exists( 'addFilter' ) || function_exists( 'applyFilters' );
	}

	/**
	 * Check if the accessibility package is installed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if accessibility package is available.
	 */
	public static function hasAccessibility(): bool
	{
		return class_exists( \ArtisanPackUI\Accessibility\AccessibleColorGenerator::class );
	}

	/**
	 * Check if the CMS framework package is installed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if CMS framework is available.
	 */
	public static function hasCmsFramework(): bool
	{
		return class_exists( \ArtisanPackUI\CmsFramework\Models\GlobalContent::class );
	}

	/**
	 * Check if the analytics package is installed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if analytics package is available.
	 */
	public static function hasAnalytics(): bool
	{
		return class_exists( \ArtisanPackUI\Analytics\Services\SearchConsoleService::class );
	}

	/**
	 * Check if the visual editor package is installed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if visual editor package is available.
	 */
	public static function hasVisualEditor(): bool
	{
		return class_exists( \ArtisanPackUI\VisualEditor\VisualEditor::class );
	}
}
