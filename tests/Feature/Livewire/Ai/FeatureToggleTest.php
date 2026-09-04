<?php

/**
 * Feature toggle fail-closed coverage for the AI Livewire components.
 *
 * Verifies that every AI trigger component reports `$isEnabled === false`
 * when its feature key is not registered with the FeatureRegistry (the
 * fail-closed guarantee provided by the shared `ChecksFeatureToggle`
 * concern), and `true` once the key is registered and toggled on.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\Ai\Contracts\FeatureRegistry;
use ArtisanPackUI\Ai\Registry\ArrayFeatureRegistry;
use ArtisanPackUI\SEO\Livewire\Ai\ContentAnalyzer;
use ArtisanPackUI\SEO\Livewire\Ai\HreflangSuggestor;
use ArtisanPackUI\SEO\Livewire\Ai\MetaDescriptionSuggestor;
use ArtisanPackUI\SEO\Livewire\Ai\MetaTitleSuggestor;
use ArtisanPackUI\SEO\Livewire\Ai\SchemaSuggestor;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Livewire\Livewire;

dataset( 'aiComponents', [
	'meta description' => [ MetaDescriptionSuggestor::class, 'seo.suggest_meta_description' ],
	'schema'           => [ SchemaSuggestor::class, 'seo.generate_schema' ],
	'content analyzer' => [ ContentAnalyzer::class, 'seo.analyze_content' ],
	'hreflang'         => [ HreflangSuggestor::class, 'seo.suggest_hreflang' ],
	'meta title'       => [ MetaTitleSuggestor::class, 'seo.suggest_meta_title' ],
] );

it( 'fails closed when the feature key is not registered', function ( string $component ): void {
	// Replace the auto-registered registry with a fresh empty one so no
	// feature keys are known. `ChecksFeatureToggle` should then report false.
	$this->app->instance(
		FeatureRegistry::class,
		new ArrayFeatureRegistry(
			$this->app->make( Container::class ),
			new Repository( [] ),
			$this->app->make( ArtisanPackUI\Ai\Contracts\CredentialResolver::class ),
		),
	);

	Livewire::test( $component )
		->assertSet( 'isEnabled', false );
} )->with( 'aiComponents' );

it( 'reports enabled once the feature is registered and toggled on', function ( string $component, string $featureKey ): void {
	// SEOServiceProvider auto-registers every AI feature key at boot;
	// enable() flips the toggle to on.
	app( FeatureRegistry::class )->enable( $featureKey );

	Livewire::test( $component )
		->assertSet( 'isEnabled', true );
} )->with( 'aiComponents' );
