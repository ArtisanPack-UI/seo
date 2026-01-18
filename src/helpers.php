<?php

/**
 * SEO Helper Functions.
 *
 * Global helper functions for the SEO package.
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

use ArtisanPackUI\SEO\Models\Redirect;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\RedirectService;
use ArtisanPackUI\SEO\Services\SeoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

if ( ! function_exists( 'seo' ) ) {
	/**
	 * Get the SEO service instance.
	 *
	 * @since 1.0.0
	 *
	 * @return SeoService
	 */
	function seo(): SeoService
	{
		return app( SeoService::class );
	}
}

if ( ! function_exists( 'seoMeta' ) ) {
	/**
	 * Get SEO meta data for a model.
	 *
	 * Returns the model's SeoMeta relationship if it exists,
	 * or null if the model doesn't have SEO meta data.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get SEO meta for.
	 *
	 * @return SeoMeta|null
	 */
	function seoMeta( Model $model ): ?SeoMeta
	{
		if ( method_exists( $model, 'seoMeta' ) ) {
			return $model->seoMeta;
		}

		return null;
	}
}

if ( ! function_exists( 'seoTitle' ) ) {
	/**
	 * Format a page title with optional site name suffix.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $title          The page title.
	 * @param  bool    $includeSuffix  Whether to include the site name suffix.
	 *
	 * @return string
	 */
	function seoTitle( string $title, bool $includeSuffix = true ): string
	{
		return app( SeoService::class )->buildTitle( $title, $includeSuffix );
	}
}

if ( ! function_exists( 'seoDescription' ) ) {
	/**
	 * Format a meta description by limiting its length.
	 *
	 * Limits the description to 160 characters, which is the
	 * recommended maximum length for meta descriptions.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $description  The description to format.
	 * @param  int     $limit        Maximum character length (default 160).
	 *
	 * @return string
	 */
	function seoDescription( string $description, int $limit = 160 ): string
	{
		return Str::limit( $description, $limit );
	}
}

if ( ! function_exists( 'seoIsEnabled' ) ) {
	/**
	 * Check if an SEO feature is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $feature  The feature name (e.g., 'sitemap', 'redirects', 'analysis').
	 *
	 * @return bool
	 */
	function seoIsEnabled( string $feature ): bool
	{
		return (bool) config( "seo.{$feature}.enabled", false );
	}
}

if ( ! function_exists( 'seoConfig' ) ) {
	/**
	 * Get an SEO configuration value.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $key      The configuration key (without 'seo.' prefix).
	 * @param  mixed   $default  The default value.
	 *
	 * @return mixed
	 */
	function seoConfig( string $key, mixed $default = null ): mixed
	{
		return config( "seo.{$key}", $default );
	}
}

if ( ! function_exists( 'seoRedirect' ) ) {
	/**
	 * Get the redirect service instance.
	 *
	 * @since 1.0.0
	 *
	 * @return RedirectService
	 */
	function seoRedirect(): RedirectService
	{
		return app( RedirectService::class );
	}
}

if ( ! function_exists( 'seoFindRedirect' ) ) {
	/**
	 * Find a redirect match for the given path.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path  The path to find a redirect for.
	 *
	 * @return Redirect|null
	 */
	function seoFindRedirect( string $path ): ?Redirect
	{
		return app( RedirectService::class )->findMatch( $path );
	}
}

if ( ! function_exists( 'seoCreateRedirect' ) ) {
	/**
	 * Create a new redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $fromPath    The source path.
	 * @param  string  $toPath      The destination path.
	 * @param  int     $statusCode  The HTTP status code (default 301).
	 * @param  string  $matchType   The match type: exact, regex, wildcard (default exact).
	 *
	 * @return Redirect
	 */
	function seoCreateRedirect(
		string $fromPath,
		string $toPath,
		int $statusCode = 301,
		string $matchType = 'exact',
	): Redirect {
		return app( RedirectService::class )->create( [
			'from_path'   => $fromPath,
			'to_path'     => $toPath,
			'status_code' => $statusCode,
			'match_type'  => $matchType,
		] );
	}
}

if ( ! function_exists( 'seoDeleteRedirect' ) ) {
	/**
	 * Delete a redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  int|Redirect  $redirect  The redirect or redirect ID to delete.
	 *
	 * @return void
	 */
	function seoDeleteRedirect( Redirect|int $redirect ): void
	{
		if ( is_int( $redirect ) ) {
			$redirect = Redirect::findOrFail( $redirect );
		}

		app( RedirectService::class )->delete( $redirect );
	}
}

if ( ! function_exists( 'seoRedirectStatistics' ) ) {
	/**
	 * Get redirect statistics.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	function seoRedirectStatistics(): array
	{
		return app( RedirectService::class )->getStatistics();
	}
}
