<?php

/**
 * Analysis API Tests.
 *
 * Feature tests for SEO analysis API endpoints.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoAnalysisCache;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses( RefreshDatabase::class );

/**
 * Test model for Analysis API tests.
 */
class AnalysisApiTestModel extends Model
{
	use HasSeo;

	protected $table = 'analysis_api_test_pages';

	protected $fillable = [
		'title',
		'content',
		'description',
	];
}

beforeEach( function (): void {
	Schema::create( 'analysis_api_test_pages', function ( Blueprint $table ): void {
		$table->id();
		$table->string( 'title' )->nullable();
		$table->text( 'content' )->nullable();
		$table->text( 'description' )->nullable();
		$table->timestamps();
	} );

	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../../database/migrations' ) ] );

	Illuminate\Database\Eloquent\Relations\Relation::morphMap( [
		'analysis_api_test_page' => AnalysisApiTestModel::class,
	] );

	config( [ 'seo.analysis.cache_enabled' => false ] );

	$this->withoutMiddleware( Illuminate\Auth\Middleware\Authenticate::class );
} );

afterEach( function (): void {
	Schema::dropIfExists( 'analysis_api_test_pages' );
} );

describe( 'POST /api/seo/analysis/analyze', function (): void {

	it( 'runs analysis on a model', function (): void {
		$page = AnalysisApiTestModel::create( [
			'title'   => 'Test Page',
			'content' => 'This is some test content for analysis purposes.',
		] );

		$response = $this->postJson( '/api/seo/analysis/analyze', [
			'model_type' => 'analysis_api_test_page',
			'model_id'   => $page->id,
		] );

		$response->assertOk()
			->assertJsonStructure( [
				'data' => [
					'overall_score',
					'grade',
					'grade_label',
					'readability_score',
					'keyword_score',
					'meta_score',
					'content_score',
					'issues',
					'suggestions',
					'passed_checks',
					'word_count',
				],
			] );
	} );

	it( 'accepts optional focus keyword', function (): void {
		$page = AnalysisApiTestModel::create( [
			'title'   => 'Test Page',
			'content' => 'Some content here.',
		] );

		$response = $this->postJson( '/api/seo/analysis/analyze', [
			'model_type'    => 'analysis_api_test_page',
			'model_id'      => $page->id,
			'focus_keyword' => 'test keyword',
		] );

		$response->assertOk()
			->assertJsonPath( 'data.focus_keyword', 'test keyword' );
	} );

	it( 'returns 404 for non-existent model', function (): void {
		$response = $this->postJson( '/api/seo/analysis/analyze', [
			'model_type' => 'analysis_api_test_page',
			'model_id'   => 999,
		] );

		$response->assertNotFound();
	} );

	it( 'validates required fields', function (): void {
		$response = $this->postJson( '/api/seo/analysis/analyze', [] );

		$response->assertUnprocessable()
			->assertJsonValidationErrors( [ 'model_type', 'model_id' ] );
	} );

	it( 'returns 404 for invalid model type', function (): void {
		$response = $this->postJson( '/api/seo/analysis/analyze', [
			'model_type' => 'nonexistent_model',
			'model_id'   => 1,
		] );

		$response->assertNotFound();
	} );

	it( 'returns non-zero scores when content is present', function (): void {
		$page = AnalysisApiTestModel::create( [
			'title'   => 'Comprehensive Guide to API Design Best Practices',
			'content' => '<h1>API Design Best Practices</h1>
				<p>Building well-designed APIs is essential for modern software development. A good API should be intuitive, consistent, and well-documented. This guide covers the key principles that every developer should follow when designing RESTful APIs.</p>
				<h2>Use Meaningful Resource Names</h2>
				<p>Choose resource names that clearly describe the data they represent. Use plural nouns for collections and keep URLs clean and readable. Avoid using verbs in URLs since HTTP methods already convey the action.</p>
				<h2>Implement Proper Error Handling</h2>
				<p>Return appropriate HTTP status codes and include helpful error messages. Clients should be able to programmatically handle errors without parsing error strings. Use a consistent error response format across all endpoints.</p>
				<p>Good error handling improves the developer experience and makes debugging much easier for consumers of your API.</p>',
		] );

		$page->seoMeta()->create( [
			'meta_title'       => 'API Design Best Practices Guide',
			'meta_description' => 'Learn the essential best practices for designing modern RESTful APIs including resource naming, error handling, and documentation.',
			'focus_keyword'    => 'API design',
		] );

		$response = $this->postJson( '/api/seo/analysis/analyze', [
			'model_type'    => 'analysis_api_test_page',
			'model_id'      => $page->id,
			'focus_keyword' => 'API design',
		] );

		$response->assertOk();

		$data = $response->json( 'data' );

		expect( $data['overall_score'] )->toBeGreaterThan( 0 )
			->and( $data['analyzer_results'] )->not->toBeEmpty()
			->and( $data['word_count'] )->toBeGreaterThan( 0 );
	} );
} );

describe( 'GET /api/seo/analysis/{modelType}/{modelId}', function (): void {

	it( 'returns null when no cached results exist', function (): void {
		$page = AnalysisApiTestModel::create( [ 'title' => 'Test Page' ] );

		$response = $this->getJson( '/api/seo/analysis/analysis_api_test_page/' . $page->id );

		$response->assertOk()
			->assertJson( [ 'data' => null ] );
	} );

	it( 'returns cached analysis results when they exist', function (): void {
		$page    = AnalysisApiTestModel::create( [ 'title' => 'Test Page' ] );
		$seoMeta = $page->seoMeta()->create( [
			'meta_title' => 'Test Title',
		] );

		SeoAnalysisCache::create( [
			'seo_meta_id'        => $seoMeta->id,
			'overall_score'      => 75,
			'readability_score'  => 80,
			'keyword_score'      => 70,
			'meta_score'         => 75,
			'content_score'      => 72,
			'issues'             => [],
			'suggestions'        => [],
			'passed_checks'      => [],
			'analyzer_results'   => [],
			'analyzed_at'        => now(),
			'focus_keyword_used' => 'test',
			'content_word_count' => 500,
		] );

		$response = $this->getJson( '/api/seo/analysis/analysis_api_test_page/' . $page->id );

		$response->assertOk()
			->assertJsonPath( 'data.overall_score', 75 )
			->assertJsonPath( 'data.grade', 'ok' );
	} );

	it( 'returns 404 for non-existent model', function (): void {
		$response = $this->getJson( '/api/seo/analysis/analysis_api_test_page/999' );

		$response->assertNotFound();
	} );
} );
