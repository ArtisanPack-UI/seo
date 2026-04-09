<?php

/**
 * SchemaApiController.
 *
 * API controller for managing Schema.org configuration.
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

use ArtisanPackUI\SEO\Http\Requests\Api\UpdateSchemaRequest;
use ArtisanPackUI\SEO\Http\Resources\SchemaResource;
use ArtisanPackUI\SEO\Schema\SchemaFactory;
use ArtisanPackUI\SEO\Services\SchemaService;
use ArtisanPackUI\SEO\Services\SeoService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Throwable;

/**
 * SchemaApiController class.
 *
 * Handles API requests for Schema.org type listing and configuration.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class SchemaApiController extends Controller
{
	/**
	 * Create a new SchemaApiController instance.
	 *
	 * @since 1.1.0
	 *
	 * @param  SchemaService   $schemaService  The schema service.
	 * @param  SchemaFactory   $schemaFactory  The schema factory.
	 * @param  SeoService      $seoService     The SEO service.
	 */
	public function __construct(
		protected SchemaService $schemaService,
		protected SchemaFactory $schemaFactory,
		protected SeoService $seoService,
	) {
	}

	/**
	 * List available schema types with field definitions.
	 *
	 * Returns full type metadata including descriptions and field
	 * definitions for dynamic form rendering in frontend editors.
	 *
	 * @since 1.1.0
	 *
	 * @return JsonResponse
	 */
	public function types(): JsonResponse
	{
		$typeDefinitions = $this->schemaFactory->getTypeDefinitions();

		return response()->json( [
			'data' => $typeDefinitions,
		] );
	}

	/**
	 * Get schema configuration for a model.
	 *
	 * @since 1.1.0
	 *
	 * @param  string  $modelType  The model morph type.
	 * @param  int     $modelId    The model ID.
	 *
	 * @return JsonResponse
	 */
	public function show( string $modelType, int $modelId ): JsonResponse|SchemaResource
	{
		$model = $this->resolveModel( $modelType, $modelId );

		if ( null === $model ) {
			return response()->json( [
				'message' => __( 'Model not found.' ),
			], 404 );
		}

		$seoMeta = $this->seoService->getSeoMeta( $model );

		$generated = null;
		try {
			$generated = $this->schemaService->generate( $model, $seoMeta );
		} catch ( Throwable ) {
			// Schema generation may fail if type cannot be resolved
		}

		return new SchemaResource(
			$seoMeta,
			$generated,
			$this->schemaFactory->getSupportedTypes(),
		);
	}

	/**
	 * Update schema configuration for a model.
	 *
	 * @since 1.1.0
	 *
	 * @param  UpdateSchemaRequest  $request    The validated request.
	 * @param  string               $modelType  The model morph type.
	 * @param  int                  $modelId    The model ID.
	 *
	 * @return JsonResponse
	 */
	public function update( UpdateSchemaRequest $request, string $modelType, int $modelId ): JsonResponse|SchemaResource
	{
		$model = $this->resolveModel( $modelType, $modelId );

		if ( null === $model ) {
			return response()->json( [
				'message' => __( 'Model not found.' ),
			], 404 );
		}

		$seoMeta = $this->seoService->updateSeoMeta( $model, $request->validated() );

		$generated = null;
		try {
			$generated = $this->schemaService->generate( $model, $seoMeta );
		} catch ( Throwable ) {
			// Schema generation may fail if type cannot be resolved
		}

		return new SchemaResource(
			$seoMeta,
			$generated,
			$this->schemaFactory->getSupportedTypes(),
		);
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
