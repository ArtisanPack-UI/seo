<?php

/**
 * Redirect API Tests.
 *
 * Feature tests for redirect API endpoints.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../../database/migrations' ) ] );

	config( [ 'seo.redirects.cache_enabled' => false ] );

	$this->withoutMiddleware( Illuminate\Auth\Middleware\Authenticate::class );
} );

describe( 'GET /api/seo/redirects', function (): void {

	it( 'returns paginated list of redirects', function (): void {
		Redirect::create( [
			'from_path'   => '/old-page',
			'to_path'     => '/new-page',
			'status_code' => 301,
			'match_type'  => 'exact',
		] );

		$response = $this->getJson( '/api/seo/redirects' );

		$response->assertOk()
			->assertJsonCount( 1, 'data' )
			->assertJsonPath( 'data.0.from_path', '/old-page' );
	} );

	it( 'filters by status code', function (): void {
		Redirect::create( [ 'from_path' => '/a', 'to_path' => '/b', 'status_code' => 301 ] );
		Redirect::create( [ 'from_path' => '/c', 'to_path' => '/d', 'status_code' => 302 ] );

		$response = $this->getJson( '/api/seo/redirects?status_code=301' );

		$response->assertOk()
			->assertJsonCount( 1, 'data' )
			->assertJsonPath( 'data.0.status_code', 301 );
	} );

	it( 'filters by match type', function (): void {
		Redirect::create( [ 'from_path' => '/a', 'to_path' => '/b', 'match_type' => 'exact' ] );
		Redirect::create( [ 'from_path' => '/c/*', 'to_path' => '/d/*', 'match_type' => 'wildcard' ] );

		$response = $this->getJson( '/api/seo/redirects?match_type=wildcard' );

		$response->assertOk()
			->assertJsonCount( 1, 'data' )
			->assertJsonPath( 'data.0.match_type', 'wildcard' );
	} );

	it( 'filters by active status', function (): void {
		Redirect::create( [ 'from_path' => '/a', 'to_path' => '/b', 'is_active' => true ] );
		Redirect::create( [ 'from_path' => '/c', 'to_path' => '/d', 'is_active' => false ] );

		$response = $this->getJson( '/api/seo/redirects?is_active=1' );

		$response->assertOk()
			->assertJsonCount( 1, 'data' );
	} );

	it( 'searches by path', function (): void {
		Redirect::create( [ 'from_path' => '/old-blog', 'to_path' => '/blog' ] );
		Redirect::create( [ 'from_path' => '/other', 'to_path' => '/elsewhere' ] );

		$response = $this->getJson( '/api/seo/redirects?search=blog' );

		$response->assertOk()
			->assertJsonCount( 1, 'data' );
	} );
} );

describe( 'POST /api/seo/redirects', function (): void {

	it( 'creates a new redirect', function (): void {
		$response = $this->postJson( '/api/seo/redirects', [
			'from_path'   => '/old-page',
			'to_path'     => '/new-page',
			'status_code' => 301,
			'match_type'  => 'exact',
		] );

		$response->assertCreated()
			->assertJsonPath( 'data.from_path', '/old-page' )
			->assertJsonPath( 'data.to_path', '/new-page' )
			->assertJsonPath( 'data.status_code', 301 );

		$this->assertDatabaseHas( 'redirects', [
			'from_path' => '/old-page',
			'to_path'   => '/new-page',
		] );
	} );

	it( 'validates required fields', function (): void {
		$response = $this->postJson( '/api/seo/redirects', [] );

		$response->assertUnprocessable()
			->assertJsonValidationErrors( [ 'from_path', 'to_path' ] );
	} );

	it( 'validates status code values', function (): void {
		$response = $this->postJson( '/api/seo/redirects', [
			'from_path'   => '/a',
			'to_path'     => '/b',
			'status_code' => 200,
		] );

		$response->assertUnprocessable()
			->assertJsonValidationErrors( [ 'status_code' ] );
	} );

	it( 'validates match type values', function (): void {
		$response = $this->postJson( '/api/seo/redirects', [
			'from_path'  => '/a',
			'to_path'    => '/b',
			'match_type' => 'invalid',
		] );

		$response->assertUnprocessable()
			->assertJsonValidationErrors( [ 'match_type' ] );
	} );
} );

describe( 'GET /api/seo/redirects/{redirect}', function (): void {

	it( 'returns redirect detail', function (): void {
		$redirect = Redirect::create( [
			'from_path'   => '/old',
			'to_path'     => '/new',
			'status_code' => 301,
		] );

		$response = $this->getJson( '/api/seo/redirects/' . $redirect->id );

		$response->assertOk()
			->assertJsonPath( 'data.from_path', '/old' )
			->assertJsonPath( 'data.to_path', '/new' )
			->assertJsonStructure( [
				'data' => [
					'id',
					'from_path',
					'to_path',
					'status_code',
					'status_code_label',
					'match_type',
					'match_type_label',
					'is_active',
					'hits',
					'last_hit_at',
					'notes',
				],
			] );
	} );

	it( 'returns 404 for non-existent redirect', function (): void {
		$response = $this->getJson( '/api/seo/redirects/999' );

		$response->assertNotFound();
	} );
} );

describe( 'PUT /api/seo/redirects/{redirect}', function (): void {

	it( 'updates a redirect', function (): void {
		$redirect = Redirect::create( [
			'from_path'   => '/old',
			'to_path'     => '/new',
			'status_code' => 301,
		] );

		$response = $this->putJson( '/api/seo/redirects/' . $redirect->id, [
			'to_path'     => '/updated',
			'status_code' => 302,
		] );

		$response->assertOk()
			->assertJsonPath( 'data.to_path', '/updated' )
			->assertJsonPath( 'data.status_code', 302 );
	} );

	it( 'returns 404 for non-existent redirect', function (): void {
		$response = $this->putJson( '/api/seo/redirects/999', [
			'to_path' => '/updated',
		] );

		$response->assertNotFound();
	} );
} );

describe( 'DELETE /api/seo/redirects/{redirect}', function (): void {

	it( 'deletes a redirect', function (): void {
		$redirect = Redirect::create( [
			'from_path' => '/old',
			'to_path'   => '/new',
		] );

		$response = $this->deleteJson( '/api/seo/redirects/' . $redirect->id );

		$response->assertNoContent();

		$this->assertDatabaseMissing( 'redirects', [ 'id' => $redirect->id ] );
	} );

	it( 'returns 404 for non-existent redirect', function (): void {
		$response = $this->deleteJson( '/api/seo/redirects/999' );

		$response->assertNotFound();
	} );
} );

describe( 'POST /api/seo/redirects/bulk', function (): void {

	it( 'bulk deletes redirects', function (): void {
		$r1 = Redirect::create( [ 'from_path' => '/a', 'to_path' => '/b' ] );
		$r2 = Redirect::create( [ 'from_path' => '/c', 'to_path' => '/d' ] );
		Redirect::create( [ 'from_path' => '/e', 'to_path' => '/f' ] );

		$response = $this->postJson( '/api/seo/redirects/bulk', [
			'action' => 'delete',
			'ids'    => [ $r1->id, $r2->id ],
		] );

		$response->assertOk()
			->assertJsonPath( 'affected', 2 );

		$this->assertDatabaseMissing( 'redirects', [ 'id' => $r1->id ] );
		$this->assertDatabaseMissing( 'redirects', [ 'id' => $r2->id ] );
		$this->assertDatabaseCount( 'redirects', 1 );
	} );

	it( 'bulk changes status code', function (): void {
		$r1 = Redirect::create( [ 'from_path' => '/a', 'to_path' => '/b', 'status_code' => 301 ] );
		$r2 = Redirect::create( [ 'from_path' => '/c', 'to_path' => '/d', 'status_code' => 301 ] );

		$response = $this->postJson( '/api/seo/redirects/bulk', [
			'action'      => 'change_status_code',
			'ids'         => [ $r1->id, $r2->id ],
			'status_code' => 302,
		] );

		$response->assertOk()
			->assertJsonPath( 'affected', 2 );

		expect( $r1->fresh()->status_code )->toBe( 302 );
		expect( $r2->fresh()->status_code )->toBe( 302 );
	} );

	it( 'validates required fields', function (): void {
		$response = $this->postJson( '/api/seo/redirects/bulk', [] );

		$response->assertUnprocessable()
			->assertJsonValidationErrors( [ 'action', 'ids' ] );
	} );
} );

describe( 'POST /api/seo/redirects/test', function (): void {

	it( 'returns matching redirect for a URL', function (): void {
		Redirect::create( [
			'from_path'   => '/old-page',
			'to_path'     => '/new-page',
			'status_code' => 301,
			'match_type'  => 'exact',
		] );

		$response = $this->postJson( '/api/seo/redirects/test', [
			'url' => '/old-page',
		] );

		$response->assertOk()
			->assertJsonPath( 'data.destination', '/new-page' );
	} );

	it( 'returns null for non-matching URL', function (): void {
		$response = $this->postJson( '/api/seo/redirects/test', [
			'url' => '/no-match',
		] );

		$response->assertOk()
			->assertJson( [ 'data' => null ] );
	} );

	it( 'validates required URL', function (): void {
		$response = $this->postJson( '/api/seo/redirects/test', [] );

		$response->assertUnprocessable()
			->assertJsonValidationErrors( [ 'url' ] );
	} );
} );
