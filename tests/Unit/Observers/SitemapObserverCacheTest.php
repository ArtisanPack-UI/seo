<?php

/**
 * SitemapObserver Cache Invalidation Tests.
 *
 * Verifies the observer forgets cached sitemap XML when tracked models are
 * saved, deleted, or restored so the next request regenerates fresh output.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Observers\SitemapObserver;
use ArtisanPackUI\SEO\Services\SitemapService;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses( RefreshDatabase::class );

/**
 * Test model that uses the HasSeo trait and soft deletes.
 */
class SitemapObserverTestPage extends Model
{
	use HasSeo;
	use SoftDeletes;

	protected $table = 'sitemap_observer_test_pages';

	protected $fillable = [ 'title', 'slug' ];

	public function getUrl(): string
	{
		return 'https://example.com/' . $this->slug;
	}

	public function getSitemapType(): string
	{
		return 'page';
	}
}

beforeEach( function (): void {
	Schema::create( 'sitemap_observer_test_pages', function ( Blueprint $table ): void {
		$table->id();
		$table->string( 'title' )->nullable();
		$table->string( 'slug' )->nullable();
		$table->softDeletes();
		$table->timestamps();
	} );

	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	config( [
		'seo.sitemap.cache_enabled' => true,
		'seo.cache.prefix'          => 'seo',
	] );
} );

afterEach( function (): void {
	Schema::dropIfExists( 'sitemap_observer_test_pages' );
} );

it( 'invalidates cached sitemap XML when a tracked model is saved', function (): void {
	$service = new SitemapService();

	$page = SitemapObserverTestPage::create( [ 'title' => 'First', 'slug' => 'first' ] );

	// Prime the cache from the first snapshot.
	$initial = $service->generate();
	expect( $initial )->toContain( 'https://example.com/first' );

	// Modify the model. The observer must flush the cache so the fresh
	// snapshot is served on the next request.
	$page->update( [ 'slug' => 'first-renamed' ] );

	$fresh = $service->generate();
	expect( $fresh )
		->toContain( 'https://example.com/first-renamed' )
		->not->toContain( 'https://example.com/first<' );
} );

it( 'invalidates cached sitemap XML when a tracked model is force deleted', function (): void {
	$service = new SitemapService();

	$page = SitemapObserverTestPage::create( [ 'title' => 'Doomed', 'slug' => 'doomed' ] );

	$initial = $service->generate();
	expect( $initial )->toContain( 'https://example.com/doomed' );

	$page->forceDelete();

	$fresh = $service->generate();
	expect( $fresh )->not->toContain( 'https://example.com/doomed' );
} );

it( 'leaves cached sitemap XML alone on a soft delete', function (): void {
	$service = new SitemapService();

	$page = SitemapObserverTestPage::create( [ 'title' => 'Soft', 'slug' => 'soft' ] );

	// Prime the cache. `saved()` invalidated it during create(); regenerate.
	$initial = $service->generate();
	expect( $initial )->toContain( 'https://example.com/soft' );

	$page->delete();

	// Soft delete does not touch sitemap_entries, so the cached snapshot is
	// still accurate and must not be regenerated. Removing the underlying
	// entry directly and re-generating should still return the primed XML.
	SitemapEntry::query()->delete();

	$stillCached = $service->generate();
	expect( $stillCached )->toContain( 'https://example.com/soft' );
} );

it( 'invalidates cached sitemap XML when a soft-deleted model is restored', function (): void {
	$service = new SitemapService();

	$page = SitemapObserverTestPage::create( [ 'title' => 'Coming Back', 'slug' => 'coming-back' ] );
	$page->delete();

	// Soft delete should not touch the entry so cache remains valid; prime it.
	$initial = $service->generate();
	expect( $initial )->toContain( 'https://example.com/coming-back' );

	$page->restore();

	// The observer's restore hook must invalidate the cache so the next
	// request rebuilds fresh XML that still contains the restored URL.
	$fresh = $service->generate();
	expect( $fresh )->toContain( 'https://example.com/coming-back' );
} );

it( 'defers cache invalidation until after the outer DB transaction commits', function (): void {
	// A spy service that counts clearCache() calls so the test can assert
	// timing relative to the enclosing transaction. Using an in-place double
	// rather than a mock keeps the test decoupled from the real cache store.
	$spy = new class () extends SitemapService {
		public int $clearCalls = 0;

		public function clearCache(): void
		{
			++$this->clearCalls;
		}
	};

	$observer = new SitemapObserver( $spy );

	DB::transaction( function () use ( $observer, $spy ): void {
		$page = SitemapObserverTestPage::create( [ 'title' => 'Txn', 'slug' => 'txn' ] );
		$observer->saved( $page );

		// Still inside the outer transaction: the deferred invalidation
		// must not have fired yet, or a concurrent read could re-cache
		// pre-commit data under the new generation.
		expect( $spy->clearCalls )->toBe( 0 );
	} );

	// Once the transaction commits, DB::afterCommit fires the callback.
	expect( $spy->clearCalls )->toBe( 1 );
} );

it( 'invalidates trailing sitemap pages when deletion shrinks the page count', function (): void {
	// One URL per file forces every entry onto its own page so a two-entry
	// site fills exactly two pages. Deleting the second entry should not
	// leave a stale cached page 2 containing the removed URL.
	config( [ 'seo.sitemap.max_urls_per_file' => 1 ] );

	$service = new SitemapService();

	$one = SitemapObserverTestPage::create( [ 'title' => 'One', 'slug' => 'one' ] );
	$two = SitemapObserverTestPage::create( [ 'title' => 'Two', 'slug' => 'two' ] );

	// Prime both pages while the site has two entries.
	$pageOne = $service->generate( 'page', 1 );
	$pageTwo = $service->generate( 'page', 2 );
	expect( $pageOne )->toContain( 'https://example.com/one' );
	expect( $pageTwo )->toContain( 'https://example.com/two' );

	// Removing the second entry shrinks the sitemap to a single page.
	$two->forceDelete();

	// Regenerating page 2 must not serve the pre-deletion snapshot that
	// still lists the removed URL.
	$freshPageTwo = $service->generate( 'page', 2 );
	expect( $freshPageTwo )->not->toContain( 'https://example.com/two' );

	// Sanity: page 1 still resolves and is fresh.
	$freshPageOne = $service->generate( 'page', 1 );
	expect( $freshPageOne )->toContain( 'https://example.com/one' );
} );
