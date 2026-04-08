<?php

/**
 * MetaTagsResource.
 *
 * API Resource for serializing MetaTagsDTO data.
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

use ArtisanPackUI\SEO\DTOs\MetaTagsDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * MetaTagsResource class.
 *
 * Transforms MetaTagsDTO data for API responses with computed fields.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class MetaTagsResource extends JsonResource
{
	/**
	 * Maximum recommended title length.
	 *
	 * @var int
	 */
	protected const MAX_TITLE_LENGTH = 60;

	/**
	 * Maximum recommended description length.
	 *
	 * @var int
	 */
	protected const MAX_DESCRIPTION_LENGTH = 160;

	/**
	 * Create a new resource instance from a MetaTagsDTO.
	 *
	 * @since 1.1.0
	 *
	 * @param  MetaTagsDTO  $resource  The DTO to serialize.
	 */
	public function __construct( MetaTagsDTO $resource )
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
		$titleLength       = mb_strlen( $this->resource->title );
		$descriptionLength = null !== $this->resource->description ? mb_strlen( $this->resource->description ) : 0;

		return [
			'title'           => $this->resource->title,
			'title_length'    => $titleLength,
			'title_warning'   => $titleLength > self::MAX_TITLE_LENGTH
				? __( 'Title exceeds :max characters (:length/:max).', [
					'max'    => self::MAX_TITLE_LENGTH,
					'length' => $titleLength,
				] )
				: null,
			'description'         => $this->resource->description,
			'description_length'  => $descriptionLength,
			'description_warning' => $descriptionLength > self::MAX_DESCRIPTION_LENGTH
				? __( 'Description exceeds :max characters (:length/:max).', [
					'max'    => self::MAX_DESCRIPTION_LENGTH,
					'length' => $descriptionLength,
				] )
				: null,
			'canonical'       => $this->resource->canonical,
			'robots'          => $this->resource->robots,
			'additional_meta' => $this->resource->additionalMeta,
		];
	}
}
