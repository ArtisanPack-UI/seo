<?php

/**
 * HreflangResource Tests.
 *
 * Unit tests for HreflangResource.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Http\Resources\HreflangResource;
use Illuminate\Http\Request;

describe( 'HreflangResource', function (): void {

	it( 'serializes hreflang data correctly', function (): void {
		$data = [
			'hreflang' => 'en',
			'href'     => 'https://example.com/en/page',
		];

		$resource = new HreflangResource( $data );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['hreflang'] )->toBe( 'en' )
			->and( $result['href'] )->toBe( 'https://example.com/en/page' );
	} );

	it( 'handles x-default hreflang', function (): void {
		$data = [
			'hreflang' => 'x-default',
			'href'     => 'https://example.com/page',
		];

		$resource = new HreflangResource( $data );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['hreflang'] )->toBe( 'x-default' );
	} );

	it( 'serializes collection correctly', function (): void {
		$items = [
			[ 'hreflang' => 'en', 'href' => 'https://example.com/en' ],
			[ 'hreflang' => 'fr', 'href' => 'https://example.com/fr' ],
			[ 'hreflang' => 'x-default', 'href' => 'https://example.com/en' ],
		];

		$collection = HreflangResource::collection( $items );
		$result     = $collection->toArray( Request::create( '/' ) );

		expect( $result )->toHaveCount( 3 )
			->and( $result[0]['hreflang'] )->toBe( 'en' )
			->and( $result[1]['hreflang'] )->toBe( 'fr' )
			->and( $result[2]['hreflang'] )->toBe( 'x-default' );
	} );
} );
