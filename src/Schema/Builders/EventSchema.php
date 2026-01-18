<?php
/**
 * EventSchema.
 *
 * Schema.org Event type builder.
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
 * EventSchema class.
 *
 * Generates Schema.org Event structured data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class EventSchema extends AbstractSchema
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
		return 'Event';
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

		// Start date (required)
		$startDate = $this->get( 'startDate' );
		if ( null !== $startDate ) {
			$schema['startDate'] = $startDate;
		}

		// End date
		$endDate = $this->get( 'endDate' );
		if ( null !== $endDate ) {
			$schema['endDate'] = $endDate;
		}

		// Location (required) - check both location and virtualLocation keys
		$location        = $this->get( 'location' );
		$virtualLocation = $this->get( 'virtualLocation' );
		if ( null !== $virtualLocation ) {
			$schema['location'] = [
				'@type' => 'VirtualLocation',
				'url'   => $virtualLocation,
			];
		} elseif ( null !== $location ) {
			$schema['location'] = $this->buildLocation( $location );
		}

		// Event status
		$eventStatus = $this->get( 'eventStatus' );
		if ( null !== $eventStatus ) {
			$schema['eventStatus'] = $this->mapEventStatus( $eventStatus );
		}

		// Event attendance mode
		$eventAttendanceMode = $this->get( 'eventAttendanceMode' );
		if ( null !== $eventAttendanceMode ) {
			$schema['eventAttendanceMode'] = $this->mapAttendanceMode( $eventAttendanceMode );
		}

		// Organizer
		$organizer = $this->get( 'organizer' );
		if ( null !== $organizer && is_array( $organizer ) ) {
			$schema['organizer'] = $this->buildOrganization( $organizer );
		}

		// Performer
		$performer = $this->get( 'performer' );
		if ( null !== $performer ) {
			$schema['performer'] = $this->buildPerformer( $performer );
		}

		// Offers (tickets)
		$offers = $this->get( 'offers' );
		if ( null !== $offers && is_array( $offers ) ) {
			$schema['offers'] = $this->buildOffers( $offers );
		}

		return $this->filterEmpty( $schema );
	}

	/**
	 * Build location schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>|string  $location  The location data.
	 *
	 * @return array<string, mixed>
	 */
	protected function buildLocation( string|array $location ): array
	{
		// Virtual location
		if ( is_string( $location ) && str_starts_with( $location, 'http' ) ) {
			return [
				'@type' => 'VirtualLocation',
				'url'   => $location,
			];
		}

		if ( is_string( $location ) ) {
			return [
				'@type' => 'Place',
				'name'  => $location,
			];
		}

		// Check for virtual location
		if ( isset( $location['type'] ) && 'VirtualLocation' === $location['type'] ) {
			return [
				'@type' => 'VirtualLocation',
				'url'   => $location['url'] ?? '',
			];
		}

		// Physical location
		$place = [
			'@type' => 'Place',
			'name'  => $location['name'] ?? '',
		];

		if ( isset( $location['address'] ) && is_array( $location['address'] ) ) {
			$place['address'] = $this->filterEmpty( [
				'@type'           => 'PostalAddress',
				'streetAddress'   => $location['address']['street'] ?? null,
				'addressLocality' => $location['address']['city'] ?? null,
				'addressRegion'   => $location['address']['state'] ?? $location['address']['region'] ?? null,
				'postalCode'      => $location['address']['zip'] ?? $location['address']['postalCode'] ?? null,
				'addressCountry'  => $location['address']['country'] ?? null,
			] );
		}

		return $this->filterEmpty( $place );
	}

	/**
	 * Build performer schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>|string  $performer  The performer data.
	 *
	 * @return array<string, mixed>
	 */
	protected function buildPerformer( string|array $performer ): array
	{
		if ( is_string( $performer ) ) {
			return [
				'@type' => 'Person',
				'name'  => $performer,
			];
		}

		$type = $performer['type'] ?? 'Person';

		return $this->filterEmpty( [
			'@type' => $type,
			'name'  => $performer['name'] ?? '',
			'url'   => $performer['url'] ?? null,
		] );
	}

	/**
	 * Build Offer schema for event tickets.
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
			'@type' => 'Offer',
		];

		if ( isset( $offers['price'] ) ) {
			$offer['price'] = $offers['price'];
		}

		if ( isset( $offers['currency'] ) ) {
			$offer['priceCurrency'] = $offers['currency'];
		}

		if ( isset( $offers['availability'] ) ) {
			$offer['availability'] = $this->mapAvailability( $offers['availability'] );
		}

		if ( isset( $offers['url'] ) ) {
			$offer['url'] = $offers['url'];
		}

		if ( isset( $offers['validFrom'] ) ) {
			$offer['validFrom'] = $offers['validFrom'];
		}

		return $this->filterEmpty( $offer );
	}

	/**
	 * Map event status to Schema.org URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $status  The event status.
	 *
	 * @return string
	 */
	protected function mapEventStatus( string $status ): string
	{
		return match ( strtolower( $status ) ) {
			'scheduled'                         => 'https://schema.org/EventScheduled',
			'cancelled', 'canceled'             => 'https://schema.org/EventCancelled',
			'postponed'                         => 'https://schema.org/EventPostponed',
			'rescheduled'                       => 'https://schema.org/EventRescheduled',
			'movedonline', 'moved_online', 'online' => 'https://schema.org/EventMovedOnline',
			default                             => $status,
		};
	}

	/**
	 * Map attendance mode to Schema.org URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $mode  The attendance mode.
	 *
	 * @return string
	 */
	protected function mapAttendanceMode( string $mode ): string
	{
		return match ( strtolower( $mode ) ) {
			'offline', 'inperson', 'in_person' => 'https://schema.org/OfflineEventAttendanceMode',
			'online', 'virtual'                 => 'https://schema.org/OnlineEventAttendanceMode',
			'mixed', 'hybrid'                   => 'https://schema.org/MixedEventAttendanceMode',
			default                             => $mode,
		};
	}

	/**
	 * Map availability to Schema.org URL.
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
			'instock', 'in_stock', 'available' => 'https://schema.org/InStock',
			'soldout', 'sold_out'               => 'https://schema.org/SoldOut',
			'preorder', 'pre_order'            => 'https://schema.org/PreOrder',
			'limited'                           => 'https://schema.org/LimitedAvailability',
			default                             => $availability,
		};
	}
}
