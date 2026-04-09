<?php

/**
 * SeoMeta API Tests.
 *
 * Feature tests for SEO meta API endpoints.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses( RefreshDatabase::class );

/**
 * Test model for SeoMeta API tests.
 */
class SeoMetaApiTestModel extends Model
{
	use HasSeo;

	protected $table = 'seo_api_test_pages';

	protected $fillable = [
		'title',
		'slug',
		'content',
		'excerpt',
		'description',
		'featured_image',
	];
}

beforeEach( function (): void {
	Schema::create( 'seo_api_test_pages', function ( Blueprint $table ): void {
		$table->id();
		$table->string( 'title' )->nullable();
		$table->string( 'slug' )->nullable();
		$table->text( 'content' )->nullable();
		$table->text( 'excerpt' )->nullable();
		$table->text( 'description' )->nullable();
		$table->string( 'featured_image' )->nullable();
		$table->timestamps();
	} );

	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../../database/migrations' ) ] );

	// Register morph map for test model
	Illuminate\Database\Eloquent\Relations\Relation::morphMap( [
		'seo_api_test_page' => SeoMetaApiTestModel::class,
	] );

	$this->withoutMiddleware( Illuminate\Auth\Middleware\Authenticate::class );
} );

afterEach( function (): void {
	Schema::dropIfExists( 'seo_api_test_pages' );
} );

describe( 'GET /api/seo/meta/{modelType}/{modelId}', function (): void {

	it( 'returns null data when model has no seo meta', function (): void {
		$page = SeoMetaApiTestModel::create( [ 'title' => 'Test Page' ] );

		$response = $this->getJson( '/api/seo/meta/seo_api_test_page/' . $page->id );

		$response->assertOk()
			->assertJson( [ 'data' => null ] );
	} );

	it( 'returns seo meta data when it exists', function (): void {
		$page = SeoMetaApiTestModel::create( [ 'title' => 'Test Page' ] );
		$page->seoMeta()->create( [
			'meta_title'       => 'Custom SEO Title',
			'meta_description' => 'Custom SEO description.',
			'focus_keyword'    => 'test keyword',
		] );

		$response = $this->getJson( '/api/seo/meta/seo_api_test_page/' . $page->id );

		$response->assertOk()
			->assertJsonPath( 'data.meta_title', 'Custom SEO Title' )
			->assertJsonPath( 'data.meta_description', 'Custom SEO description.' )
			->assertJsonPath( 'data.focus_keyword', 'test keyword' );
	} );

	it( 'returns 404 for non-existent model', function (): void {
		$response = $this->getJson( '/api/seo/meta/seo_api_test_page/999' );

		$response->assertNotFound();
	} );

	it( 'returns 404 for invalid model type', function (): void {
		$response = $this->getJson( '/api/seo/meta/invalid_type/1' );

		$response->assertNotFound();
	} );
} );

describe( 'PUT /api/seo/meta/{modelType}/{modelId}', function (): void {

	it( 'creates seo meta when none exists', function (): void {
		$page = SeoMetaApiTestModel::create( [ 'title' => 'Test Page' ] );

		$response = $this->putJson( '/api/seo/meta/seo_api_test_page/' . $page->id, [
			'meta_title'       => 'New SEO Title',
			'meta_description' => 'New description.',
		] );

		$response->assertSuccessful()
			->assertJsonPath( 'data.meta_title', 'New SEO Title' )
			->assertJsonPath( 'data.meta_description', 'New description.' );

		$this->assertDatabaseHas( 'seo_meta', [
			'seoable_type'     => 'seo_api_test_page',
			'seoable_id'       => $page->id,
			'meta_title'       => 'New SEO Title',
			'meta_description' => 'New description.',
		] );
	} );

	it( 'updates existing seo meta', function (): void {
		$page = SeoMetaApiTestModel::create( [ 'title' => 'Test Page' ] );
		$page->seoMeta()->create( [
			'meta_title'       => 'Old Title',
			'meta_description' => 'Old description.',
		] );

		$response = $this->putJson( '/api/seo/meta/seo_api_test_page/' . $page->id, [
			'meta_title' => 'Updated Title',
		] );

		$response->assertOk()
			->assertJsonPath( 'data.meta_title', 'Updated Title' );
	} );

	it( 'validates twitter card type', function (): void {
		$page = SeoMetaApiTestModel::create( [ 'title' => 'Test Page' ] );

		$response = $this->putJson( '/api/seo/meta/seo_api_test_page/' . $page->id, [
			'twitter_card' => 'invalid_type',
		] );

		$response->assertUnprocessable()
			->assertJsonValidationErrors( [ 'twitter_card' ] );
	} );

	it( 'validates sitemap priority range', function (): void {
		$page = SeoMetaApiTestModel::create( [ 'title' => 'Test Page' ] );

		$response = $this->putJson( '/api/seo/meta/seo_api_test_page/' . $page->id, [
			'sitemap_priority' => 2.0,
		] );

		$response->assertUnprocessable()
			->assertJsonValidationErrors( [ 'sitemap_priority' ] );
	} );

	it( 'returns 404 for non-existent model', function (): void {
		$response = $this->putJson( '/api/seo/meta/seo_api_test_page/999', [
			'meta_title' => 'Title',
		] );

		$response->assertNotFound();
	} );
} );

describe( 'GET /api/seo/meta/{modelType}/{modelId}/preview', function (): void {

	it( 'returns formatted meta tag preview', function (): void {
		$page = SeoMetaApiTestModel::create( [ 'title' => 'Test Page' ] );
		$page->seoMeta()->create( [
			'meta_title'       => 'Preview Title',
			'meta_description' => 'Preview description.',
		] );

		$response = $this->getJson( '/api/seo/meta/seo_api_test_page/' . $page->id . '/preview' );

		$response->assertOk()
			->assertJsonStructure( [
				'data' => [
					'meta',
					'openGraph',
					'twitterCard',
					'hreflang',
				],
			] );
	} );

	it( 'returns 404 for non-existent model', function (): void {
		$response = $this->getJson( '/api/seo/meta/seo_api_test_page/999/preview' );

		$response->assertNotFound();
	} );
} );
