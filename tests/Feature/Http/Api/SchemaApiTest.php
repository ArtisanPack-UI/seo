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

	it( 'returns list of available schema types', function (): void {
		$response = $this->getJson( '/api/seo/schema/types' );

		$response->assertOk()
			->assertJsonStructure( [ 'data' ] );

		$types = $response->json( 'data' );

		expect( $types )->toContain( 'Article' )
			->toContain( 'WebPage' )
			->toContain( 'Product' )
			->toContain( 'Organization' );
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
