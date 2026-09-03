<?php

/**
 * SchemaCollector Helper Feature Tests.
 *
 * Verifies `apSeoAddSchema()` end-to-end through the HTTP kernel and
 * asserts the singleton is per-request (a fresh application container
 * — as PHP-FPM gives every real request — starts with an empty collector).
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Schema\Builders\ArticleSchema;
use ArtisanPackUI\SEO\Support\SchemaCollector;
use Illuminate\Support\Facades\Route;

describe( 'apSeoAddSchema helper', function (): void {

	it( 'pushes an array entry through the container-resolved collector', function (): void {
		Route::get( '/__test/add-array-schema', function () {
			apSeoAddSchema( [ '@type' => 'Article', 'headline' => 'From route' ] );

			return 'ok';
		} );

		$this->get( '/__test/add-array-schema' )->assertOk();

		$entries = app( SchemaCollector::class )->all();

		expect( $entries )->toHaveCount( 1 );
		expect( $entries[0] )->toBe( [ '@type' => 'Article', 'headline' => 'From route' ] );
	} );

	it( 'converts a builder instance via toArray()', function (): void {
		$builder = new ArticleSchema( [
			'headline' => 'Builder headline',
		] );

		apSeoAddSchema( $builder );

		$entries = app( SchemaCollector::class )->all();

		expect( $entries )->toHaveCount( 1 );
		expect( $entries[0] )->toBe( $builder->toArray() );
	} );

	it( 'gives each fresh application container its own empty collector', function (): void {
		app( SchemaCollector::class )->add( [ '@type' => 'Article', 'headline' => 'First request' ] );

		expect( app( SchemaCollector::class )->all() )->toHaveCount( 1 );

		// Rebooting the application container mirrors what happens between
		// real HTTP requests under PHP-FPM: a fresh container, a fresh
		// singleton, no leaked entries from the previous request.
		$this->refreshApplication();

		expect( app( SchemaCollector::class )->all() )->toBe( [] );
	} );
} );
