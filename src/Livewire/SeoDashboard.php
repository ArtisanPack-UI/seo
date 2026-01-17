<?php

/**
 * SeoDashboard Livewire Component.
 *
 * Displays SEO performance data from Google Search Console.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Livewire;

use ArtisanPackUI\SEO\Services\AnalyticsIntegration;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * SeoDashboard component for displaying SEO performance metrics.
 *
 * Shows Search Console data including clicks, impressions, average position,
 * CTR, top pages, and top queries with period selection.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SeoDashboard extends Component
{
	/**
	 * The selected time period.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $period = '30d';

	/**
	 * The performance data array.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, mixed>
	 */
	public array $performance = [];

	/**
	 * Whether the analytics package is available.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	public bool $analyticsAvailable = false;

	/**
	 * Allowed period values for validation.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	protected array $allowedPeriods = [ '7d', '30d', '90d' ];

	/**
	 * Initialize the component.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function mount(): void
	{
		$integration              = $this->getAnalyticsIntegration();
		$this->analyticsAvailable = $integration->isAvailable();

		if ( $this->analyticsAvailable ) {
			$this->loadPerformance();
		}
	}

	/**
	 * Load the performance data from Search Console.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function loadPerformance(): void
	{
		if ( ! $this->analyticsAvailable ) {
			return;
		}

		$integration       = $this->getAnalyticsIntegration();
		$this->performance = $integration->getSeoPerformanceSummary( $this->period );
	}

	/**
	 * Handle period updates.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function updatedPeriod(): void
	{
		// Validate period against allowed values
		if ( ! in_array( $this->period, $this->allowedPeriods, true ) ) {
			$this->period = '30d';
		}

		$this->loadPerformance();
	}

	/**
	 * Get the period options for the select input.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	#[Computed]
	public function periodOptions(): array
	{
		return $this->getAnalyticsIntegration()->getPeriodOptions();
	}

	/**
	 * Get the total clicks.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function clicks(): int
	{
		return (int) ( $this->performance['clicks'] ?? 0 );
	}

	/**
	 * Get the total impressions.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function impressions(): int
	{
		return (int) ( $this->performance['impressions'] ?? 0 );
	}

	/**
	 * Get the average position.
	 *
	 * @since 1.0.0
	 *
	 * @return float
	 */
	#[Computed]
	public function avgPosition(): float
	{
		return round( (float) ( $this->performance['avgPosition'] ?? 0.0 ), 1 );
	}

	/**
	 * Get the average CTR as a percentage.
	 *
	 * @since 1.0.0
	 *
	 * @return float
	 */
	#[Computed]
	public function avgCtr(): float
	{
		return round( (float) ( $this->performance['avgCtr'] ?? 0.0 ), 2 );
	}

	/**
	 * Get the top pages.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	#[Computed]
	public function topPages(): array
	{
		return $this->performance['topPages'] ?? [];
	}

	/**
	 * Get the top queries.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	#[Computed]
	public function topQueries(): array
	{
		return $this->performance['topQueries'] ?? [];
	}

	/**
	 * Check if there are top pages to display.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function hasTopPages(): bool
	{
		return count( $this->topPages ) > 0;
	}

	/**
	 * Check if there are top queries to display.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function hasTopQueries(): bool
	{
		return count( $this->topQueries ) > 0;
	}

	/**
	 * Get the table headers for top pages.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{key: string, label: string}>
	 */
	#[Computed]
	public function pageHeaders(): array
	{
		return [
			[ 'key' => 'url', 'label' => __( 'Page' ) ],
			[ 'key' => 'clicks', 'label' => __( 'Clicks' ) ],
			[ 'key' => 'impressions', 'label' => __( 'Impressions' ) ],
			[ 'key' => 'position', 'label' => __( 'Position' ) ],
		];
	}

	/**
	 * Get the table headers for top queries.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{key: string, label: string}>
	 */
	#[Computed]
	public function queryHeaders(): array
	{
		return [
			[ 'key' => 'query', 'label' => __( 'Query' ) ],
			[ 'key' => 'clicks', 'label' => __( 'Clicks' ) ],
			[ 'key' => 'impressions', 'label' => __( 'Impressions' ) ],
			[ 'key' => 'ctr', 'label' => __( 'CTR' ) ],
		];
	}

	/**
	 * Format a number for display.
	 *
	 * @since 1.0.0
	 *
	 * @param  float|int $number The number to format.
	 *
	 * @return string The formatted number.
	 */
	public function formatNumber( int|float $number ): string
	{
		if ( $number >= 1000000 ) {
			return number_format( $number / 1000000, 1 ) . 'M';
		}

		if ( $number >= 1000 ) {
			return number_format( $number / 1000, 1 ) . 'K';
		}

		return number_format( $number );
	}

	/**
	 * Render the component.
	 *
	 * @since 1.0.0
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'seo::livewire.seo-dashboard' );
	}

	/**
	 * Get the analytics integration service.
	 *
	 * @since 1.0.0
	 *
	 * @return AnalyticsIntegration
	 */
	protected function getAnalyticsIntegration(): AnalyticsIntegration
	{
		return app( AnalyticsIntegration::class );
	}
}
