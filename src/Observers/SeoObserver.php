<?php

/**
 * SeoObserver.
 *
 * Observer for handling SEO-related events on models using the HasSeo trait.
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

use ArtisanPackUI\SEO\Services\CacheService;
use Illuminate\Database\Eloquent\Model;

/**
 * SeoObserver class.
 *
 * Observes model events for models using the HasSeo trait and handles
 * cache invalidation, sitemap updates, and cleanup operations.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SeoObserver
{
	/**
	 * Create a new SeoObserver instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  CacheService  $cacheService  The cache service.
	 */
	public function __construct(
		protected CacheService $cacheService,
	) {
	}

	/**
	 * Handle the model "saved" event.
	 *
	 * Clears SEO-related caches when a model is saved.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model that was saved.
	 *
	 * @return void
	 */
	public function saved( Model $model ): void
	{
		$this->cacheService->clearMetaCache( $model );
	}

	/**
	 * Handle the model "deleted" event.
	 *
	 * Clears all SEO-related caches and deletes associated SEO meta
	 * when a model is deleted. For soft-deleted models, only caches
	 * are cleared; SEO meta is preserved until force delete.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model that was deleted.
	 *
	 * @return void
	 */
	public function deleted( Model $model ): void
	{
		// Clear all caches for this model
		$this->cacheService->clearAllForModel( $model );

		// Only delete SEO meta on force delete, not soft delete
		if ( $this->isForceDeleting( $model ) ) {
			$this->deleteSeoMeta( $model );
		}
	}

	/**
	 * Check if the model is being force deleted.
	 *
	 * Returns true if the model doesn't use SoftDeletes trait,
	 * or if it's being force deleted.
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
	 * Delete the SEO meta for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to delete SEO meta for.
	 *
	 * @return void
	 */
	protected function deleteSeoMeta( Model $model ): void
	{
		if ( method_exists( $model, 'seoMeta' ) && null !== $model->seoMeta ) {
			$model->seoMeta->delete();
		}
	}
}
