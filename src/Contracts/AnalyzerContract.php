<?php

/**
 * AnalyzerContract.
 *
 * Interface for SEO content analyzers.
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

namespace ArtisanPackUI\SEO\Contracts;

use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

/**
 * AnalyzerContract interface.
 *
 * Implement this interface to create custom SEO analyzers
 * that can be registered with the AnalysisService.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
interface AnalyzerContract
{
	/**
	 * Analyze the given model and content.
	 *
	 * Returns an array with the following keys:
	 * - score: int (0-100) The analyzer's score
	 * - issues: array Array of issue messages
	 * - suggestions: array Array of suggestion messages
	 * - passed: array Array of passed check messages
	 * - details: array (optional) Additional analysis details
	 *
	 * Each issue/suggestion should be an array with:
	 * - type: string ('error', 'warning', or 'suggestion')
	 * - message: string The human-readable message
	 *
	 * @since 1.0.0
	 *
	 * @param  Model        $model         The model being analyzed.
	 * @param  string       $content       The content to analyze (HTML).
	 * @param  string|null  $focusKeyword  The focus keyword for SEO.
	 * @param  SeoMeta|null $seoMeta       The SEO meta data if available.
	 *
	 * @return array{
	 *     score: int,
	 *     issues: array<int, array{type: string, message: string}>,
	 *     suggestions: array<int, array{type: string, message: string}>,
	 *     passed: array<int, string>,
	 *     details?: array<string, mixed>
	 * }
	 */
	public function analyze( Model $model, string $content, ?string $focusKeyword, ?SeoMeta $seoMeta ): array;

	/**
	 * Get the unique name identifier for this analyzer.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Get the category this analyzer belongs to.
	 *
	 * Valid categories: 'readability', 'keyword', 'meta', 'content'
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getCategory(): string;

	/**
	 * Get the weight of this analyzer within its category.
	 *
	 * Higher weights give this analyzer more influence on the
	 * category score when multiple analyzers share a category.
	 *
	 * @since 1.0.0
	 *
	 * @return int Value between 1-100.
	 */
	public function getWeight(): int;
}
