<?php

/**
 * SitemapEntry Model.
 *
 * Eloquent model for storing sitemap entries with polymorphic relationships.
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

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

/**
 * SitemapEntry model for sitemap data storage.
 *
 * @property int                $id
 * @property string             $sitemapable_type
 * @property int                $sitemapable_id
 * @property string             $url
 * @property string             $type
 * @property Carbon|null        $last_modified
 * @property float              $priority
 * @property string             $changefreq
 * @property bool               $is_indexable
 * @property array<int, array<string, string>>|null $images
 * @property array<int, array<string, mixed>>|null  $videos
 * @property Carbon|null        $created_at
 * @property Carbon|null        $updated_at
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SitemapEntry extends Model
{
	/**
	 * Valid change frequency values per sitemap protocol.
	 *
	 * @var array<int, string>
	 */
	public const VALID_CHANGEFREQ = [
		'always',
		'hourly',
		'daily',
		'weekly',
		'monthly',
		'yearly',
		'never',
	];

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'sitemap_entries';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'sitemapable_type',
		'sitemapable_id',
		'url',
		'type',
		'last_modified',
		'priority',
		'changefreq',
		'is_indexable',
		'images',
		'videos',
	];

	/**
	 * Get the parent sitemapable model.
	 *
	 * @since 1.0.0
	 *
	 * @return MorphTo<Model, SitemapEntry>
	 */
	public function sitemapable(): MorphTo
	{
		return $this->morphTo();
	}

	/**
	 * Scope a query to only include indexable entries.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SitemapEntry>  $query  The query builder instance.
	 *
	 * @return Builder<SitemapEntry>
	 */
	public function scopeIndexable( Builder $query ): Builder
	{
		return $query->where( 'is_indexable', true );
	}

	/**
	 * Scope a query to filter by type.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SitemapEntry>  $query  The query builder instance.
	 * @param  string                 $type   The type to filter by (e.g., 'page', 'post', 'product').
	 *
	 * @return Builder<SitemapEntry>
	 */
	public function scopeByType( Builder $query, string $type ): Builder
	{
		return $query->where( 'type', $type );
	}

	/**
	 * Scope a query to only include entries updated within a given period.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SitemapEntry>  $query  The query builder instance.
	 * @param  int                    $days   Number of days to look back (default: 7).
	 *
	 * @return Builder<SitemapEntry>
	 */
	public function scopeRecentlyUpdated( Builder $query, int $days = 7 ): Builder
	{
		return $query->where( 'last_modified', '>=', now()->subDays( $days ) );
	}

	/**
	 * Scope a query to filter by sitemapable model type.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SitemapEntry>  $query      The query builder instance.
	 * @param  string                 $modelType  The model class name.
	 *
	 * @return Builder<SitemapEntry>
	 */
	public function scopeForModel( Builder $query, string $modelType ): Builder
	{
		return $query->where( 'sitemapable_type', $modelType );
	}

	/**
	 * Scope a query to order by priority descending.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SitemapEntry>  $query  The query builder instance.
	 *
	 * @return Builder<SitemapEntry>
	 */
	public function scopeOrderByPriority( Builder $query ): Builder
	{
		return $query->orderByDesc( 'priority' );
	}

	/**
	 * Scope a query to order by last modified date descending.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SitemapEntry>  $query  The query builder instance.
	 *
	 * @return Builder<SitemapEntry>
	 */
	public function scopeOrderByLastModified( Builder $query ): Builder
	{
		return $query->orderByDesc( 'last_modified' );
	}

	/**
	 * Scope a query to only include entries with images.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SitemapEntry>  $query  The query builder instance.
	 *
	 * @return Builder<SitemapEntry>
	 */
	public function scopeWithImages( Builder $query ): Builder
	{
		return $query->whereNotNull( 'images' )
			->where( 'images', '!=', '[]' )
			->where( 'images', '!=', 'null' );
	}

	/**
	 * Scope a query to only include entries with videos.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SitemapEntry>  $query  The query builder instance.
	 *
	 * @return Builder<SitemapEntry>
	 */
	public function scopeWithVideos( Builder $query ): Builder
	{
		return $query->whereNotNull( 'videos' )
			->where( 'videos', '!=', '[]' )
			->where( 'videos', '!=', 'null' );
	}

	/**
	 * Check if the entry is indexable.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isIndexable(): bool
	{
		return $this->is_indexable;
	}

	/**
	 * Check if the entry has images.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function hasImages(): bool
	{
		return ! empty( $this->images );
	}

	/**
	 * Check if the entry has videos.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function hasVideos(): bool
	{
		return ! empty( $this->videos );
	}

	/**
	 * Get the last modified date formatted for sitemap XML.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function getLastModifiedForSitemap(): ?string
	{
		if ( null === $this->last_modified ) {
			return null;
		}

		return $this->last_modified->toW3cString();
	}

	/**
	 * Add an image to the sitemap entry.
	 *
	 * @since 1.0.0
	 *
	 * @param  string       $url      The image URL.
	 * @param  string|null  $title    The image title.
	 * @param  string|null  $caption  The image caption.
	 * @param  string|null  $geoLoc   The geographic location.
	 * @param  string|null  $license  The license URL.
	 *
	 * @return $this
	 */
	public function addImage(
		string $url,
		?string $title = null,
		?string $caption = null,
		?string $geoLoc = null,
		?string $license = null,
	): self {
		$images   = $this->images ?? [];
		$images[] = array_filter( [
			'loc'         => $url,
			'title'       => $title,
			'caption'     => $caption,
			'geo_loc'     => $geoLoc,
			'license'     => $license,
		] );

		$this->images = $images;

		return $this;
	}

	/**
	 * Add a video to the sitemap entry.
	 *
	 * @since 1.0.0
	 *
	 * @param  string       $thumbnailUrl   The video thumbnail URL.
	 * @param  string       $title          The video title.
	 * @param  string       $description    The video description.
	 * @param  string|null  $contentUrl     The video content URL.
	 * @param  string|null  $playerUrl      The video player embed URL.
	 * @param  int|null     $duration       Duration in seconds.
	 * @param  string|null  $expirationDate Expiration date.
	 *
	 * @return $this
	 */
	public function addVideo(
		string $thumbnailUrl,
		string $title,
		string $description,
		?string $contentUrl = null,
		?string $playerUrl = null,
		?int $duration = null,
		?string $expirationDate = null,
	): self {
		$videos   = $this->videos ?? [];
		$videos[] = array_filter( [
			'thumbnail_loc'   => $thumbnailUrl,
			'title'           => $title,
			'description'     => $description,
			'content_loc'     => $contentUrl,
			'player_loc'      => $playerUrl,
			'duration'        => $duration,
			'expiration_date' => $expirationDate,
		] );

		$this->videos = $videos;

		return $this;
	}

	/**
	 * Clear all images from the entry.
	 *
	 * @since 1.0.0
	 *
	 * @return $this
	 */
	public function clearImages(): self
	{
		$this->images = null;

		return $this;
	}

	/**
	 * Clear all videos from the entry.
	 *
	 * @since 1.0.0
	 *
	 * @return $this
	 */
	public function clearVideos(): self
	{
		$this->videos = null;

		return $this;
	}

	/**
	 * Get all unique types from the database.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public static function getAvailableTypes(): array
	{
		return static::query()
			->distinct()
			->pluck( 'type' )
			->toArray();
	}

	/**
	 * Find or create a sitemap entry for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model   $model  The model to find or create entry for.
	 * @param  string  $url    The URL for the sitemap entry.
	 *
	 * @return SitemapEntry
	 */
	public static function findOrCreateForModel( Model $model, string $url ): SitemapEntry
	{
		return static::updateOrCreate(
			[
				'sitemapable_type' => get_class( $model ),
				'sitemapable_id'   => $model->getKey(),
			],
			[
				'url' => $url,
			],
		);
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
		static::saving( function ( SitemapEntry $model ): void {
			// Validate priority range
			$priority = $model->priority;

			if ( null !== $priority && ( $priority < 0.0 || $priority > 1.0 ) ) {
				throw new InvalidArgumentException(
					__( 'The priority must be between 0.0 and 1.0.' ),
				);
			}

			// Validate changefreq value
			$changefreq = $model->changefreq;

			if ( null !== $changefreq && ! in_array( $changefreq, self::VALID_CHANGEFREQ, true ) ) {
				throw new InvalidArgumentException(
					__( 'The changefreq must be one of: :values.', [
						'values' => implode( ', ', self::VALID_CHANGEFREQ ),
					] ),
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
			'is_indexable'  => 'boolean',
			'last_modified' => 'datetime',
			'priority'      => 'decimal:1',
			'images'        => 'array',
			'videos'        => 'array',
		];
	}
}
