<?php

/**
 * SitemapObserver.
 *
 * Observer for handling sitemap-related events on models using the HasSeo trait.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Observers;

use ArtisanPackUI\SEO\Models\SitemapEntry;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * SitemapObserver class.
 *
 * Observes model events for models using the HasSeo trait and handles
 * automatic creation, update, and deletion of sitemap entries.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SitemapObserver
{
	/**
	 * Handle the model "saved" event.
	 *
	 * Creates or updates the sitemap entry when a model is saved.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model that was saved.
	 *
	 * @return void
	 */
	public function saved( Model $model ): void
	{
		// If sitemap tracking is disabled for this model, remove any existing entry
		if ( ! $this->shouldTrackInSitemap( $model ) ) {
			$this->deleteSitemapEntry( $model );

			return;
		}

		$this->updateOrCreateSitemapEntry( $model );
	}

	/**
	 * Handle the model "deleted" event.
	 *
	 * Deletes the sitemap entry when a model is deleted.
	 * For soft-deleted models, the entry is preserved until force delete.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model that was deleted.
	 *
	 * @return void
	 */
	public function deleted( Model $model ): void
	{
		// Only delete sitemap entry on force delete, not soft delete
		if ( $this->isForceDeleting( $model ) ) {
			$this->deleteSitemapEntry( $model );
		}
	}

	/**
	 * Handle the model "restored" event (for soft-deleted models).
	 *
	 * Re-enables the sitemap entry when a model is restored.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model that was restored.
	 *
	 * @return void
	 */
	public function restored( Model $model ): void
	{
		// Re-create or update sitemap entry when model is restored
		if ( $this->shouldTrackInSitemap( $model ) ) {
			$this->updateOrCreateSitemapEntry( $model );
		}
	}

	/**
	 * Check if the model should be tracked in sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to check.
	 *
	 * @return bool
	 */
	protected function shouldTrackInSitemap( Model $model ): bool
	{
		// Check if model has seoMeta relationship and uses HasSeo trait
		if ( ! method_exists( $model, 'shouldBeInSitemap' ) ) {
			return true; // Default to tracking if method doesn't exist
		}

		return $model->shouldBeInSitemap();
	}

	/**
	 * Update or create a sitemap entry for a model.
	 *
	 * If the model has no valid URL or a URL conflict occurs (another model
	 * has the same URL), the entry will not be created.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to create entry for.
	 *
	 * @return void
	 */
	protected function updateOrCreateSitemapEntry( Model $model ): void
	{
		$url = $this->getModelUrl( $model );

		// Skip if no valid URL can be determined
		if ( null === $url || '' === $url ) {
			return;
		}

		$type         = $this->getModelType( $model );
		$priority     = $this->getModelPriority( $model );
		$changefreq   = $this->getModelChangefreq( $model );
		$isIndexable  = $this->getModelIndexable( $model );
		$lastModified = $this->getModelLastModified( $model );
		$images       = $this->getModelImages( $model );
		$videos       = $this->getModelVideos( $model );

		// Check if URL already exists for a different model
		$existingEntry = SitemapEntry::where( 'url', $url )
			->where( function ( $query ) use ( $model ): void {
				$query->where( 'sitemapable_type', '!=', get_class( $model ) )
					->orWhere( 'sitemapable_id', '!=', $model->getKey() );
			} )
			->first();

		// Skip if URL already belongs to another model
		if ( null !== $existingEntry ) {
			return;
		}

		SitemapEntry::updateOrCreate(
			[
				'sitemapable_type' => get_class( $model ),
				'sitemapable_id'   => $model->getKey(),
			],
			[
				'url'           => $url,
				'type'          => $type,
				'priority'      => $priority,
				'changefreq'    => $changefreq,
				'is_indexable'  => $isIndexable,
				'last_modified' => $lastModified,
				'images'        => $images,
				'videos'        => $videos,
			],
		);
	}

	/**
	 * Delete the sitemap entry for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to delete entry for.
	 *
	 * @return void
	 */
	protected function deleteSitemapEntry( Model $model ): void
	{
		SitemapEntry::query()
			->where( 'sitemapable_type', get_class( $model ) )
			->where( 'sitemapable_id', $model->getKey() )
			->delete();
	}

	/**
	 * Check if the model is being force deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to check.
	 *
	 * @return bool
	 */
	protected function isForceDeleting( Model $model ): bool
	{
		// If model doesn't use SoftDeletes, any delete is a force delete
		if ( ! method_exists( $model, 'isForceDeleting' ) ) {
			return true;
		}

		return $model->isForceDeleting();
	}

	/**
	 * Get the URL for a model.
	 *
	 * Returns null if no valid URL can be determined, which signals that
	 * the sitemap entry should not be created.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get URL for.
	 *
	 * @return string|null
	 */
	protected function getModelUrl( Model $model ): ?string
	{
		// Try canonical_url from SEO meta first
		if ( method_exists( $model, 'getCanonicalUrlAttribute' ) ) {
			$canonicalUrl = $model->canonical_url;
			if ( null !== $canonicalUrl && '' !== $canonicalUrl ) {
				return $canonicalUrl;
			}
		}

		// Try getUrl method
		if ( method_exists( $model, 'getUrl' ) ) {
			return $model->getUrl();
		}

		// Try slug property
		if ( isset( $model->slug ) && null !== $model->slug ) {
			return url( $model->slug );
		}

		// Fall back to route guess based on model name
		$routeName = Str::snake( class_basename( $model ) );

		try {
			return route( "{$routeName}.show", $model );
		} catch ( Exception $e ) {
			// Return null if route doesn't exist to prevent multiple models
			// from collapsing to the same URL
			return null;
		}
	}

	/**
	 * Get the sitemap type for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get type for.
	 *
	 * @return string
	 */
	protected function getModelType( Model $model ): string
	{
		// Check if model defines custom sitemap type
		if ( method_exists( $model, 'getSitemapType' ) ) {
			return $model->getSitemapType();
		}

		// Fall back to lowercased class basename
		return Str::snake( class_basename( $model ) );
	}

	/**
	 * Get the priority for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get priority for.
	 *
	 * @return float
	 */
	protected function getModelPriority( Model $model ): float
	{
		// Check if model has getSitemapPriority method (from HasSeo trait)
		if ( method_exists( $model, 'getSitemapPriority' ) ) {
			return $model->getSitemapPriority();
		}

		return (float) config( 'seo.sitemap.default_priority', 0.5 );
	}

	/**
	 * Get the change frequency for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get changefreq for.
	 *
	 * @return string
	 */
	protected function getModelChangefreq( Model $model ): string
	{
		// Check if model has getSitemapChangefreq method (from HasSeo trait)
		if ( method_exists( $model, 'getSitemapChangefreq' ) ) {
			return $model->getSitemapChangefreq();
		}

		return config( 'seo.sitemap.default_frequency', 'weekly' );
	}

	/**
	 * Get the indexable status for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get indexable status for.
	 *
	 * @return bool
	 */
	protected function getModelIndexable( Model $model ): bool
	{
		// Check if model has shouldBeIndexed method (from HasSeo trait)
		if ( method_exists( $model, 'shouldBeIndexed' ) ) {
			return $model->shouldBeIndexed();
		}

		return true;
	}

	/**
	 * Get the last modified date for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get last modified date for.
	 *
	 * @return DateTimeInterface|null
	 */
	protected function getModelLastModified( Model $model ): ?DateTimeInterface
	{
		// Check for updated_at timestamp
		if ( isset( $model->updated_at ) && null !== $model->updated_at ) {
			return $model->updated_at;
		}

		// Check for created_at timestamp
		if ( isset( $model->created_at ) && null !== $model->created_at ) {
			return $model->created_at;
		}

		return now();
	}

	/**
	 * Get images for a model's sitemap entry.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get images for.
	 *
	 * @return array<int, array<string, string>>|null
	 */
	protected function getModelImages( Model $model ): ?array
	{
		// Check if model has getSitemapImages method
		if ( method_exists( $model, 'getSitemapImages' ) ) {
			return $model->getSitemapImages();
		}

		// Try to get featured image
		$images = [];

		if ( method_exists( $model, 'getSeoImage' ) ) {
			$seoImage = $model->getSeoImage();
			if ( null !== $seoImage && '' !== $seoImage ) {
				$images[] = [ 'loc' => $seoImage ];
			}
		} elseif ( isset( $model->featured_image ) && null !== $model->featured_image ) {
			$images[] = [ 'loc' => $model->featured_image ];
		}

		return ! empty( $images ) ? $images : null;
	}

	/**
	 * Get videos for a model's sitemap entry.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get videos for.
	 *
	 * @return array<int, array<string, mixed>>|null
	 */
	protected function getModelVideos( Model $model ): ?array
	{
		// Check if model has getSitemapVideos method
		if ( method_exists( $model, 'getSitemapVideos' ) ) {
			return $model->getSitemapVideos();
		}

		return null;
	}
}
