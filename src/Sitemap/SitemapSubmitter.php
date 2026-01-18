<?php
/**
 * SitemapSubmitter.
 *
 * Submits sitemaps to search engines.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 * @copyright  2026 Jacob Martella
 * @license    MIT
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Sitemap;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SitemapSubmitter class.
 *
 * Pings search engines to notify them of sitemap updates.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SitemapSubmitter
{
	/**
	 * Default search engine ping URLs.
	 *
	 * Note: Google's ping URL (https://www.google.com/ping?sitemap=...) was deprecated
	 * and is no longer functional as of 2023. For Google, submit your sitemap via:
	 * - Google Search Console (https://search.google.com/search-console)
	 * - Adding the sitemap URL to your robots.txt file: Sitemap: https://example.com/sitemap.xml
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, string>
	 */
	protected const DEFAULT_SEARCH_ENGINES = [
		'bing' => 'https://www.bing.com/ping?sitemap=%s',
	];

	/**
	 * The sitemap URL to submit.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $sitemapUrl;

	/**
	 * Search engines to ping.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, string>
	 */
	protected array $searchEngines;

	/**
	 * Submission results.
	 *
	 * @since 1.0.0
	 *
	 * @var Collection<string, array<string, mixed>>
	 */
	protected Collection $results;

	/**
	 * HTTP timeout in seconds.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected int $timeout;

	/**
	 * Create a new SitemapSubmitter instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null              $sitemapUrl     The sitemap URL to submit.
	 * @param  array<string, string>|null  $searchEngines  Custom search engines to ping.
	 * @param  int|null                 $timeout        HTTP timeout in seconds.
	 */
	public function __construct(
		?string $sitemapUrl = null,
		?array $searchEngines = null,
		?int $timeout = null,
	) {
		$baseUrl             = config( 'app.url', '' );
		$sitemapPath         = config( 'seo.sitemap.route_path', 'sitemap.xml' );
		$this->sitemapUrl    = $sitemapUrl ?? rtrim( $baseUrl, '/' ) . '/' . ltrim( $sitemapPath, '/' );
		$engines             = $searchEngines ?? $this->getConfiguredSearchEngines();
		$this->searchEngines = array_change_key_case( $engines, CASE_LOWER );
		$this->timeout       = $timeout ?? (int) config( 'seo.sitemap.submit_timeout', 10 );
		$this->results       = collect();
	}

	/**
	 * Submit the sitemap to all configured search engines.
	 *
	 * @since 1.0.0
	 *
	 * @return Collection<string, array<string, mixed>> Results keyed by search engine name.
	 */
	public function submit(): Collection
	{
		$this->results = collect();

		foreach ( $this->searchEngines as $name => $pingUrl ) {
			$this->results->put( $name, $this->pingSearchEngine( $name, $pingUrl ) );
		}

		return $this->results;
	}

	/**
	 * Submit the sitemap to a specific search engine.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $name  The search engine name.
	 *
	 * @return array<string, mixed>|null The result or null if engine not found.
	 */
	public function submitTo( string $name ): ?array
	{
		$name = strtolower( $name );

		if ( ! isset( $this->searchEngines[ $name ] ) ) {
			return null;
		}

		$result = $this->pingSearchEngine( $name, $this->searchEngines[ $name ] );
		$this->results->put( $name, $result );

		return $result;
	}

	/**
	 * Get the submission results.
	 *
	 * @since 1.0.0
	 *
	 * @return Collection<string, array<string, mixed>>
	 */
	public function getResults(): Collection
	{
		return $this->results;
	}

	/**
	 * Check if all submissions were successful.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function allSuccessful(): bool
	{
		if ( $this->results->isEmpty() ) {
			return false;
		}

		return $this->results->every( fn ( $result ) => true === $result['success'] );
	}

	/**
	 * Check if any submission was successful.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function anySuccessful(): bool
	{
		return $this->results->contains( fn ( $result ) => true === $result['success'] );
	}

	/**
	 * Get failed submissions.
	 *
	 * @since 1.0.0
	 *
	 * @return Collection<string, array<string, mixed>>
	 */
	public function getFailedSubmissions(): Collection
	{
		return $this->results->filter( fn ( $result ) => false === $result['success'] );
	}

	/**
	 * Get successful submissions.
	 *
	 * @since 1.0.0
	 *
	 * @return Collection<string, array<string, mixed>>
	 */
	public function getSuccessfulSubmissions(): Collection
	{
		return $this->results->filter( fn ( $result ) => true === $result['success'] );
	}

	/**
	 * Set the sitemap URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $url  The sitemap URL.
	 *
	 * @return self
	 */
	public function setSitemapUrl( string $url ): self
	{
		$this->sitemapUrl = $url;

		return $this;
	}

	/**
	 * Get the sitemap URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getSitemapUrl(): string
	{
		return $this->sitemapUrl;
	}

	/**
	 * Add a custom search engine.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $name     The search engine name.
	 * @param  string  $pingUrl  The ping URL template (use %s for sitemap URL placeholder).
	 *
	 * @return self
	 */
	public function addSearchEngine( string $name, string $pingUrl ): self
	{
		$this->searchEngines[ strtolower( $name ) ] = $pingUrl;

		return $this;
	}

	/**
	 * Remove a search engine.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $name  The search engine name.
	 *
	 * @return self
	 */
	public function removeSearchEngine( string $name ): self
	{
		unset( $this->searchEngines[ strtolower( $name ) ] );

		return $this;
	}

	/**
	 * Get configured search engines.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public function getSearchEngines(): array
	{
		return $this->searchEngines;
	}

	/**
	 * Ping a search engine with the sitemap URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $name     The search engine name.
	 * @param  string  $pingUrl  The ping URL template.
	 *
	 * @return array<string, mixed>
	 */
	protected function pingSearchEngine( string $name, string $pingUrl ): array
	{
		$url       = sprintf( $pingUrl, urlencode( $this->sitemapUrl ) );
		$startTime = microtime( true );

		try {
			$response = Http::timeout( $this->timeout )
				->withUserAgent( 'ArtisanPackUI SEO Sitemap Submitter' )
				->get( $url );

			$result = [
				'success'       => $response->successful(),
				'status_code'   => $response->status(),
				'response_time' => round( ( microtime( true ) - $startTime ) * 1000, 2 ),
				'url'           => $url,
				'message'       => $response->successful()
					? __( 'Sitemap successfully submitted to :engine', [ 'engine' => $name ] )
					: __( 'Failed to submit sitemap to :engine', [ 'engine' => $name ] ),
			];

			$this->logResult( $name, $result, $response );

			return $result;
		} catch ( Exception $e ) {
			$result = [
				'success'       => false,
				'status_code'   => null,
				'response_time' => round( ( microtime( true ) - $startTime ) * 1000, 2 ),
				'url'           => $url,
				'message'       => __( 'Error submitting sitemap to :engine: :error', [
					'engine' => $name,
					'error'  => $e->getMessage(),
				] ),
				'exception' => $e->getMessage(),
			];

			$this->logResult( $name, $result );

			return $result;
		}
	}

	/**
	 * Log the submission result.
	 *
	 * @since 1.0.0
	 *
	 * @param  string                  $name      The search engine name.
	 * @param  array<string, mixed>    $result    The submission result.
	 * @param  Response|null           $response  The HTTP response if available.
	 *
	 * @return void
	 */
	protected function logResult( string $name, array $result, ?Response $response = null ): void
	{
		$context = [
			'search_engine' => $name,
			'sitemap_url'   => $this->sitemapUrl,
			'ping_url'      => $result['url'],
			'status_code'   => $result['status_code'],
			'response_time' => $result['response_time'],
		];

		if ( true === $result['success'] ) {
			Log::info( "Sitemap submitted to {$name}", $context );
		} else {
			$context['error'] = $result['exception'] ?? ( $response?->body() ?? 'Unknown error' );
			Log::warning( "Failed to submit sitemap to {$name}", $context );
		}
	}

	/**
	 * Get configured search engines.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	protected function getConfiguredSearchEngines(): array
	{
		$configured = config( 'seo.sitemap.search_engines', [] );

		if ( empty( $configured ) ) {
			return self::DEFAULT_SEARCH_ENGINES;
		}

		return $configured;
	}
}
