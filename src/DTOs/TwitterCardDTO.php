<?php

/**
 * TwitterCardDTO.
 *
 * Data Transfer Object for Twitter Card meta tags.
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
 * TwitterCardDTO class.
 *
 * Represents Twitter Card meta tags for Twitter sharing.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
readonly class TwitterCardDTO
{
	/**
	 * Create a new TwitterCardDTO instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  string       $card         The card type (summary, summary_large_image, etc.).
	 * @param  string       $title        The Twitter title.
	 * @param  string|null  $description  The Twitter description.
	 * @param  string|null  $image        The Twitter image URL.
	 * @param  string|null  $site         The site's Twitter handle (@username).
	 * @param  string|null  $creator      The creator's Twitter handle (@username).
	 */
	public function __construct(
		public string $card = 'summary_large_image',
		public string $title = '',
		public ?string $description = null,
		public ?string $image = null,
		public ?string $site = null,
		public ?string $creator = null,
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
			'twitter:card'        => $this->card,
			'twitter:title'       => $this->title,
			'twitter:description' => $this->description,
			'twitter:image'       => $this->image,
			'twitter:site'        => $this->site,
			'twitter:creator'     => $this->creator,
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
			card: $data['twitter:card'] ?? $data['card'] ?? 'summary_large_image',
			title: $data['twitter:title'] ?? $data['title'] ?? '',
			description: $data['twitter:description'] ?? $data['description'] ?? null,
			image: $data['twitter:image'] ?? $data['image'] ?? null,
			site: $data['twitter:site'] ?? $data['site'] ?? null,
			creator: $data['twitter:creator'] ?? $data['creator'] ?? null,
		);
	}
}
