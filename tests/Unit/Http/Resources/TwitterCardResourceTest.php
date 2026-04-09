<?php

/**
 * TwitterCardResource Tests.
 *
 * Unit tests for TwitterCardResource.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\TwitterCardDTO;
use ArtisanPackUI\SEO\Http\Resources\TwitterCardResource;
use Illuminate\Http\Request;

describe( 'TwitterCardResource', function (): void {

	it( 'serializes TwitterCardDTO correctly', function (): void {
		$dto = new TwitterCardDTO(
			card: 'summary_large_image',
			title: 'Twitter Title',
			description: 'Twitter Description',
			image: 'https://example.com/image.jpg',
			site: '@mysite',
			creator: '@author',
		);

		$resource = new TwitterCardResource( $dto );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['card'] )->toBe( 'summary_large_image' )
			->and( $result['title'] )->toBe( 'Twitter Title' )
			->and( $result['description'] )->toBe( 'Twitter Description' )
			->and( $result['image'] )->toBe( 'https://example.com/image.jpg' )
			->and( $result['site'] )->toBe( '@mysite' )
			->and( $result['creator'] )->toBe( '@author' );
	} );

	it( 'handles nullable fields', function (): void {
		$dto = new TwitterCardDTO(
			card: 'summary',
			title: 'Title',
		);

		$resource = new TwitterCardResource( $dto );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['description'] )->toBeNull()
			->and( $result['image'] )->toBeNull()
			->and( $result['site'] )->toBeNull()
			->and( $result['creator'] )->toBeNull();
	} );
} );
