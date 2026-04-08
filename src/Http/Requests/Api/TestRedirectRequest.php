<?php

/**
 * TestRedirectRequest.
 *
 * Form request for testing a URL against redirect rules via API.
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
 * TestRedirectRequest class.
 *
 * Validates redirect test requests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class TestRedirectRequest extends FormRequest
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
			'url' => [ 'required', 'string', 'max:2048' ],
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
			'url.required' => __( 'The URL to test is required.' ),
		];
	}
}
