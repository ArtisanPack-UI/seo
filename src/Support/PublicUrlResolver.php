<?php

/**
 * PublicUrlResolver.
 *
 * Utility class for resolving a model's public URL.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.9.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Support;

use ArtisanPackUI\SEO\Contracts\HasPublicUrl;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

/**
 * PublicUrlResolver class.
 *
 * The single source for "where does this model live", so canonical tags,
 * Open Graph, schema and sitemaps can't disagree. Tiers run in this order:
 * saved `canonical_url`, {@see HasPublicUrl}, `getUrl()`, a `permalink`
 * attribute, then the current request URL.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.9.0
 */
class PublicUrlResolver
{
	/**
	 * Resolve the URL for a page rendering a model.
	 *
	 * Use this while rendering the model's own page, where the request URL is
	 * a safe last resort.
	 *
	 * @since 1.9.0
	 *
	 * @param  Model         $model    The model to resolve a URL for.
	 * @param  SeoMeta|null  $seoMeta  The model's SeoMeta instance, if any.
	 *
	 * @return string The URL, or an empty string outside an HTTP request when the model has none.
	 */
	public static function resolve( Model $model, ?SeoMeta $seoMeta = null ): string
	{
		return self::resolveModelUrl( $model, $seoMeta ) ?? self::currentRequestUrl();
	}

	/**
	 * Resolve the URL a model declares for itself.
	 *
	 * Use this when the current request isn't the model's page, such as while
	 * saving it from an admin screen or building a sitemap.
	 *
	 * @since 1.9.0
	 *
	 * @param  Model         $model    The model to resolve a URL for.
	 * @param  SeoMeta|null  $seoMeta  The model's SeoMeta instance, if any.
	 *
	 * @return string|null The URL, or null if the model doesn't declare one.
	 */
	public static function resolveModelUrl( Model $model, ?SeoMeta $seoMeta = null ): ?string
	{
		$canonicalUrl = $seoMeta?->canonical_url;

		if ( null !== $canonicalUrl && '' !== $canonicalUrl ) {
			return $canonicalUrl;
		}

		return self::absoluteUrl( $model instanceof HasPublicUrl ? $model->getPublicUrl() : null )
			?? self::absoluteUrl( method_exists( $model, 'getUrl' ) ? $model->getUrl() : null )
			?? self::absoluteUrl( $model->permalink ?? null );
	}

	/**
	 * Make a URL returned by a model absolute.
	 *
	 * @since 1.9.0
	 *
	 * @param  mixed  $url  The value the model returned.
	 *
	 * @return string|null The absolute URL, or null if the value isn't usable.
	 */
	protected static function absoluteUrl( mixed $url ): ?string
	{
		return is_string( $url ) && '' !== $url ? url( $url ) : null;
	}

	/**
	 * Get the current request URL.
	 *
	 * @since 1.9.0
	 *
	 * @return string The URL, or an empty string outside an HTTP request.
	 */
	protected static function currentRequestUrl(): string
	{
		if ( app()->runningInConsole() || null === request() ) {
			return '';
		}

		return url()->current();
	}
}
