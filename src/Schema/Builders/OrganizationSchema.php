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

use ArtisanPackUI\SEO\Services\CmsFrameworkIntegration;
use Illuminate\Database\Eloquent\Model;

/**
 * OrganizationSchema class.
 *
 * Generates Schema.org Organization structured data.
 * Integrates with CMS framework when available to pull organization data.
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
	 * Get a human-readable description of this schema type.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return __( 'An organization such as a company, non-profit, or agency' );
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
			[ 'name' => 'name', 'type' => 'text', 'label' => __( 'Organization Name' ), 'required' => true, 'description' => __( 'The name of the organization' ) ],
			[ 'name' => 'url', 'type' => 'url', 'label' => __( 'Website URL' ), 'required' => true, 'description' => __( 'The URL of the organization website' ) ],
			[ 'name' => 'logo', 'type' => 'image', 'label' => __( 'Logo' ), 'required' => false, 'description' => __( 'URL of the organization logo' ) ],
			[ 'name' => 'email', 'type' => 'email', 'label' => __( 'Email' ), 'required' => false, 'description' => __( 'Contact email address' ) ],
			[ 'name' => 'phone', 'type' => 'text', 'label' => __( 'Phone' ), 'required' => false, 'description' => __( 'Contact phone number' ) ],
			[ 'name' => 'description', 'type' => 'textarea', 'label' => __( 'Description' ), 'required' => false, 'description' => __( 'A description of the organization' ) ],
			[ 'name' => 'address', 'type' => 'address', 'label' => __( 'Address' ), 'required' => false, 'description' => __( 'Physical address of the organization' ) ],
			[ 'name' => 'sameAs', 'type' => 'url_list', 'label' => __( 'Social Profiles' ), 'required' => false, 'description' => __( 'URLs of social media profiles' ) ],
		];
	}

	/**
	 * Generate the schema data array.
	 *
	 * Uses CMS framework data when available, falling back to config values.
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

		// Get CMS data if available
		$cmsData = $this->getCmsOrganizationData();

		$schema['name'] = $this->get( 'name', $cmsData['name'] ?? config( 'seo.schema.organization.name', config( 'app.name', '' ) ) );
		$schema['url']  = $this->get( 'url', $cmsData['url'] ?? config( 'seo.schema.organization.url', config( 'app.url', '' ) ) );

		$logo = $this->get( 'logo', $cmsData['logo'] ?? config( 'seo.schema.organization.logo' ) );
		if ( null !== $logo ) {
			$schema['logo'] = $this->buildImageObject( $logo );
		}

		$email = $this->get( 'email', $cmsData['email'] ?? config( 'seo.schema.organization.email' ) );
		if ( null !== $email ) {
			$schema['email'] = $email;
		}

		$phone = $this->get( 'phone', $cmsData['telephone'] ?? config( 'seo.schema.organization.phone' ) );
		if ( null !== $phone ) {
			$schema['telephone'] = $phone;
		}

		$description = $this->get( 'description', $cmsData['description'] ?? null );
		if ( null !== $description ) {
			$schema['description'] = $description;
		}

		$address = $this->get( 'address', $cmsData['address'] ?? null );
		if ( null !== $address && is_array( $address ) ) {
			$schema['address'] = $this->buildPostalAddress( $address );
		}

		$sameAs = $this->get( 'sameAs', $cmsData['sameAs'] ?? null );
		if ( null !== $sameAs && is_array( $sameAs ) && ! empty( $sameAs ) ) {
			$schema['sameAs'] = $sameAs;
		}

		// Add opening hours if available from CMS
		$openingHours = $cmsData['openingHours'] ?? null;
		if ( null !== $openingHours ) {
			$schema['openingHours'] = $openingHours;
		}

		// Add price range if available from CMS
		$priceRange = $cmsData['priceRange'] ?? null;
		if ( null !== $priceRange ) {
			$schema['priceRange'] = $priceRange;
		}

		return $this->filterEmpty( $schema );
	}

	/**
	 * Get organization data from CMS framework if available.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The organization data from CMS or empty array.
	 */
	protected function getCmsOrganizationData(): array
	{
		$integration = app( CmsFrameworkIntegration::class );

		if ( ! $integration->isAvailable() ) {
			return [];
		}

		return $integration->getOrganizationData();
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
