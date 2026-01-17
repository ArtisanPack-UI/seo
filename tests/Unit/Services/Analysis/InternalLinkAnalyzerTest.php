<?php

/**
 * InternalLinkAnalyzer Tests.
 *
 * Unit tests for the InternalLinkAnalyzer.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\Analysis\InternalLinkAnalyzer;
use Illuminate\Database\Eloquent\Model;

beforeEach( function (): void {
	$this->analyzer = new InternalLinkAnalyzer();

	// Set app URL for internal link detection
	config( [ 'app.url' => 'https://example.com' ] );
} );

/**
 * Create a simple test model.
 */
function createInternalLinkTestModel(): Model
{
	return new class extends Model {
		public string $title = 'Test Title';
	};
}

describe( 'InternalLinkAnalyzer Interface', function (): void {

	it( 'returns correct name', function (): void {
		expect( $this->analyzer->getName() )->toBe( 'internal_links' );
	} );

	it( 'returns correct category', function (): void {
		expect( $this->analyzer->getCategory() )->toBe( 'content' );
	} );

	it( 'returns correct weight', function (): void {
		expect( $this->analyzer->getWeight() )->toBe( 25 );
	} );

} );

describe( 'InternalLinkAnalyzer No Links', function (): void {

	it( 'warns when no internal links exist', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p>Content without any links.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( 'warning' === $issue['type'] && str_contains( $issue['message'], 'No internal links' ) ) {
				$hasWarning = true;
				break;
			}
		}

		expect( $hasWarning )->toBeTrue()
			->and( $result['details']['internal_link_count'] )->toBe( 0 );
	} );

} );

describe( 'InternalLinkAnalyzer Internal Links', function (): void {

	it( 'detects relative internal links', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p>Check out <a href="/about">our about page</a>.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['internal_link_count'] )->toBe( 1 )
			->and( $result['details']['external_link_count'] )->toBe( 0 );
	} );

	it( 'detects absolute internal links', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p>Visit <a href="https://example.com/contact">contact</a>.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['internal_link_count'] )->toBe( 1 );
	} );

	it( 'handles www prefix for internal links', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p>Visit <a href="https://www.example.com/page">page</a>.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['internal_link_count'] )->toBe( 1 );
	} );

	it( 'passes with good internal linking', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p>Read about <a href="/topic-a">Topic A</a> and <a href="/topic-b">Topic B</a>.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasPassed = false;
		foreach ( $result['passed'] as $passed ) {
			if ( str_contains( $passed, 'Good internal linking' ) ) {
				$hasPassed = true;
				break;
			}
		}

		expect( $hasPassed )->toBeTrue()
			->and( $result['details']['internal_link_count'] )->toBe( 2 );
	} );

} );

describe( 'InternalLinkAnalyzer External Links', function (): void {

	it( 'detects external links', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p>Source: <a href="https://other-site.com/article">Other Site</a>.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['external_link_count'] )->toBe( 1 )
			->and( $result['details']['internal_link_count'] )->toBe( 0 );
	} );

	it( 'passes when external links exist', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p><a href="/page">Internal</a> and <a href="https://example.org">External</a>.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasPassed = false;
		foreach ( $result['passed'] as $passed ) {
			if ( str_contains( $passed, 'external link' ) ) {
				$hasPassed = true;
				break;
			}
		}

		expect( $hasPassed )->toBeTrue();
	} );

	it( 'suggests external links for long content without them', function (): void {
		$model   = createInternalLinkTestModel();
		$words   = array_fill( 0, 600, 'word' );
		$content = '<p><a href="/page">Internal</a></p><p>' . implode( ' ', $words ) . '</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'external links' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue();
	} );

} );

describe( 'InternalLinkAnalyzer Skipped Links', function (): void {

	it( 'ignores anchor links', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p><a href="#section1">Jump to Section</a></p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['internal_link_count'] )->toBe( 0 )
			->and( $result['details']['external_link_count'] )->toBe( 0 );
	} );

	it( 'ignores javascript links', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p><a href="javascript:void(0)">Click</a></p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['total_link_count'] )->toBe( 0 );
	} );

	it( 'ignores mailto links', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p><a href="mailto:test@example.com">Email</a></p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['total_link_count'] )->toBe( 0 );
	} );

	it( 'ignores tel links', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p><a href="tel:+1234567890">Call</a></p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['total_link_count'] )->toBe( 0 );
	} );

} );

describe( 'InternalLinkAnalyzer Anchor Text', function (): void {

	it( 'warns about generic anchor text', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p><a href="/page">click here</a> to learn more.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'non-descriptive anchor text' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue()
			->and( $result['details']['empty_anchor_count'] )->toBe( 1 );
	} );

	it( 'counts multiple generic anchors', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p><a href="/a">here</a> and <a href="/b">read more</a> and <a href="/c">link</a></p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['empty_anchor_count'] )->toBe( 3 );
	} );

	it( 'allows descriptive anchor text', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p>Learn about <a href="/seo">search engine optimization</a>.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['empty_anchor_count'] )->toBe( 0 );
	} );

} );

describe( 'InternalLinkAnalyzer Mixed Content', function (): void {

	it( 'handles mixed link types', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '
            <p><a href="/internal1">Internal Page 1</a></p>
            <p><a href="/internal2">Internal Page 2</a></p>
            <p><a href="https://external.com">External Site</a></p>
            <p><a href="#anchor">Anchor</a></p>
            <p><a href="mailto:test@test.com">Email</a></p>
        ';
		$result = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['internal_link_count'] )->toBe( 2 )
			->and( $result['details']['external_link_count'] )->toBe( 1 )
			->and( $result['details']['total_link_count'] )->toBe( 3 );
	} );

} );

describe( 'InternalLinkAnalyzer Score', function (): void {

	it( 'returns high score for good linking', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '
            <p><a href="/page1">Internal 1</a></p>
            <p><a href="/page2">Internal 2</a></p>
            <p><a href="https://authority.com">External Source</a></p>
        ';
		$result = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['score'] )->toBeGreaterThanOrEqual( 90 );
	} );

	it( 'reduces score for no internal links', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p>Content without any links to other pages.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['score'] )->toBeLessThanOrEqual( 70 );
	} );

} );

describe( 'InternalLinkAnalyzer Details', function (): void {

	it( 'returns detailed link information', function (): void {
		$model   = createInternalLinkTestModel();
		$content = '<p><a href="/test">Link</a></p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details'] )->toHaveKey( 'internal_link_count' )
			->and( $result['details'] )->toHaveKey( 'external_link_count' )
			->and( $result['details'] )->toHaveKey( 'total_link_count' )
			->and( $result['details'] )->toHaveKey( 'ideal_internal_links' )
			->and( $result['details'] )->toHaveKey( 'word_count' )
			->and( $result['details'] )->toHaveKey( 'empty_anchor_count' )
			->and( $result['details'] )->toHaveKey( 'internal_links' )
			->and( $result['details'] )->toHaveKey( 'external_links' );
	} );

	it( 'limits link arrays to 10 items', function (): void {
		$model = createInternalLinkTestModel();
		$links = '';
		for ( $i = 0; $i < 15; $i++ ) {
			$links .= "<a href=\"/page{$i}\">Page {$i}</a> ";
		}
		$content = "<p>{$links}</p>";
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( count( $result['details']['internal_links'] ) )->toBeLessThanOrEqual( 10 )
			->and( $result['details']['internal_link_count'] )->toBe( 15 );
	} );

} );
