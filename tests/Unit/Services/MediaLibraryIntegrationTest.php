<?php

/**
 * MediaLibraryIntegration Tests.
 *
 * Unit tests for the MediaLibraryIntegration service.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\MediaLibraryIntegration;
use ArtisanPackUI\SEO\Support\PackageDetector;

beforeEach( function (): void {
	$this->integration = new MediaLibraryIntegration();
} );

describe( 'MediaLibraryIntegration Availability', function (): void {

	it( 'checks if media library is available', function (): void {
		$result = $this->integration->isAvailable();

		// The result depends on whether the package is installed
		expect( $result )->toBeBool();
	} );

	it( 'returns same result as PackageDetector', function (): void {
		$integrationResult = $this->integration->isAvailable();
		$detectorResult    = PackageDetector::hasMediaLibrary();

		expect( $integrationResult )->toBe( $detectorResult );
	} );

} );

describe( 'MediaLibraryIntegration Constants', function (): void {

	it( 'has correct social image size name', function (): void {
		expect( MediaLibraryIntegration::SOCIAL_IMAGE_SIZE )->toBe( 'social' );
	} );

	it( 'has correct social image width', function (): void {
		expect( MediaLibraryIntegration::SOCIAL_IMAGE_WIDTH )->toBe( 1200 );
	} );

	it( 'has correct social image height', function (): void {
		expect( MediaLibraryIntegration::SOCIAL_IMAGE_HEIGHT )->toBe( 630 );
	} );

} );

describe( 'MediaLibraryIntegration getMediaUrl', function (): void {

	it( 'returns null for null media id', function (): void {
		$result = $this->integration->getMediaUrl( null );

		expect( $result )->toBeNull();
	} );

	it( 'returns null when media library is not available', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( MediaLibraryIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		$result = $integration->getMediaUrl( 1 );

		expect( $result )->toBeNull();
	} );

} );

describe( 'MediaLibraryIntegration getSocialImageUrl', function (): void {

	it( 'returns null for null media id', function (): void {
		$result = $this->integration->getSocialImageUrl( null );

		expect( $result )->toBeNull();
	} );

	it( 'returns null when media library is not available', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( MediaLibraryIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		$result = $integration->getSocialImageUrl( 1 );

		expect( $result )->toBeNull();
	} );

} );

describe( 'MediaLibraryIntegration getMedia', function (): void {

	it( 'returns null when media library is not available', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( MediaLibraryIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		$result = $integration->getMedia( 1 );

		expect( $result )->toBeNull();
	} );

} );

describe( 'MediaLibraryIntegration registerSocialImageSize', function (): void {

	it( 'does not throw when media library is not available', function (): void {
		// Create a partial mock that returns false for isAvailable
		$integration = Mockery::mock( MediaLibraryIntegration::class )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( false );

		// This should not throw an exception
		$integration->registerSocialImageSize();

		expect( true )->toBeTrue();
	} );

} );
