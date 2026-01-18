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
 * @copyright  2026 Jacob Martella
 * @license    MIT
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
	 */
	public function __construct(
		public string $title,
		public ?string $description,
		public ?string $image,
		public string $url,
		public string $type = 'website',
		public string $siteName = '',
		public string $locale = 'en_US',
	) {
	}

	/**
	 * Convert the DTO to an array suitable for meta tag generation.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string|null>
	 */
	public function toArray(): array
	{
		return [
			'og:title'       => $this->title,
			'og:description' => $this->description,
			'og:image'       => $this->image,
			'og:url'         => $this->url,
			'og:type'        => $this->type,
			'og:site_name'   => $this->siteName,
			'og:locale'      => $this->locale,
		];
	}

	/**
	 * Convert the DTO to an array with non-null values only.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
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
		);
	}
}
