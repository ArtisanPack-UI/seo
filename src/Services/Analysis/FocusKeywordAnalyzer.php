<?php

/**
 * FocusKeywordAnalyzer.
 *
 * Analyzes focus keyword placement in key locations.
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
use Illuminate\Support\Str;

/**
 * FocusKeywordAnalyzer class.
 *
 * Checks focus keyword presence in critical SEO locations:
 * meta title, meta description, URL, H1, subheadings, and image alt text.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class FocusKeywordAnalyzer implements AnalyzerContract
{
	/**
	 * Analyze focus keyword placement.
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
		// Check if focus keyword is set
		if ( null === $focusKeyword || '' === trim( $focusKeyword ) ) {
			return [
				'score'       => 0,
				'issues'      => [],
				'suggestions' => [ [ 'type' => 'suggestion', 'message' => __( 'Set a focus keyword for better SEO analysis.' ) ] ],
				'passed'      => [],
				'details'     => [],
			];
		}

		$keyword      = strtolower( trim( $focusKeyword ) );
		$issues       = [];
		$suggestions  = [];
		$passed       = [];
		$checksTotal  = 0;
		$checksPassed = 0;
		$placements   = [];

		// Check in meta title
		$checksTotal++;
		$metaTitle            = strtolower( $seoMeta?->meta_title ?? $model->title ?? '' );
		$placements['title']  = str_contains( $metaTitle, $keyword );

		if ( $placements['title'] ) {
			$passed[] = __( 'Focus keyword appears in the meta title.' );
			$checksPassed++;
		} else {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => __( 'Include your focus keyword in the meta title.' ),
			];
		}

		// Check in meta description
		$checksTotal++;
		$metaDescription            = strtolower( $seoMeta?->meta_description ?? '' );
		$placements['description']  = str_contains( $metaDescription, $keyword );

		if ( $placements['description'] ) {
			$passed[] = __( 'Focus keyword appears in the meta description.' );
			$checksPassed++;
		} else {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => __( 'Include your focus keyword in the meta description.' ),
			];
		}

		// Check in URL/slug
		$checksTotal++;
		$slug               = strtolower( $model->slug ?? '' );
		$keywordSlug        = Str::slug( $keyword );
		$placements['url']  = str_contains( $slug, $keywordSlug );

		if ( $placements['url'] ) {
			$passed[] = __( 'Focus keyword appears in the URL.' );
			$checksPassed++;
		} else {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => __( 'Consider including your focus keyword in the URL slug.' ),
			];
		}

		// Check in H1
		$checksTotal++;
		preg_match( '/<h1[^>]*>(.*?)<\/h1>/si', $content, $h1Match );
		$h1Content         = strtolower( strip_tags( $h1Match[1] ?? $model->title ?? '' ) );
		$placements['h1']  = str_contains( $h1Content, $keyword );

		if ( $placements['h1'] ) {
			$passed[] = __( 'Focus keyword appears in H1 heading.' );
			$checksPassed++;
		} else {
			$issues[] = [
				'type'    => 'warning',
				'message' => __( 'Focus keyword not found in H1 heading.' ),
			];
		}

		// Check in subheadings (H2-H6)
		$checksTotal++;
		preg_match_all( '/<h[2-6][^>]*>(.*?)<\/h[2-6]>/si', $content, $subheadings );
		$subheadingText           = strtolower( implode( ' ', $subheadings[1] ?? [] ) );
		$placements['subheading'] = str_contains( $subheadingText, $keyword );

		if ( $placements['subheading'] ) {
			$passed[] = __( 'Focus keyword appears in subheadings.' );
			$checksPassed++;
		} else {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => __( 'Use your focus keyword in at least one subheading.' ),
			];
		}

		// Check in image alt text
		$checksTotal++;
		preg_match_all( '/alt=["\']([^"\']*)["\']/', $content, $altTexts );
		$allAltText             = strtolower( implode( ' ', $altTexts[1] ?? [] ) );
		$placements['alt_text'] = str_contains( $allAltText, $keyword );

		if ( $placements['alt_text'] ) {
			$passed[] = __( 'Focus keyword appears in image alt text.' );
			$checksPassed++;
		} else {
			// Only suggest if there are images
			if ( count( $altTexts[1] ?? [] ) > 0 ) {
				$suggestions[] = [
					'type'    => 'suggestion',
					'message' => __( 'Include your focus keyword in at least one image alt text.' ),
				];
			}
		}

		// Calculate score
		$score = $checksTotal > 0 ? (int) round( ( $checksPassed / $checksTotal ) * 100 ) : 0;

		return [
			'score'       => $score,
			'issues'      => $issues,
			'suggestions' => $suggestions,
			'passed'      => $passed,
			'details'     => [
				'keyword'        => $focusKeyword,
				'checks_total'   => $checksTotal,
				'checks_passed'  => $checksPassed,
				'placements'     => $placements,
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
		return 'focus_keyword';
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
}
