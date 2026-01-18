<?php
/**
 * SeoService.
 *
 * Main orchestrator service for all SEO functionality.
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

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\DTOs\MetaTagsDTO;
use ArtisanPackUI\SEO\DTOs\OpenGraphDTO;
use ArtisanPackUI\SEO\DTOs\TwitterCardDTO;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * SeoService class.
 *
 * Main orchestrator service that coordinates all SEO services
 * including meta tags, social media tags, and caching.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SeoService
{
	/**
	 * Create a new SeoService instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  MetaTagService     $metaTagService     The meta tag service.
	 * @param  SocialMetaService  $socialMetaService  The social meta service.
	 * @param  CacheService       $cacheService       The cache service.
	 */
	public function __construct(
		protected MetaTagService $metaTagService,
		protected SocialMetaService $socialMetaService,
		protected CacheService $cacheService,
	) {
	}

	/**
	 * Get all SEO data for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get SEO data for.
	 *
	 * @return array<string, mixed>
	 */
	public function getAll( Model $model ): array
	{
		$cacheKey = $this->cacheService->getMetaCacheKey( $model );

		return $this->cacheService->remember( $cacheKey, function () use ( $model ): array {
			return [
				'meta'        => $this->getMetaTags( $model )->toArray(),
				'openGraph'   => $this->getOpenGraph( $model )->toArray(),
				'twitterCard' => $this->getTwitterCard( $model )->toArray(),
				'hreflang'    => $this->getHreflang( $model ),
			];
		} );
	}

	/**
	 * Get meta tags for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get meta tags for.
	 *
	 * @return MetaTagsDTO
	 */
	public function getMetaTags( Model $model ): MetaTagsDTO
	{
		$seoMeta = $this->getSeoMeta( $model );

		return $this->metaTagService->generate( $model, $seoMeta );
	}

	/**
	 * Get Open Graph data for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get OG data for.
	 *
	 * @return OpenGraphDTO
	 */
	public function getOpenGraph( Model $model ): OpenGraphDTO
	{
		$seoMeta = $this->getSeoMeta( $model );

		return $this->socialMetaService->generateOpenGraph( $model, $seoMeta );
	}

	/**
	 * Get Twitter Card data for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get Twitter data for.
	 *
	 * @return TwitterCardDTO
	 */
	public function getTwitterCard( Model $model ): TwitterCardDTO
	{
		$seoMeta = $this->getSeoMeta( $model );

		return $this->socialMetaService->generateTwitterCard( $model, $seoMeta );
	}

	/**
	 * Get hreflang tags for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get hreflang for.
	 *
	 * @return array<int, array{hreflang: string, href: string}>
	 */
	public function getHreflang( Model $model ): array
	{
		$seoMeta = $this->getSeoMeta( $model );

		if ( null === $seoMeta?->hreflang || empty( $seoMeta->hreflang ) ) {
			return [];
		}

		$tags = [];

		foreach ( $seoMeta->hreflang as $lang => $url ) {
			$tags[] = [
				'hreflang' => $lang,
				'href'     => $url,
			];
		}

		// Add x-default if configured
		$defaultLang = config( 'seo.hreflang.default_locale' );
		if ( null !== $defaultLang && isset( $seoMeta->hreflang[ $defaultLang ] ) ) {
			$tags[] = [
				'hreflang' => 'x-default',
				'href'     => $seoMeta->hreflang[ $defaultLang ],
			];
		}

		return $tags;
	}

	/**
	 * Get or retrieve the SeoMeta for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model.
	 *
	 * @return SeoMeta|null
	 */
	public function getSeoMeta( Model $model ): ?SeoMeta
	{
		if ( method_exists( $model, 'seoMeta' ) ) {
			return $model->seoMeta;
		}

		return null;
	}

	/**
	 * Update SEO meta for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model                 $model  The model to update.
	 * @param  array<string, mixed>  $data   The SEO data to update.
	 *
	 * @throws InvalidArgumentException If model doesn't use HasSeo trait or is not persisted.
	 *
	 * @return SeoMeta
	 */
	public function updateSeoMeta( Model $model, array $data ): SeoMeta
	{
		if ( ! method_exists( $model, 'seoMeta' ) ) {
			throw new InvalidArgumentException( 'Model must use the HasSeo trait.' );
		}

		// Guard against unsaved models to prevent orphaned SeoMeta records
		if ( ! $model->exists || null === $model->getKey() ) {
			throw new InvalidArgumentException( 'Model must be persisted before creating SEO meta.' );
		}

		$seoMeta = $model->seoMeta;

		if ( null === $seoMeta ) {
			$seoMeta               = new SeoMeta();
			$seoMeta->seoable_type = get_class( $model );
			$seoMeta->seoable_id   = $model->getKey();
		}

		$seoMeta->fill( $data );
		$seoMeta->save();

		// Clear cache
		$this->cacheService->clearMetaCache( $model );

		return $seoMeta;
	}

	/**
	 * Get the default title suffix.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getTitleSuffix(): string
	{
		return $this->metaTagService->getTitleSuffix();
	}

	/**
	 * Get the title separator.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getTitleSeparator(): string
	{
		return $this->metaTagService->getTitleSeparator();
	}

	/**
	 * Build a full page title with suffix.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $title          The base title.
	 * @param  bool    $includeSuffix  Whether to include the site suffix.
	 *
	 * @return string
	 */
	public function buildTitle( string $title, bool $includeSuffix = true ): string
	{
		return $this->metaTagService->buildTitle( $title, $includeSuffix );
	}

	/**
	 * Clear SEO cache for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to clear cache for.
	 *
	 * @return void
	 */
	public function clearCache( Model $model ): void
	{
		$this->cacheService->clearAllForModel( $model );
	}

	/**
	 * Clear all SEO caches.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clearAllCaches(): void
	{
		$this->cacheService->clearAll();
	}
}
