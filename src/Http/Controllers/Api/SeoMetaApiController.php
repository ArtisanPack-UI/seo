<?php

/**
 * SeoMetaApiController.
 *
 * API controller for managing SEO meta data.
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

use ArtisanPackUI\SEO\Http\Requests\Api\UpdateSeoMetaRequest;
use ArtisanPackUI\SEO\Http\Resources\SeoMetaResource;
use ArtisanPackUI\SEO\Services\SeoService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * SeoMetaApiController class.
 *
 * Handles API requests for SEO meta data CRUD and preview.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class SeoMetaApiController extends Controller
{
	/**
	 * Create a new SeoMetaApiController instance.
	 *
	 * @since 1.1.0
	 *
	 * @param  SeoService  $seoService  The SEO service.
	 */
	public function __construct(
		protected SeoService $seoService,
	) {
	}

	/**
	 * Get all SEO data for a model.
	 *
	 * @since 1.1.0
	 *
	 * @param  string  $modelType  The model morph type.
	 * @param  int     $modelId    The model ID.
	 *
	 * @return JsonResponse|SeoMetaResource
	 */
	public function show( string $modelType, int $modelId ): JsonResponse|SeoMetaResource
	{
		$model = $this->resolveModel( $modelType, $modelId );

		if ( null === $model ) {
			return response()->json( [
				'message' => __( 'Model not found.' ),
			], 404 );
		}

		$seoMeta = $this->seoService->getSeoMeta( $model );

		if ( null === $seoMeta ) {
			return response()->json( [
				'data' => null,
			] );
		}

		return new SeoMetaResource( $seoMeta );
	}

	/**
	 * Update SEO meta data for a model.
	 *
	 * @since 1.1.0
	 *
	 * @param  UpdateSeoMetaRequest  $request    The validated request.
	 * @param  string                $modelType  The model morph type.
	 * @param  int                   $modelId    The model ID.
	 *
	 * @return JsonResponse|SeoMetaResource
	 */
	public function update( UpdateSeoMetaRequest $request, string $modelType, int $modelId ): JsonResponse|SeoMetaResource
	{
		$model = $this->resolveModel( $modelType, $modelId );

		if ( null === $model ) {
			return response()->json( [
				'message' => __( 'Model not found.' ),
			], 404 );
		}

		$seoMeta = $this->seoService->updateSeoMeta( $model, $request->validated() );

		return new SeoMetaResource( $seoMeta );
	}

	/**
	 * Get formatted meta tag preview for a model.
	 *
	 * @since 1.1.0
	 *
	 * @param  string  $modelType  The model morph type.
	 * @param  int     $modelId    The model ID.
	 *
	 * @return JsonResponse
	 */
	public function preview( string $modelType, int $modelId ): JsonResponse
	{
		$model = $this->resolveModel( $modelType, $modelId );

		if ( null === $model ) {
			return response()->json( [
				'message' => __( 'Model not found.' ),
			], 404 );
		}

		$allData = $this->seoService->getAll( $model );

		return response()->json( [
			'data' => $allData,
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
