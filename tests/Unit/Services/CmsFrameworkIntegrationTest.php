<?php

/**
 * CmsFrameworkIntegration Tests.
 *
 * Unit tests for the CmsFrameworkIntegration service.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\CmsFrameworkIntegration;
use ArtisanPackUI\SEO\Support\PackageDetector;

beforeEach( function (): void {
	$this->integration = new CmsFrameworkIntegration();
} );

describe( 'CmsFrameworkIntegration Availability', function (): void {

	it( 'checks if CMS framework is available', function (): void {
		$result = $this->integration->isAvailable();

		// The result depends on whether the package is installed
		expect( $result )->toBeBool();
	} );

	it( 'returns same result as PackageDetector', function (): void {
		$integrationResult = $this->integration->isAvailable();
		$detectorResult    = PackageDetector::hasCmsFramework();

		expect( $integrationResult )->toBe( $detectorResult );
	} );

} );

describe( 'CmsFrameworkIntegration getGlobalContent', function (): void {

	it( 'returns default for non-existent key when CMS not available', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( CmsFrameworkIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		$result = $integration->getGlobalContent( 'business_name', 'Default Business' );

		expect( $result )->toBe( 'Default Business' );
	} );

	it( 'returns null as default when no default provided and CMS not available', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( CmsFrameworkIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		$result = $integration->getGlobalContent( 'non_existent_key' );

		expect( $result )->toBeNull();
	} );

} );

describe( 'CmsFrameworkIntegration getOrganizationData', function (): void {

	it( 'returns default organization data when CMS not available', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( CmsFrameworkIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		config( [
			'seo.schema.organization.name'  => 'Test Company',
			'seo.schema.organization.url'   => 'https://test.com',
			'seo.schema.organization.logo'  => 'https://test.com/logo.png',
			'seo.schema.organization.phone' => '555-1234',
			'seo.schema.organization.email' => 'test@test.com',
		] );

		$result = $integration->getOrganizationData();

		expect( $result )->toBeArray()
			->and( $result['name'] )->toBe( 'Test Company' )
			->and( $result['url'] )->toBe( 'https://test.com' )
			->and( $result['logo'] )->toBe( 'https://test.com/logo.png' )
			->and( $result['telephone'] )->toBe( '555-1234' )
			->and( $result['email'] )->toBe( 'test@test.com' );
	} );

	it( 'filters out null values from organization data', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( CmsFrameworkIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		config( [
			'seo.schema.organization.name'  => 'Test Company',
			'seo.schema.organization.url'   => 'https://test.com',
			'seo.schema.organization.logo'  => null,
			'seo.schema.organization.phone' => null,
			'seo.schema.organization.email' => null,
		] );

		$result = $integration->getOrganizationData();

		expect( $result )->toBeArray()
			->and( $result )->toHaveKey( 'name' )
			->and( $result )->toHaveKey( 'url' )
			->and( $result )->not->toHaveKey( 'logo' )
			->and( $result )->not->toHaveKey( 'telephone' )
			->and( $result )->not->toHaveKey( 'email' );
	} );

} );

describe( 'CmsFrameworkIntegration getSitemapPages', function (): void {

	it( 'returns empty collection when CMS not available', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( CmsFrameworkIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		$result = $integration->getSitemapPages();

		expect( $result )->toBeEmpty();
	} );

} );

describe( 'CmsFrameworkIntegration getSitemapPosts', function (): void {

	it( 'returns empty collection when CMS not available', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( CmsFrameworkIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		$result = $integration->getSitemapPosts();

		expect( $result )->toBeEmpty();
	} );

} );
