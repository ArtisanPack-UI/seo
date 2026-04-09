<?php

/**
 * UpdateSchemaRequest.
 *
 * Form request for updating schema configuration via API.
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
 * UpdateSchemaRequest class.
 *
 * Validates schema configuration update requests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class UpdateSchemaRequest extends FormRequest
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
			'schema_type'   => [ 'nullable', 'string', 'max:100' ],
			'schema_markup' => [ 'nullable', 'array' ],
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
			'schema_type.max' => __( 'The schema type must not exceed 100 characters.' ),
		];
	}
}
