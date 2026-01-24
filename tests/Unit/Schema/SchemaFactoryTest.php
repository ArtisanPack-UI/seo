<?php

/**
 * SchemaFactory Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\SchemaTypeContract;
use ArtisanPackUI\SEO\Schema\Builders\ArticleSchema;
use ArtisanPackUI\SEO\Schema\Builders\OrganizationSchema;
use ArtisanPackUI\SEO\Schema\Builders\WebPageSchema;
use ArtisanPackUI\SEO\Schema\SchemaFactory;

describe( 'SchemaFactory', function (): void {

	it( 'creates Organization schema builder', function (): void {
		$factory = new SchemaFactory();

		$builder = $factory->make( 'Organization' );

		expect( $builder )->toBeInstanceOf( SchemaTypeContract::class )
			->and( $builder )->toBeInstanceOf( OrganizationSchema::class );
	} );

	it( 'creates WebPage schema builder', function (): void {
		$factory = new SchemaFactory();

		$builder = $factory->make( 'WebPage' );

		expect( $builder )->toBeInstanceOf( WebPageSchema::class );
	} );

	it( 'creates Article schema builder', function (): void {
		$factory = new SchemaFactory();

		$builder = $factory->make( 'Article' );

		expect( $builder )->toBeInstanceOf( ArticleSchema::class );
	} );

	it( 'throws exception for unknown schema type', function (): void {
		$factory = new SchemaFactory();

		$factory->make( 'UnknownType' );
	} )->throws( InvalidArgumentException::class );

	it( 'supports all expected schema types', function (): void {
		$factory = new SchemaFactory();

		$expectedTypes = [
			'Organization',
			'LocalBusiness',
			'WebSite',
			'WebPage',
			'Article',
			'BlogPosting',
			'Product',
			'Service',
			'Event',
			'FAQPage',
			'BreadcrumbList',
			'Review',
			'AggregateRating',
		];

		foreach ( $expectedTypes as $type ) {
			expect( $factory->supports( $type ) )->toBeTrue();
		}
	} );

	it( 'returns all supported types', function (): void {
		$factory = new SchemaFactory();

		$types = $factory->getSupportedTypes();

		expect( $types )->toContain( 'Organization' )
			->and( $types )->toContain( 'Article' )
			->and( $types )->toContain( 'Product' );
	} );

	it( 'allows registering custom schema types', function (): void {
		$factory = new SchemaFactory();

		expect( $factory->supports( 'CustomType' ) )->toBeFalse();

		$factory->register( 'CustomType', OrganizationSchema::class );

		expect( $factory->supports( 'CustomType' ) )->toBeTrue();
	} );

	it( 'passes data to builder', function (): void {
		$factory = new SchemaFactory();

		$builder = $factory->make( 'Organization', [ 'name' => 'Test Org' ] );
		$schema  = $builder->generate();

		expect( $schema['name'] )->toBe( 'Test Org' );
	} );

} );
