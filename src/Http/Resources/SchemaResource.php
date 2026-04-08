<?php

/**
 * SchemaResource.
 *
 * API Resource for serializing Schema.org configuration data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * SchemaResource class.
 *
 * Transforms schema configuration data for API responses,
 * including schema type, custom markup, and generated JSON-LD.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class SchemaResource extends JsonResource
{
	/**
	 * The generated schema data.
	 *
	 * @var array<string, mixed>|null
	 */
	protected ?array $generated;

	/**
	 * The available schema types.
	 *
	 * @var array<int, string>
	 */
	protected array $availableTypes;

	/**
	 * Create a new SchemaResource instance.
	 *
	 * @since 1.1.0
	 *
	 * @param  mixed                    $resource        The SeoMeta model or null.
	 * @param  array<string, mixed>|null $generated      The generated schema data.
	 * @param  array<int, string>       $availableTypes  The available schema types.
	 */
	public function __construct( mixed $resource, ?array $generated = null, array $availableTypes = [] )
	{
		parent::__construct( $resource );

		$this->generated      = $generated;
		$this->availableTypes = $availableTypes;
	}

	/**
	 * Transform the resource into an array.
	 *
	 * @since 1.1.0
	 *
	 * @param  Request  $request  The incoming request.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray( Request $request ): array
	{
		return [
			'schema_type'     => $this->resource?->schema_type,
			'schema_markup'   => $this->resource?->schema_markup,
			'generated'       => $this->generated,
			'available_types' => $this->availableTypes,
		];
	}
}
