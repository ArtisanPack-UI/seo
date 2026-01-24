<?php

/**
 * VideoSitemapGenerator Tests.
 *
 * Unit tests for the VideoSitemapGenerator class.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Sitemap\Generators\VideoSitemapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

describe( 'VideoSitemapGenerator', function (): void {

	it( 'generates empty sitemap when no entries with videos exist', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$generator = new VideoSitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<?xml version="1.0" encoding="UTF-8"?>' )
			->and( $xml )->toContain( 'xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"' )
			->and( $xml )->not->toContain( '<url>' );
	} );

	it( 'generates sitemap with video entries', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'videos'           => [
				[
					'thumbnail_loc' => 'https://example.com/thumb.jpg',
					'title'         => 'Test Video',
					'description'   => 'Test video description',
					'content_loc'   => 'https://example.com/video.mp4',
				],
			],
		] );

		$generator = new VideoSitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<url>' )
			->and( $xml )->toContain( '<loc>https://example.com/page-1</loc>' )
			->and( $xml )->toContain( '<video:video>' )
			->and( $xml )->toContain( '<video:thumbnail_loc>https://example.com/thumb.jpg</video:thumbnail_loc>' )
			->and( $xml )->toContain( '<video:title>Test Video</video:title>' )
			->and( $xml )->toContain( '<video:description>Test video description</video:description>' )
			->and( $xml )->toContain( '<video:content_loc>https://example.com/video.mp4</video:content_loc>' );
	} );

	it( 'includes all optional video attributes', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'videos'           => [
				[
					'thumbnail_loc'   => 'https://example.com/thumb.jpg',
					'title'           => 'Test Video',
					'description'     => 'Test description',
					'content_loc'     => 'https://example.com/video.mp4',
					'player_loc'      => 'https://example.com/player',
					'duration'        => 120,
					'rating'          => 4.5,
					'view_count'      => 1000,
					'family_friendly' => true,
					'tags'            => [ 'tech', 'tutorial' ],
					'category'        => 'Technology',
				],
			],
		] );

		$generator = new VideoSitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<video:player_loc>https://example.com/player</video:player_loc>' )
			->and( $xml )->toContain( '<video:duration>120</video:duration>' )
			->and( $xml )->toContain( '<video:rating>4.5</video:rating>' )
			->and( $xml )->toContain( '<video:view_count>1000</video:view_count>' )
			->and( $xml )->toContain( '<video:family_friendly>yes</video:family_friendly>' )
			->and( $xml )->toContain( '<video:tag>tech</video:tag>' )
			->and( $xml )->toContain( '<video:tag>tutorial</video:tag>' )
			->and( $xml )->toContain( '<video:category>Technology</video:category>' );
	} );

	it( 'handles family_friendly as no', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'videos'           => [
				[
					'thumbnail_loc'   => 'https://example.com/thumb.jpg',
					'title'           => 'Test Video',
					'description'     => 'Test description',
					'family_friendly' => false,
				],
			],
		] );

		$generator = new VideoSitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<video:family_friendly>no</video:family_friendly>' );
	} );

	it( 'supports multiple videos per entry', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'videos'           => [
				[
					'thumbnail_loc' => 'https://example.com/thumb1.jpg',
					'title'         => 'Video 1',
					'description'   => 'First video',
				],
				[
					'thumbnail_loc' => 'https://example.com/thumb2.jpg',
					'title'         => 'Video 2',
					'description'   => 'Second video',
				],
			],
		] );

		$generator = new VideoSitemapGenerator();
		$xml       = $generator->generate();

		expect( substr_count( $xml, '<video:video>' ) )->toBe( 2 )
			->and( $xml )->toContain( 'Video 1' )
			->and( $xml )->toContain( 'Video 2' );
	} );

	it( 'skips videos with missing required fields', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'videos'           => [
				[
					'thumbnail_loc' => 'https://example.com/thumb.jpg',
					'title'         => 'Valid Video',
					'description'   => 'Valid description',
				],
				[
					'thumbnail_loc' => 'https://example.com/thumb2.jpg',
					'title'         => 'Missing Description',
					// Missing description
				],
				[
					'title'       => 'Missing Thumbnail',
					'description' => 'Has description',
					// Missing thumbnail_loc
				],
			],
		] );

		$generator = new VideoSitemapGenerator();
		$xml       = $generator->generate();

		expect( substr_count( $xml, '<video:video>' ) )->toBe( 1 )
			->and( $xml )->toContain( 'Valid Video' )
			->and( $xml )->not->toContain( 'Missing Description' )
			->and( $xml )->not->toContain( 'Missing Thumbnail' );
	} );

	it( 'excludes non-indexable entries', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'is_indexable'     => true,
			'videos'           => [ [
				'thumbnail_loc' => 'https://example.com/thumb1.jpg',
				'title'         => 'Visible Video',
				'description'   => 'Description',
			] ],
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 2,
			'url'              => 'https://example.com/page-2',
			'type'             => 'page',
			'is_indexable'     => false,
			'videos'           => [ [
				'thumbnail_loc' => 'https://example.com/thumb2.jpg',
				'title'         => 'Hidden Video',
				'description'   => 'Description',
			] ],
		] );

		$generator = new VideoSitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( 'Visible Video' )
			->and( $xml )->not->toContain( 'Hidden Video' );
	} );

	it( 'calculates total pages correctly', function (): void {
		for ( $i = 1; $i <= 5; $i++ ) {
			SitemapEntry::create( [
				'sitemapable_type' => 'App\\Models\\Page',
				'sitemapable_id'   => $i,
				'url'              => "https://example.com/page-{$i}",
				'type'             => 'page',
				'videos'           => [ [
					'thumbnail_loc' => "https://example.com/thumb{$i}.jpg",
					'title'         => "Video {$i}",
					'description'   => "Description {$i}",
				] ],
			] );
		}

		$generator = new VideoSitemapGenerator( 2 );

		expect( $generator->getTotalPages() )->toBe( 3 );
	} );

	it( 'checks if entries have videos', function (): void {
		expect( ( new VideoSitemapGenerator() )->hasVideos() )->toBeFalse();

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'videos'           => [ [
				'thumbnail_loc' => 'https://example.com/thumb.jpg',
				'title'         => 'Test',
				'description'   => 'Test',
			] ],
		] );

		expect( ( new VideoSitemapGenerator() )->hasVideos() )->toBeTrue();
	} );

	it( 'handles uploader as string', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'videos'           => [ [
				'thumbnail_loc' => 'https://example.com/thumb.jpg',
				'title'         => 'Test',
				'description'   => 'Test',
				'uploader'      => 'John Doe',
			] ],
		] );

		$generator = new VideoSitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<video:uploader>John Doe</video:uploader>' );
	} );

	it( 'handles uploader as array with info', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'videos'           => [ [
				'thumbnail_loc' => 'https://example.com/thumb.jpg',
				'title'         => 'Test',
				'description'   => 'Test',
				'uploader'      => [
					'name' => 'John Doe',
					'info' => 'https://example.com/users/john',
				],
			] ],
		] );

		$generator = new VideoSitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<video:uploader info="https://example.com/users/john">John Doe</video:uploader>' );
	} );

} );
