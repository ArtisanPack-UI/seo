<?php

declare( strict_types=1 );

use ArtisanPackUI\SEO\Ai\Agents\HreflangSuggestionAgent;
use Tests\Feature\Ai\AiAgentTestSetup;

beforeEach( function (): void {
	$this->prompter = AiAgentTestSetup::bootstrap( $this->app );
} );

it( 'short-circuits when no pages are provided', function (): void {
	$result = HreflangSuggestionAgent::for( [ 'pages' => [] ] )->run();

	expect( $result )->toBe( [ 'issues' => [] ] );
	expect( $this->prompter->calls )->toBeEmpty();
} );

it( 'returns the shaped issues when the prompter responds', function (): void {
	$this->prompter->queue( [
		'issues' => [
			[
				'page_url'           => 'https://example.com/en',
				'issue_type'         => 'missing_reciprocal',
				'suggested_hreflang' => [
					[ 'lang' => 'en', 'url' => 'https://example.com/en' ],
					[ 'lang' => 'fr', 'url' => 'https://example.com/fr' ],
				],
			],
		],
	] );

	$result = HreflangSuggestionAgent::for( [
		'pages' => [
			[
				'url'          => 'https://example.com/en',
				'lang'         => 'en',
				'translations' => [
					[ 'url' => 'https://example.com/fr', 'lang' => 'fr' ],
				],
			],
		],
	] )->run();

	expect( $result['issues'] )->toHaveCount( 1 );
	expect( $result['issues'][0]['issue_type'] )->toBe( 'missing_reciprocal' );
	expect( $result['issues'][0]['suggested_hreflang'] )->toHaveCount( 2 );
} );

it( 'drops issues with unknown issue types', function (): void {
	$this->prompter->queue( [
		'issues' => [
			[
				'page_url'           => 'https://example.com/en',
				'issue_type'         => 'unknown_type',
				'suggested_hreflang' => [],
			],
			[
				'page_url'           => 'https://example.com/fr',
				'issue_type'         => 'missing_self',
				'suggested_hreflang' => [
					[ 'lang' => 'fr', 'url' => 'https://example.com/fr' ],
				],
			],
		],
	] );

	$result = HreflangSuggestionAgent::for( [
		'pages' => [
			[ 'url' => 'https://example.com/fr', 'lang' => 'fr', 'translations' => [] ],
		],
	] )->run();

	expect( $result['issues'] )->toHaveCount( 1 );
	expect( $result['issues'][0]['issue_type'] )->toBe( 'missing_self' );
} );

it( 'skips pages missing a url or lang', function (): void {
	$this->prompter->queue( [ 'issues' => [] ] );

	HreflangSuggestionAgent::for( [
		'pages' => [
			[ 'url' => '', 'lang' => 'en', 'translations' => [] ],
			[ 'url' => 'https://example.com', 'lang' => '', 'translations' => [] ],
			[ 'url' => 'https://example.com/en', 'lang' => 'en', 'translations' => [] ],
		],
	] )->run();

	$message = $this->prompter->calls[0]['message'][0]['text'];
	expect( $message )->toContain( 'https://example.com/en' );
	expect( substr_count( $message, 'https://example.com' ) )->toBe( 1 );
} );
