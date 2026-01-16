<?php

/**
 * InternalLinkAnalyzer.
 *
 * Analyzes internal linking in content.
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
 * InternalLinkAnalyzer class.
 *
 * Evaluates internal linking practices:
 * - Presence of internal links
 * - Link count relative to content length
 * - External vs internal link balance
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class InternalLinkAnalyzer implements AnalyzerContract
{
	/**
	 * Minimum recommended internal links.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const MIN_INTERNAL_LINKS = 1;

	/**
	 * Ideal internal links per 500 words.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const IDEAL_LINKS_PER_500_WORDS = 2;

	/**
	 * Analyze internal links in content.
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

		// Get the site's base URL
		$siteUrl  = config( 'app.url', '' );
		$siteHost = parse_url( $siteUrl, PHP_URL_HOST ) ?? '';

		// Extract all links
		preg_match_all( '/<a[^>]+href\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $content, $linkMatches );
		$links = $linkMatches[1] ?? [];

		$internalLinks = [];
		$externalLinks = [];

		foreach ( $links as $link ) {
			// Skip anchors and javascript
			if ( str_starts_with( $link, '#' ) || str_starts_with( $link, 'javascript:' ) ) {
				continue;
			}

			// Skip mailto and tel links
			if ( str_starts_with( $link, 'mailto:' ) || str_starts_with( $link, 'tel:' ) ) {
				continue;
			}

			// Check if internal or external
			if ( $this->isInternalLink( $link, $siteHost ) ) {
				$internalLinks[] = $link;
			} else {
				$externalLinks[] = $link;
			}
		}

		$internalCount = count( $internalLinks );
		$externalCount = count( $externalLinks );
		$totalLinks    = $internalCount + $externalCount;
		$wordCount     = str_word_count( strip_tags( $content ) );

		// Calculate ideal number of internal links based on word count
		$idealInternalLinks = max( self::MIN_INTERNAL_LINKS, (int) round( ( $wordCount / 500 ) * self::IDEAL_LINKS_PER_500_WORDS ) );

		// Calculate score
		$score = 100;

		// Check for internal links
		if ( 0 === $internalCount ) {
			$issues[] = [
				'type'    => 'warning',
				'message' => __( 'No internal links found. Add links to other pages on your site.' ),
			];
			$score -= 30;
		} elseif ( $internalCount < $idealInternalLinks ) {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => sprintf(
					__( 'Only %d internal link(s) found. Consider adding more (recommended: %d+ for this content length).' ),
					$internalCount,
					$idealInternalLinks,
				),
			];
			$score -= 15;
		} else {
			$passed[] = sprintf( __( 'Good internal linking: %d link(s) to other pages.' ), $internalCount );
		}

		// Check for external links (outbound links can be good for SEO)
		if ( $externalCount > 0 ) {
			$passed[] = sprintf( __( 'Content includes %d external link(s) to relevant resources.' ), $externalCount );
		} elseif ( $wordCount > 500 ) {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => __( 'Consider adding external links to authoritative sources for better credibility.' ),
			];
			// Small penalty for no external links on longer content
			$score -= 5;
		}

		// Check link anchor text quality
		$emptyAnchors = $this->countEmptyAnchors( $content );
		if ( $emptyAnchors > 0 ) {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => sprintf(
					__( '%d link(s) have non-descriptive anchor text. Use meaningful text for links.' ),
					$emptyAnchors,
				),
			];
			$score -= min( 10, $emptyAnchors * 3 );
		}

		return [
			'score'       => max( 0, $score ),
			'issues'      => $issues,
			'suggestions' => $suggestions,
			'passed'      => $passed,
			'details'     => [
				'internal_link_count'    => $internalCount,
				'external_link_count'    => $externalCount,
				'total_link_count'       => $totalLinks,
				'ideal_internal_links'   => $idealInternalLinks,
				'word_count'             => $wordCount,
				'empty_anchor_count'     => $emptyAnchors,
				'internal_links'         => array_slice( $internalLinks, 0, 10 ),
				'external_links'         => array_slice( $externalLinks, 0, 10 ),
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
		return 'internal_links';
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
	 * Determine if a link is internal.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $link      The link URL.
	 * @param  string  $siteHost  The site's hostname.
	 *
	 * @return bool
	 */
	protected function isInternalLink( string $link, string $siteHost ): bool
	{
		// Relative links are internal
		if ( str_starts_with( $link, '/' ) && ! str_starts_with( $link, '//' ) ) {
			return true;
		}

		// Parse the link
		$linkHost = parse_url( $link, PHP_URL_HOST );

		// If no host or same host, it's internal
		if ( null === $linkHost || '' === $linkHost ) {
			return true;
		}

		// Compare hosts (handle www prefix)
		$linkHost = preg_replace( '/^www\./i', '', $linkHost );
		$siteHost = preg_replace( '/^www\./i', '', $siteHost );

		return strtolower( $linkHost ?? '' ) === strtolower( $siteHost ?? '' );
	}

	/**
	 * Count links with empty or generic anchor text.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $content  The HTML content.
	 *
	 * @return int
	 */
	protected function countEmptyAnchors( string $content ): int
	{
		$genericAnchors = [
			'click here',
			'read more',
			'learn more',
			'here',
			'link',
			'this',
			'more',
		];

		$count = 0;

		// Extract anchor text
		preg_match_all( '/<a[^>]*>(.*?)<\/a>/si', $content, $matches );
		$anchors = $matches[1] ?? [];

		foreach ( $anchors as $anchor ) {
			$text = strtolower( trim( strip_tags( $anchor ) ) );

			if ( '' === $text || in_array( $text, $genericAnchors, true ) ) {
				$count++;
			}
		}

		return $count;
	}
}
