<?php

/**
 * OrganizationSchema Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Schema\Builders\LocalBusinessSchema;
use ArtisanPackUI\SEO\Schema\Builders\OrganizationSchema;

describe( 'OrganizationSchema', function (): void {

	it( 'returns correct type', function (): void {
		$builder = new OrganizationSchema();

		expect( $builder->getType() )->toBe( 'Organization' );
	} );

	it( 'generates basic organization schema', function (): void {
		$builder = new OrganizationSchema( [
			'name' => 'Test Organization',
			'url'  => 'https://example.com',
		] );

		$schema = $builder->generate();

		expect( $schema['@context'] )->toBe( 'https://schema.org' )
			->and( $schema['@type'] )->toBe( 'Organization' )
			->and( $schema['name'] )->toBe( 'Test Organization' )
			->and( $schema['url'] )->toBe( 'https://example.com' );
	} );

	it( 'includes logo when provided', function (): void {
		$builder = new OrganizationSchema( [
			'name' => 'Test Organization',
			'logo' => 'https://example.com/logo.png',
		] );

		$schema = $builder->generate();

		expect( $schema['logo'] )->toHaveKey( '@type' )
			->and( $schema['logo']['@type'] )->toBe( 'ImageObject' )
			->and( $schema['logo']['url'] )->toBe( 'https://example.com/logo.png' );
	} );

	it( 'includes contact information', function (): void {
		$builder = new OrganizationSchema( [
			'name'  => 'Test Organization',
			'email' => 'info@example.com',
			'phone' => '+1-555-555-5555',
		] );

		$schema = $builder->generate();

		expect( $schema['email'] )->toBe( 'info@example.com' )
			->and( $schema['telephone'] )->toBe( '+1-555-555-5555' );
	} );

	it( 'includes address when provided', function (): void {
		$builder = new OrganizationSchema( [
			'name'    => 'Test Organization',
			'address' => [
				'street'  => '123 Main St',
				'city'    => 'Springfield',
				'state'   => 'IL',
				'zip'     => '62701',
				'country' => 'US',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['address']['@type'] )->toBe( 'PostalAddress' )
			->and( $schema['address']['streetAddress'] )->toBe( '123 Main St' )
			->and( $schema['address']['addressLocality'] )->toBe( 'Springfield' )
			->and( $schema['address']['addressRegion'] )->toBe( 'IL' )
			->and( $schema['address']['postalCode'] )->toBe( '62701' )
			->and( $schema['address']['addressCountry'] )->toBe( 'US' );
	} );

	it( 'includes sameAs links', function (): void {
		$builder = new OrganizationSchema( [
			'name'   => 'Test Organization',
			'sameAs' => [
				'https://facebook.com/testorg',
				'https://twitter.com/testorg',
				'https://linkedin.com/company/testorg',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['sameAs'] )->toHaveCount( 3 )
			->and( $schema['sameAs'][0] )->toBe( 'https://facebook.com/testorg' );
	} );

	it( 'filters out empty values', function (): void {
		$builder = new OrganizationSchema( [
			'name'        => 'Test Organization',
			'email'       => null,
			'phone'       => '',
			'description' => null,
		] );

		$schema = $builder->generate();

		expect( $schema )->not->toHaveKey( 'email' )
			->and( $schema )->not->toHaveKey( 'telephone' )
			->and( $schema )->not->toHaveKey( 'description' );
	} );

} );

describe( 'LocalBusinessSchema', function (): void {

	it( 'returns correct type', function (): void {
		$builder = new LocalBusinessSchema();

		expect( $builder->getType() )->toBe( 'LocalBusiness' );
	} );

	it( 'includes price range', function (): void {
		$builder = new LocalBusinessSchema( [
			'name'       => 'Test Business',
			'priceRange' => '$$',
		] );

		$schema = $builder->generate();

		expect( $schema['@type'] )->toBe( 'LocalBusiness' )
			->and( $schema['priceRange'] )->toBe( '$$' );
	} );

	it( 'includes opening hours', function (): void {
		$builder = new LocalBusinessSchema( [
			'name'         => 'Test Business',
			'openingHours' => [
				[
					'dayOfWeek' => [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ],
					'opens'     => '09:00',
					'closes'    => '17:00',
				],
			],
		] );

		$schema = $builder->generate();

		expect( $schema['openingHoursSpecification'] )->toHaveCount( 1 )
			->and( $schema['openingHoursSpecification'][0]['@type'] )->toBe( 'OpeningHoursSpecification' )
			->and( $schema['openingHoursSpecification'][0]['opens'] )->toBe( '09:00' );
	} );

	it( 'includes geo coordinates', function (): void {
		$builder = new LocalBusinessSchema( [
			'name' => 'Test Business',
			'geo'  => [
				'latitude'  => 40.7128,
				'longitude' => -74.0060,
			],
		] );

		$schema = $builder->generate();

		expect( $schema['geo']['@type'] )->toBe( 'GeoCoordinates' )
			->and( $schema['geo']['latitude'] )->toBe( 40.7128 )
			->and( $schema['geo']['longitude'] )->toBe( -74.0060 );
	} );

} );
