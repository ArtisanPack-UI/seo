<div class="redirect-manager">
	{{-- Statistics Cards --}}
	<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
		<x-artisanpack-stat
			:title="__( 'Total Redirects' )"
			:value="$this->statistics['total'] ?? 0"
			icon="o-arrows-right-left"
		/>
		<x-artisanpack-stat
			:title="__( 'Active' )"
			:value="$this->statistics['active'] ?? 0"
			icon="o-check-circle"
			color="success"
		/>
		<x-artisanpack-stat
			:title="__( 'Inactive' )"
			:value="$this->statistics['inactive'] ?? 0"
			icon="o-pause-circle"
			color="warning"
		/>
		<x-artisanpack-stat
			:title="__( 'Total Hits' )"
			:value="number_format( $this->statistics['total_hits'] ?? 0 )"
			icon="o-cursor-arrow-rays"
			color="info"
		/>
	</div>

	{{-- Chain Issues Alert --}}
	@if ( $this->hasChainIssues )
		<x-artisanpack-alert type="warning" class="mb-4" dismissible wire:click="clearChainIssues">
			<div class="flex items-center justify-between">
				<div>
					<strong>{{ __( 'Redirect Chain Issues Detected' ) }}</strong>
					<p class="text-sm mt-1">
						{{ trans_choice(
							':count redirect forms a chain that could cause issues.|:count redirects form chains that could cause issues.',
							count( $chainIssues ),
							[ 'count' => count( $chainIssues ) ]
						) }}
					</p>
				</div>
			</div>
			<ul class="mt-2 text-sm list-disc list-inside">
				@foreach ( $chainIssues as $issue )
					<li>
						<code>{{ $issue['from_path'] }}</code> &rarr; <code>{{ $issue['to_path'] }}</code>
						<x-artisanpack-button
							wire:click.stop="edit({{ $issue['id'] }})"
							size="xs"
							outline
							class="ml-2"
						>
							{{ __( 'Edit' ) }}
						</x-artisanpack-button>
					</li>
				@endforeach
			</ul>
		</x-artisanpack-alert>
	@endif

	{{-- Toolbar --}}
	<div class="flex flex-col md:flex-row gap-4 mb-4">
		{{-- Search --}}
		<div class="flex-1">
			<x-artisanpack-input
				wire:model.live.debounce.300ms="search"
				:placeholder="__( 'Search redirects...' )"
				icon="o-magnifying-glass"
			/>
		</div>

		{{-- Filters --}}
		<div class="flex gap-2">
			<x-artisanpack-select
				wire:model.live="filterStatus"
				:options="$this->statusOptions"
			/>

			<x-artisanpack-select
				wire:model.live="filterMatchType"
				:options="$this->matchTypeOptions"
			/>
		</div>

		{{-- Actions --}}
		<div class="flex gap-2">
			<x-artisanpack-button
				wire:click="checkChains"
				outline
				icon="o-link"
				:title="__( 'Check for redirect chains' )"
			>
				{{ __( 'Check Chains' ) }}
			</x-artisanpack-button>

			<x-artisanpack-button
				wire:click="create"
				color="primary"
				icon="o-plus"
			>
				{{ __( 'Add Redirect' ) }}
			</x-artisanpack-button>
		</div>
	</div>

	{{-- Redirects Table --}}
	<x-artisanpack-card>
		<div class="overflow-x-auto">
			<table class="table table-zebra w-full">
				<thead>
					<tr>
						@foreach ( $this->tableHeaders as $field => $header )
							<th
								@if ( $header['sortable'] )
									class="cursor-pointer hover:bg-base-200"
									wire:click="sortBy('{{ $field }}')"
								@endif
							>
								<div class="flex items-center gap-1">
									{{ $header['label'] }}
									@if ( $header['sortable'] && $sortField === $field )
										<x-artisanpack-icon
											:name="$sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down'"
											class="w-4 h-4"
										/>
									@endif
								</div>
							</th>
						@endforeach
					</tr>
				</thead>
				<tbody>
					@forelse ( $this->redirects as $redirect )
						<tr wire:key="redirect-{{ $redirect->id }}">
							<td class="max-w-xs truncate" title="{{ $redirect->from_path }}">
								<code class="text-sm">{{ $redirect->from_path }}</code>
							</td>
							<td class="max-w-xs truncate" title="{{ $redirect->to_path }}">
								<code class="text-sm">{{ $redirect->to_path }}</code>
							</td>
							<td>
								<x-artisanpack-badge
									:value="(string) $redirect->status_code"
									:color="$redirect->isPermanent() ? 'primary' : 'secondary'"
								/>
							</td>
							<td>
								<x-artisanpack-badge
									:value="$redirect->getMatchTypeLabel()"
									:color="match( $redirect->match_type ) {
										'exact' => 'info',
										'regex' => 'warning',
										'wildcard' => 'accent',
										default => 'neutral',
									}"
								/>
							</td>
							<td>
								<span class="font-mono">{{ number_format( $redirect->hits ) }}</span>
							</td>
							<td>
								@if ( $redirect->last_hit_at )
									<span class="text-sm" title="{{ $redirect->last_hit_at->format( 'Y-m-d H:i:s' ) }}">
										{{ $redirect->last_hit_at->diffForHumans() }}
									</span>
								@else
									<span class="text-base-content/50 text-sm">{{ __( 'Never' ) }}</span>
								@endif
							</td>
							<td>
								<x-artisanpack-toggle
									wire:click="toggleActive({{ $redirect->id }})"
									:checked="$redirect->is_active"
								/>
							</td>
							<td>
								<div class="flex gap-1">
									<x-artisanpack-button
										wire:click="edit({{ $redirect->id }})"
										size="sm"
										icon="o-pencil"
										outline
										:title="__( 'Edit redirect' )"
									/>
									<x-artisanpack-button
										wire:click="confirmDelete({{ $redirect->id }})"
										size="sm"
										icon="o-trash"
										color="error"
										outline
										:title="__( 'Delete redirect' )"
									/>
								</div>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="{{ count( $this->tableHeaders ) }}" class="text-center py-8">
								<div class="text-base-content/50">
									<x-artisanpack-icon name="o-arrows-right-left" class="w-12 h-12 mx-auto mb-2 opacity-50" />
									<p>{{ __( 'No redirects found.' ) }}</p>
									@if ( $search || $filterStatus || $filterMatchType )
										<p class="text-sm mt-1">{{ __( 'Try adjusting your filters.' ) }}</p>
									@else
										<x-artisanpack-button
											wire:click="create"
											size="sm"
											color="primary"
											class="mt-2"
										>
											{{ __( 'Create your first redirect' ) }}
										</x-artisanpack-button>
									@endif
								</div>
							</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		{{-- Pagination --}}
		@if ( $this->redirects->hasPages() )
			<div class="mt-4">
				{{ $this->redirects->links() }}
			</div>
		@endif
	</x-artisanpack-card>

	{{-- Editor Modal --}}
	<x-artisanpack-modal wire:model="showEditor" :title="$this->editorTitle">
		<form wire:submit="save">
			<div class="space-y-4">
				{{-- From Path --}}
				<x-artisanpack-input
					wire:model="fromPath"
					:label="__( 'From Path' )"
					:placeholder="__( '/old-page' )"
					:hint="__( 'The URL path to redirect from (e.g., /old-page)' )"
					:error="$errors->first( 'fromPath' )"
					required
				/>

				{{-- To Path --}}
				<x-artisanpack-input
					wire:model="toPath"
					:label="__( 'To Path' )"
					:placeholder="__( '/new-page or https://example.com/page' )"
					:hint="__( 'The destination URL or path' )"
					:error="$errors->first( 'toPath' )"
					required
				/>

				{{-- Status Code and Match Type --}}
				<div class="grid grid-cols-2 gap-4">
					<x-artisanpack-select
						wire:model="statusCode"
						:label="__( 'Status Code' )"
						:options="$this->statusCodeOptions"
						:error="$errors->first( 'statusCode' )"
						required
					/>

					<x-artisanpack-select
						wire:model="matchType"
						:label="__( 'Match Type' )"
						:options="$this->formMatchTypeOptions"
						:error="$errors->first( 'matchType' )"
						required
					/>
				</div>

				{{-- Match Type Hints --}}
				<div class="text-sm text-base-content/70">
					@if ( $matchType === 'exact' )
						<p><strong>{{ __( 'Exact Match:' ) }}</strong> {{ __( 'The path must match exactly.' ) }}</p>
					@elseif ( $matchType === 'regex' )
						<p><strong>{{ __( 'Regular Expression:' ) }}</strong> {{ __( 'Use regex patterns. Capture groups ($1, $2) can be used in the destination.' ) }}</p>
						<p class="mt-1 text-xs">{{ __( 'Example: ^/blog/(\d+)$ redirects to /posts/$1' ) }}</p>
					@elseif ( $matchType === 'wildcard' )
						<p><strong>{{ __( 'Wildcard:' ) }}</strong> {{ __( 'Use * to match any characters. Matched content can be used in the destination.' ) }}</p>
						<p class="mt-1 text-xs">{{ __( 'Example: /category/* redirects to /topics/*' ) }}</p>
					@endif
				</div>

				{{-- Notes --}}
				<x-artisanpack-textarea
					wire:model="notes"
					:label="__( 'Notes' )"
					:placeholder="__( 'Optional notes about this redirect...' )"
					rows="2"
				/>

				{{-- Is Active --}}
				<x-artisanpack-checkbox
					wire:model="isActive"
					:label="__( 'Active' )"
					:hint="__( 'Inactive redirects will not be processed' )"
				/>
			</div>

			<x-slot:actions>
				<x-artisanpack-button
					type="button"
					wire:click="closeEditor"
					outline
				>
					{{ __( 'Cancel' ) }}
				</x-artisanpack-button>
				<x-artisanpack-button
					type="submit"
					color="primary"
				>
					<span wire:loading.remove wire:target="save">
						{{ $this->isEditing ? __( 'Update Redirect' ) : __( 'Create Redirect' ) }}
					</span>
					<span wire:loading wire:target="save">
						{{ __( 'Saving...' ) }}
					</span>
				</x-artisanpack-button>
			</x-slot:actions>
		</form>
	</x-artisanpack-modal>

	{{-- Delete Confirmation Modal --}}
	<x-artisanpack-modal wire:model="showDeleteConfirm" :title="__( 'Delete Redirect' )">
		<p>{{ __( 'Are you sure you want to delete this redirect?' ) }}</p>

		@if ( $deleting )
			<div class="mt-4 p-4 bg-base-200 rounded-lg">
				<p class="text-sm">
					<strong>{{ __( 'From:' ) }}</strong>
					<code>{{ $deleting->from_path }}</code>
				</p>
				<p class="text-sm mt-1">
					<strong>{{ __( 'To:' ) }}</strong>
					<code>{{ $deleting->to_path }}</code>
				</p>
				@if ( $deleting->hits > 0 )
					<p class="text-sm mt-2 text-warning">
						{{ trans_choice(
							'This redirect has been hit :count time.|This redirect has been hit :count times.',
							$deleting->hits,
							[ 'count' => number_format( $deleting->hits ) ]
						) }}
					</p>
				@endif
			</div>
		@endif

		<x-slot:actions>
			<x-artisanpack-button
				wire:click="cancelDelete"
				outline
			>
				{{ __( 'Cancel' ) }}
			</x-artisanpack-button>
			<x-artisanpack-button
				wire:click="delete"
				color="error"
			>
				<span wire:loading.remove wire:target="delete">{{ __( 'Delete' ) }}</span>
				<span wire:loading wire:target="delete">{{ __( 'Deleting...' ) }}</span>
			</x-artisanpack-button>
		</x-slot:actions>
	</x-artisanpack-modal>
</div>
