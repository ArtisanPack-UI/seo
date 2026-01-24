<?php

/**
 * SocialMetaService.
 *
 * Service for generating social media meta tags.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\DTOs\OpenGraphDTO;
use ArtisanPackUI\SEO\DTOs\TwitterCardDTO;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * SocialMetaService class.
 *
 * Generates Open Graph and Twitter Card meta tags for social media sharing.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SocialMetaService
{
	/**
	 * Generate Open Graph meta tags.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model to generate tags for.
	 * @param  SeoMeta|null   $seoMeta  Optional SeoMeta instance.
	 *
	 * @return OpenGraphDTO
	 */
	public function generateOpenGraph( Model $model, ?SeoMeta $seoMeta = null ): OpenGraphDTO
	{
		return new OpenGraphDTO(
			title: $this->resolveOgTitle( $model, $seoMeta ),
			description: $this->resolveOgDescription( $model, $seoMeta ),
			image: $this->resolveOgImage( $model, $seoMeta ),
			url: $this->resolveCanonical( $model, $seoMeta ),
			type: $seoMeta?->og_type ?? $this->inferOgType( $model ),
			siteName: $seoMeta?->og_site_name ?? config( 'seo.open_graph.site_name', config( 'app.name', '' ) ),
			locale: $seoMeta?->og_locale ?? config( 'seo.open_graph.locale', 'en_US' ),
		);
	}

	/**
	 * Generate Twitter Card meta tags.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model to generate tags for.
	 * @param  SeoMeta|null   $seoMeta  Optional SeoMeta instance.
	 *
	 * @return TwitterCardDTO
	 */
	public function generateTwitterCard( Model $model, ?SeoMeta $seoMeta = null ): TwitterCardDTO
	{
		return new TwitterCardDTO(
			card: $seoMeta?->twitter_card ?? config( 'seo.twitter.card_type', 'summary_large_image' ),
			title: $this->resolveTwitterTitle( $model, $seoMeta ),
			description: $this->resolveTwitterDescription( $model, $seoMeta ),
			image: $this->resolveTwitterImage( $model, $seoMeta ),
			site: $seoMeta?->twitter_site ?? config( 'seo.twitter.site' ),
			creator: $seoMeta?->twitter_creator ?? config( 'seo.twitter.creator' ),
		);
	}

	/**
	 * Resolve the Open Graph title.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model.
	 * @param  SeoMeta|null   $seoMeta  The SeoMeta instance.
	 *
	 * @return string
	 */
	protected function resolveOgTitle( Model $model, ?SeoMeta $seoMeta ): string
	{
		$title = $seoMeta?->og_title;

		if ( null === $title || '' === $title ) {
			$title = $seoMeta?->meta_title ?? null;
		}

		if ( null === $title || '' === $title ) {
			$title = $model->title ?? null;
		}

		if ( null === $title || '' === $title ) {
			$title = $model->name ?? null;
		}

		if ( null === $title || '' === $title ) {
			$title = config( 'app.name', '' );
		}

		return $title;
	}

	/**
	 * Resolve the Open Graph description.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model.
	 * @param  SeoMeta|null   $seoMeta  The SeoMeta instance.
	 *
	 * @return string|null
	 */
	protected function resolveOgDescription( Model $model, ?SeoMeta $seoMeta ): ?string
	{
		$description = $seoMeta?->og_description;

		if ( null === $description || '' === $description ) {
			$description = $seoMeta?->meta_description ?? null;
		}

		if ( null === $description || '' === $description ) {
			$description = $model->excerpt ?? null;
		}

		if ( null === $description || '' === $description ) {
			$description = $model->description ?? null;
		}

		if ( null !== $description && '' !== $description ) {
			// OG descriptions can be slightly longer than meta descriptions
			return Str::limit( strip_tags( $description ), 200 );
		}

		return null;
	}

	/**
	 * Resolve the Open Graph image.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model.
	 * @param  SeoMeta|null   $seoMeta  The SeoMeta instance.
	 *
	 * @return string|null
	 */
	protected function resolveOgImage( Model $model, ?SeoMeta $seoMeta ): ?string
	{
		// Check SeoMeta first (with media library integration)
		$image = $seoMeta?->getEffectiveOgImage();

		if ( null !== $image ) {
			return $image;
		}

		// Try model's featured image method
		if ( method_exists( $model, 'getFeaturedImageUrl' ) ) {
			$url = $model->getFeaturedImageUrl();
			if ( null !== $url ) {
				return $url;
			}
		}

		// Try model's featured_image property
		if ( isset( $model->featured_image ) && null !== $model->featured_image ) {
			return $model->featured_image;
		}

		// Default OG image from config
		return config( 'seo.open_graph.default_image' );
	}

	/**
	 * Resolve the Twitter title.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model.
	 * @param  SeoMeta|null   $seoMeta  The SeoMeta instance.
	 *
	 * @return string
	 */
	protected function resolveTwitterTitle( Model $model, ?SeoMeta $seoMeta ): string
	{
		$title = $seoMeta?->twitter_title;

		if ( null === $title || '' === $title ) {
			$title = $this->resolveOgTitle( $model, $seoMeta );
		}

		return $title;
	}

	/**
	 * Resolve the Twitter description.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model.
	 * @param  SeoMeta|null   $seoMeta  The SeoMeta instance.
	 *
	 * @return string|null
	 */
	protected function resolveTwitterDescription( Model $model, ?SeoMeta $seoMeta ): ?string
	{
		$description = $seoMeta?->twitter_description;

		if ( null === $description || '' === $description ) {
			$description = $this->resolveOgDescription( $model, $seoMeta );
		}

		return $description;
	}

	/**
	 * Resolve the Twitter image.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model.
	 * @param  SeoMeta|null   $seoMeta  The SeoMeta instance.
	 *
	 * @return string|null
	 */
	protected function resolveTwitterImage( Model $model, ?SeoMeta $seoMeta ): ?string
	{
		// Check for Twitter-specific image first
		if ( null !== $seoMeta?->twitter_image && '' !== $seoMeta->twitter_image ) {
			return $seoMeta->twitter_image;
		}

		// Check for Twitter image ID (media library)
		if ( null !== $seoMeta?->twitter_image_id && class_exists( 'ArtisanPackUI\MediaLibrary\Models\Media' ) ) {
			$media = \ArtisanPackUI\MediaLibrary\Models\Media::find( $seoMeta->twitter_image_id );
			if ( null !== $media ) {
				return $media->url;
			}
		}

		// Fall back to OG image
		return $this->resolveOgImage( $model, $seoMeta ) ?? config( 'seo.twitter.default_image' );
	}

	/**
	 * Resolve the canonical URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model          $model    The model.
	 * @param  SeoMeta|null   $seoMeta  The SeoMeta instance.
	 *
	 * @return string
	 */
	protected function resolveCanonical( Model $model, ?SeoMeta $seoMeta ): string
	{
		if ( null !== $seoMeta?->canonical_url && '' !== $seoMeta->canonical_url ) {
			return $seoMeta->canonical_url;
		}

		if ( method_exists( $model, 'getUrl' ) ) {
			return $model->getUrl();
		}

		if ( isset( $model->slug ) && null !== $model->slug ) {
			return url( $model->slug );
		}

		// Avoid calling url()->current() outside HTTP context
		if ( app()->runningInConsole() || null === request() ) {
			return '';
		}

		return url()->current();
	}

	/**
	 * Infer the Open Graph type from the model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model.
	 *
	 * @return string
	 */
	protected function inferOgType( Model $model ): string
	{
		$class = class_basename( $model );

		return match ( strtolower( $class ) ) {
			'post', 'article', 'blogpost' => 'article',
			'product'                     => 'product',
			'event'                       => 'event',
			'profile', 'user'             => 'profile',
			default                       => config( 'seo.open_graph.type', 'website' ),
		};
	}
}
