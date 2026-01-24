<?php

/**
 * EventSchema Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Schema\Builders\EventSchema;

describe( 'EventSchema', function (): void {

	it( 'returns correct type', function (): void {
		$builder = new EventSchema();

		expect( $builder->getType() )->toBe( 'Event' );
	} );

	it( 'generates basic event schema', function (): void {
		$builder = new EventSchema( [
			'name'        => 'Tech Conference 2024',
			'description' => 'Annual technology conference.',
			'startDate'   => '2024-06-15T09:00:00+00:00',
			'endDate'     => '2024-06-17T18:00:00+00:00',
		] );

		$schema = $builder->generate();

		expect( $schema['@type'] )->toBe( 'Event' )
			->and( $schema['name'] )->toBe( 'Tech Conference 2024' )
			->and( $schema['description'] )->toBe( 'Annual technology conference.' )
			->and( $schema['startDate'] )->toBe( '2024-06-15T09:00:00+00:00' )
			->and( $schema['endDate'] )->toBe( '2024-06-17T18:00:00+00:00' );
	} );

	it( 'includes physical location', function (): void {
		$builder = new EventSchema( [
			'name'     => 'Tech Conference',
			'location' => [
				'name'    => 'Convention Center',
				'address' => [
					'street'  => '123 Main St',
					'city'    => 'San Francisco',
					'state'   => 'CA',
					'zip'     => '94102',
					'country' => 'US',
				],
			],
		] );

		$schema = $builder->generate();

		expect( $schema['location']['@type'] )->toBe( 'Place' )
			->and( $schema['location']['name'] )->toBe( 'Convention Center' )
			->and( $schema['location']['address']['@type'] )->toBe( 'PostalAddress' )
			->and( $schema['location']['address']['addressLocality'] )->toBe( 'San Francisco' );
	} );

	it( 'includes virtual location', function (): void {
		$builder = new EventSchema( [
			'name'            => 'Online Webinar',
			'virtualLocation' => 'https://example.com/webinar',
		] );

		$schema = $builder->generate();

		expect( $schema['location']['@type'] )->toBe( 'VirtualLocation' )
			->and( $schema['location']['url'] )->toBe( 'https://example.com/webinar' );
	} );

	it( 'includes event status', function (): void {
		$builder = new EventSchema( [
			'name'        => 'Tech Conference',
			'eventStatus' => 'scheduled',
		] );

		$schema = $builder->generate();

		expect( $schema['eventStatus'] )->toBe( 'https://schema.org/EventScheduled' );
	} );

	it( 'maps event status correctly', function (): void {
		$testCases = [
			'scheduled'   => 'https://schema.org/EventScheduled',
			'cancelled'   => 'https://schema.org/EventCancelled',
			'postponed'   => 'https://schema.org/EventPostponed',
			'rescheduled' => 'https://schema.org/EventRescheduled',
			'movedOnline' => 'https://schema.org/EventMovedOnline',
		];

		foreach ( $testCases as $input => $expected ) {
			$builder = new EventSchema( [
				'name'        => 'Test Event',
				'eventStatus' => $input,
			] );

			$schema = $builder->generate();
			expect( $schema['eventStatus'] )->toBe( $expected );
		}
	} );

	it( 'includes event attendance mode', function (): void {
		$builder = new EventSchema( [
			'name'                => 'Hybrid Conference',
			'eventAttendanceMode' => 'mixed',
		] );

		$schema = $builder->generate();

		expect( $schema['eventAttendanceMode'] )->toBe( 'https://schema.org/MixedEventAttendanceMode' );
	} );

	it( 'maps attendance mode correctly', function (): void {
		$testCases = [
			'offline' => 'https://schema.org/OfflineEventAttendanceMode',
			'online'  => 'https://schema.org/OnlineEventAttendanceMode',
			'mixed'   => 'https://schema.org/MixedEventAttendanceMode',
		];

		foreach ( $testCases as $input => $expected ) {
			$builder = new EventSchema( [
				'name'                => 'Test Event',
				'eventAttendanceMode' => $input,
			] );

			$schema = $builder->generate();
			expect( $schema['eventAttendanceMode'] )->toBe( $expected );
		}
	} );

	it( 'includes organizer', function (): void {
		$builder = new EventSchema( [
			'name'      => 'Tech Conference',
			'organizer' => [
				'name' => 'Tech Corp',
				'url'  => 'https://techcorp.com',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['organizer']['@type'] )->toBe( 'Organization' )
			->and( $schema['organizer']['name'] )->toBe( 'Tech Corp' );
	} );

	it( 'includes performer', function (): void {
		$builder = new EventSchema( [
			'name'      => 'Concert',
			'performer' => [
				'name' => 'John Smith',
				'url'  => 'https://example.com/john-smith',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['performer']['@type'] )->toBe( 'Person' )
			->and( $schema['performer']['name'] )->toBe( 'John Smith' );
	} );

	it( 'includes offers', function (): void {
		$builder = new EventSchema( [
			'name'   => 'Tech Conference',
			'offers' => [
				'price'        => 299,
				'currency'     => 'USD',
				'availability' => 'InStock',
				'url'          => 'https://example.com/tickets',
				'validFrom'    => '2024-01-01T00:00:00+00:00',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['offers']['@type'] )->toBe( 'Offer' )
			->and( $schema['offers']['price'] )->toBe( 299 )
			->and( $schema['offers']['priceCurrency'] )->toBe( 'USD' )
			->and( $schema['offers']['availability'] )->toBe( 'https://schema.org/InStock' )
			->and( $schema['offers']['validFrom'] )->toBe( '2024-01-01T00:00:00+00:00' );
	} );

	it( 'includes image', function (): void {
		$builder = new EventSchema( [
			'name'  => 'Tech Conference',
			'image' => 'https://example.com/event-banner.jpg',
		] );

		$schema = $builder->generate();

		expect( $schema['image']['@type'] )->toBe( 'ImageObject' )
			->and( $schema['image']['url'] )->toBe( 'https://example.com/event-banner.jpg' );
	} );

} );
