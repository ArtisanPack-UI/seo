<?php

/**
 * SeoAnalysisPanel Livewire Component Tests.
 *
 * Feature tests for the SeoAnalysisPanel Livewire component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Livewire\SeoAnalysisPanel;
use Illuminate\View\View;
use Livewire\Livewire;

/**
 * Test version of SeoAnalysisPanel that uses a simplified view for testing.
 */
class TestSeoAnalysisPanel extends SeoAnalysisPanel
{
	/**
	 * Render the component with a simplified test view.
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'test-seo-analysis-panel' );
	}
}

beforeEach( function (): void {
	// Register the test view location
	$this->app['view']->addLocation( __DIR__ . '/../../stubs/views/livewire' );
} );

describe( 'SeoAnalysisPanel Component Mounting', function (): void {

	it( 'mounts with empty analysis', function (): void {
		Livewire::test( TestSeoAnalysisPanel::class )
			->assertSet( 'analysis', [] )
			->assertSet( 'expanded', false );
	} );

	it( 'mounts with analysis data', function (): void {
		$analysis = [
			'overall_score'     => 75,
			'readability_score' => 80,
			'keyword_score'     => 70,
			'meta_score'        => 85,
			'content_score'     => 65,
		];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSet( 'analysis', $analysis )
			->assertSee( '75' );
	} );

	it( 'mounts with expanded state', function (): void {
		Livewire::test( TestSeoAnalysisPanel::class, [ 'expanded' => true ] )
			->assertSet( 'expanded', true );
	} );

} );

describe( 'SeoAnalysisPanel Toggle Functionality', function (): void {

	it( 'toggles expanded state', function (): void {
		Livewire::test( TestSeoAnalysisPanel::class )
			->assertSet( 'expanded', false )
			->call( 'toggle' )
			->assertSet( 'expanded', true )
			->call( 'toggle' )
			->assertSet( 'expanded', false );
	} );

	it( 'expands the panel', function (): void {
		Livewire::test( TestSeoAnalysisPanel::class )
			->assertSet( 'expanded', false )
			->call( 'expand' )
			->assertSet( 'expanded', true );
	} );

	it( 'collapses the panel', function (): void {
		Livewire::test( TestSeoAnalysisPanel::class, [ 'expanded' => true ] )
			->assertSet( 'expanded', true )
			->call( 'collapse' )
			->assertSet( 'expanded', false );
	} );

} );

describe( 'SeoAnalysisPanel Score Computed Properties', function (): void {

	it( 'computes overall score from analysis', function (): void {
		$analysis = [ 'overall_score' => 85 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSee( '85' );
	} );

	it( 'returns zero for missing overall score', function (): void {
		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => [] ] )
			->assertSee( '0' );
	} );

	it( 'computes readability score', function (): void {
		$analysis = [ 'readability_score' => 72 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="readability-score">72' );
	} );

	it( 'computes keyword score', function (): void {
		$analysis = [ 'keyword_score' => 65 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="keyword-score">65' );
	} );

	it( 'computes meta score', function (): void {
		$analysis = [ 'meta_score' => 90 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="meta-score">90' );
	} );

	it( 'computes content score', function (): void {
		$analysis = [ 'content_score' => 55 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="content-score">55' );
	} );

} );

describe( 'SeoAnalysisPanel Score Color', function (): void {

	it( 'returns success color for good score (>= 70)', function (): void {
		$analysis = [ 'overall_score' => 75 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="score-color">success' );
	} );

	it( 'returns warning color for ok score (40-69)', function (): void {
		$analysis = [ 'overall_score' => 55 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="score-color">warning' );
	} );

	it( 'returns error color for poor score (< 40)', function (): void {
		$analysis = [ 'overall_score' => 25 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="score-color">error' );
	} );

	it( 'returns error color for zero score', function (): void {
		$analysis = [ 'overall_score' => 0 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="score-color">error' );
	} );

	it( 'returns success color for perfect score', function (): void {
		$analysis = [ 'overall_score' => 100 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="score-color">success' );
	} );

} );

describe( 'SeoAnalysisPanel Score Label', function (): void {

	it( 'returns Good label for high score', function (): void {
		$analysis = [ 'overall_score' => 85 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="score-label">Good' );
	} );

	it( 'returns Needs Improvement label for medium score', function (): void {
		$analysis = [ 'overall_score' => 50 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="score-label">Needs Improvement' );
	} );

	it( 'returns Poor label for low score', function (): void {
		$analysis = [ 'overall_score' => 20 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="score-label">Poor' );
	} );

} );

describe( 'SeoAnalysisPanel Category Colors', function (): void {

	it( 'returns correct color for good readability score', function (): void {
		$analysis = [ 'readability_score' => 80 ];

		$component = Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] );

		expect( $component->instance()->getCategoryColor( 'readability' ) )->toBe( 'success' );
	} );

	it( 'returns correct color for medium keyword score', function (): void {
		$analysis = [ 'keyword_score' => 55 ];

		$component = Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] );

		expect( $component->instance()->getCategoryColor( 'keyword' ) )->toBe( 'warning' );
	} );

	it( 'returns correct color for poor meta score', function (): void {
		$analysis = [ 'meta_score' => 30 ];

		$component = Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] );

		expect( $component->instance()->getCategoryColor( 'meta' ) )->toBe( 'error' );
	} );

	it( 'returns error color for unknown category', function (): void {
		$analysis = [];

		$component = Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] );

		expect( $component->instance()->getCategoryColor( 'unknown' ) )->toBe( 'error' );
	} );

} );

describe( 'SeoAnalysisPanel Issues', function (): void {

	it( 'displays issues from analysis', function (): void {
		$analysis = [
			'issues' => [
				[ 'message' => 'Missing meta description' ],
				[ 'message' => 'Title too short' ],
			],
		];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="has-issues">true' )
			->assertSeeHtml( 'data-test="issue-count">2' );
	} );

	it( 'handles empty issues array', function (): void {
		$analysis = [ 'issues' => [] ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="has-issues">false' )
			->assertSeeHtml( 'data-test="issue-count">0' );
	} );

	it( 'handles missing issues key', function (): void {
		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => [] ] )
			->assertSeeHtml( 'data-test="has-issues">false' )
			->assertSeeHtml( 'data-test="issue-count">0' );
	} );

} );

describe( 'SeoAnalysisPanel Suggestions', function (): void {

	it( 'displays suggestions from analysis', function (): void {
		$analysis = [
			'suggestions' => [
				[ 'message' => 'Consider adding more content' ],
				[ 'message' => 'Add internal links' ],
				[ 'message' => 'Optimize images' ],
			],
		];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="has-suggestions">true' )
			->assertSeeHtml( 'data-test="suggestion-count">3' );
	} );

	it( 'handles empty suggestions array', function (): void {
		$analysis = [ 'suggestions' => [] ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="has-suggestions">false' )
			->assertSeeHtml( 'data-test="suggestion-count">0' );
	} );

	it( 'handles missing suggestions key', function (): void {
		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => [] ] )
			->assertSeeHtml( 'data-test="has-suggestions">false' )
			->assertSeeHtml( 'data-test="suggestion-count">0' );
	} );

} );

describe( 'SeoAnalysisPanel Passed Checks', function (): void {

	it( 'displays passed checks from analysis', function (): void {
		$analysis = [
			'passed_checks' => [
				'Meta title is present',
				'Meta description is present',
				'Focus keyword found in title',
			],
		];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="has-passed-checks">true' )
			->assertSeeHtml( 'data-test="passed-check-count">3' );
	} );

	it( 'handles empty passed checks array', function (): void {
		$analysis = [ 'passed_checks' => [] ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="has-passed-checks">false' )
			->assertSeeHtml( 'data-test="passed-check-count">0' );
	} );

	it( 'handles missing passed checks key', function (): void {
		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => [] ] )
			->assertSeeHtml( 'data-test="has-passed-checks">false' )
			->assertSeeHtml( 'data-test="passed-check-count">0' );
	} );

} );

describe( 'SeoAnalysisPanel Category Scores Computed', function (): void {

	it( 'returns all category scores with labels and colors', function (): void {
		$analysis = [
			'readability_score' => 80,
			'keyword_score'     => 55,
			'meta_score'        => 30,
			'content_score'     => 90,
		];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="category-readability"' )
			->assertSeeHtml( 'data-score="80"' )
			->assertSeeHtml( 'data-color="success"' )
			->assertSeeHtml( 'data-test="category-keyword"' )
			->assertSeeHtml( 'data-score="55"' )
			->assertSeeHtml( 'data-color="warning"' )
			->assertSeeHtml( 'data-test="category-meta"' )
			->assertSeeHtml( 'data-score="30"' )
			->assertSeeHtml( 'data-color="error"' )
			->assertSeeHtml( 'data-test="category-content"' )
			->assertSeeHtml( 'data-score="90"' );
	} );

} );

describe( 'SeoAnalysisPanel Full Analysis', function (): void {

	it( 'handles complete analysis data', function (): void {
		$analysis = [
			'overall_score'     => 72,
			'readability_score' => 75,
			'keyword_score'     => 68,
			'meta_score'        => 80,
			'content_score'     => 65,
			'issues'            => [
				[ 'message' => 'Focus keyword not in first paragraph' ],
			],
			'suggestions'       => [
				[ 'message' => 'Consider adding more headings' ],
				[ 'message' => 'Add more internal links' ],
			],
			'passed_checks'     => [
				'Meta title present and optimal length',
				'Meta description present',
				'Focus keyword in title',
			],
		];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="overall-score">72' )
			->assertSeeHtml( 'data-test="score-color">success' )
			->assertSeeHtml( 'data-test="score-label">Good' )
			->assertSeeHtml( 'data-test="issue-count">1' )
			->assertSeeHtml( 'data-test="suggestion-count">2' )
			->assertSeeHtml( 'data-test="passed-check-count">3' );
	} );

	it( 'maintains state after analysis update', function (): void {
		$initialAnalysis = [
			'overall_score' => 50,
			'issues'        => [ [ 'message' => 'Issue 1' ] ],
		];

		$updatedAnalysis = [
			'overall_score'  => 75,
			'issues'         => [],
			'passed_checks'  => [ 'Issue 1 resolved' ],
		];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $initialAnalysis ] )
			->assertSeeHtml( 'data-test="overall-score">50' )
			->assertSeeHtml( 'data-test="score-color">warning' )
			->assertSeeHtml( 'data-test="issue-count">1' )
			->set( 'analysis', $updatedAnalysis )
			->assertSeeHtml( 'data-test="overall-score">75' )
			->assertSeeHtml( 'data-test="score-color">success' )
			->assertSeeHtml( 'data-test="issue-count">0' );
	} );

} );

describe( 'SeoAnalysisPanel Edge Cases', function (): void {

	it( 'handles score at exact boundary (70)', function (): void {
		$analysis = [ 'overall_score' => 70 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="score-color">success' )
			->assertSeeHtml( 'data-test="score-label">Good' );
	} );

	it( 'handles score at exact boundary (40)', function (): void {
		$analysis = [ 'overall_score' => 40 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="score-color">warning' )
			->assertSeeHtml( 'data-test="score-label">Needs Improvement' );
	} );

	it( 'handles score just below boundary (69)', function (): void {
		$analysis = [ 'overall_score' => 69 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="score-color">warning' )
			->assertSeeHtml( 'data-test="score-label">Needs Improvement' );
	} );

	it( 'handles score just below boundary (39)', function (): void {
		$analysis = [ 'overall_score' => 39 ];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="score-color">error' )
			->assertSeeHtml( 'data-test="score-label">Poor' );
	} );

	it( 'handles string issues and passed checks', function (): void {
		$analysis = [
			'issues'        => [
				'String issue format',
			],
			'passed_checks' => [
				'String passed check format',
			],
		];

		Livewire::test( TestSeoAnalysisPanel::class, [ 'analysis' => $analysis ] )
			->assertSeeHtml( 'data-test="issue-count">1' )
			->assertSeeHtml( 'data-test="passed-check-count">1' );
	} );

} );
