<?php

/**
 * AggregateRatingSchema.
 *
 * Schema.org AggregateRating type builder.
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
 * AggregateRatingSchema class.
 *
 * Generates Schema.org AggregateRating structured data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class AggregateRatingSchema extends AbstractSchema
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
		return 'AggregateRating';
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
		return __( 'An aggregate rating based on multiple reviews or ratings' );
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
			[ 'name' => 'ratingValue', 'type' => 'number', 'label' => __( 'Rating Value' ), 'required' => true, 'description' => __( 'The average rating value' ) ],
			[ 'name' => 'bestRating', 'type' => 'number', 'label' => __( 'Best Rating' ), 'required' => false, 'description' => __( 'The highest possible rating (default: 5)' ) ],
			[ 'name' => 'worstRating', 'type' => 'number', 'label' => __( 'Worst Rating' ), 'required' => false, 'description' => __( 'The lowest possible rating (default: 1)' ) ],
			[ 'name' => 'ratingCount', 'type' => 'number', 'label' => __( 'Rating Count' ), 'required' => true, 'description' => __( 'The total number of ratings' ) ],
			[ 'name' => 'reviewCount', 'type' => 'number', 'label' => __( 'Review Count' ), 'required' => false, 'description' => __( 'The total number of written reviews' ) ],
			[ 'name' => 'itemReviewed', 'type' => 'thing', 'label' => __( 'Item Reviewed' ), 'required' => false, 'description' => __( 'The item being rated' ) ],
		];
	}

	/**
	 * Generate the schema data array.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|null  $model  Optional model to generate schema for.
	 *
	 * @return array<string, mixed>
	 */
	public function generate( ?Model $model = null ): array
	{
		$schema = $this->getBaseSchema();

		// Rating value (required)
		$ratingValue = $this->get( 'ratingValue' ) ?? $this->get( 'value' );
		if ( null !== $ratingValue ) {
			$schema['ratingValue'] = $ratingValue;
		}

		// Best rating
		$schema['bestRating'] = $this->get( 'bestRating', 5 );

		// Worst rating
		$schema['worstRating'] = $this->get( 'worstRating', 1 );

		// Rating count (required - number of ratings)
		$ratingCount = $this->get( 'ratingCount' ) ?? $this->get( 'count' );
		if ( null !== $ratingCount ) {
			$schema['ratingCount'] = $ratingCount;
		}

		// Review count (number of reviews with text)
		$reviewCount = $this->get( 'reviewCount' );
		if ( null !== $reviewCount ) {
			$schema['reviewCount'] = $reviewCount;
		}

		// Item reviewed
		$itemReviewed = $this->get( 'itemReviewed' );
		if ( null !== $itemReviewed && is_array( $itemReviewed ) ) {
			$schema['itemReviewed'] = $this->buildItemReviewed( $itemReviewed );
		}

		return $this->filterEmpty( $schema );
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
		] );
	}
}
