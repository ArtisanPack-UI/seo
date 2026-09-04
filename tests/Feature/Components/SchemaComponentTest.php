<?php

/**
 * Schema Component Tests.
 *
 * Feature tests for the Schema Blade component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\View\Components\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	config( [
		'seo.schema.organization'       => [
			'name'  => 'Test Organization',
			'logo'  => 'https://example.com/logo.png',
			'url'   => 'https://example.com',
			'email' => 'info@example.com',
			'phone' => '+1-555-555-5555',
		],
		'seo.site.name'                 => 'Test Site',
		'seo.site.description'          => 'Test Site Description',
		'seo.schema.default_types'      => [],
		'app.name'                      => 'Test App',
		'app.url'                       => 'https://example.com',
	] );

	// Run the migration
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

/**
 * Extract and parse JSON-LD from rendered HTML.
 *
 * @param  string  $html  The rendered HTML containing JSON-LD script tags.
 *
 * @return array<string, mixed>|null  The parsed JSON data, or null if not found.
 */
function extractJsonLd( string $html ): ?array
{
	preg_match( '/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches );
	$json = $matches[1] ?? '';

	if ( '' === $json ) {
		return null;
	}

	return json_decode( $json, true );
}

/**
 * Find a schema by type in a JSON-LD structure (handles @graph).
 *
 * @param  array<string, mixed>  $jsonLd  The parsed JSON-LD data.
 * @param  string                $type    The schema type to find.
 *
 * @return array<string, mixed>|null  The found schema, or null if not found.
 */
function findSchemaByType( array $jsonLd, string $type ): ?array
{
	// Check if this is a graph structure
	if ( isset( $jsonLd['@graph'] ) ) {
		foreach ( $jsonLd['@graph'] as $schema ) {
			if ( isset( $schema['@type'] ) && $schema['@type'] === $type ) {
				return $schema;
			}
		}

		return null;
	}

	// Single schema
	if ( isset( $jsonLd['@type'] ) && $jsonLd['@type'] === $type ) {
		return $jsonLd;
	}

	return null;
}

/**
 * Create a test model for schema testing.
 */
function createSchemaComponentTestModel( array $attributes = [], ?SeoMeta $seoMeta = null, ?int $key = null ): Model
{
	return new class( $attributes, $seoMeta, $key ) extends Model {
		public ?SeoMeta $seoMeta;

		protected $guarded = [];

		protected ?int $modelKey;

		public function __construct( array $attributes = [], ?SeoMeta $seoMeta = null, ?int $key = null )
		{
			parent::__construct();
			$this->seoMeta  = $seoMeta;
			$this->modelKey = $key;
			$this->exists   = true;
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

describe( 'Schema Component Rendering', function (): void {

	it( 'renders valid JSON-LD script tag', function (): void {
		$component = new Schema(
			includeOrganization: true,
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<script type="application/ld+json">' )
			->and( $html )->toContain( '</script>' )
			->and( $html )->toContain( 'https://schema.org' );
	} );

	it( 'renders organization schema when requested', function (): void {
		$component = new Schema(
			includeOrganization: true,
		);
		$view   = $component->render();
		$html   = $view->with( $component->data() )->render();
		$jsonLd = extractJsonLd( $html );

		expect( $jsonLd )->not->toBeNull();

		$orgSchema = findSchemaByType( $jsonLd, 'Organization' );

		expect( $orgSchema )->not->toBeNull()
			->and( $orgSchema['name'] )->toBe( 'Test Organization' )
			->and( $orgSchema['url'] )->toBe( 'https://example.com' );
	} );

	it( 'renders website schema when requested', function (): void {
		$component = new Schema(
			includeWebsite: true,
		);
		$view   = $component->render();
		$html   = $view->with( $component->data() )->render();
		$jsonLd = extractJsonLd( $html );

		expect( $jsonLd )->not->toBeNull();

		$websiteSchema = findSchemaByType( $jsonLd, 'WebSite' );

		expect( $websiteSchema )->not->toBeNull()
			->and( $websiteSchema['name'] )->toBe( 'Test Site' );
	} );

	it( 'renders both organization and website schemas', function (): void {
		$component = new Schema(
			includeOrganization: true,
			includeWebsite: true,
		);
		$view   = $component->render();
		$html   = $view->with( $component->data() )->render();
		$jsonLd = extractJsonLd( $html );

		expect( $jsonLd )->not->toBeNull()
			->and( $jsonLd )->toHaveKey( '@graph' );

		$orgSchema     = findSchemaByType( $jsonLd, 'Organization' );
		$websiteSchema = findSchemaByType( $jsonLd, 'WebSite' );

		expect( $orgSchema )->not->toBeNull()
			->and( $websiteSchema )->not->toBeNull();
	} );

	it( 'returns empty string when no schemas', function (): void {
		$component = new Schema();

		expect( $component->getJsonLd() )->toBe( '' );
	} );

} );

describe( 'Schema Component with Model', function (): void {

	it( 'generates schema from model', function (): void {
		$model = createSchemaComponentTestModel( [
			'title'       => 'Test Page',
			'description' => 'Test page description',
			'slug'        => 'test-page',
		] );

		$component = new Schema( model: $model );
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();
		$jsonLd    = extractJsonLd( $html );

		expect( $html )->toContain( '<script type="application/ld+json">' )
			->and( $jsonLd )->not->toBeNull()
			->and( $jsonLd['name'] )->toBe( 'Test Page' );
	} );

	it( 'uses schema type from SeoMeta', function (): void {
		$seoMeta = new SeoMeta( [
			'schema_type' => 'Article',
			'meta_title'  => 'Article Title',
		] );

		$model = createSchemaComponentTestModel( [
			'title' => 'Test Article',
			'slug'  => 'test-article',
		], $seoMeta );

		$component = new Schema( model: $model );
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();
		$jsonLd    = extractJsonLd( $html );

		$articleSchema = findSchemaByType( $jsonLd, 'Article' );

		expect( $articleSchema )->not->toBeNull();
	} );

	it( 'includes model schema with organization', function (): void {
		$model = createSchemaComponentTestModel( [
			'title' => 'Test Page',
			'slug'  => 'test-page',
		] );

		$component = new Schema(
			model: $model,
			includeOrganization: true,
		);
		$view   = $component->render();
		$html   = $view->with( $component->data() )->render();
		$jsonLd = extractJsonLd( $html );

		expect( $jsonLd )->not->toBeNull()
			->and( $jsonLd )->toHaveKey( '@graph' );

		$orgSchema  = findSchemaByType( $jsonLd, 'Organization' );
		$pageSchema = findSchemaByType( $jsonLd, 'WebPage' );

		expect( $orgSchema )->not->toBeNull()
			->and( $pageSchema )->not->toBeNull();
	} );

} );

describe( 'Schema Component with Custom Schemas', function (): void {

	it( 'renders custom schema array', function (): void {
		$customSchema = [
			'@type' => 'Product',
			'name'  => 'Test Product',
			'sku'   => 'SKU-123',
		];

		$component = new Schema( schemas: [ $customSchema ] );
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();
		$jsonLd    = extractJsonLd( $html );

		$productSchema = findSchemaByType( $jsonLd, 'Product' );

		expect( $productSchema )->not->toBeNull()
			->and( $productSchema['name'] )->toBe( 'Test Product' )
			->and( $productSchema['sku'] )->toBe( 'SKU-123' );
	} );

	it( 'renders multiple custom schemas', function (): void {
		$productSchema = [
			'@type' => 'Product',
			'name'  => 'Test Product',
		];

		$reviewSchema = [
			'@type'      => 'Review',
			'reviewBody' => 'Great product!',
		];

		$component = new Schema( schemas: [ $productSchema, $reviewSchema ] );
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();
		$jsonLd    = extractJsonLd( $html );

		expect( $jsonLd )->not->toBeNull()
			->and( $jsonLd )->toHaveKey( '@graph' );

		$product = findSchemaByType( $jsonLd, 'Product' );
		$review  = findSchemaByType( $jsonLd, 'Review' );

		expect( $product )->not->toBeNull()
			->and( $product['name'] )->toBe( 'Test Product' )
			->and( $review )->not->toBeNull()
			->and( $review['reviewBody'] )->toBe( 'Great product!' );
	} );

	it( 'adds @context to custom schemas without it', function (): void {
		$customSchema = [
			'@type' => 'Product',
			'name'  => 'Test',
		];

		$component = new Schema(
			schemas: [ $customSchema ],
			useGraph: false,
		);

		expect( $component->collectedSchemas[0]['@context'] )->toBe( 'https://schema.org' );
	} );

	it( 'preserves @context in custom schemas', function (): void {
		$customSchema = [
			'@context' => 'https://schema.org',
			'@type'    => 'Product',
			'name'     => 'Test',
		];

		$component = new Schema(
			schemas: [ $customSchema ],
			useGraph: false,
		);

		expect( $component->collectedSchemas[0]['@context'] )->toBe( 'https://schema.org' );
	} );

} );

describe( 'Schema Component Breadcrumbs', function (): void {

	it( 'renders breadcrumb schema', function (): void {
		$breadcrumbs = [
			[ 'name' => 'Home', 'url' => 'https://example.com' ],
			[ 'name' => 'Products', 'url' => 'https://example.com/products' ],
			[ 'name' => 'Widget', 'url' => 'https://example.com/products/widget' ],
		];

		$component = new Schema( breadcrumbs: $breadcrumbs );
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();
		$jsonLd    = extractJsonLd( $html );

		expect( $jsonLd )->not->toBeNull();

		$breadcrumbSchema = findSchemaByType( $jsonLd, 'BreadcrumbList' );

		expect( $breadcrumbSchema )->not->toBeNull()
			->and( $breadcrumbSchema['itemListElement'] )->toBeArray()
			->and( $breadcrumbSchema['itemListElement'] )->toHaveCount( 3 );

		$firstItem = $breadcrumbSchema['itemListElement'][0];
		$lastItem  = $breadcrumbSchema['itemListElement'][2];

		expect( $firstItem['@type'] )->toBe( 'ListItem' )
			->and( $firstItem['name'] )->toBe( 'Home' )
			->and( $firstItem['position'] )->toBe( 1 )
			->and( $lastItem['position'] )->toBe( 3 );
	} );

	it( 'combines breadcrumbs with organization schema', function (): void {
		$breadcrumbs = [
			[ 'name' => 'Home', 'url' => 'https://example.com' ],
			[ 'name' => 'About', 'url' => 'https://example.com/about' ],
		];

		$component = new Schema(
			breadcrumbs: $breadcrumbs,
			includeOrganization: true,
		);
		$view   = $component->render();
		$html   = $view->with( $component->data() )->render();
		$jsonLd = extractJsonLd( $html );

		expect( $jsonLd )->not->toBeNull()
			->and( $jsonLd )->toHaveKey( '@graph' );

		$breadcrumbSchema = findSchemaByType( $jsonLd, 'BreadcrumbList' );
		$orgSchema        = findSchemaByType( $jsonLd, 'Organization' );

		expect( $breadcrumbSchema )->not->toBeNull()
			->and( $orgSchema )->not->toBeNull();
	} );

} );

describe( 'Schema Component Graph Mode', function (): void {

	it( 'uses graph wrapper for multiple schemas by default', function (): void {
		$component = new Schema(
			includeOrganization: true,
			includeWebsite: true,
		);
		$view   = $component->render();
		$html   = $view->with( $component->data() )->render();
		$jsonLd = extractJsonLd( $html );

		expect( $jsonLd )->not->toBeNull()
			->and( $jsonLd )->toHaveKey( '@graph' );
	} );

	it( 'can disable graph mode', function (): void {
		$component = new Schema(
			includeOrganization: true,
			includeWebsite: true,
			useGraph: false,
		);
		$view   = $component->render();
		$html   = $view->with( $component->data() )->render();
		$jsonLd = extractJsonLd( $html );

		// Should output separate script tags without @graph
		expect( $jsonLd )->not->toBeNull()
			->and( $jsonLd )->not->toHaveKey( '@graph' );
	} );

	it( 'does not use graph for single schema without flag', function (): void {
		$component = new Schema(
			includeOrganization: true,
			useGraph: false,
		);
		$view   = $component->render();
		$html   = $view->with( $component->data() )->render();
		$jsonLd = extractJsonLd( $html );

		expect( $jsonLd )->not->toBeNull()
			->and( $jsonLd )->not->toHaveKey( '@graph' );
	} );

} );

describe( 'Schema Component JSON Escaping', function (): void {

	it( 'properly escapes special characters', function (): void {
		$customSchema = [
			'@type'       => 'Product',
			'name'        => 'Test "Quoted" Product',
			'description' => 'Description with <html> & special chars',
		];

		$component = new Schema(
			schemas: [ $customSchema ],
			useGraph: false,
		);
		$view   = $component->render();
		$html   = $view->with( $component->data() )->render();
		$jsonLd = extractJsonLd( $html );

		// Verify JSON parsing succeeds (proves proper escaping)
		expect( $jsonLd )->not->toBeNull()
			->and( $jsonLd['name'] )->toBe( 'Test "Quoted" Product' )
			->and( $jsonLd['description'] )->toBe( 'Description with <html> & special chars' );
	} );

	it( 'generates valid JSON output', function (): void {
		$component = new Schema(
			includeOrganization: true,
			includeWebsite: true,
		);
		$jsonLd = $component->getJsonLd();

		// Extract JSON from script tag
		preg_match( '/<script type="application\/ld\+json">(.*?)<\/script>/s', $jsonLd, $matches );
		$json = $matches[1] ?? '';

		$decoded = json_decode( $json, true );

		expect( $decoded )->not->toBeNull()
			->and( json_last_error() )->toBe( JSON_ERROR_NONE );
	} );

} );
