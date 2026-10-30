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
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\DTOs\MetaTagsDTO;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Support\PublicUrlResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use function applyFilters;

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
	 * Fires the `ap.seo.metaTags` filter with the assembled tag array
	 * so integrators can add, remove, or rewrite tags before rendering.
	 * Filter payload: `(array $tags, ?Model $subject, Request $request)`.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model         $model    The model to generate tags for.
	 * @param  SeoMeta|null  $seoMeta  Optional SeoMeta instance.
	 * @param  Request|null  $request  Optional request; resolved from the container when null.
	 *
	 * @return MetaTagsDTO
	 */
	public function generate( Model $model, ?SeoMeta $seoMeta = null, ?Request $request = null ): MetaTagsDTO
	{
		$tags = [
			'title'           => $this->resolveTitle( $model, $seoMeta ),
			'description'     => $this->resolveDescription( $model, $seoMeta ),
			'canonical'       => $this->resolveCanonical( $model, $seoMeta ),
			'robots'          => $this->resolveRobots( $seoMeta ),
			'additionalMeta'  => $this->getAdditionalMeta( $model ),
		];

		$request = $request ?? app( 'request' );

		$filtered = applyFilters( 'ap.seo.metaTags', $tags, $model, $request );

		if ( ! is_array( $filtered ) ) {
			$filtered = $tags;
		}

		return new MetaTagsDTO(
			title: (string) ( $filtered['title'] ?? '' ),
			description: $filtered['description'] ?? null,
			canonical: (string) ( $filtered['canonical'] ?? '' ),
			robots: (string) ( $filtered['robots'] ?? 'index, follow' ),
			additionalMeta: (array) ( $filtered['additionalMeta'] ?? [] ),
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
	 * A saved description is left at its full length. Text taken from the
	 * model's excerpt or description is shortened to fit
	 * `seo.defaults.description_max_length`.
	 *
	 * @since 1.0.0
	 * @since 1.9.0 Saved descriptions are no longer shortened.
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

		if ( null !== $description && '' !== $description ) {
			return strip_tags( $description );
		}

		$description = $model->excerpt ?? null;

		if ( null === $description || '' === $description ) {
			$description = $model->description ?? null;
		}

		if ( null !== $description && '' !== $description ) {
			return seoDescription( strip_tags( $description ), (int) config( 'seo.defaults.description_max_length', 160 ) );
		}

		return config( 'seo.site.description', null );
	}

	/**
	 * Resolve the canonical URL.
	 *
	 * @since 1.0.0
	 * @since 1.9.0 No longer guesses the URL from the model's slug.
	 *
	 * @param  Model          $model    The model.
	 * @param  SeoMeta|null   $seoMeta  The SeoMeta instance.
	 *
	 * @return string
	 */
	protected function resolveCanonical( Model $model, ?SeoMeta $seoMeta ): string
	{
		return PublicUrlResolver::resolve( $model, $seoMeta );
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
	 * The suffix is left off when it would push the title past
	 * `seo.defaults.title_max_length`. A limit of 0 turns the check off.
	 *
	 * @since 1.0.0
	 * @since 1.9.0 Skips the suffix when it would exceed the title limit.
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

		$fullTitle = $title . $separator . $suffix;
		$maxLength = (int) config( 'seo.defaults.title_max_length', 60 );

		if ( $maxLength > 0 && mb_strlen( $fullTitle ) > $maxLength ) {
			return $title;
		}

		return $fullTitle;
	}

	/**
	 * Get additional meta tags.
	 *
	 * @since 1.0.0
	 * @since 1.9.0 Article dates moved to Open Graph and `keywords` was removed.
	 *
	 * @param  Model  $model  The model.
	 *
	 * @return array<string, string>
	 */
	protected function getAdditionalMeta( Model $model ): array
	{
		$meta = [];

		// Author meta
		$author = $model->author?->name ?? null;
		if ( null !== $author ) {
			$meta['author'] = $author;
		}

		return $meta;
	}
}
