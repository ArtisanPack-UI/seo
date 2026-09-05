<?php

/**
 * IndexNowKeyController.
 *
 * Serves the IndexNow key-verification text file at `/{key}.txt`.
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

use ArtisanPackUI\SEO\Contracts\IndexNowKeyProviderContract;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Throwable;

/**
 * IndexNowKeyController class.
 *
 * Returns the current IndexNow key as `text/plain` when the requested
 * filename matches. Delegates key resolution to a bound
 * `IndexNowKeyProviderContract`.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
class IndexNowKeyController extends Controller
{
	/**
	 * Serve the IndexNow key file.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $key  The key filename (from the URL, minus `.txt`).
	 *
	 * @return Response
	 */
	public function show( string $key ): Response
	{
		try {
			$provider = app( IndexNowKeyProviderContract::class );
		} catch ( Throwable $e ) {
			return response( 'Not Found', 404 );
		}

		try {
			$actual = $provider->getKey();
		} catch ( Throwable $e ) {
			return response( 'Not Found', 404 );
		}

		if ( '' === $actual || $key !== $actual ) {
			return response( 'Not Found', 404 );
		}

		return response( $actual, 200 )
			->header( 'Content-Type', 'text/plain; charset=UTF-8' )
			->header( 'Cache-Control', 'public, max-age=86400' );
	}
}
