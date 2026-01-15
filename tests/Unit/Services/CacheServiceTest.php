<?php

/**
 * CacheService Tests.
 *
 * Unit tests for the CacheService.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\CacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

beforeEach( function (): void {
	Cache::flush();
	config( [
		'seo.cache.enabled' => true,
		'seo.cache.ttl'     => 3600,
		'seo.cache.prefix'  => 'seo',
	] );
} );

describe( 'CacheService Basic Operations', function (): void {

	it( 'can store and retrieve values', function (): void {
		$service = new CacheService();

		$service->put( 'test-key', 'test-value' );
		$result = $service->get( 'test-key' );

		expect( $result )->toBe( 'test-value' );
	} );

	it( 'returns null for non-existent keys', function (): void {
		$service = new CacheService();

		$result = $service->get( 'non-existent-key' );

		expect( $result )->toBeNull();
	} );

	it( 'can forget cached values', function (): void {
		$service = new CacheService();

		$service->put( 'forget-key', 'forget-value' );
		$service->forget( 'forget-key' );
		$result = $service->get( 'forget-key' );

		expect( $result )->toBeNull();
	} );

	it( 'can remember values using callback', function (): void {
		$service   = new CacheService();
		$callCount = 0;

		$callback = function () use ( &$callCount ): string {
			$callCount++;

			return 'computed-value';
		};

		// First call - should execute callback
		$result1 = $service->remember( 'remember-key', $callback );

		// Second call - should return cached value
		$result2 = $service->remember( 'remember-key', $callback );

		expect( $result1 )->toBe( 'computed-value' )
			->and( $result2 )->toBe( 'computed-value' )
			->and( $callCount )->toBe( 1 );
	} );

	it( 'can store values with custom TTL', function (): void {
		$service = new CacheService();

		$service->put( 'ttl-key', 'ttl-value', 60 );
		$result = $service->get( 'ttl-key' );

		expect( $result )->toBe( 'ttl-value' );
	} );

} );

describe( 'CacheService Configuration', function (): void {

	it( 'returns TTL from config', function (): void {
		config( [ 'seo.cache.ttl' => 7200 ] );
		$service = new CacheService();

		expect( $service->getTtl() )->toBe( 7200 );
	} );

	it( 'returns enabled status from config', function (): void {
		config( [ 'seo.cache.enabled' => true ] );
		$service = new CacheService();

		expect( $service->isEnabled() )->toBeTrue();

		config( [ 'seo.cache.enabled' => false ] );

		expect( $service->isEnabled() )->toBeFalse();
	} );

	it( 'returns cache prefix from config', function (): void {
		config( [ 'seo.cache.prefix' => 'custom-prefix' ] );
		$service = new CacheService();

		expect( $service->getPrefix() )->toBe( 'custom-prefix' );
	} );

} );

describe( 'CacheService Cache Keys', function (): void {

	it( 'generates meta cache key for model', function (): void {
		$service = new CacheService();

		$model = new class extends Model {
			protected $guarded = [];

			public function getKey(): mixed
			{
				return 123;
			}
		};

		$key = $service->getMetaCacheKey( $model );

		expect( $key )->toContain( 'meta:' )
			->and( $key )->toContain( ':123' );
	} );

	it( 'generates analysis cache key for model', function (): void {
		$service = new CacheService();

		$model = new class extends Model {
			protected $guarded = [];

			public function getKey(): mixed
			{
				return 456;
			}
		};

		$key = $service->getAnalysisCacheKey( $model );

		expect( $key )->toContain( 'analysis:' )
			->and( $key )->toContain( ':456' );
	} );

} );

describe( 'CacheService Cache Clearing', function (): void {

	it( 'clears meta cache for model', function (): void {
		$service = new CacheService();

		$model = new class extends Model {
			protected $guarded = [];

			public function getKey(): mixed
			{
				return 1;
			}
		};

		$cacheKey = $service->getMetaCacheKey( $model );
		$service->put( $cacheKey, 'cached-meta' );

		$service->clearMetaCache( $model );

		expect( $service->get( $cacheKey ) )->toBeNull();
	} );

	it( 'clears analysis cache for model', function (): void {
		$service = new CacheService();

		$model = new class extends Model {
			protected $guarded = [];

			public function getKey(): mixed
			{
				return 2;
			}
		};

		$cacheKey = $service->getAnalysisCacheKey( $model );
		$service->put( $cacheKey, 'cached-analysis' );

		$service->clearAnalysisCache( $model );

		expect( $service->get( $cacheKey ) )->toBeNull();
	} );

	it( 'clears all caches for a model', function (): void {
		$service = new CacheService();

		$model = new class extends Model {
			protected $guarded = [];

			public function getKey(): mixed
			{
				return 3;
			}
		};

		$metaKey     = $service->getMetaCacheKey( $model );
		$analysisKey = $service->getAnalysisCacheKey( $model );

		$service->put( $metaKey, 'meta-value' );
		$service->put( $analysisKey, 'analysis-value' );

		$service->clearAllForModel( $model );

		expect( $service->get( $metaKey ) )->toBeNull()
			->and( $service->get( $analysisKey ) )->toBeNull();
	} );

	it( 'clears all known SEO caches', function (): void {
		$service = new CacheService();

		$service->put( 'sitemap:index', 'sitemap-data' );
		$service->put( 'redirects:all', 'redirects-data' );

		$service->clearAll();

		expect( $service->get( 'sitemap:index' ) )->toBeNull()
			->and( $service->get( 'redirects:all' ) )->toBeNull();
	} );

} );

describe( 'CacheService Disabled Mode', function (): void {

	it( 'returns null when caching is disabled', function (): void {
		config( [ 'seo.cache.enabled' => false ] );
		$service = new CacheService();

		$service->put( 'disabled-key', 'disabled-value' );
		$result = $service->get( 'disabled-key' );

		expect( $result )->toBeNull();
	} );

	it( 'executes callback directly when caching is disabled', function (): void {
		config( [ 'seo.cache.enabled' => false ] );
		$service   = new CacheService();
		$callCount = 0;

		$callback = function () use ( &$callCount ): string {
			$callCount++;

			return 'direct-value';
		};

		$result1 = $service->remember( 'disabled-remember', $callback );
		$result2 = $service->remember( 'disabled-remember', $callback );

		expect( $result1 )->toBe( 'direct-value' )
			->and( $result2 )->toBe( 'direct-value' )
			->and( $callCount )->toBe( 2 );
	} );

	it( 'does not store values when caching is disabled', function (): void {
		config( [ 'seo.cache.enabled' => false ] );
		$service = new CacheService();

		$service->put( 'no-store-key', 'no-store-value' );

		// Re-enable to verify nothing was stored
		config( [ 'seo.cache.enabled' => true ] );

		expect( $service->get( 'no-store-key' ) )->toBeNull();
	} );

} );
