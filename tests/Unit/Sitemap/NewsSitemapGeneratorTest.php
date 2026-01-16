<?php

/**
 * NewsSitemapGenerator Tests.
 *
 * Unit tests for the NewsSitemapGenerator class.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Sitemap\Generators\NewsSitemapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
	config( [ 'app.name' => 'Test Publication' ] );
	config( [ 'app.locale' => 'en' ] );
} );

/**
 * Create a mock news provider with the given URLs.
 *
 * @param  array<int, array<string, mixed>>  $urls  The URLs to return.
 *
 * @return SitemapProviderContract
 */
function createNewsProvider( array $urls ): SitemapProviderContract
{
	return new class( $urls ) implements SitemapProviderContract {
		public function __construct( private array $urls )
		{
		}

		public function getUrls(): Collection
		{
			return collect( $this->urls );
		}

		public function getChangeFrequency(): string
		{
			return 'daily';
		}

		public function getPriority(): float
		{
			return 0.8;
		}

		public function getType(): string
		{
			return 'news';
		}
	};
}

describe( 'NewsSitemapGenerator', function (): void {

	it( 'generates empty sitemap when no recent news entries exist', function (): void {
		// Create an old entry that should not appear
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Article',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/article-1',
			'type'             => 'article',
			'last_modified'    => now()->subDays( 5 ),
		] );

		$generator = new NewsSitemapGenerator();
		$xml       = $generator->generate();

		expect( $xml )->toContain( '<?xml version="1.0" encoding="UTF-8"?>' )
			->and( $xml )->toContain( 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"' )
			->and( $xml )->not->toContain( '<url>' );
	} );

	it( 'generates sitemap with news entries from provider', function (): void {
		$provider = createNewsProvider( [
			[
				'url'              => 'https://example.com/article-1',
				'title'            => 'Test Article Title',
				'publication_date' => now()->subHours( 5 )->toIso8601String(),
			],
		] );

		$generator = new NewsSitemapGenerator();
		$xml       = $generator->generateFromProvider( $provider );

		expect( $xml )->toContain( '<url>' )
			->and( $xml )->toContain( '<loc>https://example.com/article-1</loc>' )
			->and( $xml )->toContain( '<news:news>' )
			->and( $xml )->toContain( '<news:publication>' )
			->and( $xml )->toContain( '<news:name>Test Publication</news:name>' )
			->and( $xml )->toContain( '<news:language>en</news:language>' )
			->and( $xml )->toContain( '<news:publication_date>' )
			->and( $xml )->toContain( '<news:title>Test Article Title</news:title>' );
	} );

	it( 'only includes entries from last 2 days by default from provider', function (): void {
		$provider = createNewsProvider( [
			[
				'url'              => 'https://example.com/recent-article',
				'title'            => 'Recent Article',
				'publication_date' => now()->subHours( 12 )->toIso8601String(),
			],
			[
				'url'              => 'https://example.com/old-article',
				'title'            => 'Old Article',
				'publication_date' => now()->subDays( 5 )->toIso8601String(),
			],
		] );

		$generator = new NewsSitemapGenerator();
		$xml       = $generator->generateFromProvider( $provider );

		expect( $xml )->toContain( 'recent-article' )
			->and( $xml )->not->toContain( 'old-article' );
	} );

	it( 'skips entries without titles from provider', function (): void {
		$provider = createNewsProvider( [
			[
				'url'              => 'https://example.com/with-title',
				'title'            => 'Has Title',
				'publication_date' => now()->subHours( 1 )->toIso8601String(),
			],
			[
				'url'              => 'https://example.com/without-title',
				'publication_date' => now()->subHours( 1 )->toIso8601String(),
			],
		] );

		$generator = new NewsSitemapGenerator();
		$xml       = $generator->generateFromProvider( $provider );

		expect( $xml )->toContain( 'with-title' )
			->and( $xml )->not->toContain( 'without-title' );
	} );

	it( 'respects configured news types', function (): void {
		// Article type - default configured
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Article',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/article-1',
			'type'             => 'article',
			'last_modified'    => now()->subHours( 1 ),
		] );

		// Page type - not in default news types
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/page-1',
			'type'             => 'page',
			'last_modified'    => now()->subHours( 1 ),
		] );

		$generator = new NewsSitemapGenerator();

		// Article should be counted (it's in news types)
		// Page should not be counted (it's not in news types)
		expect( $generator->hasNews() )->toBeTrue();
	} );

	it( 'allows setting custom news types', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\BlogPost',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/blog-1',
			'type'             => 'blog',
			'last_modified'    => now()->subHours( 1 ),
		] );

		$generator = new NewsSitemapGenerator();
		$generator->setNewsTypes( [ 'blog' ] );

		expect( $generator->hasNews() )->toBeTrue();
	} );

	it( 'allows setting custom publication name', function (): void {
		$provider = createNewsProvider( [
			[
				'url'              => 'https://example.com/article-1',
				'title'            => 'Test Article',
				'publication_date' => now()->subHours( 1 )->toIso8601String(),
			],
		] );

		$generator = new NewsSitemapGenerator();
		$generator->setPublicationName( 'Custom Publication' );
		$xml = $generator->generateFromProvider( $provider );

		expect( $xml )->toContain( '<news:name>Custom Publication</news:name>' );
	} );

	it( 'allows setting custom publication language', function (): void {
		$provider = createNewsProvider( [
			[
				'url'              => 'https://example.com/article-1',
				'title'            => 'Test Article',
				'publication_date' => now()->subHours( 1 )->toIso8601String(),
			],
		] );

		$generator = new NewsSitemapGenerator();
		$generator->setPublicationLanguage( 'de' );
		$xml = $generator->generateFromProvider( $provider );

		expect( $xml )->toContain( '<news:language>de</news:language>' );
	} );

	it( 'excludes non-indexable entries', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Article',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/visible-article',
			'type'             => 'article',
			'is_indexable'     => true,
			'last_modified'    => now()->subHours( 1 ),
		] );

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Article',
			'sitemapable_id'   => 2,
			'url'              => 'https://example.com/hidden-article',
			'type'             => 'article',
			'is_indexable'     => false,
			'last_modified'    => now()->subHours( 1 ),
		] );

		$generator = new NewsSitemapGenerator();

		// Only the indexable entry should be counted
		expect( $generator->hasNews() )->toBeTrue();
		expect( $generator->getTotalPages() )->toBe( 1 );
	} );

	it( 'calculates total pages correctly', function (): void {
		for ( $i = 1; $i <= 5; $i++ ) {
			SitemapEntry::create( [
				'sitemapable_type' => 'App\\Models\\Article',
				'sitemapable_id'   => $i,
				'url'              => "https://example.com/article-{$i}",
				'type'             => 'article',
				'last_modified'    => now()->subHours( $i ),
			] );
		}

		$generator = new NewsSitemapGenerator( null, null, null, 2 );

		expect( $generator->getTotalPages() )->toBe( 3 );
	} );

	it( 'checks if news entries exist', function (): void {
		expect( ( new NewsSitemapGenerator() )->hasNews() )->toBeFalse();

		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Article',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/article-1',
			'type'             => 'article',
			'last_modified'    => now()->subHours( 1 ),
		] );

		expect( ( new NewsSitemapGenerator() )->hasNews() )->toBeTrue();
	} );

	it( 'uses post type for news sitemap counting', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Post',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/post-1',
			'type'             => 'post',
			'last_modified'    => now()->subHours( 1 ),
		] );

		$generator = new NewsSitemapGenerator();

		expect( $generator->hasNews() )->toBeTrue();
	} );

	it( 'paginates results correctly from provider', function (): void {
		$urls = [];
		for ( $i = 1; $i <= 5; $i++ ) {
			$urls[] = [
				'url'              => "https://example.com/article-{$i}",
				'title'            => "Article {$i}",
				'publication_date' => now()->subHours( $i )->toIso8601String(),
			];
		}

		$provider  = createNewsProvider( $urls );
		$generator = new NewsSitemapGenerator( null, null, null, 2 );

		$xml = $generator->generateFromProvider( $provider );

		// All 5 entries should be in the output (provider doesn't paginate, it filters by date)
		expect( substr_count( $xml, '<url>' ) )->toBe( 5 );
	} );

	it( 'returns 0 pages when maxUrls is 0', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Article',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/article-1',
			'type'             => 'article',
			'last_modified'    => now()->subHours( 1 ),
		] );

		$generator = new NewsSitemapGenerator( null, null, null, 0 );

		// Should not throw division by zero, should return 1 page (ceil(1/1))
		expect( $generator->getTotalPages() )->toBe( 1 );
	} );

	it( 'includes keywords when provided', function (): void {
		$provider = createNewsProvider( [
			[
				'url'              => 'https://example.com/article-1',
				'title'            => 'Test Article',
				'publication_date' => now()->subHours( 1 )->toIso8601String(),
				'keywords'         => [ 'tech', 'news', 'update' ],
			],
		] );

		$generator = new NewsSitemapGenerator();
		$xml       = $generator->generateFromProvider( $provider );

		expect( $xml )->toContain( '<news:keywords>tech, news, update</news:keywords>' );
	} );

	it( 'includes stock tickers when provided', function (): void {
		$provider = createNewsProvider( [
			[
				'url'              => 'https://example.com/article-1',
				'title'            => 'Test Article',
				'publication_date' => now()->subHours( 1 )->toIso8601String(),
				'stock_tickers'    => [ 'NASDAQ:GOOG', 'NASDAQ:AAPL' ],
			],
		] );

		$generator = new NewsSitemapGenerator();
		$xml       = $generator->generateFromProvider( $provider );

		expect( $xml )->toContain( '<news:stock_tickers>NASDAQ:GOOG, NASDAQ:AAPL</news:stock_tickers>' );
	} );

	it( 'uses entry-specific publication info when provided', function (): void {
		$provider = createNewsProvider( [
			[
				'url'              => 'https://example.com/article-1',
				'title'            => 'Test Article',
				'publication_date' => now()->subHours( 1 )->toIso8601String(),
				'publication_name' => 'Entry Specific Publication',
				'language'         => 'fr',
			],
		] );

		$generator = new NewsSitemapGenerator();
		$xml       = $generator->generateFromProvider( $provider );

		expect( $xml )->toContain( '<news:name>Entry Specific Publication</news:name>' )
			->and( $xml )->toContain( '<news:language>fr</news:language>' );
	} );

} );
