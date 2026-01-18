<?php
/**
 * ContentLengthAnalyzer.
 *
 * Analyzes content word count.
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
 * ContentLengthAnalyzer class.
 *
 * Evaluates content length based on word count:
 * - Minimum: 300 words
 * - Good: 600 words
 * - Excellent: 1000+ words
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class ContentLengthAnalyzer implements AnalyzerContract
{
	/**
	 * Minimum acceptable word count.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const MIN_WORDS = 300;

	/**
	 * Good word count threshold.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const GOOD_WORDS = 600;

	/**
	 * Excellent word count threshold.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const EXCELLENT_WORDS = 1000;

	/**
	 * Analyze content length.
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
		$text      = strip_tags( $content );
		$wordCount = str_word_count( $text );

		$issues      = [];
		$suggestions = [];
		$passed      = [];
		$rating      = '';

		if ( $wordCount < self::MIN_WORDS ) {
			$issues[] = [
				'type'    => 'warning',
				'message' => sprintf(
					__( 'Content is too short (%d words). Aim for at least %d words.' ),
					$wordCount,
					self::MIN_WORDS,
				),
			];
			$score  = (int) round( ( $wordCount / self::MIN_WORDS ) * 50 );
			$rating = 'poor';
		} elseif ( $wordCount < self::GOOD_WORDS ) {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => sprintf(
					__( 'Content length is acceptable (%d words). Consider expanding to %d+ words for better ranking.' ),
					$wordCount,
					self::GOOD_WORDS,
				),
			];
			$score  = 60 + (int) round( ( ( $wordCount - self::MIN_WORDS ) / ( self::GOOD_WORDS - self::MIN_WORDS ) ) * 20 );
			$rating = 'acceptable';
		} elseif ( $wordCount < self::EXCELLENT_WORDS ) {
			$passed[] = sprintf( __( 'Good content length: %d words.' ), $wordCount );
			$score    = 80 + (int) round( ( ( $wordCount - self::GOOD_WORDS ) / ( self::EXCELLENT_WORDS - self::GOOD_WORDS ) ) * 15 );
			$rating   = 'good';
		} else {
			$passed[] = sprintf( __( 'Excellent content length: %d words.' ), $wordCount );
			$score    = 95 + min( 5, (int) round( ( $wordCount - self::EXCELLENT_WORDS ) / 500 ) );
			$rating   = 'excellent';
		}

		return [
			'score'       => min( 100, $score ),
			'issues'      => $issues,
			'suggestions' => $suggestions,
			'passed'      => $passed,
			'details'     => [
				'word_count'      => $wordCount,
				'min_words'       => self::MIN_WORDS,
				'good_words'      => self::GOOD_WORDS,
				'excellent_words' => self::EXCELLENT_WORDS,
				'rating'          => $rating,
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
		return 'content_length';
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
}
