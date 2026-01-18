<?php
/**
 * ImageAltAnalyzer.
 *
 * Analyzes image alt text presence and quality.
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
 * ImageAltAnalyzer class.
 *
 * Checks for image alt text presence and quality:
 * - Presence of alt attributes
 * - Non-empty alt text
 * - Descriptive alt text (not just filenames)
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class ImageAltAnalyzer implements AnalyzerContract
{
	/**
	 * Minimum recommended alt text length.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const MIN_ALT_LENGTH = 5;

	/**
	 * Maximum recommended alt text length.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const MAX_ALT_LENGTH = 125;

	/**
	 * Analyze image alt text in content.
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

		// Extract all images
		preg_match_all( '/<img[^>]*>/i', $content, $imgMatches );
		$images     = $imgMatches[0] ?? [];
		$imageCount = count( $images );

		// No images found - not an issue, just skip
		if ( 0 === $imageCount ) {
			return [
				'score'       => 100,
				'issues'      => [],
				'suggestions' => [],
				'passed'      => [ __( 'No images to analyze.' ) ],
				'details'     => [
					'image_count'            => 0,
					'images_with_alt'        => 0,
					'images_with_empty_alt'  => 0,
					'images_without_alt'     => 0,
				],
			];
		}

		$withAlt       = 0;
		$withEmptyAlt  = 0;
		$withoutAlt    = 0;
		$goodAltCount  = 0;
		$tooShortAlt   = 0;
		$tooLongAlt    = 0;
		$filenameAlts  = 0;

		foreach ( $images as $img ) {
			// Check if alt attribute exists
			if ( ! preg_match( '/alt\s*=/i', $img ) ) {
				$withoutAlt++;
				continue;
			}

			// Extract alt text
			preg_match( '/alt\s*=\s*["\']([^"\']*)["\']/', $img, $altMatch );
			$altText = $altMatch[1] ?? '';

			if ( '' === trim( $altText ) ) {
				$withEmptyAlt++;
				continue;
			}

			$withAlt++;
			$altLength = strlen( $altText );

			// Check alt text quality
			if ( $altLength < self::MIN_ALT_LENGTH ) {
				$tooShortAlt++;
			} elseif ( $altLength > self::MAX_ALT_LENGTH ) {
				$tooLongAlt++;
			} else {
				$goodAltCount++;
			}

			// Check if alt text looks like a filename
			if ( preg_match( '/\.(jpg|jpeg|png|gif|webp|svg|bmp)$/i', $altText ) ) {
				$filenameAlts++;
			}
		}

		// Calculate score
		$score = 100;

		// Penalize missing alt attributes
		if ( $withoutAlt > 0 ) {
			$issues[] = [
				'type'    => 'error',
				'message' => sprintf(
					__( '%d image(s) are missing alt attributes.' ),
					$withoutAlt,
				),
			];
			$score -= min( 40, $withoutAlt * 10 );
		}

		// Penalize empty alt attributes (when they should have text)
		if ( $withEmptyAlt > 0 ) {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => sprintf(
					__( '%d image(s) have empty alt text. Add descriptive alt text for better accessibility and SEO.' ),
					$withEmptyAlt,
				),
			];
			$score -= min( 20, $withEmptyAlt * 5 );
		}

		// Penalize filename-like alt text
		if ( $filenameAlts > 0 ) {
			$issues[] = [
				'type'    => 'warning',
				'message' => sprintf(
					__( '%d image(s) have alt text that looks like a filename. Use descriptive text instead.' ),
					$filenameAlts,
				),
			];
			$score -= min( 15, $filenameAlts * 5 );
		}

		// Penalize very short alt text
		if ( $tooShortAlt > 0 ) {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => sprintf(
					__( '%d image(s) have very short alt text. Consider being more descriptive.' ),
					$tooShortAlt,
				),
			];
			$score -= min( 10, $tooShortAlt * 3 );
		}

		// Penalize very long alt text
		if ( $tooLongAlt > 0 ) {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => sprintf(
					__( '%d image(s) have very long alt text. Keep it under %d characters.' ),
					$tooLongAlt,
					self::MAX_ALT_LENGTH,
				),
			];
			$score -= min( 5, $tooLongAlt * 2 );
		}

		// Good alt text usage
		if ( $goodAltCount > 0 && 0 === $withoutAlt && 0 === $withEmptyAlt ) {
			$passed[] = sprintf( __( 'All %d image(s) have proper alt text.' ), $goodAltCount );
		} elseif ( $goodAltCount > 0 ) {
			$passed[] = sprintf( __( '%d image(s) have good alt text.' ), $goodAltCount );
		}

		return [
			'score'       => max( 0, $score ),
			'issues'      => $issues,
			'suggestions' => $suggestions,
			'passed'      => $passed,
			'details'     => [
				'image_count'            => $imageCount,
				'images_with_alt'        => $withAlt,
				'images_with_empty_alt'  => $withEmptyAlt,
				'images_without_alt'     => $withoutAlt,
				'good_alt_count'         => $goodAltCount,
				'too_short_alt'          => $tooShortAlt,
				'too_long_alt'           => $tooLongAlt,
				'filename_alts'          => $filenameAlts,
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
		return 'image_alt';
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
