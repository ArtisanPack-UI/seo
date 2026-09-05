<?php

/**
 * IndexNowSubmitter Tests.
 *
 * Unit tests for the IndexNow submitter.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\IndexNowKeyProviderContract;
use ArtisanPackUI\SEO\IndexNow\ConfigIndexNowKeyProvider;
use ArtisanPackUI\SEO\IndexNow\IndexNowSubmitter;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

beforeEach( function (): void {
	config( [
		'seo.indexnow.endpoint'   => 'https://api.indexnow.org/IndexNow',
		'seo.indexnow.batch_size' => 10000,
		'seo.indexnow.timeout'    => 5,
		'seo.indexnow.user_agent' => 'ArtisanPackUI SEO IndexNow Submitter',
	] );
} );

function makeKeyProvider( string $key = 'a1b2c3d4e5f6a7b8', ?string $location = null ): IndexNowKeyProviderContract
{
	return new class( $key, $location ) implements IndexNowKeyProviderContract {
		public function __construct( private string $key, private ?string $location )
		{
		}

		public function getKey(): string
		{
			return $this->key;
		}

		public function getKeyLocation(): ?string
		{
			return $this->location;
		}
	};
}

describe( 'IndexNowSubmitter', function (): void {

	it( 'builds a spec-compliant payload for a batch', function (): void {
		$submitter = new IndexNowSubmitter( makeKeyProvider( 'abcdef1234567890' ) );

		$payload = $submitter->buildPayload( 'example.com', [
			'https://example.com/page-1',
			'https://example.com/page-2',
		] );

		expect( $payload )->toBe( [
			'host'    => 'example.com',
			'key'     => 'abcdef1234567890',
			'urlList' => [
				'https://example.com/page-1',
				'https://example.com/page-2',
			],
		] );
	} );

	it( 'includes keyLocation when the provider supplies one', function (): void {
		$submitter = new IndexNowSubmitter(
			makeKeyProvider( 'abcdef1234567890', 'https://example.com/verify/abcdef1234567890.txt' ),
		);

		$payload = $submitter->buildPayload( 'example.com', [ 'https://example.com/x' ] );

		expect( $payload )->toHaveKey( 'keyLocation', 'https://example.com/verify/abcdef1234567890.txt' );
	} );

	it( 'posts JSON to the configured endpoint with the correct User-Agent', function (): void {
		Http::fake( [
			'api.indexnow.org/*' => Http::response( '', 200 ),
		] );

		$submitter = new IndexNowSubmitter( makeKeyProvider( 'abcdef1234567890' ) );
		$results   = $submitter->submit( [ 'https://example.com/one', 'https://example.com/two' ] );

		expect( $results )->toHaveCount( 1 )
			->and( $results->first()['success'] )->toBeTrue()
			->and( $results->first()['url_count'] )->toBe( 2 );

		Http::assertSent( function ( HttpRequest $request ): bool {
			return 'POST' === $request->method()
				&& 'https://api.indexnow.org/IndexNow' === $request->url()
				&& 'ArtisanPackUI SEO IndexNow Submitter' === $request->header( 'User-Agent' )[0]
				&& str_contains( $request->header( 'Content-Type' )[0], 'application/json' )
				&& 'example.com' === $request->data()['host']
				&& [ 'https://example.com/one', 'https://example.com/two' ] === $request->data()['urlList'];
		} );
	} );

	it( 'groups URLs by host into separate requests', function (): void {
		Http::fake( [ 'api.indexnow.org/*' => Http::response( '', 200 ) ] );

		$submitter = new IndexNowSubmitter( makeKeyProvider() );
		$results   = $submitter->submit( [
			'https://a.example.com/one',
			'https://b.example.com/one',
			'https://a.example.com/two',
		] );

		expect( $results )->toHaveCount( 2 );

		$hosts = $results->pluck( 'host' )->all();
		sort( $hosts );

		expect( $hosts )->toBe( [ 'a.example.com', 'b.example.com' ] );
	} );

	it( 'batches large URL lists into chunks', function (): void {
		Http::fake( [ 'api.indexnow.org/*' => Http::response( '', 200 ) ] );

		$submitter = new IndexNowSubmitter( makeKeyProvider(), null, 2 );

		$urls = [
			'https://example.com/1',
			'https://example.com/2',
			'https://example.com/3',
			'https://example.com/4',
			'https://example.com/5',
		];

		$results = $submitter->submit( $urls );

		expect( $results )->toHaveCount( 3 )
			->and( $results->pluck( 'url_count' )->all() )->toBe( [ 2, 2, 1 ] );
	} );

	it( 'reports failure results without throwing on rejection', function (): void {
		Http::fake( [ 'api.indexnow.org/*' => Http::response( 'bad key', 403 ) ] );

		$submitter = new IndexNowSubmitter( makeKeyProvider() );
		$results   = $submitter->submit( 'https://example.com/x' );

		$first = $results->first();

		expect( $first['success'] )->toBeFalse()
			->and( $first['status_code'] )->toBe( 403 );
	} );

	it( 'filters out invalid URLs before dispatch', function (): void {
		Http::fake( [ 'api.indexnow.org/*' => Http::response( '', 200 ) ] );

		$submitter = new IndexNowSubmitter( makeKeyProvider() );
		$results   = $submitter->submit( [
			'https://example.com/ok',
			'not-a-url',
			'',
			'https://example.com/ok', // duplicate
		] );

		expect( $results )->toHaveCount( 1 )
			->and( $results->first()['url_count'] )->toBe( 1 );
	} );

	it( 'rejects non-http(s) schemes', function (): void {
		Http::fake( [ 'api.indexnow.org/*' => Http::response( '', 200 ) ] );

		$submitter = new IndexNowSubmitter( makeKeyProvider() );

		$submitter->submit( [
			'javascript:alert(1)',
			'file:///etc/passwd',
			'ftp://example.com/one',
		] );
	} )->throws( InvalidArgumentException::class );

	it( 'throws when no valid URLs are supplied', function (): void {
		$submitter = new IndexNowSubmitter( makeKeyProvider() );

		$submitter->submit( [ 'not-a-url', '' ] );
	} )->throws( InvalidArgumentException::class );

	it( 'ConfigIndexNowKeyProvider reads the key from config', function (): void {
		config( [
			'seo.indexnow.key'          => 'abcdef1234567890',
			'seo.indexnow.key_location' => 'https://example.com/abcdef1234567890.txt',
		] );

		$provider = new ConfigIndexNowKeyProvider();

		expect( $provider->getKey() )->toBe( 'abcdef1234567890' )
			->and( $provider->getKeyLocation() )->toBe( 'https://example.com/abcdef1234567890.txt' );
	} );

	it( 'ConfigIndexNowKeyProvider throws when no key is configured', function (): void {
		config( [ 'seo.indexnow.key' => null ] );

		( new ConfigIndexNowKeyProvider() )->getKey();
	} )->throws( RuntimeException::class );

	it( 'flags a 200 response carrying an UnverifiedHost code as a failure', function (): void {
		Http::fake( [
			'api.indexnow.org/*' => Http::response(
				[ 'code' => 'UnverifiedHost', 'message' => 'Host ownership could not be verified.' ],
				200,
			),
		] );

		$submitter = new IndexNowSubmitter( makeKeyProvider() );
		$results   = $submitter->submit( 'https://example.com/x' );
		$first     = $results->first();

		expect( $first['success'] )->toBeFalse()
			->and( $first['status_code'] )->toBe( 200 )
			->and( $first['warning'] ?? null )->toContain( 'UnverifiedHost' );
	} );

	it( 'treats a 200 response with warnings[] as a partial failure', function (): void {
		Http::fake( [
			'api.indexnow.org/*' => Http::response(
				[ 'warnings' => [ [ 'code' => 'InvalidUrl', 'url' => 'https://example.com/x' ] ] ],
				200,
			),
		] );

		$submitter = new IndexNowSubmitter( makeKeyProvider() );
		$first     = $submitter->submit( 'https://example.com/x' )->first();

		expect( $first['success'] )->toBeFalse()
			->and( $first['warning'] ?? null )->not->toBeNull();
	} );

	it( 'passes clean 200 responses through as successful', function (): void {
		Http::fake( [ 'api.indexnow.org/*' => Http::response( '', 200 ) ] );

		$submitter = new IndexNowSubmitter( makeKeyProvider() );
		$first     = $submitter->submit( 'https://example.com/x' )->first();

		expect( $first['success'] )->toBeTrue();
	} );
} );
