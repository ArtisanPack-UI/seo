<?php

/**
 * ServiceSchema.
 *
 * Schema.org Service type builder.
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
 * ServiceSchema class.
 *
 * Generates Schema.org Service structured data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class ServiceSchema extends AbstractSchema
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
		return 'Service';
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
		return __( 'A service provided by an organization or individual' );
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
			[ 'name' => 'name', 'type' => 'text', 'label' => __( 'Service Name' ), 'required' => true, 'description' => __( 'The name of the service' ) ],
			[ 'name' => 'description', 'type' => 'textarea', 'label' => __( 'Description' ), 'required' => false, 'description' => __( 'A description of the service' ) ],
			[ 'name' => 'url', 'type' => 'url', 'label' => __( 'URL' ), 'required' => false, 'description' => __( 'URL of the service page' ) ],
			[ 'name' => 'image', 'type' => 'image', 'label' => __( 'Image' ), 'required' => false, 'description' => __( 'URL of the service image' ) ],
			[ 'name' => 'provider', 'type' => 'organization', 'label' => __( 'Provider' ), 'required' => false, 'description' => __( 'The organization providing the service' ) ],
			[ 'name' => 'areaServed', 'type' => 'text', 'label' => __( 'Area Served' ), 'required' => false, 'description' => __( 'The geographic area where the service is available' ) ],
			[ 'name' => 'serviceType', 'type' => 'text', 'label' => __( 'Service Type' ), 'required' => false, 'description' => __( 'The type of service' ) ],
			[ 'name' => 'category', 'type' => 'text', 'label' => __( 'Category' ), 'required' => false, 'description' => __( 'The category of the service' ) ],
			[ 'name' => 'offers', 'type' => 'offer', 'label' => __( 'Offers' ), 'required' => false, 'description' => __( 'Pricing information for the service' ) ],
			[ 'name' => 'aggregateRating', 'type' => 'aggregate_rating', 'label' => __( 'Aggregate Rating' ), 'required' => false, 'description' => __( 'Average rating and review count' ) ],
			[ 'name' => 'brand', 'type' => 'text', 'label' => __( 'Brand' ), 'required' => false, 'description' => __( 'The brand associated with the service' ) ],
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

		// Name (required)
		$schema['name'] = $this->get( 'name', '' );

		// Description
		$description = $this->get( 'description' );
		if ( null !== $description && '' !== $description ) {
			$schema['description'] = $description;
		}

		// URL
		$url = $this->get( 'url' );
		if ( null !== $url ) {
			$schema['url'] = $url;
		}

		// Image
		$image = $this->get( 'image' );
		if ( null !== $image ) {
			$schema['image'] = $this->buildImageObject( $image );
		}

		// Provider (the organization providing the service)
		$provider = $this->get( 'provider' );
		if ( null !== $provider && is_array( $provider ) ) {
			$schema['provider'] = $this->buildOrganization( $provider );
		} else {
			// Default to site organization
			$schema['provider'] = $this->filterEmpty( [
				'@type' => 'Organization',
				'name'  => config( 'seo.schema.organization.name', config( 'app.name', '' ) ),
				'url'   => config( 'seo.schema.organization.url', config( 'app.url', '' ) ),
			] );
		}

		// Area served
		$areaServed = $this->get( 'areaServed' );
		if ( null !== $areaServed ) {
			$schema['areaServed'] = $this->buildAreaServed( $areaServed );
		}

		// Service type
		$serviceType = $this->get( 'serviceType' );
		if ( null !== $serviceType ) {
			$schema['serviceType'] = $serviceType;
		}

		// Category
		$category = $this->get( 'category' );
		if ( null !== $category ) {
			$schema['category'] = $category;
		}

		// Offers (pricing)
		$offers = $this->get( 'offers' );
		if ( null !== $offers && is_array( $offers ) ) {
			$schema['offers'] = $this->buildOffers( $offers );
		}

		// Aggregate rating
		$aggregateRating = $this->get( 'aggregateRating' );
		if ( null !== $aggregateRating && is_array( $aggregateRating ) ) {
			$schema['aggregateRating'] = $this->filterEmpty( [
				'@type'       => 'AggregateRating',
				'ratingValue' => $aggregateRating['value'] ?? $aggregateRating['ratingValue'] ?? null,
				'bestRating'  => $aggregateRating['bestRating'] ?? 5,
				'worstRating' => $aggregateRating['worstRating'] ?? 1,
				'ratingCount' => $aggregateRating['count'] ?? $aggregateRating['ratingCount'] ?? null,
				'reviewCount' => $aggregateRating['reviewCount'] ?? null,
			] );
		}

		// Brand
		$brand = $this->get( 'brand' );
		if ( null !== $brand ) {
			$schema['brand'] = is_string( $brand )
				? [ '@type' => 'Brand', 'name' => $brand ]
				: [ '@type' => 'Brand', 'name' => $brand['name'] ?? '' ];
		}

		return $this->filterEmpty( $schema );
	}

	/**
	 * Build area served schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, string>|string  $area  The area served data.
	 *
	 * @return array<string, string>|string
	 */
	protected function buildAreaServed( string|array $area ): array|string
	{
		if ( is_string( $area ) ) {
			return $area;
		}

		if ( isset( $area['type'] ) && 'GeoCircle' === $area['type'] ) {
			return $this->filterEmpty( [
				'@type'         => 'GeoCircle',
				'geoMidpoint'   => $this->filterEmpty( [
					'@type'     => 'GeoCoordinates',
					'latitude'  => $area['latitude'] ?? null,
					'longitude' => $area['longitude'] ?? null,
				] ),
				'geoRadius'     => $area['radius'] ?? null,
			] );
		}

		return $this->filterEmpty( [
			'@type' => 'Place',
			'name'  => $area['name'] ?? null,
		] );
	}

	/**
	 * Build Offer schema for service.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>  $offers  The offers data.
	 *
	 * @return array<string, mixed>
	 */
	protected function buildOffers( array $offers ): array
	{
		return $this->filterEmpty( [
			'@type'         => 'Offer',
			'priceCurrency' => $offers['currency'] ?? 'USD',
			'price'         => $offers['price'] ?? null,
			'priceRange'    => $offers['priceRange'] ?? null,
			'url'           => $offers['url'] ?? null,
		] );
	}
}
