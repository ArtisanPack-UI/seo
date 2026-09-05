<?php

/**
 * Feed Routes Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\FeedProviderContract;
use ArtisanPackUI\SEO\Http\Controllers\FeedController;
use Illuminate\Support\Facades\Route;

function registerFeedRoutes(): void
{
	Route::get(
		trim( (string) config( 'seo.feeds.rss_path', 'feed.xml' ), '/' ),
		[ FeedController::class, 'rss' ],
	);
	Route::get(
		trim( (string) config( 'seo.feeds.atom_path', 'feed.atom' ), '/' ),
		[ FeedController::class, 'atom' ],
	);
}

function bindStubFeedProvider(): void
{
	app()->bind( FeedProviderContract::class, function () {
		return new class() implements FeedProviderContract {
			public function getTitle(): string
			{
				return 'Stub Feed';
			}

			public function getLink(): string
			{
				return 'https://example.com';
			}

			public function getDescription(): string
			{
				return 'A stub';
			}

			public function getFeedUrl(): string
			{
				return 'https://example.com/feed.xml';
			}

			public function getEntries(): Illuminate\Support\Collection
			{
				return collect();
			}
		};
	} );
}

describe( 'feed routes', function (): void {

	it( 'serves RSS 2.0 when a provider is bound', function (): void {
		registerFeedRoutes();
		bindStubFeedProvider();

		$response = $this->get( '/feed.xml' );

		$response->assertStatus( 200 )
			->assertHeader( 'Content-Type', 'application/rss+xml; charset=UTF-8' );
	} );

	it( 'serves Atom 1.0 when a provider is bound', function (): void {
		registerFeedRoutes();
		bindStubFeedProvider();

		$response = $this->get( '/feed.atom' );

		$response->assertStatus( 200 )
			->assertHeader( 'Content-Type', 'application/atom+xml; charset=UTF-8' );
	} );

	it( 'returns 404 when no provider is bound', function (): void {
		registerFeedRoutes();

		$response = $this->get( '/feed.xml' );

		$response->assertStatus( 404 );
	} );

} );
