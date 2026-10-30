<?php

/**
 * OpenGraphDTO.
 *
 * Data Transfer Object for Open Graph meta tags.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\DTOs;

/**
 * OpenGraphDTO class.
 *
 * Represents Open Graph meta tags for social media sharing.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
readonly class OpenGraphDTO
{
	/**
	 * Create a new OpenGraphDTO instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  string       $title        The OG title.
	 * @param  string|null  $description  The OG description.
	 * @param  string|null  $image        The OG image URL.
	 * @param  string       $url          The canonical URL.
	 * @param  string       $type         The OG type (website, article, etc.).
	 * @param  string       $siteName     The site name.
	 * @param  string       $locale       The locale (e.g., en_US).
	 * @param  int|null     $imageWidth   The OG image width in pixels, when known.
	 * @param  int|null     $imageHeight  The OG image height in pixels, when known.
	 * @param  string|null  $imageAlt     The OG image alt text, when known.
	 * @param  string|null  $publishedTime  The article's published time, for the `article` type.
	 * @param  string|null  $modifiedTime   The article's modified time, for the `article` type.
	 */
	public function __construct(
		public string $title,
		public ?string $description,
		public ?string $image,
		public string $url,
		public string $type = 'website',
		public string $siteName = '',
		public string $locale = 'en_US',
		public ?int $imageWidth = null,
		public ?int $imageHeight = null,
		public ?string $imageAlt = null,
		public ?string $publishedTime = null,
		public ?string $modifiedTime = null,
	) {
	}

	/**
	 * Convert the DTO to an array suitable for meta tag generation.
	 *
	 * @since 1.0.0
	 * @since 1.9.0 Includes image details and article times.
	 *
	 * @return array<string, int|string|null>
	 */
	public function toArray(): array
	{
		return [
			'og:title'               => $this->title,
			'og:description'         => $this->description,
			'og:image'               => $this->image,
			'og:image:width'         => $this->imageWidth,
			'og:image:height'        => $this->imageHeight,
			'og:image:alt'           => $this->imageAlt,
			'og:url'                 => $this->url,
			'og:type'                => $this->type,
			'og:site_name'           => $this->siteName,
			'og:locale'              => $this->locale,
			'article:published_time' => $this->publishedTime,
			'article:modified_time'  => $this->modifiedTime,
		];
	}

	/**
	 * Convert the DTO to an array with non-null values only.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, int|string>
	 */
	public function toArrayFiltered(): array
	{
		return array_filter( $this->toArray(), fn ( $value ) => null !== $value && '' !== $value );
	}

	/**
	 * Create a DTO from an array.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>  $data  The data array.
	 *
	 * @return self
	 */
	public static function fromArray( array $data ): self
	{
		return new self(
			title: $data['og:title'] ?? $data['title'] ?? '',
			description: $data['og:description'] ?? $data['description'] ?? null,
			image: $data['og:image'] ?? $data['image'] ?? null,
			url: $data['og:url'] ?? $data['url'] ?? '',
			type: $data['og:type'] ?? $data['type'] ?? 'website',
			siteName: $data['og:site_name'] ?? $data['site_name'] ?? '',
			locale: $data['og:locale'] ?? $data['locale'] ?? 'en_US',
			imageWidth: $data['og:image:width'] ?? $data['image_width'] ?? null,
			imageHeight: $data['og:image:height'] ?? $data['image_height'] ?? null,
			imageAlt: $data['og:image:alt'] ?? $data['image_alt'] ?? null,
			publishedTime: $data['article:published_time'] ?? $data['published_time'] ?? null,
			modifiedTime: $data['article:modified_time'] ?? $data['modified_time'] ?? null,
		);
	}
}
