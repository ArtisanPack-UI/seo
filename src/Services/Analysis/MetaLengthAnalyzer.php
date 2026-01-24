<?php

/**
 * MetaLengthAnalyzer.
 *
 * Analyzes meta title and description lengths.
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
 * MetaLengthAnalyzer class.
 *
 * Checks optimal lengths for meta title (30-60 chars)
 * and meta description (120-160 chars).
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class MetaLengthAnalyzer implements AnalyzerContract
{
	/**
	 * Minimum recommended title length.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const TITLE_MIN = 30;

	/**
	 * Maximum recommended title length.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const TITLE_MAX = 60;

	/**
	 * Ideal title length.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const TITLE_IDEAL = 55;

	/**
	 * Minimum recommended description length.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const DESC_MIN = 120;

	/**
	 * Maximum recommended description length.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const DESC_MAX = 160;

	/**
	 * Ideal description length.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const DESC_IDEAL = 155;

	/**
	 * Analyze meta title and description lengths.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model        $model          The model being analyzed.
	 * @param  string       $_content       The content to analyze (unused).
	 * @param  string|null  $_focusKeyword  The focus keyword (unused).
	 * @param  SeoMeta|null $seoMeta        The SEO meta data.
	 *
	 * @return array{
	 *     score: int,
	 *     issues: array<int, array{type: string, message: string}>,
	 *     suggestions: array<int, array{type: string, message: string}>,
	 *     passed: array<int, string>,
	 *     details: array<string, mixed>
	 * }
	 */
	public function analyze( Model $model, string $_content, ?string $_focusKeyword, ?SeoMeta $seoMeta ): array
	{
		$issues      = [];
		$suggestions = [];
		$passed      = [];
		$score       = 100;

		// Analyze meta title using effective title method for proper fallback
		$metaTitle   = null !== $seoMeta ? $seoMeta->getEffectiveTitle() : ( $model->title ?? '' );
		$titleLength = mb_strlen( $metaTitle, 'UTF-8' );

		if ( '' === $metaTitle ) {
			$issues[] = [
				'type'    => 'error',
				'message' => __( 'Meta title is missing.' ),
			];
			$score -= 25;
		} elseif ( $titleLength < self::TITLE_MIN ) {
			$issues[] = [
				'type'    => 'warning',
				'message' => sprintf(
					__( 'Meta title is too short (%d characters). Aim for %d-%d characters.' ),
					$titleLength,
					self::TITLE_MIN,
					self::TITLE_MAX,
				),
			];
			$score -= 15;
		} elseif ( $titleLength > self::TITLE_MAX ) {
			$issues[] = [
				'type'    => 'warning',
				'message' => sprintf(
					__( 'Meta title is too long (%d characters). It may be truncated in search results.' ),
					$titleLength,
				),
			];
			$score -= 10;
		} else {
			$passed[] = sprintf( __( 'Meta title length is good (%d characters).' ), $titleLength );
		}

		// Analyze meta description using effective description method for proper fallback
		$metaDescription = null !== $seoMeta ? ( $seoMeta->getEffectiveDescription() ?? '' ) : '';
		$descLength      = mb_strlen( $metaDescription, 'UTF-8' );

		if ( '' === $metaDescription ) {
			$issues[] = [
				'type'    => 'warning',
				'message' => __( 'Meta description is missing.' ),
			];
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => __( 'Add a meta description to improve click-through rates from search results.' ),
			];
			$score -= 25;
		} elseif ( $descLength < self::DESC_MIN ) {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => sprintf(
					__( 'Meta description is short (%d characters). Aim for %d-%d characters.' ),
					$descLength,
					self::DESC_MIN,
					self::DESC_MAX,
				),
			];
			$score -= 10;
		} elseif ( $descLength > self::DESC_MAX ) {
			$issues[] = [
				'type'    => 'warning',
				'message' => sprintf(
					__( 'Meta description is too long (%d characters). It may be truncated.' ),
					$descLength,
				),
			];
			$score -= 10;
		} else {
			$passed[] = sprintf( __( 'Meta description length is good (%d characters).' ), $descLength );
		}

		return [
			'score'       => max( 0, $score ),
			'issues'      => $issues,
			'suggestions' => $suggestions,
			'passed'      => $passed,
			'details'     => [
				'title_length'       => $titleLength,
				'title_min'          => self::TITLE_MIN,
				'title_max'          => self::TITLE_MAX,
				'title_ideal'        => self::TITLE_IDEAL,
				'description_length' => $descLength,
				'description_min'    => self::DESC_MIN,
				'description_max'    => self::DESC_MAX,
				'description_ideal'  => self::DESC_IDEAL,
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
		return 'meta_length';
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
		return 'meta';
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
		return 100;
	}
}
