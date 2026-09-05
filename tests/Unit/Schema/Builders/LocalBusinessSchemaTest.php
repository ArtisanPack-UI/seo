<?php

/**
 * LocalBusinessSchema Tests.
 *
 * Extracted from OrganizationSchemaTest to keep LocalBusiness-specific
 * cases discoverable and to prevent regressions from hiding under an
 * unexpected suite name.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Schema\Builders\LocalBusinessSchema;
use Carbon\Carbon;

describe( 'LocalBusinessSchema opening hours', function (): void {

	it( 'drops the flat openingHours when a structured spec is provided', function (): void {
		$schema = ( new LocalBusinessSchema( [
			'name'         => 'Acme',
			'openingHours' => [
				[ 'dayOfWeek' => 'Monday', 'opens' => '09:00', 'closes' => '17:00' ],
			],
		] ) )->generate();

		expect( $schema )->toHaveKey( 'openingHoursSpecification' )
			->and( $schema )->not->toHaveKey( 'openingHours' );
	} );

	it( 'accepts a Carbon instance as validFrom', function (): void {
		$schema = ( new LocalBusinessSchema( [
			'name'         => 'Acme',
			'openingHours' => [
				[
					'dayOfWeek' => 'Friday',
					'opens'     => '09:00',
					'closes'    => '17:00',
					'validFrom' => Carbon::create( 2026, 12, 25 ),
				],
			],
		] ) )->generate();

		expect( $schema['openingHoursSpecification'][0]['validFrom'] ?? null )->toBe( '2026-12-25' );
	} );

	it( 'drops a validFrom string that is not YYYY-MM-DD, with a warning', function (): void {
		Illuminate\Support\Facades\Log::spy();

		$schema = ( new LocalBusinessSchema( [
			'name'         => 'Acme',
			'openingHours' => [
				[
					'dayOfWeek' => 'Friday',
					'opens'     => '09:00',
					'closes'    => '17:00',
					'validFrom' => '12/25',
				],
			],
		] ) )->generate();

		expect( $schema['openingHoursSpecification'][0] ?? [] )->not->toHaveKey( 'validFrom' );
		Illuminate\Support\Facades\Log::shouldHaveReceived( 'warning' )->atLeast()->once();
	} );

} );

describe( 'OrganizationSchema sameAs + logo width/height', function (): void {

	it( 'rejects non-URL strings from sameAs', function (): void {
		$schema = ( new ArtisanPackUI\SEO\Schema\Builders\OrganizationSchema( [
			'name'   => 'Acme',
			'sameAs' => [
				'https://twitter.com/acme',
				'not-a-url',
				'   ',
			],
		] ) )->generate();

		expect( $schema['sameAs'] ?? [] )->toBe( [ 'https://twitter.com/acme' ] );
	} );

	it( 'emits width/height on the logo ImageObject when provided as an array', function (): void {
		$schema = ( new ArtisanPackUI\SEO\Schema\Builders\OrganizationSchema( [
			'name' => 'Acme',
			'logo' => [ 'url' => 'https://example.com/logo.png', 'width' => 512, 'height' => 128 ],
		] ) )->generate();

		expect( $schema['logo']['url'] ?? null )->toBe( 'https://example.com/logo.png' )
			->and( $schema['logo']['width'] ?? null )->toBe( 512 )
			->and( $schema['logo']['height'] ?? null )->toBe( 128 );
	} );

	it( 'still accepts a plain string logo URL for BC', function (): void {
		$schema = ( new ArtisanPackUI\SEO\Schema\Builders\OrganizationSchema( [
			'name' => 'Acme',
			'logo' => 'https://example.com/logo.png',
		] ) )->generate();

		expect( $schema['logo']['url'] ?? null )->toBe( 'https://example.com/logo.png' )
			->and( $schema['logo'] ?? [] )->not->toHaveKey( 'width' );
	} );

} );
