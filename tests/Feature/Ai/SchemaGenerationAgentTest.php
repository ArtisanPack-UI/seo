<?php

declare( strict_types=1 );

use ArtisanPackUI\Ai\Exceptions\FeatureError;
use ArtisanPackUI\SEO\Ai\Agents\SchemaGenerationAgent;
use Tests\Feature\Ai\AiAgentTestSetup;

beforeEach( function (): void {
	$this->prompter = AiAgentTestSetup::bootstrap( $this->app );
} );

it( 'returns the shaped suggestion when the prompter responds', function (): void {
	$this->prompter->queue( [
		'suggested_type'          => 'Article',
		'confidence'              => 0.87,
		'jsonld'                  => [
			'@context' => 'https://schema.org',
			'@type'    => 'Article',
			'headline' => 'Espresso Guide',
			'url'      => 'https://example.com/espresso',
		],
		'missing_required_fields' => [ 'author' ],
	] );

	$result = SchemaGenerationAgent::for( [
		'content' => 'Article body about espresso.',
		'title'   => 'Espresso Guide',
		'url'     => 'https://example.com/espresso',
	] )->run();

	expect( $result['suggested_type'] )->toBe( 'Article' );
	expect( $result['confidence'] )->toBe( 0.87 );
	expect( $result['jsonld']['@type'] )->toBe( 'Article' );
	expect( $result['missing_required_fields'] )->toBe( [ 'author' ] );
} );

it( 'falls back to WebPage for unsupported types', function (): void {
	$this->prompter->queue( [
		'suggested_type'          => 'SomeUnsupportedType',
		'confidence'              => 0.5,
		'jsonld'                  => [ '@context' => 'https://schema.org', '@type' => 'SomeUnsupportedType' ],
		'missing_required_fields' => [],
	] );

	$result = SchemaGenerationAgent::for( [
		'content' => 'sample',
		'title'   => 'sample',
	] )->run();

	expect( $result['suggested_type'] )->toBe( 'WebPage' );
} );

it( 'syncs jsonld @type when suggested_type is coerced', function (): void {
	// Model returns an unsupported type in BOTH suggested_type and jsonld['@type'];
	// the agent must coerce jsonld to match the top-level fallback so downstream
	// callers never emit invalid schema.org markup.
	$this->prompter->queue( [
		'suggested_type'          => 'MadeUpType',
		'confidence'              => 0.4,
		'jsonld'                  => [
			'@context' => 'https://schema.org',
			'@type'    => 'MadeUpType',
			'headline' => 'Sample',
		],
		'missing_required_fields' => [],
	] );

	$result = SchemaGenerationAgent::for( [
		'content' => 'sample',
		'title'   => 'sample',
	] )->run();

	expect( $result['suggested_type'] )->toBe( 'WebPage' );
	expect( $result['jsonld']['@type'] )->toBe( 'WebPage' );
	expect( $result['jsonld']['headline'] )->toBe( 'Sample' );
} );

it( 'clamps confidence to the 0..1 range', function (): void {
	$this->prompter->queue( [
		'suggested_type'          => 'Article',
		'confidence'              => 2.5,
		'jsonld'                  => [ '@context' => 'https://schema.org', '@type' => 'Article' ],
		'missing_required_fields' => [],
	] );

	$result = SchemaGenerationAgent::for( [
		'content' => 'sample',
		'title'   => 'sample',
	] )->run();

	expect( $result['confidence'] )->toBe( 1.0 );
} );

it( 'auto-fills the @context and @type keys when the model omits them', function (): void {
	$this->prompter->queue( [
		'suggested_type'          => 'Article',
		'confidence'              => 0.5,
		'jsonld'                  => [ 'headline' => 'x' ],
		'missing_required_fields' => [],
	] );

	$result = SchemaGenerationAgent::for( [
		'content' => 'sample',
		'title'   => 'sample',
	] )->run();

	expect( $result['jsonld']['@context'] )->toBe( 'https://schema.org' );
	expect( $result['jsonld']['@type'] )->toBe( 'Article' );
} );

it( 'raises FeatureError when title is missing', function (): void {
	expect( fn () => SchemaGenerationAgent::for( [ 'content' => 'sample' ] )->run() )
		->toThrow( FeatureError::class );
} );
