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
use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Services\SitemapService;
use ArtisanPackUI\SEO\Sitemap\Generators\LlmsTxtGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
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

/**
 * Build a sitemap provider serving the given URLs.
 *
 * @param  array<int, array<string, mixed>>  $urls  The provider's URLs.
 * @param  string                            $type  The provider's type.
 */
function llmsTestProvider( array $urls, string $type = 'archive' ): SitemapProviderContract
{
	return new class( $urls, $type ) implements SitemapProviderContract {
		public function __construct( private array $urls, private string $type )
		{
		}

		public function getUrls(): Collection
		{
			return collect( $this->urls );
		}

		public function getChangeFrequency(): ?string
		{
			return null;
		}

		public function getPriority(): ?float
		{
			return null;
		}

		public function getType(): string
		{
			return $this->type;
		}
	};
}

describe( 'LlmsTxtGenerator providers and summary', function (): void {

	it( 'lists provider URLs under their type', function (): void {
		$generator = ( new LlmsTxtGenerator() )->setProviders( collect( [
			'archive' => llmsTestProvider( [
				[ 'loc' => 'https://example.com/blog', 'title' => 'Blog', 'description' => 'All posts.' ],
				[ 'url' => 'https://example.com/news/latest-news' ],
			] ),
		] ) );

		$output = $generator->generate();

		expect( $output )->toContain( '## Archives' )
			->and( $output )->toContain( '- [Blog](https://example.com/blog): All posts.' )
			->and( $output )->toContain( '- [Latest News](https://example.com/news/latest-news)' );
	} );

	it( 'merges provider URLs into a matching stored type', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/about',
			'type'             => 'page',
		] );

		$output = ( new LlmsTxtGenerator() )->setProviders( collect( [
			'page' => llmsTestProvider( [ [ 'loc' => 'https://example.com/team' ] ], 'page' ),
		] ) )->generate();

		expect( substr_count( $output, '## Pages' ) )->toBe( 1 )
			->and( $output )->toContain( 'https://example.com/about' )
			->and( $output )->toContain( 'https://example.com/team' );
	} );

	it( 'skips provider URLs with a stored entry, including non-indexable ones', function (): void {
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/about',
			'type'             => 'page',
		] );
		SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 2,
			'url'              => 'https://example.com/hidden',
			'type'             => 'page',
			'is_indexable'     => false,
		] );

		$output = ( new LlmsTxtGenerator() )->setProviders( collect( [
			'archive' => llmsTestProvider( [
				[ 'loc' => 'https://example.com/about' ],
				[ 'loc' => 'https://example.com/hidden' ],
				[ 'loc' => 'https://example.com/blog' ],
				[ 'loc' => 'https://example.com/blog' ],
				[ 'loc' => '' ],
			] ),
		] ) )->generate();

		expect( substr_count( $output, 'https://example.com/about' ) )->toBe( 1 )
			->and( $output )->not->toContain( 'https://example.com/hidden' )
			->and( substr_count( $output, 'https://example.com/blog' ) )->toBe( 1 )
			->and( $output )->not->toContain( '()' );
	} );

	it( 'applies type rules to providers', function (): void {
		$providers = collect( [
			'archive' => llmsTestProvider( [ [ 'loc' => 'https://example.com/blog' ] ] ),
			'tag'     => llmsTestProvider( [ [ 'loc' => 'https://example.com/tags' ] ], 'tag' ),
		] );

		config( [ 'seo.llms_txt.exclude_types' => [ 'tag' ] ] );
		$excluded = ( new LlmsTxtGenerator() )->setProviders( $providers )->generate();

		config( [ 'seo.llms_txt.exclude_types' => [], 'seo.llms_txt.include_types' => [ 'tag' ] ] );
		$included = ( new LlmsTxtGenerator() )->setProviders( $providers )->generate();

		expect( $excluded )->toContain( 'https://example.com/blog' )
			->and( $excluded )->not->toContain( 'https://example.com/tags' )
			->and( $included )->toContain( 'https://example.com/tags' )
			->and( $included )->not->toContain( 'https://example.com/blog' );
	} );

	it( 'counts provider URLs toward the entry cap', function (): void {
		$output = ( new LlmsTxtGenerator( 1 ) )->setProviders( collect( [
			'archive' => llmsTestProvider( [
				[ 'loc' => 'https://example.com/one' ],
				[ 'loc' => 'https://example.com/two' ],
			] ),
		] ) )->generate();

		expect( $output )->toContain( 'https://example.com/one' )
			->and( $output )->not->toContain( 'https://example.com/two' );
	} );

	it( 'passes provider URLs to the entries filter', function (): void {
		$seen = null;

		Filter::add( 'ap.seo.llmsTxtEntries', function ( array $entries ) use ( &$seen ): array {
			$seen = $entries;

			return $entries;
		} );

		( new LlmsTxtGenerator() )->setProviders( collect( [
			'archive' => llmsTestProvider( [ [ 'loc' => 'https://example.com/blog' ] ] ),
		] ) )->generate();

		expect( $seen[0]['url'] )->toBe( 'https://example.com/blog' );
	} );

	it( 'includes registered providers through the service', function (): void {
		$service = new SitemapService();
		$service->setCacheEnabled( false );
		$service->registerProvider( 'archive', llmsTestProvider( [ [ 'loc' => 'https://example.com/blog' ] ] ) );

		expect( $service->generateLlmsTxt() )->toContain( 'https://example.com/blog' );
	} );

	it( 'falls back to the homepage description for the summary', function (): void {
		config( [ 'seo.site.description' => '', 'app.url' => 'https://example.com' ] );

		$home = SitemapEntry::create( [
			'sitemapable_type' => 'App\\Models\\Page',
			'sitemapable_id'   => 1,
			'url'              => 'https://example.com/',
			'type'             => 'page',
		] );

		$generator = new class( $home ) extends LlmsTxtGenerator {
			public function __construct( private SitemapEntry $home )
			{
				parent::__construct();
			}

			protected function resolveDescription( SitemapEntry|array $entry ): ?string
			{
				return $entry instanceof SitemapEntry && $entry->is( $this->home ) ? 'What this site is about.' : null;
			}
		};

		expect( $generator->generate() )->toContain( "> What this site is about.\n" );
	} );

	it( 'omits the blockquote when nothing describes the site', function (): void {
		config( [ 'seo.site.description' => '', 'app.url' => 'https://example.com' ] );

		expect( ( new LlmsTxtGenerator() )->generate() )->not->toContain( '> ' );
	} );

	it( 'keeps a multi-line summary inside the blockquote', function (): void {
		config( [ 'seo.llms_txt.summary' => "First line.\nSecond line." ] );

		expect( ( new LlmsTxtGenerator() )->generate() )->toContain( '> First line. Second line.' );
	} );

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

		// Same generation — cache still serves the pre-change snapshot.
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
