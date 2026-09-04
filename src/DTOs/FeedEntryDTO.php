<?php

/**
 * FeedEntryDTO.
 *
 * Data Transfer Object for a single RSS/Atom feed entry.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\DTOs;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;

/**
 * FeedEntryDTO class.
 *
 * Represents a single item/entry in a feed, framework-agnostic so that
 * consumers can hydrate it from any source (blog archive, per-type
 * collection, external data, etc.).
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
readonly class FeedEntryDTO
{
	/**
	 * Create a new FeedEntryDTO instance.
	 *
	 * @since 1.4.0
	 *
	 * @param  string                  $title        Entry title.
	 * @param  string                  $link         Absolute URL to the entry.
	 * @param  string                  $summary      Description or summary body.
	 * @param  DateTimeInterface|null  $publishedAt  Original publication date.
	 * @param  DateTimeInterface|null  $updatedAt    Last updated date (Atom only; falls back to publishedAt).
	 * @param  string|null             $author       Author name.
	 * @param  string|null             $authorEmail  Author email (RSS-only; Atom uses name).
	 * @param  string|null             $guid         Stable identifier. Defaults to link when omitted.
	 * @param  array<int, string>      $categories   Optional category/tag labels.
	 */
	public function __construct(
		public string $title,
		public string $link,
		public string $summary,
		public ?DateTimeInterface $publishedAt = null,
		public ?DateTimeInterface $updatedAt = null,
		public ?string $author = null,
		public ?string $authorEmail = null,
		public ?string $guid = null,
		public array $categories = [],
	) {
	}

	/**
	 * Build a FeedEntryDTO from an array.
	 *
	 * Accepts flexible keys so consumers can pass provider payloads directly.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<string, mixed>  $data  Source array.
	 *
	 * @return self
	 */
	public static function fromArray( array $data ): self
	{
		return new self(
			title: (string) ( $data['title'] ?? '' ),
			link: (string) ( $data['link'] ?? $data['url'] ?? '' ),
			summary: (string) ( $data['summary'] ?? $data['description'] ?? '' ),
			publishedAt: self::normalizeDate( $data['published_at'] ?? $data['pubDate'] ?? $data['published'] ?? null ),
			updatedAt: self::normalizeDate( $data['updated_at'] ?? $data['updated'] ?? null ),
			author: isset( $data['author'] ) ? (string) $data['author'] : null,
			authorEmail: isset( $data['author_email'] ) ? (string) $data['author_email'] : null,
			guid: isset( $data['guid'] ) ? (string) $data['guid'] : ( isset( $data['id'] ) ? (string) $data['id'] : null ),
			categories: array_values( array_map( 'strval', (array) ( $data['categories'] ?? [] ) ) ),
		);
	}

	/**
	 * Normalize an arbitrary date value to a DateTimeInterface.
	 *
	 * @since 1.4.0
	 *
	 * @param  mixed  $value  Raw date input.
	 *
	 * @return DateTimeInterface|null
	 */
	protected static function normalizeDate( mixed $value ): ?DateTimeInterface
	{
		if ( null === $value ) {
			return null;
		}

		if ( $value instanceof DateTimeInterface ) {
			return $value;
		}

		if ( is_string( $value ) && '' !== $value ) {
			try {
				return new DateTimeImmutable( $value );
			} catch ( Exception $e ) {
				return null;
			}
		}

		return null;
	}
}
