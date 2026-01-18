<?php

/**
 * RedirectManager Livewire Component.
 *
 * Full redirect management interface for admin use.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 * @copyright  2026 Jacob Martella
 * @license    MIT
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Livewire;

use ArtisanPackUI\SEO\Models\Redirect;
use ArtisanPackUI\SEO\Services\RedirectService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * RedirectManager component for managing URL redirects.
 *
 * Provides a full CRUD interface for redirect management including
 * search, filtering, sorting, and bulk operations.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class RedirectManager extends Component
{
	use WithPagination;

	/**
	 * Number of items per page.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const PER_PAGE = 20;

	/**
	 * Allowed sort fields for security validation.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	protected const ALLOWED_SORT_FIELDS = [
		'from_path',
		'to_path',
		'status_code',
		'match_type',
		'hits',
		'last_hit_at',
		'is_active',
		'created_at',
		'updated_at',
	];

	/**
	 * Allowed sort directions.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	protected const ALLOWED_SORT_DIRECTIONS = [ 'asc', 'desc' ];

	/**
	 * Default sort field.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected const DEFAULT_SORT_FIELD = 'hits';

	/**
	 * Default sort direction.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected const DEFAULT_SORT_DIRECTION = 'desc';

	/**
	 * Search query for filtering redirects.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	#[Url( except: '' )]
	public string $search = '';

	/**
	 * Filter by status (active, inactive, issues).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	#[Url( except: '' )]
	public string $filterStatus = '';

	/**
	 * Filter by match type (exact, regex, wildcard).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	#[Url( except: '' )]
	public string $filterMatchType = '';

	/**
	 * Sort field.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $sortField = 'hits';

	/**
	 * Sort direction.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $sortDirection = 'desc';

	/**
	 * Whether the editor modal is shown.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	public bool $showEditor = false;

	/**
	 * Whether the delete confirmation modal is shown.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	public bool $showDeleteConfirm = false;

	/**
	 * The redirect being edited (null for create).
	 *
	 * @since 1.0.0
	 *
	 * @var Redirect|null
	 */
	public ?Redirect $editing = null;

	/**
	 * The redirect pending deletion.
	 *
	 * @since 1.0.0
	 *
	 * @var Redirect|null
	 */
	public ?Redirect $deleting = null;

	/**
	 * Form field: from path.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $fromPath = '';

	/**
	 * Form field: to path.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $toPath = '';

	/**
	 * Form field: status code.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public int $statusCode = 301;

	/**
	 * Form field: match type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $matchType = 'exact';

	/**
	 * Form field: notes.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $notes = '';

	/**
	 * Form field: is active.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	public bool $isActive = true;

	/**
	 * Chain issues found by chain detection.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, array{id: int, from_path: string, to_path: string}>
	 */
	public array $chainIssues = [];

	/**
	 * Boot the component and validate sort parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function boot(): void
	{
		$this->validateSortParameters();
	}

	/**
	 * Reset pagination when search is updated.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function updatedSearch(): void
	{
		$this->resetPage();
	}

	/**
	 * Reset pagination when filter status is updated.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function updatedFilterStatus(): void
	{
		$this->resetPage();
	}

	/**
	 * Reset pagination when filter match type is updated.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function updatedFilterMatchType(): void
	{
		$this->resetPage();
	}

	/**
	 * Sort by a given field.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $field  The field to sort by.
	 *
	 * @return void
	 */
	public function sortBy( string $field ): void
	{
		// Validate the field is in the allowed list
		if ( ! in_array( $field, self::ALLOWED_SORT_FIELDS, true ) ) {
			return;
		}

		if ( $this->sortField === $field ) {
			$this->sortDirection = 'asc' === $this->sortDirection ? 'desc' : 'asc';
		} else {
			$this->sortField     = $field;
			$this->sortDirection = 'asc';
		}
	}

	/**
	 * Open the editor for creating a new redirect.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function create(): void
	{
		$this->authorizeAccess();

		$this->resetForm();
		$this->editing    = null;
		$this->showEditor = true;
	}

	/**
	 * Open the editor for editing an existing redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $redirectId  The ID of the redirect to edit.
	 *
	 * @return void
	 */
	public function edit( int $redirectId ): void
	{
		$this->authorizeAccess();

		$redirect = Redirect::find( $redirectId );

		if ( null === $redirect ) {
			return;
		}

		$this->editing    = $redirect;
		$this->fromPath   = $redirect->from_path;
		$this->toPath     = $redirect->to_path;
		$this->statusCode = $redirect->status_code;
		$this->matchType  = $redirect->match_type;
		$this->notes      = $redirect->notes ?? '';
		$this->isActive   = $redirect->is_active;
		$this->showEditor = true;
	}

	/**
	 * Close the editor modal.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function closeEditor(): void
	{
		$this->showEditor = false;
		$this->resetForm();
	}

	/**
	 * Save the redirect (create or update).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function save(): void
	{
		$this->authorizeAccess();

		$this->validate( [
			'fromPath'   => 'required|string|max:500',
			'toPath'     => 'required|string|max:500',
			'statusCode' => 'required|in:301,302,307,308',
			'matchType'  => 'required|in:exact,regex,wildcard',
			'notes'      => 'nullable|string|max:1000',
		] );

		$data = [
			'from_path'   => $this->fromPath,
			'to_path'     => $this->toPath,
			'status_code' => $this->statusCode,
			'match_type'  => $this->matchType,
			'notes'       => $this->notes ?: null,
			'is_active'   => $this->isActive,
		];

		$service = app( RedirectService::class );

		try {
			if ( null !== $this->editing ) {
				$service->update( $this->editing, $data );
				$this->dispatch( 'notify', message: __( 'Redirect updated successfully.' ) );
			} else {
				$service->create( $data );
				$this->dispatch( 'notify', message: __( 'Redirect created successfully.' ) );
			}

			$this->showEditor = false;
			$this->resetForm();
		} catch ( InvalidArgumentException $e ) {
			$this->addError( 'fromPath', $e->getMessage() );
		}
	}

	/**
	 * Open the delete confirmation modal.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $redirectId  The ID of the redirect to delete.
	 *
	 * @return void
	 */
	public function confirmDelete( int $redirectId ): void
	{
		$this->authorizeAccess();

		$redirect = Redirect::find( $redirectId );

		if ( null === $redirect ) {
			return;
		}

		$this->deleting          = $redirect;
		$this->showDeleteConfirm = true;
	}

	/**
	 * Close the delete confirmation modal.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function cancelDelete(): void
	{
		$this->showDeleteConfirm = false;
		$this->deleting          = null;
	}

	/**
	 * Delete the redirect.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function delete(): void
	{
		$this->authorizeAccess();

		if ( null === $this->deleting ) {
			return;
		}

		app( RedirectService::class )->delete( $this->deleting );

		$this->dispatch( 'notify', message: __( 'Redirect deleted.' ) );

		$this->showDeleteConfirm = false;
		$this->deleting          = null;
	}

	/**
	 * Toggle the active state of a redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $redirectId  The ID of the redirect to toggle.
	 *
	 * @return void
	 */
	public function toggleActive( int $redirectId ): void
	{
		$this->authorizeAccess();

		$redirect = Redirect::find( $redirectId );

		if ( null === $redirect ) {
			return;
		}

		app( RedirectService::class )->update( $redirect, [
			'is_active' => ! $redirect->is_active,
		] );

		// Refresh to get the updated state from the database
		$redirect->refresh();

		$message = $redirect->is_active
			? __( 'Redirect activated.' )
			: __( 'Redirect deactivated.' );

		$this->dispatch( 'notify', message: $message );
	}

	/**
	 * Check all redirects for chain issues.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function checkChains(): void
	{
		$this->authorizeAccess();

		$service         = app( RedirectService::class );
		$chainIssues     = [];
		$activeRedirects = $service->getActiveRedirects();

		foreach ( $activeRedirects as $redirect ) {
			if ( $service->checkForChains( $redirect ) ) {
				$chainIssues[] = [
					'id'        => $redirect->id,
					'from_path' => $redirect->from_path,
					'to_path'   => $redirect->to_path,
				];
			}
		}

		$this->chainIssues = $chainIssues;

		$count = count( $chainIssues );

		if ( $count > 0 ) {
			$this->dispatch(
				'notify',
				message: trans_choice(
					':count redirect chain issue found.|:count redirect chain issues found.',
					$count,
					[ 'count' => $count ],
				),
				type: 'warning',
			);
		} else {
			$this->dispatch(
				'notify',
				message: __( 'No redirect chain issues found.' ),
				type: 'success',
			);
		}
	}

	/**
	 * Clear chain issues.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clearChainIssues(): void
	{
		$this->chainIssues = [];
	}

	/**
	 * Get the paginated redirects.
	 *
	 * @since 1.0.0
	 *
	 * @return LengthAwarePaginator<Redirect>
	 */
	#[Computed]
	public function redirects(): LengthAwarePaginator
	{
		return Redirect::query()
			->when(
				$this->search,
				fn ( $query ) => $query->where( function ( $q ): void {
					$q->where( 'from_path', 'like', '%' . $this->search . '%' )
						->orWhere( 'to_path', 'like', '%' . $this->search . '%' );
				} ),
			)
			->when( 'active' === $this->filterStatus, fn ( $query ) => $query->active() )
			->when( 'inactive' === $this->filterStatus, fn ( $query ) => $query->inactive() )
			->when( $this->filterMatchType, fn ( $query ) => $query->ofType( $this->filterMatchType ) )
			->orderBy( $this->sortField, $this->sortDirection )
			->paginate( self::PER_PAGE );
	}

	/**
	 * Get redirect statistics.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	#[Computed]
	public function statistics(): array
	{
		return app( RedirectService::class )->getStatistics();
	}

	/**
	 * Get the status filter options.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	#[Computed]
	public function statusOptions(): array
	{
		return [
			''         => __( 'All Statuses' ),
			'active'   => __( 'Active' ),
			'inactive' => __( 'Inactive' ),
		];
	}

	/**
	 * Get the match type filter options.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	#[Computed]
	public function matchTypeOptions(): array
	{
		return [
			''         => __( 'All Match Types' ),
			'exact'    => __( 'Exact Match' ),
			'regex'    => __( 'Regular Expression' ),
			'wildcard' => __( 'Wildcard' ),
		];
	}

	/**
	 * Get the status code options for the form.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	#[Computed]
	public function statusCodeOptions(): array
	{
		return [
			301 => __( '301 - Moved Permanently' ),
			302 => __( '302 - Found (Temporary)' ),
			307 => __( '307 - Temporary Redirect' ),
			308 => __( '308 - Permanent Redirect' ),
		];
	}

	/**
	 * Get the match type options for the form.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	#[Computed]
	public function formMatchTypeOptions(): array
	{
		return [
			'exact'    => __( 'Exact Match' ),
			'regex'    => __( 'Regular Expression' ),
			'wildcard' => __( 'Wildcard' ),
		];
	}

	/**
	 * Get the table headers for sorting.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{label: string, sortable: bool}>
	 */
	#[Computed]
	public function tableHeaders(): array
	{
		return [
			'from_path'   => [ 'label' => __( 'From' ), 'sortable' => true ],
			'to_path'     => [ 'label' => __( 'To' ), 'sortable' => true ],
			'status_code' => [ 'label' => __( 'Status' ), 'sortable' => true ],
			'match_type'  => [ 'label' => __( 'Type' ), 'sortable' => true ],
			'hits'        => [ 'label' => __( 'Hits' ), 'sortable' => true ],
			'last_hit_at' => [ 'label' => __( 'Last Hit' ), 'sortable' => true ],
			'is_active'   => [ 'label' => __( 'Active' ), 'sortable' => true ],
			'actions'     => [ 'label' => __( 'Actions' ), 'sortable' => false ],
		];
	}

	/**
	 * Check if editing an existing redirect.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function isEditing(): bool
	{
		return null !== $this->editing;
	}

	/**
	 * Get the editor title.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	#[Computed]
	public function editorTitle(): string
	{
		return $this->isEditing ? __( 'Edit Redirect' ) : __( 'Create Redirect' );
	}

	/**
	 * Check if there are chain issues.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function hasChainIssues(): bool
	{
		return count( $this->chainIssues ) > 0;
	}

	/**
	 * Render the component.
	 *
	 * @since 1.0.0
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'seo::livewire.redirect-manager' );
	}

	/**
	 * Reset the form fields to defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function resetForm(): void
	{
		$this->fromPath   = '';
		$this->toPath     = '';
		$this->statusCode = 301;
		$this->matchType  = 'exact';
		$this->notes      = '';
		$this->isActive   = true;
		$this->editing    = null;
		$this->resetErrorBag();
	}

	/**
	 * Validate and normalize sort parameters.
	 *
	 * Ensures sortField and sortDirection contain valid values,
	 * resetting to defaults if invalid values are detected.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function validateSortParameters(): void
	{
		// Validate sort field
		if ( ! in_array( $this->sortField, self::ALLOWED_SORT_FIELDS, true ) ) {
			$this->sortField = self::DEFAULT_SORT_FIELD;
		}

		// Validate sort direction
		if ( ! in_array( $this->sortDirection, self::ALLOWED_SORT_DIRECTIONS, true ) ) {
			$this->sortDirection = self::DEFAULT_SORT_DIRECTION;
		}
	}

	/**
	 * Authorize the user for redirect management.
	 *
	 * Checks if authorization is enabled in config and if so,
	 * verifies the user has the required ability.
	 *
	 * @since 1.0.0
	 *
	 * @throws AccessDeniedHttpException If authorization fails.
	 *
	 * @return void
	 */
	protected function authorizeAccess(): void
	{
		// Skip if authorization is not enabled
		if ( ! config( 'seo.redirects.authorization_enabled', false ) ) {
			return;
		}

		$ability = config( 'seo.redirects.authorization_ability', 'manage-redirects' );

		if ( ! Gate::allows( $ability ) ) {
			throw new AccessDeniedHttpException( __( 'You are not authorized to manage redirects.' ) );
		}
	}
}
