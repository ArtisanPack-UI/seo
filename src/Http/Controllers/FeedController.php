<?php

/**
 * FeedController.
 *
 * Serves RSS 2.0 and Atom 1.0 feeds when a FeedProviderContract binding
 * is registered in the container.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Http\Controllers;

use ArtisanPackUI\SEO\Contracts\FeedProviderContract;
use ArtisanPackUI\SEO\Feed\Generators\FeedGenerator;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Throwable;

/**
 * FeedController class.
 *
 * Delegates to a bound FeedProviderContract implementation. When no
 * binding exists a 404 is returned; consumers must register a provider
 * (via `$this->app->bind( FeedProviderContract::class, ... )` from a
 * service provider) before enabling `seo.feeds.route_enabled`.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
class FeedController extends Controller
{
	/**
	 * Create a new FeedController instance.
	 *
	 * @since 1.4.0
	 *
	 * @param  FeedGenerator  $generator  The feed generator.
	 */
	public function __construct( protected FeedGenerator $generator )
	{
	}

	/**
	 * Serve the RSS 2.0 feed.
	 *
	 * @since 1.4.0
	 *
	 * @return Response
	 */
	public function rss(): Response
	{
		$provider = $this->resolveProvider();
		if ( null === $provider ) {
			return response( 'No feed provider registered.', 404 );
		}

		return $this->xmlResponse(
			$this->generator->generateRssFromProvider( $provider ),
			'application/rss+xml',
		);
	}

	/**
	 * Serve the Atom 1.0 feed.
	 *
	 * @since 1.4.0
	 *
	 * @return Response
	 */
	public function atom(): Response
	{
		$provider = $this->resolveProvider();
		if ( null === $provider ) {
			return response( 'No feed provider registered.', 404 );
		}

		return $this->xmlResponse(
			$this->generator->generateAtomFromProvider( $provider ),
			'application/atom+xml',
		);
	}

	/**
	 * Resolve the bound FeedProviderContract implementation, if any.
	 *
	 * @since 1.4.0
	 *
	 * @return FeedProviderContract|null
	 */
	protected function resolveProvider(): ?FeedProviderContract
	{
		try {
			$provider = app( FeedProviderContract::class );
		} catch ( Throwable $e ) {
			return null;
		}

		return $provider instanceof FeedProviderContract ? $provider : null;
	}

	/**
	 * Build a text/xml response with cache headers.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $body  Feed body.
	 * @param  string  $type  Content type.
	 *
	 * @return Response
	 */
	protected function xmlResponse( string $body, string $type ): Response
	{
		$ttl = (int) config( 'seo.feeds.cache_ttl', 300 );

		return response( $body, 200 )
			->header( 'Content-Type', $type . '; charset=UTF-8' )
			->header( 'Cache-Control', "public, max-age={$ttl}" );
	}
}
