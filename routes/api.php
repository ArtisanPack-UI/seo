<?php

/**
 * SEO Package API Routes.
 *
 * API routes for SEO analysis and management.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Http\Controllers\Api\AnalysisApiController;
use ArtisanPackUI\SEO\Http\Controllers\Api\RedirectApiController;
use ArtisanPackUI\SEO\Http\Controllers\Api\SchemaApiController;
use ArtisanPackUI\SEO\Http\Controllers\Api\SeoMetaApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO API Routes
|--------------------------------------------------------------------------
|
| These routes provide API access to SEO functionality.
| All routes are prefixed with the configured API prefix and use
| the configured middleware.
|
*/

if ( true === config( 'seo.api.enabled', true ) ) {
	$prefix     = config( 'seo.api.prefix', 'api/seo' );
	$middleware = config( 'seo.api.middleware', [ 'api', 'auth:sanctum' ] );

	Route::prefix( $prefix )
		->middleware( $middleware )
		->group( function (): void {

			// Analysis endpoints
			Route::prefix( 'analysis' )->group( function (): void {
				Route::post( 'analyze', [ AnalysisApiController::class, 'analyze' ] )
					->name( 'seo.api.analysis.analyze' );

				Route::get( '{modelType}/{modelId}', [ AnalysisApiController::class, 'show' ] )
					->where( 'modelId', '[0-9]+' )
					->name( 'seo.api.analysis.get' );
			} );

			// Meta endpoints
			Route::prefix( 'meta' )->group( function (): void {
				Route::get( '{modelType}/{modelId}', [ SeoMetaApiController::class, 'show' ] )
					->where( 'modelId', '[0-9]+' )
					->name( 'seo.api.meta.get' );

				Route::put( '{modelType}/{modelId}', [ SeoMetaApiController::class, 'update' ] )
					->where( 'modelId', '[0-9]+' )
					->name( 'seo.api.meta.update' );

				Route::get( '{modelType}/{modelId}/preview', [ SeoMetaApiController::class, 'preview' ] )
					->where( 'modelId', '[0-9]+' )
					->name( 'seo.api.meta.preview' );
			} );

			// Redirect endpoints
			Route::prefix( 'redirects' )->group( function (): void {
				Route::get( '/', [ RedirectApiController::class, 'index' ] )
					->name( 'seo.api.redirects.index' );

				Route::post( '/', [ RedirectApiController::class, 'store' ] )
					->name( 'seo.api.redirects.store' );

				Route::post( 'bulk', [ RedirectApiController::class, 'bulk' ] )
					->name( 'seo.api.redirects.bulk' );

				Route::post( 'test', [ RedirectApiController::class, 'test' ] )
					->name( 'seo.api.redirects.test' );

				Route::get( '{redirect}', [ RedirectApiController::class, 'show' ] )
					->name( 'seo.api.redirects.show' );

				Route::put( '{redirect}', [ RedirectApiController::class, 'update' ] )
					->name( 'seo.api.redirects.update' );

				Route::delete( '{redirect}', [ RedirectApiController::class, 'destroy' ] )
					->name( 'seo.api.redirects.destroy' );
			} );

			// Schema endpoints
			Route::prefix( 'schema' )->group( function (): void {
				Route::get( 'types', [ SchemaApiController::class, 'types' ] )
					->name( 'seo.api.schema.types' );

				Route::get( '{modelType}/{modelId}', [ SchemaApiController::class, 'show' ] )
					->where( 'modelId', '[0-9]+' )
					->name( 'seo.api.schema.get' );

				Route::put( '{modelType}/{modelId}', [ SchemaApiController::class, 'update' ] )
					->where( 'modelId', '[0-9]+' )
					->name( 'seo.api.schema.update' );
			} );
		} );
}
