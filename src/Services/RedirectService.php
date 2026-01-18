<?php
/**
 * RedirectService.
 *
 * Service for managing URL redirects.
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

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\Models\Redirect;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Throwable;

/**
 * RedirectService class.
 *
 * Provides CRUD operations and URL matching functionality for redirects.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class RedirectService
{
	/**
	 * Cache key prefix for redirect lookups.
	 *
	 * @var string
	 */
	protected const CACHE_PREFIX = 'seo:redirects:';

	/**
	 * Cache key for tracking cached match paths.
	 *
	 * @var string
	 */
	protected const CACHE_PATHS_KEY = 'seo:redirects:cached_paths';

	/**
	 * Find a matching redirect for the given path.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The URL path to find a match for.
	 *
	 * @return Redirect|null
	 */
	public function findMatch( string $path ): ?Redirect
	{
		$path = $this->normalizePath( $path );

		if ( $this->isCacheEnabled() ) {
			$cacheKey = $this->getMatchCacheKey( $path );
			$cached   = Cache::get( $cacheKey );

			if ( null !== $cached ) {
				if ( false === $cached ) {
					return null;
				}

				return Redirect::find( $cached );
			}
		}

		// First, try exact matches (most efficient)
		$redirect = $this->findExactMatch( $path );

		// Then try wildcard matches
		if ( null === $redirect ) {
			$redirect = $this->findWildcardMatch( $path );
		}

		// Finally, try regex matches (least efficient)
		if ( null === $redirect ) {
			$redirect = $this->findRegexMatch( $path );
		}

		// Cache the result
		if ( $this->isCacheEnabled() ) {
			$cacheKey = $this->getMatchCacheKey( $path );
			$ttl      = config( 'seo.redirects.cache_ttl', 86400 );

			Cache::put( $cacheKey, $redirect?->id ?? false, $ttl );

			// Track the cached path for later invalidation
			$this->trackCachedPath( $cacheKey );
		}

		return $redirect;
	}

	/**
	 * Create a new redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>  $data  The redirect data.
	 *
	 * @throws InvalidArgumentException If validation fails.
	 *
	 * @return Redirect
	 */
	public function create( array $data ): Redirect
	{
		$this->validateData( $data );

		// Check for chain loop
		if ( $this->wouldCreateChain( $data['from_path'], $data['to_path'] ) ) {
			throw new InvalidArgumentException(
				__( 'This redirect would create a redirect chain or loop.' ),
			);
		}

		$redirect = Redirect::create( $data );

		$this->clearCache();

		return $redirect;
	}

	/**
	 * Update an existing redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  Redirect              $redirect  The redirect to update.
	 * @param  array<string, mixed>  $data      The data to update.
	 *
	 * @throws InvalidArgumentException If validation fails.
	 *
	 * @return Redirect
	 */
	public function update( Redirect $redirect, array $data ): Redirect
	{
		$this->validateData( $data, $redirect );

		// Check for chain loop if paths are being changed
		$fromPath = $data['from_path'] ?? $redirect->from_path;
		$toPath   = $data['to_path'] ?? $redirect->to_path;

		if ( $this->wouldCreateChain( $fromPath, $toPath, $redirect->id ) ) {
			throw new InvalidArgumentException(
				__( 'This redirect would create a redirect chain or loop.' ),
			);
		}

		$redirect->update( $data );

		$this->clearCache();

		return $redirect->fresh();
	}

	/**
	 * Delete a redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  Redirect  $redirect  The redirect to delete.
	 *
	 * @return void
	 */
	public function delete( Redirect $redirect ): void
	{
		$redirect->delete();

		$this->clearCache();
	}

	/**
	 * Check if a redirect is part of a chain.
	 *
	 * Returns true if the redirect's destination is the source of another redirect,
	 * meaning following this redirect would lead to another redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  Redirect  $redirect  The redirect to check.
	 *
	 * @return bool
	 */
	public function checkForChains( Redirect $redirect ): bool
	{
		$destination = $this->normalizePath( $redirect->to_path );

		// Check if there's any active redirect from this destination
		return Redirect::active()
			->where( 'id', '!=', $redirect->id )
			->where( 'from_path', $destination )
			->exists();
	}

	/**
	 * Get redirect statistics.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function getStatistics(): array
	{
		return [
			'total'          => Redirect::count(),
			'active'         => Redirect::active()->count(),
			'inactive'       => Redirect::inactive()->count(),
			'total_hits'     => Redirect::sum( 'hits' ),
			'by_status_code' => [
				301 => Redirect::withStatusCode( 301 )->count(),
				302 => Redirect::withStatusCode( 302 )->count(),
				307 => Redirect::withStatusCode( 307 )->count(),
				308 => Redirect::withStatusCode( 308 )->count(),
			],
			'by_match_type' => [
				'exact'    => Redirect::exact()->count(),
				'regex'    => Redirect::regex()->count(),
				'wildcard' => Redirect::wildcard()->count(),
			],
			'most_used' => Redirect::active()
				->withHits()
				->mostHits()
				->take( 10 )
				->get( [ 'id', 'from_path', 'to_path', 'hits', 'last_hit_at' ] ),
			'never_used' => Redirect::active()
				->withoutHits()
				->count(),
		];
	}

	/**
	 * Get all active redirects.
	 *
	 * @since 1.0.0
	 *
	 * @return Collection<int, Redirect>
	 */
	public function getActiveRedirects(): Collection
	{
		return Redirect::active()->get();
	}

	/**
	 * Find redirects that point to the given path.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The destination path to search for.
	 *
	 * @return Collection<int, Redirect>
	 */
	public function findRedirectsTo( string $path ): Collection
	{
		$path = $this->normalizePath( $path );

		return Redirect::where( 'to_path', $path )->get();
	}

	/**
	 * Find redirects from the given path.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The source path to search for.
	 *
	 * @return Collection<int, Redirect>
	 */
	public function findRedirectsFrom( string $path ): Collection
	{
		$path = $this->normalizePath( $path );

		return Redirect::where( 'from_path', $path )->get();
	}

	/**
	 * Import redirects from an array.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<int, array<string, mixed>>  $redirects  Array of redirect data.
	 * @param  bool                              $skipErrors Whether to skip errors or throw.
	 *
	 * @return array{created: int, skipped: int, errors: array<int, string>}
	 */
	public function import( array $redirects, bool $skipErrors = true ): array
	{
		$result = [
			'created' => 0,
			'skipped' => 0,
			'errors'  => [],
		];

		foreach ( $redirects as $index => $data ) {
			try {
				$this->create( $data );
				$result['created']++;
			} catch ( Throwable $e ) {
				$result['skipped']++;
				$result['errors'][ $index ] = $e->getMessage();

				if ( ! $skipErrors ) {
					throw $e;
				}
			}
		}

		return $result;
	}

	/**
	 * Export all redirects.
	 *
	 * @since 1.0.0
	 *
	 * @param  bool  $activeOnly  Whether to export only active redirects.
	 *
	 * @return Collection<int, array<string, mixed>>
	 */
	public function export( bool $activeOnly = false ): Collection
	{
		$query = Redirect::query();

		if ( $activeOnly ) {
			$query->active();
		}

		return $query->get()->map( function ( Redirect $redirect ) {
			return [
				'from_path'   => $redirect->from_path,
				'to_path'     => $redirect->to_path,
				'status_code' => $redirect->status_code,
				'match_type'  => $redirect->match_type,
				'is_active'   => $redirect->is_active,
				'notes'       => $redirect->notes,
			];
		} );
	}

	/**
	 * Clear the redirect cache.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clearCache(): void
	{
		if ( $this->isCacheEnabled() ) {
			Cache::forget( self::CACHE_PREFIX . 'all' );

			// Clear all tracked match caches
			$cachedPaths = Cache::get( self::CACHE_PATHS_KEY, [] );

			foreach ( $cachedPaths as $cacheKey ) {
				Cache::forget( $cacheKey );
			}

			// Clear the tracking key itself
			Cache::forget( self::CACHE_PATHS_KEY );
		}
	}

	/**
	 * Find an exact match redirect.
	 *
	 * Checks for exact path matches, including handling trailing slash variations.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The path to match.
	 *
	 * @return Redirect|null
	 */
	protected function findExactMatch( string $path ): ?Redirect
	{
		// Try exact match first
		$redirect = Redirect::active()
			->exact()
			->where( 'from_path', $path )
			->first();

		if ( null !== $redirect ) {
			return $redirect;
		}

		// Try with trailing slash if path doesn't have one
		if ( '/' !== $path && ! str_ends_with( $path, '/' ) ) {
			$redirect = Redirect::active()
				->exact()
				->where( 'from_path', $path . '/' )
				->first();

			if ( null !== $redirect ) {
				return $redirect;
			}
		}

		// Try without trailing slash if path has one
		if ( '/' !== $path && str_ends_with( $path, '/' ) ) {
			$redirect = Redirect::active()
				->exact()
				->where( 'from_path', rtrim( $path, '/' ) )
				->first();
		}

		return $redirect;
	}

	/**
	 * Find a wildcard match redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The path to match.
	 *
	 * @return Redirect|null
	 */
	protected function findWildcardMatch( string $path ): ?Redirect
	{
		$redirects = Redirect::active()->wildcard()->get();

		foreach ( $redirects as $redirect ) {
			if ( $redirect->matches( $path ) ) {
				return $redirect;
			}
		}

		return null;
	}

	/**
	 * Find a regex match redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The path to match.
	 *
	 * @return Redirect|null
	 */
	protected function findRegexMatch( string $path ): ?Redirect
	{
		$redirects = Redirect::active()->regex()->get();

		foreach ( $redirects as $redirect ) {
			if ( $redirect->matches( $path ) ) {
				return $redirect;
			}
		}

		return null;
	}

	/**
	 * Check if creating/updating a redirect would create a chain.
	 *
	 * @since 1.0.0
	 *
	 * @param  string   $fromPath   The source path.
	 * @param  string   $toPath     The destination path.
	 * @param  int|null $excludeId  Redirect ID to exclude from chain detection.
	 *
	 * @return bool
	 */
	protected function wouldCreateChain( string $fromPath, string $toPath, ?int $excludeId = null ): bool
	{
		$maxDepth = config( 'seo.redirects.max_chain_depth', 5 );
		$visited  = [ $this->normalizePath( $fromPath ) ];
		$current  = $this->normalizePath( $toPath );

		for ( $i = 0; $i < $maxDepth; $i++ ) {
			// Check for direct loop
			if ( in_array( $current, $visited, true ) ) {
				return true;
			}

			// Find if there's a redirect from the destination
			$query = Redirect::active()->where( 'from_path', $current );

			if ( null !== $excludeId ) {
				$query->where( 'id', '!=', $excludeId );
			}

			$nextRedirect = $query->first();

			if ( null === $nextRedirect ) {
				// No chain found
				return false;
			}

			$visited[] = $current;
			$current   = $this->normalizePath( $nextRedirect->to_path );
		}

		// Max depth exceeded, consider it a chain
		return true;
	}

	/**
	 * Validate redirect data.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>  $data      The data to validate.
	 * @param  Redirect|null         $redirect  Existing redirect for updates.
	 *
	 * @throws InvalidArgumentException If validation fails.
	 *
	 * @return void
	 */
	protected function validateData( array $data, ?Redirect $redirect = null ): void
	{
		// Require from_path for new redirects
		if ( null === $redirect && empty( $data['from_path'] ) ) {
			throw new InvalidArgumentException( __( 'The from_path field is required.' ) );
		}

		// Require to_path for new redirects
		if ( null === $redirect && empty( $data['to_path'] ) ) {
			throw new InvalidArgumentException( __( 'The to_path field is required.' ) );
		}

		// Validate status code if provided
		if ( isset( $data['status_code'] ) && ! in_array( (int) $data['status_code'], Redirect::VALID_STATUS_CODES, true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					__( 'Invalid status code. Must be one of: %s' ),
					implode( ', ', Redirect::VALID_STATUS_CODES ),
				),
			);
		}

		// Validate match type if provided
		if ( isset( $data['match_type'] ) && ! in_array( $data['match_type'], Redirect::VALID_MATCH_TYPES, true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					__( 'Invalid match type. Must be one of: %s' ),
					implode( ', ', Redirect::VALID_MATCH_TYPES ),
				),
			);
		}

		// Check for duplicate from_path (only for new redirects or when changing from_path)
		$fromPath = $data['from_path'] ?? $redirect?->from_path;
		if ( null !== $fromPath ) {
			$query = Redirect::where( 'from_path', $this->normalizePath( $fromPath ) );

			if ( null !== $redirect ) {
				$query->where( 'id', '!=', $redirect->id );
			}

			// Only check for exact duplicates with exact match type
			$matchType = $data['match_type'] ?? $redirect?->match_type ?? 'exact';
			if ( 'exact' === $matchType && $query->exact()->exists() ) {
				throw new InvalidArgumentException(
					__( 'A redirect from this path already exists.' ),
				);
			}
		}

		// Validate regex pattern if match type is regex
		$matchType = $data['match_type'] ?? $redirect?->match_type ?? 'exact';
		$fromPath  = $data['from_path'] ?? $redirect?->from_path;

		if ( 'regex' === $matchType && null !== $fromPath ) {
			$this->validateRegexPattern( $fromPath );
		}
	}

	/**
	 * Validate a regex pattern.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $pattern  The pattern to validate.
	 *
	 * @throws InvalidArgumentException If the pattern is invalid.
	 *
	 * @return void
	 */
	protected function validateRegexPattern( string $pattern ): void
	{
		// Add delimiters if not present
		if ( ! preg_match( '/^[\/\#\~\@].*[\/\#\~\@][a-zA-Z]*$/', $pattern ) ) {
			$pattern = '#' . $pattern . '#';
		}

		$result = @preg_match( $pattern, '' );

		if ( false === $result ) {
			throw new InvalidArgumentException( __( 'Invalid regular expression pattern.' ) );
		}
	}

	/**
	 * Normalize a path.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The path to normalize.
	 *
	 * @return string
	 */
	protected function normalizePath( string $path ): string
	{
		$path = '/' . ltrim( $path, '/' );

		return rtrim( $path, '/' ) ?: '/';
	}

	/**
	 * Check if caching is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function isCacheEnabled(): bool
	{
		return (bool) config( 'seo.redirects.cache_enabled', true );
	}

	/**
	 * Get the cache key for a path match.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The path.
	 *
	 * @return string
	 */
	protected function getMatchCacheKey( string $path ): string
	{
		return self::CACHE_PREFIX . 'match:' . md5( $path );
	}

	/**
	 * Track a cached path for later invalidation.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $cacheKey  The cache key to track.
	 *
	 * @return void
	 */
	protected function trackCachedPath( string $cacheKey ): void
	{
		Cache::lock( self::CACHE_PATHS_KEY . ':lock', 5 )->block( 5, function () use ( $cacheKey ): void {
			$cachedPaths = Cache::get( self::CACHE_PATHS_KEY, [] );

			if ( ! in_array( $cacheKey, $cachedPaths, true ) ) {
				$cachedPaths[] = $cacheKey;
				Cache::put( self::CACHE_PATHS_KEY, $cachedPaths );
			}
		} );
	}
}
