<?php

/**
 * Schema API Tests.
 *
 * Feature tests for Schema.org API endpoints.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses( RefreshDatabase::class );

/**
 * Test model for Schema API tests.
 */
class SchemaApiTestModel extends Model
{
	use HasSeo;

	protected $table = 'schema_api_test_pages';

	protected $fillable = [
		'title',
		'slug',
		'content',
		'description',
	];
}

beforeEach( function (): void {
	Schema::create( 'schema_api_test_pages', function ( Blueprint $table ): void {
		$table->id();
		$table->string( 'title' )->nullable();
		$table->string( 'slug' )->nullable();
		$table->text( 'content' )->nullable();
		$table->text( 'description' )->nullable();
		$table->timestamps();
	} );

	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../../database/migrations' ) ] );

	Illuminate\Database\Eloquent\Relations\Relation::morphMap( [
		'schema_api_test_page' => SchemaApiTestModel::class,
	] );

	$this->withoutMiddleware( Illuminate\Auth\Middleware\Authenticate::class );
} );

afterEach( function (): void {
	Schema::dropIfExists( 'schema_api_test_pages' );
} );

describe( 'GET /api/seo/schema/types', function (): void {

	it( 'returns list of available schema types with field definitions', function (): void {
		$response = $this->getJson( '/api/seo/schema/types' );

		$response->assertOk()
			->assertJsonStructure( [
				'data' => [
					'*' => [
						'name',
						'label',
						'description',
						'fields',
					],
				],
			] );

		$types     = $response->json( 'data' );
		$typeNames = array_column( $types, 'name' );

		expect( $typeNames )->toContain( 'Article' )
			->toContain( 'WebPage' )
			->toContain( 'Product' )
			->toContain( 'Organization' );
	} );

	it( 'returns all 13 schema types', function (): void {
		$response = $this->getJson( '/api/seo/schema/types' );

		$types = $response->json( 'data' );

		expect( $types )->toHaveCount( 13 );
	} );

	it( 'includes field definitions for each type', function (): void {
		$response = $this->getJson( '/api/seo/schema/types' );

		$types = $response->json( 'data' );

		foreach ( $types as $type ) {
			expect( $type )->toHaveKey( 'fields' )
				->and( $type['fields'] )->toBeArray();
		}
	} );

	it( 'returns correct field structure for Organization type', function (): void {
		$response = $this->getJson( '/api/seo/schema/types' );

		$types = $response->json( 'data' );
		$org   = collect( $types )->firstWhere( 'name', 'Organization' );

		expect( $org )->not->toBeNull()
			->and( $org['description'] )->not->toBeEmpty();

		$fieldNames = array_column( $org['fields'], 'name' );

		expect( $fieldNames )->toContain( 'name' )
			->toContain( 'url' )
			->toContain( 'logo' )
			->toContain( 'sameAs' );
	} );

	it( 'returns required flag on fields', function (): void {
		$response = $this->getJson( '/api/seo/schema/types' );

		$types   = $response->json( 'data' );
		$product = collect( $types )->firstWhere( 'name', 'Product' );
		$fields  = collect( $product['fields'] );

		$nameField  = $fields->firstWhere( 'name', 'name' );
		$colorField = $fields->firstWhere( 'name', 'color' );

		expect( $nameField['required'] )->toBeTrue()
			->and( $colorField['required'] )->toBeFalse();
	} );

	it( 'returns options for select fields', function (): void {
		$response = $this->getJson( '/api/seo/schema/types' );

		$types  = $response->json( 'data' );
		$event  = collect( $types )->firstWhere( 'name', 'Event' );
		$fields = collect( $event['fields'] );

		$statusField = $fields->firstWhere( 'name', 'eventStatus' );

		expect( $statusField['type'] )->toBe( 'select' )
			->and( $statusField )->toHaveKey( 'options' )
			->and( $statusField['options'] )->toContain( 'Scheduled' )
			->and( $statusField['options'] )->toContain( 'Cancelled' );
	} );

	it( 'inherits parent fields for LocalBusiness', function (): void {
		$response = $this->getJson( '/api/seo/schema/types' );

		$types      = $response->json( 'data' );
		$lb         = collect( $types )->firstWhere( 'name', 'LocalBusiness' );
		$fieldNames = array_column( $lb['fields'], 'name' );

		// Inherited from Organization
		expect( $fieldNames )->toContain( 'name' )
			->toContain( 'url' );

		// LocalBusiness-specific
		expect( $fieldNames )->toContain( 'priceRange' )
			->toContain( 'openingHours' )
			->toContain( 'geo' );
	} );

	it( 'inherits parent fields for BlogPosting from Article', function (): void {
		$response = $this->getJson( '/api/seo/schema/types' );

		$types       = $response->json( 'data' );
		$blogPosting = collect( $types )->firstWhere( 'name', 'BlogPosting' );
		$fieldNames  = array_column( $blogPosting['fields'], 'name' );

		// Inherited from Article
		expect( $fieldNames )->toContain( 'headline' )
			->toContain( 'author' )
			->toContain( 'datePublished' );
	} );

	it( 'returns field type and label for each field', function (): void {
		$response = $this->getJson( '/api/seo/schema/types' );

		$types   = $response->json( 'data' );
		$article = collect( $types )->firstWhere( 'name', 'Article' );

		foreach ( $article['fields'] as $field ) {
			expect( $field )->toHaveKey( 'name' )
				->toHaveKey( 'type' )
				->toHaveKey( 'label' )
				->toHaveKey( 'required' )
				->toHaveKey( 'description' );
		}
	} );
} );

describe( 'GET /api/seo/schema/{modelType}/{modelId}', function (): void {

	it( 'returns schema configuration for a model', function (): void {
		$page = SchemaApiTestModel::create( [
			'title' => 'Test Page',
		] );

		$response = $this->getJson( '/api/seo/schema/schema_api_test_page/' . $page->id );

		$response->assertOk()
			->assertJsonStructure( [
				'data' => [
					'schema_type',
					'schema_markup',
					'generated',
				],
			] );
	} );

	it( 'returns schema with custom type when set', function (): void {
		$page = SchemaApiTestModel::create( [ 'title' => 'Test Page' ] );
		$page->seoMeta()->create( [
			'schema_type' => 'Article',
		] );

		$response = $this->getJson( '/api/seo/schema/schema_api_test_page/' . $page->id );

		$response->assertOk()
			->assertJsonPath( 'data.schema_type', 'Article' );
	} );

	it( 'returns 404 for non-existent model', function (): void {
		$response = $this->getJson( '/api/seo/schema/schema_api_test_page/999' );

		$response->assertNotFound();
	} );
} );

describe( 'PUT /api/seo/schema/{modelType}/{modelId}', function (): void {

	it( 'updates schema configuration', function (): void {
		$page = SchemaApiTestModel::create( [ 'title' => 'Test Page' ] );

		$response = $this->putJson( '/api/seo/schema/schema_api_test_page/' . $page->id, [
			'schema_type'   => 'Article',
			'schema_markup' => [ 'author' => 'John Doe' ],
		] );

		$response->assertSuccessful()
			->assertJsonPath( 'data.schema_type', 'Article' )
			->assertJsonPath( 'data.schema_markup.author', 'John Doe' );

		$this->assertDatabaseHas( 'seo_meta', [
			'seoable_id'  => $page->id,
			'schema_type' => 'Article',
		] );
	} );

	it( 'returns 404 for non-existent model', function (): void {
		$response = $this->putJson( '/api/seo/schema/schema_api_test_page/999', [
			'schema_type' => 'Article',
		] );

		$response->assertNotFound();
	} );

	it( 'validates schema type max length', function (): void {
		$page = SchemaApiTestModel::create( [ 'title' => 'Test Page' ] );

		$response = $this->putJson( '/api/seo/schema/schema_api_test_page/' . $page->id, [
			'schema_type' => str_repeat( 'a', 101 ),
		] );

		$response->assertUnprocessable()
			->assertJsonValidationErrors( [ 'schema_type' ] );
	} );
} );
