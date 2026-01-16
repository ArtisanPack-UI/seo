<?php

/**
 * BreadcrumbListSchema Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Schema\Builders\BreadcrumbListSchema;

describe( 'BreadcrumbListSchema', function (): void {

	it( 'returns correct type', function (): void {
		$builder = new BreadcrumbListSchema();

		expect( $builder->getType() )->toBe( 'BreadcrumbList' );
	} );

	it( 'generates basic breadcrumb schema', function (): void {
		$builder = new BreadcrumbListSchema( [
			'items' => [
				[ 'name' => 'Home', 'url' => 'https://example.com' ],
			],
		] );

		$schema = $builder->generate();

		expect( $schema['@type'] )->toBe( 'BreadcrumbList' )
			->and( $schema['itemListElement'] )->toHaveCount( 1 );
	} );

	it( 'generates breadcrumb with multiple items', function (): void {
		$builder = new BreadcrumbListSchema( [
			'items' => [
				[ 'name' => 'Home', 'url' => 'https://example.com' ],
				[ 'name' => 'Products', 'url' => 'https://example.com/products' ],
				[ 'name' => 'Widget', 'url' => 'https://example.com/products/widget' ],
			],
		] );

		$schema = $builder->generate();

		expect( $schema['itemListElement'] )->toHaveCount( 3 )
			->and( $schema['itemListElement'][0]['@type'] )->toBe( 'ListItem' )
			->and( $schema['itemListElement'][0]['position'] )->toBe( 1 )
			->and( $schema['itemListElement'][0]['name'] )->toBe( 'Home' )
			->and( $schema['itemListElement'][0]['item'] )->toBe( 'https://example.com' )
			->and( $schema['itemListElement'][1]['position'] )->toBe( 2 )
			->and( $schema['itemListElement'][2]['position'] )->toBe( 3 );
	} );

	it( 'assigns sequential position numbers', function (): void {
		$builder = new BreadcrumbListSchema( [
			'items' => [
				[ 'name' => 'A', 'url' => 'https://example.com/a' ],
				[ 'name' => 'B', 'url' => 'https://example.com/b' ],
				[ 'name' => 'C', 'url' => 'https://example.com/c' ],
				[ 'name' => 'D', 'url' => 'https://example.com/d' ],
				[ 'name' => 'E', 'url' => 'https://example.com/e' ],
			],
		] );

		$schema = $builder->generate();

		foreach ( $schema['itemListElement'] as $index => $item ) {
			expect( $item['position'] )->toBe( $index + 1 );
		}
	} );

	it( 'handles empty items array', function (): void {
		$builder = new BreadcrumbListSchema( [
			'items' => [],
		] );

		$schema = $builder->generate();

		// Empty items should not include itemListElement key
		expect( $schema )->not->toHaveKey( 'itemListElement' );
	} );

	it( 'includes item URL as item property', function (): void {
		$builder = new BreadcrumbListSchema( [
			'items' => [
				[ 'name' => 'Test', 'url' => 'https://example.com/test' ],
			],
		] );

		$schema = $builder->generate();

		expect( $schema['itemListElement'][0]['item'] )->toBe( 'https://example.com/test' );
	} );

	it( 'handles items without URL', function (): void {
		$builder = new BreadcrumbListSchema( [
			'items' => [
				[ 'name' => 'Current Page' ],
			],
		] );

		$schema = $builder->generate();

		expect( $schema['itemListElement'][0]['name'] )->toBe( 'Current Page' )
			->and( $schema['itemListElement'][0] )->not->toHaveKey( 'item' );
	} );

	it( 'preserves order of breadcrumb items', function (): void {
		$builder = new BreadcrumbListSchema( [
			'items' => [
				[ 'name' => 'First', 'url' => 'https://example.com/first' ],
				[ 'name' => 'Second', 'url' => 'https://example.com/second' ],
				[ 'name' => 'Third', 'url' => 'https://example.com/third' ],
			],
		] );

		$schema = $builder->generate();

		expect( $schema['itemListElement'][0]['name'] )->toBe( 'First' )
			->and( $schema['itemListElement'][1]['name'] )->toBe( 'Second' )
			->and( $schema['itemListElement'][2]['name'] )->toBe( 'Third' );
	} );

} );
