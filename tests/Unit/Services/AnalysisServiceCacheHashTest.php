<?php

/**
 * AnalysisService cache content-hash invalidation tests.
 *
 * Guards the invariant that the analysis cache invalidates when the
 * resolved-and-filtered analysis HTML fingerprint changes, so that a
 * template-provided H1 or an `ap.seo.analysisContent` filter output
 * change forces a fresh analysis even when the model's content field
 * is untouched.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.7.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\AnalysisResultDTO;
use ArtisanPackUI\SEO\Models\SeoAnalysisCache;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\AnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	$this->service = new class() extends AnalysisService {
		public function callGetCachedResult( ?SeoMeta $seoMeta, ?string $focusKeyword, ?string $contentHash = null ): ?AnalysisResultDTO
		{
			return $this->getCachedResult( $seoMeta, $focusKeyword, $contentHash );
		}

		public function callCacheResults( SeoMeta $seoMeta, AnalysisResultDTO $result, ?string $contentHash = null ): SeoAnalysisCache
		{
			return $this->cacheResults( $seoMeta, $result, $contentHash );
		}

		public function callHashContent( string $content ): string
		{
			return $this->hashContent( $content );
		}
	};
} );

function makeCacheHashResultDto(): AnalysisResultDTO
{
	return new AnalysisResultDTO(
		overallScore: 82,
		readabilityScore: 80,
		keywordScore: 85,
		metaScore: 78,
		contentScore: 88,
		issues: [],
		suggestions: [],
		passedChecks: [ 'Test passed' ],
		focusKeyword: 'seo',
		wordCount: 42,
		analyzerResults: [],
	);
}

describe( 'AnalysisService cache content-hash invalidation', function (): void {

	it( 'stores the content hash on the cache row', function (): void {
		$seoMeta = SeoMeta::create( [ 'seoable_type' => 'App\\Models\\Post', 'seoable_id' => 1 ] );
		$hash    = $this->service->callHashContent( '<h1>Template H1</h1><p>Body</p>' );

		$cache = $this->service->callCacheResults( $seoMeta, makeCacheHashResultDto(), $hash );

		expect( $cache->content_hash )->toBe( $hash );
	} );

	it( 'returns the cached DTO when the content hash matches', function (): void {
		$seoMeta = SeoMeta::create( [ 'seoable_type' => 'App\\Models\\Post', 'seoable_id' => 1 ] );
		$hash    = $this->service->callHashContent( '<h1>Template H1</h1><p>Body</p>' );

		$this->service->callCacheResults( $seoMeta, makeCacheHashResultDto(), $hash );

		$cached = $this->service->callGetCachedResult( $seoMeta->fresh(), 'seo', $hash );

		expect( $cached )->toBeInstanceOf( AnalysisResultDTO::class )
			->and( $cached->overallScore )->toBe( 82 );
	} );

	it( 'returns null when the content hash has changed', function (): void {
		$seoMeta = SeoMeta::create( [ 'seoable_type' => 'App\\Models\\Post', 'seoable_id' => 1 ] );
		$oldHash = $this->service->callHashContent( '<h1>Old Template H1</h1>' );
		$newHash = $this->service->callHashContent( '<h1>New Template H1</h1>' );

		$this->service->callCacheResults( $seoMeta, makeCacheHashResultDto(), $oldHash );

		$cached = $this->service->callGetCachedResult( $seoMeta->fresh(), 'seo', $newHash );

		expect( $cached )->toBeNull();
	} );

	it( 'invalidates rows written before fingerprinting (null hash)', function (): void {
		$seoMeta = SeoMeta::create( [ 'seoable_type' => 'App\\Models\\Post', 'seoable_id' => 1 ] );

		$this->service->callCacheResults( $seoMeta, makeCacheHashResultDto(), null );

		$cached = $this->service->callGetCachedResult(
			$seoMeta->fresh(),
			'seo',
			$this->service->callHashContent( '<h1>Fresh</h1>' ),
		);

		expect( $cached )->toBeNull();
	} );

	it( 'still returns the cache when no hash is provided (back-compat)', function (): void {
		$seoMeta = SeoMeta::create( [ 'seoable_type' => 'App\\Models\\Post', 'seoable_id' => 1 ] );

		$this->service->callCacheResults( $seoMeta, makeCacheHashResultDto(), 'stored-hash' );

		$cached = $this->service->callGetCachedResult( $seoMeta->fresh(), 'seo', null );

		expect( $cached )->toBeInstanceOf( AnalysisResultDTO::class );
	} );
} );
