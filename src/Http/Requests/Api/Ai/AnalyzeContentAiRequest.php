<?php

/**
 * AnalyzeContentAiRequest.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Http\Requests\Api\Ai;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Validates requests to the content-analysis AI endpoint.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class AnalyzeContentAiRequest extends FormRequest
{
	/**
	 * {@inheritDoc}
	 */
	public function authorize(): bool
	{
		return Gate::allows( 'seo.ai.use' );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function rules(): array
	{
		return [
			'content'              => [ 'required', 'string', 'min:1', 'max:20000' ],
			'primary_keyword'      => [ 'required', 'string', 'max:200' ],
			'secondary_keywords'   => [ 'nullable', 'array', 'max:20' ],
			'secondary_keywords.*' => [ 'string', 'max:200' ],
		];
	}
}
