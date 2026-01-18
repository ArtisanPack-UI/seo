<?php
/**
 * ReviewSchema.
 *
 * Schema.org Review type builder.
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

namespace ArtisanPackUI\SEO\Schema\Builders;

use Illuminate\Database\Eloquent\Model;

/**
 * ReviewSchema class.
 *
 * Generates Schema.org Review structured data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class ReviewSchema extends AbstractSchema
{
	/**
	 * Get the Schema.org type name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getType(): string
	{
		return 'Review';
	}

	/**
	 * Generate the schema data array.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|null  $_model  Optional model to generate schema for (unused in this implementation).
	 *
	 * @return array<string, mixed>
	 */
	public function generate( ?Model $_model = null ): array
	{
		$schema = $this->getBaseSchema();

		// Name
		$name = $this->get( 'name' );
		if ( null !== $name && '' !== $name ) {
			$schema['name'] = $name;
		}

		// Review body (required)
		$reviewBody = $this->get( 'reviewBody' ) ?? $this->get( 'body' ) ?? $this->get( 'content' );
		if ( null !== $reviewBody && '' !== $reviewBody ) {
			$schema['reviewBody'] = $reviewBody;
		}

		// Author (required)
		$author = $this->get( 'author' );
		if ( null !== $author ) {
			$schema['author'] = $this->buildAuthor( $author );
		}

		// Review rating
		$rating = $this->get( 'rating' ) ?? $this->get( 'reviewRating' );
		if ( null !== $rating ) {
			$schema['reviewRating'] = $this->buildRating( $rating );
		}

		// Date published
		$datePublished = $this->get( 'datePublished' );
		if ( null !== $datePublished ) {
			$schema['datePublished'] = $datePublished;
		}

		// Item reviewed
		$itemReviewed = $this->get( 'itemReviewed' );
		if ( null !== $itemReviewed && is_array( $itemReviewed ) ) {
			$schema['itemReviewed'] = $this->buildItemReviewed( $itemReviewed );
		}

		// Publisher
		$publisher = $this->get( 'publisher' );
		if ( null !== $publisher && is_array( $publisher ) ) {
			$schema['publisher'] = $this->buildOrganization( $publisher );
		}

		return $this->filterEmpty( $schema );
	}

	/**
	 * Build author schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>|string  $author  The author data.
	 *
	 * @return array<string, mixed>
	 */
	protected function buildAuthor( string|array $author ): array
	{
		if ( is_string( $author ) ) {
			return [
				'@type' => 'Person',
				'name'  => $author,
			];
		}

		$type = $author['type'] ?? 'Person';

		return $this->filterEmpty( [
			'@type' => $type,
			'name'  => $author['name'] ?? '',
			'url'   => $author['url'] ?? null,
		] );
	}

	/**
	 * Build rating schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>|float  $rating  The rating data (numeric or array).
	 *
	 * @return array<string, mixed>
	 */
	protected function buildRating( float|array $rating ): array
	{
		if ( is_numeric( $rating ) ) {
			return [
				'@type'       => 'Rating',
				'ratingValue' => $rating,
				'bestRating'  => 5,
				'worstRating' => 1,
			];
		}

		return $this->filterEmpty( [
			'@type'       => 'Rating',
			'ratingValue' => $rating['value'] ?? $rating['ratingValue'] ?? null,
			'bestRating'  => $rating['bestRating'] ?? 5,
			'worstRating' => $rating['worstRating'] ?? 1,
		] );
	}

	/**
	 * Build item reviewed schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>  $item  The item data.
	 *
	 * @return array<string, mixed>
	 */
	protected function buildItemReviewed( array $item ): array
	{
		$type = $item['type'] ?? $item['@type'] ?? 'Thing';

		return $this->filterEmpty( [
			'@type' => $type,
			'name'  => $item['name'] ?? '',
			'url'   => $item['url'] ?? null,
			'image' => isset( $item['image'] ) ? $this->buildImageObject( $item['image'] ) : null,
		] );
	}
}
