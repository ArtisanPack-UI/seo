<?php

/**
 * CacheService.
 *
 * Service for managing SEO-specific caching.
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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * CacheService class.
 *
 * Manages SEO-specific caching operations including storage,
 * retrieval, and invalidation of cached SEO data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class CacheService
{
	/**
	 * Get a value from the cache.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $key  The cache key.
	 *
	 * @return mixed
	 */
	public function get( string $key ): mixed
	{
		if ( ! $this->isEnabled() ) {
			return null;
		}

		return $this->getCacheStore()->get( $this->prefixKey( $key ) );
	}

	/**
	 * Store a value in the cache.
	 *
	 * @since 1.0.0
	 *
	 * @param  string    $key    The cache key.
	 * @param  mixed     $value  The value to cache.
	 * @param  int|null  $ttl    Optional TTL in seconds.
	 *
	 * @return void
	 */
	public function put( string $key, mixed $value, ?int $ttl = null ): void
	{
		if ( ! $this->isEnabled() ) {
			return;
		}

		$this->getCacheStore()->put(
			$this->prefixKey( $key ),
			$value,
			$ttl ?? $this->getTtl(),
		);
	}

	/**
	 * Remember a value in the cache.
	 *
	 * @since 1.0.0
	 *
	 * @param  string    $key       The cache key.
	 * @param  callable  $callback  The callback to generate the value.
	 * @param  int|null  $ttl       Optional TTL in seconds.
	 *
	 * @return mixed
	 */
	public function remember( string $key, callable $callback, ?int $ttl = null ): mixed
	{
		if ( ! $this->isEnabled() ) {
			return $callback();
		}

		return $this->getCacheStore()->remember(
			$this->prefixKey( $key ),
			$ttl ?? $this->getTtl(),
			$callback,
		);
	}

	/**
	 * Remove a value from the cache.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $key  The cache key.
	 *
	 * @return void
	 */
	public function forget( string $key ): void
	{
		if ( ! $this->isEnabled() ) {
			return;
		}

		$this->getCacheStore()->forget( $this->prefixKey( $key ) );
	}

	/**
	 * Get cache key for a model's meta tags.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model instance.
	 *
	 * @return string
	 */
	public function getMetaCacheKey( Model $model ): string
	{
		return sprintf(
			'meta:%s:%s',
			class_basename( $model ),
			$model->getKey(),
		);
	}

	/**
	 * Get cache key for a model's analysis.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model instance.
	 *
	 * @return string
	 */
	public function getAnalysisCacheKey( Model $model ): string
	{
		return sprintf(
			'analysis:%s:%s',
			class_basename( $model ),
			$model->getKey(),
		);
	}

	/**
	 * Clear meta cache for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model instance.
	 *
	 * @return void
	 */
	public function clearMetaCache( Model $model ): void
	{
		$this->forget( $this->getMetaCacheKey( $model ) );
	}

	/**
	 * Clear analysis cache for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model instance.
	 *
	 * @return void
	 */
	public function clearAnalysisCache( Model $model ): void
	{
		$this->forget( $this->getAnalysisCacheKey( $model ) );
	}

	/**
	 * Clear all SEO caches for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model instance.
	 *
	 * @return void
	 */
	public function clearAllForModel( Model $model ): void
	{
		$this->clearMetaCache( $model );
		$this->clearAnalysisCache( $model );
	}

	/**
	 * Clear all SEO caches.
	 *
	 * Note: For cache stores that don't support tagging (e.g., file, database),
	 * model-specific caches (meta:*, analysis:*) cannot be cleared without
	 * knowing the specific model class and ID. Use clearAllForModel() to clear
	 * caches for specific models, or use a tagging-capable cache store (Redis,
	 * Memcached) for full clearAll() support.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clearAll(): void
	{
		// Clear all tagged caches if tags are supported
		if ( $this->supportsTagging() ) {
			Cache::tags( [ 'seo' ] )->flush();

			return;
		}

		// Fall back to clearing known static keys for non-tagging cache stores.
		// Note: Model-specific caches (meta:ModelClass:id, analysis:ModelClass:id)
		// cannot be cleared without knowing specific model identifiers.
		// Use clearAllForModel() for those, or switch to a tagging-capable cache.
		$knownKeys = [
			'redirects:all',
			'redirects:patterns',
			'sitemap:index',
			'sitemap:pages',
			'sitemap:posts',
			'sitemap:products',
			'sitemap:images',
			'sitemap:video',
			'sitemap:news',
		];

		foreach ( $knownKeys as $key ) {
			Cache::forget( $this->prefixKey( $key ) );
		}
	}

	/**
	 * Get the cache TTL from config.
	 *
	 * @since 1.0.0
	 *
	 * @return int TTL in seconds.
	 */
	public function getTtl(): int
	{
		return (int) config( 'seo.cache.ttl', 3600 );
	}

	/**
	 * Check if caching is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return (bool) config( 'seo.cache.enabled', true );
	}

	/**
	 * Get the cache prefix.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getPrefix(): string
	{
		return config( 'seo.cache.prefix', 'seo' );
	}

	/**
	 * Prefix a cache key with the SEO prefix.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $key  The cache key.
	 *
	 * @return string
	 */
	protected function prefixKey( string $key ): string
	{
		return $this->getPrefix() . ':' . $key;
	}

	/**
	 * Check if the cache store supports tagging.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function supportsTagging(): bool
	{
		return method_exists( Cache::getStore(), 'tags' );
	}

	/**
	 * Get the cache store instance (tagged or regular).
	 *
	 * Returns a tagged cache instance when the cache store supports
	 * tagging, otherwise returns the regular Cache facade.
	 *
	 * @since 1.0.0
	 *
	 * @return \Illuminate\Contracts\Cache\Repository
	 */
	protected function getCacheStore(): \Illuminate\Contracts\Cache\Repository
	{
		if ( $this->supportsTagging() ) {
			return Cache::tags( [ 'seo' ] );
		}

		return Cache::store();
	}
}
