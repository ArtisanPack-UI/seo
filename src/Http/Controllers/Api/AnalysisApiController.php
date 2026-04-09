<?php

/**
 * AnalysisApiController.
 *
 * API controller for SEO content analysis.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Http\Controllers\Api;

use ArtisanPackUI\SEO\Http\Requests\Api\AnalyzeContentRequest;
use ArtisanPackUI\SEO\Models\SeoAnalysisCache;
use ArtisanPackUI\SEO\Services\AnalysisService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * AnalysisApiController class.
 *
 * Handles API requests for SEO content analysis.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class AnalysisApiController extends Controller
{
	/**
	 * Create a new AnalysisApiController instance.
	 *
	 * @since 1.1.0
	 *
	 * @param  AnalysisService  $analysisService  The analysis service.
	 */
	public function __construct(
		protected AnalysisService $analysisService,
	) {
	}

	/**
	 * Run SEO analysis on a model.
	 *
	 * @since 1.1.0
	 *
	 * @param  AnalyzeContentRequest  $request  The validated request.
	 *
	 * @return JsonResponse
	 */
	public function analyze( AnalyzeContentRequest $request ): JsonResponse
	{
		$model = $this->resolveModel(
			$request->validated( 'model_type' ),
			(int) $request->validated( 'model_id' ),
		);

		if ( null === $model ) {
			return response()->json( [
				'message' => __( 'Model not found.' ),
			], 404 );
		}

		$result = $this->analysisService->analyze(
			$model,
			$request->validated( 'focus_keyword' ),
			false,
		);

		return response()->json( [
			'data' => $result->toArray(),
		] );
	}

	/**
	 * Get cached analysis results for a model.
	 *
	 * @since 1.1.0
	 *
	 * @param  string  $modelType  The model morph type.
	 * @param  int     $modelId    The model ID.
	 *
	 * @return JsonResponse
	 */
	public function show( string $modelType, int $modelId ): JsonResponse
	{
		$model = $this->resolveModel( $modelType, $modelId );

		if ( null === $model ) {
			return response()->json( [
				'message' => __( 'Model not found.' ),
			], 404 );
		}

		$seoMeta = null;
		if ( method_exists( $model, 'seoMeta' ) ) {
			$seoMeta = $model->seoMeta;
		}

		if ( null === $seoMeta ) {
			return response()->json( [
				'data' => null,
			] );
		}

		$cache = SeoAnalysisCache::where( 'seo_meta_id', $seoMeta->id )->first();

		if ( null === $cache ) {
			return response()->json( [
				'data' => null,
			] );
		}

		return response()->json( [
			'data' => [
				'overall_score'     => $cache->overall_score,
				'readability_score' => $cache->readability_score,
				'keyword_score'     => $cache->keyword_score,
				'meta_score'        => $cache->meta_score,
				'content_score'     => $cache->content_score,
				'grade'             => $cache->getGrade(),
				'issues'            => $cache->issues,
				'suggestions'       => $cache->suggestions,
				'passed_checks'     => $cache->passed_checks,
				'focus_keyword'     => $cache->focus_keyword_used,
				'word_count'        => $cache->content_word_count,
				'analyzer_results'  => $cache->analyzer_results,
				'analyzed_at'       => $cache->analyzed_at?->toIso8601String(),
				'is_stale'          => $cache->isStale(),
			],
		] );
	}

	/**
	 * Resolve the model from type and ID.
	 *
	 * @since 1.1.0
	 *
	 * @param  string  $modelType  The model morph type or class name.
	 * @param  int     $modelId    The model ID.
	 *
	 * @return \Illuminate\Database\Eloquent\Model|null
	 */
	protected function resolveModel( string $modelType, int $modelId ): ?\Illuminate\Database\Eloquent\Model
	{
		$modelClass = Relation::getMorphedModel( $modelType );

		if ( null === $modelClass || ! class_exists( $modelClass ) || ! is_subclass_of( $modelClass, \Illuminate\Database\Eloquent\Model::class ) ) {
			return null;
		}

		return $modelClass::find( $modelId );
	}
}
