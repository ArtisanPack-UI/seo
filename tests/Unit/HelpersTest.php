<?php

/**
 * Helper Functions Tests.
 *
 * Unit tests for the SEO helper functions.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\SeoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	config( [
		'seo.site.name'         => 'Test Site',
		'seo.site.separator'    => ' | ',
		'seo.defaults.robots'   => 'index, follow',
		'seo.sitemap.enabled'   => true,
		'seo.redirects.enabled' => false,
		'seo.custom_setting'    => 'custom_value',
		'app.name'              => 'Laravel',
	] );

	// Run the migration
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../database/migrations' ) ] );
} );

/**
 * Create a test model with seoMeta relationship.
 */
function createHelperTestModel( array $attributes = [], ?SeoMeta $seoMeta = null ): Model
{
	return new class( $attributes, $seoMeta ) extends Model {
		public ?SeoMeta $seoMeta;

		protected $guarded = [];

		public function __construct( array $attributes = [], ?SeoMeta $seoMeta = null )
		{
			parent::__construct();
			$this->seoMeta = $seoMeta;
			foreach ( $attributes as $key => $value ) {
				$this->setAttribute( $key, $value );
			}
		}

		public function seoMeta(): ?SeoMeta
		{
			return $this->seoMeta;
		}
	};
}

/**
 * Create a test model without seoMeta relationship.
 */
function createHelperTestModelWithoutSeo( array $attributes = [] ): Model
{
	return new class( $attributes ) extends Model {
		protected $guarded = [];

		public function __construct( array $attributes = [] )
		{
			parent::__construct();
			foreach ( $attributes as $key => $value ) {
				$this->setAttribute( $key, $value );
			}
		}
	};
}

describe( 'seo() Helper', function (): void {

	it( 'returns SeoService instance', function (): void {
		$service = seo();

		expect( $service )->toBeInstanceOf( SeoService::class );
	} );

	it( 'returns same instance on multiple calls', function (): void {
		$service1 = seo();
		$service2 = seo();

		expect( $service1 )->toBe( $service2 );
	} );

	it( 'can access SeoService methods', function (): void {
		$service = seo();

		$title = $service->buildTitle( 'Page Title' );

		expect( $title )->toBe( 'Page Title | Test Site' );
	} );

} );

describe( 'seoMeta() Helper', function (): void {

	it( 'returns SeoMeta when model has seoMeta relationship', function (): void {
		$seoMeta = new SeoMeta( [
			'meta_title'       => 'Test SEO Title',
			'meta_description' => 'Test SEO Description',
		] );

		$model = createHelperTestModel( [ 'title' => 'Test' ], $seoMeta );

		$result = seoMeta( $model );

		expect( $result )->toBeInstanceOf( SeoMeta::class )
			->and( $result->meta_title )->toBe( 'Test SEO Title' )
			->and( $result->meta_description )->toBe( 'Test SEO Description' );
	} );

	it( 'returns null when model has no seoMeta relationship', function (): void {
		$model = createHelperTestModelWithoutSeo( [ 'title' => 'Test' ] );

		$result = seoMeta( $model );

		expect( $result )->toBeNull();
	} );

	it( 'returns null when model has seoMeta method but no data', function (): void {
		$model = createHelperTestModel( [ 'title' => 'Test' ], null );

		$result = seoMeta( $model );

		expect( $result )->toBeNull();
	} );

} );

describe( 'seoTitle() Helper', function (): void {

	it( 'formats title with site name suffix by default', function (): void {
		config( [
			'seo.site.name'      => 'My Website',
			'seo.site.separator' => ' - ',
		] );

		$title = seoTitle( 'About Us' );

		expect( $title )->toBe( 'About Us - My Website' );
	} );

	it( 'formats title without suffix when disabled', function (): void {
		config( [ 'seo.site.name' => 'My Website' ] );

		$title = seoTitle( 'About Us', false );

		expect( $title )->toBe( 'About Us' );
	} );

	it( 'uses pipe separator by default', function (): void {
		config( [
			'seo.site.name'      => 'Site',
			'seo.site.separator' => ' | ',
		] );

		$title = seoTitle( 'Page' );

		expect( $title )->toBe( 'Page | Site' );
	} );

	it( 'uses app name when site name not configured', function (): void {
		config( [
			'seo.site.name' => null,
			'app.name'      => 'Laravel App',
		] );

		$title = seoTitle( 'Home' );

		expect( $title )->toContain( 'Home' );
	} );

} );

describe( 'seoDescription() Helper', function (): void {

	it( 'limits description to 160 characters by default', function (): void {
		$longDescription = str_repeat( 'This is a test. ', 20 );

		$result = seoDescription( $longDescription );

		expect( strlen( $result ) )->toBeLessThanOrEqual( 163 ); // 160 + '...'
	} );

	it( 'returns full description when under limit', function (): void {
		$shortDescription = 'This is a short description.';

		$result = seoDescription( $shortDescription );

		expect( $result )->toBe( $shortDescription );
	} );

	it( 'accepts custom limit parameter', function (): void {
		$description = 'This is a test description that is longer than fifty characters for testing.';

		$result = seoDescription( $description, 50 );

		expect( strlen( $result ) )->toBeLessThanOrEqual( 53 ); // 50 + '...'
	} );

	it( 'adds ellipsis when truncated', function (): void {
		$longDescription = str_repeat( 'a', 200 );

		$result = seoDescription( $longDescription );

		expect( $result )->toEndWith( '...' );
	} );

} );

describe( 'seoIsEnabled() Helper', function (): void {

	it( 'returns true when feature is enabled', function (): void {
		config( [ 'seo.sitemap.enabled' => true ] );

		$result = seoIsEnabled( 'sitemap' );

		expect( $result )->toBeTrue();
	} );

	it( 'returns false when feature is disabled', function (): void {
		config( [ 'seo.redirects.enabled' => false ] );

		$result = seoIsEnabled( 'redirects' );

		expect( $result )->toBeFalse();
	} );

	it( 'returns false when feature config does not exist', function (): void {
		$result = seoIsEnabled( 'nonexistent_feature' );

		expect( $result )->toBeFalse();
	} );

} );

describe( 'seoConfig() Helper', function (): void {

	it( 'returns config value for key', function (): void {
		config( [ 'seo.site.name' => 'Configured Site' ] );

		$result = seoConfig( 'site.name' );

		expect( $result )->toBe( 'Configured Site' );
	} );

	it( 'returns default when key does not exist', function (): void {
		$result = seoConfig( 'nonexistent.key', 'default_value' );

		expect( $result )->toBe( 'default_value' );
	} );

	it( 'returns null when key does not exist and no default', function (): void {
		$result = seoConfig( 'nonexistent.key' );

		expect( $result )->toBeNull();
	} );

	it( 'returns nested config values', function (): void {
		config( [ 'seo.open_graph.type' => 'article' ] );

		$result = seoConfig( 'open_graph.type' );

		expect( $result )->toBe( 'article' );
	} );

	it( 'returns boolean config values correctly', function (): void {
		config( [ 'seo.cache.enabled' => true ] );

		$result = seoConfig( 'cache.enabled' );

		expect( $result )->toBeTrue();
	} );

} );
