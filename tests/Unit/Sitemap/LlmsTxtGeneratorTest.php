<?php

/**
 * LlmsTxtGenerator Tests.
 *
 * Unit tests for the llms.txt AI-discovery manifest generator.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\Hooks\Facades\Filter;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Services\SitemapService;
use ArtisanPackUI\SEO\Sitemap\Generators\LlmsTxtGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	config( [
		'seo.site.name'        => 'Acme Widgets',
		'seo.site.description' => 'The finest widgets on the internet.',
		'seo.llms_txt'         => [
			'enabled'       => true,
			'title'         => null,
			'summary'       => null,
			'intro'         => null,
			'include_types' => [],
			'exclude_types' => [],
			'max_entries'   => null,
		],
	] );

	Filter::removeAll( 'ap.seo.llmsTxtEntries' );
} );

afterEach( function (): void {
	Filter::removeAll( 'ap.seo.llmsTxtEntries' );
} );

describe( 'LlmsTxtGenerator', function (): void {

	it( 'emits a manifest with the site title and summary', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/about',
			'type'             => 'page',
			'priority'         => 0.8,
			'changefreq'       => 'weekly',
		] );

		$output = ( new LlmsTxtGenerator() )->generate();

		expect( $output )->toStartWith( '# Acme Widgets' )
			->and( $output )->toContain( '> The finest widgets on the internet.' )
			->and( $output )->toContain( '## Pages' )
			->and( $output )->toContain( '- [About](https://example.com/about)' )
			->and( $output )->toEndWith( "\n" );
	} );

	it( 'excludes non-indexable entries', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/public',
			'type'             => 'page',
			'is_indexable'     => true,
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 2,
			'url'              => 'https://example.com/hidden',
			'type'             => 'page',
			'is_indexable'     => false,
		] );

		$output = ( new LlmsTxtGenerator() )->generate();

		expect( $output )->toContain( 'https://example.com/public' )
			->and( $output )->not->toContain( 'https://example.com/hidden' );
	} );

	it( 'groups entries by type into Markdown sections', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/about',
			'type'             => 'page',
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Post',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/blog/hello-world',
			'type'             => 'post',
		] );

		$output = ( new LlmsTxtGenerator() )->generate();

		expect( $output )->toContain( '## Pages' )
			->and( $output )->toContain( '## Posts' )
			->and( $output )->toContain( 'https://example.com/about' )
			->and( $output )->toContain( 'https://example.com/blog/hello-world' );
	} );

	it( 'derives readable titles from URL slugs when no model metadata exists', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Post',
			'sitemapable_id'   => 42,
			'url'              => 'https://example.com/blog/my-first-post',
			'type'             => 'post',
		] );

		$output = ( new LlmsTxtGenerator() )->generate();

		expect( $output )->toContain( '- [My First Post](https://example.com/blog/my-first-post)' );
	} );

	it( 'honours include and exclude type filters from config', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/about',
			'type'             => 'page',
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Post',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/blog/hello-world',
			'type'             => 'post',
		] );

		config( [ 'seo.llms_txt.exclude_types' => [ 'post' ] ] );

		$output = ( new LlmsTxtGenerator() )->generate();

		expect( $output )->toContain( 'https://example.com/about' )
			->and( $output )->not->toContain( 'hello-world' );
	} );

	it( 'applies the ap.seo.llmsTxtEntries filter', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/about',
			'type'             => 'page',
		] );

		Filter::add( 'ap.seo.llmsTxtEntries', function ( array $entries ): array {
			$entries[] = [
				'url'         => 'https://example.com/extra',
				'type'        => 'page',
				'title'       => 'Extra Page',
				'description' => 'Added via filter.',
			];

			return $entries;
		} );

		$output = ( new LlmsTxtGenerator() )->generate();

		expect( $output )->toContain( '- [Extra Page](https://example.com/extra): Added via filter.' );
	} );

	it( 'caps entries after filter callbacks have run', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/one',
			'type'             => 'page',
			'priority'         => 0.9,
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 2,
			'url'              => 'https://example.com/two',
			'type'             => 'page',
			'priority'         => 0.8,
		] );

		Filter::add( 'ap.seo.llmsTxtEntries', function ( array $entries ): array {
			$entries[] = [
				'url'   => 'https://example.com/filter-added',
				'type'  => 'page',
				'title' => 'Filter Added',
			];

			return $entries;
		} );

		config( [ 'seo.llms_txt.max_entries' => 2 ] );

		$output = ( new LlmsTxtGenerator() )->generate();

		// Cap is applied after the filter, so the total across DB + filter entries is 2.
		expect( substr_count( $output, "\n- [" ) )->toBe( 2 );
	} );

	it( 'treats max_entries=0 as literal zero, not unlimited', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/anything',
			'type'             => 'page',
		] );

		config( [ 'seo.llms_txt.max_entries' => 0 ] );

		$output = ( new LlmsTxtGenerator() )->generate();

		expect( $output )->not->toContain( 'https://example.com/anything' )
			->and( $output )->not->toContain( '## Pages' );
	} );

	it( 'is cached and invalidated by SitemapService::clearCache()', function (): void {
		Cache::flush();

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/first',
			'type'             => 'page',
		] );

		$service = new SitemapService();
		$first   = $service->generateLlmsTxt();

		expect( $first )->toContain( 'https://example.com/first' );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 2,
			'url'              => 'https://example.com/second',
			'type'             => 'page',
		] );

		// Same generation counter — cache still serves the pre-change snapshot.
		expect( $service->generateLlmsTxt() )->not->toContain( 'https://example.com/second' );

		$service->clearCache();

		expect( $service->generateLlmsTxt() )->toContain( 'https://example.com/second' );
	} );

	it( 'collapses newlines in titles so a Markdown list item stays on one line', function (): void {
		Filter::add( 'ap.seo.llmsTxtEntries', function ( array $entries ): array {
			$entries[] = [
				'url'   => 'https://example.com/mb',
				'type'  => 'page',
				'title' => "foo\nbar",
			];

			return $entries;
		} );

		$output = ( new LlmsTxtGenerator() )->generate();

		expect( $output )->toContain( '- [foo bar](https://example.com/mb)' )
			->and( $output )->not->toContain( "foo\nbar" );
	} );

	it( 'escapes square brackets that appear in a description', function (): void {
		Filter::add( 'ap.seo.llmsTxtEntries', function ( array $entries ): array {
			$entries[] = [
				'url'         => 'https://example.com/desc',
				'type'        => 'page',
				'title'       => 'Guide',
				'description' => 'see [here] for details',
			];

			return $entries;
		} );

		$output = ( new LlmsTxtGenerator() )->generate();

		expect( $output )->toContain( 'see \\[here\\] for details' );
	} );
} );
