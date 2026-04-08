<?php

/**
 * OpenGraphResource.
 *
 * API Resource for serializing OpenGraphDTO data.
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

use ArtisanPackUI\SEO\DTOs\OpenGraphDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * OpenGraphResource class.
 *
 * Transforms OpenGraphDTO data for API responses.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class OpenGraphResource extends JsonResource
{
	/**
	 * Create a new resource instance from an OpenGraphDTO.
	 *
	 * @since 1.1.0
	 *
	 * @param  OpenGraphDTO  $resource  The DTO to serialize.
	 */
	public function __construct( OpenGraphDTO $resource )
	{
		parent::__construct( $resource );
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
			'title'       => $this->resource->title,
			'description' => $this->resource->description,
			'image'       => $this->resource->image,
			'url'         => $this->resource->url,
			'type'        => $this->resource->type,
			'site_name'   => $this->resource->siteName,
			'locale'      => $this->resource->locale,
		];
	}
}
