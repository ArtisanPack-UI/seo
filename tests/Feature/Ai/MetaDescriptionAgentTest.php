<?php

declare( strict_types=1 );

use ArtisanPackUI\Ai\Exceptions\FeatureError;
use ArtisanPackUI\SEO\Ai\Agents\MetaDescriptionAgent;
use Tests\Feature\Ai\AiAgentTestSetup;

beforeEach( function (): void {
	$this->prompter = AiAgentTestSetup::bootstrap( $this->app );
} );

it( 'returns the shaped suggestion when the prompter responds', function (): void {
	$description = str_pad( 'Discover the best espresso grinders reviewed. ', 158, 'a' );

	$this->prompter->queue( [
		'meta_description' => $description,
		'character_count'  => mb_strlen( $description ),
		'rationale'        => 'benefit-forward summary',
	] );

	$result = MetaDescriptionAgent::for( [
		'content'         => 'Long article about espresso grinders.',
		'primary_keyword' => 'espresso grinder',
	] )->run();

	expect( $result['meta_description'] )->toBe( $description );
	expect( $result['character_count'] )->toBe( mb_strlen( $description ) );
	expect( $result['rationale'] )->toBe( 'benefit-forward summary' );
} );

it( 'clamps oversized descriptions to 160 characters', function (): void {
	$oversize = str_repeat( 'x', 200 );

	$this->prompter->queue( [
		'meta_description' => $oversize,
		'character_count'  => 200,
		'rationale'        => '',
	] );

	$result = MetaDescriptionAgent::for( [ 'content' => 'sample' ] )->run();

	expect( mb_strlen( $result['meta_description'] ) )->toBe( 160 );
	expect( $result['character_count'] )->toBe( 160 );
} );

it( 'raises FeatureError when content is missing', function (): void {
	expect( fn () => MetaDescriptionAgent::for( [] )->run() )
		->toThrow( FeatureError::class );
} );

it( 'forwards primary_keyword into the prompter message', function (): void {
	$this->prompter->queue( [
		'meta_description' => 'x',
		'character_count'  => 1,
		'rationale'        => '',
	] );

	MetaDescriptionAgent::for( [
		'content'         => 'sample',
		'primary_keyword' => 'espresso grinder',
	] )->run();

	$parts = collect( $this->prompter->calls[0]['message'] )->pluck( 'text' );
	expect( $parts->contains( fn ( string $text ): bool => str_contains( $text, 'espresso grinder' ) ) )
		->toBeTrue();
} );
