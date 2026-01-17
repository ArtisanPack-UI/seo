<?php

/**
 * SeoAnalysisCache Model.
 *
 * Eloquent model for storing cached SEO analysis results.
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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SeoAnalysisCache model for caching SEO analysis results.
 *
 * @property int         $id
 * @property int         $seo_meta_id
 * @property int         $overall_score
 * @property int         $readability_score
 * @property int         $keyword_score
 * @property int         $meta_score
 * @property int         $content_score
 * @property array|null  $issues
 * @property array|null  $suggestions
 * @property array|null  $passed_checks
 * @property array|null  $analyzer_results
 * @property Carbon|null $analyzed_at
 * @property string|null $focus_keyword_used
 * @property int         $content_word_count
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 * @property-read SeoMeta $seoMeta
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SeoAnalysisCache extends Model
{
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'seo_analysis_cache';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'seo_meta_id',
		'overall_score',
		'readability_score',
		'keyword_score',
		'meta_score',
		'content_score',
		'issues',
		'suggestions',
		'passed_checks',
		'analyzer_results',
		'analyzed_at',
		'focus_keyword_used',
		'content_word_count',
	];

	/**
	 * Get the SEO meta this analysis belongs to.
	 *
	 * @since 1.0.0
	 *
	 * @return BelongsTo<SeoMeta, SeoAnalysisCache>
	 */
	public function seoMeta(): BelongsTo
	{
		return $this->belongsTo( SeoMeta::class, 'seo_meta_id' );
	}

	/**
	 * Get the grade based on overall score.
	 *
	 * @since 1.0.0
	 *
	 * @return string One of 'good', 'ok', or 'poor'.
	 */
	public function getGrade(): string
	{
		return match ( true ) {
			$this->overall_score >= 80 => 'good',
			$this->overall_score >= 50 => 'ok',
			default                    => 'poor',
		};
	}

	/**
	 * Get the color for the grade.
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS color name.
	 */
	public function getGradeColor(): string
	{
		return match ( $this->getGrade() ) {
			'good' => 'green',
			'ok'   => 'yellow',
			'poor' => 'red',
		};
	}

	/**
	 * Get the count of issues.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getIssueCount(): int
	{
		return is_array( $this->issues ) ? count( $this->issues ) : 0;
	}

	/**
	 * Get the count of suggestions.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getSuggestionCount(): int
	{
		return is_array( $this->suggestions ) ? count( $this->suggestions ) : 0;
	}

	/**
	 * Get the count of passed checks.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getPassedCount(): int
	{
		return is_array( $this->passed_checks ) ? count( $this->passed_checks ) : 0;
	}

	/**
	 * Check if the analysis is stale based on TTL.
	 *
	 * @since 1.0.0
	 *
	 * @param  int|null  $ttl  TTL in seconds. Uses config default if null.
	 *
	 * @return bool
	 */
	public function isStale( ?int $ttl = null ): bool
	{
		if ( null === $this->analyzed_at ) {
			return true;
		}

		$ttl = $ttl ?? config( 'seo.analysis.cache_ttl', 86400 );

		return $this->analyzed_at->copy()->addSeconds( $ttl )->isPast();
	}

	/**
	 * Check if the analysis needs refresh due to focus keyword change.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null  $currentKeyword  The current focus keyword.
	 *
	 * @return bool
	 */
	public function needsRefreshForKeyword( ?string $currentKeyword ): bool
	{
		return $this->focus_keyword_used !== $currentKeyword;
	}

	/**
	 * Scope a query to only include good grades (score >= 80).
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SeoAnalysisCache>  $query  The query builder instance.
	 *
	 * @return Builder<SeoAnalysisCache>
	 */
	public function scopeGoodGrade( Builder $query ): Builder
	{
		return $query->where( 'overall_score', '>=', 80 );
	}

	/**
	 * Scope a query to only include ok grades (score 50-79).
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SeoAnalysisCache>  $query  The query builder instance.
	 *
	 * @return Builder<SeoAnalysisCache>
	 */
	public function scopeOkGrade( Builder $query ): Builder
	{
		return $query->where( 'overall_score', '>=', 50 )
			->where( 'overall_score', '<', 80 );
	}

	/**
	 * Scope a query to only include poor grades (score < 50).
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SeoAnalysisCache>  $query  The query builder instance.
	 *
	 * @return Builder<SeoAnalysisCache>
	 */
	public function scopePoorGrade( Builder $query ): Builder
	{
		return $query->where( 'overall_score', '<', 50 );
	}

	/**
	 * Scope a query to only include stale analyses.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SeoAnalysisCache>  $query  The query builder instance.
	 * @param  int|null                   $ttl    TTL in seconds. Uses config default if null.
	 *
	 * @return Builder<SeoAnalysisCache>
	 */
	public function scopeStale( Builder $query, ?int $ttl = null ): Builder
	{
		$ttl       = $ttl ?? config( 'seo.analysis.cache_ttl', 86400 );
		$threshold = now()->subSeconds( $ttl );

		return $query->where( function ( Builder $q ) use ( $threshold ): void {
			$q->whereNull( 'analyzed_at' )
				->orWhere( 'analyzed_at', '<', $threshold );
		} );
	}

	/**
	 * Scope a query to only include non-stale (fresh) analyses.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SeoAnalysisCache>  $query  The query builder instance.
	 * @param  int|null                   $ttl    TTL in seconds. Uses config default if null.
	 *
	 * @return Builder<SeoAnalysisCache>
	 */
	public function scopeNotStale( Builder $query, ?int $ttl = null ): Builder
	{
		$ttl       = $ttl ?? config( 'seo.analysis.cache_ttl', 86400 );
		$threshold = now()->subSeconds( $ttl );

		return $query->whereNotNull( 'analyzed_at' )
			->where( 'analyzed_at', '>=', $threshold );
	}

	/**
	 * Scope a query to order by score descending.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SeoAnalysisCache>  $query  The query builder instance.
	 *
	 * @return Builder<SeoAnalysisCache>
	 */
	public function scopeOrderByScore( Builder $query ): Builder
	{
		return $query->orderByDesc( 'overall_score' );
	}

	/**
	 * Scope a query to filter by minimum score.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<SeoAnalysisCache>  $query     The query builder instance.
	 * @param  int                        $minScore  The minimum score.
	 *
	 * @return Builder<SeoAnalysisCache>
	 */
	public function scopeMinimumScore( Builder $query, int $minScore ): Builder
	{
		return $query->where( 'overall_score', '>=', $minScore );
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
			'overall_score'      => 'integer',
			'readability_score'  => 'integer',
			'keyword_score'      => 'integer',
			'meta_score'         => 'integer',
			'content_score'      => 'integer',
			'issues'             => 'array',
			'suggestions'        => 'array',
			'passed_checks'      => 'array',
			'analyzer_results'   => 'array',
			'analyzed_at'        => 'datetime',
			'content_word_count' => 'integer',
		];
	}
}
