<?php

/**
 * Redirect Model Tests.
 *
 * Unit tests for the Redirect Eloquent model.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

describe( 'Redirect Model', function (): void {

	it( 'can create a redirect record', function (): void {
		$redirect = Redirect::create( [
			'from_path'   => '/old-page',
			'to_path'     => '/new-page',
			'status_code' => 301,
			'match_type'  => 'exact',
		] );

		expect( $redirect )->toBeInstanceOf( Redirect::class )
			->and( $redirect->from_path )->toBe( '/old-page' )
			->and( $redirect->to_path )->toBe( '/new-page' )
			->and( $redirect->status_code )->toBe( 301 )
			->and( $redirect->match_type )->toBe( 'exact' );
	} );

	it( 'has correct default values', function (): void {
		$redirect = Redirect::create( [
			'from_path' => '/old',
			'to_path'   => '/new',
		] );

		$redirect->refresh();

		expect( $redirect->status_code )->toBe( 301 )
			->and( $redirect->match_type )->toBe( 'exact' )
			->and( $redirect->is_active )->toBeTrue()
			->and( $redirect->hits )->toBe( 0 )
			->and( $redirect->last_hit_at )->toBeNull();
	} );

	it( 'casts fields correctly', function (): void {
		$redirect = Redirect::create( [
			'from_path'   => '/test',
			'to_path'     => '/new-test',
			'status_code' => 302,
			'is_active'   => false,
		] );

		expect( $redirect->status_code )->toBeInt()
			->and( $redirect->is_active )->toBeBool()
			->and( $redirect->hits )->toBeInt();
	} );

	it( 'validates status code on save', function (): void {
		expect( function (): void {
			Redirect::create( [
				'from_path'   => '/test',
				'to_path'     => '/new',
				'status_code' => 404,
			] );
		} )->toThrow( InvalidArgumentException::class );
	} );

	it( 'validates match type on save', function (): void {
		expect( function (): void {
			Redirect::create( [
				'from_path'  => '/test',
				'to_path'    => '/new',
				'match_type' => 'invalid',
			] );
		} )->toThrow( InvalidArgumentException::class );
	} );

	it( 'validates regex pattern on save', function (): void {
		expect( function (): void {
			Redirect::create( [
				'from_path'  => '/test[invalid',
				'to_path'    => '/new',
				'match_type' => 'regex',
			] );
		} )->toThrow( InvalidArgumentException::class );
	} );

	it( 'allows valid regex patterns', function (): void {
		$redirect = Redirect::create( [
			'from_path'  => '/blog/(\d+)',
			'to_path'    => '/posts/$1',
			'match_type' => 'regex',
		] );

		expect( $redirect->from_path )->toBe( '/blog/(\d+)' );
	} );

} );

describe( 'Redirect Scopes', function (): void {

	beforeEach( function (): void {
		Redirect::create( [
			'from_path'   => '/active-301',
			'to_path'     => '/new-1',
			'status_code' => 301,
			'match_type'  => 'exact',
			'is_active'   => true,
		] );

		Redirect::create( [
			'from_path'   => '/active-302',
			'to_path'     => '/new-2',
			'status_code' => 302,
			'match_type'  => 'wildcard',
			'is_active'   => true,
		] );

		Redirect::create( [
			'from_path'   => '/inactive',
			'to_path'     => '/new-3',
			'status_code' => 307,
			'match_type'  => 'regex',
			'is_active'   => false,
		] );

		// Create one with hits using forceFill to bypass validation
		$withHits = Redirect::create( [
			'from_path'   => '/popular',
			'to_path'     => '/new-popular',
			'status_code' => 308,
			'is_active'   => true,
		] );
		$withHits->forceFill( [ 'hits' => 100 ] )->saveQuietly();
	} );

	it( 'filters active redirects', function (): void {
		$active = Redirect::active()->get();

		expect( $active )->toHaveCount( 3 )
			->and( $active->pluck( 'is_active' )->unique()->toArray() )->toBe( [ true ] );
	} );

	it( 'filters inactive redirects', function (): void {
		$inactive = Redirect::inactive()->get();

		expect( $inactive )->toHaveCount( 1 )
			->and( $inactive->first()->from_path )->toBe( '/inactive' );
	} );

	it( 'filters by match type', function (): void {
		expect( Redirect::exact()->count() )->toBe( 2 )
			->and( Redirect::wildcard()->count() )->toBe( 1 )
			->and( Redirect::regex()->count() )->toBe( 1 );
	} );

	it( 'filters by status code', function (): void {
		expect( Redirect::withStatusCode( 301 )->count() )->toBe( 1 )
			->and( Redirect::withStatusCode( 302 )->count() )->toBe( 1 );
	} );

	it( 'filters permanent redirects', function (): void {
		$permanent = Redirect::permanent()->get();

		expect( $permanent )->toHaveCount( 2 )
			->and( $permanent->pluck( 'status_code' )->toArray() )->toContain( 301, 308 );
	} );

	it( 'filters temporary redirects', function (): void {
		$temporary = Redirect::temporary()->get();

		expect( $temporary )->toHaveCount( 2 )
			->and( $temporary->pluck( 'status_code' )->toArray() )->toContain( 302, 307 );
	} );

	it( 'filters redirects with hits', function (): void {
		$withHits = Redirect::withHits()->get();

		expect( $withHits )->toHaveCount( 1 )
			->and( $withHits->first()->from_path )->toBe( '/popular' );
	} );

	it( 'filters redirects without hits', function (): void {
		$withoutHits = Redirect::withoutHits()->get();

		expect( $withoutHits )->toHaveCount( 3 );
	} );

	it( 'orders by most hits', function (): void {
		$ordered = Redirect::mostHits()->get();

		expect( $ordered->first()->from_path )->toBe( '/popular' );
	} );

} );

describe( 'Redirect Matching', function (): void {

	it( 'matches exact paths', function (): void {
		$redirect = new Redirect( [
			'from_path'  => '/old-page',
			'to_path'    => '/new-page',
			'match_type' => 'exact',
		] );

		expect( $redirect->matches( '/old-page' ) )->toBeTrue()
			->and( $redirect->matches( '/old-page/' ) )->toBeTrue()
			->and( $redirect->matches( 'old-page' ) )->toBeTrue()
			->and( $redirect->matches( '/old-page/extra' ) )->toBeFalse()
			->and( $redirect->matches( '/different' ) )->toBeFalse();
	} );

	it( 'matches wildcard patterns', function (): void {
		$redirect = new Redirect( [
			'from_path'  => '/blog/*',
			'to_path'    => '/posts/*',
			'match_type' => 'wildcard',
		] );

		expect( $redirect->matches( '/blog/my-post' ) )->toBeTrue()
			->and( $redirect->matches( '/blog/category/my-post' ) )->toBeTrue()
			->and( $redirect->matches( '/blog/a' ) )->toBeTrue()
			->and( $redirect->matches( '/other/path' ) )->toBeFalse();
	} );

	it( 'matches regex patterns', function (): void {
		$redirect = new Redirect( [
			'from_path'  => '/blog/(\d+)',
			'to_path'    => '/posts/$1',
			'match_type' => 'regex',
		] );

		expect( $redirect->matches( '/blog/123' ) )->toBeTrue()
			->and( $redirect->matches( '/blog/456' ) )->toBeTrue()
			->and( $redirect->matches( '/blog/abc' ) )->toBeFalse()
			->and( $redirect->matches( '/other/123' ) )->toBeFalse();
	} );

	it( 'matches regex patterns with delimiters', function (): void {
		$redirect = new Redirect( [
			'from_path'  => '#/product/([a-z]+)/(\d+)#i',
			'to_path'    => '/item/$1/$2',
			'match_type' => 'regex',
		] );

		expect( $redirect->matches( '/product/shoes/42' ) )->toBeTrue()
			->and( $redirect->matches( '/product/HATS/99' ) )->toBeTrue();
	} );

	it( 'handles regex timeout protection', function (): void {
		// This should not hang or timeout
		$redirect = new Redirect( [
			'from_path'  => '(a+)+b',
			'to_path'    => '/new',
			'match_type' => 'regex',
		] );

		// Test with a string that could cause catastrophic backtracking
		$result = $redirect->matches( '/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaac' );

		expect( $result )->toBeFalse();
	} );

} );

describe( 'Redirect Destination Resolution', function (): void {

	it( 'resolves exact match destination', function (): void {
		$redirect = new Redirect( [
			'from_path'  => '/old',
			'to_path'    => '/new',
			'match_type' => 'exact',
		] );

		expect( $redirect->getResolvedDestination( '/old' ) )->toBe( '/new' );
	} );

	it( 'resolves regex destination with capture groups', function (): void {
		$redirect = new Redirect( [
			'from_path'  => '/blog/(\d{4})/(\d{2})/(.+)',
			'to_path'    => '/posts/$1-$2/$3',
			'match_type' => 'regex',
		] );

		expect( $redirect->getResolvedDestination( '/blog/2024/01/my-post' ) )
			->toBe( '/posts/2024-01/my-post' );
	} );

	it( 'resolves wildcard destination', function (): void {
		$redirect = new Redirect( [
			'from_path'  => '/old-blog/*',
			'to_path'    => '/blog/*',
			'match_type' => 'wildcard',
		] );

		expect( $redirect->getResolvedDestination( '/old-blog/my-post' ) )
			->toBe( '/blog/my-post' );
	} );

} );

describe( 'Redirect Helper Methods', function (): void {

	it( 'identifies permanent redirects', function (): void {
		$r301 = new Redirect( [ 'status_code' => 301 ] );
		$r308 = new Redirect( [ 'status_code' => 308 ] );
		$r302 = new Redirect( [ 'status_code' => 302 ] );

		expect( $r301->isPermanent() )->toBeTrue()
			->and( $r308->isPermanent() )->toBeTrue()
			->and( $r302->isPermanent() )->toBeFalse();
	} );

	it( 'identifies temporary redirects', function (): void {
		$r302 = new Redirect( [ 'status_code' => 302 ] );
		$r307 = new Redirect( [ 'status_code' => 307 ] );
		$r301 = new Redirect( [ 'status_code' => 301 ] );

		expect( $r302->isTemporary() )->toBeTrue()
			->and( $r307->isTemporary() )->toBeTrue()
			->and( $r301->isTemporary() )->toBeFalse();
	} );

	it( 'identifies match types', function (): void {
		$exact    = new Redirect( [ 'match_type' => 'exact' ] );
		$regex    = new Redirect( [ 'match_type' => 'regex' ] );
		$wildcard = new Redirect( [ 'match_type' => 'wildcard' ] );

		expect( $exact->isExactMatch() )->toBeTrue()
			->and( $exact->isRegexMatch() )->toBeFalse()
			->and( $regex->isRegexMatch() )->toBeTrue()
			->and( $wildcard->isWildcardMatch() )->toBeTrue();
	} );

	it( 'returns status code labels', function (): void {
		$r301 = new Redirect( [ 'status_code' => 301 ] );
		$r302 = new Redirect( [ 'status_code' => 302 ] );
		$r307 = new Redirect( [ 'status_code' => 307 ] );
		$r308 = new Redirect( [ 'status_code' => 308 ] );

		expect( $r301->getStatusCodeLabel() )->toContain( '301' )
			->and( $r302->getStatusCodeLabel() )->toContain( '302' )
			->and( $r307->getStatusCodeLabel() )->toContain( '307' )
			->and( $r308->getStatusCodeLabel() )->toContain( '308' );
	} );

	it( 'returns match type labels', function (): void {
		$exact    = new Redirect( [ 'match_type' => 'exact' ] );
		$regex    = new Redirect( [ 'match_type' => 'regex' ] );
		$wildcard = new Redirect( [ 'match_type' => 'wildcard' ] );

		expect( $exact->getMatchTypeLabel() )->not->toBeEmpty()
			->and( $regex->getMatchTypeLabel() )->not->toBeEmpty()
			->and( $wildcard->getMatchTypeLabel() )->not->toBeEmpty();
	} );

	it( 'records hits correctly', function (): void {
		$redirect = Redirect::create( [
			'from_path' => '/test',
			'to_path'   => '/new',
		] );

		expect( $redirect->hits )->toBe( 0 )
			->and( $redirect->last_hit_at )->toBeNull();

		$redirect->recordHit();
		$redirect->refresh();

		expect( $redirect->hits )->toBe( 1 )
			->and( $redirect->last_hit_at )->not->toBeNull();

		$redirect->recordHit();
		$redirect->refresh();

		expect( $redirect->hits )->toBe( 2 );
	} );

} );
