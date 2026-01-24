<?php

/**
 * KeywordDensityAnalyzer Tests.
 *
 * Unit tests for the KeywordDensityAnalyzer.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\Analysis\KeywordDensityAnalyzer;
use Illuminate\Database\Eloquent\Model;

beforeEach( function (): void {
	$this->analyzer = new KeywordDensityAnalyzer();
} );

/**
 * Create a simple test model.
 */
function createKeywordDensityTestModel(): Model
{
	return new class extends Model {
		public string $title = 'Test Title';
	};
}

describe( 'KeywordDensityAnalyzer Interface', function (): void {

	it( 'returns correct name', function (): void {
		expect( $this->analyzer->getName() )->toBe( 'keyword_density' );
	} );

	it( 'returns correct category', function (): void {
		expect( $this->analyzer->getCategory() )->toBe( 'keyword' );
	} );

	it( 'returns correct weight', function (): void {
		expect( $this->analyzer->getWeight() )->toBe( 50 );
	} );

} );

describe( 'KeywordDensityAnalyzer No Keyword', function (): void {

	it( 'returns warning when no focus keyword is set', function (): void {
		$model  = createKeywordDensityTestModel();
		$result = $this->analyzer->analyze( $model, 'Some content here.', null, null );

		expect( $result['score'] )->toBe( 0 )
			->and( $result['issues'] )->toHaveCount( 1 )
			->and( $result['issues'][0]['message'] )->toContain( 'No focus keyword' );
	} );

	it( 'returns warning for empty keyword', function (): void {
		$model  = createKeywordDensityTestModel();
		$result = $this->analyzer->analyze( $model, 'Some content here.', '   ', null );

		expect( $result['score'] )->toBe( 0 );
	} );

} );

describe( 'KeywordDensityAnalyzer Empty Content', function (): void {

	it( 'handles empty content', function (): void {
		$model  = createKeywordDensityTestModel();
		$result = $this->analyzer->analyze( $model, '', 'keyword', null );

		expect( $result['score'] )->toBe( 0 )
			->and( $result['issues'] )->toHaveCount( 1 )
			->and( $result['issues'][0]['type'] )->toBe( 'error' );
	} );

} );

describe( 'KeywordDensityAnalyzer Density Calculation', function (): void {

	it( 'calculates density correctly', function (): void {
		$model = createKeywordDensityTestModel();
		// 100 words, keyword appears 2 times = 2% density
		$words   = array_fill( 0, 96, 'word' );
		$content = 'keyword ' . implode( ' ', $words ) . ' keyword ' . implode( ' ', array_slice( $words, 0, 2 ) );
		$result  = $this->analyzer->analyze( $model, $content, 'keyword', null );

		expect( $result['details']['density'] )->toBeGreaterThan( 0 )
			->and( $result['details']['occurrences'] )->toBeGreaterThanOrEqual( 2 );
	} );

	it( 'warns when density is too low', function (): void {
		$model = createKeywordDensityTestModel();
		// 1000 words with keyword appearing once = 0.1% density (too low)
		$words   = array_fill( 0, 999, 'word' );
		$content = 'keyword ' . implode( ' ', $words );
		$result  = $this->analyzer->analyze( $model, $content, 'keyword', null );

		$hasDensityWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'too low' ) ) {
				$hasDensityWarning = true;
				break;
			}
		}

		expect( $hasDensityWarning )->toBeTrue();
	} );

	it( 'warns when density is too high', function (): void {
		$model = createKeywordDensityTestModel();
		// 100 words with keyword appearing 10 times = 10% density (too high)
		$content = str_repeat( 'keyword word word word word word word word word keyword ', 5 );
		$result  = $this->analyzer->analyze( $model, $content, 'keyword', null );

		$hasDensityWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'too high' ) ) {
				$hasDensityWarning = true;
				break;
			}
		}

		expect( $hasDensityWarning )->toBeTrue();
	} );

	it( 'passes with ideal density', function (): void {
		$model = createKeywordDensityTestModel();
		// To achieve ~1.5% density: 2 keywords / X words * 100 = 1.5
		// X = 2 * 100 / 1.5 = 133 words total
		// So we need 131 filler words + 2 keywords = 133 words
		$words   = array_fill( 0, 131, 'word' );
		$content = 'keyword ' . implode( ' ', $words ) . ' keyword';
		$result  = $this->analyzer->analyze( $model, $content, 'keyword', null );

		// Density should be within 0.5-2.5% range for high score
		expect( $result['details']['density'] )->toBeGreaterThanOrEqual( 0.5 )
			->and( $result['details']['density'] )->toBeLessThanOrEqual( 2.5 )
			->and( $result['score'] )->toBeGreaterThanOrEqual( 80 );
	} );

} );

describe( 'KeywordDensityAnalyzer First Paragraph', function (): void {

	it( 'passes when keyword is in first paragraph', function (): void {
		$model   = createKeywordDensityTestModel();
		$content = '<p>This keyword is in the first paragraph.</p><p>Second paragraph here.</p>';
		$result  = $this->analyzer->analyze( $model, $content, 'keyword', null );

		expect( $result['passed'] )->toContain( 'Focus keyword appears in the first paragraph.' );
	} );

	it( 'suggests adding keyword to first paragraph', function (): void {
		$model   = createKeywordDensityTestModel();
		$content = '<p>First paragraph without the target word.</p><p>The keyword is here.</p>';
		$result  = $this->analyzer->analyze( $model, $content, 'keyword', null );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'first paragraph' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue();
	} );

} );

describe( 'KeywordDensityAnalyzer Details', function (): void {

	it( 'returns detailed analysis information', function (): void {
		$model   = createKeywordDensityTestModel();
		$content = 'This content has the keyword in it. The keyword appears twice.';
		$result  = $this->analyzer->analyze( $model, $content, 'keyword', null );

		expect( $result['details'] )->toHaveKey( 'keyword' )
			->and( $result['details'] )->toHaveKey( 'occurrences' )
			->and( $result['details'] )->toHaveKey( 'density' )
			->and( $result['details'] )->toHaveKey( 'word_count' )
			->and( $result['details'] )->toHaveKey( 'in_first_paragraph' );
	} );

} );
