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
use ArtisanPackUI\SEO\Support\PublicUrlResolver;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
		$title   = $this->resolveOgTitle( $model, $seoMeta );
		$image   = $this->resolveOgImage( $model, $seoMeta );
		$type    = $this->resolveOgType( $model, $seoMeta );
		$details = $this->resolveImageDetails( $image, $seoMeta?->og_image_id, $title );
		$times   = 'article' === $type ? $this->resolveArticleTimes( $model ) : [];

		return new OpenGraphDTO(
			title: $title,
			description: $this->resolveOgDescription( $model, $seoMeta ),
			image: $image,
			url: $this->resolveCanonical( $model, $seoMeta ),
			type: $type,
			siteName: $seoMeta?->og_site_name ?? config( 'seo.open_graph.site_name', config( 'app.name', '' ) ),
			locale: $seoMeta?->og_locale ?? config( 'seo.open_graph.locale', 'en_US' ),
			imageWidth: $details['width'],
			imageHeight: $details['height'],
			imageAlt: $details['alt'],
			publishedTime: $times['published'] ?? null,
			modifiedTime: $times['modified'] ?? null,
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
		$title = $this->resolveTwitterTitle( $model, $seoMeta );
		$image = $this->resolveTwitterImage( $model, $seoMeta );

		return new TwitterCardDTO(
			card: $seoMeta?->twitter_card ?? config( 'seo.twitter.card_type', 'summary_large_image' ),
			title: $title,
			description: $this->resolveTwitterDescription( $model, $seoMeta ),
			image: $image,
			site: $seoMeta?->twitter_site ?? config( 'seo.twitter.site' ),
			creator: $seoMeta?->twitter_creator ?? config( 'seo.twitter.creator' ),
			imageAlt: $this->resolveTwitterImageAlt( $image, $seoMeta, $title ),
		);
	}

	/**
	 * Resolve the known details of a social image.
	 *
	 * Details come from the media library, the configured default image, or
	 * the OG image generator, whose cards show the title as their text. An
	 * image from anywhere else has no known details.
	 *
	 * @since 1.9.0
	 *
	 * @param  string|null  $image    The image URL.
	 * @param  int|null     $mediaId  The media library ID behind the image, if any.
	 * @param  string|null  $title    The page title, used as a generated card's alt text.
	 *
	 * @return array{width: int|null, height: int|null, alt: string|null}
	 */
	public function resolveImageDetails( ?string $image, ?int $mediaId = null, ?string $title = null ): array
	{
		if ( null === $image || '' === $image ) {
			return [ 'width' => null, 'height' => null, 'alt' => null ];
		}

		$integration = app( MediaLibraryIntegration::class );

		if ( null !== $mediaId && $image === $integration->getSocialImageUrl( $mediaId ) ) {
			return $integration->getSocialImageDetails( $mediaId, $image );
		}

		if ( $image === config( 'seo.open_graph.default_image' ) ) {
			$width  = config( 'seo.open_graph.default_image_width' );
			$height = config( 'seo.open_graph.default_image_height' );

			return [
				'width'  => null === $width ? null : (int) $width,
				'height' => null === $height ? null : (int) $height,
				'alt'    => config( 'seo.open_graph.default_image_alt' ),
			];
		}

		$dimensions = app( OgImageService::class )->dimensionsForUrl( $image );

		return [
			'width'  => $dimensions['width'] ?? null,
			'height' => $dimensions['height'] ?? null,
			'alt'    => null === $dimensions ? null : $title,
		];
	}

	/**
	 * Resolve a model's article times as ISO 8601 strings.
	 *
	 * @since 1.9.0
	 *
	 * @param  Model  $model  The model.
	 *
	 * @return array{published?: string, modified?: string}
	 */
	public function resolveArticleTimes( Model $model ): array
	{
		$times = [];

		if ( ( $model->published_at ?? null ) instanceof DateTimeInterface ) {
			$times['published'] = Carbon::instance( $model->published_at )->toIso8601String();
		}

		if ( ( $model->updated_at ?? null ) instanceof DateTimeInterface ) {
			$times['modified'] = Carbon::instance( $model->updated_at )->toIso8601String();
		}

		return $times;
	}

	/**
	 * Resolve the Open Graph type.
	 *
	 * A saved type wins. Otherwise the type is inferred from the model.
	 *
	 * @since 1.9.0
	 *
	 * @param  Model         $model    The model.
	 * @param  SeoMeta|null  $seoMeta  The SeoMeta instance.
	 *
	 * @return string
	 */
	public function resolveOgType( Model $model, ?SeoMeta $seoMeta = null ): string
	{
		$type = $seoMeta?->og_type;

		if ( null !== $type && '' !== $type ) {
			return $type;
		}

		return $this->inferOgType( $model );
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
	 * A saved description is left at its full length. Text taken from the
	 * model's excerpt or description is shortened to 200 characters.
	 *
	 * @since 1.0.0
	 * @since 1.9.0 Saved descriptions are no longer shortened.
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

		if ( null !== $description && '' !== $description ) {
			return strip_tags( $description );
		}

		$description = $model->excerpt ?? null;

		if ( null === $description || '' === $description ) {
			$description = $model->description ?? null;
		}

		if ( null !== $description && '' !== $description ) {
			// OG descriptions can be slightly longer than meta descriptions
			return seoDescription( strip_tags( $description ), 200 );
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
	 * Resolve the Twitter image alt text.
	 *
	 * @since 1.9.0
	 *
	 * @param  string|null   $image    The resolved Twitter image URL.
	 * @param  SeoMeta|null  $seoMeta  The SeoMeta instance.
	 * @param  string        $title    The Twitter title.
	 *
	 * @return string|null
	 */
	protected function resolveTwitterImageAlt( ?string $image, ?SeoMeta $seoMeta, string $title ): ?string
	{
		if ( null !== $seoMeta?->twitter_image_id && '' === (string) $seoMeta->twitter_image ) {
			return app( MediaLibraryIntegration::class )->getSocialImageDetails( $seoMeta->twitter_image_id )['alt'];
		}

		return $this->resolveImageDetails( $image, $seoMeta?->og_image_id, $title )['alt'];
	}

	/**
	 * Resolve the canonical URL.
	 *
	 * @since 1.0.0
	 * @since 1.9.0 No longer guesses the URL from the model's slug.
	 *
	 * @param  Model          $model    The model.
	 * @param  SeoMeta|null   $seoMeta  The SeoMeta instance.
	 *
	 * @return string
	 */
	protected function resolveCanonical( Model $model, ?SeoMeta $seoMeta ): string
	{
		return PublicUrlResolver::resolve( $model, $seoMeta );
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
