<?php

/**
 * AiReadinessAnalyzer Tests.
 *
 * Unit tests for the AiReadinessAnalyzer.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\Analysis\AiReadinessAnalyzer;
use Illuminate\Database\Eloquent\Model;

beforeEach( function (): void {
	$this->analyzer = new AiReadinessAnalyzer();
} );

/**
 * Create a simple test model for AI readiness analysis.
 */
function createAiReadinessTestModel(): Model
{
	return new class() extends Model {
		public string $title = 'Test Post';
	};
}

describe( 'AiReadinessAnalyzer Interface', function (): void {

	it( 'returns correct name', function (): void {
		expect( $this->analyzer->getName() )->toBe( 'ai_readiness' );
	} );

	it( 'returns correct category', function (): void {
		expect( $this->analyzer->getCategory() )->toBe( 'content' );
	} );

	it( 'returns a weight between 1 and 100', function (): void {
		expect( $this->analyzer->getWeight() )
			->toBeGreaterThan( 0 )
			->toBeLessThanOrEqual( 100 );
	} );

} );

describe( 'AiReadinessAnalyzer Question Headings', function (): void {

	it( 'passes when subheadings end with a question mark', function (): void {
		$model   = createAiReadinessTestModel();
		$content = '<h1>Widgets</h1><p>Widgets are small components.</p><h2>What is a widget?</h2><p>A widget is a small reusable UI piece.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['question_count'] )->toBeGreaterThan( 0 )
			->and( $result['details']['question_headings'] )->toContain( 'What is a widget?' );
	} );

	it( 'detects question-style openers without punctuation', function (): void {
		$model   = createAiReadinessTestModel();
		$content = '<h1>Widgets</h1><p>Widgets are small components.</p><h2>How widgets work</h2>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['question_count'] )->toBe( 1 );
	} );

	it( 'suggests adding questions when no question headings exist', function (): void {
		$model   = createAiReadinessTestModel();
		$content = '<h1>Widgets</h1><p>Widgets are small components.</p><h2>Overview</h2><h2>Details</h2>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'question-style subheading' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue()
			->and( $result['details']['question_count'] )->toBe( 0 );
	} );

} );

describe( 'AiReadinessAnalyzer Definition Intro', function (): void {

	it( 'passes when intro opens with a definition-style sentence', function (): void {
		$model   = createAiReadinessTestModel();
		$content = '<h1>Widgets</h1><p>A widget is a small reusable UI piece used in dashboards.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['has_definition_intro'] )->toBeTrue()
			->and( $result['passed'] )->toContain( 'Intro paragraph opens with a definition-style statement.' );
	} );

	it( 'suggests a definition when intro does not open with one', function (): void {
		$model   = createAiReadinessTestModel();
		$content = '<h1>Widgets</h1><p>We built widgets over many years of experimenting with UI patterns.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['has_definition_intro'] )->toBeFalse();

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'definition-style' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue();
	} );

} );

describe( 'AiReadinessAnalyzer FAQ Detection', function (): void {

	it( 'passes when an FAQ heading is present', function (): void {
		$model   = createAiReadinessTestModel();
		$content = '<h1>Widgets</h1><p>A widget is a small UI piece.</p><h2>FAQ</h2><h3>Are widgets free?</h3><p>Yes.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['has_faq'] )->toBeTrue();
	} );

	it( 'detects FAQPage JSON-LD schema even without a heading', function (): void {
		$model   = createAiReadinessTestModel();
		$content = '<h1>Widgets</h1><p>A widget is a small UI piece.</p><script type="application/ld+json">{"@context":"https://schema.org","@type":"FAQPage"}</script>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['has_faq'] )->toBeTrue();
	} );

	it( 'suggests an FAQ when none is found', function (): void {
		$model   = createAiReadinessTestModel();
		$content = '<h1>Widgets</h1><p>A widget is a small UI piece.</p><h2>Overview</h2>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['has_faq'] )->toBeFalse();

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'FAQ' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue();
	} );

} );

describe( 'AiReadinessAnalyzer Summary Window', function (): void {

	it( 'passes when the first paragraph fits the summary window', function (): void {
		$model   = createAiReadinessTestModel();
		$content = '<h1>Widgets</h1><p>A widget is a small UI element that packages related controls into a reusable block for dashboards.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['first_paragraph_words'] )->toBeGreaterThan( 0 )
			->toBeLessThanOrEqual( 60 );

		$hasPass = false;
		foreach ( $result['passed'] as $passed ) {
			if ( str_contains( $passed, 'summary window' ) ) {
				$hasPass = true;
				break;
			}
		}

		expect( $hasPass )->toBeTrue();
	} );

	it( 'suggests tightening the intro when it exceeds the window', function (): void {
		$model     = createAiReadinessTestModel();
		$longIntro = str_repeat( 'word ', 80 );
		$content   = '<h1>Widgets</h1><p>' . trim( $longIntro ) . '</p>';
		$result    = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['first_paragraph_words'] )->toBeGreaterThan( 60 );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'stand alone' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue();
	} );

	it( 'counts non-Latin words (Japanese, Cyrillic) instead of returning zero', function (): void {
		$model = createAiReadinessTestModel();

		$japanese = '<h1>大阪</h1><p>大阪 は 日本 で 二番目 に 大きい 都市 です。</p>';
		$result   = $this->analyzer->analyze( $model, $japanese, null, null );
		expect( $result['details']['first_paragraph_words'] )->toBeGreaterThan( 0 );

		$cyrillic = '<h1>Заголовок</h1><p>Это первая часть статьи о СЕО оптимизации сайта.</p>';
		$result   = $this->analyzer->analyze( $model, $cyrillic, null, null );
		expect( $result['details']['first_paragraph_words'] )->toBeGreaterThan( 0 );
	} );

	it( 'warns when there is no opening paragraph at all', function (): void {
		$model   = createAiReadinessTestModel();
		$content = '';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['first_paragraph_words'] )->toBe( 0 );

		$hasWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'opening paragraph' ) ) {
				$hasWarning = true;
				break;
			}
		}

		expect( $hasWarning )->toBeTrue();
	} );

} );

describe( 'AiReadinessAnalyzer Score', function (): void {

	it( 'returns 100 when every check passes', function (): void {
		$model   = createAiReadinessTestModel();
		$content = '<h1>Widgets</h1>'
			. '<p>A widget is a small reusable UI element used across dashboards.</p>'
			. '<h2>What is a widget?</h2><p>A short answer.</p>'
			. '<h2>FAQ</h2><h3>Are widgets free?</h3><p>Yes.</p>';

		$result = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['score'] )->toBe( 100 )
			->and( $result['details']['checks_passed'] )->toBe( $result['details']['checks_total'] );
	} );

	it( 'returns 0 when no checks pass', function (): void {
		$model   = createAiReadinessTestModel();
		$content = '<h1>Widgets</h1>'
			. '<p>' . trim( str_repeat( 'word ', 80 ) ) . '</p>'
			. '<h2>Overview</h2><h2>Details</h2>';

		$result = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['score'] )->toBe( 0 )
			->and( $result['details']['checks_passed'] )->toBe( 0 );
	} );

} );
