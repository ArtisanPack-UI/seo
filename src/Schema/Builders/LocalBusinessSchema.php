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

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
			$built = $this->buildOpeningHours( $openingHours );
			if ( [] !== $built ) {
				// Structured OpeningHoursSpecification shipped: unset the flat
				// `openingHours` inherited from Organization so Google's Rich
				// Results Test does not flag redundancy (P1-10).
				unset( $schema['openingHours'] );
				$schema['openingHoursSpecification'] = $built;
			}
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
	 * Override the Organization @id so a LocalBusiness node in the same
	 * graph does not collide with a plain Organization node.
	 *
	 * @since 1.4.0
	 *
	 * @return string|null
	 */
	protected function getSchemaId(): ?string
	{
		return $this->buildIdFor( '/#localbusiness' );
	}

	/**
	 * Build OpeningHoursSpecification schema array.
	 *
	 * Supports both recurring entries (via `dayOfWeek`) and dated entries
	 * (via `validFrom` / `validThrough`) for special or holiday hours. A
	 * truthy `closed` flag on an entry emits `opens` and `closes` as
	 * `"00:00"`, per Google's guidance for all-day closures.
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

			$closed = ! empty( $spec['closed'] );

			if ( $closed ) {
				$specification['opens']  = '00:00';
				$specification['closes'] = '00:00';
			} else {
				if ( isset( $spec['opens'] ) ) {
					$specification['opens'] = $spec['opens'];
				}

				if ( isset( $spec['closes'] ) ) {
					$specification['closes'] = $spec['closes'];
				}
			}

			if ( isset( $spec['validFrom'] ) ) {
				$validFrom = $this->normalizeDateOnly( $spec['validFrom'], 'validFrom' );
				if ( null !== $validFrom ) {
					$specification['validFrom'] = $validFrom;
				}
			}

			if ( isset( $spec['validThrough'] ) ) {
				$validThrough = $this->normalizeDateOnly( $spec['validThrough'], 'validThrough' );
				if ( null !== $validThrough ) {
					$specification['validThrough'] = $validThrough;
				}
			}

			$specs[] = $this->filterEmpty( $specification );
		}

		return $specs;
	}

	/**
	 * Normalize a validFrom/validThrough value to an ISO-8601 YYYY-MM-DD
	 * string. Accepts DateTimeInterface (including Carbon), a
	 * `YYYY-MM-DD` string, or drops the value with a warning.
	 *
	 * @since 1.4.0
	 *
	 * @param  mixed   $value  The raw value.
	 * @param  string  $field  The field name (for log context).
	 *
	 * @return string|null
	 */
	protected function normalizeDateOnly( mixed $value, string $field ): ?string
	{
		if ( $value instanceof DateTimeInterface ) {
			return $value->format( 'Y-m-d' );
		}

		if ( is_string( $value ) && 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return $value;
		}

		Log::warning( 'Dropped invalid LocalBusiness date-only value.', [
			'field' => $field,
			'value' => is_scalar( $value ) ? (string) $value : gettype( $value ),
		] );

		return null;
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
