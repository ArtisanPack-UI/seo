<?php

/**
 * Sitemap Hints Tests.
 *
 * Verifies `priority` and `changefreq` are only emitted when set, that the
 * migration clears the old defaults, and that the submit command copes with
 * no configured search engines.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.9.0
 */

declare( strict_types=1 );

namespace Tests\Unit\Sitemap\SitemapHints;

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Observers\SitemapObserver;
use ArtisanPackUI\SEO\Sitemap\Generators\SitemapGenerator;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

uses( RefreshDatabase::class );

/**
 * Model tracked by the sitemap observer.
 */
class HintPage extends Model
{
	use HasSeo;

	protected $table = 'hint_pages';

	protected $guarded = [];

	public function getUrl(): string
	{
		return 'https://example.com/pages/' . $this->id;
	}
}

/**
 * Model with no HasSeo trait.
 */
class PlainPage extends Model
{
	protected $table = 'hint_pages';

	protected $guarded = [];

	public function getUrl(): string
	{
		return 'https://example.com/plain/' . $this->id;
	}
}

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	Schema::create( 'hint_pages', function ( Blueprint $table ): void {
		$table->id();
		$table->string( 'title' )->nullable();
		$table->timestamps();
	} );

	HintPage::observe( SitemapObserver::class );
	PlainPage::observe( SitemapObserver::class );
} );

describe( 'sitemap hints', function (): void {

	it( 'omits priority and changefreq for a page with none set', function (): void {
		HintPage::create( [ 'title' => 'Page' ] );

		$xml = ( new SitemapGenerator() )->generate();

		expect( $xml )->toContain( 'https://example.com/pages/1' )
			->and( $xml )->not->toContain( '<priority>' )
			->and( $xml )->not->toContain( '<changefreq>' );
	} );

	it( 'omits them for a model without the HasSeo trait', function (): void {
		PlainPage::create( [ 'title' => 'Page' ] );

		$entry = SitemapEntry::first();

		expect( $entry->priority )->toBeNull()
			->and( $entry->changefreq )->toBeNull();
	} );

	it( 'emits only the hint that was set for a URL', function (): void {
		$page = HintPage::create( [ 'title' => 'Page' ] );

		SeoMeta::create( [
			'seoable_type'     => $page->getMorphClass(),
			'seoable_id'       => $page->id,
			'sitemap_priority' => 0.8,
		] );

		$page->fresh()->save();
		$page->fresh()->touch();

		$xml = ( new SitemapGenerator() )->generate();

		expect( $xml )->toContain( '<priority>0.8</priority>' )
			->and( $xml )->not->toContain( '<changefreq>' );
	} );

	it( 'does not record the default image as a page image', function (): void {
		config( [ 'seo.open_graph.default_image' => 'https://example.com/logo.png' ] );

		HintPage::create( [ 'title' => 'Page' ] );

		expect( SitemapEntry::first()->images )->toBeNull();
	} );

	it( 'still honors a configured site-wide default', function (): void {
		config( [ 'seo.sitemap.default_priority' => 0.7, 'seo.sitemap.default_frequency' => 'daily' ] );

		HintPage::create( [ 'title' => 'Page' ] );

		$xml = ( new SitemapGenerator() )->generate();

		expect( $xml )->toContain( '<priority>0.7</priority>' )
			->and( $xml )->toContain( '<changefreq>daily</changefreq>' );
	} );

	it( 'omits them for a provider that returns null', function (): void {
		$provider = new class implements SitemapProviderContract {
			public function getUrls(): Collection
			{
				return collect( [ [ 'loc' => 'https://example.com/archive' ] ] );
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
				return 'archive';
			}
		};

		$xml = ( new SitemapGenerator() )->generateFromProvider( $provider );

		expect( $xml )->toContain( 'https://example.com/archive' )
			->and( $xml )->not->toContain( '<priority>' )
			->and( $xml )->not->toContain( '<changefreq>' );
	} );

} );

describe( 'sitemap hints migration', function (): void {

	beforeEach( function (): void {
		$this->migration = require realpath( __DIR__ . '/../../../database/migrations/2026_10_30_000002_make_sitemap_hints_nullable.php' );
		$this->migration->down();
	} );

	it( 'clears the old defaults and keeps deliberate values', function (): void {
		DB::table( 'sitemap_entries' )->insert( [
			[ 'sitemapable_type' => 'Page', 'sitemapable_id' => 1, 'url' => 'https://example.com/1', 'type' => 'page', 'priority' => 0.5, 'changefreq' => 'weekly' ],
			[ 'sitemapable_type' => 'Page', 'sitemapable_id' => 2, 'url' => 'https://example.com/2', 'type' => 'page', 'priority' => 0.8, 'changefreq' => 'weekly' ],
		] );
		DB::table( 'seo_meta' )->insert( [
			[ 'seoable_type' => 'Page', 'seoable_id' => 1, 'sitemap_priority' => 0.5, 'sitemap_changefreq' => 'weekly' ],
			[ 'seoable_type' => 'Page', 'seoable_id' => 2, 'sitemap_priority' => 0.5, 'sitemap_changefreq' => 'daily' ],
		] );

		$this->migration->up();

		$entries = DB::table( 'sitemap_entries' )->orderBy( 'sitemapable_id' )->get();
		$meta    = DB::table( 'seo_meta' )->orderBy( 'seoable_id' )->get();

		expect( $entries[0]->priority )->toBeNull()
			->and( $entries[0]->changefreq )->toBeNull()
			->and( (float) $entries[1]->priority )->toBe( 0.8 )
			->and( $entries[1]->changefreq )->toBeNull()
			->and( $meta[0]->sitemap_priority )->toBeNull()
			->and( $meta[0]->sitemap_changefreq )->toBeNull()
			->and( $meta[1]->sitemap_priority )->toBeNull()
			->and( $meta[1]->sitemap_changefreq )->toBe( 'daily' );
	} );

	it( 'restores the defaults when rolled back', function (): void {
		$this->migration->up();

		DB::table( 'sitemap_entries' )->insert( [ 'sitemapable_type' => 'Page', 'sitemapable_id' => 1, 'url' => 'https://example.com/1', 'type' => 'page' ] );

		$this->migration->down();

		$entry = DB::table( 'sitemap_entries' )->first();

		expect( (float) $entry->priority )->toBe( 0.5 )
			->and( $entry->changefreq )->toBe( 'weekly' );

		$this->migration->up();
	} );

} );

describe( 'seo:submit-sitemap', function (): void {

	it( 'explains itself when no search engines are configured', function (): void {
		config( [ 'seo.sitemap.search_engines' => [] ] );
		Http::fake();

		$this->artisan( 'seo:submit-sitemap' )
			->expectsOutputToContain( 'IndexNow' )
			->assertSuccessful();

		Http::assertNothingSent();
	} );

	it( 'fails when an engine is requested but none are configured', function (): void {
		config( [ 'seo.sitemap.search_engines' => [] ] );
		Http::fake();

		$this->artisan( 'seo:submit-sitemap', [ '--engine' => 'bing' ] )
			->expectsOutputToContain( 'Unknown search engine' )
			->assertFailed();

		Http::assertNothingSent();
	} );

} );
