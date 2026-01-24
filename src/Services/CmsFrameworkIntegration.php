<?php

/**
 * CmsFrameworkIntegration.
 *
 * Service class for integrating with the optional artisanpack-ui/cms-framework package.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\Support\PackageDetector;
use Illuminate\Support\Collection;

/**
 * CmsFrameworkIntegration class.
 *
 * Provides methods to interact with the CMS framework package
 * for GlobalContent values and sitemap data. Gracefully handles missing package.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class CmsFrameworkIntegration
{
	/**
	 * Social profile key mappings.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, string>
	 */
	protected const SOCIAL_PROFILE_KEYS = [
		'facebook_url',
		'twitter_url',
		'instagram_url',
		'linkedin_url',
		'youtube_url',
		'pinterest_url',
		'tiktok_url',
		'github_url',
	];

	/**
	 * Check if the CMS framework is available.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if CMS framework is installed.
	 */
	public function isAvailable(): bool
	{
		return PackageDetector::hasCmsFramework();
	}

	/**
	 * Get a GlobalContent value by key.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $key     The GlobalContent key.
	 * @param  mixed  $default The default value if not found.
	 *
	 * @return mixed The GlobalContent value or default.
	 */
	public function getGlobalContent( string $key, mixed $default = null ): mixed
	{
		if ( ! $this->isAvailable() ) {
			return $default;
		}

		// Use the global_content helper if available
		if ( function_exists( 'global_content' ) ) {
			return global_content( $key, $default );
		}

		// Fallback: try to access the model directly
		return $this->getGlobalContentDirect( $key, $default );
	}

	/**
	 * Get organization data for schema markup.
	 *
	 * Returns data suitable for OrganizationSchema.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The organization data.
	 */
	public function getOrganizationData(): array
	{
		if ( ! $this->isAvailable() ) {
			return $this->getDefaultOrganizationData();
		}

		$data = [
			'name'         => $this->getGlobalContent( 'business_name', config( 'app.name' ) ),
			'url'          => config( 'app.url' ),
			'logo'         => $this->getGlobalContent( 'logo_url' ),
			'telephone'    => $this->getGlobalContent( 'phone' ),
			'email'        => $this->getGlobalContent( 'email' ),
			'description'  => $this->getGlobalContent( 'business_description' ),
			'address'      => $this->getAddressData(),
			'openingHours' => $this->getGlobalContent( 'business_hours' ),
			'priceRange'   => $this->getGlobalContent( 'price_range' ),
			'sameAs'       => $this->getSocialProfiles(),
		];

		// Filter out null values
		return array_filter( $data, fn ( $value ) => null !== $value );
	}

	/**
	 * Get pages eligible for sitemap inclusion.
	 *
	 * @since 1.0.0
	 *
	 * @return Collection<int, \Illuminate\Database\Eloquent\Model> Collection of pages.
	 */
	public function getSitemapPages(): Collection
	{
		if ( ! $this->isAvailable() ) {
			return collect();
		}

		$pageClass = 'ArtisanPackUI\CmsFramework\Models\Page';

		if ( ! class_exists( $pageClass ) ) {
			return collect();
		}

		return $pageClass::query()
			->where( 'is_published', true )
			->whereDoesntHave( 'seoMeta', function ( $query ): void {
				$query->where( 'exclude_from_sitemap', true )
					->orWhere( 'no_index', true );
			} )
			->get();
	}

	/**
	 * Get posts eligible for sitemap inclusion.
	 *
	 * @since 1.0.0
	 *
	 * @return Collection<int, \Illuminate\Database\Eloquent\Model> Collection of posts.
	 */
	public function getSitemapPosts(): Collection
	{
		if ( ! $this->isAvailable() ) {
			return collect();
		}

		$postClass = 'ArtisanPackUI\CmsFramework\Models\Post';

		if ( ! class_exists( $postClass ) ) {
			return collect();
		}

		return $postClass::query()
			->where( 'is_published', true )
			->whereDoesntHave( 'seoMeta', function ( $query ): void {
				$query->where( 'exclude_from_sitemap', true )
					->orWhere( 'no_index', true );
			} )
			->get();
	}

	/**
	 * Get social profile URLs.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Array of social profile URLs.
	 */
	protected function getSocialProfiles(): array
	{
		$profiles = [];

		foreach ( self::SOCIAL_PROFILE_KEYS as $key ) {
			$url = $this->getGlobalContent( $key );

			if ( null !== $url && '' !== $url ) {
				$profiles[] = $url;
			}
		}

		return empty( $profiles ) ? [] : $profiles;
	}

	/**
	 * Get address data formatted for schema markup.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>|null The address data or null if not available.
	 */
	protected function getAddressData(): ?array
	{
		$street  = $this->getGlobalContent( 'address' );
		$city    = $this->getGlobalContent( 'city' );
		$state   = $this->getGlobalContent( 'state' );
		$zip     = $this->getGlobalContent( 'zip' );
		$country = $this->getGlobalContent( 'country', 'US' );

		// Return null if no address components are available
		if ( null === $street && null === $city && null === $state && null === $zip ) {
			return null;
		}

		return array_filter( [
			'street'  => $street,
			'city'    => $city,
			'state'   => $state,
			'zip'     => $zip,
			'country' => $country,
		], fn ( $value ) => null !== $value );
	}

	/**
	 * Get default organization data from config.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The default organization data.
	 */
	protected function getDefaultOrganizationData(): array
	{
		return array_filter( [
			'name'      => config( 'seo.schema.organization.name', config( 'app.name' ) ),
			'url'       => config( 'seo.schema.organization.url', config( 'app.url' ) ),
			'logo'      => config( 'seo.schema.organization.logo' ),
			'telephone' => config( 'seo.schema.organization.phone' ),
			'email'     => config( 'seo.schema.organization.email' ),
		], fn ( $value ) => null !== $value );
	}

	/**
	 * Get GlobalContent value directly from the model.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $key     The content key.
	 * @param  mixed  $default The default value.
	 *
	 * @return mixed The content value or default.
	 */
	protected function getGlobalContentDirect( string $key, mixed $default ): mixed
	{
		$globalContentClass = \ArtisanPackUI\CmsFramework\Models\GlobalContent::class;

		if ( ! class_exists( $globalContentClass ) ) {
			return $default;
		}

		$content = $globalContentClass::where( 'key', $key )->first();

		if ( null === $content ) {
			return $default;
		}

		return $content->value ?? $default;
	}
}
