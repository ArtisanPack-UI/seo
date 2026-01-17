<?php

/**
 * AnalysisService.
 *
 * Orchestrates SEO content analysis across multiple analyzers.
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

use ArtisanPackUI\SEO\Contracts\AnalyzerContract;
use ArtisanPackUI\SEO\DTOs\AnalysisResultDTO;
use ArtisanPackUI\SEO\Models\SeoAnalysisCache;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * AnalysisService class.
 *
 * Main orchestrator for all SEO analysis operations. Coordinates
 * multiple analyzers and calculates category and overall scores.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class AnalysisService
{
	/**
	 * Default category weights for score calculation.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, int>
	 */
	protected const DEFAULT_CATEGORY_WEIGHTS = [
		'readability' => 25,
		'keyword'     => 30,
		'meta'        => 20,
		'content'     => 25,
	];

	/**
	 * Valid analysis categories.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	protected const VALID_CATEGORIES = [
		'readability',
		'keyword',
		'meta',
		'content',
	];

	/**
	 * Registered analyzers.
	 *
	 * @var array<string, AnalyzerContract>
	 */
	protected array $analyzers = [];

	/**
	 * Category weights for score calculation.
	 *
	 * @var array<string, int>
	 */
	protected array $categoryWeights;

	/**
	 * Create a new AnalysisService instance.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->categoryWeights = config(
			'seo.analysis.category_weights',
			self::DEFAULT_CATEGORY_WEIGHTS,
		);
	}

	/**
	 * Run full analysis on a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model        $model         The model to analyze.
	 * @param  string|null  $focusKeyword  Optional focus keyword override.
	 * @param  bool         $useCache      Whether to use cached results.
	 *
	 * @return AnalysisResultDTO
	 */
	public function analyze( Model $model, ?string $focusKeyword = null, bool $useCache = true ): AnalysisResultDTO
	{
		$seoMeta      = $this->getSeoMeta( $model );
		$focusKeyword = $focusKeyword ?? $seoMeta?->focus_keyword;

		// Check cache first
		if ( $useCache && config( 'seo.analysis.cache_enabled', true ) ) {
			$cached = $this->getCachedResult( $seoMeta, $focusKeyword );
			if ( null !== $cached ) {
				return $cached;
			}
		}

		// Extract content from model
		$content = $this->extractContent( $model );

		// Run all analyzers
		$results      = [];
		$issues       = [];
		$suggestions  = [];
		$passedChecks = [];

		foreach ( $this->getEnabledAnalyzers() as $name => $analyzer ) {
			$result = $analyzer->analyze( $model, $content, $focusKeyword, $seoMeta );

			$results[ $name ] = $result;

			// Collect issues, suggestions, and passed checks
			$issues       = array_merge( $issues, $result['issues'] ?? [] );
			$suggestions  = array_merge( $suggestions, $result['suggestions'] ?? [] );
			$passedChecks = array_merge( $passedChecks, $result['passed'] ?? [] );
		}

		// Calculate category scores
		$readabilityScore = $this->calculateCategoryScore( $results, 'readability' );
		$keywordScore     = $this->calculateCategoryScore( $results, 'keyword' );
		$metaScore        = $this->calculateCategoryScore( $results, 'meta' );
		$contentScore     = $this->calculateCategoryScore( $results, 'content' );

		// Calculate overall weighted score
		$overallScore = $this->calculateOverallScore( [
			'readability' => $readabilityScore,
			'keyword'     => $keywordScore,
			'meta'        => $metaScore,
			'content'     => $contentScore,
		] );

		// Create result DTO
		$analysisResult = new AnalysisResultDTO(
			overallScore: $overallScore,
			readabilityScore: $readabilityScore,
			keywordScore: $keywordScore,
			metaScore: $metaScore,
			contentScore: $contentScore,
			issues: $issues,
			suggestions: $suggestions,
			passedChecks: $passedChecks,
			focusKeyword: $focusKeyword,
			wordCount: str_word_count( strip_tags( $content ) ),
			analyzerResults: $results,
		);

		// Cache the results
		if ( config( 'seo.analysis.cache_enabled', true ) && null !== $seoMeta ) {
			$this->cacheResults( $seoMeta, $analysisResult );
		}

		return $analysisResult;
	}

	/**
	 * Register a custom analyzer.
	 *
	 * @since 1.0.0
	 *
	 * @param  string            $name      The unique analyzer name.
	 * @param  AnalyzerContract  $analyzer  The analyzer instance.
	 *
	 * @throws InvalidArgumentException If the analyzer category is invalid.
	 *
	 * @return self
	 */
	public function registerAnalyzer( string $name, AnalyzerContract $analyzer ): self
	{
		$category = $analyzer->getCategory();

		if ( ! in_array( $category, self::VALID_CATEGORIES, true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					__( 'Invalid analyzer category "%s". Valid categories: %s' ),
					$category,
					implode( ', ', self::VALID_CATEGORIES ),
				),
			);
		}

		$this->analyzers[ $name ] = $analyzer;

		return $this;
	}

	/**
	 * Unregister an analyzer.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $name  The analyzer name to remove.
	 *
	 * @return self
	 */
	public function unregisterAnalyzer( string $name ): self
	{
		unset( $this->analyzers[ $name ] );

		return $this;
	}

	/**
	 * Check if an analyzer is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $name  The analyzer name.
	 *
	 * @return bool
	 */
	public function hasAnalyzer( string $name ): bool
	{
		return isset( $this->analyzers[ $name ] );
	}

	/**
	 * Get all registered analyzers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, AnalyzerContract>
	 */
	public function getAnalyzers(): array
	{
		return $this->analyzers;
	}

	/**
	 * Get analyzers for a specific category.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $category  The category name.
	 *
	 * @return array<string, AnalyzerContract>
	 */
	public function getAnalyzersByCategory( string $category ): array
	{
		return array_filter(
			$this->analyzers,
			fn ( AnalyzerContract $analyzer ): bool => $analyzer->getCategory() === $category,
		);
	}

	/**
	 * Set category weights for score calculation.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, int>  $weights  The category weights.
	 *
	 * @return self
	 */
	public function setCategoryWeights( array $weights ): self
	{
		$this->categoryWeights = array_merge( self::DEFAULT_CATEGORY_WEIGHTS, $weights );

		return $this;
	}

	/**
	 * Get current category weights.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, int>
	 */
	public function getCategoryWeights(): array
	{
		return $this->categoryWeights;
	}

	/**
	 * Get the weight for a specific category.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $category  The category name.
	 *
	 * @return int
	 */
	public function getCategoryWeight( string $category ): int
	{
		return $this->categoryWeights[ $category ] ?? 0;
	}

	/**
	 * Clear the analysis cache for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to clear cache for.
	 *
	 * @return bool
	 */
	public function clearCache( Model $model ): bool
	{
		$seoMeta = $this->getSeoMeta( $model );

		if ( null === $seoMeta ) {
			return false;
		}

		return SeoAnalysisCache::where( 'seo_meta_id', $seoMeta->id )->delete() > 0;
	}

	/**
	 * Clear all analysis caches.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of cleared records.
	 */
	public function clearAllCaches(): int
	{
		$count = SeoAnalysisCache::count();
		SeoAnalysisCache::truncate();
		return $count;
	}

	/**
	 * Get analysis statistics across all cached analyses.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function getStatistics(): array
	{
		$total     = SeoAnalysisCache::count();
		$goodCount = SeoAnalysisCache::goodGrade()->count();
		$okCount   = SeoAnalysisCache::okGrade()->count();
		$poorCount = SeoAnalysisCache::poorGrade()->count();

		$avgScore = $total > 0
			? (int) round( SeoAnalysisCache::avg( 'overall_score' ) )
			: 0;

		$staleCount = SeoAnalysisCache::stale()->count();

		return [
			'total'           => $total,
			'good_count'      => $goodCount,
			'ok_count'        => $okCount,
			'poor_count'      => $poorCount,
			'average_score'   => $avgScore,
			'stale_count'     => $staleCount,
			'fresh_count'     => $total - $staleCount,
			'good_percentage' => $total > 0 ? round( $goodCount / $total * 100, 1 ) : 0,
			'ok_percentage'   => $total > 0 ? round( $okCount / $total * 100, 1 ) : 0,
			'poor_percentage' => $total > 0 ? round( $poorCount / $total * 100, 1 ) : 0,
		];
	}

	/**
	 * Get the SEO meta for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model.
	 *
	 * @return SeoMeta|null
	 */
	protected function getSeoMeta( Model $model ): ?SeoMeta
	{
		if ( method_exists( $model, 'seoMeta' ) ) {
			return $model->seoMeta;
		}

		return null;
	}

	/**
	 * Extract content from a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model.
	 *
	 * @return string
	 */
	protected function extractContent( Model $model ): string
	{
		// Try common content field names
		$contentFields = [ 'content', 'body', 'description', 'text' ];

		foreach ( $contentFields as $field ) {
			if ( isset( $model->{$field} ) && is_string( $model->{$field} ) ) {
				return $model->{$field};
			}
		}

		// Check for a getContent method
		if ( method_exists( $model, 'getContent' ) ) {
			return $model->getContent();
		}

		// Check for a getSeoContent method
		if ( method_exists( $model, 'getSeoContent' ) ) {
			return $model->getSeoContent();
		}

		return '';
	}

	/**
	 * Get enabled analyzers based on config.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, AnalyzerContract>
	 */
	protected function getEnabledAnalyzers(): array
	{
		$enabledConfig = config( 'seo.analysis.analyzers', [] );

		// If no config or all true, return all analyzers
		if ( empty( $enabledConfig ) ) {
			return $this->analyzers;
		}

		return array_filter(
			$this->analyzers,
			function ( AnalyzerContract $analyzer ) use ( $enabledConfig ): bool {
				$name = $analyzer->getName();

				return $enabledConfig[ $name ] ?? true;
			},
		);
	}

	/**
	 * Calculate the score for a category.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, array<string, mixed>>  $results   The analyzer results.
	 * @param  string                               $category  The category to calculate.
	 *
	 * @return int
	 */
	protected function calculateCategoryScore( array $results, string $category ): int
	{
		$categoryAnalyzers = $this->getAnalyzersByCategory( $category );

		if ( empty( $categoryAnalyzers ) ) {
			return 0;
		}

		$weightedSum   = 0;
		$totalWeight   = 0;

		foreach ( $categoryAnalyzers as $name => $analyzer ) {
			if ( ! isset( $results[ $name ]['score'] ) ) {
				continue;
			}

			$score  = $results[ $name ]['score'];
			$weight = $analyzer->getWeight();

			$weightedSum += $score * $weight;
			$totalWeight += $weight;
		}

		if ( 0 === $totalWeight ) {
			return 0;
		}

		return (int) round( $weightedSum / $totalWeight );
	}

	/**
	 * Calculate the overall weighted score.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, int>  $categoryScores  The category scores.
	 *
	 * @return int
	 */
	protected function calculateOverallScore( array $categoryScores ): int
	{
		$weightedSum = 0;
		$totalWeight = 0;

		foreach ( $categoryScores as $category => $score ) {
			$weight       = $this->categoryWeights[ $category ] ?? 0;
			$weightedSum += $score * $weight;
			$totalWeight += $weight;
		}

		if ( 0 === $totalWeight ) {
			return 0;
		}

		return (int) round( $weightedSum / $totalWeight );
	}

	/**
	 * Get cached analysis result.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta|null  $seoMeta       The SEO meta.
	 * @param  string|null   $focusKeyword  The focus keyword.
	 *
	 * @return AnalysisResultDTO|null
	 */
	protected function getCachedResult( ?SeoMeta $seoMeta, ?string $focusKeyword ): ?AnalysisResultDTO
	{
		if ( null === $seoMeta ) {
			return null;
		}

		$cache = SeoAnalysisCache::where( 'seo_meta_id', $seoMeta->id )->first();

		if ( null === $cache ) {
			return null;
		}

		// Check if cache is stale
		if ( $cache->isStale() ) {
			return null;
		}

		// Check if focus keyword changed
		if ( $cache->needsRefreshForKeyword( $focusKeyword ) ) {
			return null;
		}

		return AnalysisResultDTO::fromCache( $cache );
	}

	/**
	 * Cache analysis results.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta            $seoMeta  The SEO meta.
	 * @param  AnalysisResultDTO  $result   The analysis result.
	 *
	 * @return SeoAnalysisCache
	 */
	protected function cacheResults( SeoMeta $seoMeta, AnalysisResultDTO $result ): SeoAnalysisCache
	{
		return SeoAnalysisCache::updateOrCreate(
			[ 'seo_meta_id' => $seoMeta->id ],
			[
				'overall_score'      => $result->overallScore,
				'readability_score'  => $result->readabilityScore,
				'keyword_score'      => $result->keywordScore,
				'meta_score'         => $result->metaScore,
				'content_score'      => $result->contentScore,
				'issues'             => $result->issues,
				'suggestions'        => $result->suggestions,
				'passed_checks'      => $result->passedChecks,
				'analyzer_results'   => $result->analyzerResults,
				'analyzed_at'        => now(),
				'focus_keyword_used' => $result->focusKeyword,
				'content_word_count' => $result->wordCount,
			],
		);
	}
}
