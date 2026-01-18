<?php
/**
 * Redirect Model.
 *
 * Eloquent model for managing URL redirects.
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

namespace ArtisanPackUI\SEO\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Redirect model for URL redirect management.
 *
 * @property int         $id
 * @property string      $from_path
 * @property string      $to_path
 * @property int         $status_code
 * @property string      $match_type
 * @property bool        $is_active
 * @property int         $hits
 * @property Carbon|null $last_hit_at
 * @property string|null $notes
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class Redirect extends Model
{
	/**
	 * Match type for exact path matching.
	 *
	 * @var string
	 */
	public const MATCH_EXACT = 'exact';

	/**
	 * Match type for regex pattern matching.
	 *
	 * @var string
	 */
	public const MATCH_REGEX = 'regex';

	/**
	 * Match type for wildcard pattern matching.
	 *
	 * @var string
	 */
	public const MATCH_WILDCARD = 'wildcard';

	/**
	 * Valid status codes for redirects.
	 *
	 * @var array<int>
	 */
	public const VALID_STATUS_CODES = [ 301, 302, 307, 308 ];

	/**
	 * Valid match types.
	 *
	 * @var array<string>
	 */
	public const VALID_MATCH_TYPES = [ self::MATCH_EXACT, self::MATCH_REGEX, self::MATCH_WILDCARD ];

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'redirects';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'from_path',
		'to_path',
		'status_code',
		'match_type',
		'is_active',
		'notes',
	];

	/**
	 * The model's default values for attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected $attributes = [
		'status_code' => 301,
		'match_type'  => 'exact',
		'is_active'   => true,
		'hits'        => 0,
	];

	/**
	 * Scope a query to only include active redirects.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<Redirect>  $query  The query builder instance.
	 *
	 * @return Builder<Redirect>
	 */
	public function scopeActive( Builder $query ): Builder
	{
		return $query->where( 'is_active', true );
	}

	/**
	 * Scope a query to only include inactive redirects.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<Redirect>  $query  The query builder instance.
	 *
	 * @return Builder<Redirect>
	 */
	public function scopeInactive( Builder $query ): Builder
	{
		return $query->where( 'is_active', false );
	}

	/**
	 * Scope a query to filter by match type.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<Redirect>  $query  The query builder instance.
	 * @param  string             $type   The match type to filter by.
	 *
	 * @return Builder<Redirect>
	 */
	public function scopeOfType( Builder $query, string $type ): Builder
	{
		return $query->where( 'match_type', $type );
	}

	/**
	 * Scope a query to filter exact match redirects.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<Redirect>  $query  The query builder instance.
	 *
	 * @return Builder<Redirect>
	 */
	public function scopeExact( Builder $query ): Builder
	{
		return $query->where( 'match_type', self::MATCH_EXACT );
	}

	/**
	 * Scope a query to filter regex match redirects.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<Redirect>  $query  The query builder instance.
	 *
	 * @return Builder<Redirect>
	 */
	public function scopeRegex( Builder $query ): Builder
	{
		return $query->where( 'match_type', self::MATCH_REGEX );
	}

	/**
	 * Scope a query to filter wildcard match redirects.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<Redirect>  $query  The query builder instance.
	 *
	 * @return Builder<Redirect>
	 */
	public function scopeWildcard( Builder $query ): Builder
	{
		return $query->where( 'match_type', self::MATCH_WILDCARD );
	}

	/**
	 * Scope a query to filter by status code.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<Redirect>  $query       The query builder instance.
	 * @param  int                $statusCode  The status code to filter by.
	 *
	 * @return Builder<Redirect>
	 */
	public function scopeWithStatusCode( Builder $query, int $statusCode ): Builder
	{
		return $query->where( 'status_code', $statusCode );
	}

	/**
	 * Scope a query to filter permanent redirects (301, 308).
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<Redirect>  $query  The query builder instance.
	 *
	 * @return Builder<Redirect>
	 */
	public function scopePermanent( Builder $query ): Builder
	{
		return $query->whereIn( 'status_code', [ 301, 308 ] );
	}

	/**
	 * Scope a query to filter temporary redirects (302, 307).
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<Redirect>  $query  The query builder instance.
	 *
	 * @return Builder<Redirect>
	 */
	public function scopeTemporary( Builder $query ): Builder
	{
		return $query->whereIn( 'status_code', [ 302, 307 ] );
	}

	/**
	 * Scope a query to filter redirects with hits.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<Redirect>  $query  The query builder instance.
	 *
	 * @return Builder<Redirect>
	 */
	public function scopeWithHits( Builder $query ): Builder
	{
		return $query->where( 'hits', '>', 0 );
	}

	/**
	 * Scope a query to filter redirects without hits.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<Redirect>  $query  The query builder instance.
	 *
	 * @return Builder<Redirect>
	 */
	public function scopeWithoutHits( Builder $query ): Builder
	{
		return $query->where( 'hits', 0 );
	}

	/**
	 * Scope a query to order by most hits.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<Redirect>  $query  The query builder instance.
	 *
	 * @return Builder<Redirect>
	 */
	public function scopeMostHits( Builder $query ): Builder
	{
		return $query->orderBy( 'hits', 'desc' );
	}

	/**
	 * Scope a query to order by recently hit.
	 *
	 * @since 1.0.0
	 *
	 * @param  Builder<Redirect>  $query  The query builder instance.
	 *
	 * @return Builder<Redirect>
	 */
	public function scopeRecentlyHit( Builder $query ): Builder
	{
		return $query->orderBy( 'last_hit_at', 'desc' );
	}

	/**
	 * Check if this is a permanent redirect.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isPermanent(): bool
	{
		return in_array( $this->status_code, [ 301, 308 ], true );
	}

	/**
	 * Check if this is a temporary redirect.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isTemporary(): bool
	{
		return in_array( $this->status_code, [ 302, 307 ], true );
	}

	/**
	 * Check if this redirect uses exact matching.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isExactMatch(): bool
	{
		return self::MATCH_EXACT === $this->match_type;
	}

	/**
	 * Check if this redirect uses regex matching.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isRegexMatch(): bool
	{
		return self::MATCH_REGEX === $this->match_type;
	}

	/**
	 * Check if this redirect uses wildcard matching.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isWildcardMatch(): bool
	{
		return self::MATCH_WILDCARD === $this->match_type;
	}

	/**
	 * Get the status code label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getStatusCodeLabel(): string
	{
		$labels = [
			301 => __( 'Moved Permanently (301)' ),
			302 => __( 'Found (302)' ),
			307 => __( 'Temporary Redirect (307)' ),
			308 => __( 'Permanent Redirect (308)' ),
		];

		return $labels[ $this->status_code ] ?? __( 'Unknown' );
	}

	/**
	 * Get the match type label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getMatchTypeLabel(): string
	{
		$labels = [
			self::MATCH_EXACT    => __( 'Exact Match' ),
			self::MATCH_REGEX    => __( 'Regular Expression' ),
			self::MATCH_WILDCARD => __( 'Wildcard' ),
		];

		return $labels[ $this->match_type ] ?? __( 'Unknown' );
	}

	/**
	 * Increment the hit counter.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function recordHit(): void
	{
		$this->timestamps = false;
		$this->increment( 'hits' );
		$this->forceFill( [ 'last_hit_at' => now() ] )->saveQuietly();
		$this->timestamps = true;
	}

	/**
	 * Check if the given path matches this redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The path to check.
	 *
	 * @return bool
	 */
	public function matches( string $path ): bool
	{
		$path = $this->normalizePath( $path );

		return match ( $this->match_type ) {
			self::MATCH_EXACT    => $this->matchExact( $path ),
			self::MATCH_REGEX    => $this->matchRegex( $path ),
			self::MATCH_WILDCARD => $this->matchWildcard( $path ),
			default              => false,
		};
	}

	/**
	 * Get the resolved destination path.
	 *
	 * For regex and wildcard matches, this may include captured groups.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The original request path.
	 *
	 * @return string
	 */
	public function getResolvedDestination( string $path ): string
	{
		$path = $this->normalizePath( $path );

		if ( self::MATCH_REGEX === $this->match_type ) {
			return $this->resolveRegexDestination( $path );
		}

		if ( self::MATCH_WILDCARD === $this->match_type ) {
			return $this->resolveWildcardDestination( $path );
		}

		return $this->to_path;
	}

	/**
	 * Bootstrap the model and register event listeners.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected static function booted(): void
	{
		static::saving( function ( Redirect $redirect ): void {
			// Validate status code
			if ( ! in_array( $redirect->status_code, self::VALID_STATUS_CODES, true ) ) {
				throw new InvalidArgumentException(
					sprintf(
						__( 'Invalid status code. Must be one of: %s' ),
						implode( ', ', self::VALID_STATUS_CODES ),
					),
				);
			}

			// Validate match type
			if ( ! in_array( $redirect->match_type, self::VALID_MATCH_TYPES, true ) ) {
				throw new InvalidArgumentException(
					sprintf(
						__( 'Invalid match type. Must be one of: %s' ),
						implode( ', ', self::VALID_MATCH_TYPES ),
					),
				);
			}

			// Validate regex pattern if applicable
			if ( self::MATCH_REGEX === $redirect->match_type ) {
				$redirect->validateRegexPattern();
			}
		} );
	}

	/**
	 * Get the attributes that should be cast.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'status_code' => 'integer',
			'is_active'   => 'boolean',
			'hits'        => 'integer',
			'last_hit_at' => 'datetime',
		];
	}

	/**
	 * Normalize a path for matching.
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
	 * Check for exact path match.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The path to check.
	 *
	 * @return bool
	 */
	protected function matchExact( string $path ): bool
	{
		$fromPath = $this->normalizePath( $this->from_path );

		return $path === $fromPath;
	}

	/**
	 * Check for regex pattern match with timeout protection.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The path to check.
	 *
	 * @return bool
	 */
	protected function matchRegex( string $path ): bool
	{
		$pattern = $this->from_path;

		// Add delimiters if not present
		if ( ! preg_match( '/^[\/\#\~\@].*[\/\#\~\@][a-zA-Z]*$/', $pattern ) ) {
			$pattern = '#' . $pattern . '#';
		}

		// Use pcre.backtrack_limit to prevent ReDoS
		$backtrackLimit = ini_get( 'pcre.backtrack_limit' );
		ini_set( 'pcre.backtrack_limit', '10000' );

		try {
			$result = @preg_match( $pattern, $path );

			return 1 === $result;
		} finally {
			ini_set( 'pcre.backtrack_limit', $backtrackLimit );
		}
	}

	/**
	 * Check for wildcard pattern match.
	 *
	 * Wildcard patterns use * for any characters.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The path to check.
	 *
	 * @return bool
	 */
	protected function matchWildcard( string $path ): bool
	{
		$pattern = $this->normalizePath( $this->from_path );

		// Convert wildcard to regex
		$regex = '#^' . str_replace(
			[ '\*', '\?' ],
			[ '.*', '.' ],
			preg_quote( $pattern, '#' ),
		) . '$#';

		return 1 === preg_match( $regex, $path );
	}

	/**
	 * Resolve regex destination with captured groups.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The original request path.
	 *
	 * @return string
	 */
	protected function resolveRegexDestination( string $path ): string
	{
		$pattern = $this->from_path;

		// Add delimiters if not present
		if ( ! preg_match( '/^[\/\#\~\@].*[\/\#\~\@][a-zA-Z]*$/', $pattern ) ) {
			$pattern = '#' . $pattern . '#';
		}

		$backtrackLimit = ini_get( 'pcre.backtrack_limit' );
		ini_set( 'pcre.backtrack_limit', '10000' );

		try {
			if ( preg_match( $pattern, $path, $matches ) ) {
				$destination = $this->to_path;

				// Replace $1, $2, etc. with captured groups (in reverse to avoid double-replacement)
				for ( $index = count( $matches ) - 1; $index > 0; $index-- ) {
					$destination = str_replace( '$' . $index, $matches[ $index ], $destination );
				}

				return $destination;
			}
		} finally {
			ini_set( 'pcre.backtrack_limit', $backtrackLimit );
		}

		return $this->to_path;
	}

	/**
	 * Resolve wildcard destination.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The original request path.
	 *
	 * @return string
	 */
	protected function resolveWildcardDestination( string $path ): string
	{
		$pattern = $this->normalizePath( $this->from_path );

		// Convert wildcard to regex with capturing group
		$regex = '#^' . str_replace(
			[ '\*', '\?' ],
			[ '(.*)', '(.)' ],
			preg_quote( $pattern, '#' ),
		) . '$#';

		if ( preg_match( $regex, $path, $matches ) ) {
			$destination = $this->to_path;

			// Replace * with captured content
			$captureIndex = 1;
			while ( false !== strpos( $destination, '*' ) && isset( $matches[ $captureIndex ] ) ) {
				$destination = preg_replace( '/\*/', $matches[ $captureIndex ], $destination, 1 );
				$captureIndex++;
			}

			return $destination;
		}

		return $this->to_path;
	}

	/**
	 * Validate the regex pattern.
	 *
	 * @since 1.0.0
	 *
	 * @throws InvalidArgumentException If the pattern is invalid.
	 *
	 * @return void
	 */
	protected function validateRegexPattern(): void
	{
		$pattern = $this->from_path;

		// Add delimiters if not present
		if ( ! preg_match( '/^[\/\#\~\@].*[\/\#\~\@][a-zA-Z]*$/', $pattern ) ) {
			$pattern = '#' . $pattern . '#';
		}

		// Test compile the pattern
		$result = @preg_match( $pattern, '' );

		if ( false === $result ) {
			throw new InvalidArgumentException(
				__( 'Invalid regular expression pattern.' ),
			);
		}
	}
}
