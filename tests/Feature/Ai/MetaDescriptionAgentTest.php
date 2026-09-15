<?php

declare( strict_types=1 );

use ArtisanPackUI\Ai\Exceptions\FeatureError;
use ArtisanPackUI\SEO\Ai\Agents\MetaDescriptionAgent;
use Tests\Feature\Ai\AiAgentTestSetup;

beforeEach( function (): void {
	$this->prompter = AiAgentTestSetup::bootstrap( $this->app );
} );

/**
 * Build a description in the 150–160 char window for use in fixtures.
 */
function fillDescription( string $prefix, int $length ): string
{
	if ( mb_strlen( $prefix ) >= $length ) {
		return mb_substr( $prefix, 0, $length );
	}

	return $prefix . str_repeat( 'a', $length - mb_strlen( $prefix ) );
}

it( 'returns the shaped variants when the prompter responds', function (): void {
	$first  = fillDescription( 'Burr grinders make espresso worth drinking. ', 155 );
	$second = fillDescription( 'Consistent grind size unlocks balanced espresso. ', 158 );
	$third  = fillDescription( 'For serious home baristas, a burr grinder is table stakes. ', 152 );

	$this->prompter->queue( [
		'variants' => [
			[ 'meta_description' => $first, 'character_count' => mb_strlen( $first ), 'rationale' => 'benefit-forward' ],
			[ 'meta_description' => $second, 'character_count' => mb_strlen( $second ), 'rationale' => 'outcome angle' ],
			[ 'meta_description' => $third, 'character_count' => mb_strlen( $third ), 'rationale' => 'audience angle' ],
		],
	] );

	$result = MetaDescriptionAgent::for( [
		'content'         => 'Long article about espresso grinders.',
		'primary_keyword' => 'espresso grinder',
	] )->run();

	expect( $result['variants'] )->toHaveCount( 3 );
	expect( $result['variants'][0]['meta_description'] )->toBe( $first );
	expect( $result['variants'][0]['character_count'] )->toBe( mb_strlen( $first ) );
	expect( $result['variants'][0]['rationale'] )->toBe( 'benefit-forward' );
} );

it( 'drops variants that overshoot the 160 char limit', function (): void {
	$oversize = str_repeat( 'x', 200 );
	$good     = fillDescription( 'Balanced espresso demands a consistent grind. ', 155 );

	$this->prompter->queue( [
		'variants' => [
			[ 'meta_description' => $oversize, 'character_count' => 200, 'rationale' => 'too long' ],
			[ 'meta_description' => $good, 'character_count' => mb_strlen( $good ), 'rationale' => 'in-window' ],
		],
	] );

	$result = MetaDescriptionAgent::for( [ 'content' => 'sample' ] )->run();

	expect( $result['variants'] )->toHaveCount( 1 );
	expect( $result['variants'][0]['meta_description'] )->toBe( $good );
} );

it( 'deduplicates variants case-insensitively', function (): void {
	$a = fillDescription( 'Balanced espresso demands a consistent grind. ', 155 );
	$b = mb_strtoupper( $a );
	$c = fillDescription( 'Different angle on the same topic here. ', 152 );

	$this->prompter->queue( [
		'variants' => [
			[ 'meta_description' => $a, 'character_count' => mb_strlen( $a ), 'rationale' => 'first' ],
			[ 'meta_description' => $b, 'character_count' => mb_strlen( $b ), 'rationale' => 'dupe' ],
			[ 'meta_description' => $c, 'character_count' => mb_strlen( $c ), 'rationale' => 'third' ],
		],
	] );

	$result = MetaDescriptionAgent::for( [ 'content' => 'sample' ] )->run();

	expect( $result['variants'] )->toHaveCount( 2 );
} );

it( 'trims variants to the requested `n`', function (): void {
	$variants = [];

	for ( $i = 0; $i < 6; $i++ ) {
		$text       = fillDescription( "Variant number {$i} sits inside the window. ", 155 );
		$variants[] = [ 'meta_description' => $text, 'character_count' => mb_strlen( $text ), 'rationale' => "reason {$i}" ];
	}

	$this->prompter->queue( [ 'variants' => $variants ] );

	$result = MetaDescriptionAgent::for( [ 'content' => 'sample', 'n' => 3 ] )->run();

	expect( $result['variants'] )->toHaveCount( 3 );
} );

it( 'clamps `n` above the max down to the ceiling of 10', function (): void {
	$variants = [];

	for ( $i = 0; $i < 10; $i++ ) {
		$text       = fillDescription( "Variant number {$i} sits inside the window. ", 155 );
		$variants[] = [ 'meta_description' => $text, 'character_count' => mb_strlen( $text ), 'rationale' => "reason {$i}" ];
	}

	$this->prompter->queue( [ 'variants' => $variants ] );

	$result = MetaDescriptionAgent::for( [ 'content' => 'sample', 'n' => 500 ] )->run();

	expect( $result['variants'] )->toHaveCount( 10 );
} );

it( 'raises FeatureError when content is missing', function (): void {
	expect( fn () => MetaDescriptionAgent::for( [] )->run() )
		->toThrow( FeatureError::class );
} );

it( 'forwards primary_keyword and h1 into the prompter message', function (): void {
	$good = fillDescription( 'Real description here. ', 155 );

	$this->prompter->queue( [
		'variants' => [
			[ 'meta_description' => $good, 'character_count' => mb_strlen( $good ), 'rationale' => '' ],
		],
	] );

	MetaDescriptionAgent::for( [
		'content'         => 'sample',
		'primary_keyword' => 'espresso grinder',
		'h1'              => 'Choose the Right Espresso Grinder',
	] )->run();

	$parts = collect( $this->prompter->calls[0]['message'] )->pluck( 'text' );
	expect( $parts->contains( fn ( string $text ): bool => str_contains( $text, 'espresso grinder' ) ) )
		->toBeTrue();
	expect( $parts->contains( fn ( string $text ): bool => str_contains( $text, 'Choose the Right Espresso Grinder' ) ) )
		->toBeTrue();
} );

it( 'drops a variant that restates the H1 verbatim', function (): void {
	$h1   = 'Choose the Right Espresso Grinder';
	$good = fillDescription( 'A specific and different angle on the topic. ', 155 );

	$this->prompter->queue( [
		'variants' => [
			[ 'meta_description' => $h1, 'character_count' => mb_strlen( $h1 ), 'rationale' => 'restates h1' ],
			[ 'meta_description' => $good, 'character_count' => mb_strlen( $good ), 'rationale' => 'good' ],
		],
	] );

	$result = MetaDescriptionAgent::for( [ 'content' => 'sample', 'h1' => $h1 ] )->run();

	expect( $result['variants'] )->toHaveCount( 1 );
	expect( $result['variants'][0]['meta_description'] )->toBe( $good );
} );
