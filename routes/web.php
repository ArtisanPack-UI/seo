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

	Route::get( $sitemapPath, function () {
		// Placeholder - will be replaced with SitemapController in sitemap task
		return response( '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>' )
			->header( 'Content-Type', 'application/xml' );
	} )->name( 'seo.sitemap' );

	// Sitemap index route (for large sites with multiple sitemaps)
	Route::get( 'sitemap-index.xml', function () {
		// Placeholder - will be replaced with SitemapController in sitemap task
		return response( '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>' )
			->header( 'Content-Type', 'application/xml' );
	} )->name( 'seo.sitemap.index' );
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

	Route::get( $robotsPath, function () {
		$disallow = config( 'seo.robots.disallow', [] );
		$allow    = config( 'seo.robots.allow', [] );

		$content = "User-agent: *\n";

		foreach ( $disallow as $path ) {
			$content .= "Disallow: {$path}\n";
		}

		foreach ( $allow as $path ) {
			$content .= "Allow: {$path}\n";
		}

		// Add sitemap URL
		$sitemapUrl = config( 'seo.robots.sitemap_url' );
		if ( null === $sitemapUrl && true === config( 'seo.sitemap.route_enabled', true ) ) {
			$sitemapUrl = url( config( 'seo.sitemap.route_path', 'sitemap.xml' ) );
		}

		if ( null !== $sitemapUrl ) {
			$content .= "\nSitemap: {$sitemapUrl}\n";
		}

		return response( $content )
			->header( 'Content-Type', 'text/plain' );
	} )->name( 'seo.robots' );
}
