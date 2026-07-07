<?php

declare( strict_types=1 );

use ArtisanPackUI\Ai\Contracts\FeatureRegistry;
use ArtisanPackUI\Ai\Exceptions\FeatureDisabledException;
use ArtisanPackUI\SEO\Ai\Agents\ContentAnalysisAgent;
use ArtisanPackUI\SEO\Ai\Agents\HreflangSuggestionAgent;
use ArtisanPackUI\SEO\Ai\Agents\MetaDescriptionAgent;
use ArtisanPackUI\SEO\Ai\Agents\MetaTitleSuggestionAgent;
use ArtisanPackUI\SEO\Ai\Agents\SchemaGenerationAgent;
use Tests\Feature\Ai\AiAgentTestSetup;

beforeEach( function (): void {
	$this->prompter = AiAgentTestSetup::bootstrap( $this->app );
} );

it( 'auto-registers all five SEO features via aiFeatures()', function (): void {
	/** @var FeatureRegistry $registry */
	$registry = app( FeatureRegistry::class );

	$expected = [
		'seo.suggest_meta_title'       => MetaTitleSuggestionAgent::class,
		'seo.suggest_meta_description' => MetaDescriptionAgent::class,
		'seo.analyze_content'          => ContentAnalysisAgent::class,
		'seo.generate_schema'          => SchemaGenerationAgent::class,
		'seo.suggest_hreflang'         => HreflangSuggestionAgent::class,
	];

	foreach ( $expected as $key => $class ) {
		$definition = $registry->get( $key );

		expect( $definition )->not->toBeNull( "feature {$key} was not registered" );
		expect( $definition->agentClass )->toBe( $class );
		expect( $definition->package )->toBe( 'artisanpack-ui/seo' );
	}
} );

it( 'refuses to run when the feature toggle is off', function (): void {
	/** @var FeatureRegistry $registry */
	$registry = app( FeatureRegistry::class );
	$registry->disable( 'seo.suggest_meta_title' );

	expect( fn () => MetaTitleSuggestionAgent::for( [ 'content' => 'sample' ] )->run() )
		->toThrow( FeatureDisabledException::class );
} );
