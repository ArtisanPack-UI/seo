<?php

/**
 * UpdateRedirectRequest.
 *
 * Form request for updating a redirect via API.
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

use ArtisanPackUI\SEO\Models\Redirect;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateRedirectRequest class.
 *
 * Validates redirect update requests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class UpdateRedirectRequest extends FormRequest
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
			'from_path'   => [ 'nullable', 'string', 'max:2048' ],
			'to_path'     => [ 'nullable', 'string', 'max:2048' ],
			'status_code' => [ 'nullable', 'integer', Rule::in( Redirect::VALID_STATUS_CODES ) ],
			'match_type'  => [ 'nullable', 'string', Rule::in( Redirect::VALID_MATCH_TYPES ) ],
			'is_active'   => [ 'nullable', 'boolean' ],
			'notes'       => [ 'nullable', 'string', 'max:1000' ],
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
			'status_code.in' => __( 'The status code must be one of: :values.', [
				'values' => implode( ', ', Redirect::VALID_STATUS_CODES ),
			] ),
			'match_type.in' => __( 'The match type must be one of: :values.', [
				'values' => implode( ', ', Redirect::VALID_MATCH_TYPES ),
			] ),
		];
	}
}
