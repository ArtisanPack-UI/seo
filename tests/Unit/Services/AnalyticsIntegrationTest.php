<?php

/**
 * AnalyticsIntegration Tests.
 *
 * Unit tests for the AnalyticsIntegration service.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\AnalyticsIntegration;
use ArtisanPackUI\SEO\Support\PackageDetector;

beforeEach( function (): void {
	$this->integration = new AnalyticsIntegration();
} );

describe( 'AnalyticsIntegration Availability', function (): void {

	it( 'checks if analytics package is available', function (): void {
		$result = $this->integration->isAvailable();

		// The result depends on whether the package is installed
		expect( $result )->toBeBool();
	} );

	it( 'returns same result as PackageDetector', function (): void {
		$integrationResult = $this->integration->isAvailable();
		$detectorResult    = PackageDetector::hasAnalytics();

		expect( $integrationResult )->toBe( $detectorResult );
	} );

} );

describe( 'AnalyticsIntegration getSearchConsoleData', function (): void {

	it( 'returns null when analytics not available', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( AnalyticsIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		$result = $integration->getSearchConsoleData( 'https://example.com' );

		expect( $result )->toBeNull();
	} );

	it( 'accepts period parameter', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( AnalyticsIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		$result = $integration->getSearchConsoleData( 'https://example.com', '7d' );

		expect( $result )->toBeNull();
	} );

} );

describe( 'AnalyticsIntegration getTopQueries', function (): void {

	it( 'returns empty collection when analytics not available', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( AnalyticsIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		$result = $integration->getTopQueries( 'https://example.com' );

		expect( $result )->toBeEmpty();
	} );

	it( 'accepts limit parameter', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( AnalyticsIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		$result = $integration->getTopQueries( 'https://example.com', 5 );

		expect( $result )->toBeEmpty();
	} );

} );

describe( 'AnalyticsIntegration getSeoPerformanceSummary', function (): void {

	it( 'returns empty summary when analytics not available', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( AnalyticsIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		$result = $integration->getSeoPerformanceSummary();

		expect( $result )->toBeArray()
			->and( $result['clicks'] )->toBe( 0 )
			->and( $result['impressions'] )->toBe( 0 )
			->and( $result['avgPosition'] )->toBe( 0.0 )
			->and( $result['avgCtr'] )->toBe( 0.0 )
			->and( $result['topPages'] )->toBe( [] )
			->and( $result['topQueries'] )->toBe( [] );
	} );

	it( 'returns proper structure', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( AnalyticsIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		$result = $integration->getSeoPerformanceSummary( '30d' );

		expect( $result )->toHaveKeys( [
			'clicks',
			'impressions',
			'avgPosition',
			'avgCtr',
			'topPages',
			'topQueries',
		] );
	} );

} );

describe( 'AnalyticsIntegration getPeriodOptions', function (): void {

	it( 'returns array of period options', function (): void {
		$result = $this->integration->getPeriodOptions();

		expect( $result )->toBeArray()
			->and( count( $result ) )->toBe( 3 );
	} );

	it( 'includes 7d, 30d, and 90d options', function (): void {
		$result = $this->integration->getPeriodOptions();
		$values = array_column( $result, 'value' );

		expect( $values )->toContain( '7d' )
			->and( $values )->toContain( '30d' )
			->and( $values )->toContain( '90d' );
	} );

	it( 'has translated labels', function (): void {
		$result = $this->integration->getPeriodOptions();

		foreach ( $result as $option ) {
			expect( $option )->toHaveKey( 'value' )
				->and( $option )->toHaveKey( 'label' );
		}
	} );

} );
