<?php
/**
 * HandleRedirects Middleware.
 *
 * Intercepts incoming requests and handles URL redirects.
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

namespace ArtisanPackUI\SEO\Http\Middleware;

use ArtisanPackUI\SEO\Models\Redirect;
use ArtisanPackUI\SEO\Services\RedirectService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HandleRedirects middleware class.
 *
 * Checks incoming requests against redirect rules and performs
 * the redirect if a match is found.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class HandleRedirects
{
	/**
	 * The redirect service instance.
	 *
	 * @since 1.0.0
	 *
	 * @var RedirectService
	 */
	protected RedirectService $redirectService;

	/**
	 * Track visited paths to prevent infinite redirect loops.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>
	 */
	protected array $visitedPaths = [];

	/**
	 * Create a new middleware instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  RedirectService  $redirectService  The redirect service.
	 */
	public function __construct( RedirectService $redirectService )
	{
		$this->redirectService = $redirectService;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @since 1.0.0
	 *
	 * @param  Request  $request  The incoming request.
	 * @param  Closure  $next     The next middleware.
	 *
	 * @return Response
	 */
	public function handle( Request $request, Closure $next ): Response
	{
		// Check if redirects are enabled
		if ( ! $this->isEnabled() ) {
			return $next( $request );
		}

		// Skip redirect checks for certain request types
		if ( $this->shouldSkip( $request ) ) {
			return $next( $request );
		}

		// Find a matching redirect
		try {
			$redirect = $this->redirectService->findMatch( $request->path() );
		} catch ( \Illuminate\Database\QueryException $e ) {
			// Table might not exist yet (migrations not run)
			// Gracefully continue without redirects
			return $next( $request );
		}

		if ( null !== $redirect ) {
			return $this->performRedirect( $redirect, $request, $next );
		}

		return $next( $request );
	}

	/**
	 * Check if redirect handling is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function isEnabled(): bool
	{
		return (bool) config( 'seo.redirects.enabled', true )
			&& (bool) config( 'seo.redirects.middleware_enabled', true );
	}

	/**
	 * Determine if the request should skip redirect checking.
	 *
	 * @since 1.0.0
	 *
	 * @param  Request  $request  The incoming request.
	 *
	 * @return bool
	 */
	protected function shouldSkip( Request $request ): bool
	{
		// Skip non-GET requests (redirects are typically for GET requests)
		if ( ! $request->isMethod( 'GET' ) && ! $request->isMethod( 'HEAD' ) ) {
			return true;
		}

		// Skip AJAX requests unless explicitly enabled
		if ( $request->ajax() && ! config( 'seo.redirects.handle_ajax', false ) ) {
			return true;
		}

		// Skip API routes unless explicitly enabled
		if ( $this->isApiRoute( $request ) && ! config( 'seo.redirects.handle_api', false ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the request is for an API route.
	 *
	 * @since 1.0.0
	 *
	 * @param  Request  $request  The incoming request.
	 *
	 * @return bool
	 */
	protected function isApiRoute( Request $request ): bool
	{
		$path = $request->path();

		return str_starts_with( $path, 'api/' ) || 'api' === $path;
	}

	/**
	 * Perform the redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  Redirect  $redirect  The redirect rule.
	 * @param  Request   $request   The incoming request.
	 * @param  Closure   $next      The next middleware.
	 *
	 * @return Response
	 */
	protected function performRedirect( Redirect $redirect, Request $request, Closure $next ): Response
	{
		// Get the resolved destination
		$destination = $redirect->getResolvedDestination( $request->path() );

		// Detect and prevent redirect loops
		if ( $this->wouldCreateLoop( $destination, $request ) ) {
			// Log the loop and let the request continue without redirecting
			$this->logRedirectLoop( $redirect, $destination );

			// Let the request continue normally to prevent the loop
			return $next( $request );
		}

		// Track this redirect for hit counting
		if ( $this->shouldTrackHits() ) {
			$redirect->recordHit();
		}

		// Preserve query string if configured
		$destination = $this->buildDestinationUrl( $destination, $request );

		return redirect( $destination, $redirect->status_code );
	}

	/**
	 * Check if the redirect would create an infinite loop.
	 *
	 * @since 1.0.0
	 *
	 * @param  string   $destination  The destination path.
	 * @param  Request  $request      The incoming request.
	 *
	 * @return bool
	 */
	protected function wouldCreateLoop( string $destination, Request $request ): bool
	{
		$normalizedDestination = $this->normalizePath( $destination );
		$normalizedCurrent     = $this->normalizePath( $request->path() );

		// Direct loop: redirecting to the same URL
		if ( $normalizedDestination === $normalizedCurrent ) {
			return true;
		}

		// Check max chain depth
		$maxChainDepth = (int) config( 'seo.redirects.max_chain_depth', 5 );
		$chainCount    = $this->countRedirectChain( $destination, $maxChainDepth );

		return $chainCount >= $maxChainDepth;
	}

	/**
	 * Count the length of a redirect chain starting from a destination.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path      The starting path.
	 * @param  int     $maxDepth  Maximum depth to check.
	 *
	 * @return int
	 */
	protected function countRedirectChain( string $path, int $maxDepth ): int
	{
		$visited = [];
		$current = $this->normalizePath( $path );
		$count   = 0;

		while ( $count < $maxDepth ) {
			if ( in_array( $current, $visited, true ) ) {
				// Found a loop
				return $maxDepth;
			}

			$visited[] = $current;

			$nextRedirect = $this->redirectService->findMatch( $current );
			if ( null === $nextRedirect ) {
				break;
			}

			$current = $this->normalizePath( $nextRedirect->getResolvedDestination( $current ) );
			$count++;
		}

		return $count;
	}

	/**
	 * Log a redirect loop for debugging.
	 *
	 * @since 1.0.0
	 *
	 * @param  Redirect  $redirect     The redirect that caused the loop.
	 * @param  string    $destination  The problematic destination.
	 *
	 * @return void
	 */
	protected function logRedirectLoop( Redirect $redirect, string $destination ): void
	{
		logger()->warning( __( 'Redirect loop detected' ), [
			'redirect_id' => $redirect->id,
			'from_path'   => $redirect->from_path,
			'to_path'     => $redirect->to_path,
			'destination' => $destination,
		] );
	}

	/**
	 * Check if hit tracking is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function shouldTrackHits(): bool
	{
		return (bool) config( 'seo.redirects.track_hits', true );
	}

	/**
	 * Build the full destination URL, preserving query string if configured.
	 *
	 * @since 1.0.0
	 *
	 * @param  string   $destination  The destination path.
	 * @param  Request  $request      The incoming request.
	 *
	 * @return string
	 */
	protected function buildDestinationUrl( string $destination, Request $request ): string
	{
		// Check if we should preserve the query string
		if ( ! config( 'seo.redirects.preserve_query_string', true ) ) {
			return $destination;
		}

		// If destination already has query string, don't modify
		if ( str_contains( $destination, '?' ) ) {
			return $destination;
		}

		// Append original query string if present
		$queryString = $request->getQueryString();
		if ( null !== $queryString && '' !== $queryString ) {
			return $destination . '?' . $queryString;
		}

		return $destination;
	}

	/**
	 * Normalize a path for comparison.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The path to normalize.
	 *
	 * @return string
	 */
	protected function normalizePath( string $path ): string
	{
		// Handle full URLs
		if ( str_starts_with( $path, 'http://' ) || str_starts_with( $path, 'https://' ) ) {
			$parsed = parse_url( $path );
			$path   = $parsed['path'] ?? '/';
		}

		$path = '/' . ltrim( $path, '/' );

		return rtrim( $path, '/' ) ?: '/';
	}
}
