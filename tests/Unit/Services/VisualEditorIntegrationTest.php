<?php

/**
 * VisualEditorIntegration Tests.
 *
 * Unit tests for the VisualEditorIntegration service.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\AnalysisResultDTO;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\AnalysisService;
use ArtisanPackUI\SEO\Services\VisualEditorIntegration;
use ArtisanPackUI\SEO\Support\PackageDetector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Test model with seoMeta relationship for testing.
 */
class TestModelWithSeoMeta extends Model
{
	public ?SeoMeta $seoMetaValue = null;

	public function seoMeta(): ?SeoMeta
	{
		return $this->seoMetaValue;
	}

	public function getSeoMetaAttribute(): ?SeoMeta
	{
		return $this->seoMetaValue;
	}
}

beforeEach( function (): void {
	$this->analysisService = Mockery::mock( AnalysisService::class );
	$this->integration     = new VisualEditorIntegration( $this->analysisService );
} );

describe( 'VisualEditorIntegration Availability', function (): void {

	it( 'checks if visual editor package is available', function (): void {
		$result = $this->integration->isAvailable();

		// The result depends on whether the package is installed
		expect( $result )->toBeBool();
	} );

	it( 'returns same result as PackageDetector', function (): void {
		$integrationResult = $this->integration->isAvailable();
		$detectorResult    = PackageDetector::hasVisualEditor();

		expect( $integrationResult )->toBe( $detectorResult );
	} );

} );

describe( 'VisualEditorIntegration getSeoChecks', function (): void {

	it( 'returns a collection', function (): void {
		$page = Mockery::mock( Model::class );
		$page->shouldReceive( 'seoMeta' )->andReturn( null );

		$result = $this->integration->getSeoChecks( $page );

		expect( $result )->toBeInstanceOf( Collection::class );
	} );

	it( 'returns warning for missing meta title when no SEO meta', function (): void {
		$page = Mockery::mock( Model::class );

		$result = $this->integration->getSeoChecks( $page );

		$messages = $result->pluck( 'message' )->toArray();

		expect( $messages )->toContain( __( 'Page is missing a meta title' ) );
	} );

	it( 'returns warning for missing meta description when no SEO meta', function (): void {
		$page = Mockery::mock( Model::class );

		$result = $this->integration->getSeoChecks( $page );

		$messages = $result->pluck( 'message' )->toArray();

		expect( $messages )->toContain( __( 'Page is missing a meta description' ) );
	} );

	it( 'returns suggestion for missing focus keyword when no SEO meta', function (): void {
		$page = Mockery::mock( Model::class );

		$result = $this->integration->getSeoChecks( $page );

		$messages = $result->pluck( 'message' )->toArray();

		expect( $messages )->toContain( __( 'Page is missing a focus keyword' ) );
	} );

	it( 'returns suggestion for missing OG image when no SEO meta', function (): void {
		$page = Mockery::mock( Model::class );

		$result = $this->integration->getSeoChecks( $page );

		$messages = $result->pluck( 'message' )->toArray();

		expect( $messages )->toContain( __( 'Page is missing a social sharing image' ) );
	} );

	it( 'checks have correct structure', function (): void {
		$page = Mockery::mock( Model::class );

		$result = $this->integration->getSeoChecks( $page );

		foreach ( $result as $check ) {
			expect( $check )->toHaveKey( 'type' )
				->and( $check )->toHaveKey( 'category' )
				->and( $check )->toHaveKey( 'message' )
				->and( $check )->toHaveKey( 'action' )
				->and( $check['category'] )->toBe( 'seo' );
		}
	} );

	it( 'includes valid check types', function (): void {
		$page = Mockery::mock( Model::class );

		$result = $this->integration->getSeoChecks( $page );

		$validTypes = [ 'warning', 'suggestion', 'info' ];

		foreach ( $result as $check ) {
			expect( $validTypes )->toContain( $check['type'] );
		}
	} );

} );

describe( 'VisualEditorIntegration getSeoChecks with SeoMeta', function (): void {

	it( 'does not return meta title warning when title exists', function (): void {
		$seoMeta = Mockery::mock( SeoMeta::class )->makePartial();
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'meta_title' )->andReturn( 'Test Title' );
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'meta_description' )->andReturn( null );
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'focus_keyword' )->andReturn( null );
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'og_image' )->andReturn( null );
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'og_image_id' )->andReturn( null );
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'no_index' )->andReturn( false );
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'analysisCache' )->andReturn(
			(object) [ 'overall_score' => 75 ],
		);

		$page                = new TestModelWithSeoMeta();
		$page->seoMetaValue  = $seoMeta;

		$result = $this->integration->getSeoChecks( $page );

		$messages = $result->pluck( 'message' )->toArray();

		expect( $messages )->not->toContain( __( 'Page is missing a meta title' ) );
	} );

	it( 'returns noindex info when noindex is enabled', function (): void {
		$seoMeta = Mockery::mock( SeoMeta::class )->makePartial();
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'meta_title' )->andReturn( 'Test Title' );
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'meta_description' )->andReturn( 'Test Description' );
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'focus_keyword' )->andReturn( 'test keyword' );
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'og_image' )->andReturn( 'https://example.com/image.jpg' );
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'og_image_id' )->andReturn( null );
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'no_index' )->andReturn( true );
		$seoMeta->shouldReceive( 'getAttribute' )->with( 'analysisCache' )->andReturn(
			(object) [ 'overall_score' => 75 ],
		);

		$page                = new TestModelWithSeoMeta();
		$page->seoMetaValue  = $seoMeta;

		$result = $this->integration->getSeoChecks( $page );

		$messages = $result->pluck( 'message' )->toArray();

		expect( $messages )->toContain( __( 'This page is set to noindex' ) );
	} );

} );

describe( 'VisualEditorIntegration analyzeForEditor', function (): void {

	it( 'returns array with required keys', function (): void {
		$analysisResult = new AnalysisResultDTO(
			overallScore: 75,
			readabilityScore: 80,
			keywordScore: 70,
			metaScore: 85,
			contentScore: 65,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: 'test keyword',
			wordCount: 500,
		);

		$this->analysisService
			->shouldReceive( 'analyze' )
			->once()
			->andReturn( $analysisResult );

		$page = Mockery::mock( Model::class );

		$result = $this->integration->analyzeForEditor( $page );

		expect( $result )->toHaveKeys( [
			'overall_score',
			'readability_score',
			'keyword_score',
			'meta_score',
			'content_score',
			'issues',
			'suggestions',
			'passed_checks',
			'focus_keyword',
			'word_count',
			'checks',
		] );
	} );

	it( 'returns correct score values', function (): void {
		$analysisResult = new AnalysisResultDTO(
			overallScore: 75,
			readabilityScore: 80,
			keywordScore: 70,
			metaScore: 85,
			contentScore: 65,
			issues: [ [ 'type' => 'error', 'message' => 'issue 1' ] ],
			suggestions: [ [ 'type' => 'info', 'message' => 'suggestion 1' ] ],
			passedChecks: [ 'check 1' ],
			focusKeyword: 'test keyword',
			wordCount: 500,
		);

		$this->analysisService
			->shouldReceive( 'analyze' )
			->once()
			->andReturn( $analysisResult );

		$page = Mockery::mock( Model::class );

		$result = $this->integration->analyzeForEditor( $page );

		expect( $result['overall_score'] )->toBe( 75 )
			->and( $result['readability_score'] )->toBe( 80 )
			->and( $result['keyword_score'] )->toBe( 70 )
			->and( $result['meta_score'] )->toBe( 85 )
			->and( $result['content_score'] )->toBe( 65 )
			->and( $result['focus_keyword'] )->toBe( 'test keyword' )
			->and( $result['word_count'] )->toBe( 500 );
	} );

	it( 'includes seo checks array', function (): void {
		$analysisResult = new AnalysisResultDTO(
			overallScore: 75,
			readabilityScore: 80,
			keywordScore: 70,
			metaScore: 85,
			contentScore: 65,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 0,
		);

		$this->analysisService
			->shouldReceive( 'analyze' )
			->once()
			->andReturn( $analysisResult );

		$page = Mockery::mock( Model::class );

		$result = $this->integration->analyzeForEditor( $page );

		expect( $result['checks'] )->toBeArray();
	} );

} );

describe( 'VisualEditorIntegration registerPrePublishChecks', function (): void {

	it( 'does nothing when visual editor not available', function (): void {
		// Create a partial mock
		$integration = Mockery::mock( VisualEditorIntegration::class, [ $this->analysisService ] )
			->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		// This should not throw an error
		$integration->registerPrePublishChecks();

		expect( true )->toBeTrue();
	} );

} );
