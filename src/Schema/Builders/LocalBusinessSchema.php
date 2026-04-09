<?php

/**
 * LocalBusinessSchema.
 *
 * Schema.org LocalBusiness type builder.
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
 * LocalBusinessSchema class.
 *
 * Generates Schema.org LocalBusiness structured data.
 * Extends Organization with business-specific properties.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class LocalBusinessSchema extends OrganizationSchema
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
		return 'LocalBusiness';
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
		return __( 'A local business or physical store with location-specific details' );
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
		return array_merge( parent::getFieldDefinitions(), [
			[ 'name' => 'priceRange', 'type' => 'text', 'label' => __( 'Price Range' ), 'required' => false, 'description' => __( 'The price range of the business (e.g. "$$")' ) ],
			[ 'name' => 'openingHours', 'type' => 'opening_hours', 'label' => __( 'Opening Hours' ), 'required' => false, 'description' => __( 'Business opening hours' ) ],
			[ 'name' => 'geo', 'type' => 'geo', 'label' => __( 'Geo Coordinates' ), 'required' => false, 'description' => __( 'Latitude and longitude of the business' ) ],
			[ 'name' => 'areaServed', 'type' => 'text', 'label' => __( 'Area Served' ), 'required' => false, 'description' => __( 'The geographic area served by the business' ) ],
			[ 'name' => 'paymentAccepted', 'type' => 'text', 'label' => __( 'Payment Accepted' ), 'required' => false, 'description' => __( 'Payment methods accepted' ) ],
			[ 'name' => 'currenciesAccepted', 'type' => 'text', 'label' => __( 'Currencies Accepted' ), 'required' => false, 'description' => __( 'Currencies accepted for payment' ) ],
		] );
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
		$schema = parent::generate( $model );

		// Override the type
		$schema['@type'] = $this->getType();

		// Price range
		$priceRange = $this->get( 'priceRange' );
		if ( null !== $priceRange ) {
			$schema['priceRange'] = $priceRange;
		}

		// Opening hours
		$openingHours = $this->get( 'openingHours' );
		if ( null !== $openingHours && is_array( $openingHours ) ) {
			$schema['openingHoursSpecification'] = $this->buildOpeningHours( $openingHours );
		}

		// Geo coordinates
		$geo = $this->get( 'geo' );
		if ( null !== $geo && is_array( $geo ) ) {
			$schema['geo'] = $this->buildGeoCoordinates( $geo );
		}

		// Area served
		$areaServed = $this->get( 'areaServed' );
		if ( null !== $areaServed ) {
			$schema['areaServed'] = $areaServed;
		}

		// Payment accepted
		$paymentAccepted = $this->get( 'paymentAccepted' );
		if ( null !== $paymentAccepted ) {
			$schema['paymentAccepted'] = $paymentAccepted;
		}

		// Currencies accepted
		$currenciesAccepted = $this->get( 'currenciesAccepted' );
		if ( null !== $currenciesAccepted ) {
			$schema['currenciesAccepted'] = $currenciesAccepted;
		}

		return $this->filterEmpty( $schema );
	}

	/**
	 * Build OpeningHoursSpecification schema array.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<int, array<string, mixed>>  $hours  The opening hours data.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function buildOpeningHours( array $hours ): array
	{
		$specs = [];

		foreach ( $hours as $spec ) {
			$specification = [
				'@type' => 'OpeningHoursSpecification',
			];

			if ( isset( $spec['dayOfWeek'] ) ) {
				$specification['dayOfWeek'] = $spec['dayOfWeek'];
			}

			if ( isset( $spec['opens'] ) ) {
				$specification['opens'] = $spec['opens'];
			}

			if ( isset( $spec['closes'] ) ) {
				$specification['closes'] = $spec['closes'];
			}

			$specs[] = $this->filterEmpty( $specification );
		}

		return $specs;
	}

	/**
	 * Build GeoCoordinates schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, float|string>  $geo  The geo data.
	 *
	 * @return array<string, mixed>
	 */
	protected function buildGeoCoordinates( array $geo ): array
	{
		return $this->filterEmpty( [
			'@type'     => 'GeoCoordinates',
			'latitude'  => $geo['latitude'] ?? $geo['lat'] ?? null,
			'longitude' => $geo['longitude'] ?? $geo['lng'] ?? $geo['lon'] ?? null,
		] );
	}
}
