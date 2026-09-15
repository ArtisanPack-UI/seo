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

it( 'drops variants that overshoot the 60 char limit rather than truncating', function (): void {
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

	expect( $result['variants'] )->toHaveCount( 2 );
	expect( $result['variants'][0]['title'] )->toBe( 'Normal Title' );
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

it( 'deduplicates variants case-insensitively', function (): void {
	$this->prompter->queue( [
		'variants' => [
			[ 'title' => 'Best Espresso Grinders', 'char_count' => 22, 'rationale' => 'first' ],
			[ 'title' => 'BEST ESPRESSO GRINDERS', 'char_count' => 22, 'rationale' => 'dupe' ],
			[ 'title' => 'Different Angle Entirely', 'char_count' => 24, 'rationale' => 'third' ],
		],
	] );

	$result = MetaTitleSuggestionAgent::for( [ 'content' => 'sample' ] )->run();

	expect( $result['variants'] )->toHaveCount( 2 );
} );

it( 'trims variants to the requested `n`', function (): void {
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

	$result = MetaTitleSuggestionAgent::for( [ 'content' => 'sample', 'n' => 5 ] )->run();

	expect( $result['variants'] )->toHaveCount( 5 );
} );

it( 'clamps `n` above the max down to the ceiling of 10', function (): void {
	$this->prompter->queue( [
		'variants' => array_map(
			static fn ( int $i ): array => [
				'title'      => "Title Number {$i}",
				'char_count' => 15,
				'rationale'  => "reason {$i}",
			],
			range( 1, 10 ),
		),
	] );

	$result = MetaTitleSuggestionAgent::for( [ 'content' => 'sample', 'n' => 500 ] )->run();

	expect( $result['variants'] )->toHaveCount( 10 );
} );

it( 'raises FeatureError when content is missing', function (): void {
	expect( fn () => MetaTitleSuggestionAgent::for( [] )->run() )
		->toThrow( FeatureError::class );
} );

it( 'forwards primary_keyword, brand, and h1 into the prompter message', function (): void {
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
		'h1'              => 'Choose the Right Espresso Grinder',
	] )->run();

	$parts = collect( $this->prompter->calls[0]['message'] )->pluck( 'text' );
	expect( $parts->contains( fn ( string $text ): bool => str_contains( $text, 'espresso grinder' ) ) )
		->toBeTrue();
	expect( $parts->contains( fn ( string $text ): bool => str_contains( $text, 'Acme' ) ) )
		->toBeTrue();
	expect( $parts->contains( fn ( string $text ): bool => str_contains( $text, 'Choose the Right Espresso Grinder' ) ) )
		->toBeTrue();
} );

it( 'drops a variant that restates the H1 verbatim', function (): void {
	$h1 = 'Choose the Right Espresso Grinder';

	$this->prompter->queue( [
		'variants' => [
			[ 'title' => $h1, 'char_count' => mb_strlen( $h1 ), 'rationale' => 'restates h1' ],
			[ 'title' => 'Espresso Grinders, Ranked for Home', 'char_count' => 34, 'rationale' => 'good' ],
		],
	] );

	$result = MetaTitleSuggestionAgent::for( [ 'content' => 'sample', 'h1' => $h1 ] )->run();

	expect( $result['variants'] )->toHaveCount( 1 );
	expect( $result['variants'][0]['title'] )->toBe( 'Espresso Grinders, Ranked for Home' );
} );
