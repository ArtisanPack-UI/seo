<?php

/**
 * MetaTagsDTO.
 *
 * Data Transfer Object for HTML meta tags.
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
 * MetaTagsDTO class.
 *
 * Represents the core HTML meta tags for SEO purposes.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
readonly class MetaTagsDTO
{
	/**
	 * Create a new MetaTagsDTO instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  string               $title           The page title.
	 * @param  string|null          $description     The meta description.
	 * @param  string               $canonical       The canonical URL.
	 * @param  string               $robots          The robots directive.
	 * @param  array<string, mixed> $additionalMeta  Additional meta tags.
	 */
	public function __construct(
		public string $title,
		public ?string $description,
		public string $canonical,
		public string $robots,
		public array $additionalMeta = [],
	) {
	}

	/**
	 * Convert the DTO to an array.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'title'           => $this->title,
			'description'     => $this->description,
			'canonical'       => $this->canonical,
			'robots'          => $this->robots,
			'additional_meta' => $this->additionalMeta,
		];
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
			title: $data['title'] ?? '',
			description: $data['description'] ?? null,
			canonical: $data['canonical'] ?? '',
			robots: $data['robots'] ?? 'index, follow',
			additionalMeta: $data['additional_meta'] ?? [],
		);
	}
}
