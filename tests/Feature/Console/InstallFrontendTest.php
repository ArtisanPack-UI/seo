<?php

/**
 * InstallFrontend Command Tests.
 *
 * Feature tests verifying that the seo:install-frontend command
 * publishes React or Vue SEO components and TypeScript type definitions.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use Illuminate\Support\ServiceProvider;

describe( 'InstallFrontend Command', function (): void {
	describe( 'command registration', function (): void {
		it( 'is registered as an artisan command', function (): void {
			$this->artisan( 'seo:install-frontend', ['--stack' => 'react'] )
				->assertSuccessful();
		} );
	} );

	describe( 'stack option', function (): void {
		it( 'accepts react as a valid stack', function (): void {
			$this->artisan( 'seo:install-frontend', ['--stack' => 'react'] )
				->assertSuccessful();
		} );

		it( 'accepts vue as a valid stack', function (): void {
			$this->artisan( 'seo:install-frontend', ['--stack' => 'vue'] )
				->assertSuccessful();
		} );

		it( 'rejects an invalid stack', function (): void {
			$this->artisan( 'seo:install-frontend', ['--stack' => 'angular'] )
				->assertFailed();
		} );

		it( 'is case insensitive for stack names', function (): void {
			$this->artisan( 'seo:install-frontend', ['--stack' => 'React'] )
				->assertSuccessful();
		} );

		it( 'prompts for stack when not provided', function (): void {
			$this->artisan( 'seo:install-frontend' )
				->expectsChoice(
					'Which frontend stack would you like to install?',
					'react',
					['react', 'vue'],
				)
				->assertSuccessful();
		} );
	} );

	describe( 'publishing react stack', function (): void {
		it( 'publishes the seo-react tag assets', function (): void {
			$publishGroups = ServiceProvider::$publishGroups;

			expect( $publishGroups )->toHaveKey( 'seo-react' );

			$paths = $publishGroups['seo-react'];

			$reactKeys = array_filter(
				array_keys( $paths ),
				fn ( $key ) => str_ends_with( $key, 'resources/js/react' ),
			);
			expect( $reactKeys )->not->toBeEmpty();
		} );

		it( 'outputs success message for react', function (): void {
			$this->artisan( 'seo:install-frontend', ['--stack' => 'react'] )
				->expectsOutputToContain( 'react' )
				->assertSuccessful();
		} );
	} );

	describe( 'publishing vue stack', function (): void {
		it( 'publishes the seo-vue tag assets', function (): void {
			$publishGroups = ServiceProvider::$publishGroups;

			expect( $publishGroups )->toHaveKey( 'seo-vue' );

			$paths = $publishGroups['seo-vue'];

			$vueKeys = array_filter(
				array_keys( $paths ),
				fn ( $key ) => str_ends_with( $key, 'resources/js/vue' ),
			);
			expect( $vueKeys )->not->toBeEmpty();
		} );

		it( 'outputs success message for vue', function (): void {
			$this->artisan( 'seo:install-frontend', ['--stack' => 'vue'] )
				->expectsOutputToContain( 'vue' )
				->assertSuccessful();
		} );
	} );

	describe( 'type definitions publishing', function (): void {
		it( 'publishes type definitions alongside stack components', function (): void {
			$publishGroups = ServiceProvider::$publishGroups;

			expect( $publishGroups )->toHaveKey( 'seo-types' );

			$paths = $publishGroups['seo-types'];

			$typeKeys = array_filter(
				array_keys( $paths ),
				fn ( $key ) => str_ends_with( $key, 'resources/js/types' ),
			);
			expect( $typeKeys )->not->toBeEmpty();
		} );
	} );

	describe( 'force option', function (): void {
		it( 'accepts the force flag', function (): void {
			$this->artisan( 'seo:install-frontend', [
				'--stack' => 'react',
				'--force' => true,
			] )->assertSuccessful();
		} );
	} );
} );
