<?php
/**
 * MetaTagService.
 *
 * Service for generating HTML meta tags.
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
use ArtisanPackUI\SEO\Models\SeoMeta;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * MetaTagService class.
 *
 * Generates HTML meta tags including title, description,
 * canonical URL, and robots directives.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class MetaTagService
{
	/**
	 * Generate meta tags for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model to generate tags for.
	 * @param  SeoMeta|null   $seoMeta  Optional SeoMeta instance.
	 *
	 * @return MetaTagsDTO
	 */
	public function generate( Model $model, ?SeoMeta $seoMeta = null ): MetaTagsDTO
	{
		$title       = $this->resolveTitle( $model, $seoMeta );
		$description = $this->resolveDescription( $model, $seoMeta );
		$canonical   = $this->resolveCanonical( $model, $seoMeta );
		$robots      = $this->resolveRobots( $seoMeta );

		return new MetaTagsDTO(
			title: $title,
			description: $description,
			canonical: $canonical,
			robots: $robots,
			additionalMeta: $this->getAdditionalMeta( $model, $seoMeta ),
		);
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
		if ( ! $includeSuffix ) {
			return $title;
		}

		return $this->buildFullTitle( $title );
	}

	/**
	 * Build the robots directive string.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta|null  $seoMeta  The SeoMeta instance.
	 *
	 * @return string
	 */
	public function buildRobotsDirective( ?SeoMeta $seoMeta ): string
	{
		return $this->resolveRobots( $seoMeta );
	}

	/**
	 * Get the title suffix from configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getTitleSuffix(): string
	{
		return config( 'seo.site.name' ) ?? config( 'app.name' ) ?? '';
	}

	/**
	 * Get the title separator from configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getTitleSeparator(): string
	{
		return config( 'seo.site.separator' ) ?? ' | ';
	}

	/**
	 * Resolve the page title.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model.
	 * @param  SeoMeta|null   $seoMeta  The SeoMeta instance.
	 *
	 * @return string
	 */
	protected function resolveTitle( Model $model, ?SeoMeta $seoMeta ): string
	{
		// Priority: SeoMeta -> Model meta_title -> Model title -> Model name -> App name
		$baseTitle = $seoMeta?->meta_title;

		if ( null === $baseTitle || '' === $baseTitle ) {
			$baseTitle = $model->meta_title ?? null;
		}

		if ( null === $baseTitle || '' === $baseTitle ) {
			$baseTitle = $model->title ?? null;
		}

		if ( null === $baseTitle || '' === $baseTitle ) {
			$baseTitle = $model->name ?? null;
		}

		if ( null === $baseTitle || '' === $baseTitle ) {
			$baseTitle = config( 'app.name', '' );
		}

		return $this->buildFullTitle( $baseTitle );
	}

	/**
	 * Resolve the meta description.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model.
	 * @param  SeoMeta|null   $seoMeta  The SeoMeta instance.
	 *
	 * @return string|null
	 */
	protected function resolveDescription( Model $model, ?SeoMeta $seoMeta ): ?string
	{
		$description = $seoMeta?->meta_description;

		if ( null === $description || '' === $description ) {
			$description = $model->meta_description ?? null;
		}

		if ( null === $description || '' === $description ) {
			$description = $model->excerpt ?? null;
		}

		if ( null === $description || '' === $description ) {
			$description = $model->description ?? null;
		}

		if ( null !== $description && '' !== $description ) {
			$maxLength = (int) config( 'seo.defaults.description_max_length', 160 );

			return Str::limit( strip_tags( $description ), $maxLength );
		}

		return config( 'seo.site.description', null );
	}

	/**
	 * Resolve the canonical URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model.
	 * @param  SeoMeta|null   $seoMeta  The SeoMeta instance.
	 *
	 * @return string
	 */
	protected function resolveCanonical( Model $model, ?SeoMeta $seoMeta ): string
	{
		if ( null !== $seoMeta?->canonical_url && '' !== $seoMeta->canonical_url ) {
			return $seoMeta->canonical_url;
		}

		// Try to get URL from model
		if ( method_exists( $model, 'getUrl' ) ) {
			return $model->getUrl();
		}

		if ( isset( $model->slug ) && null !== $model->slug ) {
			return url( $model->slug );
		}

		// Avoid calling url()->current() outside HTTP context
		if ( app()->runningInConsole() || null === request() ) {
			return '';
		}

		return url()->current();
	}

	/**
	 * Resolve the robots directive.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta|null  $seoMeta  The SeoMeta instance.
	 *
	 * @return string
	 */
	protected function resolveRobots( ?SeoMeta $seoMeta ): string
	{
		if ( null === $seoMeta ) {
			return config( 'seo.defaults.robots', 'index, follow' );
		}

		return $seoMeta->getRobotsContent();
	}

	/**
	 * Build the full title with suffix.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $title  The base title.
	 *
	 * @return string
	 */
	protected function buildFullTitle( string $title ): string
	{
		$suffix    = $this->getTitleSuffix();
		$separator = $this->getTitleSeparator();

		// Don't add suffix if title already contains site name
		if ( '' !== $suffix && str_contains( $title, $suffix ) ) {
			return $title;
		}

		// Don't add suffix if it's empty
		if ( '' === $suffix ) {
			return $title;
		}

		return $title . $separator . $suffix;
	}

	/**
	 * Get additional meta tags.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model.
	 * @param  SeoMeta|null   $seoMeta  The SeoMeta instance.
	 *
	 * @return array<string, string>
	 */
	protected function getAdditionalMeta( Model $model, ?SeoMeta $seoMeta ): array
	{
		$meta = [];

		// Author meta
		$author = $model->author?->name ?? null;
		if ( null !== $author ) {
			$meta['author'] = $author;
		}

		// Published/Modified dates for articles
		if ( isset( $model->published_at ) && null !== $model->published_at ) {
			$publishedAt = $model->published_at;
			if ( $publishedAt instanceof DateTimeInterface ) {
				$meta['article:published_time'] = Carbon::instance( $publishedAt )->toIso8601String();
			}
		}

		if ( isset( $model->updated_at ) && null !== $model->updated_at ) {
			$updatedAt = $model->updated_at;
			if ( $updatedAt instanceof DateTimeInterface ) {
				$meta['article:modified_time'] = Carbon::instance( $updatedAt )->toIso8601String();
			}
		}

		// Focus keyword
		if ( null !== $seoMeta?->focus_keyword && '' !== $seoMeta->focus_keyword ) {
			$meta['keywords'] = $seoMeta->focus_keyword;

			// Add secondary keywords if available
			if ( is_array( $seoMeta->secondary_keywords ) && ! empty( $seoMeta->secondary_keywords ) ) {
				$meta['keywords'] .= ', ' . implode( ', ', $seoMeta->secondary_keywords );
			}
		}

		return $meta;
	}
}
