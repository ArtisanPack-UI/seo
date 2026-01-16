<?php

/**
 * SitemapEntry Model Tests.
 *
 * Unit tests for the SitemapEntry Eloquent model.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SitemapEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	// Run the migrations
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

describe( 'SitemapEntry Model', function (): void {

	it( 'can create a sitemap entry record', function (): void {
		$entry = SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		expect( $entry )->toBeInstanceOf( SitemapEntry::class )
			->and( $entry->url )->toBe( 'https://example.com/page-1' )
			->and( $entry->type )->toBe( 'page' );
	} );

	it( 'has correct default values', function (): void {
		$entry = SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
		] );

		$entry->refresh();

		expect( $entry->type )->toBe( 'page' )
			->and( (string) $entry->priority )->toBe( '0.5' )
			->and( $entry->changefreq )->toBe( 'weekly' )
			->and( $entry->is_indexable )->toBeTrue();
	} );

	it( 'casts boolean fields correctly', function (): void {
		$entry = SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'is_indexable'     => false,
		] );

		expect( $entry->is_indexable )->toBeFalse();
	} );

	it( 'casts JSON fields correctly', function (): void {
		$images = [
			[ 'loc' => 'https://example.com/image1.jpg', 'title' => 'Image 1' ],
			[ 'loc' => 'https://example.com/image2.jpg', 'title' => 'Image 2' ],
		];

		$videos = [
			[
				'thumbnail_loc' => 'https://example.com/thumb.jpg',
				'title'         => 'Video 1',
				'description'   => 'A test video',
			],
		];

		$entry = SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'images'           => $images,
			'videos'           => $videos,
		] );

		$entry->refresh();

		expect( $entry->images )->toBe( $images )
			->and( $entry->videos )->toBe( $videos );
	} );

	it( 'casts datetime fields correctly', function (): void {
		$lastModified = now()->subDay();

		$entry = SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'last_modified'    => $lastModified,
		] );

		$entry->refresh();

		expect( $entry->last_modified )->toBeInstanceOf( Carbon\Carbon::class )
			->and( $entry->last_modified->toDateString() )->toBe( $lastModified->toDateString() );
	} );

	it( 'validates priority range', function (): void {
		expect( fn () => SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'priority'         => 1.5,
		] ) )->toThrow( InvalidArgumentException::class );
	} );

	it( 'validates changefreq values', function (): void {
		expect( fn () => SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'changefreq'       => 'invalid',
		] ) )->toThrow( InvalidArgumentException::class );
	} );

	it( 'accepts valid changefreq values', function (): void {
		foreach ( SitemapEntry::VALID_CHANGEFREQ as $index => $freq ) {
			$entry = SitemapEntry::create( [
				'sitemapable_type' => 'App\Models\Page',
				'sitemapable_id'   => $index + 100,
				'url'              => 'https://example.com/page-' . $freq,
				'changefreq'       => $freq,
			] );

			expect( $entry->changefreq )->toBe( $freq );
		}
	} );

} );

describe( 'SitemapEntry Scopes', function (): void {

	beforeEach( function (): void {
		// Create test records
		SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'is_indexable'     => true,
			'last_modified'    => now()->subDays( 3 ),
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Page',
			'sitemapable_id'   => 2,
			'url'              => 'https://example.com/page-2',
			'type'             => 'page',
			'is_indexable'     => false,
			'last_modified'    => now()->subDays( 10 ),
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Post',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/post-1',
			'type'             => 'post',
			'is_indexable'     => true,
			'priority'         => 0.8,
			'last_modified'    => now()->subDays( 1 ),
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Product',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/product-1',
			'type'             => 'product',
			'is_indexable'     => true,
			'images'           => [ [ 'loc' => 'https://example.com/image.jpg' ] ],
			'videos'           => [ [ 'title' => 'Test Video' ] ],
			'last_modified'    => now(),
		] );
	} );

	it( 'filters indexable entries', function (): void {
		$indexable = SitemapEntry::indexable()->get();

		expect( $indexable )->toHaveCount( 3 )
			->and( $indexable->pluck( 'is_indexable' )->unique()->toArray() )->toBe( [ true ] );
	} );

	it( 'filters by type', function (): void {
		$pages = SitemapEntry::byType( 'page' )->get();

		expect( $pages )->toHaveCount( 2 )
			->and( $pages->pluck( 'type' )->unique()->toArray() )->toBe( [ 'page' ] );
	} );

	it( 'filters recently updated entries', function (): void {
		$recent = SitemapEntry::recentlyUpdated( 7 )->get();

		expect( $recent )->toHaveCount( 3 );
	} );

	it( 'filters by model type', function (): void {
		$pages = SitemapEntry::forModel( 'App\Models\Page' )->get();

		expect( $pages )->toHaveCount( 2 )
			->and( $pages->pluck( 'sitemapable_type' )->unique()->toArray() )->toBe( [ 'App\Models\Page' ] );
	} );

	it( 'orders by priority', function (): void {
		$ordered = SitemapEntry::orderByPriority()->get();

		expect( (float) $ordered->first()->priority )->toBe( 0.8 );
	} );

	it( 'orders by last modified', function (): void {
		$ordered = SitemapEntry::orderByLastModified()->get();

		expect( $ordered->first()->type )->toBe( 'product' );
	} );

	it( 'filters entries with images', function (): void {
		$withImages = SitemapEntry::withImages()->get();

		expect( $withImages )->toHaveCount( 1 )
			->and( $withImages->first()->type )->toBe( 'product' );
	} );

	it( 'filters entries with videos', function (): void {
		$withVideos = SitemapEntry::withVideos()->get();

		expect( $withVideos )->toHaveCount( 1 )
			->and( $withVideos->first()->type )->toBe( 'product' );
	} );

} );

describe( 'SitemapEntry Helper Methods', function (): void {

	it( 'checks if entry is indexable', function (): void {
		$indexable    = new SitemapEntry( [ 'is_indexable' => true ] );
		$notIndexable = new SitemapEntry( [ 'is_indexable' => false ] );

		expect( $indexable->isIndexable() )->toBeTrue()
			->and( $notIndexable->isIndexable() )->toBeFalse();
	} );

	it( 'checks if entry has images', function (): void {
		$withImages    = new SitemapEntry( [ 'images' => [ [ 'loc' => 'test.jpg' ] ] ] );
		$withoutImages = new SitemapEntry( [ 'images' => null ] );
		$emptyImages   = new SitemapEntry( [ 'images' => [] ] );

		expect( $withImages->hasImages() )->toBeTrue()
			->and( $withoutImages->hasImages() )->toBeFalse()
			->and( $emptyImages->hasImages() )->toBeFalse();
	} );

	it( 'checks if entry has videos', function (): void {
		$withVideos    = new SitemapEntry( [ 'videos' => [ [ 'title' => 'Test' ] ] ] );
		$withoutVideos = new SitemapEntry( [ 'videos' => null ] );
		$emptyVideos   = new SitemapEntry( [ 'videos' => [] ] );

		expect( $withVideos->hasVideos() )->toBeTrue()
			->and( $withoutVideos->hasVideos() )->toBeFalse()
			->and( $emptyVideos->hasVideos() )->toBeFalse();
	} );

	it( 'formats last modified for sitemap', function (): void {
		$date  = now();
		$entry = new SitemapEntry( [ 'last_modified' => $date ] );

		expect( $entry->getLastModifiedForSitemap() )->toBe( $date->toW3cString() );
	} );

	it( 'returns null for sitemap date when not set', function (): void {
		$entry = new SitemapEntry( [ 'last_modified' => null ] );

		expect( $entry->getLastModifiedForSitemap() )->toBeNull();
	} );

	it( 'can add images fluently', function (): void {
		$entry = new SitemapEntry();

		$entry->addImage( 'https://example.com/image1.jpg', 'Title 1', 'Caption 1' )
			->addImage( 'https://example.com/image2.jpg', 'Title 2' );

		expect( $entry->images )->toHaveCount( 2 )
			->and( $entry->images[0]['loc'] )->toBe( 'https://example.com/image1.jpg' )
			->and( $entry->images[0]['title'] )->toBe( 'Title 1' )
			->and( $entry->images[0]['caption'] )->toBe( 'Caption 1' )
			->and( $entry->images[1]['loc'] )->toBe( 'https://example.com/image2.jpg' );
	} );

	it( 'can add videos fluently', function (): void {
		$entry = new SitemapEntry();

		$entry->addVideo(
			'https://example.com/thumb.jpg',
			'Video Title',
			'Video Description',
			'https://example.com/video.mp4',
			null,
			120,
		);

		expect( $entry->videos )->toHaveCount( 1 )
			->and( $entry->videos[0]['thumbnail_loc'] )->toBe( 'https://example.com/thumb.jpg' )
			->and( $entry->videos[0]['title'] )->toBe( 'Video Title' )
			->and( $entry->videos[0]['description'] )->toBe( 'Video Description' )
			->and( $entry->videos[0]['content_loc'] )->toBe( 'https://example.com/video.mp4' )
			->and( $entry->videos[0]['duration'] )->toBe( 120 );
	} );

	it( 'can clear images', function (): void {
		$entry         = new SitemapEntry();
		$entry->images = [ [ 'loc' => 'test.jpg' ] ];

		$entry->clearImages();

		expect( $entry->images )->toBeNull();
	} );

	it( 'can clear videos', function (): void {
		$entry         = new SitemapEntry();
		$entry->videos = [ [ 'title' => 'test' ] ];

		$entry->clearVideos();

		expect( $entry->videos )->toBeNull();
	} );

} );

describe( 'SitemapEntry Static Methods', function (): void {

	it( 'gets available types from database', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Post',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/post-1',
			'type'             => 'post',
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\Models\Product',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/product-1',
			'type'             => 'product',
		] );

		$types = SitemapEntry::getAvailableTypes();

		expect( $types )->toContain( 'page' )
			->and( $types )->toContain( 'post' )
			->and( $types )->toContain( 'product' );
	} );

} );
