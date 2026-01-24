<?php

/**
 * SchemaTypeContract.
 *
 * Contract for Schema.org type builders.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * SchemaTypeContract interface.
 *
 * Defines the contract for all Schema.org type builders.
 * Each schema type builder must implement this interface.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
interface SchemaTypeContract
{
	/**
	 * Generate the schema data array.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|null  $model  Optional model to generate schema for.
	 *
	 * @return array<string, mixed>
	 */
	public function generate( ?Model $model = null ): array;

	/**
	 * Get the Schema.org type name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getType(): string;
}
