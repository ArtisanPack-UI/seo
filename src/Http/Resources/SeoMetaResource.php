<?php

/**
 * SeoMetaResource.
 *
 * API Resource for serializing SEO meta data.
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
 * SeoMetaResource class.
 *
 * Transforms SeoMeta model data for API responses with
 * conditional relationships and computed fields.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class SeoMetaResource extends JsonResource
{
	/**
	 * Maximum recommended title length.
	 *
	 * @var int
	 */
	protected const MAX_TITLE_LENGTH = 60;

	/**
	 * Maximum recommended description length.
	 *
	 * @var int
	 */
	protected const MAX_DESCRIPTION_LENGTH = 160;

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
		$titleLength       = null !== $this->meta_title ? mb_strlen( $this->meta_title ) : 0;
		$descriptionLength = null !== $this->meta_description ? mb_strlen( $this->meta_description ) : 0;

		return [
			'id'                      => $this->id,
			'seoable_type'            => $this->seoable_type,
			'seoable_id'              => $this->seoable_id,
			'meta_title'              => $this->meta_title,
			'meta_title_length'       => $titleLength,
			'meta_title_warning'      => $titleLength > self::MAX_TITLE_LENGTH
				? __( 'Title exceeds :max characters (:length/:max).', [
					'max'    => self::MAX_TITLE_LENGTH,
					'length' => $titleLength,
				] )
				: null,
			'meta_description'         => $this->meta_description,
			'meta_description_length'  => $descriptionLength,
			'meta_description_warning' => $descriptionLength > self::MAX_DESCRIPTION_LENGTH
				? __( 'Description exceeds :max characters (:length/:max).', [
					'max'    => self::MAX_DESCRIPTION_LENGTH,
					'length' => $descriptionLength,
				] )
				: null,
			'canonical_url'           => $this->canonical_url,
			'no_index'                => $this->no_index,
			'no_follow'               => $this->no_follow,
			'robots_meta'             => $this->robots_meta,
			'robots_content'          => $this->getRobotsContent(),
			'is_indexable'            => $this->isIndexable(),
			'is_followable'           => $this->isFollowable(),
			'og_title'                => $this->og_title,
			'og_description'          => $this->og_description,
			'og_image'                => $this->og_image,
			'og_image_id'             => $this->og_image_id,
			'og_type'                 => $this->og_type,
			'og_locale'               => $this->og_locale,
			'og_site_name'            => $this->og_site_name,
			'has_open_graph'          => $this->hasOpenGraphData(),
			'twitter_card'            => $this->twitter_card,
			'twitter_title'           => $this->twitter_title,
			'twitter_description'     => $this->twitter_description,
			'twitter_image'           => $this->twitter_image,
			'twitter_image_id'        => $this->twitter_image_id,
			'twitter_site'            => $this->twitter_site,
			'twitter_creator'         => $this->twitter_creator,
			'has_twitter_card'        => $this->hasTwitterCardData(),
			'pinterest_description'   => $this->pinterest_description,
			'pinterest_image'         => $this->pinterest_image,
			'pinterest_image_id'      => $this->pinterest_image_id,
			'slack_title'             => $this->slack_title,
			'slack_description'       => $this->slack_description,
			'slack_image'             => $this->slack_image,
			'slack_image_id'          => $this->slack_image_id,
			'schema_type'             => $this->schema_type,
			'schema_markup'           => $this->schema_markup,
			'has_schema'              => $this->hasSchemaMarkup(),
			'focus_keyword'           => $this->focus_keyword,
			'secondary_keywords'      => $this->secondary_keywords,
			'all_keywords'            => $this->getAllKeywords(),
			'hreflang'                => $this->hreflang,
			'sitemap_priority'        => $this->sitemap_priority,
			'sitemap_changefreq'      => $this->sitemap_changefreq,
			'exclude_from_sitemap'    => $this->exclude_from_sitemap,
			'in_sitemap'              => $this->shouldIncludeInSitemap(),
			'analysis_cache'          => $this->whenLoaded( 'analysisCache', function () {
				$cache = $this->analysisCache;

				return [
					'overall_score' => $cache->overall_score,
					'grade'         => $cache->getGrade(),
					'analyzed_at'   => $cache->analyzed_at?->toIso8601String(),
					'is_stale'      => $cache->isStale(),
				];
			} ),
			'created_at'              => $this->created_at?->toIso8601String(),
			'updated_at'              => $this->updated_at?->toIso8601String(),
		];
	}
}
