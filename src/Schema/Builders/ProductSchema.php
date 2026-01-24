<?php

/**
 * ProductSchema.
 *
 * Schema.org Product type builder.
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
 * ProductSchema class.
 *
 * Generates Schema.org Product structured data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class ProductSchema extends AbstractSchema
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
		return 'Product';
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

		// Name (required)
		$schema['name'] = $this->get( 'name', '' );

		// Description
		$description = $this->get( 'description' );
		if ( null !== $description && '' !== $description ) {
			$schema['description'] = $description;
		}

		// Image (required for rich results)
		$image = $this->get( 'image' );
		if ( null !== $image ) {
			$schema['image'] = $this->buildImageObject( $image );
		}

		// URL
		$url = $this->get( 'url' );
		if ( null !== $url ) {
			$schema['url'] = $url;
		}

		// SKU
		$sku = $this->get( 'sku' );
		if ( null !== $sku ) {
			$schema['sku'] = $sku;
		}

		// GTIN/EAN/UPC
		$gtin = $this->get( 'gtin' ) ?? $this->get( 'ean' ) ?? $this->get( 'upc' );
		if ( null !== $gtin ) {
			$schema['gtin'] = $gtin;
		}

		// MPN
		$mpn = $this->get( 'mpn' );
		if ( null !== $mpn ) {
			$schema['mpn'] = $mpn;
		}

		// Brand
		$brand = $this->get( 'brand' );
		if ( null !== $brand ) {
			$schema['brand'] = $this->buildBrand( $brand );
		}

		// Offers (pricing)
		$offers = $this->get( 'offers' );
		if ( null !== $offers && is_array( $offers ) ) {
			$schema['offers'] = $this->buildOffers( $offers );
		}

		// Aggregate rating
		$aggregateRating = $this->get( 'aggregateRating' );
		if ( null !== $aggregateRating && is_array( $aggregateRating ) ) {
			$schema['aggregateRating'] = $this->buildAggregateRating( $aggregateRating );
		}

		// Reviews
		$reviews = $this->get( 'reviews' );
		if ( null !== $reviews && is_array( $reviews ) ) {
			$schema['review'] = $this->buildReviews( $reviews );
		}

		// Category
		$category = $this->get( 'category' );
		if ( null !== $category ) {
			$schema['category'] = $category;
		}

		// Color
		$color = $this->get( 'color' );
		if ( null !== $color ) {
			$schema['color'] = $color;
		}

		// Material
		$material = $this->get( 'material' );
		if ( null !== $material ) {
			$schema['material'] = $material;
		}

		return $this->filterEmpty( $schema );
	}

	/**
	 * Build a Brand schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, string>|string  $brand  The brand data.
	 *
	 * @return array<string, string>
	 */
	protected function buildBrand( string|array $brand ): array
	{
		if ( is_string( $brand ) ) {
			return [
				'@type' => 'Brand',
				'name'  => $brand,
			];
		}

		return $this->filterEmpty( [
			'@type' => 'Brand',
			'name'  => $brand['name'] ?? '',
			'url'   => $brand['url'] ?? null,
		] );
	}

	/**
	 * Build Offer schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>  $offers  The offers data.
	 *
	 * @return array<string, mixed>
	 */
	protected function buildOffers( array $offers ): array
	{
		$offer = [
			'@type'         => 'Offer',
			'priceCurrency' => $offers['currency'] ?? 'USD',
			'price'         => $offers['price'] ?? null,
		];

		if ( isset( $offers['availability'] ) ) {
			$offer['availability'] = $this->mapAvailability( $offers['availability'] );
		}

		if ( isset( $offers['url'] ) ) {
			$offer['url'] = $offers['url'];
		}

		if ( isset( $offers['validFrom'] ) ) {
			$offer['priceValidFrom'] = $offers['validFrom'];
		}

		if ( isset( $offers['validUntil'] ) || isset( $offers['priceValidUntil'] ) ) {
			$offer['priceValidUntil'] = $offers['validUntil'] ?? $offers['priceValidUntil'];
		}

		if ( isset( $offers['seller'] ) && is_array( $offers['seller'] ) ) {
			$offer['seller'] = $this->buildOrganization( $offers['seller'] );
		}

		if ( isset( $offers['itemCondition'] ) ) {
			$offer['itemCondition'] = $this->mapItemCondition( $offers['itemCondition'] );
		}

		return $this->filterEmpty( $offer );
	}

	/**
	 * Build AggregateRating schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>  $rating  The rating data.
	 *
	 * @return array<string, mixed>
	 */
	protected function buildAggregateRating( array $rating ): array
	{
		return $this->filterEmpty( [
			'@type'       => 'AggregateRating',
			'ratingValue' => $rating['value'] ?? $rating['ratingValue'] ?? null,
			'bestRating'  => $rating['bestRating'] ?? 5,
			'worstRating' => $rating['worstRating'] ?? 1,
			'ratingCount' => $rating['count'] ?? $rating['ratingCount'] ?? null,
			'reviewCount' => $rating['reviewCount'] ?? null,
		] );
	}

	/**
	 * Build Review schema array.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<int, array<string, mixed>>  $reviews  The reviews data.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function buildReviews( array $reviews ): array
	{
		$result = [];

		foreach ( $reviews as $review ) {
			$reviewSchema = [
				'@type' => 'Review',
			];

			if ( isset( $review['author'] ) ) {
				$reviewSchema['author'] = is_array( $review['author'] )
					? $this->buildPerson( $review['author'] )
					: [ '@type' => 'Person', 'name' => $review['author'] ];
			}

			if ( isset( $review['rating'] ) ) {
				$reviewSchema['reviewRating'] = [
					'@type'       => 'Rating',
					'ratingValue' => $review['rating'],
					'bestRating'  => $review['bestRating'] ?? 5,
				];
			}

			if ( isset( $review['body'] ) ) {
				$reviewSchema['reviewBody'] = $review['body'];
			}

			if ( isset( $review['datePublished'] ) ) {
				$reviewSchema['datePublished'] = $review['datePublished'];
			}

			$result[] = $this->filterEmpty( $reviewSchema );
		}

		return $result;
	}

	/**
	 * Map availability string to Schema.org URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $availability  The availability status.
	 *
	 * @return string
	 */
	protected function mapAvailability( string $availability ): string
	{
		return match ( strtolower( $availability ) ) {
			'instock', 'in_stock', 'in stock'          => 'https://schema.org/InStock',
			'outofstock', 'out_of_stock', 'out of stock' => 'https://schema.org/OutOfStock',
			'preorder', 'pre_order', 'pre order'       => 'https://schema.org/PreOrder',
			'backorder', 'back_order', 'back order'    => 'https://schema.org/BackOrder',
			'discontinued'                              => 'https://schema.org/Discontinued',
			default                                     => $availability,
		};
	}

	/**
	 * Map item condition string to Schema.org URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $condition  The item condition.
	 *
	 * @return string
	 */
	protected function mapItemCondition( string $condition ): string
	{
		return match ( strtolower( $condition ) ) {
			'new'         => 'https://schema.org/NewCondition',
			'used'        => 'https://schema.org/UsedCondition',
			'refurbished' => 'https://schema.org/RefurbishedCondition',
			'damaged'     => 'https://schema.org/DamagedCondition',
			default       => $condition,
		};
	}
}
