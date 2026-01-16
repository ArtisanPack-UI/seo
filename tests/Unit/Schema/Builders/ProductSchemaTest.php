<?php

/**
 * Product and Service Schema Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Schema\Builders\ProductSchema;
use ArtisanPackUI\SEO\Schema\Builders\ServiceSchema;

describe( 'ProductSchema', function (): void {

	it( 'returns correct type', function (): void {
		$builder = new ProductSchema();

		expect( $builder->getType() )->toBe( 'Product' );
	} );

	it( 'generates basic product schema', function (): void {
		$builder = new ProductSchema( [
			'name'        => 'Test Product',
			'description' => 'A great test product.',
			'image'       => 'https://example.com/product.jpg',
		] );

		$schema = $builder->generate();

		expect( $schema['@type'] )->toBe( 'Product' )
			->and( $schema['name'] )->toBe( 'Test Product' )
			->and( $schema['description'] )->toBe( 'A great test product.' )
			->and( $schema['image']['url'] )->toBe( 'https://example.com/product.jpg' );
	} );

	it( 'includes SKU and identifiers', function (): void {
		$builder = new ProductSchema( [
			'name' => 'Test Product',
			'sku'  => 'SKU-12345',
			'gtin' => '0123456789012',
			'mpn'  => 'MPN-67890',
		] );

		$schema = $builder->generate();

		expect( $schema['sku'] )->toBe( 'SKU-12345' )
			->and( $schema['gtin'] )->toBe( '0123456789012' )
			->and( $schema['mpn'] )->toBe( 'MPN-67890' );
	} );

	it( 'includes brand as string', function (): void {
		$builder = new ProductSchema( [
			'name'  => 'Test Product',
			'brand' => 'Test Brand',
		] );

		$schema = $builder->generate();

		expect( $schema['brand']['@type'] )->toBe( 'Brand' )
			->and( $schema['brand']['name'] )->toBe( 'Test Brand' );
	} );

	it( 'includes brand as array', function (): void {
		$builder = new ProductSchema( [
			'name'  => 'Test Product',
			'brand' => [
				'name' => 'Premium Brand',
				'url'  => 'https://brand.com',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['brand']['name'] )->toBe( 'Premium Brand' )
			->and( $schema['brand']['url'] )->toBe( 'https://brand.com' );
	} );

	it( 'includes offers with price', function (): void {
		$builder = new ProductSchema( [
			'name'   => 'Test Product',
			'offers' => [
				'price'        => 29.99,
				'currency'     => 'USD',
				'availability' => 'InStock',
				'url'          => 'https://example.com/product',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['offers']['@type'] )->toBe( 'Offer' )
			->and( $schema['offers']['price'] )->toBe( 29.99 )
			->and( $schema['offers']['priceCurrency'] )->toBe( 'USD' )
			->and( $schema['offers']['availability'] )->toBe( 'https://schema.org/InStock' );
	} );

	it( 'maps availability statuses correctly', function (): void {
		$testCases = [
			'InStock'      => 'https://schema.org/InStock',
			'in_stock'     => 'https://schema.org/InStock',
			'OutOfStock'   => 'https://schema.org/OutOfStock',
			'PreOrder'     => 'https://schema.org/PreOrder',
			'BackOrder'    => 'https://schema.org/BackOrder',
			'Discontinued' => 'https://schema.org/Discontinued',
		];

		foreach ( $testCases as $input => $expected ) {
			$builder = new ProductSchema( [
				'name'   => 'Test',
				'offers' => [
					'price'        => 10,
					'availability' => $input,
				],
			] );

			$schema = $builder->generate();
			expect( $schema['offers']['availability'] )->toBe( $expected );
		}
	} );

	it( 'includes aggregate rating', function (): void {
		$builder = new ProductSchema( [
			'name'            => 'Test Product',
			'aggregateRating' => [
				'value' => 4.5,
				'count' => 125,
			],
		] );

		$schema = $builder->generate();

		expect( $schema['aggregateRating']['@type'] )->toBe( 'AggregateRating' )
			->and( $schema['aggregateRating']['ratingValue'] )->toBe( 4.5 )
			->and( $schema['aggregateRating']['ratingCount'] )->toBe( 125 );
	} );

	it( 'includes reviews', function (): void {
		$builder = new ProductSchema( [
			'name'    => 'Test Product',
			'reviews' => [
				[
					'author'        => 'John Doe',
					'rating'        => 5,
					'body'          => 'Great product!',
					'datePublished' => '2024-01-15',
				],
			],
		] );

		$schema = $builder->generate();

		expect( $schema['review'] )->toHaveCount( 1 )
			->and( $schema['review'][0]['@type'] )->toBe( 'Review' )
			->and( $schema['review'][0]['reviewRating']['ratingValue'] )->toBe( 5 );
	} );

	it( 'includes item condition', function (): void {
		$builder = new ProductSchema( [
			'name'   => 'Test Product',
			'offers' => [
				'price'         => 10,
				'itemCondition' => 'new',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['offers']['itemCondition'] )->toBe( 'https://schema.org/NewCondition' );
	} );

} );

describe( 'ServiceSchema', function (): void {

	beforeEach( function (): void {
		config()->set( 'seo.schema.organization.name', 'Test Organization' );
		config()->set( 'seo.schema.organization.url', 'https://example.com' );
		config()->set( 'app.name', 'Test App' );
		config()->set( 'app.url', 'https://example.com' );
	} );

	it( 'returns correct type', function (): void {
		$builder = new ServiceSchema();

		expect( $builder->getType() )->toBe( 'Service' );
	} );

	it( 'generates basic service schema', function (): void {
		$builder = new ServiceSchema( [
			'name'        => 'Web Development Service',
			'description' => 'Professional web development services.',
		] );

		$schema = $builder->generate();

		expect( $schema['@type'] )->toBe( 'Service' )
			->and( $schema['name'] )->toBe( 'Web Development Service' )
			->and( $schema['description'] )->toBe( 'Professional web development services.' );
	} );

	it( 'includes default provider from config', function (): void {
		$builder = new ServiceSchema( [
			'name' => 'Test Service',
		] );

		$schema = $builder->generate();

		expect( $schema['provider']['@type'] )->toBe( 'Organization' )
			->and( $schema['provider']['name'] )->toBe( 'Test Organization' );
	} );

	it( 'includes custom provider', function (): void {
		$builder = new ServiceSchema( [
			'name'     => 'Test Service',
			'provider' => [
				'name' => 'Custom Provider',
				'url'  => 'https://provider.com',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['provider']['name'] )->toBe( 'Custom Provider' );
	} );

	it( 'includes area served', function (): void {
		$builder = new ServiceSchema( [
			'name'       => 'Test Service',
			'areaServed' => 'United States',
		] );

		$schema = $builder->generate();

		expect( $schema['areaServed'] )->toBe( 'United States' );
	} );

	it( 'includes offers', function (): void {
		$builder = new ServiceSchema( [
			'name'   => 'Test Service',
			'offers' => [
				'price'    => 100,
				'currency' => 'USD',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['offers']['@type'] )->toBe( 'Offer' )
			->and( $schema['offers']['price'] )->toBe( 100 );
	} );

} );
