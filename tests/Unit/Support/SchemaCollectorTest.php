<?php

/**
 * SchemaCollector Tests.
 *
 * Unit tests for the request-scoped SchemaCollector.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Support\SchemaCollector;

describe( 'SchemaCollector', function (): void {

	it( 'starts empty', function (): void {
		$collector = new SchemaCollector();

		expect( $collector->all() )->toBe( [] );
	} );

	it( 'collects entries in order via add()', function (): void {
		$collector = new SchemaCollector();

		$collector->add( [ '@type' => 'Article', 'headline' => 'One' ] );
		$collector->add( [ '@type' => 'Product', 'name' => 'Two' ] );

		expect( $collector->all() )->toBe( [
			[ '@type' => 'Article', 'headline' => 'One' ],
			[ '@type' => 'Product', 'name' => 'Two' ],
		] );
	} );

	it( 'all() does not clear the collector', function (): void {
		$collector = new SchemaCollector();
		$collector->add( [ '@type' => 'Article' ] );

		$collector->all();

		expect( $collector->all() )->toHaveCount( 1 );
	} );

	it( 'flush() returns entries and clears the collector', function (): void {
		$collector = new SchemaCollector();
		$collector->add( [ '@type' => 'Article' ] );
		$collector->add( [ '@type' => 'Product' ] );

		$flushed = $collector->flush();

		expect( $flushed )->toHaveCount( 2 );
		expect( $collector->all() )->toBe( [] );
	} );

	it( 'flush() on an empty collector returns an empty array', function (): void {
		$collector = new SchemaCollector();

		expect( $collector->flush() )->toBe( [] );
	} );

	it( 'accepts entries after a flush', function (): void {
		$collector = new SchemaCollector();
		$collector->add( [ '@type' => 'Article' ] );
		$collector->flush();

		$collector->add( [ '@type' => 'Product' ] );

		expect( $collector->all() )->toBe( [
			[ '@type' => 'Product' ],
		] );
	} );
} );
