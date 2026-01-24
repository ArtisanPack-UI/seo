<?php

/**
 * AnalysisResultDTO.
 *
 * Data Transfer Object for SEO analysis results.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\DTOs;

/**
 * AnalysisResultDTO class.
 *
 * Represents the complete SEO analysis results including
 * scores, issues, suggestions, and passed checks.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
readonly class AnalysisResultDTO
{
	/**
	 * Create a new AnalysisResultDTO instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  int                                              $overallScore     The overall SEO score (0-100).
	 * @param  int                                              $readabilityScore The readability category score (0-100).
	 * @param  int                                              $keywordScore     The keyword category score (0-100).
	 * @param  int                                              $metaScore        The meta tags category score (0-100).
	 * @param  int                                              $contentScore     The content category score (0-100).
	 * @param  array<int, array{type: string, message: string}> $issues           List of issues found.
	 * @param  array<int, array{type: string, message: string}> $suggestions      List of improvement suggestions.
	 * @param  array<int, string>                               $passedChecks     List of passed checks.
	 * @param  string|null                                      $focusKeyword     The focus keyword used for analysis.
	 * @param  int                                              $wordCount        The content word count.
	 * @param  array<string, mixed>                             $analyzerResults  Individual analyzer results.
	 */
	public function __construct(
		public int $overallScore,
		public int $readabilityScore,
		public int $keywordScore,
		public int $metaScore,
		public int $contentScore,
		public array $issues,
		public array $suggestions,
		public array $passedChecks,
		public ?string $focusKeyword,
		public int $wordCount,
		public array $analyzerResults = [],
	) {
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
			$this->overallScore >= 80 => 'good',
			$this->overallScore >= 50 => 'ok',
			default                   => 'poor',
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
	 * Get the grade label for display.
	 *
	 * @since 1.0.0
	 *
	 * @return string Human-readable grade label.
	 */
	public function getGradeLabel(): string
	{
		return match ( $this->getGrade() ) {
			'good' => __( 'Good' ),
			'ok'   => __( 'Needs Improvement' ),
			'poor' => __( 'Poor' ),
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
		return count( $this->issues );
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
		return count( $this->suggestions );
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
		return count( $this->passedChecks );
	}

	/**
	 * Get issues filtered by type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $type  The issue type ('error' or 'warning').
	 *
	 * @return array<int, array{type: string, message: string}>
	 */
	public function getIssuesByType( string $type ): array
	{
		return array_filter(
			$this->issues,
			fn ( array $issue ): bool => ( $issue['type'] ?? null ) === $type,
		);
	}

	/**
	 * Get error issues.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{type: string, message: string}>
	 */
	public function getErrors(): array
	{
		return $this->getIssuesByType( 'error' );
	}

	/**
	 * Get warning issues.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{type: string, message: string}>
	 */
	public function getWarnings(): array
	{
		return $this->getIssuesByType( 'warning' );
	}

	/**
	 * Check if the result has any issues.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function hasIssues(): bool
	{
		return $this->getIssueCount() > 0;
	}

	/**
	 * Check if the result has any suggestions.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function hasSuggestions(): bool
	{
		return $this->getSuggestionCount() > 0;
	}

	/**
	 * Get the total count of all feedback items.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getTotalFeedbackCount(): int
	{
		return $this->getIssueCount() + $this->getSuggestionCount() + $this->getPassedCount();
	}

	/**
	 * Get results for a specific analyzer.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $analyzerName  The analyzer name.
	 *
	 * @return array<string, mixed>|null
	 */
	public function getAnalyzerResult( string $analyzerName ): ?array
	{
		return $this->analyzerResults[ $analyzerName ] ?? null;
	}

	/**
	 * Get score for a specific category.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $category  The category name.
	 *
	 * @return int
	 */
	public function getCategoryScore( string $category ): int
	{
		return match ( $category ) {
			'readability' => $this->readabilityScore,
			'keyword'     => $this->keywordScore,
			'meta'        => $this->metaScore,
			'content'     => $this->contentScore,
			default       => 0,
		};
	}

	/**
	 * Convert the DTO to an array.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'overall_score'     => $this->overallScore,
			'grade'             => $this->getGrade(),
			'grade_label'       => $this->getGradeLabel(),
			'grade_color'       => $this->getGradeColor(),
			'readability_score' => $this->readabilityScore,
			'keyword_score'     => $this->keywordScore,
			'meta_score'        => $this->metaScore,
			'content_score'     => $this->contentScore,
			'issues'            => $this->issues,
			'suggestions'       => $this->suggestions,
			'passed_checks'     => $this->passedChecks,
			'focus_keyword'     => $this->focusKeyword,
			'word_count'        => $this->wordCount,
			'issue_count'       => $this->getIssueCount(),
			'suggestion_count'  => $this->getSuggestionCount(),
			'passed_count'      => $this->getPassedCount(),
			'analyzer_results'  => $this->analyzerResults,
		];
	}

	/**
	 * Create a DTO from an array.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>  $data  The data array.
	 *
	 * @return self
	 */
	public static function fromArray( array $data ): self
	{
		return new self(
			overallScore: $data['overall_score'] ?? 0,
			readabilityScore: $data['readability_score'] ?? 0,
			keywordScore: $data['keyword_score'] ?? 0,
			metaScore: $data['meta_score'] ?? 0,
			contentScore: $data['content_score'] ?? 0,
			issues: $data['issues'] ?? [],
			suggestions: $data['suggestions'] ?? [],
			passedChecks: $data['passed_checks'] ?? [],
			focusKeyword: $data['focus_keyword'] ?? null,
			wordCount: $data['word_count'] ?? 0,
			analyzerResults: $data['analyzer_results'] ?? [],
		);
	}

	/**
	 * Create a DTO from a SeoAnalysisCache model.
	 *
	 * @since 1.0.0
	 *
	 * @param  \ArtisanPackUI\SEO\Models\SeoAnalysisCache  $cache  The cache model.
	 *
	 * @return self
	 */
	public static function fromCache( \ArtisanPackUI\SEO\Models\SeoAnalysisCache $cache ): self
	{
		return new self(
			overallScore: $cache->overall_score,
			readabilityScore: $cache->readability_score,
			keywordScore: $cache->keyword_score,
			metaScore: $cache->meta_score,
			contentScore: $cache->content_score,
			issues: $cache->issues ?? [],
			suggestions: $cache->suggestions ?? [],
			passedChecks: $cache->passed_checks ?? [],
			focusKeyword: $cache->focus_keyword_used,
			wordCount: $cache->content_word_count,
			analyzerResults: $cache->analyzer_results ?? [],
		);
	}
}
