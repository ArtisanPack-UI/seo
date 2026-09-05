<?php

/**
 * IndexNowSubmitter.
 *
 * Submits URL sets to IndexNow-compatible search engines.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\IndexNow;

use ArtisanPackUI\SEO\Contracts\IndexNowKeyProviderContract;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * IndexNowSubmitter class.
 *
 * Batches URLs into IndexNow-compliant payloads and POSTs them to the
 * configured endpoint (the aggregator by default, so a single request
 * fans out to every participating engine). Failure results are returned
 * to the caller so a queued job can decide to retry.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
class IndexNowSubmitter
{
	/**
	 * Aggregator endpoint that fans out to all participating engines.
	 *
	 * @since 1.4.0
	 *
	 * @var string
	 */
	protected const DEFAULT_ENDPOINT = 'https://api.indexnow.org/IndexNow';

	/**
	 * IndexNow permits up to 10,000 URLs per request.
	 *
	 * @since 1.4.0
	 *
	 * @var int
	 */
	protected const MAX_BATCH_SIZE = 10000;

	/**
	 * Key provider.
	 *
	 * @since 1.4.0
	 *
	 * @var IndexNowKeyProviderContract
	 */
	protected IndexNowKeyProviderContract $keyProvider;

	/**
	 * Endpoint URL to POST to.
	 *
	 * @since 1.4.0
	 *
	 * @var string
	 */
	protected string $endpoint;

	/**
	 * Maximum URLs per batch.
	 *
	 * @since 1.4.0
	 *
	 * @var int
	 */
	protected int $batchSize;

	/**
	 * HTTP timeout in seconds.
	 *
	 * @since 1.4.0
	 *
	 * @var int
	 */
	protected int $timeout;

	/**
	 * User-Agent header value.
	 *
	 * @since 1.4.0
	 *
	 * @var string
	 */
	protected string $userAgent;

	/**
	 * Create a new IndexNowSubmitter.
	 *
	 * @since 1.4.0
	 *
	 * @param  IndexNowKeyProviderContract  $keyProvider  Provides the IndexNow key.
	 * @param  string|null                  $endpoint     Endpoint URL; falls back to config.
	 * @param  int|null                     $batchSize    URLs per batch; falls back to config.
	 * @param  int|null                     $timeout      HTTP timeout in seconds; falls back to config.
	 * @param  string|null                  $userAgent    Custom User-Agent header; falls back to config.
	 */
	public function __construct(
		IndexNowKeyProviderContract $keyProvider,
		?string $endpoint = null,
		?int $batchSize = null,
		?int $timeout = null,
		?string $userAgent = null,
	) {
		$this->keyProvider = $keyProvider;
		$this->endpoint    = $endpoint ?? (string) config( 'seo.indexnow.endpoint', self::DEFAULT_ENDPOINT );
		$this->batchSize   = min(
			self::MAX_BATCH_SIZE,
			max( 1, $batchSize ?? (int) config( 'seo.indexnow.batch_size', self::MAX_BATCH_SIZE ) ),
		);
		$this->timeout     = $timeout ?? (int) config( 'seo.indexnow.timeout', 10 );
		$this->userAgent   = $userAgent ?? (string) config(
			'seo.indexnow.user_agent',
			'ArtisanPackUI SEO IndexNow Submitter',
		);
	}

	/**
	 * Submit one or more URLs.
	 *
	 * URLs are grouped by host (IndexNow requires a single host per
	 * request), then batched, and each batch POSTed independently.
	 * Results are returned as a collection of per-batch payloads —
	 * check `success` on each to decide whether to requeue.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<int, string>|string  $urls  URL or list of URLs.
	 *
	 * @throws InvalidArgumentException When no URLs are provided.
	 *
	 * @return Collection<int, array<string, mixed>>
	 */
	public function submit( array|string $urls ): Collection
	{
		$normalized = $this->normalizeUrls( is_array( $urls ) ? $urls : [ $urls ] );

		if ( [] === $normalized ) {
			throw new InvalidArgumentException( 'At least one URL is required for IndexNow submission.' );
		}

		$results = collect();

		foreach ( $this->groupByHost( $normalized ) as $host => $hostUrls ) {
			foreach ( array_chunk( $hostUrls, $this->batchSize ) as $batch ) {
				$results->push( $this->submitBatch( $host, $batch ) );
			}
		}

		return $results;
	}

	/**
	 * Build the JSON payload for a batch.
	 *
	 * Exposed for callers and tests that need to inspect the exact body
	 * that would be sent without performing the HTTP request.
	 *
	 * @since 1.4.0
	 *
	 * @param  string             $host      The host these URLs share.
	 * @param  array<int, string> $batchUrls The URLs in this batch.
	 *
	 * @return array<string, mixed>
	 */
	public function buildPayload( string $host, array $batchUrls ): array
	{
		$payload = [
			'host'    => $host,
			'key'     => $this->keyProvider->getKey(),
			'urlList' => array_values( $batchUrls ),
		];

		$keyLocation = $this->keyProvider->getKeyLocation();

		if ( null !== $keyLocation && '' !== $keyLocation ) {
			$payload['keyLocation'] = $keyLocation;
		}

		return $payload;
	}

	/**
	 * Get the effective endpoint URL.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function getEndpoint(): string
	{
		return $this->endpoint;
	}

	/**
	 * Get the effective batch size.
	 *
	 * @since 1.4.0
	 *
	 * @return int
	 */
	public function getBatchSize(): int
	{
		return $this->batchSize;
	}

	/**
	 * Normalize and filter a raw URL list.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<int, mixed>  $urls  The raw URL list.
	 *
	 * @return array<int, string>
	 */
	protected function normalizeUrls( array $urls ): array
	{
		$normalized = [];

		foreach ( $urls as $url ) {
			if ( ! is_string( $url ) ) {
				continue;
			}

			$trimmed = trim( $url );

			if ( '' === $trimmed || false === filter_var( $trimmed, FILTER_VALIDATE_URL ) ) {
				continue;
			}

			// IndexNow only accepts http/https URLs.
			$scheme = strtolower( (string) parse_url( $trimmed, PHP_URL_SCHEME ) );
			if ( 'http' !== $scheme && 'https' !== $scheme ) {
				continue;
			}

			$normalized[] = $trimmed;
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Group URLs by their host component.
	 *
	 * IndexNow accepts only one host per request, so each host gets its
	 * own batch(es).
	 *
	 * @since 1.4.0
	 *
	 * @param  array<int, string>  $urls  The normalized URL list.
	 *
	 * @return array<string, array<int, string>>
	 */
	protected function groupByHost( array $urls ): array
	{
		$groups = [];

		foreach ( $urls as $url ) {
			$host = parse_url( $url, PHP_URL_HOST );

			if ( ! is_string( $host ) || '' === $host ) {
				continue;
			}

			$groups[ $host ][] = $url;
		}

		return $groups;
	}

	/**
	 * POST a single batch and return a result payload.
	 *
	 * @since 1.4.0
	 *
	 * @param  string             $host       The host for this batch.
	 * @param  array<int, string> $batchUrls  The URLs to submit.
	 *
	 * @return array<string, mixed>
	 */
	protected function submitBatch( string $host, array $batchUrls ): array
	{
		$startTime = microtime( true );

		try {
			$payload = $this->buildPayload( $host, $batchUrls );
		} catch ( Throwable $e ) {
			return $this->buildErrorResult( $host, $batchUrls, $startTime, $e );
		}

		try {
			$response = Http::timeout( $this->timeout )
				->withUserAgent( $this->userAgent )
				->acceptJson()
				->asJson()
				->post( $this->endpoint, $payload );

			// IndexNow endpoints return 200 for the batch as a whole, but embed
			// per-URL rejections (unverified key, wrong host) in the response
			// body as {code, message, warnings}. Read those so a caller can
			// distinguish "accepted" from "quietly rejected".
			$body    = $this->parseResponseBody( $response );
			$warning = $this->extractResponseWarning( $body );
			$success = $response->successful() && null === $warning;

			$result = [
				'success'       => $success,
				'status_code'   => $response->status(),
				'response_time' => round( ( microtime( true ) - $startTime ) * 1000, 2 ),
				'endpoint'      => $this->endpoint,
				'host'          => $host,
				'url_count'     => count( $batchUrls ),
				'urls'          => $batchUrls,
				'response_body' => $body,
			];

			if ( null !== $warning ) {
				$result['warning'] = $warning;
			}

			$result['message'] = $success
				? __( 'IndexNow submission accepted for :host (:count URLs).', [
					'host'  => $host,
					'count' => count( $batchUrls ),
				] )
				: (
					$response->successful()
						? __( 'IndexNow submission for :host returned HTTP 200 with warning: :warning', [
							'host'    => $host,
							'warning' => (string) $warning,
						] )
						: __( 'IndexNow submission rejected for :host with status :status.', [
							'host'   => $host,
							'status' => $response->status(),
						] )
				);

			$this->logResult( $host, $result, $response );

			return $result;
		} catch ( Throwable $e ) {
			return $this->buildErrorResult( $host, $batchUrls, $startTime, $e );
		}
	}

	/**
	 * Build a failure result payload for exceptions.
	 *
	 * @since 1.4.0
	 *
	 * @param  string             $host       The host for the batch.
	 * @param  array<int, string> $batchUrls  The URLs in the batch.
	 * @param  float              $startTime  microtime(true) when the batch started.
	 * @param  Throwable          $exception  The captured exception.
	 *
	 * @return array<string, mixed>
	 */
	protected function buildErrorResult(
		string $host,
		array $batchUrls,
		float $startTime,
		Throwable $exception,
	): array {
		$result = [
			'success'       => false,
			'status_code'   => null,
			'response_time' => round( ( microtime( true ) - $startTime ) * 1000, 2 ),
			'endpoint'      => $this->endpoint,
			'host'          => $host,
			'url_count'     => count( $batchUrls ),
			'urls'          => $batchUrls,
			'message'       => __( 'IndexNow submission failed for :host: :error', [
				'host'  => $host,
				'error' => $exception->getMessage(),
			] ),
			'exception'     => $exception->getMessage(),
		];

		$this->logResult( $host, $result );

		return $result;
	}

	/**
	 * Log the batch result.
	 *
	 * @since 1.4.0
	 *
	 * @param  string                $host      The host for the batch.
	 * @param  array<string, mixed>  $result    The result payload.
	 * @param  Response|null         $response  The HTTP response, if any.
	 *
	 * @return void
	 */
	protected function logResult( string $host, array $result, ?Response $response = null ): void
	{
		$context = [
			'endpoint'      => $result['endpoint'],
			'host'          => $host,
			'url_count'     => $result['url_count'],
			'status_code'   => $result['status_code'],
			'response_time' => $result['response_time'],
		];

		if ( true === $result['success'] ) {
			if ( null !== $response ) {
				$context['body'] = Str::limit( (string) $response->body(), 512 );
			}
			Log::info( "IndexNow submission accepted for {$host}", $context );

			return;
		}

		$context['error'] = $result['exception']
			?? $result['warning']
			?? ( null !== $response ? Str::limit( (string) $response->body(), 512 ) : 'Unknown error' );
		Log::warning( "IndexNow submission failed for {$host}", $context );
	}

	/**
	 * Attempt to decode the response body as JSON.
	 *
	 * @since 1.4.0
	 *
	 * @param  Response  $response  The HTTP response.
	 *
	 * @return array<string, mixed>|null
	 */
	protected function parseResponseBody( Response $response ): ?array
	{
		$body = trim( (string) $response->body() );

		if ( '' === $body ) {
			return null;
		}

		try {
			$decoded = $response->json();
		} catch ( Throwable $e ) {
			return null;
		}

		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * Extract a per-URL warning code/message from a parsed response body.
	 *
	 * IndexNow endpoints signal partial failures with a JSON body that
	 * carries a `code` (e.g. `UnverifiedHost`) or a `warnings` array on
	 * an otherwise 200 response.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<string, mixed>|null  $body  Parsed JSON body.
	 *
	 * @return string|null
	 */
	protected function extractResponseWarning( ?array $body ): ?string
	{
		if ( null === $body ) {
			return null;
		}

		if ( isset( $body['code'] ) && '' !== (string) $body['code'] ) {
			$message = isset( $body['message'] ) ? (string) $body['message'] : '';

			return '' !== $message
				? sprintf( '%s: %s', (string) $body['code'], $message )
				: (string) $body['code'];
		}

		if ( isset( $body['warnings'] ) && is_array( $body['warnings'] ) && [] !== $body['warnings'] ) {
			return Str::limit( json_encode( $body['warnings'] ) ?: '', 256 );
		}

		return null;
	}
}
