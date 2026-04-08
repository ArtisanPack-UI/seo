<?php

/**
 * AnalyzeContentRequest.
 *
 * Form request for running SEO content analysis via API.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * AnalyzeContentRequest class.
 *
 * Validates SEO content analysis requests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class AnalyzeContentRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function authorize(): bool
	{
		return true;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function rules(): array
	{
		return [
			'model_type'    => [ 'required', 'string', 'max:255' ],
			'model_id'      => [ 'required', 'integer', 'min:1' ],
			'focus_keyword' => [ 'nullable', 'string', 'max:255' ],
		];
	}

	/**
	 * Get custom validation messages.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, string>
	 */
	public function messages(): array
	{
		return [
			'model_type.required' => __( 'The model type is required.' ),
			'model_id.required'   => __( 'The model ID is required.' ),
			'model_id.integer'    => __( 'The model ID must be an integer.' ),
		];
	}
}
