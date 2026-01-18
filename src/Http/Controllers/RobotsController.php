<?php
/**
 * RobotsController.
 *
 * Handles serving robots.txt files.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 * @copyright  2026 Jacob Martella
 * @license    MIT
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Http\Controllers;

use ArtisanPackUI\SEO\Services\RobotsService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * RobotsController class.
 *
 * Serves robots.txt files with proper headers and caching.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class RobotsController extends Controller
{
	/**
	 * The robots service instance.
	 *
	 * @since 1.0.0
	 *
	 * @var RobotsService
	 */
	protected RobotsService $robotsService;

	/**
	 * Create a new RobotsController instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  RobotsService  $robotsService  The robots service.
	 */
	public function __construct( RobotsService $robotsService )
	{
		$this->robotsService = $robotsService;
	}

	/**
	 * Serve the robots.txt file.
	 *
	 * @since 1.0.0
	 *
	 * @return Response
	 */
	public function index(): Response
	{
		$content = $this->robotsService->generate();

		return $this->textResponse( $content );
	}

	/**
	 * Create a text response with proper headers.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $content  The text content.
	 *
	 * @return Response
	 */
	protected function textResponse( string $content ): Response
	{
		$cacheTtl = (int) config( 'seo.robots.cache_ttl', 3600 );

		return response( $content, 200 )
			->header( 'Content-Type', 'text/plain; charset=UTF-8' )
			->header( 'Cache-Control', "public, max-age={$cacheTtl}" );
	}
}
