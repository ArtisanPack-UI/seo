<?php

/**
 * HasSeo Trait.
 *
 * Provides SEO functionality for any Eloquent model.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Traits;

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Observers\SeoObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * HasSeo trait for Eloquent models.
 *
 * Add this trait to any model to enable SEO functionality including
 * meta tags, Open Graph, Twitter Cards, and sitemap integration.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
trait HasSeo
{
	/**
	 * Boot the trait and register the observer.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function bootHasSeo(): void
	{
		static::observe( SeoObserver::class );
	}

	/**
	 * Get the SEO meta relationship.
	 *
	 * @since 1.0.0
	 *
	 * @return MorphOne<SeoMeta, $this>
	 */
	public function seoMeta(): MorphOne
	{
		return $this->morphOne( SeoMeta::class, 'seoable' );
	}

	/**
	 * Get or create SEO meta for this model.
	 *
	 * @since 1.0.0
	 *
	 * @throws InvalidArgumentException If model is not persisted.
	 *
	 * @return SeoMeta
	 */
	public function getOrCreateSeoMeta(): SeoMeta
	{
		// Guard against unsaved models to prevent orphaned SeoMeta records
		if ( ! $this->exists || null === $this->getKey() ) {
			throw new InvalidArgumentException( 'Model must be persisted before creating SEO meta.' );
		}

		if ( ! $this->seoMeta ) {
			$this->seoMeta()->create( [
				'seoable_type' => get_class( $this ),
				'seoable_id'   => $this->getKey(),
			] );

			$this->load( 'seoMeta' );
		}

		return $this->seoMeta;
	}

	/**
	 * Update SEO meta with given data.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>  $data  The SEO data to update.
	 *
	 * @return SeoMeta
	 */
	public function updateSeoMeta( array $data ): SeoMeta
	{
		$seoMeta = $this->getOrCreateSeoMeta();
		$seoMeta->update( $data );

		return $seoMeta->fresh();
	}

	/**
	 * Get the SEO title for this model.
	 *
	 * Falls back to model's title/name property or app name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getSeoTitle(): string
	{
		if ( null !== $this->seoMeta?->meta_title && '' !== $this->seoMeta->meta_title ) {
			return $this->seoMeta->meta_title;
		}

		if ( isset( $this->title ) && null !== $this->title && '' !== $this->title ) {
			return $this->title;
		}

		if ( isset( $this->name ) && null !== $this->name && '' !== $this->name ) {
			return $this->name;
		}

		return config( 'app.name', '' );
	}

	/**
	 * Get the meta title attribute.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getMetaTitleAttribute(): string
	{
		return $this->getSeoTitle();
	}

	/**
	 * Get the SEO description for this model.
	 *
	 * Falls back to model's excerpt/description/content property.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function getSeoDescription(): ?string
	{
		if ( null !== $this->seoMeta?->meta_description && '' !== $this->seoMeta->meta_description ) {
			return $this->seoMeta->meta_description;
		}

		// Try to generate from model content
		$content = $this->excerpt ?? $this->description ?? $this->content ?? null;

		if ( null !== $content && '' !== $content ) {
			return Str::limit( strip_tags( $content ), 160 );
		}

		return config( 'seo.defaults.meta_description', null );
	}

	/**
	 * Get the meta description attribute.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function getMetaDescriptionAttribute(): ?string
	{
		return $this->getSeoDescription();
	}

	/**
	 * Get the SEO image for this model.
	 *
	 * Falls back to model's featured_image property or config default.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function getSeoImage(): ?string
	{
		$effectiveImage = $this->seoMeta?->getEffectiveOgImage();

		if ( null !== $effectiveImage ) {
			return $effectiveImage;
		}

		if ( isset( $this->featured_image ) && null !== $this->featured_image ) {
			return $this->featured_image;
		}

		return config( 'seo.open_graph.default_image', null );
	}

	/**
	 * Get the OG image attribute.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function getOgImageAttribute(): ?string
	{
		return $this->getSeoImage();
	}

	/**
	 * Get the canonical URL for this model.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getCanonicalUrlAttribute(): string
	{
		if ( null !== $this->seoMeta?->canonical_url && '' !== $this->seoMeta->canonical_url ) {
			return $this->seoMeta->canonical_url;
		}

		if ( method_exists( $this, 'getUrl' ) ) {
			return $this->getUrl();
		}

		if ( isset( $this->slug ) && null !== $this->slug ) {
			return url( $this->slug );
		}

		// Safely get current URL outside HTTP context
		try {
			return url()->current();
		} catch ( RuntimeException $e ) {
			return '';
		}
	}

	/**
	 * Get the focus keyword for this model.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function getFocusKeywordAttribute(): ?string
	{
		return $this->seoMeta?->focus_keyword;
	}

	/**
	 * Set the focus keyword for this model.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $keyword  The focus keyword to set.
	 *
	 * @return $this
	 */
	public function setFocusKeyword( string $keyword ): self
	{
		$this->getOrCreateSeoMeta()->update( [ 'focus_keyword' => $keyword ] );

		return $this;
	}

	/**
	 * Check if this model should be indexed by search engines.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function shouldBeIndexed(): bool
	{
		return ! ( $this->seoMeta?->no_index ?? false );
	}

	/**
	 * Check if this model's links should be followed by search engines.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function shouldBeFollowed(): bool
	{
		return ! ( $this->seoMeta?->no_follow ?? false );
	}

	/**
	 * Check if this model should be included in the sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function shouldBeInSitemap(): bool
	{
		return ! ( $this->seoMeta?->exclude_from_sitemap ?? false )
			&& $this->shouldBeIndexed();
	}

	/**
	 * Get the sitemap priority for this model.
	 *
	 * @since 1.0.0
	 *
	 * @return float
	 */
	public function getSitemapPriority(): float
	{
		return (float) ( $this->seoMeta?->sitemap_priority ?? config( 'seo.sitemap.default_priority', 0.5 ) );
	}

	/**
	 * Get the sitemap change frequency for this model.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getSitemapChangefreq(): string
	{
		return $this->seoMeta?->sitemap_changefreq ?? config( 'seo.sitemap.default_frequency', 'weekly' );
	}

	/**
	 * Get the robots meta content for this model.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getRobotsMetaAttribute(): string
	{
		return $this->seoMeta?->getRobotsContent() ?? config( 'seo.defaults.robots', 'index, follow' );
	}

	/**
	 * Get all SEO data for this model as an array.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function getSeoData(): array
	{
		$seoMeta = $this->seoMeta;

		return [
			'title'             => $this->getSeoTitle(),
			'description'       => $this->getSeoDescription(),
			'canonical_url'     => $this->canonical_url,
			'robots'            => $this->robots_meta,
			'focus_keyword'     => $this->focus_keyword,
			'open_graph'        => [
				'title'       => $seoMeta?->og_title ?? $this->getSeoTitle(),
				'description' => $seoMeta?->og_description ?? $this->getSeoDescription(),
				'image'       => $this->getSeoImage(),
				'type'        => $seoMeta?->og_type ?? config( 'seo.open_graph.type', 'website' ),
				'locale'      => $seoMeta?->og_locale ?? app()->getLocale(),
				'site_name'   => $seoMeta?->og_site_name ?? config( 'seo.open_graph.site_name', config( 'app.name' ) ),
			],
			'twitter'           => [
				'card'        => $seoMeta?->twitter_card ?? config( 'seo.twitter.card_type', 'summary_large_image' ),
				'title'       => $seoMeta?->twitter_title ?? $this->getSeoTitle(),
				'description' => $seoMeta?->twitter_description ?? $this->getSeoDescription(),
				'image'       => $seoMeta?->getEffectiveTwitterImage() ?? $this->getSeoImage(),
				'site'        => $seoMeta?->twitter_site ?? config( 'seo.twitter.site' ),
				'creator'     => $seoMeta?->twitter_creator ?? config( 'seo.twitter.creator' ),
			],
			'schema'            => [
				'type'   => $seoMeta?->schema_type,
				'markup' => $seoMeta?->schema_markup,
			],
			'hreflang'          => $seoMeta?->hreflang ?? [],
			'sitemap'           => [
				'include'    => $this->shouldBeInSitemap(),
				'priority'   => $this->getSitemapPriority(),
				'changefreq' => $this->getSitemapChangefreq(),
			],
		];
	}

	/**
	 * Get hreflang tags for this model.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public function getHreflangAttribute(): array
	{
		return $this->seoMeta?->hreflang ?? [];
	}

	/**
	 * Set hreflang tags for this model.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, string>  $hreflang  The hreflang data.
	 *
	 * @return $this
	 */
	public function setHreflang( array $hreflang ): self
	{
		$this->getOrCreateSeoMeta()->update( [ 'hreflang' => $hreflang ] );

		return $this;
	}

	/**
	 * Get the schema type for this model.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function getSchemaType(): ?string
	{
		return $this->seoMeta?->schema_type;
	}

	/**
	 * Set the schema type for this model.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $type  The schema type.
	 *
	 * @return $this
	 */
	public function setSchemaType( string $type ): self
	{
		$this->getOrCreateSeoMeta()->update( [ 'schema_type' => $type ] );

		return $this;
	}

	/**
	 * Scope: Models that should be included in the sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<static>  $query  The query builder instance.
	 *
	 * @return Builder<static>
	 */
	public function scopeForSitemap( Builder $query ): Builder
	{
		return $query->whereDoesntHave( 'seoMeta', function ( Builder $q ): void {
			$q->where( 'exclude_from_sitemap', true )
				->orWhere( 'no_index', true );
		} );
	}

	/**
	 * Scope: Models with a specific focus keyword.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<static>  $query    The query builder instance.
	 * @param  string           $keyword  The focus keyword to search for.
	 *
	 * @return Builder<static>
	 */
	public function scopeWithFocusKeyword( Builder $query, string $keyword ): Builder
	{
		return $query->whereHas( 'seoMeta', function ( Builder $q ) use ( $keyword ): void {
			$q->where( 'focus_keyword', $keyword );
		} );
	}
}
