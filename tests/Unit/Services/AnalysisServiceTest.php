<?php

/**
 * AnalysisService Tests.
 *
 * Unit tests for the AnalysisService.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\AnalyzerContract;
use ArtisanPackUI\SEO\DTOs\AnalysisResultDTO;
use ArtisanPackUI\SEO\Models\SeoAnalysisCache;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\AnalysisService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
	$this->service = new AnalysisService();
} );

/**
 * Create a mock analyzer for testing.
 */
function createMockAnalyzer( string $name, string $category, int $weight, int $score ): AnalyzerContract
{
	return new class( $name, $category, $weight, $score ) implements AnalyzerContract {
		public function __construct(
			private string $name,
			private string $category,
			private int $weight,
			private int $score,
		) {
		}

		public function analyze( Model $model, string $content, ?string $focusKeyword, ?SeoMeta $seoMeta ): array
		{
			return [
				'score'       => $this->score,
				'issues'      => $this->score < 50 ? [ [ 'type' => 'warning', 'message' => 'Test issue' ] ] : [],
				'suggestions' => $this->score < 80 ? [ [ 'type' => 'suggestion', 'message' => 'Test suggestion' ] ] : [],
				'passed'      => $this->score >= 50 ? [ 'Test passed' ] : [],
				'details'     => [ 'test_detail' => true ],
			];
		}

		public function getName(): string
		{
			return $this->name;
		}

		public function getCategory(): string
		{
			return $this->category;
		}

		public function getWeight(): int
		{
			return $this->weight;
		}
	};
}

/**
 * Create a simple test model for analysis.
 */
function createAnalysisTestModel( string $content = 'Test content' ): Model
{
	return new class( $content ) extends Model {
		protected $fillable = [ 'content' ];

		public function __construct( string $content = '' )
		{
			parent::__construct();
			$this->content = $content;
		}
	};
}

describe( 'AnalysisService Analyzer Registration', function (): void {

	it( 'registers an analyzer', function (): void {
		$analyzer = createMockAnalyzer( 'test_analyzer', 'readability', 50, 75 );

		$this->service->registerAnalyzer( 'test_analyzer', $analyzer );

		expect( $this->service->hasAnalyzer( 'test_analyzer' ) )->toBeTrue()
			->and( $this->service->getAnalyzers() )->toHaveKey( 'test_analyzer' );
	} );

	it( 'unregisters an analyzer', function (): void {
		$analyzer = createMockAnalyzer( 'test_analyzer', 'readability', 50, 75 );

		$this->service->registerAnalyzer( 'test_analyzer', $analyzer );
		$this->service->unregisterAnalyzer( 'test_analyzer' );

		expect( $this->service->hasAnalyzer( 'test_analyzer' ) )->toBeFalse();
	} );

	it( 'returns analyzers by category', function (): void {
		$readabilityAnalyzer = createMockAnalyzer( 'readability', 'readability', 50, 75 );
		$keywordAnalyzer     = createMockAnalyzer( 'keyword', 'keyword', 50, 70 );
		$metaAnalyzer        = createMockAnalyzer( 'meta', 'meta', 50, 80 );

		$this->service->registerAnalyzer( 'readability', $readabilityAnalyzer );
		$this->service->registerAnalyzer( 'keyword', $keywordAnalyzer );
		$this->service->registerAnalyzer( 'meta', $metaAnalyzer );

		$readabilityAnalyzers = $this->service->getAnalyzersByCategory( 'readability' );
		$keywordAnalyzers     = $this->service->getAnalyzersByCategory( 'keyword' );

		expect( count( $readabilityAnalyzers ) )->toBe( 1 )
			->and( count( $keywordAnalyzers ) )->toBe( 1 )
			->and( $this->service->getAnalyzersByCategory( 'nonexistent' ) )->toBe( [] );
	} );

} );

describe( 'AnalysisService Category Weights', function (): void {

	it( 'has default category weights', function (): void {
		$weights = $this->service->getCategoryWeights();

		expect( $weights )->toBe( [
			'readability' => 25,
			'keyword'     => 30,
			'meta'        => 20,
			'content'     => 25,
		] );
	} );

	it( 'allows setting custom weights', function (): void {
		$this->service->setCategoryWeights( [
			'readability' => 30,
			'keyword'     => 35,
		] );

		$weights = $this->service->getCategoryWeights();

		expect( $weights['readability'] )->toBe( 30 )
			->and( $weights['keyword'] )->toBe( 35 )
			->and( $weights['meta'] )->toBe( 20 ) // Default preserved
			->and( $weights['content'] )->toBe( 25 ); // Default preserved
	} );

	it( 'gets weight for specific category', function (): void {
		expect( $this->service->getCategoryWeight( 'readability' ) )->toBe( 25 )
			->and( $this->service->getCategoryWeight( 'keyword' ) )->toBe( 30 )
			->and( $this->service->getCategoryWeight( 'invalid' ) )->toBe( 0 );
	} );

} );

describe( 'AnalysisService Analysis', function (): void {

	it( 'analyzes model and returns DTO', function (): void {
		$analyzer = createMockAnalyzer( 'readability', 'readability', 100, 75 );
		$this->service->registerAnalyzer( 'readability', $analyzer );

		$model  = createAnalysisTestModel( 'This is test content for SEO analysis.' );
		$result = $this->service->analyze( $model, null, false );

		expect( $result )->toBeInstanceOf( AnalysisResultDTO::class )
			->and( $result->readabilityScore )->toBe( 75 );
	} );

	it( 'calculates category scores from multiple analyzers', function (): void {
		// Register two keyword analyzers with different weights
		$keywordAnalyzer1 = createMockAnalyzer( 'keyword_density', 'keyword', 60, 80 );
		$keywordAnalyzer2 = createMockAnalyzer( 'focus_keyword', 'keyword', 40, 60 );

		$this->service->registerAnalyzer( 'keyword_density', $keywordAnalyzer1 );
		$this->service->registerAnalyzer( 'focus_keyword', $keywordAnalyzer2 );

		$model  = createAnalysisTestModel( 'Keyword optimization test content.' );
		$result = $this->service->analyze( $model, 'keyword', false );

		// Weighted average: (80*60 + 60*40) / (60+40) = (4800 + 2400) / 100 = 72
		expect( $result->keywordScore )->toBe( 72 );
	} );

	it( 'calculates overall weighted score', function (): void {
		$readabilityAnalyzer = createMockAnalyzer( 'readability', 'readability', 100, 80 );
		$keywordAnalyzer     = createMockAnalyzer( 'keyword', 'keyword', 100, 70 );
		$metaAnalyzer        = createMockAnalyzer( 'meta', 'meta', 100, 90 );
		$contentAnalyzer     = createMockAnalyzer( 'content', 'content', 100, 60 );

		$this->service->registerAnalyzer( 'readability', $readabilityAnalyzer );
		$this->service->registerAnalyzer( 'keyword', $keywordAnalyzer );
		$this->service->registerAnalyzer( 'meta', $metaAnalyzer );
		$this->service->registerAnalyzer( 'content', $contentAnalyzer );

		$model  = createAnalysisTestModel( 'Full analysis test content.' );
		$result = $this->service->analyze( $model, null, false );

		// With default weights: readability=25, keyword=30, meta=20, content=25
		// Overall = (80*25 + 70*30 + 90*20 + 60*25) / (25+30+20+25)
		// = (2000 + 2100 + 1800 + 1500) / 100 = 74
		expect( $result->overallScore )->toBe( 74 );
	} );

	it( 'collects issues from all analyzers', function (): void {
		$analyzer1 = createMockAnalyzer( 'analyzer1', 'readability', 50, 30 ); // Low score = has issue
		$analyzer2 = createMockAnalyzer( 'analyzer2', 'keyword', 50, 30 );     // Low score = has issue

		$this->service->registerAnalyzer( 'analyzer1', $analyzer1 );
		$this->service->registerAnalyzer( 'analyzer2', $analyzer2 );

		$model  = createAnalysisTestModel( 'Test content.' );
		$result = $this->service->analyze( $model, null, false );

		expect( $result->hasIssues() )->toBeTrue()
			->and( $result->getIssueCount() )->toBe( 2 );
	} );

	it( 'collects suggestions from all analyzers', function (): void {
		$analyzer1 = createMockAnalyzer( 'analyzer1', 'readability', 50, 60 ); // Medium score = has suggestion
		$analyzer2 = createMockAnalyzer( 'analyzer2', 'keyword', 50, 60 );     // Medium score = has suggestion

		$this->service->registerAnalyzer( 'analyzer1', $analyzer1 );
		$this->service->registerAnalyzer( 'analyzer2', $analyzer2 );

		$model  = createAnalysisTestModel( 'Test content.' );
		$result = $this->service->analyze( $model, null, false );

		expect( $result->hasSuggestions() )->toBeTrue()
			->and( $result->getSuggestionCount() )->toBe( 2 );
	} );

	it( 'collects passed checks from all analyzers', function (): void {
		$analyzer1 = createMockAnalyzer( 'analyzer1', 'readability', 50, 80 ); // High score = passed
		$analyzer2 = createMockAnalyzer( 'analyzer2', 'keyword', 50, 80 );     // High score = passed

		$this->service->registerAnalyzer( 'analyzer1', $analyzer1 );
		$this->service->registerAnalyzer( 'analyzer2', $analyzer2 );

		$model  = createAnalysisTestModel( 'Test content.' );
		$result = $this->service->analyze( $model, null, false );

		expect( $result->getPassedCount() )->toBe( 2 );
	} );

	it( 'counts words correctly', function (): void {
		$analyzer = createMockAnalyzer( 'test', 'readability', 100, 75 );
		$this->service->registerAnalyzer( 'test', $analyzer );

		$model  = createAnalysisTestModel( 'One two three four five six seven eight nine ten.' );
		$result = $this->service->analyze( $model, null, false );

		expect( $result->wordCount )->toBe( 10 );
	} );

	it( 'returns zero scores when no analyzers registered', function (): void {
		$model  = createAnalysisTestModel( 'Test content.' );
		$result = $this->service->analyze( $model, null, false );

		expect( $result->overallScore )->toBe( 0 )
			->and( $result->readabilityScore )->toBe( 0 )
			->and( $result->keywordScore )->toBe( 0 )
			->and( $result->metaScore )->toBe( 0 )
			->and( $result->contentScore )->toBe( 0 );
	} );

	it( 'includes analyzer results in DTO', function (): void {
		$analyzer = createMockAnalyzer( 'test_analyzer', 'readability', 100, 75 );
		$this->service->registerAnalyzer( 'test_analyzer', $analyzer );

		$model  = createAnalysisTestModel( 'Test content.' );
		$result = $this->service->analyze( $model, null, false );

		expect( $result->analyzerResults )->toHaveKey( 'test_analyzer' )
			->and( $result->getAnalyzerResult( 'test_analyzer' ) )->toHaveKey( 'score' );
	} );

} );

describe( 'AnalysisService Statistics', function (): void {

	it( 'returns empty statistics when no data', function (): void {
		$stats = $this->service->getStatistics();

		expect( $stats['total'] )->toBe( 0 )
			->and( $stats['good_count'] )->toBe( 0 )
			->and( $stats['average_score'] )->toBe( 0 )
			->and( $stats['good_percentage'] )->toEqual( 0 );
	} );

	it( 'calculates statistics correctly', function (): void {
		// Create test data
		$seoMeta1 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 1 ] );
		$seoMeta2 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 2 ] );
		$seoMeta3 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 3 ] );

		SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta1->id,
			'overall_score' => 85,
			'analyzed_at'   => now(),
		] );
		SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta2->id,
			'overall_score' => 65,
			'analyzed_at'   => now(),
		] );
		SeoAnalysisCache::create( [
			'seo_meta_id'   => $seoMeta3->id,
			'overall_score' => 30,
			'analyzed_at'   => now(),
		] );

		$stats = $this->service->getStatistics();

		expect( $stats['total'] )->toBe( 3 )
			->and( $stats['good_count'] )->toBe( 1 )
			->and( $stats['ok_count'] )->toBe( 1 )
			->and( $stats['poor_count'] )->toBe( 1 )
			->and( $stats['average_score'] )->toBe( 60 ); // (85+65+30)/3 = 60
	} );

	it( 'calculates percentages correctly', function (): void {
		$seoMeta1 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 1 ] );
		$seoMeta2 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 2 ] );
		$seoMeta3 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 3 ] );
		$seoMeta4 = SeoMeta::create( [ 'seoable_type' => 'App\Models\Post', 'seoable_id' => 4 ] );

		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta1->id, 'overall_score' => 85 ] );
		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta2->id, 'overall_score' => 90 ] );
		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta3->id, 'overall_score' => 65 ] );
		SeoAnalysisCache::create( [ 'seo_meta_id' => $seoMeta4->id, 'overall_score' => 30 ] );

		$stats = $this->service->getStatistics();

		expect( $stats['good_percentage'] )->toBe( 50.0 )   // 2/4
			->and( $stats['ok_percentage'] )->toBe( 25.0 )  // 1/4
			->and( $stats['poor_percentage'] )->toBe( 25.0 ); // 1/4
	} );

	it( 'counts stale and fresh analyses', function (): void {
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
			'analyzed_at'   => now(), // Fresh
		] );

		$stats = $this->service->getStatistics();

		expect( $stats['stale_count'] )->toBe( 1 )
			->and( $stats['fresh_count'] )->toBe( 1 );
	} );

} );
