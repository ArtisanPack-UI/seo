<?php

/**
 * KeywordDensityAnalyzer.
 *
 * Analyzes keyword density in content.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Services\Analysis;

use ArtisanPackUI\SEO\Contracts\AnalyzerContract;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

/**
 * KeywordDensityAnalyzer class.
 *
 * Calculates and evaluates keyword density, ensuring
 * it falls within the optimal range (0.5-2.5%).
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class KeywordDensityAnalyzer implements AnalyzerContract
{
	/**
	 * Minimum acceptable keyword density percentage.
	 *
	 * @since 1.0.0
	 *
	 * @var float
	 */
	protected const MIN_DENSITY = 0.5;

	/**
	 * Maximum acceptable keyword density percentage.
	 *
	 * @since 1.0.0
	 *
	 * @var float
	 */
	protected const MAX_DENSITY = 2.5;

	/**
	 * Ideal keyword density percentage.
	 *
	 * @since 1.0.0
	 *
	 * @var float
	 */
	protected const IDEAL_DENSITY = 1.5;

	/**
	 * Analyze keyword density in content.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model        $_model        The model being analyzed (unused).
	 * @param  string       $content       The content to analyze.
	 * @param  string|null  $focusKeyword  The focus keyword.
	 * @param  SeoMeta|null $_seoMeta      The SEO meta data (unused).
	 *
	 * @return array{
	 *     score: int,
	 *     issues: array<int, array{type: string, message: string}>,
	 *     suggestions: array<int, array{type: string, message: string}>,
	 *     passed: array<int, string>,
	 *     details: array<string, mixed>
	 * }
	 */
	public function analyze( Model $_model, string $content, ?string $focusKeyword, ?SeoMeta $_seoMeta ): array
	{
		// Check if focus keyword is set
		if ( null === $focusKeyword || '' === trim( $focusKeyword ) ) {
			return [
				'score'       => 0,
				'issues'      => [ [ 'type' => 'warning', 'message' => __( 'No focus keyword set.' ) ] ],
				'suggestions' => [ [ 'type' => 'suggestion', 'message' => __( 'Set a focus keyword to analyze keyword density.' ) ] ],
				'passed'      => [],
				'details'     => [],
			];
		}

		$text      = strtolower( strip_tags( $content ) );
		$keyword   = strtolower( trim( $focusKeyword ) );
		$wordCount = str_word_count( $text );

		// Handle empty content
		if ( 0 === $wordCount ) {
			return [
				'score'       => 0,
				'issues'      => [ [ 'type' => 'error', 'message' => __( 'No content to analyze for keyword density.' ) ] ],
				'suggestions' => [],
				'passed'      => [],
				'details'     => [],
			];
		}

		// Count keyword occurrences using word boundaries
		$escapedKeyword   = preg_quote( $keyword, '/' );
		$keywordCount     = preg_match_all( '/\b' . $escapedKeyword . '\b/i', $text );
		$keywordWordCount = str_word_count( $keyword );
		$density          = ( $keywordCount * $keywordWordCount / $wordCount ) * 100;

		$issues      = [];
		$suggestions = [];
		$passed      = [];

		// Evaluate density
		if ( $density < self::MIN_DENSITY ) {
			$issues[] = [
				'type'    => 'warning',
				'message' => sprintf(
					__( 'Keyword density is too low (%.2f%%). Aim for %.1f-%.1f%%.' ),
					$density,
					self::MIN_DENSITY,
					self::MAX_DENSITY,
				),
			];
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => sprintf(
					__( 'Try to include "%s" more naturally in your content.' ),
					$focusKeyword,
				),
			];
		} elseif ( $density > self::MAX_DENSITY ) {
			$issues[] = [
				'type'    => 'warning',
				'message' => sprintf(
					__( 'Keyword density is too high (%.2f%%). This may be seen as keyword stuffing.' ),
					$density,
				),
			];
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => __( 'Consider using synonyms or related terms instead.' ),
			];
		} else {
			$passed[] = sprintf( __( 'Good keyword density: %.2f%%.' ), $density );
		}

		// Check keyword in first paragraph
		$paragraphs     = preg_split( '/<\/p>|\n\n/', $content );
		$firstParagraph = strtolower( strip_tags( is_array( $paragraphs ) ? ( $paragraphs[0] ?? '' ) : '' ) );

		if ( str_contains( $firstParagraph, $keyword ) ) {
			$passed[] = __( 'Focus keyword appears in the first paragraph.' );
		} else {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => __( 'Include your focus keyword in the first paragraph.' ),
			];
		}

		// Calculate score
		$score = $this->calculateDensityScore( $density );

		return [
			'score'       => $score,
			'issues'      => $issues,
			'suggestions' => $suggestions,
			'passed'      => $passed,
			'details'     => [
				'keyword'            => $focusKeyword,
				'occurrences'        => $keywordCount,
				'density'            => round( $density, 2 ),
				'word_count'         => $wordCount,
				'in_first_paragraph' => str_contains( $firstParagraph, $keyword ),
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
		return 'keyword_density';
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
		return 'keyword';
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
		return 50;
	}

	/**
	 * Calculate score based on keyword density.
	 *
	 * @since 1.0.0
	 *
	 * @param  float  $density  The keyword density percentage.
	 *
	 * @return int Score from 0-100.
	 */
	protected function calculateDensityScore( float $density ): int
	{
		// Within ideal range
		if ( $density >= self::MIN_DENSITY && $density <= self::MAX_DENSITY ) {
			$deviation    = abs( $density - self::IDEAL_DENSITY );
			$maxDeviation = max( self::IDEAL_DENSITY - self::MIN_DENSITY, self::MAX_DENSITY - self::IDEAL_DENSITY );

			return (int) round( 100 - ( $deviation / $maxDeviation * 20 ) );
		}

		// Below minimum density
		if ( $density < self::MIN_DENSITY ) {
			if ( 0.0 === $density ) {
				return 0;
			}

			return (int) round( ( $density / self::MIN_DENSITY ) * 60 );
		}

		// Above maximum density
		$overAmount = $density - self::MAX_DENSITY;

		return (int) max( 0, round( 60 - ( $overAmount * 20 ) ) );
	}
}
