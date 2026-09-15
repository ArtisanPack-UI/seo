<?php

declare( strict_types=1 );

use ArtisanPackUI\SEO\Ai\Agents\MetaDescriptionAgent;
use ArtisanPackUI\SEO\Ai\Agents\MetaTitleSuggestionAgent;

/**
 * Prompt-quality golden tests.
 *
 * These pin the anti-filler / anti-H1-restate contract that the meta title
 * and description prompts must carry. A prompt regression here is a real
 * regression — filler openers and H1 restates are what shipped the "generic
 * AI filler" complaint in the first place.
 */

it( 'title prompt forbids the common filler openers', function (): void {
	$prompt = ( new MetaTitleSuggestionAgent() )->instructions();

	$forbidden = [
		'Discover',
		'Everything about',
		'Ultimate guide',
	];

	foreach ( $forbidden as $needle ) {
		expect( $prompt )->toContain( $needle );
	}

	expect( $prompt )->toContain( '60 characters or fewer' );
	expect( $prompt )->toContain( 'Front-load' );
} );

it( 'title prompt bans brand-first and H1 restate', function (): void {
	$prompt = ( new MetaTitleSuggestionAgent() )->instructions();

	expect( $prompt )->toContain( 'restate' );
	expect( $prompt )->toContain( 'H1' );
	expect( $prompt )->toContain( 'brand' );
} );

it( 'title prompt varies angles across variants', function (): void {
	$prompt = ( new MetaTitleSuggestionAgent() )->instructions();

	expect( $prompt )->toContain( 'Vary the angle' );
} );

it( 'description prompt forbids the common filler openers', function (): void {
	$prompt = ( new MetaDescriptionAgent() )->instructions();

	$forbidden = [
		'Discover',
		'Learn',
		'Everything you need',
		'ultimate guide',
		'Welcome to',
	];

	foreach ( $forbidden as $needle ) {
		expect( $prompt )->toContain( $needle );
	}
} );

it( 'description prompt enforces the 150 to 160 window', function (): void {
	$prompt = ( new MetaDescriptionAgent() )->instructions();

	expect( $prompt )->toContain( '150' );
	expect( $prompt )->toContain( '160' );
	expect( $prompt )->toContain( 'complete sentences' );
} );

it( 'description prompt bans H1 restate and hedging', function (): void {
	$prompt = ( new MetaDescriptionAgent() )->instructions();

	expect( $prompt )->toContain( 'restate the H1' );
	expect( $prompt )->toContain( 'Active voice' );
} );

it( 'both agents advertise the variants output shape in their schemas', function (): void {
	$titleSchema = ( new MetaTitleSuggestionAgent() )->outputSchema();
	$descSchema  = ( new MetaDescriptionAgent() )->outputSchema();

	expect( $titleSchema['required'] )->toContain( 'variants' );
	expect( $titleSchema['properties']['variants']['items']['properties'] )->toHaveKey( 'title' );

	expect( $descSchema['required'] )->toContain( 'variants' );
	expect( $descSchema['properties']['variants']['items']['properties'] )->toHaveKey( 'meta_description' );
} );
