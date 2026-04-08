<?php

/**
 * MetaTagsResource Tests.
 *
 * Unit tests for MetaTagsResource.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\MetaTagsDTO;
use ArtisanPackUI\SEO\Http\Resources\MetaTagsResource;
use Illuminate\Http\Request;

describe( 'MetaTagsResource', function (): void {

	it( 'serializes MetaTagsDTO correctly', function (): void {
		$dto = new MetaTagsDTO(
			title: 'Test Title',
			description: 'Test description for the page.',
			canonical: 'https://example.com/page',
			robots: 'index, follow',
			additionalMeta: [ 'author' => 'John' ],
		);

		$resource = new MetaTagsResource( $dto );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['title'] )->toBe( 'Test Title' )
			->and( $result['description'] )->toBe( 'Test description for the page.' )
			->and( $result['canonical'] )->toBe( 'https://example.com/page' )
			->and( $result['robots'] )->toBe( 'index, follow' )
			->and( $result['additional_meta'] )->toBe( [ 'author' => 'John' ] )
			->and( $result['title_length'] )->toBe( 10 )
			->and( $result['description_length'] )->toBe( 30 );
	} );

	it( 'includes title warning when exceeding max length', function (): void {
		$longTitle = str_repeat( 'a', 65 );
		$dto       = new MetaTagsDTO(
			title: $longTitle,
			description: null,
			canonical: 'https://example.com',
			robots: 'index, follow',
		);

		$resource = new MetaTagsResource( $dto );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['title_warning'] )->not->toBeNull()
			->and( $result['title_length'] )->toBe( 65 );
	} );

	it( 'does not include title warning within limit', function (): void {
		$dto = new MetaTagsDTO(
			title: 'Short Title',
			description: null,
			canonical: 'https://example.com',
			robots: 'index, follow',
		);

		$resource = new MetaTagsResource( $dto );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['title_warning'] )->toBeNull();
	} );

	it( 'includes description warning when exceeding max length', function (): void {
		$longDescription = str_repeat( 'a', 165 );
		$dto             = new MetaTagsDTO(
			title: 'Title',
			description: $longDescription,
			canonical: 'https://example.com',
			robots: 'index, follow',
		);

		$resource = new MetaTagsResource( $dto );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['description_warning'] )->not->toBeNull()
			->and( $result['description_length'] )->toBe( 165 );
	} );

	it( 'handles null description', function (): void {
		$dto = new MetaTagsDTO(
			title: 'Title',
			description: null,
			canonical: 'https://example.com',
			robots: 'index, follow',
		);

		$resource = new MetaTagsResource( $dto );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['description'] )->toBeNull()
			->and( $result['description_length'] )->toBe( 0 )
			->and( $result['description_warning'] )->toBeNull();
	} );
} );
