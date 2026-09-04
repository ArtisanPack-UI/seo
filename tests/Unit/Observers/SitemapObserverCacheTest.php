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
use ArtisanPackUI\SEO\Services\SitemapService;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

	// Fetching from cache directly should return null after invalidation.
	$cached = Cache::get( 'seo:sitemap:standard' );
	expect( $cached )->toBeNull();

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

	expect( Cache::get( 'seo:sitemap:standard' ) )->toBeNull();

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
	// still accurate and must not be flushed (which would force a needless
	// regeneration on the next request).
	$cached = Cache::get( 'seo:sitemap:standard' );
	expect( $cached )->toContain( 'https://example.com/soft' );
} );

it( 'invalidates cached sitemap XML when a soft-deleted model is restored', function (): void {
	$service = new SitemapService();

	$page = SitemapObserverTestPage::create( [ 'title' => 'Coming Back', 'slug' => 'coming-back' ] );
	$page->delete();

	// Soft delete should not touch the entry so cache remains valid; prime it.
	$initial = $service->generate();
	expect( $initial )->toContain( 'https://example.com/coming-back' );

	// Remove the entry manually to simulate a state where the sitemap does
	// not currently include the URL, then restore.
	SitemapEntry::query()->delete();
	Cache::forget( 'seo:sitemap:standard' );
	$snapshotWithout = $service->generate();
	expect( $snapshotWithout )->not->toContain( 'https://example.com/coming-back' );

	$page->restore();

	expect( Cache::get( 'seo:sitemap:standard' ) )->toBeNull();

	$fresh = $service->generate();
	expect( $fresh )->toContain( 'https://example.com/coming-back' );
} );
