<?php

/**
 * SeoMetaResolver.
 *
 * Utility class for resolving a model's SeoMeta relationship.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.7.1
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Support;

use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

/**
 * SeoMetaResolver class.
 *
 * Provides a single relation-aware lookup for a model's SeoMeta so every
 * call site recognizes both a `seoMeta()` method (e.g. from the HasSeo
 * trait) and a relation bound at runtime with `Model::resolveRelationUsing()`.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.7.1
 */
class SeoMetaResolver
{
	/**
	 * The name of the SeoMeta relationship.
	 *
	 * @since 1.7.1
	 *
	 * @var string
	 */
	public const RELATION = 'seoMeta';

	/**
	 * Check if a model defines the seoMeta relationship.
	 *
	 * `method_exists()` alone misses relations registered with
	 * `Model::resolveRelationUsing()`, which are served by `__call()`.
	 *
	 * @since 1.7.1
	 *
	 * @param  Model  $model  The model to check.
	 *
	 * @return bool True if the model has a seoMeta relationship.
	 */
	public static function hasRelation( Model $model ): bool
	{
		return method_exists( $model, self::RELATION ) || $model->isRelation( self::RELATION );
	}

	/**
	 * Resolve the SeoMeta record for a model.
	 *
	 * @since 1.7.1
	 *
	 * @param  Model  $model  The model to resolve SEO meta for.
	 *
	 * @return SeoMeta|null The SeoMeta record, or null if none exists.
	 */
	public static function resolve( Model $model ): ?SeoMeta
	{
		if ( ! self::hasRelation( $model ) ) {
			return null;
		}

		$seoMeta = $model->{self::RELATION};

		return $seoMeta instanceof SeoMeta ? $seoMeta : null;
	}
}
