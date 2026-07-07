<?php

/**
 * SuggestHreflangAiRequest.
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
 * Validates requests to the hreflang-suggestion AI endpoint.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class SuggestHreflangAiRequest extends FormRequest
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
			'pages'                       => [ 'required', 'array', 'min:1', 'max:500' ],
			'pages.*.url'                 => [ 'required', 'string', 'max:2048' ],
			'pages.*.lang'                => [ 'required', 'string', 'max:35' ],
			'pages.*.translations'        => [ 'nullable', 'array' ],
			'pages.*.translations.*.url'  => [ 'required', 'string', 'max:2048' ],
			'pages.*.translations.*.lang' => [ 'required', 'string', 'max:35' ],
		];
	}
}
