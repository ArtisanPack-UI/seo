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
	 * Get a human-readable description of this schema type.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return __( 'A review of a product, service, or other item' );
	}

	/**
	 * Get the field definitions for this schema type.
	 *
	 * @since 1.1.0
	 *
	 * @return array<int, array{name: string, type: string, label: string, required: bool, description: string}>
	 */
	public function getFieldDefinitions(): array
	{
		return [
			[ 'name' => 'name', 'type' => 'text', 'label' => __( 'Review Title' ), 'required' => false, 'description' => __( 'The title of the review' ) ],
			[ 'name' => 'reviewBody', 'type' => 'textarea', 'label' => __( 'Review Body' ), 'required' => true, 'description' => __( 'The full text of the review' ) ],
			[ 'name' => 'author', 'type' => 'person', 'label' => __( 'Author' ), 'required' => true, 'description' => __( 'The author of the review' ) ],
			[ 'name' => 'rating', 'type' => 'number', 'label' => __( 'Rating' ), 'required' => false, 'description' => __( 'The rating given in the review' ) ],
			[ 'name' => 'datePublished', 'type' => 'datetime', 'label' => __( 'Date Published' ), 'required' => false, 'description' => __( 'The date the review was published' ) ],
			[ 'name' => 'itemReviewed', 'type' => 'thing', 'label' => __( 'Item Reviewed' ), 'required' => false, 'description' => __( 'The item being reviewed' ) ],
			[ 'name' => 'publisher', 'type' => 'organization', 'label' => __( 'Publisher' ), 'required' => false, 'description' => __( 'The publisher of the review' ) ],
		];
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
