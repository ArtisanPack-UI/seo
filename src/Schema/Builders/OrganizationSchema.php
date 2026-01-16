<?php

/**
 * OrganizationSchema.
 *
 * Schema.org Organization type builder.
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
 * OrganizationSchema class.
 *
 * Generates Schema.org Organization structured data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class OrganizationSchema extends AbstractSchema
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
		return 'Organization';
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

		$schema['name'] = $this->get( 'name', config( 'seo.schema.organization.name', config( 'app.name', '' ) ) );
		$schema['url']  = $this->get( 'url', config( 'seo.schema.organization.url', config( 'app.url', '' ) ) );

		$logo = $this->get( 'logo', config( 'seo.schema.organization.logo' ) );
		if ( null !== $logo ) {
			$schema['logo'] = $this->buildImageObject( $logo );
		}

		$email = $this->get( 'email', config( 'seo.schema.organization.email' ) );
		if ( null !== $email ) {
			$schema['email'] = $email;
		}

		$phone = $this->get( 'phone', config( 'seo.schema.organization.phone' ) );
		if ( null !== $phone ) {
			$schema['telephone'] = $phone;
		}

		$description = $this->get( 'description' );
		if ( null !== $description ) {
			$schema['description'] = $description;
		}

		$address = $this->get( 'address' );
		if ( null !== $address && is_array( $address ) ) {
			$schema['address'] = $this->buildPostalAddress( $address );
		}

		$sameAs = $this->get( 'sameAs' );
		if ( null !== $sameAs && is_array( $sameAs ) ) {
			$schema['sameAs'] = $sameAs;
		}

		return $this->filterEmpty( $schema );
	}

	/**
	 * Build a PostalAddress schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, string>  $address  The address data.
	 *
	 * @return array<string, string>
	 */
	protected function buildPostalAddress( array $address ): array
	{
		return $this->filterEmpty( [
			'@type'           => 'PostalAddress',
			'streetAddress'   => $address['street'] ?? null,
			'addressLocality' => $address['city'] ?? null,
			'addressRegion'   => $address['state'] ?? $address['region'] ?? null,
			'postalCode'      => $address['zip'] ?? $address['postalCode'] ?? null,
			'addressCountry'  => $address['country'] ?? null,
		] );
	}
}
