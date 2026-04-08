<?php

/**
 * VisualEditorIntegration.
 *
 * Service class for integrating with the optional artisanpack-ui/visual-editor package.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\DTOs\AnalysisResultDTO;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Support\PackageDetector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use function addFilter;

/**
 * VisualEditorIntegration class.
 *
 * Provides pre-publish SEO checks for the visual editor workflow.
 * Helps content creators ensure SEO best practices before publishing.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class VisualEditorIntegration
{
	/**
	 * Minimum SEO score threshold.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const MIN_SEO_SCORE = 50;

	/**
	 * Check type constants.
	 *
	 * @since 1.0.0
	 */
	protected const TYPE_WARNING    = 'warning';
	protected const TYPE_SUGGESTION = 'suggestion';
	protected const TYPE_INFO       = 'info';

	/**
	 * The analysis service instance.
	 *
	 * @since 1.0.0
	 *
	 * @var AnalysisService
	 */
	protected AnalysisService $analysisService;

	/**
	 * Create a new VisualEditorIntegration instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  AnalysisService $analysisService The analysis service.
	 */
	public function __construct( AnalysisService $analysisService )
	{
		$this->analysisService = $analysisService;
	}

	/**
	 * Check if the visual editor package is available.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if visual editor is installed.
	 */
	public function isAvailable(): bool
	{
		return PackageDetector::hasVisualEditor();
	}

	/**
	 * Register pre-publish checks with the visual editor.
	 *
	 * This method registers a filter hook that adds SEO checks
	 * to the visual editor's pre-publish workflow.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function registerPrePublishChecks(): void
	{
		if ( ! $this->isAvailable() ) {
			return;
		}

		if ( ! PackageDetector::hasHooks() ) {
			return;
		}

		addFilter( 'visual_editor.pre_publish_checks', function ( Collection $checks, Model $page ) {
			return $checks->merge( $this->getSeoChecks( $page ) );
		} );
	}

	/**
	 * Get SEO checks for a model.
	 *
	 * Returns a collection of SEO-related checks that should be
	 * addressed before publishing the content.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model                   $page           The model to check.
	 * @param  AnalysisResultDTO|null  $analysisResult Optional pre-computed analysis result.
	 *
	 * @return Collection<int, array<string, string>> Collection of checks.
	 */
	public function getSeoChecks( Model $page, ?AnalysisResultDTO $analysisResult = null ): Collection
	{
		$checks  = collect();
		$seoMeta = $this->getSeoMeta( $page );

		// Check for missing meta title
		if ( $this->isMissingMetaTitle( $seoMeta ) ) {
			$checks->push( $this->createCheck(
				self::TYPE_WARNING,
				__( 'Page is missing a meta title' ),
				__( 'Add a meta title for better search visibility' ),
			) );
		}

		// Check for missing meta description
		if ( $this->isMissingMetaDescription( $seoMeta ) ) {
			$checks->push( $this->createCheck(
				self::TYPE_WARNING,
				__( 'Page is missing a meta description' ),
				__( 'Add a meta description to improve click-through rates from search results' ),
			) );
		}

		// Check for missing focus keyword
		if ( $this->isMissingFocusKeyword( $seoMeta ) ) {
			$checks->push( $this->createCheck(
				self::TYPE_SUGGESTION,
				__( 'Page is missing a focus keyword' ),
				__( 'Set a focus keyword to help optimize your content for search engines' ),
			) );
		}

		// Check for low SEO score - use provided result if available to avoid re-analysis
		$seoScore = null !== $analysisResult ? $analysisResult->overallScore : $this->getSeoScore( $page );
		if ( null !== $seoScore && $seoScore < self::MIN_SEO_SCORE ) {
			$checks->push( $this->createCheck(
				self::TYPE_WARNING,
				__( 'SEO score is below 50' ),
				__( 'Review the SEO analysis panel and address the identified issues to improve your score' ),
			) );
		}

		// Check for missing OG image
		if ( $this->isMissingOgImage( $seoMeta ) ) {
			$checks->push( $this->createCheck(
				self::TYPE_SUGGESTION,
				__( 'Page is missing a social sharing image' ),
				__( 'Add an Open Graph image for better social media previews' ),
			) );
		}

		// Info notice when noindex is enabled
		if ( $this->hasNoIndex( $seoMeta ) ) {
			$checks->push( $this->createCheck(
				self::TYPE_INFO,
				__( 'This page is set to noindex' ),
				__( 'Search engines will not index this page. Remove the noindex setting if you want it to appear in search results' ),
			) );
		}

		return $checks;
	}

	/**
	 * Run full SEO analysis for the editor.
	 *
	 * Returns comprehensive analysis data suitable for display
	 * in the visual editor interface.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model $page The model to analyze.
	 *
	 * @return array<string, mixed> The analysis data.
	 */
	public function analyzeForEditor( Model $page ): array
	{
		$result = $this->analysisService->analyze( $page, null, false );

		return [
			'overall_score'     => $result->overallScore,
			'readability_score' => $result->readabilityScore,
			'keyword_score'     => $result->keywordScore,
			'meta_score'        => $result->metaScore,
			'content_score'     => $result->contentScore,
			'issues'            => $result->issues,
			'suggestions'       => $result->suggestions,
			'passed_checks'     => $result->passedChecks,
			'focus_keyword'     => $result->focusKeyword,
			'word_count'        => $result->wordCount,
			'checks'            => $this->getSeoChecks( $page, $result )->toArray(),
		];
	}

	/**
	 * Get the SEO meta for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model $page The model.
	 *
	 * @return SeoMeta|null
	 */
	protected function getSeoMeta( Model $page ): ?SeoMeta
	{
		if ( method_exists( $page, 'seoMeta' ) ) {
			return $page->seoMeta;
		}

		return null;
	}

	/**
	 * Get the SEO score for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model $page The model.
	 *
	 * @return int|null The SEO score or null if not available.
	 */
	protected function getSeoScore( Model $page ): ?int
	{
		$seoMeta = $this->getSeoMeta( $page );

		if ( null === $seoMeta ) {
			return null;
		}

		$cache = $seoMeta->analysisCache;

		if ( null === $cache ) {
			// Run analysis to get score
			$result = $this->analysisService->analyze( $page, null, false );

			return $result->overallScore;
		}

		return $cache->overall_score;
	}

	/**
	 * Check if the meta title is missing.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta|null $seoMeta The SEO meta.
	 *
	 * @return bool
	 */
	protected function isMissingMetaTitle( ?SeoMeta $seoMeta ): bool
	{
		if ( null === $seoMeta ) {
			return true;
		}

		return null === $seoMeta->meta_title || '' === $seoMeta->meta_title;
	}

	/**
	 * Check if the meta description is missing.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta|null $seoMeta The SEO meta.
	 *
	 * @return bool
	 */
	protected function isMissingMetaDescription( ?SeoMeta $seoMeta ): bool
	{
		if ( null === $seoMeta ) {
			return true;
		}

		return null === $seoMeta->meta_description || '' === $seoMeta->meta_description;
	}

	/**
	 * Check if the focus keyword is missing.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta|null $seoMeta The SEO meta.
	 *
	 * @return bool
	 */
	protected function isMissingFocusKeyword( ?SeoMeta $seoMeta ): bool
	{
		if ( null === $seoMeta ) {
			return true;
		}

		return null === $seoMeta->focus_keyword || '' === $seoMeta->focus_keyword;
	}

	/**
	 * Check if the OG image is missing.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta|null $seoMeta The SEO meta.
	 *
	 * @return bool
	 */
	protected function isMissingOgImage( ?SeoMeta $seoMeta ): bool
	{
		if ( null === $seoMeta ) {
			return true;
		}

		$hasOgImage   = null !== $seoMeta->og_image && '' !== $seoMeta->og_image;
		$hasOgImageId = null !== $seoMeta->og_image_id;

		return ! $hasOgImage && ! $hasOgImageId;
	}

	/**
	 * Check if noindex is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta|null $seoMeta The SEO meta.
	 *
	 * @return bool
	 */
	protected function hasNoIndex( ?SeoMeta $seoMeta ): bool
	{
		if ( null === $seoMeta ) {
			return false;
		}

		return true === $seoMeta->no_index;
	}

	/**
	 * Create a check array structure.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $type    The check type (warning, suggestion, info).
	 * @param  string $message The check message.
	 * @param  string $action  The recommended action.
	 *
	 * @return array<string, string>
	 */
	protected function createCheck( string $type, string $message, string $action ): array
	{
		return [
			'type'     => $type,
			'category' => 'seo',
			'message'  => $message,
			'action'   => $action,
		];
	}
}
