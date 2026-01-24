<?php

/**
 * ImageSitemapGenerator Tests.
 *
 * Unit tests for the ImageSitemapGenerator class.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Sitemap\Generators\ImageSitemapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

describe( 'ImageSitemapGenerator', function (): void {

	it( 'generates empty sitemap when no entries with images exist', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$generator = new ImageSitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<?xml version="1.0" encoding="UTF-8"?>' )
			->and( $xml )->toContain( 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' )
			->and( $xml )->not->toContain( '<url>' );
	} );

	it( 'generates sitemap with image entries', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'images'           => [
				[
					'loc'     => 'https://example.com/image1.jpg',
					'caption' => 'Image caption',
					'title'   => 'Image title',
				],
			],
		] );

		$generator = new ImageSitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<url>' )
			->and( $xml )->toContain( '<loc>https://example.com/page-1</loc>' )
			->and( $xml )->toContain( '<image:image>' )
			->and( $xml )->toContain( '<image:loc>https://example.com/image1.jpg</image:loc>' )
			->and( $xml )->toContain( '<image:caption>Image caption</image:caption>' )
			->and( $xml )->toContain( '<image:title>Image title</image:title>' );
	} );

	it( 'supports multiple images per entry', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'images'           => [
				[ 'loc' => 'https://example.com/image1.jpg' ],
				[ 'loc' => 'https://example.com/image2.jpg' ],
				[ 'loc' => 'https://example.com/image3.jpg' ],
			],
		] );

		$generator = new ImageSitemapGenerator();
		$xml       = $generator->generate();

		expect( substr_count( $xml, '<image:image>' ) )->toBe( 3 )
			->and( $xml )->toContain( 'image1.jpg' )
			->and( $xml )->toContain( 'image2.jpg' )
			->and( $xml )->toContain( 'image3.jpg' );
	} );

	it( 'includes optional image attributes', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'images'           => [
				[
					'loc'          => 'https://example.com/image.jpg',
					'caption'      => 'Test caption',
					'geo_location' => 'New York, NY',
					'title'        => 'Test title',
					'license'      => 'https://example.com/license',
				],
			],
		] );

		$generator = new ImageSitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<image:caption>Test caption</image:caption>' )
			->and( $xml )->toContain( '<image:geo_location>New York, NY</image:geo_location>' )
			->and( $xml )->toContain( '<image:title>Test title</image:title>' )
			->and( $xml )->toContain( '<image:license>https://example.com/license</image:license>' );
	} );

	it( 'excludes non-indexable entries', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'is_indexable'     => true,
			'images'           => [ [ 'loc' => 'https://example.com/image1.jpg' ] ],
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 2,
			'url'              => 'https://example.com/page-2',
			'type'             => 'page',
			'is_indexable'     => false,
			'images'           => [ [ 'loc' => 'https://example.com/image2.jpg' ] ],
		] );

		$generator = new ImageSitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( 'image1.jpg' )
			->and( $xml )->not->toContain( 'image2.jpg' );
	} );

	it( 'calculates total pages correctly', function (): void {
		for ( $i = 1; $i <= 5; $i++ ) {
			SitemapEntry::create( [
				'sitemapable_type' => 'App\\Models\\Page',
				'sitemapable_id'   => $i,
				'url'              => "https://example.com/page-{$i}",
				'type'             => 'page',
				'images'           => [ [ 'loc' => "https://example.com/image{$i}.jpg" ] ],
			] );
		}

		$generator = new ImageSitemapGenerator( 2 );

		expect( $generator->getTotalPages() )->toBe( 3 );
	} );

	it( 'checks if entries have images', function (): void {
		expect( ( new ImageSitemapGenerator() )->hasImages() )->toBeFalse();

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'images'           => [ [ 'loc' => 'https://example.com/image.jpg' ] ],
		] );

		expect( ( new ImageSitemapGenerator() )->hasImages() )->toBeTrue();
	} );

	it( 'skips images with missing loc', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'images'           => [
				[ 'loc' => 'https://example.com/valid.jpg' ],
				[ 'title' => 'No loc provided' ],
				[ 'loc'   => '' ],
			],
		] );

		$generator = new ImageSitemapGenerator();
		$xml       = $generator->generate();

		expect( substr_count( $xml, '<image:image>' ) )->toBe( 1 )
			->and( $xml )->toContain( 'valid.jpg' );
	} );

} );
