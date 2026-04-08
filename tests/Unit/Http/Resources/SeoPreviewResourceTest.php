<?php

/**
 * SeoPreviewResource Tests.
 *
 * Unit tests for SeoPreviewResource.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\MetaTagsDTO;
use ArtisanPackUI\SEO\DTOs\OpenGraphDTO;
use ArtisanPackUI\SEO\DTOs\TwitterCardDTO;
use ArtisanPackUI\SEO\Http\Resources\SeoPreviewResource;
use Illuminate\Http\Request;

describe( 'SeoPreviewResource', function (): void {

	it( 'includes search and social preview sections', function (): void {
		$meta    = new MetaTagsDTO( 'Page Title', 'Page description.', 'https://example.com/page', 'index, follow' );
		$og      = new OpenGraphDTO( 'OG Title', 'OG desc', 'https://example.com/og.jpg', 'https://example.com/page' );
		$twitter = new TwitterCardDTO( 'summary_large_image', 'Twitter Title', 'Twitter desc' );

		$resource = new SeoPreviewResource( $meta, $og, $twitter );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result )->toHaveKeys( [ 'search', 'social', 'meta', 'hreflang' ] )
			->and( $result['social'] )->toHaveKeys( [ 'open_graph', 'twitter_card' ] );
	} );

	it( 'builds search preview with truncation info', function (): void {
		$longTitle = str_repeat( 'a', 70 );
		$meta      = new MetaTagsDTO( $longTitle, 'Description.', 'https://example.com', 'index, follow' );
		$og        = new OpenGraphDTO( 'OG', null, null, 'https://example.com' );
		$twitter   = new TwitterCardDTO();

		$resource = new SeoPreviewResource( $meta, $og, $twitter );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['search']['title_is_truncated'] )->toBeTrue()
			->and( $result['search']['title_length'] )->toBe( 70 )
			->and( mb_strlen( $result['search']['title_truncated'] ) )->toBeLessThanOrEqual( 60 );
	} );

	it( 'does not truncate short title', function (): void {
		$meta    = new MetaTagsDTO( 'Short Title', 'Desc.', 'https://example.com', 'index, follow' );
		$og      = new OpenGraphDTO( 'OG', null, null, 'https://example.com' );
		$twitter = new TwitterCardDTO();

		$resource = new SeoPreviewResource( $meta, $og, $twitter );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['search']['title_is_truncated'] )->toBeFalse()
			->and( $result['search']['title_truncated'] )->toBe( 'Short Title' );
	} );

	it( 'includes hreflang data when provided', function (): void {
		$meta    = new MetaTagsDTO( 'Title', null, 'https://example.com', 'index, follow' );
		$og      = new OpenGraphDTO( 'OG', null, null, 'https://example.com' );
		$twitter = new TwitterCardDTO();

		$hreflang = [
			[ 'hreflang' => 'en', 'href' => 'https://example.com/en' ],
			[ 'hreflang' => 'fr', 'href' => 'https://example.com/fr' ],
		];

		$resource = new SeoPreviewResource( $meta, $og, $twitter, $hreflang );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['hreflang'] )->toHaveCount( 2 );
	} );

	it( 'includes OG and Twitter data in social preview', function (): void {
		$meta    = new MetaTagsDTO( 'Title', null, 'https://example.com', 'index, follow' );
		$og      = new OpenGraphDTO( 'OG Title', 'OG Desc', 'https://example.com/og.jpg', 'https://example.com', 'article', 'Site Name' );
		$twitter = new TwitterCardDTO( 'summary', 'TW Title', 'TW Desc', 'https://example.com/tw.jpg', '@site', '@author' );

		$resource = new SeoPreviewResource( $meta, $og, $twitter );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['social']['open_graph']['title'] )->toBe( 'OG Title' )
			->and( $result['social']['open_graph']['image'] )->toBe( 'https://example.com/og.jpg' )
			->and( $result['social']['twitter_card']['card'] )->toBe( 'summary' )
			->and( $result['social']['twitter_card']['site'] )->toBe( '@site' );
	} );
} );
