<?php

/**
 * MediaLibraryIntegration.
 *
 * Service class for integrating with the optional artisanpack-ui/media-library package.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\Support\PackageDetector;

/**
 * MediaLibraryIntegration class.
 *
 * Provides methods to interact with the media library package
 * for social image management. Gracefully handles missing package.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class MediaLibraryIntegration
{
	/**
	 * The social image size name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public const SOCIAL_IMAGE_SIZE = 'social';

	/**
	 * Social image width (recommended for OG/Twitter).
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public const SOCIAL_IMAGE_WIDTH = 1200;

	/**
	 * Social image height (recommended for OG/Twitter).
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public const SOCIAL_IMAGE_HEIGHT = 630;

	/**
	 * Check if the media library is available.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if media library is installed.
	 */
	public function isAvailable(): bool
	{
		return PackageDetector::hasMediaLibrary();
	}

	/**
	 * Get a media URL by ID with optional size.
	 *
	 * @since 1.0.0
	 *
	 * @param  int|null $mediaId The media ID.
	 * @param  string   $size    The image size (thumbnail, medium, large, full, social).
	 *
	 * @return string|null The media URL or null if not found.
	 */
	public function getMediaUrl( ?int $mediaId, string $size = 'large' ): ?string
	{
		if ( null === $mediaId || ! $this->isAvailable() ) {
			return null;
		}

		// Use the apGetMediaUrl helper if available
		if ( function_exists( 'apGetMediaUrl' ) ) {
			return apGetMediaUrl( $mediaId, $size );
		}

		// Fallback: try to get the media directly
		return $this->getMediaUrlDirect( $mediaId, $size );
	}

	/**
	 * Get a social-optimized image URL (1200x630).
	 *
	 * Falls back to 'large' size if social size is not available.
	 *
	 * @since 1.0.0
	 *
	 * @param  int|null $mediaId The media ID.
	 *
	 * @return string|null The social image URL or null if not found.
	 */
	public function getSocialImageUrl( ?int $mediaId ): ?string
	{
		if ( null === $mediaId || ! $this->isAvailable() ) {
			return null;
		}

		// Try social size first, fall back to large
		$url = $this->getMediaUrl( $mediaId, self::SOCIAL_IMAGE_SIZE );

		if ( null === $url ) {
			$url = $this->getMediaUrl( $mediaId, 'large' );
		}

		return $url;
	}

	/**
	 * Get the media model by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $mediaId The media ID.
	 *
	 * @return object|null The media model or null.
	 */
	public function getMedia( int $mediaId ): ?object
	{
		if ( ! $this->isAvailable() ) {
			return null;
		}

		// Use the apGetMedia helper if available
		if ( function_exists( 'apGetMedia' ) ) {
			return apGetMedia( $mediaId );
		}

		// Fallback: try to get the media directly
		$mediaClass = \ArtisanPackUI\MediaLibrary\Models\Media::class;

		return $mediaClass::find( $mediaId );
	}

	/**
	 * Register the social image size with the media library.
	 *
	 * This should be called during application boot.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function registerSocialImageSize(): void
	{
		if ( ! $this->isAvailable() ) {
			return;
		}

		// Use the apRegisterImageSize helper if available
		if ( function_exists( 'apRegisterImageSize' ) ) {
			apRegisterImageSize(
				self::SOCIAL_IMAGE_SIZE,
				self::SOCIAL_IMAGE_WIDTH,
				self::SOCIAL_IMAGE_HEIGHT,
				true, // Crop to exact dimensions
			);
		}
	}

	/**
	 * Get the media URL directly from the model.
	 *
	 * @since 1.0.0
	 *
	 * @param  int    $mediaId The media ID.
	 * @param  string $size    The image size.
	 *
	 * @return string|null The media URL or null.
	 */
	protected function getMediaUrlDirect( int $mediaId, string $size ): ?string
	{
		$media = $this->getMedia( $mediaId );

		if ( null === $media ) {
			return null;
		}

		// Try to get the URL for the specific size
		if ( method_exists( $media, 'imageUrl' ) ) {
			$url = $media->imageUrl( $size );
			if ( null !== $url ) {
				return $url;
			}
		}

		// Fall back to the base URL method
		if ( method_exists( $media, 'url' ) ) {
			return $media->url();
		}

		return null;
	}
}
