<?php

/**
 * SeoAnalysisPanel Livewire Component.
 *
 * Displays SEO analysis results with scoring and recommendations.
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

namespace ArtisanPackUI\SEO\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * SeoAnalysisPanel component for displaying SEO analysis results.
 *
 * Shows overall SEO score with expandable details including
 * category scores, issues, suggestions, and passed checks.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SeoAnalysisPanel extends Component
{

	/**
	 * Score threshold for "good" rating.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const SCORE_GOOD = 70;

	/**
	 * Score threshold for "ok" rating.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const SCORE_OK = 40;

	/**
	 * The analysis data array.
	 *
	 * Expected structure:
	 * - overall_score: int (0-100)
	 * - readability_score: int (0-100)
	 * - keyword_score: int (0-100)
	 * - meta_score: int (0-100)
	 * - content_score: int (0-100)
	 * - issues: array<array{message: string, category: string}>
	 * - suggestions: array<array{message: string, category: string}>
	 * - passed_checks: array<string>
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, mixed>
	 */
	public array $analysis = [];

	/**
	 * Whether the panel details are expanded.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	public bool $expanded = false;

	/**
	 * Toggle the expanded state of the panel.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function toggle(): void
	{
		$this->expanded = ! $this->expanded;
	}

	/**
	 * Expand the panel.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function expand(): void
	{
		$this->expanded = true;
	}

	/**
	 * Collapse the panel.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function collapse(): void
	{
		$this->expanded = false;
	}

	/**
	 * Get the overall score from the analysis.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function overallScore(): int
	{
		return (int) ( $this->analysis['overall_score'] ?? 0 );
	}

	/**
	 * Get the color class for the overall score.
	 *
	 * Returns 'success' for good scores (>= 70),
	 * 'warning' for ok scores (>= 40),
	 * and 'error' for poor scores (< 40).
	 *
	 * @since 1.0.0
	 *
	 * @return string The color class name.
	 */
	#[Computed]
	public function scoreColor(): string
	{
		return $this->getColorForScore( $this->overallScore );
	}

	/**
	 * Get the label for the overall score.
	 *
	 * Returns a human-readable label based on the score value.
	 *
	 * @since 1.0.0
	 *
	 * @return string The score label.
	 */
	#[Computed]
	public function scoreLabel(): string
	{
		$score = $this->overallScore;

		if ( $score >= self::SCORE_GOOD ) {
			return __( 'Good' );
		}

		if ( $score >= self::SCORE_OK ) {
			return __( 'Needs Improvement' );
		}

		return __( 'Poor' );
	}

	/**
	 * Get the readability score.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function readabilityScore(): int
	{
		return (int) ( $this->analysis['readability_score'] ?? 0 );
	}

	/**
	 * Get the keyword score.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function keywordScore(): int
	{
		return (int) ( $this->analysis['keyword_score'] ?? 0 );
	}

	/**
	 * Get the meta score.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function metaScore(): int
	{
		return (int) ( $this->analysis['meta_score'] ?? 0 );
	}

	/**
	 * Get the content score.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function contentScore(): int
	{
		return (int) ( $this->analysis['content_score'] ?? 0 );
	}

	/**
	 * Get the color class for a specific category.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $category  The category key (readability, keyword, meta, content).
	 *
	 * @return string The color class name.
	 */
	public function getCategoryColor( string $category ): string
	{
		$score = match ( $category ) {
			'readability' => $this->readabilityScore,
			'keyword'     => $this->keywordScore,
			'meta'        => $this->metaScore,
			'content'     => $this->contentScore,
			default       => 0,
		};

		return $this->getColorForScore( $score );
	}

	/**
	 * Get the issues from the analysis.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{message: string, category?: string}>
	 */
	#[Computed]
	public function issues(): array
	{
		return $this->analysis['issues'] ?? [];
	}

	/**
	 * Get the suggestions from the analysis.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{message: string, category?: string}>
	 */
	#[Computed]
	public function suggestions(): array
	{
		return $this->analysis['suggestions'] ?? [];
	}

	/**
	 * Get the passed checks from the analysis.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	#[Computed]
	public function passedChecks(): array
	{
		return $this->analysis['passed_checks'] ?? [];
	}

	/**
	 * Check if there are any issues.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function hasIssues(): bool
	{
		return count( $this->issues ) > 0;
	}

	/**
	 * Check if there are any suggestions.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function hasSuggestions(): bool
	{
		return count( $this->suggestions ) > 0;
	}

	/**
	 * Check if there are any passed checks.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function hasPassedChecks(): bool
	{
		return count( $this->passedChecks ) > 0;
	}

	/**
	 * Get the total number of issues.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function issueCount(): int
	{
		return count( $this->issues );
	}

	/**
	 * Get the total number of suggestions.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function suggestionCount(): int
	{
		return count( $this->suggestions );
	}

	/**
	 * Get the total number of passed checks.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function passedCheckCount(): int
	{
		return count( $this->passedChecks );
	}

	/**
	 * Get the category scores for display.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{label: string, score: int, color: string}>
	 */
	#[Computed]
	public function categoryScores(): array
	{
		return [
			'readability' => [
				'label' => __( 'Readability' ),
				'score' => $this->readabilityScore,
				'color' => $this->getCategoryColor( 'readability' ),
			],
			'keyword'     => [
				'label' => __( 'Keywords' ),
				'score' => $this->keywordScore,
				'color' => $this->getCategoryColor( 'keyword' ),
			],
			'meta'        => [
				'label' => __( 'Meta Tags' ),
				'score' => $this->metaScore,
				'color' => $this->getCategoryColor( 'meta' ),
			],
			'content'     => [
				'label' => __( 'Content' ),
				'score' => $this->contentScore,
				'color' => $this->getCategoryColor( 'content' ),
			],
		];
	}

	/**
	 * Render the component.
	 *
	 * @since 1.0.0
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'seo::livewire.seo-analysis-panel' );
	}

	/**
	 * Get color class based on score value.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $score  The score value (0-100).
	 *
	 * @return string The color class name.
	 */
	protected function getColorForScore( int $score ): string
	{
		if ( $score >= self::SCORE_GOOD ) {
			return 'success';
		}

		if ( $score >= self::SCORE_OK ) {
			return 'warning';
		}

		return 'error';
	}
}
