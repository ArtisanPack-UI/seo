<?php

/**
 * MorphModelResolver.
 *
 * Utility class for resolving a polymorphic type string to a model instance.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.8.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * MorphModelResolver class.
 *
 * Lets an application resolve `seoable_type` / `sitemapable_type` strings
 * that are not class names or morph-map aliases. A model that overrides
 * `getMorphClass()` to return something like `App\Models\Record:products`
 * stores a string Eloquent cannot instantiate, so the `seoable` and
 * `sitemapable` relations would throw `Class "…" not found`. A callback
 * on the `ap.seo.resolveMorphModel` filter can return a configured model
 * instance for such a string instead:
 *
 * ```php
 * addFilter( 'ap.seo.resolveMorphModel', function ( ?Model $model, string $type ): ?Model {
 *     if ( ! str_starts_with( $type, Record::class . ':' ) ) {
 *         return $model;
 *     }
 *
 *     return ( new Record() )->setTable( substr( $type, strlen( Record::class ) + 1 ) );
 * } );
 * ```
 *
 * The returned instance is only used to build the query, so it should be
 * a fresh, unsaved model. Returning `null` leaves Eloquent's default
 * resolution in place.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.8.0
 */
class MorphModelResolver
{
	/**
	 * The filter hook that resolves a morph type to a model instance.
	 *
	 * @since 1.8.0
	 *
	 * @var string
	 */
	public const FILTER = 'ap.seo.resolveMorphModel';

	/**
	 * Resolve a morph type string to a model instance through the filter.
	 *
	 * @since 1.8.0
	 *
	 * @param  string  $type  The stored morph type.
	 *
	 * @return Model|null The resolved model instance, or null to use Eloquent's default.
	 */
	public static function resolve( string $type ): ?Model
	{
		$model = applyFilters( self::FILTER, null, $type );

		return $model instanceof Model ? $model : null;
	}

	/**
	 * Check whether a morph type can be turned into a model.
	 *
	 * True when the filter resolves it, when it is a morph-map alias for an
	 * existing class, or when it is itself an existing class name.
	 *
	 * @since 1.8.0
	 *
	 * @param  string  $type  The stored morph type.
	 *
	 * @return bool True if the morph type can be resolved.
	 */
	public static function canResolve( string $type ): bool
	{
		if ( null !== self::resolve( $type ) ) {
			return true;
		}

		return class_exists( Relation::getMorphedModel( $type ) ?? $type );
	}
}
