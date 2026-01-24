<?php

/**
 * ContentLengthAnalyzer Tests.
 *
 * Unit tests for the ContentLengthAnalyzer.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\Analysis\ContentLengthAnalyzer;
use Illuminate\Database\Eloquent\Model;

beforeEach( function (): void {
	$this->analyzer = new ContentLengthAnalyzer();
} );

/**
 * Create a simple test model.
 */
function createContentLengthTestModel(): Model
{
	return new class extends Model {
		public string $title = 'Test Title';
	};
}

/**
 * Generate content with specific word count.
 */
function generateContent( int $wordCount ): string
{
	$words = array_fill( 0, $wordCount, 'word' );

	return implode( ' ', $words );
}

describe( 'ContentLengthAnalyzer Interface', function (): void {

	it( 'returns correct name', function (): void {
		expect( $this->analyzer->getName() )->toBe( 'content_length' );
	} );

	it( 'returns correct category', function (): void {
		expect( $this->analyzer->getCategory() )->toBe( 'content' );
	} );

	it( 'returns correct weight', function (): void {
		expect( $this->analyzer->getWeight() )->toBe( 25 );
	} );

} );

describe( 'ContentLengthAnalyzer Short Content', function (): void {

	it( 'warns when content is below minimum', function (): void {
		$model   = createContentLengthTestModel();
		$content = generateContent( 100 ); // Below 300 minimum
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'too short' ) ) {
				$hasWarning = true;
				break;
			}
		}

		expect( $hasWarning )->toBeTrue()
			->and( $result['details']['rating'] )->toBe( 'poor' )
			->and( $result['score'] )->toBeLessThan( 60 );
	} );

	it( 'calculates proportional score for short content', function (): void {
		$model   = createContentLengthTestModel();
		$content = generateContent( 150 ); // 50% of minimum
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		// Should be around 25 (50% of 50 max score for short content)
		expect( $result['score'] )->toBeGreaterThanOrEqual( 20 )
			->and( $result['score'] )->toBeLessThanOrEqual( 30 );
	} );

} );

describe( 'ContentLengthAnalyzer Acceptable Content', function (): void {

	it( 'suggests expanding acceptable content', function (): void {
		$model   = createContentLengthTestModel();
		$content = generateContent( 400 ); // Between 300 and 600
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'acceptable' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue()
			->and( $result['details']['rating'] )->toBe( 'acceptable' )
			->and( $result['score'] )->toBeGreaterThanOrEqual( 60 )
			->and( $result['score'] )->toBeLessThan( 80 );
	} );

} );

describe( 'ContentLengthAnalyzer Good Content', function (): void {

	it( 'passes for good content length', function (): void {
		$model   = createContentLengthTestModel();
		$content = generateContent( 700 ); // Between 600 and 1000
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasPassed = false;
		foreach ( $result['passed'] as $passed ) {
			if ( str_contains( $passed, 'Good content length' ) ) {
				$hasPassed = true;
				break;
			}
		}

		expect( $hasPassed )->toBeTrue()
			->and( $result['details']['rating'] )->toBe( 'good' )
			->and( $result['score'] )->toBeGreaterThanOrEqual( 80 )
			->and( $result['score'] )->toBeLessThan( 95 );
	} );

} );

describe( 'ContentLengthAnalyzer Excellent Content', function (): void {

	it( 'passes for excellent content length', function (): void {
		$model   = createContentLengthTestModel();
		$content = generateContent( 1200 ); // Over 1000
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasPassed = false;
		foreach ( $result['passed'] as $passed ) {
			if ( str_contains( $passed, 'Excellent content length' ) ) {
				$hasPassed = true;
				break;
			}
		}

		expect( $hasPassed )->toBeTrue()
			->and( $result['details']['rating'] )->toBe( 'excellent' )
			->and( $result['score'] )->toBeGreaterThanOrEqual( 95 );
	} );

	it( 'caps score at 100', function (): void {
		$model   = createContentLengthTestModel();
		$content = generateContent( 5000 ); // Very long content
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['score'] )->toBe( 100 );
	} );

} );

describe( 'ContentLengthAnalyzer HTML Handling', function (): void {

	it( 'strips HTML tags before counting', function (): void {
		$model   = createContentLengthTestModel();
		$content = '<p><strong>One</strong> <em>two</em> three.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['word_count'] )->toBe( 3 );
	} );

} );

describe( 'ContentLengthAnalyzer Details', function (): void {

	it( 'returns detailed information', function (): void {
		$model   = createContentLengthTestModel();
		$content = generateContent( 500 );
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details'] )->toHaveKey( 'word_count' )
			->and( $result['details'] )->toHaveKey( 'min_words' )
			->and( $result['details'] )->toHaveKey( 'good_words' )
			->and( $result['details'] )->toHaveKey( 'excellent_words' )
			->and( $result['details'] )->toHaveKey( 'rating' )
			->and( $result['details']['word_count'] )->toBe( 500 );
	} );

} );
