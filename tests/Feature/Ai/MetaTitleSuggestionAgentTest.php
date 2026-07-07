<?php

declare( strict_types=1 );

use ArtisanPackUI\Ai\Exceptions\FeatureError;
use ArtisanPackUI\SEO\Ai\Agents\MetaTitleSuggestionAgent;
use Tests\Feature\Ai\AiAgentTestSetup;

beforeEach( function (): void {
	$this->prompter = AiAgentTestSetup::bootstrap( $this->app );
} );

it( 'returns the shaped variants when the prompter responds', function (): void {
	$this->prompter->queue( [
		'variants' => [
			[ 'title' => 'Best Coffee Grinders for Espresso', 'char_count' => 33, 'rationale' => 'benefit-forward' ],
			[ 'title' => 'Espresso Grinder Buying Guide 2026', 'char_count' => 34, 'rationale' => 'guide framing' ],
			[ 'title' => 'How to Pick an Espresso Grinder', 'char_count' => 31, 'rationale' => 'how-to lens' ],
		],
	] );

	$result = MetaTitleSuggestionAgent::for( [
		'content'         => 'A long article about picking the right grinder for espresso.',
		'primary_keyword' => 'espresso grinder',
		'brand'           => 'Acme',
	] )->run();

	expect( $result['variants'] )->toHaveCount( 3 );
	expect( $result['variants'][0]['title'] )->toBe( 'Best Coffee Grinders for Espresso' );
	expect( $result['variants'][0]['char_count'] )->toBe( 33 );
} );

it( 'clamps oversized titles down to 60 characters', function (): void {
	$oversize = str_repeat( 'x', 80 );

	$this->prompter->queue( [
		'variants' => [
			[ 'title' => $oversize, 'char_count' => 80, 'rationale' => 'too long on purpose' ],
			[ 'title' => 'Normal Title', 'char_count' => 12, 'rationale' => 'baseline' ],
			[ 'title' => 'Another Title', 'char_count' => 13, 'rationale' => 'baseline' ],
		],
	] );

	$result = MetaTitleSuggestionAgent::for( [
		'content' => 'sample',
	] )->run();

	expect( mb_strlen( $result['variants'][0]['title'] ) )->toBe( 60 );
	expect( $result['variants'][0]['char_count'] )->toBe( 60 );
} );

it( 'drops variants with an empty title', function (): void {
	$this->prompter->queue( [
		'variants' => [
			[ 'title' => '', 'char_count' => 0, 'rationale' => 'empty' ],
			[ 'title' => 'Real Title', 'char_count' => 10, 'rationale' => 'real' ],
			[ 'title' => 'Another Real', 'char_count' => 12, 'rationale' => 'real' ],
			[ 'title' => 'Third Real', 'char_count' => 10, 'rationale' => 'real' ],
		],
	] );

	$result = MetaTitleSuggestionAgent::for( [ 'content' => 'sample' ] )->run();

	expect( $result['variants'] )->toHaveCount( 3 );
} );

it( 'trims variants to at most 5 entries', function (): void {
	$this->prompter->queue( [
		'variants' => array_map(
			static fn ( int $i ): array => [
				'title'      => "Title {$i}",
				'char_count' => 7,
				'rationale'  => "reason {$i}",
			],
			range( 1, 8 ),
		),
	] );

	$result = MetaTitleSuggestionAgent::for( [ 'content' => 'sample' ] )->run();

	expect( $result['variants'] )->toHaveCount( 5 );
} );

it( 'raises FeatureError when content is missing', function (): void {
	expect( fn () => MetaTitleSuggestionAgent::for( [] )->run() )
		->toThrow( FeatureError::class );
} );

it( 'forwards primary_keyword and brand into the prompter message', function (): void {
	$this->prompter->queue( [
		'variants' => [
			[ 'title' => 'A', 'char_count' => 1, 'rationale' => '' ],
			[ 'title' => 'B', 'char_count' => 1, 'rationale' => '' ],
			[ 'title' => 'C', 'char_count' => 1, 'rationale' => '' ],
		],
	] );

	MetaTitleSuggestionAgent::for( [
		'content'         => 'sample',
		'primary_keyword' => 'espresso grinder',
		'brand'           => 'Acme',
	] )->run();

	$parts = collect( $this->prompter->calls[0]['message'] )->pluck( 'text' );
	expect( $parts->contains( fn ( string $text ): bool => str_contains( $text, 'espresso grinder' ) ) )
		->toBeTrue();
	expect( $parts->contains( fn ( string $text ): bool => str_contains( $text, 'Acme' ) ) )
		->toBeTrue();
} );
