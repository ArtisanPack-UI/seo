<?php

/**
 * RedirectService Tests.
 *
 * Unit tests for the RedirectService.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\Redirect;
use ArtisanPackUI\SEO\Services\RedirectService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
	$this->service = new RedirectService();
} );

describe( 'RedirectService CRUD Operations', function (): void {

	it( 'creates a redirect', function (): void {
		$redirect = $this->service->create( [
			'from_path'   => '/old-page',
			'to_path'     => '/new-page',
			'status_code' => 301,
			'match_type'  => 'exact',
		] );

		expect( $redirect )->toBeInstanceOf( Redirect::class )
			->and( $redirect->from_path )->toBe( '/old-page' )
			->and( $redirect->to_path )->toBe( '/new-page' )
			->and( $redirect->exists )->toBeTrue();
	} );

	it( 'creates redirect with defaults', function (): void {
		$redirect = $this->service->create( [
			'from_path' => '/old',
			'to_path'   => '/new',
		] );

		expect( $redirect->status_code )->toBe( 301 )
			->and( $redirect->match_type )->toBe( 'exact' )
			->and( $redirect->is_active )->toBeTrue();
	} );

	it( 'updates a redirect', function (): void {
		$redirect = $this->service->create( [
			'from_path' => '/old',
			'to_path'   => '/new',
		] );

		$updated = $this->service->update( $redirect, [
			'to_path'     => '/updated-new',
			'status_code' => 302,
		] );

		expect( $updated->to_path )->toBe( '/updated-new' )
			->and( $updated->status_code )->toBe( 302 );
	} );

	it( 'deletes a redirect', function (): void {
		$redirect = $this->service->create( [
			'from_path' => '/old',
			'to_path'   => '/new',
		] );

		$id = $redirect->id;

		$this->service->delete( $redirect );

		expect( Redirect::find( $id ) )->toBeNull();
	} );

	it( 'validates required fields on create', function (): void {
		expect( function (): void {
			$this->service->create( [
				'to_path' => '/new',
			] );
		} )->toThrow( InvalidArgumentException::class );

		expect( function (): void {
			$this->service->create( [
				'from_path' => '/old',
			] );
		} )->toThrow( InvalidArgumentException::class );
	} );

	it( 'validates status code', function (): void {
		expect( function (): void {
			$this->service->create( [
				'from_path'   => '/old',
				'to_path'     => '/new',
				'status_code' => 404,
			] );
		} )->toThrow( InvalidArgumentException::class );
	} );

	it( 'validates match type', function (): void {
		expect( function (): void {
			$this->service->create( [
				'from_path'  => '/old',
				'to_path'    => '/new',
				'match_type' => 'invalid',
			] );
		} )->toThrow( InvalidArgumentException::class );
	} );

	it( 'prevents duplicate exact match redirects', function (): void {
		$this->service->create( [
			'from_path' => '/old',
			'to_path'   => '/new',
		] );

		expect( function (): void {
			$this->service->create( [
				'from_path' => '/old',
				'to_path'   => '/different',
			] );
		} )->toThrow( InvalidArgumentException::class );
	} );

} );

describe( 'RedirectService Path Matching', function (): void {

	it( 'finds exact match redirect', function (): void {
		$this->service->create( [
			'from_path' => '/old-page',
			'to_path'   => '/new-page',
		] );

		$match = $this->service->findMatch( '/old-page' );

		expect( $match )->not->toBeNull()
			->and( $match->to_path )->toBe( '/new-page' );
	} );

	it( 'finds wildcard match redirect', function (): void {
		$this->service->create( [
			'from_path'  => '/blog/*',
			'to_path'    => '/posts/*',
			'match_type' => 'wildcard',
		] );

		$match = $this->service->findMatch( '/blog/my-article' );

		expect( $match )->not->toBeNull()
			->and( $match->match_type )->toBe( 'wildcard' );
	} );

	it( 'finds regex match redirect', function (): void {
		$this->service->create( [
			'from_path'  => '/product/(\d+)',
			'to_path'    => '/item/$1',
			'match_type' => 'regex',
		] );

		$match = $this->service->findMatch( '/product/123' );

		expect( $match )->not->toBeNull()
			->and( $match->match_type )->toBe( 'regex' );
	} );

	it( 'returns null when no match found', function (): void {
		$this->service->create( [
			'from_path' => '/old',
			'to_path'   => '/new',
		] );

		$match = $this->service->findMatch( '/nonexistent' );

		expect( $match )->toBeNull();
	} );

	it( 'prioritizes exact matches over wildcard and regex', function (): void {
		$this->service->create( [
			'from_path'  => '/test/*',
			'to_path'    => '/wildcard-destination',
			'match_type' => 'wildcard',
		] );

		$this->service->create( [
			'from_path'  => '/test/specific',
			'to_path'    => '/exact-destination',
			'match_type' => 'exact',
		] );

		$match = $this->service->findMatch( '/test/specific' );

		expect( $match->to_path )->toBe( '/exact-destination' );
	} );

	it( 'only matches active redirects', function (): void {
		Redirect::create( [
			'from_path' => '/old',
			'to_path'   => '/new',
			'is_active' => false,
		] );

		$match = $this->service->findMatch( '/old' );

		expect( $match )->toBeNull();
	} );

} );

describe( 'RedirectService Chain Detection', function (): void {

	it( 'detects direct loop', function (): void {
		expect( function (): void {
			$this->service->create( [
				'from_path' => '/page-a',
				'to_path'   => '/page-a',
			] );
		} )->toThrow( InvalidArgumentException::class );
	} );

	it( 'detects redirect chain', function (): void {
		$this->service->create( [
			'from_path' => '/page-a',
			'to_path'   => '/page-b',
		] );

		$this->service->create( [
			'from_path' => '/page-b',
			'to_path'   => '/page-c',
		] );

		expect( function (): void {
			$this->service->create( [
				'from_path' => '/page-c',
				'to_path'   => '/page-a',
			] );
		} )->toThrow( InvalidArgumentException::class );
	} );

	it( 'allows non-chained redirects', function (): void {
		$this->service->create( [
			'from_path' => '/page-a',
			'to_path'   => '/page-b',
		] );

		$redirect = $this->service->create( [
			'from_path' => '/page-c',
			'to_path'   => '/page-d',
		] );

		expect( $redirect )->toBeInstanceOf( Redirect::class );
	} );

	it( 'checks for chains on existing redirect', function (): void {
		$redirect = $this->service->create( [
			'from_path' => '/page-a',
			'to_path'   => '/page-b',
		] );

		$this->service->create( [
			'from_path' => '/page-b',
			'to_path'   => '/page-c',
		] );

		expect( $this->service->checkForChains( $redirect ) )->toBeTrue();
	} );

} );

describe( 'RedirectService Statistics', function (): void {

	beforeEach( function (): void {
		$this->service->create( [
			'from_path'   => '/page-1',
			'to_path'     => '/new-1',
			'status_code' => 301,
			'match_type'  => 'exact',
		] );

		$this->service->create( [
			'from_path'   => '/page-2',
			'to_path'     => '/new-2',
			'status_code' => 302,
			'match_type'  => 'wildcard',
		] );

		Redirect::create( [
			'from_path'   => '/page-3',
			'to_path'     => '/new-3',
			'status_code' => 307,
			'match_type'  => 'regex',
			'is_active'   => false,
		] );

		// Add hits to one redirect using forceFill to bypass validation
		$popular = Redirect::where( 'from_path', '/page-1' )->first();
		$popular->forceFill( [ 'hits' => 50, 'last_hit_at' => now() ] )->saveQuietly();
	} );

	it( 'returns correct total count', function (): void {
		$stats = $this->service->getStatistics();

		expect( $stats['total'] )->toBe( 3 );
	} );

	it( 'returns correct active count', function (): void {
		$stats = $this->service->getStatistics();

		expect( $stats['active'] )->toBe( 2 );
	} );

	it( 'returns correct inactive count', function (): void {
		$stats = $this->service->getStatistics();

		expect( $stats['inactive'] )->toBe( 1 );
	} );

	it( 'returns correct total hits', function (): void {
		$stats = $this->service->getStatistics();

		expect( $stats['total_hits'] )->toBe( 50 );
	} );

	it( 'returns counts by status code', function (): void {
		$stats = $this->service->getStatistics();

		expect( $stats['by_status_code'][301] )->toBe( 1 )
			->and( $stats['by_status_code'][302] )->toBe( 1 )
			->and( $stats['by_status_code'][307] )->toBe( 1 )
			->and( $stats['by_status_code'][308] )->toBe( 0 );
	} );

	it( 'returns counts by match type', function (): void {
		$stats = $this->service->getStatistics();

		expect( $stats['by_match_type']['exact'] )->toBe( 1 )
			->and( $stats['by_match_type']['wildcard'] )->toBe( 1 )
			->and( $stats['by_match_type']['regex'] )->toBe( 1 );
	} );

	it( 'returns most used redirects', function (): void {
		$stats = $this->service->getStatistics();

		expect( $stats['most_used'] )->toHaveCount( 1 )
			->and( $stats['most_used']->first()->from_path )->toBe( '/page-1' );
	} );

	it( 'returns never used count', function (): void {
		$stats = $this->service->getStatistics();

		expect( $stats['never_used'] )->toBe( 1 );
	} );

} );

describe( 'RedirectService Import/Export', function (): void {

	it( 'exports redirects', function (): void {
		$this->service->create( [
			'from_path' => '/old-1',
			'to_path'   => '/new-1',
		] );

		$this->service->create( [
			'from_path' => '/old-2',
			'to_path'   => '/new-2',
		] );

		$exported = $this->service->export();

		expect( $exported )->toHaveCount( 2 )
			->and( $exported->first() )->toHaveKeys( [
				'from_path',
				'to_path',
				'status_code',
				'match_type',
				'is_active',
				'notes',
			] );
	} );

	it( 'exports only active redirects when specified', function (): void {
		$this->service->create( [
			'from_path' => '/active',
			'to_path'   => '/new',
		] );

		Redirect::create( [
			'from_path' => '/inactive',
			'to_path'   => '/new',
			'is_active' => false,
		] );

		$exported = $this->service->export( activeOnly: true );

		expect( $exported )->toHaveCount( 1 )
			->and( $exported->first()['from_path'] )->toBe( '/active' );
	} );

	it( 'imports redirects', function (): void {
		$data = [
			[
				'from_path'   => '/import-1',
				'to_path'     => '/new-1',
				'status_code' => 301,
			],
			[
				'from_path'   => '/import-2',
				'to_path'     => '/new-2',
				'status_code' => 302,
			],
		];

		$result = $this->service->import( $data );

		expect( $result['created'] )->toBe( 2 )
			->and( $result['skipped'] )->toBe( 0 )
			->and( Redirect::count() )->toBe( 2 );
	} );

	it( 'skips invalid redirects during import', function (): void {
		$data = [
			[
				'from_path' => '/valid',
				'to_path'   => '/new',
			],
			[
				'from_path'   => '/invalid',
				'to_path'     => '/new',
				'status_code' => 999, // Invalid
			],
		];

		$result = $this->service->import( $data, skipErrors: true );

		expect( $result['created'] )->toBe( 1 )
			->and( $result['skipped'] )->toBe( 1 )
			->and( $result['errors'] )->not->toBeEmpty();
	} );

} );

describe( 'RedirectService Queries', function (): void {

	beforeEach( function (): void {
		$this->service->create( [
			'from_path' => '/old-1',
			'to_path'   => '/common-destination',
		] );

		$this->service->create( [
			'from_path' => '/old-2',
			'to_path'   => '/common-destination',
		] );

		$this->service->create( [
			'from_path' => '/old-3',
			'to_path'   => '/unique-destination',
		] );
	} );

	it( 'finds redirects pointing to a path', function (): void {
		$redirects = $this->service->findRedirectsTo( '/common-destination' );

		expect( $redirects )->toHaveCount( 2 );
	} );

	it( 'finds redirects from a path', function (): void {
		$redirects = $this->service->findRedirectsFrom( '/old-1' );

		expect( $redirects )->toHaveCount( 1 )
			->and( $redirects->first()->to_path )->toBe( '/common-destination' );
	} );

	it( 'gets all active redirects', function (): void {
		Redirect::create( [
			'from_path' => '/inactive',
			'to_path'   => '/new',
			'is_active' => false,
		] );

		$active = $this->service->getActiveRedirects();

		expect( $active )->toHaveCount( 3 );
	} );

} );
