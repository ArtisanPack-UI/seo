<?php

/**
 * ResolvesMorphModels Trait.
 *
 * Routes a model's morphTo relations through the morph model resolver.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.8.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Traits;

use ArtisanPackUI\SEO\Models\Relations\ResolvableMorphTo;
use ArtisanPackUI\SEO\Support\MorphModelResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * ResolvesMorphModels trait.
 *
 * Used by `SeoMeta` and `SitemapEntry` so `seoable` and `sitemapable`
 * resolve types registered on the `ap.seo.resolveMorphModel` filter
 * ({@see MorphModelResolver}). Lazy loading goes through
 * `morphInstanceTo()`, eager loading through {@see ResolvableMorphTo}.
 * Types the filter does not resolve keep Eloquent's default behavior.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.8.0
 */
trait ResolvesMorphModels
{
	/**
	 * Define a polymorphic, inverse relationship for a known morph type.
	 *
	 * @since 1.8.0
	 *
	 * @param  string       $target    The stored morph type.
	 * @param  string       $name      The relationship name.
	 * @param  string       $type      The morph type column.
	 * @param  string       $id        The morph ID column.
	 * @param  string|null  $ownerKey  The owner key on the related model.
	 *
	 * @return MorphTo<Model, $this>
	 */
	protected function morphInstanceTo( $target, $name, $type, $id, $ownerKey )
	{
		$instance = MorphModelResolver::resolve( (string) $target );

		if ( null === $instance ) {
			return parent::morphInstanceTo( $target, $name, $type, $id, $ownerKey );
		}

		if ( ! $instance->getConnectionName() ) {
			$instance->setConnection( $this->connection );
		}

		return $this->newMorphTo(
			$instance->newQuery(),
			$this,
			$id,
			$ownerKey ?? $instance->getKeyName(),
			$type,
			$name,
		);
	}

	/**
	 * Instantiate a resolver-aware MorphTo relationship.
	 *
	 * @since 1.8.0
	 *
	 * @param  Builder<Model>  $query       The related model query.
	 * @param  Model           $parent      The declaring model.
	 * @param  string          $foreignKey  The morph ID column.
	 * @param  string|null     $ownerKey    The owner key on the related model.
	 * @param  string          $type        The morph type column.
	 * @param  string          $relation    The relationship name.
	 *
	 * @return ResolvableMorphTo<Model, $this>
	 */
	protected function newMorphTo( Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation )
	{
		return new ResolvableMorphTo( $query, $parent, $foreignKey, $ownerKey, $type, $relation );
	}
}
