<?php

/**
 * PackageDetector Tests.
 *
 * Unit tests for the PackageDetector support class.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Support\PackageDetector;

describe( 'PackageDetector hasMediaLibrary', function (): void {

	it( 'returns boolean value', function (): void {
		$result = PackageDetector::hasMediaLibrary();

		expect( $result )->toBeBool();
	} );

	it( 'returns consistent results', function (): void {
		$result1 = PackageDetector::hasMediaLibrary();
		$result2 = PackageDetector::hasMediaLibrary();

		expect( $result1 )->toBe( $result2 );
	} );

} );

describe( 'PackageDetector hasHooks', function (): void {

	it( 'returns boolean value', function (): void {
		$result = PackageDetector::hasHooks();

		expect( $result )->toBeBool();
	} );

	it( 'returns consistent results', function (): void {
		$result1 = PackageDetector::hasHooks();
		$result2 = PackageDetector::hasHooks();

		expect( $result1 )->toBe( $result2 );
	} );

} );

describe( 'PackageDetector hasAccessibility', function (): void {

	it( 'returns boolean value', function (): void {
		$result = PackageDetector::hasAccessibility();

		expect( $result )->toBeBool();
	} );

	it( 'returns consistent results', function (): void {
		$result1 = PackageDetector::hasAccessibility();
		$result2 = PackageDetector::hasAccessibility();

		expect( $result1 )->toBe( $result2 );
	} );

} );

describe( 'PackageDetector hasCmsFramework', function (): void {

	it( 'returns boolean value', function (): void {
		$result = PackageDetector::hasCmsFramework();

		expect( $result )->toBeBool();
	} );

	it( 'returns consistent results', function (): void {
		$result1 = PackageDetector::hasCmsFramework();
		$result2 = PackageDetector::hasCmsFramework();

		expect( $result1 )->toBe( $result2 );
	} );

} );

describe( 'PackageDetector hasAnalytics', function (): void {

	it( 'returns boolean value', function (): void {
		$result = PackageDetector::hasAnalytics();

		expect( $result )->toBeBool();
	} );

	it( 'returns consistent results', function (): void {
		$result1 = PackageDetector::hasAnalytics();
		$result2 = PackageDetector::hasAnalytics();

		expect( $result1 )->toBe( $result2 );
	} );

} );

describe( 'PackageDetector hasVisualEditor', function (): void {

	it( 'returns boolean value', function (): void {
		$result = PackageDetector::hasVisualEditor();

		expect( $result )->toBeBool();
	} );

	it( 'returns consistent results', function (): void {
		$result1 = PackageDetector::hasVisualEditor();
		$result2 = PackageDetector::hasVisualEditor();

		expect( $result1 )->toBe( $result2 );
	} );

} );
