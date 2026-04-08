<?php

/**
 * RedirectResource Tests.
 *
 * Unit tests for RedirectResource.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Http\Resources\RedirectResource;
use ArtisanPackUI\SEO\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../../database/migrations' ) ] );
} );

describe( 'RedirectResource', function (): void {

	it( 'includes computed label fields', function (): void {
		$redirect = Redirect::create( [
			'from_path'   => '/old',
			'to_path'     => '/new',
			'status_code' => 301,
			'match_type'  => 'exact',
		] );

		$resource = new RedirectResource( $redirect );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['status_code_label'] )->not->toBeEmpty()
			->and( $result['match_type_label'] )->not->toBeEmpty();
	} );

	it( 'includes is_permanent and is_temporary flags', function (): void {
		$permanent = Redirect::create( [
			'from_path'   => '/a',
			'to_path'     => '/b',
			'status_code' => 301,
		] );

		$temporary = Redirect::create( [
			'from_path'   => '/c',
			'to_path'     => '/d',
			'status_code' => 302,
		] );

		$permResult = ( new RedirectResource( $permanent ) )->toArray( Request::create( '/' ) );
		$tempResult = ( new RedirectResource( $temporary ) )->toArray( Request::create( '/' ) );

		expect( $permResult['is_permanent'] )->toBeTrue()
			->and( $permResult['is_temporary'] )->toBeFalse()
			->and( $tempResult['is_permanent'] )->toBeFalse()
			->and( $tempResult['is_temporary'] )->toBeTrue();
	} );

	it( 'includes hit statistics', function (): void {
		$redirect = Redirect::create( [
			'from_path' => '/old',
			'to_path'   => '/new',
		] );

		$resource = new RedirectResource( $redirect );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result )->toHaveKeys( [ 'hits', 'last_hit_at' ] )
			->and( $result['hits'] )->toBe( 0 )
			->and( $result['last_hit_at'] )->toBeNull();
	} );

	it( 'includes all expected fields', function (): void {
		$redirect = Redirect::create( [
			'from_path'   => '/old',
			'to_path'     => '/new',
			'status_code' => 307,
			'match_type'  => 'wildcard',
			'notes'       => 'Test note',
		] );

		$resource = new RedirectResource( $redirect );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result )->toHaveKeys( [
			'id',
			'from_path',
			'to_path',
			'status_code',
			'status_code_label',
			'match_type',
			'match_type_label',
			'is_active',
			'is_permanent',
			'is_temporary',
			'hits',
			'last_hit_at',
			'notes',
			'created_at',
			'updated_at',
		] );
	} );
} );
