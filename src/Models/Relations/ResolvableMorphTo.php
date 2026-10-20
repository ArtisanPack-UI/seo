<?php

/**
 * ResolvableMorphTo Relation.
 *
 * MorphTo relation that consults the morph model resolver when eager loading.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.8.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Models\Relations;

use ArtisanPackUI\SEO\Support\MorphModelResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * ResolvableMorphTo class.
 *
 * Eager loading (`with( 'seoable' )`) groups rows by their stored type and
 * builds one query per type from `createModelByType()`. This override asks
 * {@see MorphModelResolver} for a configured instance first, so a type the
 * filter resolves queries the right table instead of throwing.
 *
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 *
 * @extends MorphTo<TRelatedModel, TDeclaringModel>
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.8.0
 */
class ResolvableMorphTo extends MorphTo
{
	/**
	 * Create a new model instance by type.
	 *
	 * @since 1.8.0
	 *
	 * @param  string  $type  The stored morph type.
	 *
	 * @return Model
	 */
	public function createModelByType( $type )
	{
		$instance = MorphModelResolver::resolve( (string) $type );

		if ( null === $instance ) {
			return parent::createModelByType( $type );
		}

		if ( ! $instance->getConnectionName() ) {
			$instance->setConnection( $this->getConnection()->getName() );
		}

		return $instance;
	}
}
