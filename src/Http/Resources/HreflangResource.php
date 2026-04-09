<?php

/**
 * HreflangResource.
 *
 * API Resource for serializing hreflang language/region URL mappings.
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
 * HreflangResource class.
 *
 * Transforms hreflang data for API responses.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class HreflangResource extends JsonResource
{
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
			'hreflang' => $this->resource['hreflang'],
			'href'     => $this->resource['href'],
		];
	}
}
