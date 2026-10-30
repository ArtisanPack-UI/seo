<?php

/**
 * PublicUrlResolver Tests.
 *
 * Verifies the tier order for a model's public URL, and that every service
 * reports the same URL.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.9.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\HasPublicUrl;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use ArtisanPackUI\SEO\Services\MetaTagService;
use ArtisanPackUI\SEO\Services\SchemaService;
use ArtisanPackUI\SEO\Services\SocialMetaService;
use ArtisanPackUI\SEO\Support\PublicUrlResolver;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

uses( RefreshDatabase::class );

/**
 * Model with a slug and no declared URL.
 */
class PublicUrlTestPage extends Model
{
	use HasSeo;

	protected $table = 'public_url_test_pages';

	protected $guarded = [];
}

/**
 * Model declaring a URL through every tier.
 */
class PublicUrlTestContractPage extends PublicUrlTestPage implements HasPublicUrl
{
	public function getPublicUrl(): string
	{
		return 'https://example.com/from-contract';
	}

	public function getUrl(): string
	{
		return 'https://example.com/from-get-url';
	}

	public function getPermalinkAttribute(): string
	{
		return 'https://example.com/from-permalink';
	}
}

/**
 * Model declaring a URL through getUrl() and a permalink.
 */
class PublicUrlTestGetUrlPage extends PublicUrlTestPage
{
	public function getUrl(): string
	{
		return 'https://example.com/from-get-url';
	}

	public function getPermalinkAttribute(): string
	{
		return 'https://example.com/from-permalink';
	}
}

/**
 * Model declaring a URL through a permalink only.
 */
class PublicUrlTestPermalinkPage extends PublicUrlTestPage
{
	public function getPermalinkAttribute(): string
	{
		return 'https://example.com/packages/seo';
	}
}

/**
 * Model whose higher tiers have nothing to offer.
 */
class PublicUrlTestEmptyTiersPage extends PublicUrlTestPermalinkPage implements HasPublicUrl
{
	public function getPublicUrl(): string
	{
		return '';
	}

	public function getUrl(): ?string
	{
		return null;
	}
}

/**
 * Pretend the current process is serving an HTTP request.
 *
 * @param  string  $url  The URL being requested.
 *
 * @return void
 */
function simulatePublicUrlRequest( string $url ): void
{
	( new ReflectionProperty( app(), 'isRunningInConsole' ) )->setValue( app(), false );

	$request = Request::create( $url );

	app()->instance( 'request', $request );
	app( 'url' )->setRequest( $request );
}

beforeEach( function (): void {
	Schema::create( 'public_url_test_pages', function ( Blueprint $table ): void {
		$table->id();
		$table->string( 'title' )->nullable();
		$table->string( 'slug' )->nullable();
		$table->string( 'permalink' )->nullable();
		$table->timestamps();
	} );

	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

describe( 'PublicUrlResolver tier order', function (): void {

	it( 'prefers the saved canonical URL', function (): void {
		$seoMeta = new SeoMeta( [ 'canonical_url' => 'https://example.com/saved' ] );

		expect( PublicUrlResolver::resolve( new PublicUrlTestContractPage(), $seoMeta ) )
			->toBe( 'https://example.com/saved' );
	} );

	it( 'skips an empty saved canonical URL', function (): void {
		$seoMeta = new SeoMeta( [ 'canonical_url' => '' ] );

		expect( PublicUrlResolver::resolve( new PublicUrlTestContractPage(), $seoMeta ) )
			->toBe( 'https://example.com/from-contract' );
	} );

	it( 'prefers the contract over getUrl() and permalink', function (): void {
		expect( PublicUrlResolver::resolve( new PublicUrlTestContractPage() ) )
			->toBe( 'https://example.com/from-contract' );
	} );

	it( 'prefers getUrl() over permalink', function (): void {
		expect( PublicUrlResolver::resolve( new PublicUrlTestGetUrlPage() ) )
			->toBe( 'https://example.com/from-get-url' );
	} );

	it( 'uses a permalink accessor', function (): void {
		expect( PublicUrlResolver::resolve( new PublicUrlTestPermalinkPage() ) )
			->toBe( 'https://example.com/packages/seo' );
	} );

	it( 'uses a permalink column and makes a path absolute', function (): void {
		$page = new PublicUrlTestPage( [ 'permalink' => '/about/team' ] );

		expect( PublicUrlResolver::resolve( $page ) )->toBe( url( '/about/team' ) );
	} );

	it( 'falls past tiers that return nothing usable', function (): void {
		expect( PublicUrlResolver::resolve( new PublicUrlTestEmptyTiersPage() ) )
			->toBe( 'https://example.com/packages/seo' );
	} );

	it( 'falls back to the request URL', function (): void {
		simulatePublicUrlRequest( 'https://example.com/packages/seo' );

		expect( PublicUrlResolver::resolve( new PublicUrlTestPage() ) )
			->toBe( 'https://example.com/packages/seo' );
	} );

	it( 'returns an empty string outside a request', function (): void {
		expect( PublicUrlResolver::resolve( new PublicUrlTestPage() ) )->toBe( '' );
	} );

	it( 'never builds a URL from a slug', function (): void {
		simulatePublicUrlRequest( 'https://example.com/packages/seo' );

		expect( PublicUrlResolver::resolve( new PublicUrlTestPage( [ 'slug' => 'seo' ] ) ) )
			->toBe( 'https://example.com/packages/seo' );
	} );

	it( 'leaves the request out of a model URL', function (): void {
		simulatePublicUrlRequest( 'https://example.com/admin/pages/1/edit' );

		expect( PublicUrlResolver::resolveModelUrl( new PublicUrlTestPage() ) )->toBeNull();
	} );

} );

describe( 'Shared public URL', function (): void {

	it( 'gives every service the same URL', function (): void {
		$page = PublicUrlTestContractPage::create( [ 'title' => 'SEO', 'slug' => 'seo' ] );

		$page->seoMeta()->create( [
			'canonical_url' => 'https://example.com/packages/seo',
			'schema_type'   => 'WebPage',
		] );
		$page->refresh();

		$seoMeta = $page->seoMeta;

		expect( app( MetaTagService::class )->generate( $page, $seoMeta )->canonical )->toBe( 'https://example.com/packages/seo' )
			->and( app( SocialMetaService::class )->generateOpenGraph( $page, $seoMeta )->url )->toBe( 'https://example.com/packages/seo' )
			->and( app( SchemaService::class )->generate( $page, $seoMeta )['url'] )->toBe( 'https://example.com/packages/seo' )
			->and( $page->canonical_url )->toBe( 'https://example.com/packages/seo' );
	} );

	it( 'leaves the schema URL out when there is none', function (): void {
		$page = PublicUrlTestPage::create( [ 'title' => 'SEO', 'slug' => 'seo' ] );

		$schema = app( SchemaService::class )->generate( $page, new SeoMeta( [ 'schema_type' => 'WebPage' ] ) );

		expect( $schema )->not->toHaveKey( 'url' );
	} );

} );

describe( 'Sitemap URLs', function (): void {

	it( 'stores the model URL', function (): void {
		$page = PublicUrlTestPermalinkPage::create( [ 'title' => 'SEO', 'slug' => 'seo' ] );

		expect( SitemapEntry::where( 'sitemapable_id', $page->id )->value( 'url' ) )
			->toBe( 'https://example.com/packages/seo' );
	} );

	it( 'skips a model with only a slug', function (): void {
		PublicUrlTestPage::create( [ 'title' => 'SEO', 'slug' => 'seo' ] );

		expect( SitemapEntry::count() )->toBe( 0 );
	} );

	it( 'never stores the admin URL that saved the model', function (): void {
		simulatePublicUrlRequest( 'https://example.com/admin/pages/1/edit' );

		PublicUrlTestPage::create( [ 'title' => 'SEO' ] );

		expect( SitemapEntry::count() )->toBe( 0 );
	} );

	it( 'refuses to create an entry from the request URL', function (): void {
		$page = PublicUrlTestPage::create( [ 'title' => 'SEO' ] );

		simulatePublicUrlRequest( 'https://example.com/admin/pages/1/edit' );

		expect( fn () => $page->getOrCreateSitemapEntry() )->toThrow( InvalidArgumentException::class );
	} );

} );
