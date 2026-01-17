<?php

/**
 * ReadabilityAnalyzer Tests.
 *
 * Unit tests for the ReadabilityAnalyzer.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\Analysis\ReadabilityAnalyzer;
use Illuminate\Database\Eloquent\Model;

beforeEach( function (): void {
	$this->analyzer = new ReadabilityAnalyzer();
} );

/**
 * Create a simple test model for analysis.
 */
function createReadabilityTestModel(): Model
{
	return new class extends Model {
		public string $title = 'Test Title';
	};
}

describe( 'ReadabilityAnalyzer Interface', function (): void {

	it( 'returns correct name', function (): void {
		expect( $this->analyzer->getName() )->toBe( 'readability' );
	} );

	it( 'returns correct category', function (): void {
		expect( $this->analyzer->getCategory() )->toBe( 'readability' );
	} );

	it( 'returns correct weight', function (): void {
		expect( $this->analyzer->getWeight() )->toBe( 100 );
	} );

} );

describe( 'ReadabilityAnalyzer Empty Content', function (): void {

	it( 'handles empty content', function (): void {
		$model  = createReadabilityTestModel();
		$result = $this->analyzer->analyze( $model, '', null, null );

		expect( $result['score'] )->toBe( 0 )
			->and( $result['issues'] )->toHaveCount( 1 )
			->and( $result['issues'][0]['type'] )->toBe( 'error' );
	} );

	it( 'handles whitespace-only content', function (): void {
		$model  = createReadabilityTestModel();
		$result = $this->analyzer->analyze( $model, '   ', null, null );

		expect( $result['score'] )->toBe( 0 );
	} );

} );

describe( 'ReadabilityAnalyzer Flesch Score', function (): void {

	it( 'calculates high score for simple content', function (): void {
		$model   = createReadabilityTestModel();
		$content = 'The cat sat on the mat. It was a nice day. The sun was warm. Birds sang in the trees. Life was good.';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		// Simple content should have high readability score (60+)
		expect( $result['score'] )->toBeGreaterThanOrEqual( 60 )
			->and( $result['details']['flesch_reading_ease'] )->toBeGreaterThanOrEqual( 60 );
	} );

	it( 'calculates lower score for complex content', function (): void {
		$model   = createReadabilityTestModel();
		$content = 'The implementation of sophisticated algorithmic methodologies necessitates comprehensive understanding of computational paradigms and their manifestations within contemporary technological infrastructures, particularly when considering the ramifications of such implementations on organizational efficiency and operational effectiveness.';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		// Complex content should have lower readability score
		expect( $result['score'] )->toBeLessThan( 50 );
	} );

	it( 'returns details with flesch metrics', function (): void {
		$model   = createReadabilityTestModel();
		$content = 'This is a simple test. It has short sentences. The words are easy.';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details'] )->toHaveKey( 'flesch_reading_ease' )
			->and( $result['details'] )->toHaveKey( 'flesch_kincaid_grade' )
			->and( $result['details'] )->toHaveKey( 'avg_sentence_length' )
			->and( $result['details'] )->toHaveKey( 'avg_syllables_per_word' );
	} );

} );

describe( 'ReadabilityAnalyzer Sentence Length', function (): void {

	it( 'passes when sentences are appropriate length', function (): void {
		$model   = createReadabilityTestModel();
		$content = 'This is a normal sentence. Here is another one. They are both reasonable.';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['passed'] )->toContain( 'Sentence length is appropriate.' );
	} );

	it( 'warns about long sentences', function (): void {
		$model   = createReadabilityTestModel();
		// Create content where most sentences are too long (over 25 words each)
		$longSentence = 'This is a very long sentence that contains many words and keeps going on and on without stopping for quite a while because it needs to exceed twenty-five words to trigger the warning. ';
		$content      = $longSentence . $longSentence . $longSentence . $longSentence;
		$result       = $this->analyzer->analyze( $model, $content, null, null );

		$hasLongSentenceWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'sentences are too long' ) ) {
				$hasLongSentenceWarning = true;
				break;
			}
		}

		expect( $hasLongSentenceWarning )->toBeTrue();
	} );

} );

describe( 'ReadabilityAnalyzer Paragraph Length', function (): void {

	it( 'passes when paragraphs are appropriate length', function (): void {
		$model   = createReadabilityTestModel();
		$content = '<p>This is a short paragraph.</p><p>This is another short paragraph.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['passed'] )->toContain( 'Paragraph length is appropriate.' );
	} );

	it( 'suggests breaking up long paragraphs', function (): void {
		$model = createReadabilityTestModel();
		// Create a very long paragraph (over 200 words)
		$words   = array_fill( 0, 250, 'word' );
		$content = '<p>' . implode( ' ', $words ) . '</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasParagraphSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'paragraph' ) ) {
				$hasParagraphSuggestion = true;
				break;
			}
		}

		expect( $hasParagraphSuggestion )->toBeTrue();
	} );

} );

describe( 'ReadabilityAnalyzer Word and Sentence Counts', function (): void {

	it( 'correctly counts words', function (): void {
		$model   = createReadabilityTestModel();
		$content = 'One two three four five six seven eight nine ten.';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['word_count'] )->toBe( 10 );
	} );

	it( 'correctly counts sentences', function (): void {
		$model   = createReadabilityTestModel();
		$content = 'First sentence. Second sentence! Third sentence?';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['sentence_count'] )->toBe( 3 );
	} );

	it( 'strips HTML tags before analysis', function (): void {
		$model   = createReadabilityTestModel();
		$content = '<p><strong>Bold</strong> text and <em>italic</em> text.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['word_count'] )->toBe( 5 );
	} );

} );
