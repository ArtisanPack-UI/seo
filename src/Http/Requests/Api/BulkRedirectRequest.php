<?php

/**
 * BulkRedirectRequest.
 *
 * Form request for bulk redirect operations via API.
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
 * BulkRedirectRequest class.
 *
 * Validates bulk redirect action requests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class BulkRedirectRequest extends FormRequest
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
			'action'      => [ 'required', 'string', Rule::in( [ 'delete', 'change_status_code' ] ) ],
			'ids'         => [ 'required', 'array', 'min:1' ],
			'ids.*'       => [ 'integer', 'exists:redirects,id' ],
			'status_code' => [ 'required_if:action,change_status_code', 'nullable', 'integer', Rule::in( Redirect::VALID_STATUS_CODES ) ],
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
			'action.required'         => __( 'The bulk action is required.' ),
			'action.in'               => __( 'The action must be one of: delete, change_status_code.' ),
			'ids.required'            => __( 'At least one redirect ID is required.' ),
			'ids.min'                 => __( 'At least one redirect ID is required.' ),
			'status_code.required_if' => __( 'A status code is required for the change_status_code action.' ),
		];
	}
}
