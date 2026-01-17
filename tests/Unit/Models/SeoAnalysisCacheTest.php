<?php

/**
 * SeoAnalysisCache Tests.
 *
 * Unit tests for the SeoAnalysisCache model.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoAnalysisCache;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

describe( 'SeoAnalysisCache Model Basics', function (): void {

	it( 'can be created with valid data', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type'     => 'App\Models\Post',
			'seoable_id'       => 1,
			'meta_title'       => 'Test Post',
			'meta_description' => 'Test description',
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'        => $seoMeta->id,
			'overall_score'      => 75,
			'readability_score'  => 80,
			'keyword_score'      => 70,
			'meta_score'         => 65,
			'content_score'      => 85,
			'issues'             => [ [ 'type' => 'warning', 'message' => 'Test issue' ] ],
			'suggestions'        => [ [ 'type' => 'suggestion', 'message' => 'Test suggestion' ] ],
			'passed_checks'      => [ 'Good readability' ],
			'analyzed_at'        => now(),
			'focus_keyword_used' => 'test keyword',
			'content_word_count' => 500,
		] );

		expect( $cache->exists )->toBeTrue()
			->and( $cache->overall_score )->toBe( 75 )
			->and( $cache->readability_score )->toBe( 80 );
	} );

	it( 'belongs to seo meta', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
			'meta_title'   => 'Test Post',
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 75,
		] );

		expect( $cache->seoMeta )->toBeInstanceOf( SeoMeta::class )
			->and( $cache->seoMeta->id )->toBe( $seoMeta->id );
	} );

	it( 'casts json fields correctly', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 60,
			'issues'        => [ [ 'type' => 'warning', 'message' => 'Test' ] ],
			'suggestions'   => [ [ 'type' => 'suggestion', 'message' => 'Suggest' ] ],
			'passed_checks' => [ 'Check 1', 'Check 2' ],
		] );

		$cache->refresh();

		expect( $cache->issues )->toBeArray()
			->and( $cache->suggestions )->toBeArray()
			->and( $cache->passed_checks )->toBeArray()
			->and( count( $cache->issues ) )->toBe( 1 )
			->and( count( $cache->passed_checks ) )->toBe( 2 );
	} );

	it( 'casts datetime field correctly', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 60,
			'analyzed_at'   => now(),
		] );

		$cache->refresh();

		expect( $cache->analyzed_at )->toBeInstanceOf( Carbon\Carbon::class );
	} );

} );

describe( 'SeoAnalysisCache Grade Methods', function (): void {

	it( 'returns good grade for score 80 or higher', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 80,
		] );

		expect( $cache->getGrade() )->toBe( 'good' );

		$cache->update( [ 'overall_score' => 100 ] );
		expect( $cache->getGrade() )->toBe( 'good' );
	} );

	it( 'returns ok grade for score 50-79', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 50,
		] );

		expect( $cache->getGrade() )->toBe( 'ok' );

		$cache->update( [ 'overall_score' => 79 ] );
		expect( $cache->getGrade() )->toBe( 'ok' );
	} );

	it( 'returns poor grade for score below 50', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 49,
		] );

		expect( $cache->getGrade() )->toBe( 'poor' );

		$cache->update( [ 'overall_score' => 0 ] );
		expect( $cache->getGrade() )->toBe( 'poor' );
	} );

	it( 'returns correct grade color', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 85,
		] );

		expect( $cache->getGradeColor() )->toBe( 'green' );

		$cache->update( [ 'overall_score' => 65 ] );
		expect( $cache->getGradeColor() )->toBe( 'yellow' );

		$cache->update( [ 'overall_score' => 30 ] );
		expect( $cache->getGradeColor() )->toBe( 'red' );
	} );

} );

describe( 'SeoAnalysisCache Count Methods', function (): void {

	it( 'counts issues correctly', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 60,
			'issues'        => [
				[ 'type' => 'warning', 'message' => '1' ],
				[ 'type' => 'warning', 'message' => '2' ],
				[ 'type' => 'error', 'message' => '3' ],
			],
		] );

		expect( $cache->getIssueCount() )->toBe( 3 );
	} );

	it( 'returns zero for null issues', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 80,
			'issues'        => null,
		] );

		expect( $cache->getIssueCount() )->toBe( 0 );
	} );

	it( 'counts suggestions correctly', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 70,
			'suggestions'   => [
				[ 'type' => 'suggestion', 'message' => '1' ],
				[ 'type' => 'suggestion', 'message' => '2' ],
			],
		] );

		expect( $cache->getSuggestionCount() )->toBe( 2 );
	} );

	it( 'counts passed checks correctly', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 85,
			'passed_checks' => [ 'Check 1', 'Check 2', 'Check 3', 'Check 4' ],
		] );

		expect( $cache->getPassedCount() )->toBe( 4 );
	} );

} );

describe( 'SeoAnalysisCache Staleness', function (): void {

	it( 'is stale when analyzed_at is null', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 75,
			'analyzed_at'   => null,
		] );

		expect( $cache->isStale() )->toBeTrue();
	} );

	it( 'is stale when older than TTL', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 75,
			'analyzed_at'   => now()->subHours( 25 ), // 25 hours ago, default TTL is 24 hours
		] );

		expect( $cache->isStale() )->toBeTrue();
	} );

	it( 'is not stale when within TTL', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 75,
			'analyzed_at'   => now()->subHours( 1 ), // 1 hour ago
		] );

		expect( $cache->isStale() )->toBeFalse();
	} );

	it( 'respects custom TTL', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta->id,
			'overall_score' => 75,
			'analyzed_at'   => now()->subMinutes( 30 ),
		] );

		// With 1 hour TTL, should not be stale
		expect( $cache->isStale( 3600 ) )->toBeFalse();

		// With 10 minute TTL, should be stale
		expect( $cache->isStale( 600 ) )->toBeTrue();
	} );

	it( 'needs refresh when focus keyword changed', function (): void {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
		] );

		$cache = SeoAnalysisCache::create( [
			'seo_meta_id'        => $seoMeta->id,
			'overall_score'      => 75,
			'focus_keyword_used' => 'old keyword',
		] );

		expect( $cache->needsRefreshForKeyword( 'new keyword' ) )->toBeTrue()
			->and( $cache->needsRefreshForKeyword( 'old keyword' ) )->toBeFalse();
	} );

} );

describe( 'SeoAnalysisCache Scopes', function (): void {

	it( 'filters by good grade', function (): void {
		$seoMeta1 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 1 ] );
		$seoMeta2 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 2 ] );
		$seoMeta3 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 3 ] );

		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta1->id, 'overall_score' => 85 ] );
		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta2->id, 'overall_score' => 65 ] );
		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta3->id, 'overall_score' => 30 ] );

		expect( SeoAnalysisCache::goodGrade()->count() )->toBe( 1 );
	} );

	it( 'filters by ok grade', function (): void {
		$seoMeta1 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 1 ] );
		$seoMeta2 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 2 ] );
		$seoMeta3 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 3 ] );

		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta1->id, 'overall_score' => 85 ] );
		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta2->id, 'overall_score' => 65 ] );
		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta3->id, 'overall_score' => 30 ] );

		expect( SeoAnalysisCache::okGrade()->count() )->toBe( 1 );
	} );

	it( 'filters by poor grade', function (): void {
		$seoMeta1 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 1 ] );
		$seoMeta2 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 2 ] );
		$seoMeta3 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 3 ] );

		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta1->id, 'overall_score' => 85 ] );
		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta2->id, 'overall_score' => 65 ] );
		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta3->id, 'overall_score' => 30 ] );

		expect( SeoAnalysisCache::poorGrade()->count() )->toBe( 1 );
	} );

	it( 'filters stale analyses', function (): void {
		$seoMeta1 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 1 ] );
		$seoMeta2 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 2 ] );
		$seoMeta3 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 3 ] );

		SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta1->id,
			'overall_score' => 75,
			'analyzed_at'   => now()->subDays( 2 ), // Stale
		] );
		SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta2->id,
			'overall_score' => 75,
			'analyzed_at'   => now()->subHours( 1 ), // Fresh
		] );
		SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta3->id,
			'overall_score' => 75,
			'analyzed_at'   => null, // Stale (null)
		] );

		expect( SeoAnalysisCache::stale()->count() )->toBe( 2 );
	} );

	it( 'filters non-stale (fresh) analyses', function (): void {
		$seoMeta1 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 1 ] );
		$seoMeta2 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 2 ] );

		SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta1->id,
			'overall_score' => 75,
			'analyzed_at'   => now()->subDays( 2 ), // Stale
		] );
		SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta2->id,
			'overall_score' => 75,
			'analyzed_at'   => now()->subHours( 1 ), // Fresh
		] );

		expect( SeoAnalysisCache::notStale()->count() )->toBe( 1 );
	} );

	it( 'orders by score descending', function (): void {
		$seoMeta1 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 1 ] );
		$seoMeta2 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 2 ] );
		$seoMeta3 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 3 ] );

		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta1->id, 'overall_score' => 50 ] );
		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta2->id, 'overall_score' => 90 ] );
		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta3->id, 'overall_score' => 70 ] );

		$ordered = SeoAnalysisCache::orderByScore()->pluck( 'overall_score' )->toArray();

		expect( $ordered )->toBe( [ 90, 70, 50 ] );
	} );

	it( 'filters by minimum score', function (): void {
		$seoMeta1 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 1 ] );
		$seoMeta2 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 2 ] );
		$seoMeta3 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 3 ] );

		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta1->id, 'overall_score' => 50 ] );
		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta2->id, 'overall_score' => 80 ] );
		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta3->id, 'overall_score' => 70 ] );

		expect( SeoAnalysisCache::minimumScore( 70 )->count() )->toBe( 2 )
			->and( SeoAnalysisCache::minimumScore( 80 )->count() )->toBe( 1 );
	} );

} );
