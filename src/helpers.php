<?php

/**
 * SEO Helper Functions.
 *
 * Global helper functions for the SEO package.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Facades\SEO;
use Illuminate\Database\Eloquent\Model;

if ( ! function_exists( 'seo' ) ) {
	/**
	 * Get the SEO service instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \ArtisanPackUI\SEO\SEO
	 */
	function seo(): \ArtisanPackUI\SEO\SEO
	{
		return app( 'seo' );
	}
}

if ( ! function_exists( 'seoTitle' ) ) {
	/**
	 * Get a formatted page title with site name.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $title  The page title.
	 *
	 * @return string
	 */
	function seoTitle( string $title ): string
	{
		return SEO::title( $title );
	}
}

if ( ! function_exists( 'seoMeta' ) ) {
	/**
	 * Get meta tags for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|null  $model  The model to get meta tags for.
	 *
	 * @return array<string, mixed>
	 */
	function seoMeta( ?Model $model = null ): array
	{
		return SEO::getMetaTags( $model );
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
		return SEO::isEnabled( $feature );
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
