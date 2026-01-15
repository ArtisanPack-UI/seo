<?php

/**
 * SeoMeta Model.
 *
 * Eloquent model for storing SEO metadata with polymorphic relationships.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

/**
 * SeoMeta model for SEO metadata storage.
 *
 * @property int         $id
 * @property string      $seoable_type
 * @property int         $seoable_id
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $canonical_url
 * @property bool        $no_index
 * @property bool        $no_follow
 * @property string|null $robots_meta
 * @property string|null $og_title
 * @property string|null $og_description
 * @property string|null $og_image
 * @property int|null    $og_image_id
 * @property string      $og_type
 * @property string|null $og_locale
 * @property string|null $og_site_name
 * @property string      $twitter_card
 * @property string|null $twitter_title
 * @property string|null $twitter_description
 * @property string|null $twitter_image
 * @property int|null    $twitter_image_id
 * @property string|null $twitter_site
 * @property string|null $twitter_creator
 * @property string|null $pinterest_description
 * @property string|null $pinterest_image
 * @property int|null    $pinterest_image_id
 * @property string|null $slack_title
 * @property string|null $slack_description
 * @property string|null $slack_image
 * @property int|null    $slack_image_id
 * @property string|null $schema_type
 * @property array|null  $schema_markup
 * @property string|null $focus_keyword
 * @property array|null  $secondary_keywords
 * @property array|null  $hreflang
 * @property float       $sitemap_priority
 * @property string      $sitemap_changefreq
 * @property bool        $exclude_from_sitemap
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SeoMeta extends Model
{
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'seo_meta';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'seoable_type',
		'seoable_id',
		'meta_title',
		'meta_description',
		'canonical_url',
		'no_index',
		'no_follow',
		'robots_meta',
		'og_title',
		'og_description',
		'og_image',
		'og_image_id',
		'og_type',
		'og_locale',
		'og_site_name',
		'twitter_card',
		'twitter_title',
		'twitter_description',
		'twitter_image',
		'twitter_image_id',
		'twitter_site',
		'twitter_creator',
		'pinterest_description',
		'pinterest_image',
		'pinterest_image_id',
		'slack_title',
		'slack_description',
		'slack_image',
		'slack_image_id',
		'schema_type',
		'schema_markup',
		'focus_keyword',
		'secondary_keywords',
		'hreflang',
		'sitemap_priority',
		'sitemap_changefreq',
		'exclude_from_sitemap',
	];

	/**
	 * Get the parent seoable model.
	 *
	 * @since 1.0.0
	 *
	 * @return MorphTo<Model, SeoMeta>
	 */
	public function seoable(): MorphTo
	{
		return $this->morphTo();
	}

	/**
	 * Scope a query to only include indexable entries.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SeoMeta>  $query  The query builder instance.
	 *
	 * @return Builder<SeoMeta>
	 */
	public function scopeIndexable( Builder $query ): Builder
	{
		return $query->where( 'no_index', false );
	}

	/**
	 * Scope a query to only include entries with a focus keyword.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SeoMeta>  $query  The query builder instance.
	 *
	 * @return Builder<SeoMeta>
	 */
	public function scopeWithFocusKeyword( Builder $query ): Builder
	{
		return $query->whereNotNull( 'focus_keyword' )
			->where( 'focus_keyword', '!=', '' );
	}

	/**
	 * Scope a query to only include entries included in sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SeoMeta>  $query  The query builder instance.
	 *
	 * @return Builder<SeoMeta>
	 */
	public function scopeInSitemap( Builder $query ): Builder
	{
		return $query->where( 'exclude_from_sitemap', false );
	}

	/**
	 * Scope a query to only include entries with a specific schema type.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SeoMeta>  $query  The query builder instance.
	 * @param  string            $type   The schema type to filter by.
	 *
	 * @return Builder<SeoMeta>
	 */
	public function scopeWithSchemaType( Builder $query, string $type ): Builder
	{
		return $query->where( 'schema_type', $type );
	}

	/**
	 * Scope a query to filter by seoable type.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SeoMeta>  $query  The query builder instance.
	 * @param  string            $type   The seoable type class name.
	 *
	 * @return Builder<SeoMeta>
	 */
	public function scopeForType( Builder $query, string $type ): Builder
	{
		return $query->where( 'seoable_type', $type );
	}

	/**
	 * Get the effective title, falling back to seoable title or app name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getEffectiveTitle(): string
	{
		if ( null !== $this->meta_title && '' !== $this->meta_title ) {
			return $this->meta_title;
		}

		$seoable = $this->seoable;
		if ( null !== $seoable && isset( $seoable->title ) ) {
			return $seoable->title;
		}

		return config( 'app.name', '' );
	}

	/**
	 * Get the effective description, falling back to seoable excerpt.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function getEffectiveDescription(): ?string
	{
		if ( null !== $this->meta_description && '' !== $this->meta_description ) {
			return $this->meta_description;
		}

		$seoable = $this->seoable;
		if ( null !== $seoable && isset( $seoable->excerpt ) ) {
			return $seoable->excerpt;
		}

		return null;
	}

	/**
	 * Get the effective Open Graph image URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function getEffectiveOgImage(): ?string
	{
		// Check for media library integration
		if ( null !== $this->og_image_id && class_exists( 'ArtisanPackUI\MediaLibrary\Models\Media' ) ) {
			$media = \ArtisanPackUI\MediaLibrary\Models\Media::find( $this->og_image_id );
			if ( null !== $media ) {
				return $media->url;
			}
		}

		// Fall back to direct URL
		if ( null !== $this->og_image && '' !== $this->og_image ) {
			return $this->og_image;
		}

		// Fall back to seoable's featured image
		$seoable = $this->seoable;
		if ( null !== $seoable && isset( $seoable->featured_image ) ) {
			return $seoable->featured_image;
		}

		return null;
	}

	/**
	 * Get the robots meta content string.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getRobotsContent(): string
	{
		$directives = [];

		if ( $this->no_index ) {
			$directives[] = 'noindex';
		}

		if ( $this->no_follow ) {
			$directives[] = 'nofollow';
		}

		if ( null !== $this->robots_meta && '' !== $this->robots_meta ) {
			$directives[] = $this->robots_meta;
		}

		if ( empty( $directives ) ) {
			return 'index, follow';
		}

		return implode( ', ', $directives );
	}

	/**
	 * Check if the entry is indexable by search engines.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isIndexable(): bool
	{
		return ! $this->no_index;
	}

	/**
	 * Check if the entry is followable by search engines.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isFollowable(): bool
	{
		return ! $this->no_follow;
	}

	/**
	 * Check if the entry should be included in sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function shouldIncludeInSitemap(): bool
	{
		return ! $this->exclude_from_sitemap && $this->isIndexable();
	}

	/**
	 * Get all keywords (focus + secondary) as an array.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public function getAllKeywords(): array
	{
		$keywords = [];

		if ( null !== $this->focus_keyword && '' !== $this->focus_keyword ) {
			$keywords[] = $this->focus_keyword;
		}

		if ( is_array( $this->secondary_keywords ) ) {
			$keywords = array_merge( $keywords, $this->secondary_keywords );
		}

		return $keywords;
	}

	/**
	 * Check if the entry has Open Graph data.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function hasOpenGraphData(): bool
	{
		return null !== $this->og_title
			|| null !== $this->og_description
			|| null !== $this->og_image
			|| null !== $this->og_image_id;
	}

	/**
	 * Check if the entry has Twitter Card data.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function hasTwitterCardData(): bool
	{
		return null !== $this->twitter_title
			|| null !== $this->twitter_description
			|| null !== $this->twitter_image
			|| null !== $this->twitter_image_id;
	}

	/**
	 * Check if the entry has schema markup.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function hasSchemaMarkup(): bool
	{
		return null !== $this->schema_type || ! empty( $this->schema_markup );
	}

	/**
	 * Bootstrap the model and register event listeners.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected static function booted(): void
	{
		static::saving( function ( SeoMeta $model ): void {
			$priority = $model->sitemap_priority;

			if ( null !== $priority && ( $priority < 0.0 || $priority > 1.0 ) ) {
				throw new InvalidArgumentException(
					'The sitemap_priority must be between 0.0 and 1.0.',
				);
			}
		} );
	}

	/**
	 * Get the attributes that should be cast.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'no_index'             => 'boolean',
			'no_follow'            => 'boolean',
			'exclude_from_sitemap' => 'boolean',
			'schema_markup'        => 'array',
			'secondary_keywords'   => 'array',
			'hreflang'             => 'array',
			'sitemap_priority'     => 'decimal:1',
			'og_image_id'          => 'integer',
			'twitter_image_id'     => 'integer',
			'pinterest_image_id'   => 'integer',
			'slack_image_id'       => 'integer',
		];
	}
}
