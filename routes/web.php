<?php

/**
 * SEO Package Web Routes.
 *
 * Routes for sitemap.xml and robots.txt.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Http\Controllers\RobotsController;
use ArtisanPackUI\SEO\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sitemap Routes
|--------------------------------------------------------------------------
|
| These routes handle sitemap.xml generation and serving.
|
*/

if ( true === config( 'seo.sitemap.route_enabled', true ) ) {
	$sitemapPath = config( 'seo.sitemap.route_path', 'sitemap.xml' );

	// Main sitemap route
	Route::get( $sitemapPath, [ SitemapController::class, 'index' ] )
		->name( 'seo.sitemap' );

	// Paginated main sitemap (when not using types)
	Route::get( 'sitemap-{page}.xml', [ SitemapController::class, 'page' ] )
		->where( 'page', '[1-9][0-9]*' )
		->name( 'seo.sitemap.page' );

	// Type-specific sitemaps with pagination
	Route::get( 'sitemap-{type}-{page}.xml', [ SitemapController::class, 'show' ] )
		->where( 'type', '[a-z0-9\-]+' )
		->where( 'page', '[1-9][0-9]*' )
		->name( 'seo.sitemap.type' );

	// Image sitemap routes
	if ( true === config( 'seo.sitemap.types.image', false ) ) {
		Route::get( 'sitemap-images.xml', [ SitemapController::class, 'images' ] )
			->name( 'seo.sitemap.images' );

		Route::get( 'sitemap-images-{page}.xml', [ SitemapController::class, 'images' ] )
			->where( 'page', '[1-9][0-9]*' )
			->name( 'seo.sitemap.images.page' );
	}

	// Video sitemap routes
	if ( true === config( 'seo.sitemap.types.video', false ) ) {
		Route::get( 'sitemap-videos.xml', [ SitemapController::class, 'videos' ] )
			->name( 'seo.sitemap.videos' );

		Route::get( 'sitemap-videos-{page}.xml', [ SitemapController::class, 'videos' ] )
			->where( 'page', '[1-9][0-9]*' )
			->name( 'seo.sitemap.videos.page' );
	}

	// News sitemap routes
	if ( true === config( 'seo.sitemap.types.news', false ) ) {
		Route::get( 'sitemap-news.xml', [ SitemapController::class, 'news' ] )
			->name( 'seo.sitemap.news' );

		Route::get( 'sitemap-news-{page}.xml', [ SitemapController::class, 'news' ] )
			->where( 'page', '[1-9][0-9]*' )
			->name( 'seo.sitemap.news.page' );
	}
}

/*
|--------------------------------------------------------------------------
| Robots.txt Route
|--------------------------------------------------------------------------
|
| This route handles dynamic robots.txt generation.
|
*/

if ( true === config( 'seo.robots.route_enabled', true ) ) {
	$robotsPath = config( 'seo.robots.route_path', 'robots.txt' );

	Route::get( $robotsPath, [ RobotsController::class, 'index' ] )
		->name( 'seo.robots' );
}
