<?php

/**
 * AnalyticsIntegration.
 *
 * Service class for integrating with the optional artisanpack-ui/analytics package.
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

use ArtisanPackUI\SEO\Support\PackageDetector;
use Illuminate\Support\Collection;

/**
 * AnalyticsIntegration class.
 *
 * Provides methods to interact with the analytics package
 * for Google Search Console data. Gracefully handles missing package.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class AnalyticsIntegration
{
	/**
	 * Valid period options.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, string>
	 */
	public const PERIOD_OPTIONS = [
		'7d'  => '7 Days',
		'30d' => '30 Days',
		'90d' => '90 Days',
	];

	/**
	 * Check if the analytics package is available.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if analytics is installed.
	 */
	public function isAvailable(): bool
	{
		return PackageDetector::hasAnalytics();
	}

	/**
	 * Get Search Console data for a specific URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url    The URL to get data for.
	 * @param  string $period The time period (7d, 30d, 90d).
	 *
	 * @return array<string, mixed>|null The Search Console data or null if unavailable.
	 */
	public function getSearchConsoleData( string $url, string $period = '30d' ): ?array
	{
		if ( ! $this->hasSearchConsoleAccess() ) {
			return null;
		}

		$searchConsole = $this->getSearchConsoleService();

		if ( null === $searchConsole ) {
			return null;
		}

		$days = $this->periodToDays( $period );

		return [
			'clicks'      => $this->safeCall( $searchConsole, 'getClicksForUrl', [ $url, $days ], 0 ),
			'impressions' => $this->safeCall( $searchConsole, 'getImpressionsForUrl', [ $url, $days ], 0 ),
			'avgPosition' => $this->safeCall( $searchConsole, 'getAveragePositionForUrl', [ $url, $days ], 0.0 ),
			'avgCtr'      => $this->safeCall( $searchConsole, 'getAverageCtrForUrl', [ $url, $days ], 0.0 ),
			'queries'     => $this->safeCall( $searchConsole, 'getQueriesForUrl', [ $url, $days ], [] ),
		];
	}

	/**
	 * Get top queries for a specific URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url   The URL to get queries for.
	 * @param  int    $limit The maximum number of queries to return.
	 *
	 * @return Collection<int, array<string, mixed>> Collection of top queries.
	 */
	public function getTopQueries( string $url, int $limit = 10 ): Collection
	{
		if ( ! $this->hasSearchConsoleAccess() ) {
			return collect();
		}

		$searchConsole = $this->getSearchConsoleService();

		if ( null === $searchConsole ) {
			return collect();
		}

		$result = $this->safeCall( $searchConsole, 'getTopQueriesForUrl', [ $url, $limit ], [] );

		return collect( $result );
	}

	/**
	 * Get SEO performance summary for the dashboard.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $period The time period (7d, 30d, 90d).
	 *
	 * @return array<string, mixed> The performance summary data.
	 */
	public function getSeoPerformanceSummary( string $period = '30d' ): array
	{
		if ( ! $this->hasSearchConsoleAccess() ) {
			return $this->getEmptyPerformanceSummary();
		}

		$searchConsole = $this->getSearchConsoleService();

		if ( null === $searchConsole ) {
			return $this->getEmptyPerformanceSummary();
		}

		$days = $this->periodToDays( $period );

		return [
			'clicks'      => $this->safeCall( $searchConsole, 'getTotalClicks', [ $days ], 0 ),
			'impressions' => $this->safeCall( $searchConsole, 'getTotalImpressions', [ $days ], 0 ),
			'avgPosition' => $this->safeCall( $searchConsole, 'getAveragePosition', [ $days ], 0.0 ),
			'avgCtr'      => $this->safeCall( $searchConsole, 'getAverageCtr', [ $days ], 0.0 ),
			'topPages'    => $this->safeCall( $searchConsole, 'getTopPages', [ $days, 5 ], [] ),
			'topQueries'  => $this->safeCall( $searchConsole, 'getTopQueries', [ $days, 5 ], [] ),
		];
	}

	/**
	 * Get the available period options for selection.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{value: string, label: string}> The period options.
	 */
	public function getPeriodOptions(): array
	{
		return [
			[ 'value' => '7d', 'label' => __( '7 Days' ) ],
			[ 'value' => '30d', 'label' => __( '30 Days' ) ],
			[ 'value' => '90d', 'label' => __( '90 Days' ) ],
		];
	}

	/**
	 * Check if Search Console access is available.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if Search Console is configured and accessible.
	 */
	protected function hasSearchConsoleAccess(): bool
	{
		if ( ! $this->isAvailable() ) {
			return false;
		}

		$searchConsole = $this->getSearchConsoleService();

		if ( null === $searchConsole ) {
			return false;
		}

		// Check if the service has a method to verify configuration
		if ( method_exists( $searchConsole, 'isConfigured' ) ) {
			return $searchConsole->isConfigured();
		}

		return true;
	}

	/**
	 * Get the Search Console service instance.
	 *
	 * @since 1.0.0
	 *
	 * @return object|null The Search Console service or null.
	 */
	protected function getSearchConsoleService(): ?object
	{
		$serviceClass = \ArtisanPackUI\Analytics\Services\SearchConsoleService::class;

		if ( ! class_exists( $serviceClass ) ) {
			return null;
		}

		return app( $serviceClass );
	}

	/**
	 * Convert a period string to days.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $period The period string (7d, 30d, 90d).
	 *
	 * @return int The number of days.
	 */
	protected function periodToDays( string $period ): int
	{
		return match ( $period ) {
			'7d'    => 7,
			'30d'   => 30,
			'90d'   => 90,
			default => 30,
		};
	}

	/**
	 * Get an empty performance summary structure.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The empty summary structure.
	 */
	protected function getEmptyPerformanceSummary(): array
	{
		return [
			'clicks'      => 0,
			'impressions' => 0,
			'avgPosition' => 0.0,
			'avgCtr'      => 0.0,
			'topPages'    => [],
			'topQueries'  => [],
		];
	}

	/**
	 * Safely call a method on an object with fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param  object               $object   The object to call the method on.
	 * @param  string               $method   The method name.
	 * @param  array<int, mixed>    $args     The method arguments.
	 * @param  mixed                $fallback The fallback value if method doesn't exist.
	 *
	 * @return mixed The method result or fallback.
	 */
	protected function safeCall( object $object, string $method, array $args, mixed $fallback ): mixed
	{
		if ( method_exists( $object, $method ) ) {
			return $object->{$method}( ...$args );
		}

		return $fallback;
	}
}
