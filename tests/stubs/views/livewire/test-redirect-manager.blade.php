<div data-test="redirect-manager">
	{{-- Statistics --}}
	<div data-test="statistics">
		<span data-test="stat-total">{{ $this->statistics['total'] ?? 0 }}</span>
		<span data-test="stat-active">{{ $this->statistics['active'] ?? 0 }}</span>
		<span data-test="stat-inactive">{{ $this->statistics['inactive'] ?? 0 }}</span>
		<span data-test="stat-total-hits">{{ $this->statistics['total_hits'] ?? 0 }}</span>
	</div>

	{{-- Chain Issues --}}
	@if ( $this->hasChainIssues )
		<div data-test="chain-issues">
			<span data-test="chain-issues-count">{{ count( $chainIssues ) }}</span>
			@foreach ( $chainIssues as $issue )
				<div data-test="chain-issue-{{ $issue['id'] }}">
					<span data-test="chain-from">{{ $issue['from_path'] }}</span>
					<span data-test="chain-to">{{ $issue['to_path'] }}</span>
				</div>
			@endforeach
		</div>
	@endif

	{{-- Search and Filters --}}
	<div data-test="toolbar">
		<input
			type="text"
			wire:model.live="search"
			data-test="search-input"
			value="{{ $search }}"
		/>
		<select wire:model.live="filterStatus" data-test="filter-status">
			@foreach ( $this->statusOptions as $value => $label )
				<option value="{{ $value }}">{{ $label }}</option>
			@endforeach
		</select>
		<select wire:model.live="filterMatchType" data-test="filter-match-type">
			@foreach ( $this->matchTypeOptions as $value => $label )
				<option value="{{ $value }}">{{ $label }}</option>
			@endforeach
		</select>
		<button wire:click="checkChains" data-test="check-chains-btn">Check Chains</button>
		<button wire:click="create" data-test="create-btn">Add Redirect</button>
	</div>

	{{-- Sorting Info --}}
	<div data-test="sort-info">
		<span data-test="sort-field">{{ $sortField }}</span>
		<span data-test="sort-direction">{{ $sortDirection }}</span>
	</div>

	{{-- Redirects Table --}}
	<div data-test="redirects-table">
		<span data-test="redirect-count">{{ $this->redirects->count() }}</span>
		<span data-test="redirect-total">{{ $this->redirects->total() }}</span>

		@foreach ( $this->redirects as $redirect )
			<div data-test="redirect-row-{{ $redirect->id }}">
				<span data-test="from-path">{{ $redirect->from_path }}</span>
				<span data-test="to-path">{{ $redirect->to_path }}</span>
				<span data-test="status-code">{{ $redirect->status_code }}</span>
				<span data-test="match-type">{{ $redirect->match_type }}</span>
				<span data-test="hits">{{ $redirect->hits }}</span>
				<span data-test="is-active">{{ $redirect->is_active ? 'true' : 'false' }}</span>
				<span data-test="last-hit">{{ $redirect->last_hit_at?->toIso8601String() ?? 'null' }}</span>
				<button wire:click="edit({{ $redirect->id }})" data-test="edit-btn">Edit</button>
				<button wire:click="toggleActive({{ $redirect->id }})" data-test="toggle-btn">Toggle</button>
				<button wire:click="confirmDelete({{ $redirect->id }})" data-test="delete-btn">Delete</button>
			</div>
		@endforeach

		@if ( $this->redirects->isEmpty() )
			<div data-test="empty-state">No redirects found.</div>
		@endif
	</div>

	{{-- Pagination Info --}}
	@if ( $this->redirects->hasPages() )
		<div data-test="pagination">
			<span data-test="current-page">{{ $this->redirects->currentPage() }}</span>
			<span data-test="last-page">{{ $this->redirects->lastPage() }}</span>
		</div>
	@endif

	{{-- Editor Modal --}}
	@if ( $showEditor )
		<div data-test="editor-modal">
			<span data-test="editor-title">{{ $this->editorTitle }}</span>
			<span data-test="is-editing">{{ $this->isEditing ? 'true' : 'false' }}</span>

			<input type="text" wire:model="fromPath" data-test="form-from-path" value="{{ $fromPath }}" />
			<input type="text" wire:model="toPath" data-test="form-to-path" value="{{ $toPath }}" />
			<select wire:model="statusCode" data-test="form-status-code">
				@foreach ( $this->statusCodeOptions as $value => $label )
					<option value="{{ $value }}" @if ( $statusCode == $value ) selected @endif>{{ $label }}</option>
				@endforeach
			</select>
			<select wire:model="matchType" data-test="form-match-type">
				@foreach ( $this->formMatchTypeOptions as $value => $label )
					<option value="{{ $value }}" @if ( $matchType === $value ) selected @endif>{{ $label }}</option>
				@endforeach
			</select>
			<textarea wire:model="notes" data-test="form-notes">{{ $notes }}</textarea>
			<input type="checkbox" wire:model="isActive" data-test="form-is-active" @if ( $isActive ) checked @endif />

			<button wire:click="save" data-test="save-btn">Save</button>
			<button wire:click="closeEditor" data-test="close-editor-btn">Cancel</button>

			@error( 'fromPath' )
				<span data-test="error-from-path">{{ $message }}</span>
			@enderror
		</div>
	@endif

	{{-- Delete Confirmation Modal --}}
	@if ( $showDeleteConfirm )
		<div data-test="delete-modal">
			<span data-test="deleting-from">{{ $deleting?->from_path }}</span>
			<span data-test="deleting-to">{{ $deleting?->to_path }}</span>
			<span data-test="deleting-hits">{{ $deleting?->hits }}</span>
			<button wire:click="delete" data-test="confirm-delete-btn">Delete</button>
			<button wire:click="cancelDelete" data-test="cancel-delete-btn">Cancel</button>
		</div>
	@endif
</div>
