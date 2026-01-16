<?php

/**
 * SitemapController.
 *
 * Handles serving XML sitemaps.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Http\Controllers;

use ArtisanPackUI\SEO\Services\SitemapService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * SitemapController class.
 *
 * Serves sitemap XML files with proper headers and caching.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SitemapController extends Controller
{
	/**
	 * The sitemap service instance.
	 *
	 * @since 1.0.0
	 *
	 * @var SitemapService
	 */
	protected SitemapService $sitemapService;

	/**
	 * Create a new SitemapController instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  SitemapService  $sitemapService  The sitemap service.
	 */
	public function __construct( SitemapService $sitemapService )
	{
		$this->sitemapService = $sitemapService;
	}

	/**
	 * Serve the main sitemap or sitemap index.
	 *
	 * If the site has multiple sitemap types or pages, serves the sitemap index.
	 * Otherwise, serves the main sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @return Response
	 */
	public function index(): Response
	{
		if ( $this->sitemapService->needsIndex() ) {
			$content = $this->sitemapService->generateIndex();
		} else {
			$content = $this->sitemapService->generate();
		}

		return $this->xmlResponse( $content );
	}

	/**
	 * Serve a paginated sitemap for a specific type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $type  The sitemap type.
	 * @param  int     $page  The page number.
	 *
	 * @return Response
	 */
	public function show( string $type, int $page = 1 ): Response
	{
		$content = $this->sitemapService->generate( $type, $page );

		return $this->xmlResponse( $content );
	}

	/**
	 * Serve a paginated main sitemap (when no types).
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $page  The page number.
	 *
	 * @return Response
	 */
	public function page( int $page ): Response
	{
		$content = $this->sitemapService->generate( null, $page );

		return $this->xmlResponse( $content );
	}

	/**
	 * Serve the image sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $page  The page number.
	 *
	 * @return Response
	 */
	public function images( int $page = 1 ): Response
	{
		if ( ! $this->sitemapService->isImageSitemapEnabled() ) {
			abort( 404 );
		}

		$content = $this->sitemapService->generateImages( $page );

		return $this->xmlResponse( $content );
	}

	/**
	 * Serve the video sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $page  The page number.
	 *
	 * @return Response
	 */
	public function videos( int $page = 1 ): Response
	{
		if ( ! $this->sitemapService->isVideoSitemapEnabled() ) {
			abort( 404 );
		}

		$content = $this->sitemapService->generateVideos( $page );

		return $this->xmlResponse( $content );
	}

	/**
	 * Serve the news sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $page  The page number.
	 *
	 * @return Response
	 */
	public function news( int $page = 1 ): Response
	{
		if ( ! $this->sitemapService->isNewsSitemapEnabled() ) {
			abort( 404 );
		}

		$content = $this->sitemapService->generateNews( $page );

		return $this->xmlResponse( $content );
	}

	/**
	 * Create an XML response with proper headers.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $content  The XML content.
	 *
	 * @return Response
	 */
	protected function xmlResponse( string $content ): Response
	{
		$cacheTtl = (int) config( 'seo.sitemap.cache_ttl', 3600 );

		return response( $content, 200 )
			->header( 'Content-Type', 'application/xml; charset=UTF-8' )
			->header( 'Cache-Control', "public, max-age={$cacheTtl}" )
			->header( 'X-Robots-Tag', 'noindex' );
	}
}
