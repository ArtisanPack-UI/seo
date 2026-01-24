<?php

/**
 * SeoDashboard Livewire Component Tests.
 *
 * Feature tests for the SeoDashboard Livewire component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Livewire\SeoDashboard;
use ArtisanPackUI\SEO\Services\AnalyticsIntegration;
use Illuminate\View\View;
use Livewire\Livewire;

/**
 * Test version of SeoDashboard that uses a simplified view for testing.
 */
class TestSeoDashboard extends SeoDashboard
{
	/**
	 * Render the component with a simplified test view.
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'test-seo-dashboard' );
	}
}

beforeEach( function (): void {
	// Register the test view location
	$this->app['view']->addLocation( __DIR__ . '/../../stubs/views/livewire' );
} );

describe( 'SeoDashboard Component Mounting', function (): void {

	it( 'mounts with default period', function (): void {
		Livewire::test( TestSeoDashboard::class )
			->assertSet( 'period', '30d' );
	} );

	it( 'mounts with analytics availability status', function (): void {
		Livewire::test( TestSeoDashboard::class )
			->assertSeeHtml( 'data-test="analytics-available"' );
	} );

	it( 'loads performance data on mount', function (): void {
		Livewire::test( TestSeoDashboard::class )
			->assertSeeHtml( 'data-test="clicks"' )
			->assertSeeHtml( 'data-test="impressions"' );
	} );

} );

describe( 'SeoDashboard Period Selection', function (): void {

	it( 'updates data when period changes', function (): void {
		Livewire::test( TestSeoDashboard::class )
			->assertSet( 'period', '30d' )
			->set( 'period', '7d' )
			->assertSet( 'period', '7d' );
	} );

	it( 'has valid period options', function (): void {
		Livewire::test( TestSeoDashboard::class )
			->assertSeeHtml( 'data-value="7d"' )
			->assertSeeHtml( 'data-value="30d"' )
			->assertSeeHtml( 'data-value="90d"' );
	} );

} );

describe( 'SeoDashboard Performance Data', function (): void {

	it( 'displays clicks', function (): void {
		Livewire::test( TestSeoDashboard::class )
			->assertSeeHtml( 'data-test="clicks"' );
	} );

	it( 'displays impressions', function (): void {
		Livewire::test( TestSeoDashboard::class )
			->assertSeeHtml( 'data-test="impressions"' );
	} );

	it( 'displays average position', function (): void {
		Livewire::test( TestSeoDashboard::class )
			->assertSeeHtml( 'data-test="avg-position"' );
	} );

	it( 'displays average CTR', function (): void {
		Livewire::test( TestSeoDashboard::class )
			->assertSeeHtml( 'data-test="avg-ctr"' );
	} );

} );

describe( 'SeoDashboard Computed Properties', function (): void {

	it( 'computes clicks from performance', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );

		// Should return int type
		expect( $component->instance()->clicks )->toBeInt();
	} );

	it( 'computes impressions from performance', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );

		// Should return int type
		expect( $component->instance()->impressions )->toBeInt();
	} );

	it( 'computes average position from performance', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );

		// Should return float type
		expect( $component->instance()->avgPosition )->toBeFloat();
	} );

	it( 'computes average CTR from performance', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );

		// Should return float type
		expect( $component->instance()->avgCtr )->toBeFloat();
	} );

} );

describe( 'SeoDashboard Table Headers', function (): void {

	it( 'has page headers', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );
		$headers   = $component->instance()->pageHeaders;

		expect( $headers )->toBeArray()
			->and( count( $headers ) )->toBe( 4 );
	} );

	it( 'has query headers', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );
		$headers   = $component->instance()->queryHeaders;

		expect( $headers )->toBeArray()
			->and( count( $headers ) )->toBe( 4 );
	} );

	it( 'page headers have correct structure', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );
		$headers   = $component->instance()->pageHeaders;

		foreach ( $headers as $header ) {
			expect( $header )->toHaveKey( 'key' )
				->and( $header )->toHaveKey( 'label' );
		}
	} );

	it( 'query headers have correct structure', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );
		$headers   = $component->instance()->queryHeaders;

		foreach ( $headers as $header ) {
			expect( $header )->toHaveKey( 'key' )
				->and( $header )->toHaveKey( 'label' );
		}
	} );

} );

describe( 'SeoDashboard Number Formatting', function (): void {

	it( 'formats numbers under 1000', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );

		expect( $component->instance()->formatNumber( 500 ) )->toBe( '500' );
	} );

	it( 'formats numbers over 1000 with K suffix', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );

		expect( $component->instance()->formatNumber( 1500 ) )->toBe( '1.5K' );
	} );

	it( 'formats numbers over 1 million with M suffix', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );

		expect( $component->instance()->formatNumber( 1500000 ) )->toBe( '1.5M' );
	} );

	it( 'formats exact thousand', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );

		expect( $component->instance()->formatNumber( 1000 ) )->toBe( '1.0K' );
	} );

	it( 'formats exact million', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );

		expect( $component->instance()->formatNumber( 1000000 ) )->toBe( '1.0M' );
	} );

} );

describe( 'SeoDashboard Top Pages', function (): void {

	it( 'has hasTopPages computed property', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );

		expect( $component->instance()->hasTopPages )->toBeBool();
	} );

	it( 'returns empty array when no top pages', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );

		expect( $component->instance()->topPages )->toBeArray();
	} );

} );

describe( 'SeoDashboard Top Queries', function (): void {

	it( 'has hasTopQueries computed property', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );

		expect( $component->instance()->hasTopQueries )->toBeBool();
	} );

	it( 'returns empty array when no top queries', function (): void {
		$component = Livewire::test( TestSeoDashboard::class );

		expect( $component->instance()->topQueries )->toBeArray();
	} );

} );

describe( 'SeoDashboard Analytics Not Available', function (): void {

	it( 'handles missing analytics package gracefully', function (): void {
		// Mock the AnalyticsIntegration to return not available
		$mockIntegration = Mockery::mock( AnalyticsIntegration::class );
		$mockIntegration->shouldReceive( 'isAvailable' )->andReturn( false );
		$mockIntegration->shouldReceive( 'getSeoPerformanceSummary' )->andReturn( [
			'clicks'      => 0,
			'impressions' => 0,
			'avgPosition' => 0.0,
			'avgCtr'      => 0.0,
			'topPages'    => [],
			'topQueries'  => [],
		] );
		$mockIntegration->shouldReceive( 'getPeriodOptions' )->andReturn( [
			[ 'value' => '7d', 'label' => '7 Days' ],
			[ 'value' => '30d', 'label' => '30 Days' ],
			[ 'value' => '90d', 'label' => '90 Days' ],
		] );

		$this->app->instance( AnalyticsIntegration::class, $mockIntegration );

		Livewire::test( TestSeoDashboard::class )
			->assertSeeHtml( 'data-test="analytics-available">false' );
	} );

	it( 'shows zero values when analytics not available', function (): void {
		// Mock the AnalyticsIntegration to return not available
		$mockIntegration = Mockery::mock( AnalyticsIntegration::class );
		$mockIntegration->shouldReceive( 'isAvailable' )->andReturn( false );
		$mockIntegration->shouldReceive( 'getSeoPerformanceSummary' )->andReturn( [
			'clicks'      => 0,
			'impressions' => 0,
			'avgPosition' => 0.0,
			'avgCtr'      => 0.0,
			'topPages'    => [],
			'topQueries'  => [],
		] );
		$mockIntegration->shouldReceive( 'getPeriodOptions' )->andReturn( [
			[ 'value' => '7d', 'label' => '7 Days' ],
			[ 'value' => '30d', 'label' => '30 Days' ],
			[ 'value' => '90d', 'label' => '90 Days' ],
		] );

		$this->app->instance( AnalyticsIntegration::class, $mockIntegration );

		Livewire::test( TestSeoDashboard::class )
			->assertSeeHtml( 'data-test="clicks">0' )
			->assertSeeHtml( 'data-test="impressions">0' );
	} );

} );

describe( 'SeoDashboard With Mock Analytics Data', function (): void {

	it( 'displays data from analytics service', function (): void {
		$mockIntegration = Mockery::mock( AnalyticsIntegration::class );
		$mockIntegration->shouldReceive( 'isAvailable' )->andReturn( true );
		$mockIntegration->shouldReceive( 'getSeoPerformanceSummary' )->andReturn( [
			'clicks'      => 1500,
			'impressions' => 25000,
			'avgPosition' => 8.5,
			'avgCtr'      => 6.0,
			'topPages'    => [
				[ 'url' => '/page-1', 'clicks' => 500, 'impressions' => 8000, 'position' => 5.2 ],
				[ 'url' => '/page-2', 'clicks' => 300, 'impressions' => 5000, 'position' => 7.1 ],
			],
			'topQueries'  => [
				[ 'query' => 'test query', 'clicks' => 400, 'impressions' => 6000, 'ctr' => 0.067 ],
				[ 'query' => 'another query', 'clicks' => 250, 'impressions' => 4000, 'ctr' => 0.063 ],
			],
		] );
		$mockIntegration->shouldReceive( 'getPeriodOptions' )->andReturn( [
			[ 'value' => '7d', 'label' => '7 Days' ],
			[ 'value' => '30d', 'label' => '30 Days' ],
			[ 'value' => '90d', 'label' => '90 Days' ],
		] );

		$this->app->instance( AnalyticsIntegration::class, $mockIntegration );

		Livewire::test( TestSeoDashboard::class )
			->assertSeeHtml( 'data-test="analytics-available">true' )
			->assertSeeHtml( 'data-test="clicks">1500' )
			->assertSeeHtml( 'data-test="impressions">25000' )
			->assertSeeHtml( 'data-test="has-top-pages">true' )
			->assertSeeHtml( 'data-test="has-top-queries">true' )
			->assertSeeHtml( 'data-test="top-pages-count">2' )
			->assertSeeHtml( 'data-test="top-queries-count">2' );
	} );

} );
