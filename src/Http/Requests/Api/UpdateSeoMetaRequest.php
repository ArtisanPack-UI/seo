<?php

/**
 * UpdateSeoMetaRequest.
 *
 * Form request for updating SEO meta data via API.
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
 * UpdateSeoMetaRequest class.
 *
 * Validates SEO meta data update requests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class UpdateSeoMetaRequest extends FormRequest
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
			'meta_title'           => [ 'nullable', 'string', 'max:255' ],
			'meta_description'     => [ 'nullable', 'string', 'max:500' ],
			'canonical_url'        => [ 'nullable', 'url', 'max:2048' ],
			'no_index'             => [ 'nullable', 'boolean' ],
			'no_follow'            => [ 'nullable', 'boolean' ],
			'robots_meta'          => [ 'nullable', 'string', 'max:255' ],
			'og_title'             => [ 'nullable', 'string', 'max:255' ],
			'og_description'       => [ 'nullable', 'string', 'max:500' ],
			'og_image'             => [ 'nullable', 'string', 'max:2048' ],
			'og_image_id'          => [ 'nullable', 'integer' ],
			'og_type'              => [ 'nullable', 'string', 'max:50' ],
			'og_locale'            => [ 'nullable', 'string', 'max:10' ],
			'og_site_name'         => [ 'nullable', 'string', 'max:255' ],
			'twitter_card'         => [ 'nullable', 'string', 'in:summary,summary_large_image,app,player' ],
			'twitter_title'        => [ 'nullable', 'string', 'max:255' ],
			'twitter_description'  => [ 'nullable', 'string', 'max:500' ],
			'twitter_image'        => [ 'nullable', 'string', 'max:2048' ],
			'twitter_image_id'     => [ 'nullable', 'integer' ],
			'twitter_site'         => [ 'nullable', 'string', 'max:255' ],
			'twitter_creator'      => [ 'nullable', 'string', 'max:255' ],
			'schema_type'          => [ 'nullable', 'string', 'max:100' ],
			'schema_markup'        => [ 'nullable', 'array' ],
			'focus_keyword'        => [ 'nullable', 'string', 'max:255' ],
			'secondary_keywords'   => [ 'nullable', 'array' ],
			'secondary_keywords.*' => [ 'string', 'max:255' ],
			'hreflang'             => [ 'nullable', 'array' ],
			'hreflang.*'           => [ 'url', 'max:2048' ],
			'sitemap_priority'     => [ 'nullable', 'numeric', 'between:0,1' ],
			'sitemap_changefreq'   => [ 'nullable', 'string', 'in:always,hourly,daily,weekly,monthly,yearly,never' ],
			'exclude_from_sitemap' => [ 'nullable', 'boolean' ],
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
			'canonical_url.url'          => __( 'The canonical URL must be a valid URL.' ),
			'twitter_card.in'            => __( 'The Twitter card type must be one of: summary, summary_large_image, app, player.' ),
			'sitemap_priority.between'   => __( 'The sitemap priority must be between 0 and 1.' ),
			'sitemap_changefreq.in'      => __( 'The sitemap change frequency must be one of: always, hourly, daily, weekly, monthly, yearly, never.' ),
		];
	}
}
