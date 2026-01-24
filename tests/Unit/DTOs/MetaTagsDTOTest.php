<?php

/**
 * MetaTagsDTO Tests.
 *
 * Unit tests for the MetaTagsDTO.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\MetaTagsDTO;

describe( 'MetaTagsDTO', function (): void {

	it( 'can be instantiated with required parameters', function (): void {
		$dto = new MetaTagsDTO(
			title: 'Test Page',
			description: 'Test description',
			canonical: 'https://example.com/test',
			robots: 'index, follow',
		);

		expect( $dto->title )->toBe( 'Test Page' )
			->and( $dto->description )->toBe( 'Test description' )
			->and( $dto->canonical )->toBe( 'https://example.com/test' )
			->and( $dto->robots )->toBe( 'index, follow' )
			->and( $dto->additionalMeta )->toBe( [] );
	} );

	it( 'can be instantiated with additional meta', function (): void {
		$additionalMeta = [
			'author'   => 'John Doe',
			'keywords' => 'test, keywords',
		];

		$dto = new MetaTagsDTO(
			title: 'Test Page',
			description: 'Test description',
			canonical: 'https://example.com/test',
			robots: 'index, follow',
			additionalMeta: $additionalMeta,
		);

		expect( $dto->additionalMeta )->toBe( $additionalMeta );
	} );

	it( 'accepts null description', function (): void {
		$dto = new MetaTagsDTO(
			title: 'Test Page',
			description: null,
			canonical: 'https://example.com/test',
			robots: 'index, follow',
		);

		expect( $dto->description )->toBeNull();
	} );

	it( 'converts to array correctly', function (): void {
		$additionalMeta = [ 'author' => 'Jane Doe' ];

		$dto = new MetaTagsDTO(
			title: 'Test Title',
			description: 'Test Description',
			canonical: 'https://example.com',
			robots: 'noindex, nofollow',
			additionalMeta: $additionalMeta,
		);

		$array = $dto->toArray();

		expect( $array )->toBe( [
			'title'           => 'Test Title',
			'description'     => 'Test Description',
			'canonical'       => 'https://example.com',
			'robots'          => 'noindex, nofollow',
			'additional_meta' => $additionalMeta,
		] );
	} );

	it( 'can be created from array', function (): void {
		$data = [
			'title'           => 'From Array Title',
			'description'     => 'From Array Description',
			'canonical'       => 'https://example.com/from-array',
			'robots'          => 'noindex',
			'additional_meta' => [ 'keywords' => 'array, test' ],
		];

		$dto = MetaTagsDTO::fromArray( $data );

		expect( $dto->title )->toBe( 'From Array Title' )
			->and( $dto->description )->toBe( 'From Array Description' )
			->and( $dto->canonical )->toBe( 'https://example.com/from-array' )
			->and( $dto->robots )->toBe( 'noindex' )
			->and( $dto->additionalMeta )->toBe( [ 'keywords' => 'array, test' ] );
	} );

	it( 'uses default values when creating from incomplete array', function (): void {
		$dto = MetaTagsDTO::fromArray( [] );

		expect( $dto->title )->toBe( '' )
			->and( $dto->description )->toBeNull()
			->and( $dto->canonical )->toBe( '' )
			->and( $dto->robots )->toBe( 'index, follow' )
			->and( $dto->additionalMeta )->toBe( [] );
	} );

	it( 'is readonly and immutable', function (): void {
		$dto = new MetaTagsDTO(
			title: 'Original',
			description: 'Original description',
			canonical: 'https://example.com',
			robots: 'index, follow',
		);

		$reflection = new ReflectionClass( $dto );

		expect( $reflection->isReadOnly() )->toBeTrue();
	} );

} );
