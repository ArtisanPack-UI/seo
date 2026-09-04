<?php

/**
 * SchemaService Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\SchemaService;
use Illuminate\Database\Eloquent\Model;

/**
 * Create a test model for schema testing.
 *
 * @param  array<string, mixed>  $attributes  Model attributes.
 *
 * @return Model
 */
function createSchemaTestModel( array $attributes = [] ): Model
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

describe( 'SchemaService', function (): void {

	beforeEach( function (): void {
		config()->set( 'seo.schema.organization', [
			'name'  => 'Test Organization',
			'logo'  => 'https://example.com/logo.png',
			'url'   => 'https://example.com',
			'email' => 'info@example.com',
			'phone' => '+1-555-555-5555',
		] );
		config()->set( 'seo.site.name', 'Test Site' );
		config()->set( 'seo.site.description', 'Test Description' );
		config()->set( 'app.name', 'Test App' );
		config()->set( 'app.url', 'https://example.com' );
	} );

	it( 'generates schema for model', function (): void {
		$service = app( SchemaService::class );

		$model = createSchemaTestModel( [
			'title' => 'Test Article',
		] );

		$schema = $service->generate( $model );

		expect( $schema )->toHaveKey( '@context' )
			->and( $schema )->toHaveKey( '@type' )
			->and( $schema['@context'] )->toBe( 'https://schema.org' );
	} );

	it( 'uses schema type from SeoMeta', function (): void {
		$service = app( SchemaService::class );

		$seoMeta = new SeoMeta( [
			'schema_type' => 'Article',
		] );

		$model = createSchemaTestModel( [
			'title' => 'Test Article',
		] );

		$schema = $service->generate( $model, $seoMeta );

		expect( $schema['@type'] )->toBe( 'Article' );
	} );

	it( 'generates organization schema', function (): void {
		$service = app( SchemaService::class );

		$schema = $service->generateOrganizationSchema();

		expect( $schema['@type'] )->toBe( 'Organization' )
			->and( $schema['name'] )->toBe( 'Test Organization' )
			->and( $schema['url'] )->toBe( 'https://example.com' );
	} );

	it( 'generates website schema', function (): void {
		$service = app( SchemaService::class );

		$schema = $service->generateWebsiteSchema();

		expect( $schema['@type'] )->toBe( 'WebSite' )
			->and( $schema['name'] )->toBe( 'Test Site' )
			->and( $schema['url'] )->toBe( 'https://example.com' );
	} );

	it( 'generates breadcrumbs schema', function (): void {
		$service = app( SchemaService::class );

		$items = [
			[ 'name' => 'Home', 'url' => 'https://example.com' ],
			[ 'name' => 'Products', 'url' => 'https://example.com/products' ],
			[ 'name' => 'Widget', 'url' => 'https://example.com/products/widget' ],
		];

		$schema = $service->generateBreadcrumbs( $items );

		expect( $schema['@type'] )->toBe( 'BreadcrumbList' )
			->and( $schema['itemListElement'] )->toHaveCount( 3 )
			->and( $schema['itemListElement'][0]['name'] )->toBe( 'Home' )
			->and( $schema['itemListElement'][0]['position'] )->toBe( 1 )
			->and( $schema['itemListElement'][2]['position'] )->toBe( 3 );
	} );

	it( 'generates graph with multiple schemas', function (): void {
		$service = app( SchemaService::class );

		$schemas = [
			[ '@type' => 'Organization', 'name' => 'Test Org' ],
			[ '@type' => 'WebSite', 'name' => 'Test Site' ],
		];

		$graph = $service->generateGraph( $schemas );

		expect( $graph )->toHaveKey( '@context' )
			->and( $graph )->toHaveKey( '@graph' )
			->and( $graph['@graph'] )->toHaveCount( 2 );
	} );

	it( 'converts schema to JSON-LD', function (): void {
		$service = app( SchemaService::class );

		$schema = [
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => 'Test',
		];

		$jsonLd = $service->toJsonLd( $schema );

		expect( $jsonLd )->toContain( '<script type="application/ld+json">' )
			->and( $jsonLd )->toContain( '</script>' )
			->and( $jsonLd )->toContain( 'https://schema.org' );
	} );

	it( 'infers article schema from Post model', function (): void {
		$service = app( SchemaService::class );

		$model = new class extends Model {
			protected $guarded = [];

			public function __construct()
			{
				parent::__construct();
			}

			public function getTable(): string
			{
				return 'posts';
			}
		};

		// Setting class basename for inference
		$schema = $service->generate( $model );

		expect( $schema['@type'] )->toBeIn( [ 'Article', 'WebPage' ] );
	} );

	it( 'falls back to WebPage for unknown model types', function (): void {
		$service = app( SchemaService::class );

		$model = createSchemaTestModel( [
			'title' => 'Test',
		] );

		$schema = $service->generate( $model );

		expect( $schema['@type'] )->toBe( 'WebPage' );
	} );

} );
