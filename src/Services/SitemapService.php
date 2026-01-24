<?php

/**
 * SitemapService.
 *
 * Orchestrates sitemap generation and caching.
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

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Sitemap\Generators\ImageSitemapGenerator;
use ArtisanPackUI\SEO\Sitemap\Generators\NewsSitemapGenerator;
use ArtisanPackUI\SEO\Sitemap\Generators\SitemapGenerator;
use ArtisanPackUI\SEO\Sitemap\Generators\SitemapIndexGenerator;
use ArtisanPackUI\SEO\Sitemap\Generators\VideoSitemapGenerator;
use ArtisanPackUI\SEO\Sitemap\SitemapSubmitter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * SitemapService class.
 *
 * Main service for orchestrating sitemap generation, caching, and submission.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SitemapService
{
	/**
	 * Registered sitemap providers.
	 *
	 * @since 1.0.0
	 *
	 * @var Collection<string, SitemapProviderContract>
	 */
	protected Collection $providers;

	/**
	 * Cache TTL in seconds.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected int $cacheTtl;

	/**
	 * Whether caching is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	protected bool $cacheEnabled;

	/**
	 * Cache key prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $cachePrefix;

	/**
	 * Maximum URLs per sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected int $maxUrls;

	/**
	 * Create a new SitemapService instance.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->providers    = collect();
		$this->cacheTtl     = (int) config( 'seo.sitemap.cache_ttl', 3600 );
		$this->cacheEnabled = (bool) config( 'seo.sitemap.cache_enabled', true );
		$this->cachePrefix  = config( 'seo.cache.prefix', 'seo' ) . ':sitemap:';
		$this->maxUrls      = (int) config( 'seo.sitemap.max_urls_per_file', 10000 );

		$this->registerConfiguredProviders();
	}

	/**
	 * Generate a sitemap for the given type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null  $type  The sitemap type to generate.
	 * @param  int          $page  Page number for pagination.
	 *
	 * @return string The generated XML sitemap.
	 */
	public function generate( ?string $type = null, int $page = 1 ): string
	{
		$cacheKey = $this->getCacheKey( 'standard', $type, $page );

		if ( $this->cacheEnabled ) {
			return Cache::remember( $cacheKey, $this->cacheTtl, function () use ( $type, $page ) {
				return $this->generateFresh( $type, $page );
			} );
		}

		return $this->generateFresh( $type, $page );
	}

	/**
	 * Generate the sitemap index.
	 *
	 * @since 1.0.0
	 *
	 * @return string The generated XML sitemap index.
	 */
	public function generateIndex(): string
	{
		$cacheKey = $this->getCacheKey( 'index' );

		if ( $this->cacheEnabled ) {
			return Cache::remember( $cacheKey, $this->cacheTtl, function () {
				return $this->generateIndexFresh();
			} );
		}

		return $this->generateIndexFresh();
	}

	/**
	 * Generate the image sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $page  Page number for pagination.
	 *
	 * @return string The generated XML image sitemap.
	 */
	public function generateImages( int $page = 1 ): string
	{
		$cacheKey = $this->getCacheKey( 'images', null, $page );

		if ( $this->cacheEnabled ) {
			return Cache::remember( $cacheKey, $this->cacheTtl, function () use ( $page ) {
				$generator = new ImageSitemapGenerator( $this->maxUrls );

				return $generator->generate( $page );
			} );
		}

		$generator = new ImageSitemapGenerator( $this->maxUrls );

		return $generator->generate( $page );
	}

	/**
	 * Generate the video sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $page  Page number for pagination.
	 *
	 * @return string The generated XML video sitemap.
	 */
	public function generateVideos( int $page = 1 ): string
	{
		$cacheKey = $this->getCacheKey( 'videos', null, $page );

		if ( $this->cacheEnabled ) {
			return Cache::remember( $cacheKey, $this->cacheTtl, function () use ( $page ) {
				$generator = new VideoSitemapGenerator( $this->maxUrls );

				return $generator->generate( $page );
			} );
		}

		$generator = new VideoSitemapGenerator( $this->maxUrls );

		return $generator->generate( $page );
	}

	/**
	 * Generate the news sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $page  Page number for pagination.
	 *
	 * @return string The generated XML news sitemap.
	 */
	public function generateNews( int $page = 1 ): string
	{
		// News sitemaps have a shorter cache time since they only show recent content
		$cacheKey = $this->getCacheKey( 'news', null, $page );
		$newsTtl  = min( $this->cacheTtl, 900 ); // Max 15 minutes for news

		if ( $this->cacheEnabled ) {
			return Cache::remember( $cacheKey, $newsTtl, function () use ( $page ) {
				$generator = new NewsSitemapGenerator();

				return $generator->generate( $page );
			} );
		}

		$generator = new NewsSitemapGenerator();

		return $generator->generate( $page );
	}

	/**
	 * Get all available sitemap types.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public function getTypes(): array
	{
		$types = SitemapEntry::getAvailableTypes();

		// Add provider types
		foreach ( $this->providers->keys() as $type ) {
			if ( ! in_array( $type, $types, true ) ) {
				$types[] = $type;
			}
		}

		return $types;
	}

	/**
	 * Submit sitemaps to search engines.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null  $sitemapUrl  Optional custom sitemap URL.
	 *
	 * @return Collection<string, array<string, mixed>> Submission results.
	 */
	public function submit( ?string $sitemapUrl = null ): Collection
	{
		$submitter = new SitemapSubmitter( $sitemapUrl );

		return $submitter->submit();
	}

	/**
	 * Register a sitemap provider.
	 *
	 * @since 1.0.0
	 *
	 * @param  string                    $type      The provider type identifier.
	 * @param  SitemapProviderContract   $provider  The provider instance.
	 *
	 * @return self
	 */
	public function registerProvider( string $type, SitemapProviderContract $provider ): self
	{
		$this->providers->put( $type, $provider );

		return $this;
	}

	/**
	 * Get registered providers.
	 *
	 * @since 1.0.0
	 *
	 * @return Collection<string, SitemapProviderContract>
	 */
	public function getProviders(): Collection
	{
		return $this->providers;
	}

	/**
	 * Check if a sitemap index is needed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function needsIndex(): bool
	{
		$generator = new SitemapIndexGenerator( config( 'app.url' ), $this->maxUrls );

		return $generator->needsIndex();
	}

	/**
	 * Get the total number of pages for a sitemap type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null  $type  The sitemap type.
	 *
	 * @return int
	 */
	public function getTotalPages( ?string $type = null ): int
	{
		$generator = new SitemapGenerator( $this->maxUrls );

		return $generator->getTotalPages( $type );
	}

	/**
	 * Clear all sitemap caches.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clearCache(): void
	{
		// Clear standard sitemap caches for all types
		$types = $this->getTypes();
		foreach ( $types as $type ) {
			$generator  = new SitemapGenerator( $this->maxUrls );
			$totalPages = $generator->getTotalPages( $type );

			for ( $page = 1; $page <= max( 1, $totalPages ); $page++ ) {
				Cache::forget( $this->getCacheKey( 'standard', $type, $page ) );
			}
		}

		// Clear standard sitemap cache without type filter (null type)
		$generator  = new SitemapGenerator( $this->maxUrls );
		$totalPages = $generator->getTotalPages( null );
		for ( $page = 1; $page <= max( 1, $totalPages ); $page++ ) {
			Cache::forget( $this->getCacheKey( 'standard', null, $page ) );
		}

		// Clear index cache
		Cache::forget( $this->getCacheKey( 'index' ) );

		// Clear image sitemap cache
		$imageGenerator = new ImageSitemapGenerator( $this->maxUrls );
		for ( $page = 1; $page <= max( 1, $imageGenerator->getTotalPages() ); $page++ ) {
			Cache::forget( $this->getCacheKey( 'images', null, $page ) );
		}

		// Clear video sitemap cache
		$videoGenerator = new VideoSitemapGenerator( $this->maxUrls );
		for ( $page = 1; $page <= max( 1, $videoGenerator->getTotalPages() ); $page++ ) {
			Cache::forget( $this->getCacheKey( 'videos', null, $page ) );
		}

		// Clear news sitemap cache
		$newsGenerator = new NewsSitemapGenerator();
		for ( $page = 1; $page <= max( 1, $newsGenerator->getTotalPages() ); $page++ ) {
			Cache::forget( $this->getCacheKey( 'news', null, $page ) );
		}
	}

	/**
	 * Set the cache TTL.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $seconds  Cache TTL in seconds.
	 *
	 * @return self
	 */
	public function setCacheTtl( int $seconds ): self
	{
		$this->cacheTtl = $seconds;

		return $this;
	}

	/**
	 * Enable or disable caching.
	 *
	 * @since 1.0.0
	 *
	 * @param  bool  $enabled  Whether caching is enabled.
	 *
	 * @return self
	 */
	public function setCacheEnabled( bool $enabled ): self
	{
		$this->cacheEnabled = $enabled;

		return $this;
	}

	/**
	 * Check if caching is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isCacheEnabled(): bool
	{
		return $this->cacheEnabled;
	}

	/**
	 * Set the maximum URLs per sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $maxUrls  Maximum URLs per sitemap.
	 *
	 * @return self
	 */
	public function setMaxUrls( int $maxUrls ): self
	{
		$this->maxUrls = $maxUrls;

		return $this;
	}

	/**
	 * Get the maximum URLs per sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getMaxUrls(): int
	{
		return $this->maxUrls;
	}

	/**
	 * Check if image sitemaps are enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isImageSitemapEnabled(): bool
	{
		return (bool) config( 'seo.sitemap.types.image', false );
	}

	/**
	 * Check if video sitemaps are enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isVideoSitemapEnabled(): bool
	{
		return (bool) config( 'seo.sitemap.types.video', false );
	}

	/**
	 * Check if news sitemaps are enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isNewsSitemapEnabled(): bool
	{
		return (bool) config( 'seo.sitemap.types.news', false );
	}

	/**
	 * Check if any specialized sitemap is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function hasSpecializedSitemaps(): bool
	{
		return $this->isImageSitemapEnabled()
			|| $this->isVideoSitemapEnabled()
			|| $this->isNewsSitemapEnabled();
	}

	/**
	 * Generate a fresh sitemap without caching.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null  $type  The sitemap type to generate.
	 * @param  int          $page  Page number for pagination.
	 *
	 * @return string The generated XML sitemap.
	 */
	protected function generateFresh( ?string $type = null, int $page = 1 ): string
	{
		// Check for registered provider
		if ( null !== $type && $this->providers->has( $type ) ) {
			$generator = new SitemapGenerator( $this->maxUrls );

			return $generator->generateFromProvider( $this->providers->get( $type ) );
		}

		$generator = new SitemapGenerator( $this->maxUrls );

		return $generator->generate( $type, $page );
	}

	/**
	 * Generate a fresh sitemap index without caching.
	 *
	 * @since 1.0.0
	 *
	 * @return string The generated XML sitemap index.
	 */
	protected function generateIndexFresh(): string
	{
		$generator = new SitemapIndexGenerator( config( 'app.url' ), $this->maxUrls );
		$generator->setProviders( $this->providers );

		return $generator->generate();
	}

	/**
	 * Register providers from config.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function registerConfiguredProviders(): void
	{
		$providers = config( 'seo.sitemap.providers', [] );

		foreach ( $providers as $type => $class ) {
			if ( class_exists( $class ) && is_a( $class, SitemapProviderContract::class, true ) ) {
				$this->registerProvider( $type, app( $class ) );
			}
		}
	}

	/**
	 * Get a cache key for a sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @param  string       $variant  The sitemap variant (standard, index, images, etc.).
	 * @param  string|null  $type     The sitemap type.
	 * @param  int|null     $page     The page number.
	 *
	 * @return string
	 */
	protected function getCacheKey( string $variant, ?string $type = null, ?int $page = null ): string
	{
		$key = $this->cachePrefix . $variant;

		if ( null !== $type ) {
			$key .= ':' . $type;
		}

		if ( null !== $page && $page > 1 ) {
			$key .= ':page:' . $page;
		}

		return $key;
	}
}
