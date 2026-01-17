<?php

/**
 * HeadingStructureAnalyzer Tests.
 *
 * Unit tests for the HeadingStructureAnalyzer.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\Analysis\HeadingStructureAnalyzer;
use Illuminate\Database\Eloquent\Model;

beforeEach( function (): void {
	$this->analyzer = new HeadingStructureAnalyzer();
} );

/**
 * Create a simple test model.
 */
function createHeadingTestModel(): Model
{
	return new class extends Model {
		public string $title = 'Test Title';
	};
}

describe( 'HeadingStructureAnalyzer Interface', function (): void {

	it( 'returns correct name', function (): void {
		expect( $this->analyzer->getName() )->toBe( 'heading_structure' );
	} );

	it( 'returns correct category', function (): void {
		expect( $this->analyzer->getCategory() )->toBe( 'content' );
	} );

	it( 'returns correct weight', function (): void {
		expect( $this->analyzer->getWeight() )->toBe( 25 );
	} );

} );

describe( 'HeadingStructureAnalyzer H1 Analysis', function (): void {

	it( 'errors when no H1 is found', function (): void {
		$model   = createHeadingTestModel();
		$content = '<h2>Subheading</h2><p>Content here.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasError = false;
		foreach ( $result['issues'] as $issue ) {
			if ( 'error' === $issue['type'] && str_contains( $issue['message'], 'No H1' ) ) {
				$hasError = true;
				break;
			}
		}

		expect( $hasError )->toBeTrue()
			->and( $result['details']['h1_count'] )->toBe( 0 );
	} );

	it( 'warns when multiple H1s are found', function (): void {
		$model   = createHeadingTestModel();
		$content = '<h1>First H1</h1><h1>Second H1</h1><p>Content.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'Multiple H1' ) ) {
				$hasWarning = true;
				break;
			}
		}

		expect( $hasWarning )->toBeTrue()
			->and( $result['details']['h1_count'] )->toBe( 2 );
	} );

	it( 'passes when exactly one H1 exists', function (): void {
		$model   = createHeadingTestModel();
		$content = '<h1>Single H1</h1><p>Content here.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['passed'] )->toContain( 'Page has exactly one H1 heading.' )
			->and( $result['details']['h1_count'] )->toBe( 1 );
	} );

} );

describe( 'HeadingStructureAnalyzer Subheadings', function (): void {

	it( 'suggests using subheadings when none exist', function (): void {
		$model   = createHeadingTestModel();
		$content = '<h1>Main Title</h1><p>Content without any subheadings.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'No subheadings' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue()
			->and( $result['details']['total_subheadings'] )->toBe( 0 );
	} );

	it( 'passes when subheadings are used', function (): void {
		$model   = createHeadingTestModel();
		$content = '<h1>Main</h1><h2>Section 1</h2><h2>Section 2</h2><p>Content.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasPassed = false;
		foreach ( $result['passed'] as $passed ) {
			if ( str_contains( $passed, 'subheading' ) ) {
				$hasPassed = true;
				break;
			}
		}

		expect( $hasPassed )->toBeTrue()
			->and( $result['details']['h2_count'] )->toBe( 2 );
	} );

	it( 'counts all heading levels', function (): void {
		$model   = createHeadingTestModel();
		$content = '<h1>H1</h1><h2>H2</h2><h3>H3</h3><h4>H4</h4><h5>H5</h5><h6>H6</h6>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['h1_count'] )->toBe( 1 )
			->and( $result['details']['h2_count'] )->toBe( 1 )
			->and( $result['details']['h3_count'] )->toBe( 1 )
			->and( $result['details']['h4_count'] )->toBe( 1 )
			->and( $result['details']['h5_count'] )->toBe( 1 )
			->and( $result['details']['h6_count'] )->toBe( 1 )
			->and( $result['details']['total_subheadings'] )->toBe( 5 );
	} );

} );

describe( 'HeadingStructureAnalyzer Hierarchy', function (): void {

	it( 'passes when hierarchy is correct', function (): void {
		$model   = createHeadingTestModel();
		$content = '<h1>H1</h1><h2>H2</h2><h3>H3</h3><h2>Another H2</h2>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['passed'] )->toContain( 'Heading hierarchy is properly structured.' );
	} );

	it( 'warns when hierarchy skips levels', function (): void {
		$model   = createHeadingTestModel();
		$content = '<h1>H1</h1><h3>H3 without H2</h3>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'skipped' ) ) {
				$hasWarning = true;
				break;
			}
		}

		expect( $hasWarning )->toBeTrue();
	} );

	it( 'warns when H3/H4 used without H2', function (): void {
		$model   = createHeadingTestModel();
		$content = '<h1>H1</h1><h4>H4 directly</h4>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'skipped H2' ) ) {
				$hasWarning = true;
				break;
			}
		}

		expect( $hasWarning )->toBeTrue();
	} );

} );

describe( 'HeadingStructureAnalyzer Long Content', function (): void {

	it( 'suggests subheadings for long content without H2s', function (): void {
		$model   = createHeadingTestModel();
		$words   = array_fill( 0, 400, 'word' );
		$content = '<h1>Title</h1><p>' . implode( ' ', $words ) . '</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'Long content without subheadings' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue();
	} );

} );

describe( 'HeadingStructureAnalyzer Score', function (): void {

	it( 'returns high score for well-structured content', function (): void {
		$model   = createHeadingTestModel();
		$content = '<h1>Main Title</h1><h2>Section 1</h2><p>Content.</p><h2>Section 2</h2><p>More content.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['score'] )->toBeGreaterThanOrEqual( 90 );
	} );

	it( 'reduces score for missing H1', function (): void {
		$model   = createHeadingTestModel();
		$content = '<h2>Only Subheading</h2><p>Content.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['score'] )->toBeLessThanOrEqual( 75 );
	} );

} );
