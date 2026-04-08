<?php

/**
 * RedirectResource.
 *
 * API Resource for serializing redirect data.
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
 * RedirectResource class.
 *
 * Transforms Redirect model data for API responses.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class RedirectResource extends JsonResource
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
			'id'                => $this->id,
			'from_path'         => $this->from_path,
			'to_path'           => $this->to_path,
			'status_code'       => $this->status_code,
			'status_code_label' => $this->getStatusCodeLabel(),
			'match_type'        => $this->match_type,
			'match_type_label'  => $this->getMatchTypeLabel(),
			'is_active'         => $this->is_active,
			'hits'              => $this->hits,
			'last_hit_at'       => $this->last_hit_at?->toIso8601String(),
			'notes'             => $this->notes,
			'created_at'        => $this->created_at?->toIso8601String(),
			'updated_at'        => $this->updated_at?->toIso8601String(),
		];
	}
}
