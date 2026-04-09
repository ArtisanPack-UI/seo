<?php

/**
 * AnalysisService Integration Tests.
 *
 * Tests that the AnalysisService is properly registered with all built-in analyzers
 * and produces meaningful scores when resolved from the container.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\AnalysisService;

describe( 'AnalysisService container registration', function (): void {

	it( 'is registered as a singleton', function (): void {
		$service1 = app( AnalysisService::class );
		$service2 = app( AnalysisService::class );

		expect( $service1 )->toBe( $service2 );
	} );

	it( 'has all eight built-in analyzers registered', function (): void {
		$service   = app( AnalysisService::class );
		$analyzers = $service->getAnalyzers();

		expect( $analyzers )->toHaveCount( 8 )
			->and( $service->hasAnalyzer( 'content_length' ) )->toBeTrue()
			->and( $service->hasAnalyzer( 'focus_keyword' ) )->toBeTrue()
			->and( $service->hasAnalyzer( 'heading_structure' ) )->toBeTrue()
			->and( $service->hasAnalyzer( 'image_alt' ) )->toBeTrue()
			->and( $service->hasAnalyzer( 'internal_links' ) )->toBeTrue()
			->and( $service->hasAnalyzer( 'keyword_density' ) )->toBeTrue()
			->and( $service->hasAnalyzer( 'meta_length' ) )->toBeTrue()
			->and( $service->hasAnalyzer( 'readability' ) )->toBeTrue();
	} );

	it( 'has analyzers in all four categories', function (): void {
		$service = app( AnalysisService::class );

		expect( $service->getAnalyzersByCategory( 'readability' ) )->not->toBeEmpty()
			->and( $service->getAnalyzersByCategory( 'keyword' ) )->not->toBeEmpty()
			->and( $service->getAnalyzersByCategory( 'meta' ) )->not->toBeEmpty()
			->and( $service->getAnalyzersByCategory( 'content' ) )->not->toBeEmpty();
	} );

} );
