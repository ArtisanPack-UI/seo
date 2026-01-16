<?php

/**
 * RedirectManager Livewire Component Tests.
 *
 * Feature tests for the RedirectManager Livewire component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Livewire\RedirectManager;
use ArtisanPackUI\SEO\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use Livewire\Livewire;

uses( RefreshDatabase::class );

/**
 * Test version of RedirectManager that uses a simplified view for testing.
 */
class TestRedirectManager extends RedirectManager
{
	/**
	 * Render the component with a simplified test view.
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'test-redirect-manager' );
	}
}

beforeEach( function (): void {
	// Run migrations
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	// Register the test view location
	$this->app['view']->addLocation( __DIR__ . '/../../stubs/views/livewire' );
} );

describe( 'RedirectManager Component Mounting', function (): void {

	it( 'mounts with default values', function (): void {
		Livewire::test( TestRedirectManager::class )
			->assertSet( 'search', '' )
			->assertSet( 'filterStatus', '' )
			->assertSet( 'filterMatchType', '' )
			->assertSet( 'sortField', 'hits' )
			->assertSet( 'sortDirection', 'desc' )
			->assertSet( 'showEditor', false )
			->assertSet( 'showDeleteConfirm', false )
			->assertSet( 'editing', null );
	} );

	it( 'shows empty state when no redirects exist', function (): void {
		Livewire::test( TestRedirectManager::class )
			->assertSee( 'No redirects found.' );
	} );

	it( 'displays redirects in the table', function (): void {
		$redirect = Redirect::create( [
			'from_path'   => '/old-page',
			'to_path'     => '/new-page',
			'status_code' => 301,
			'match_type'  => 'exact',
			'is_active'   => true,
		] );

		Livewire::test( TestRedirectManager::class )
			->assertSee( '/old-page' )
			->assertSee( '/new-page' )
			->assertSee( '301' );
	} );

} );

describe( 'RedirectManager Statistics', function (): void {

	it( 'displays correct statistics', function (): void {
		Redirect::create( [
			'from_path'   => '/page-1',
			'to_path'     => '/new-1',
			'is_active'   => true,
		] );

		Redirect::create( [
			'from_path'   => '/page-2',
			'to_path'     => '/new-2',
			'is_active'   => false,
		] );

		$component = Livewire::test( TestRedirectManager::class );

		expect( $component->get( 'statistics' ) )
			->toHaveKey( 'total', 2 )
			->toHaveKey( 'active', 1 )
			->toHaveKey( 'inactive', 1 );
	} );

	it( 'displays total hits in statistics', function (): void {
		$redirect1 = Redirect::create( [
			'from_path' => '/page-1',
			'to_path'   => '/new-1',
		] );
		$redirect1->forceFill( [ 'hits' => 100 ] )->save();

		$redirect2 = Redirect::create( [
			'from_path' => '/page-2',
			'to_path'   => '/new-2',
		] );
		$redirect2->forceFill( [ 'hits' => 50 ] )->save();

		$component = Livewire::test( TestRedirectManager::class );

		expect( $component->get( 'statistics' )['total_hits'] )->toBe( 150 );
	} );

} );

describe( 'RedirectManager Search and Filtering', function (): void {

	it( 'filters redirects by search term in from path', function (): void {
		Redirect::create( [
			'from_path' => '/blog/post-1',
			'to_path'   => '/articles/post-1',
		] );

		Redirect::create( [
			'from_path' => '/contact',
			'to_path'   => '/get-in-touch',
		] );

		Livewire::test( TestRedirectManager::class )
			->set( 'search', 'blog' )
			->assertSee( '/blog/post-1' )
			->assertDontSee( '/contact' );
	} );

	it( 'filters redirects by search term in to path', function (): void {
		Redirect::create( [
			'from_path' => '/page-1',
			'to_path'   => '/new/blog/page',
		] );

		Redirect::create( [
			'from_path' => '/page-2',
			'to_path'   => '/new/contact',
		] );

		Livewire::test( TestRedirectManager::class )
			->set( 'search', 'blog' )
			->assertSee( '/page-1' )
			->assertDontSee( '/page-2' );
	} );

	it( 'filters by active status', function (): void {
		$active = Redirect::create( [
			'from_path' => '/active-page',
			'to_path'   => '/new-active',
			'is_active' => true,
		] );

		$inactive = Redirect::create( [
			'from_path' => '/inactive-page',
			'to_path'   => '/new-inactive',
			'is_active' => false,
		] );

		Livewire::test( TestRedirectManager::class )
			->set( 'filterStatus', 'active' )
			->assertSee( '/active-page' )
			->assertDontSee( '/inactive-page' );
	} );

	it( 'filters by inactive status', function (): void {
		Redirect::create( [
			'from_path' => '/active-page',
			'to_path'   => '/new-active',
			'is_active' => true,
		] );

		Redirect::create( [
			'from_path' => '/inactive-page',
			'to_path'   => '/new-inactive',
			'is_active' => false,
		] );

		Livewire::test( TestRedirectManager::class )
			->set( 'filterStatus', 'inactive' )
			->assertDontSee( '/active-page' )
			->assertSee( '/inactive-page' );
	} );

	it( 'filters by match type', function (): void {
		Redirect::create( [
			'from_path'  => '/exact-page',
			'to_path'    => '/new-exact',
			'match_type' => 'exact',
		] );

		Redirect::create( [
			'from_path'  => '^/regex.*',
			'to_path'    => '/new-regex',
			'match_type' => 'regex',
		] );

		Livewire::test( TestRedirectManager::class )
			->set( 'filterMatchType', 'exact' )
			->assertSee( '/exact-page' )
			->assertDontSee( '^/regex.*' );
	} );

	it( 'resets pagination when search changes', function (): void {
		// Create enough redirects to have multiple pages
		for ( $i = 1; $i <= 25; $i++ ) {
			Redirect::create( [
				'from_path' => "/page-{$i}",
				'to_path'   => "/new-{$i}",
			] );
		}

		$component = Livewire::test( TestRedirectManager::class )
			->call( 'gotoPage', 2 )
			->set( 'search', 'page-1' );

		// Should be on page 1 after search reset
		expect( $component->get( 'paginators' )['page'] ?? 1 )->toBe( 1 );
	} );

} );

describe( 'RedirectManager Sorting', function (): void {

	it( 'sorts by field ascending', function (): void {
		Redirect::create( [
			'from_path' => '/z-page',
			'to_path'   => '/new-z',
		] );

		Redirect::create( [
			'from_path' => '/a-page',
			'to_path'   => '/new-a',
		] );

		Livewire::test( TestRedirectManager::class )
			->call( 'sortBy', 'from_path' )
			->assertSet( 'sortField', 'from_path' )
			->assertSet( 'sortDirection', 'asc' );
	} );

	it( 'toggles sort direction when clicking same field', function (): void {
		Livewire::test( TestRedirectManager::class )
			->set( 'sortField', 'hits' )
			->set( 'sortDirection', 'desc' )
			->call( 'sortBy', 'hits' )
			->assertSet( 'sortDirection', 'asc' )
			->call( 'sortBy', 'hits' )
			->assertSet( 'sortDirection', 'desc' );
	} );

	it( 'resets to ascending when sorting by new field', function (): void {
		Livewire::test( TestRedirectManager::class )
			->set( 'sortField', 'hits' )
			->set( 'sortDirection', 'desc' )
			->call( 'sortBy', 'from_path' )
			->assertSet( 'sortField', 'from_path' )
			->assertSet( 'sortDirection', 'asc' );
	} );

} );

describe( 'RedirectManager CRUD Operations', function (): void {

	it( 'opens create modal', function (): void {
		Livewire::test( TestRedirectManager::class )
			->call( 'create' )
			->assertSet( 'showEditor', true )
			->assertSet( 'editing', null )
			->assertSet( 'fromPath', '' )
			->assertSet( 'toPath', '' )
			->assertSet( 'statusCode', 301 )
			->assertSet( 'matchType', 'exact' );
	} );

	it( 'creates a new redirect', function (): void {
		Livewire::test( TestRedirectManager::class )
			->call( 'create' )
			->set( 'fromPath', '/old-url' )
			->set( 'toPath', '/new-url' )
			->set( 'statusCode', 301 )
			->set( 'matchType', 'exact' )
			->call( 'save' )
			->assertSet( 'showEditor', false )
			->assertDispatched( 'notify' );

		expect( Redirect::where( 'from_path', '/old-url' )->exists() )->toBeTrue();
	} );

	it( 'opens edit modal with redirect data', function (): void {
		$redirect = Redirect::create( [
			'from_path'   => '/edit-me',
			'to_path'     => '/edited',
			'status_code' => 302,
			'match_type'  => 'wildcard',
			'notes'       => 'Test notes',
			'is_active'   => false,
		] );

		Livewire::test( TestRedirectManager::class )
			->call( 'edit', $redirect->id )
			->assertSet( 'showEditor', true )
			->assertSet( 'fromPath', '/edit-me' )
			->assertSet( 'toPath', '/edited' )
			->assertSet( 'statusCode', 302 )
			->assertSet( 'matchType', 'wildcard' )
			->assertSet( 'notes', 'Test notes' )
			->assertSet( 'isActive', false );
	} );

	it( 'updates an existing redirect', function (): void {
		$redirect = Redirect::create( [
			'from_path' => '/original',
			'to_path'   => '/target',
		] );

		Livewire::test( TestRedirectManager::class )
			->call( 'edit', $redirect->id )
			->set( 'fromPath', '/updated-from' )
			->set( 'toPath', '/updated-to' )
			->call( 'save' )
			->assertSet( 'showEditor', false )
			->assertDispatched( 'notify' );

		$redirect->refresh();
		expect( $redirect->from_path )->toBe( '/updated-from' );
		expect( $redirect->to_path )->toBe( '/updated-to' );
	} );

	it( 'closes editor modal', function (): void {
		Livewire::test( TestRedirectManager::class )
			->call( 'create' )
			->assertSet( 'showEditor', true )
			->call( 'closeEditor' )
			->assertSet( 'showEditor', false );
	} );

	it( 'shows delete confirmation modal', function (): void {
		$redirect = Redirect::create( [
			'from_path' => '/delete-me',
			'to_path'   => '/deleted',
		] );

		Livewire::test( TestRedirectManager::class )
			->call( 'confirmDelete', $redirect->id )
			->assertSet( 'showDeleteConfirm', true )
			->assertSet( 'deleting.id', $redirect->id );
	} );

	it( 'cancels delete confirmation', function (): void {
		$redirect = Redirect::create( [
			'from_path' => '/delete-me',
			'to_path'   => '/deleted',
		] );

		Livewire::test( TestRedirectManager::class )
			->call( 'confirmDelete', $redirect->id )
			->call( 'cancelDelete' )
			->assertSet( 'showDeleteConfirm', false )
			->assertSet( 'deleting', null );

		expect( Redirect::find( $redirect->id ) )->not->toBeNull();
	} );

	it( 'deletes a redirect', function (): void {
		$redirect = Redirect::create( [
			'from_path' => '/delete-me',
			'to_path'   => '/deleted',
		] );

		Livewire::test( TestRedirectManager::class )
			->call( 'confirmDelete', $redirect->id )
			->call( 'delete' )
			->assertSet( 'showDeleteConfirm', false )
			->assertDispatched( 'notify' );

		expect( Redirect::find( $redirect->id ) )->toBeNull();
	} );

	it( 'toggles redirect active status', function (): void {
		$redirect = Redirect::create( [
			'from_path' => '/toggle-me',
			'to_path'   => '/toggled',
			'is_active' => true,
		] );

		Livewire::test( TestRedirectManager::class )
			->call( 'toggleActive', $redirect->id )
			->assertDispatched( 'notify' );

		$redirect->refresh();
		expect( $redirect->is_active )->toBeFalse();

		Livewire::test( TestRedirectManager::class )
			->call( 'toggleActive', $redirect->id );

		$redirect->refresh();
		expect( $redirect->is_active )->toBeTrue();
	} );

} );

describe( 'RedirectManager Validation', function (): void {

	it( 'validates required from path', function (): void {
		Livewire::test( TestRedirectManager::class )
			->call( 'create' )
			->set( 'fromPath', '' )
			->set( 'toPath', '/destination' )
			->call( 'save' )
			->assertHasErrors( [ 'fromPath' => 'required' ] );
	} );

	it( 'validates required to path', function (): void {
		Livewire::test( TestRedirectManager::class )
			->call( 'create' )
			->set( 'fromPath', '/source' )
			->set( 'toPath', '' )
			->call( 'save' )
			->assertHasErrors( [ 'toPath' => 'required' ] );
	} );

	it( 'validates status code', function (): void {
		Livewire::test( TestRedirectManager::class )
			->call( 'create' )
			->set( 'fromPath', '/source' )
			->set( 'toPath', '/destination' )
			->set( 'statusCode', 999 )
			->call( 'save' )
			->assertHasErrors( [ 'statusCode' ] );
	} );

	it( 'validates match type', function (): void {
		Livewire::test( TestRedirectManager::class )
			->call( 'create' )
			->set( 'fromPath', '/source' )
			->set( 'toPath', '/destination' )
			->set( 'matchType', 'invalid' )
			->call( 'save' )
			->assertHasErrors( [ 'matchType' ] );
	} );

	it( 'shows error for duplicate redirects', function (): void {
		Redirect::create( [
			'from_path' => '/existing',
			'to_path'   => '/target',
		] );

		Livewire::test( TestRedirectManager::class )
			->call( 'create' )
			->set( 'fromPath', '/existing' )
			->set( 'toPath', '/new-target' )
			->call( 'save' )
			->assertHasErrors( [ 'fromPath' ] );
	} );

} );

describe( 'RedirectManager Chain Detection', function (): void {

	it( 'detects chain issues', function (): void {
		// Create a chain: /a -> /b, /b -> /c
		Redirect::create( [
			'from_path' => '/a',
			'to_path'   => '/b',
			'is_active' => true,
		] );

		Redirect::create( [
			'from_path' => '/b',
			'to_path'   => '/c',
			'is_active' => true,
		] );

		Livewire::test( TestRedirectManager::class )
			->call( 'checkChains' )
			->assertSet( 'hasChainIssues', true )
			->assertDispatched( 'notify' );
	} );

	it( 'reports no issues when no chains exist', function (): void {
		Redirect::create( [
			'from_path' => '/a',
			'to_path'   => '/b',
			'is_active' => true,
		] );

		Redirect::create( [
			'from_path' => '/c',
			'to_path'   => '/d',
			'is_active' => true,
		] );

		Livewire::test( TestRedirectManager::class )
			->call( 'checkChains' )
			->assertSet( 'hasChainIssues', false )
			->assertDispatched( 'notify' );
	} );

	it( 'clears chain issues', function (): void {
		Redirect::create( [
			'from_path' => '/a',
			'to_path'   => '/b',
			'is_active' => true,
		] );

		Redirect::create( [
			'from_path' => '/b',
			'to_path'   => '/c',
			'is_active' => true,
		] );

		Livewire::test( TestRedirectManager::class )
			->call( 'checkChains' )
			->assertSet( 'hasChainIssues', true )
			->call( 'clearChainIssues' )
			->assertSet( 'chainIssues', [] )
			->assertSet( 'hasChainIssues', false );
	} );

} );

describe( 'RedirectManager Computed Properties', function (): void {

	it( 'returns correct status options', function (): void {
		$component = Livewire::test( TestRedirectManager::class );

		$options = $component->get( 'statusOptions' );

		expect( $options )->toHaveKey( '' )
			->toHaveKey( 'active' )
			->toHaveKey( 'inactive' );
	} );

	it( 'returns correct match type options', function (): void {
		$component = Livewire::test( TestRedirectManager::class );

		$options = $component->get( 'matchTypeOptions' );

		expect( $options )->toHaveKey( '' )
			->toHaveKey( 'exact' )
			->toHaveKey( 'regex' )
			->toHaveKey( 'wildcard' );
	} );

	it( 'returns correct status code options', function (): void {
		$component = Livewire::test( TestRedirectManager::class );

		$options = $component->get( 'statusCodeOptions' );

		expect( $options )->toHaveKey( 301 )
			->toHaveKey( 302 )
			->toHaveKey( 307 )
			->toHaveKey( 308 );
	} );

	it( 'returns correct table headers', function (): void {
		$component = Livewire::test( TestRedirectManager::class );

		$headers = $component->get( 'tableHeaders' );

		expect( $headers )->toHaveKey( 'from_path' )
			->toHaveKey( 'to_path' )
			->toHaveKey( 'status_code' )
			->toHaveKey( 'match_type' )
			->toHaveKey( 'hits' )
			->toHaveKey( 'actions' );
	} );

	it( 'returns correct editor title for create', function (): void {
		Livewire::test( TestRedirectManager::class )
			->call( 'create' )
			->assertSet( 'isEditing', false );
	} );

	it( 'returns correct editor title for edit', function (): void {
		$redirect = Redirect::create( [
			'from_path' => '/edit-test',
			'to_path'   => '/edited',
		] );

		Livewire::test( TestRedirectManager::class )
			->call( 'edit', $redirect->id )
			->assertSet( 'isEditing', true );
	} );

} );

describe( 'RedirectManager Edge Cases', function (): void {

	it( 'handles edit of non-existent redirect gracefully', function (): void {
		Livewire::test( TestRedirectManager::class )
			->call( 'edit', 99999 )
			->assertSet( 'showEditor', false );
	} );

	it( 'handles toggle of non-existent redirect gracefully', function (): void {
		// Should not throw an exception and should not dispatch any notification
		Livewire::test( TestRedirectManager::class )
			->call( 'toggleActive', 99999 )
			->assertNotDispatched( 'notify' );
	} );

	it( 'handles delete of non-existent redirect gracefully', function (): void {
		Livewire::test( TestRedirectManager::class )
			->call( 'confirmDelete', 99999 )
			->assertSet( 'showDeleteConfirm', false );
	} );

	it( 'handles delete when deleting is null', function (): void {
		Livewire::test( TestRedirectManager::class )
			->set( 'deleting', null )
			->call( 'delete' )
			// Should not throw an exception and should not dispatch delete notification
			->assertNotDispatched( 'notify' );
	} );

	it( 'resets form after successful create', function (): void {
		Livewire::test( TestRedirectManager::class )
			->call( 'create' )
			->set( 'fromPath', '/test' )
			->set( 'toPath', '/destination' )
			->set( 'notes', 'Some notes' )
			->call( 'save' )
			->assertSet( 'fromPath', '' )
			->assertSet( 'toPath', '' )
			->assertSet( 'notes', '' );
	} );

	it( 'creates redirect with all fields', function (): void {
		Livewire::test( TestRedirectManager::class )
			->call( 'create' )
			->set( 'fromPath', '/full-test' )
			->set( 'toPath', '/full-destination' )
			->set( 'statusCode', 302 )
			->set( 'matchType', 'wildcard' )
			->set( 'notes', 'Full test notes' )
			->set( 'isActive', false )
			->call( 'save' );

		$redirect = Redirect::where( 'from_path', '/full-test' )->first();

		expect( $redirect )->not->toBeNull();
		expect( $redirect->to_path )->toBe( '/full-destination' );
		expect( $redirect->status_code )->toBe( 302 );
		expect( $redirect->match_type )->toBe( 'wildcard' );
		expect( $redirect->notes )->toBe( 'Full test notes' );
		expect( $redirect->is_active )->toBeFalse();
	} );

} );

describe( 'RedirectManager Pagination', function (): void {

	it( 'paginates redirects', function (): void {
		// Create 25 redirects (more than one page of 20)
		for ( $i = 1; $i <= 25; $i++ ) {
			Redirect::create( [
				'from_path' => "/page-{$i}",
				'to_path'   => "/new-{$i}",
			] );
		}

		$component = Livewire::test( TestRedirectManager::class );

		// Check pagination info in view - the stub shows count and total
		$component->assertSee( '20' ) // count on page
			->assertSee( '25' ); // total

		// Verify the computed property returns a paginator
		$redirects = $component->get( 'redirects' );
		expect( $redirects )->toBeInstanceOf( Illuminate\Contracts\Pagination\LengthAwarePaginator::class );
		expect( $redirects->count() )->toBe( 20 );
		expect( $redirects->total() )->toBe( 25 );
	} );

} );
