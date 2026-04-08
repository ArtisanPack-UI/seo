<?php

/**
 * TwitterCardResource.
 *
 * API Resource for serializing TwitterCardDTO data.
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

use ArtisanPackUI\SEO\DTOs\TwitterCardDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TwitterCardResource class.
 *
 * Transforms TwitterCardDTO data for API responses.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class TwitterCardResource extends JsonResource
{
	/**
	 * Create a new resource instance from a TwitterCardDTO.
	 *
	 * @since 1.1.0
	 *
	 * @param  TwitterCardDTO  $resource  The DTO to serialize.
	 */
	public function __construct( TwitterCardDTO $resource )
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
			'card'        => $this->resource->card,
			'title'       => $this->resource->title,
			'description' => $this->resource->description,
			'image'       => $this->resource->image,
			'site'        => $this->resource->site,
			'creator'     => $this->resource->creator,
		];
	}
}
