<?php
/**
 * HeadingStructureAnalyzer.
 *
 * Analyzes HTML heading hierarchy and usage.
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

namespace ArtisanPackUI\SEO\Services\Analysis;

use ArtisanPackUI\SEO\Contracts\AnalyzerContract;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

/**
 * HeadingStructureAnalyzer class.
 *
 * Evaluates the proper use of H1-H6 headings:
 * - Single H1 presence
 * - Logical heading hierarchy
 * - Subheading usage for readability
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class HeadingStructureAnalyzer implements AnalyzerContract
{
	/**
	 * Analyze heading structure in content.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model        $model         The model being analyzed.
	 * @param  string       $content       The content to analyze.
	 * @param  string|null  $focusKeyword  The focus keyword.
	 * @param  SeoMeta|null $seoMeta       The SEO meta data.
	 *
	 * @return array{
	 *     score: int,
	 *     issues: array<int, array{type: string, message: string}>,
	 *     suggestions: array<int, array{type: string, message: string}>,
	 *     passed: array<int, string>,
	 *     details: array<string, mixed>
	 * }
	 */
	public function analyze( Model $model, string $content, ?string $focusKeyword, ?SeoMeta $seoMeta ): array
	{
		$issues      = [];
		$suggestions = [];
		$passed      = [];
		$score       = 100;

		// Extract all headings
		$headings = $this->extractHeadings( $content );

		// Count headings by level
		$h1Count = count( $headings[1] ?? [] );
		$h2Count = count( $headings[2] ?? [] );
		$h3Count = count( $headings[3] ?? [] );
		$h4Count = count( $headings[4] ?? [] );
		$h5Count = count( $headings[5] ?? [] );
		$h6Count = count( $headings[6] ?? [] );

		$totalSubheadings = $h2Count + $h3Count + $h4Count + $h5Count + $h6Count;

		// Check H1 usage
		if ( 0 === $h1Count ) {
			$issues[] = [
				'type'    => 'error',
				'message' => __( 'No H1 heading found. Every page should have exactly one H1.' ),
			];
			$score -= 25;
		} elseif ( $h1Count > 1 ) {
			$issues[] = [
				'type'    => 'warning',
				'message' => sprintf(
					__( 'Multiple H1 headings found (%d). Each page should have exactly one H1.' ),
					$h1Count,
				),
			];
			$score -= 15;
		} else {
			$passed[] = __( 'Page has exactly one H1 heading.' );
		}

		// Check for subheadings
		if ( 0 === $totalSubheadings ) {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => __( 'No subheadings found. Use H2-H6 headings to organize your content.' ),
			];
			$score -= 20;
		} elseif ( $h2Count > 0 ) {
			$passed[] = sprintf( __( 'Content uses %d subheading(s) for better structure.' ), $totalSubheadings );
		}

		// Check heading hierarchy (H2 should come before H3, etc.)
		$hierarchyIssues = $this->checkHierarchy( $content );
		if ( count( $hierarchyIssues ) > 0 ) {
			foreach ( $hierarchyIssues as $issue ) {
				$issues[] = [
					'type'    => 'warning',
					'message' => $issue,
				];
			}
			$score -= min( 20, count( $hierarchyIssues ) * 5 );
		} else {
			$passed[] = __( 'Heading hierarchy is properly structured.' );
		}

		// Check if H2s are used without skipping
		if ( 0 === $h2Count && ( $h3Count > 0 || $h4Count > 0 ) ) {
			$issues[] = [
				'type'    => 'warning',
				'message' => __( 'H3/H4 headings found without H2. Use headings in proper order.' ),
			];
			$score -= 10;
		}

		// Word count check for subheading frequency
		$wordCount = str_word_count( strip_tags( $content ) );
		if ( $wordCount > 300 && 0 === $h2Count ) {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => __( 'Long content without subheadings. Consider adding H2 headings to improve readability.' ),
			];
			$score -= 10;
		}

		return [
			'score'       => max( 0, $score ),
			'issues'      => $issues,
			'suggestions' => $suggestions,
			'passed'      => $passed,
			'details'     => [
				'h1_count'          => $h1Count,
				'h2_count'          => $h2Count,
				'h3_count'          => $h3Count,
				'h4_count'          => $h4Count,
				'h5_count'          => $h5Count,
				'h6_count'          => $h6Count,
				'total_subheadings' => $totalSubheadings,
				'headings'          => $headings,
			],
		];
	}

	/**
	 * Get the analyzer name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return 'heading_structure';
	}

	/**
	 * Get the analyzer category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getCategory(): string
	{
		return 'content';
	}

	/**
	 * Get the analyzer weight.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getWeight(): int
	{
		return 25;
	}

	/**
	 * Extract headings from content by level.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $content  The HTML content.
	 *
	 * @return array<int, array<int, string>>
	 */
	protected function extractHeadings( string $content ): array
	{
		$headings = [];

		for ( $level = 1; $level <= 6; $level++ ) {
			preg_match_all( "/<h{$level}[^>]*>(.*?)<\/h{$level}>/si", $content, $matches );
			$headings[ $level ] = array_map( 'strip_tags', $matches[1] ?? [] );
		}

		return $headings;
	}

	/**
	 * Check heading hierarchy for skipped levels.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $content  The HTML content.
	 *
	 * @return array<int, string>
	 */
	protected function checkHierarchy( string $content ): array
	{
		$issues = [];

		// Extract all headings in order
		preg_match_all( '/<h([1-6])[^>]*>(.*?)<\/h\1>/si', $content, $matches, PREG_SET_ORDER );

		$previousLevel = 0;

		foreach ( $matches as $match ) {
			$currentLevel = (int) $match[1];

			// Check if we skipped a level (e.g., H1 to H3)
			if ( $previousLevel > 0 && $currentLevel > $previousLevel + 1 ) {
				$issues[] = sprintf(
					__( 'Heading hierarchy issue: H%d followed by H%d (skipped H%d).' ),
					$previousLevel,
					$currentLevel,
					$previousLevel + 1,
				);
			}

			$previousLevel = $currentLevel;
		}

		return $issues;
	}
}
