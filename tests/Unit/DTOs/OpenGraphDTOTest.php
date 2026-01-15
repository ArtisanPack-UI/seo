<?php

/**
 * OpenGraphDTO Tests.
 *
 * Unit tests for the OpenGraphDTO.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\OpenGraphDTO;

describe( 'OpenGraphDTO', function (): void {

	it( 'can be instantiated with required parameters', function (): void {
		$dto = new OpenGraphDTO(
			title: 'OG Title',
			description: 'OG Description',
			image: 'https://example.com/image.jpg',
			url: 'https://example.com/page',
		);

		expect( $dto->title )->toBe( 'OG Title' )
			->and( $dto->description )->toBe( 'OG Description' )
			->and( $dto->image )->toBe( 'https://example.com/image.jpg' )
			->and( $dto->url )->toBe( 'https://example.com/page' )
			->and( $dto->type )->toBe( 'website' )
			->and( $dto->siteName )->toBe( '' )
			->and( $dto->locale )->toBe( 'en_US' );
	} );

	it( 'can be instantiated with all parameters', function (): void {
		$dto = new OpenGraphDTO(
			title: 'Article Title',
			description: 'Article description',
			image: 'https://example.com/article.jpg',
			url: 'https://example.com/articles/1',
			type: 'article',
			siteName: 'My Site',
			locale: 'es_ES',
		);

		expect( $dto->type )->toBe( 'article' )
			->and( $dto->siteName )->toBe( 'My Site' )
			->and( $dto->locale )->toBe( 'es_ES' );
	} );

	it( 'accepts null values for optional fields', function (): void {
		$dto = new OpenGraphDTO(
			title: 'Title',
			description: null,
			image: null,
			url: 'https://example.com',
		);

		expect( $dto->description )->toBeNull()
			->and( $dto->image )->toBeNull();
	} );

	it( 'converts to array with og prefixed keys', function (): void {
		$dto = new OpenGraphDTO(
			title: 'OG Title',
			description: 'OG Description',
			image: 'https://example.com/image.jpg',
			url: 'https://example.com',
			type: 'article',
			siteName: 'Test Site',
			locale: 'en_GB',
		);

		$array = $dto->toArray();

		expect( $array )->toBe( [
			'og:title'       => 'OG Title',
			'og:description' => 'OG Description',
			'og:image'       => 'https://example.com/image.jpg',
			'og:url'         => 'https://example.com',
			'og:type'        => 'article',
			'og:site_name'   => 'Test Site',
			'og:locale'      => 'en_GB',
		] );
	} );

	it( 'converts to filtered array excluding null and empty values', function (): void {
		$dto = new OpenGraphDTO(
			title: 'OG Title',
			description: null,
			image: null,
			url: 'https://example.com',
			type: 'website',
			siteName: '',
			locale: 'en_US',
		);

		$filtered = $dto->toArrayFiltered();

		expect( $filtered )->toHaveKeys( [ 'og:title', 'og:url', 'og:type', 'og:locale' ] )
			->and( $filtered )->not->toHaveKeys( [ 'og:description', 'og:image', 'og:site_name' ] );
	} );

	it( 'can be created from array with og prefixed keys', function (): void {
		$data = [
			'og:title'       => 'From OG Array',
			'og:description' => 'OG Description',
			'og:image'       => 'https://example.com/og.jpg',
			'og:url'         => 'https://example.com/og',
			'og:type'        => 'product',
			'og:site_name'   => 'OG Site',
			'og:locale'      => 'fr_FR',
		];

		$dto = OpenGraphDTO::fromArray( $data );

		expect( $dto->title )->toBe( 'From OG Array' )
			->and( $dto->description )->toBe( 'OG Description' )
			->and( $dto->image )->toBe( 'https://example.com/og.jpg' )
			->and( $dto->url )->toBe( 'https://example.com/og' )
			->and( $dto->type )->toBe( 'product' )
			->and( $dto->siteName )->toBe( 'OG Site' )
			->and( $dto->locale )->toBe( 'fr_FR' );
	} );

	it( 'can be created from array with unprefixed keys', function (): void {
		$data = [
			'title'       => 'Simple Title',
			'description' => 'Simple Description',
			'image'       => 'https://example.com/simple.jpg',
			'url'         => 'https://example.com/simple',
			'type'        => 'event',
			'site_name'   => 'Simple Site',
			'locale'      => 'de_DE',
		];

		$dto = OpenGraphDTO::fromArray( $data );

		expect( $dto->title )->toBe( 'Simple Title' )
			->and( $dto->type )->toBe( 'event' )
			->and( $dto->siteName )->toBe( 'Simple Site' );
	} );

	it( 'uses defaults when creating from empty array', function (): void {
		$dto = OpenGraphDTO::fromArray( [] );

		expect( $dto->title )->toBe( '' )
			->and( $dto->description )->toBeNull()
			->and( $dto->image )->toBeNull()
			->and( $dto->url )->toBe( '' )
			->and( $dto->type )->toBe( 'website' )
			->and( $dto->siteName )->toBe( '' )
			->and( $dto->locale )->toBe( 'en_US' );
	} );

	it( 'is readonly and immutable', function (): void {
		$dto = new OpenGraphDTO(
			title: 'Test',
			description: 'Description',
			image: null,
			url: 'https://example.com',
		);

		$reflection = new ReflectionClass( $dto );

		expect( $reflection->isReadOnly() )->toBeTrue();
	} );

} );
