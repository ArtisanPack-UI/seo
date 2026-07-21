<?php

/**
 * SitemapGenerator Hook Tests.
 *
 * Tests the `ap.seo.sitemapEntries` filter hook fired during sitemap generation.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.3.0
 */

declare( strict_types=1 );

use ArtisanPackUI\Hooks\Facades\Filter;
use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Sitemap\Generators\SitemapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	Filter::removeAll( 'ap.seo.sitemapEntries' );
} );

afterEach( function (): void {
	Filter::removeAll( 'ap.seo.sitemapEntries' );
} );

describe( 'SitemapGenerator ap.seo.sitemapEntries filter', function (): void {

	it( 'fires the filter with the entries array and sitemap type', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		$captured = [];

		addFilter( 'ap.seo.sitemapEntries', function ( array $entries, string $sitemapType ) use ( &$captured ): array {
			$captured['entries'] = $entries;
			$captured['type']    = $sitemapType;

			return $entries;
		} );

		( new SitemapGenerator() )->generate( 'page' );

		expect( $captured )->toHaveKeys( [ 'entries', 'type' ] )
			->and( $captured['entries'] )->toBeArray()
			->and( $captured['entries'] )->toHaveCount( 1 )
			->and( $captured['type'] )->toBe( 'page' );
	} );

	it( 'allows callbacks to append new entries', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
		] );

		addFilter( 'ap.seo.sitemapEntries', function ( array $entries ): array {
			$entries[] = [
				'url'        => 'https://example.com/injected',
				'lastmod'    => null,
				'changefreq' => 'weekly',
				'priority'   => 0.5,
			];

			return $entries;
		} );

		$xml = ( new SitemapGenerator() )->generate();

		expect( $xml )->toContain( 'https://example.com/page-1' )
			->and( $xml )->toContain( 'https://example.com/injected' );
	} );

	it( 'allows callbacks to remove entries', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/keep',
			'type'             => 'page',
		] );
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 2,
			'url'              => 'https://example.com/drop',
			'type'             => 'page',
		] );

		addFilter( 'ap.seo.sitemapEntries', function ( array $entries ): array {
			return array_values( array_filter( $entries, function ( $entry ): bool {
				$url = $entry instanceof SitemapEntry ? $entry->url : ( $entry['url'] ?? '' );

				return ! str_contains( $url, '/drop' );
			} ) );
		} );

		$xml = ( new SitemapGenerator() )->generate();

		expect( $xml )->toContain( 'https://example.com/keep' )
			->and( $xml )->not->toContain( 'https://example.com/drop' );
	} );

	it( 'fires the filter with the provider type when generating from a provider', function (): void {
		$captured = [];

		addFilter( 'ap.seo.sitemapEntries', function ( array $entries, string $sitemapType ) use ( &$captured ): array {
			$captured[] = $sitemapType;

			return $entries;
		} );

		$provider = new class implements SitemapProviderContract {
			public function getUrls(): Collection
			{
				return collect( [
					[ 'loc' => 'https://example.com/custom' ],
				] );
			}

			public function getChangeFrequency(): string
			{
				return 'weekly';
			}

			public function getPriority(): float
			{
				return 0.7;
			}

			public function getType(): string
			{
				return 'custom-provider';
			}
		};

		( new SitemapGenerator() )->generateFromProvider( $provider );

		expect( $captured )->toContain( 'custom-provider' );
	} );

} );
