<?php

/**
 * SeoService Tests.
 *
 * Unit tests for the main SeoService orchestrator.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\MetaTagsDTO;
use ArtisanPackUI\SEO\DTOs\OpenGraphDTO;
use ArtisanPackUI\SEO\DTOs\TwitterCardDTO;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\CacheService;
use ArtisanPackUI\SEO\Services\MetaTagService;
use ArtisanPackUI\SEO\Services\SeoService;
use ArtisanPackUI\SEO\Services\SocialMetaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	Cache::flush();

	config( [
		'seo.site.name'                => 'Test Site',
		'seo.site.separator'           => ' | ',
		'seo.cache.enabled'            => true,
		'seo.cache.ttl'                => 3600,
		'seo.cache.prefix'             => 'seo',
		'seo.hreflang.default_locale'  => 'en',
		'app.name'                     => 'Laravel',
	] );

	// Run the migration
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

/**
 * Create a simple test model class.
 */
function createSeoTestModel( array $attributes = [], ?int $key = null ): Model
{
	return new class( $attributes, $key ) extends Model {
		protected $guarded = [];

		protected ?int $modelKey;

		public function __construct( array $attributes = [], ?int $key = null )
		{
			parent::__construct();
			$this->modelKey = $key;
			foreach ( $attributes as $attrKey => $value ) {
				$this->setAttribute( $attrKey, $value );
			}
		}

		public function getKey(): mixed
		{
			return $this->modelKey ?? 1;
		}
	};
}

/**
 * Create a test model with seoMeta relationship.
 */
function createSeoTestModelWithMeta( array $attributes = [], ?SeoMeta $seoMeta = null, ?int $key = null ): Model
{
	return new class( $attributes, $seoMeta, $key ) extends Model {
		public ?SeoMeta $seoMeta;

		protected $guarded = [];

		protected ?int $modelKey;

		public function __construct( array $attributes = [], ?SeoMeta $seoMeta = null, ?int $key = null )
		{
			parent::__construct();
			$this->modelKey = $key;
			$this->seoMeta  = $seoMeta;
			$this->exists   = true; // Simulate persisted model
			foreach ( $attributes as $attrKey => $value ) {
				$this->setAttribute( $attrKey, $value );
			}
		}

		public function getKey(): mixed
		{
			return $this->modelKey ?? 1;
		}

		public function seoMeta(): ?SeoMeta
		{
			return $this->seoMeta;
		}
	};
}

describe( 'SeoService Instantiation', function (): void {

	it( 'can be instantiated with dependencies', function (): void {
		$metaTagService     = new MetaTagService();
		$socialMetaService  = new SocialMetaService();
		$cacheService       = new CacheService();

		$service = new SeoService( $metaTagService, $socialMetaService, $cacheService );

		expect( $service )->toBeInstanceOf( SeoService::class );
	} );

	it( 'can be resolved from service container', function (): void {
		$service = app( SeoService::class );

		expect( $service )->toBeInstanceOf( SeoService::class );
	} );

} );

describe( 'SeoService getMetaTags', function (): void {

	it( 'returns MetaTagsDTO for model', function (): void {
		$service = app( SeoService::class );

		$model = createSeoTestModelWithMeta( [
			'title' => 'Test Title',
			'slug'  => 'test-slug',
		] );

		$dto = $service->getMetaTags( $model );

		expect( $dto )->toBeInstanceOf( MetaTagsDTO::class )
			->and( $dto->title )->toContain( 'Test Title' );
	} );

} );

describe( 'SeoService getOpenGraph', function (): void {

	it( 'returns OpenGraphDTO for model', function (): void {
		$service = app( SeoService::class );

		$model = createSeoTestModelWithMeta( [
			'title' => 'OG Title',
			'slug'  => 'og-slug',
		] );

		$dto = $service->getOpenGraph( $model );

		expect( $dto )->toBeInstanceOf( OpenGraphDTO::class )
			->and( $dto->title )->toBe( 'OG Title' );
	} );

} );

describe( 'SeoService getTwitterCard', function (): void {

	it( 'returns TwitterCardDTO for model', function (): void {
		$service = app( SeoService::class );

		$model = createSeoTestModelWithMeta( [
			'title' => 'Twitter Title',
			'slug'  => 'twitter-slug',
		] );

		$dto = $service->getTwitterCard( $model );

		expect( $dto )->toBeInstanceOf( TwitterCardDTO::class )
			->and( $dto->title )->toBe( 'Twitter Title' );
	} );

} );

describe( 'SeoService getHreflang', function (): void {

	it( 'returns empty array when model has no seo meta', function (): void {
		$service = app( SeoService::class );

		$model = createSeoTestModelWithMeta( [
			'title' => 'Title',
		] );

		$hreflang = $service->getHreflang( $model );

		expect( $hreflang )->toBe( [] );
	} );

	it( 'returns hreflang array from seo meta', function (): void {
		$service = app( SeoService::class );

		$seoMeta = new SeoMeta( [
			'hreflang' => [
				'en' => 'https://example.com/en',
				'es' => 'https://example.com/es',
				'fr' => 'https://example.com/fr',
			],
		] );

		$model = createSeoTestModelWithMeta( [
			'title' => 'Title',
		], $seoMeta );

		$hreflang = $service->getHreflang( $model );

		expect( $hreflang )->toHaveCount( 4 ) // 3 languages + x-default
			->and( $hreflang[0] )->toBe( [ 'hreflang' => 'en', 'href' => 'https://example.com/en' ] )
			->and( $hreflang[1] )->toBe( [ 'hreflang' => 'es', 'href' => 'https://example.com/es' ] );
	} );

	it( 'adds x-default when default locale is configured', function (): void {
		config( [ 'seo.hreflang.default_locale' => 'en' ] );
		$service = app( SeoService::class );

		$seoMeta = new SeoMeta( [
			'hreflang' => [
				'en' => 'https://example.com/en',
				'es' => 'https://example.com/es',
			],
		] );

		$model = createSeoTestModelWithMeta( [
			'title' => 'Title',
		], $seoMeta );

		$hreflang = $service->getHreflang( $model );

		$xDefault = collect( $hreflang )->firstWhere( 'hreflang', 'x-default' );

		expect( $xDefault )->not->toBeNull()
			->and( $xDefault['href'] )->toBe( 'https://example.com/en' );
	} );

} );

describe( 'SeoService getAll', function (): void {

	it( 'returns all SEO data for model', function (): void {
		$service = app( SeoService::class );

		$model = createSeoTestModelWithMeta( [
			'title' => 'Test Title',
			'slug'  => 'test-slug',
		], null, 1 );

		$all = $service->getAll( $model );

		expect( $all )->toHaveKeys( [ 'meta', 'openGraph', 'twitterCard', 'hreflang' ] )
			->and( $all['meta'] )->toBeArray()
			->and( $all['openGraph'] )->toBeArray()
			->and( $all['twitterCard'] )->toBeArray()
			->and( $all['hreflang'] )->toBeArray();
	} );

	it( 'caches getAll results', function (): void {
		$service = app( SeoService::class );

		$model = createSeoTestModelWithMeta( [
			'title' => 'Cached Title',
			'slug'  => 'cached-slug',
		], null, 999 );

		// First call
		$result1 = $service->getAll( $model );

		// Second call - should be from cache
		$result2 = $service->getAll( $model );

		expect( $result1 )->toBe( $result2 );
	} );

} );

describe( 'SeoService Title Methods', function (): void {

	it( 'returns title suffix', function (): void {
		config( [ 'seo.site.name' => 'My Site Name' ] );
		$service = app( SeoService::class );

		expect( $service->getTitleSuffix() )->toBe( 'My Site Name' );
	} );

	it( 'returns title separator', function (): void {
		config( [ 'seo.site.separator' => ' - ' ] );
		$service = app( SeoService::class );

		expect( $service->getTitleSeparator() )->toBe( ' - ' );
	} );

	it( 'builds title with suffix', function (): void {
		config( [
			'seo.site.name'      => 'Site Name',
			'seo.site.separator' => ' | ',
		] );
		$service = app( SeoService::class );

		$title = $service->buildTitle( 'Page Title' );

		expect( $title )->toBe( 'Page Title | Site Name' );
	} );

	it( 'builds title without suffix', function (): void {
		config( [ 'seo.site.name' => 'Site Name' ] );
		$service = app( SeoService::class );

		$title = $service->buildTitle( 'Page Title', false );

		expect( $title )->toBe( 'Page Title' );
	} );

} );

describe( 'SeoService Cache Operations', function (): void {

	it( 'clears cache for model', function (): void {
		$service = app( SeoService::class );

		$model = createSeoTestModel( [
			'title' => 'Title',
		], 1 );

		// This should not throw an exception
		$service->clearCache( $model );

		expect( true )->toBeTrue();
	} );

	it( 'clears all caches', function (): void {
		$service = app( SeoService::class );

		// This should not throw an exception
		$service->clearAllCaches();

		expect( true )->toBeTrue();
	} );

} );

describe( 'SeoService updateSeoMeta', function (): void {

	it( 'creates seo meta when none exists', function (): void {
		$service = app( SeoService::class );

		$model = createSeoTestModelWithMeta( [
			'title' => 'Title',
		], null, 100 );

		$seoMeta = $service->updateSeoMeta( $model, [
			'meta_title'       => 'New SEO Title',
			'meta_description' => 'New SEO Description',
		] );

		expect( $seoMeta )->toBeInstanceOf( SeoMeta::class )
			->and( $seoMeta->meta_title )->toBe( 'New SEO Title' )
			->and( $seoMeta->meta_description )->toBe( 'New SEO Description' );
	} );

	it( 'updates existing seo meta', function (): void {
		$service = app( SeoService::class );

		// Create initial seo meta
		$existingSeoMeta = SeoMeta::create( [
			'seoable_type'     => 'App\Models\Page',
			'seoable_id'       => 200,
			'meta_title'       => 'Original Title',
			'meta_description' => 'Original Description',
		] );

		$model = createSeoTestModelWithMeta( [
			'title' => 'Title',
		], $existingSeoMeta, 200 );

		$seoMeta = $service->updateSeoMeta( $model, [
			'meta_title' => 'Updated Title',
		] );

		expect( $seoMeta->meta_title )->toBe( 'Updated Title' )
			->and( $seoMeta->meta_description )->toBe( 'Original Description' );
	} );

} );

describe( 'SeoService getSeoMeta', function (): void {

	it( 'returns seo meta from model with seoMeta method', function (): void {
		$service = app( SeoService::class );

		$seoMeta = new SeoMeta( [
			'meta_title' => 'Test Title',
		] );

		$model = createSeoTestModelWithMeta( [
			'title' => 'Title',
		], $seoMeta );

		$result = $service->getSeoMeta( $model );

		expect( $result )->toBeInstanceOf( SeoMeta::class )
			->and( $result->meta_title )->toBe( 'Test Title' );
	} );

	it( 'returns null when model does not have seoMeta method', function (): void {
		$service = app( SeoService::class );

		$model = createSeoTestModel( [
			'title' => 'Title',
		] );

		$result = $service->getSeoMeta( $model );

		expect( $result )->toBeNull();
	} );

} );
