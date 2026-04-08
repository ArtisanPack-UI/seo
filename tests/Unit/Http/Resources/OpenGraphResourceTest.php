<?php

/**
 * OpenGraphResource Tests.
 *
 * Unit tests for OpenGraphResource.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\OpenGraphDTO;
use ArtisanPackUI\SEO\Http\Resources\OpenGraphResource;
use Illuminate\Http\Request;

describe( 'OpenGraphResource', function (): void {

	it( 'serializes OpenGraphDTO correctly', function (): void {
		$dto = new OpenGraphDTO(
			title: 'OG Title',
			description: 'OG Description',
			image: 'https://example.com/image.jpg',
			url: 'https://example.com/page',
			type: 'article',
			siteName: 'My Site',
			locale: 'en_US',
		);

		$resource = new OpenGraphResource( $dto );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['title'] )->toBe( 'OG Title' )
			->and( $result['description'] )->toBe( 'OG Description' )
			->and( $result['image'] )->toBe( 'https://example.com/image.jpg' )
			->and( $result['url'] )->toBe( 'https://example.com/page' )
			->and( $result['type'] )->toBe( 'article' )
			->and( $result['site_name'] )->toBe( 'My Site' )
			->and( $result['locale'] )->toBe( 'en_US' );
	} );

	it( 'handles null description and image', function (): void {
		$dto = new OpenGraphDTO(
			title: 'Title',
			description: null,
			image: null,
			url: 'https://example.com',
		);

		$resource = new OpenGraphResource( $dto );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['description'] )->toBeNull()
			->and( $result['image'] )->toBeNull();
	} );
} );
