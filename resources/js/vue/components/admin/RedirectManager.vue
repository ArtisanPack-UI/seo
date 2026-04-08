<!--
  RedirectManager component.

  CRUD table for URL redirects with filtering, sorting, pagination,
  bulk actions, and a URL test tool.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.1.0
-->

<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue';

import {
	Alert,
	Badge,
	Button,
	Card,
	Checkbox,
	Input,
	Modal,
	Pagination,
	Select,
	Textarea,
} from '@artisanpack-ui/vue';

import type { UseApiOptions } from '../../composables/useApi';
import { useRedirects } from '../../composables/useRedirects';

import type { Redirect, RedirectMatchType, RedirectStatusCode } from '../../../types/redirect';
import type { RedirectSortField, SortDirection } from '../../../types/components';

const STATUS_CODE_OPTIONS = [
	{ value: '', name: 'All Status Codes' },
	{ value: '301', name: '301 - Permanent' },
	{ value: '302', name: '302 - Temporary' },
	{ value: '307', name: '307 - Temporary (Strict)' },
	{ value: '308', name: '308 - Permanent (Strict)' },
];

const MATCH_TYPE_OPTIONS = [
	{ value: '', name: 'All Match Types' },
	{ value: 'exact', name: 'Exact' },
	{ value: 'regex', name: 'Regex' },
	{ value: 'wildcard', name: 'Wildcard' },
];

const FORM_STATUS_OPTIONS = STATUS_CODE_OPTIONS.slice( 1 );
const FORM_MATCH_OPTIONS  = MATCH_TYPE_OPTIONS.slice( 1 );

interface RedirectFormData {
	from_path: string;
	to_path: string;
	status_code: string;
	match_type: string;
	is_active: boolean;
	notes: string;
}

const EMPTY_FORM: RedirectFormData = {
	from_path: '',
	to_path: '',
	status_code: '301',
	match_type: 'exact',
	is_active: true,
	notes: '',
};

const props = withDefaults( defineProps<UseApiOptions & {
	className?: string;
}>(), {
	csrfToken: undefined,
	authorization: undefined,
	credentials: undefined,
	className: undefined,
} );

const {
	redirects,
	meta,
	loading,
	mutating,
	error,
	validationErrors,
	filters,
	sort,
	page,
	createRedirect,
	updateRedirect,
	deleteRedirect,
	bulkAction,
	testUrl,
	setFilters,
	setSort,
	setPage,
} = useRedirects( {
	baseUrl: props.baseUrl,
	csrfToken: props.csrfToken,
	authorization: props.authorization,
	credentials: props.credentials,
} );

const showCreateModal  = ref( false );
const editingRedirect  = ref<Redirect | null>( null );
const formData         = ref<RedirectFormData>( { ...EMPTY_FORM } );
const selectedIds      = ref<Set<number>>( new Set() );
const testUrlValue     = ref( '' );
const testResult       = ref<string | null>( null );
const searchValue      = ref( '' );
let searchTimer: ReturnType<typeof setTimeout> | null = null;

onUnmounted( () => {
	if ( searchTimer ) {
		clearTimeout( searchTimer );
		searchTimer = null;
	}
} );

// Clear selection when visible list changes
watch( [redirects, page, filters], () => {
	selectedIds.value = new Set();
}, { deep: true } );

function handleSearchChange( value: string ): void {
	searchValue.value = String( value );

	if ( searchTimer ) {
		clearTimeout( searchTimer );
	}

	searchTimer = setTimeout( () => {
		setFilters( { ...filters.value, search: searchValue.value || undefined } );
	}, 300 );
}

function openCreate(): void {
	formData.value = { ...EMPTY_FORM };
	showCreateModal.value = true;
}

function openEdit( redirect: Redirect ): void {
	formData.value = {
		from_path: redirect.from_path,
		to_path: redirect.to_path,
		status_code: String( redirect.status_code ),
		match_type: redirect.match_type,
		is_active: redirect.is_active,
		notes: redirect.notes ?? '',
	};
	editingRedirect.value = redirect;
}

function closeModals(): void {
	showCreateModal.value = false;
	editingRedirect.value = null;
	formData.value        = { ...EMPTY_FORM };
}

async function handleCreate(): Promise<void> {
	const result = await createRedirect( {
		...formData.value,
		status_code: parseInt( formData.value.status_code, 10 ),
	} );

	if ( result ) {
		closeModals();
	}
}

async function handleUpdate(): Promise<void> {
	if ( !editingRedirect.value ) {
		return;
	}

	const result = await updateRedirect( editingRedirect.value.id, {
		...formData.value,
		status_code: parseInt( formData.value.status_code, 10 ),
	} );

	if ( result ) {
		closeModals();
	}
}

async function handleDelete( id: number ): Promise<void> {
	if ( window.confirm( 'Are you sure you want to delete this redirect?' ) ) {
		await deleteRedirect( id );
	}
}

async function handleBulkDelete(): Promise<void> {
	if ( 0 === selectedIds.value.size ) {
		return;
	}

	if ( window.confirm( `Delete ${ selectedIds.value.size } selected redirect(s)?` ) ) {
		const success = await bulkAction( 'delete', Array.from( selectedIds.value ) );

		if ( success ) {
			selectedIds.value = new Set();
		}
	}
}

function toggleSelectAll(): void {
	if ( selectedIds.value.size === redirects.value.length ) {
		selectedIds.value = new Set();
	} else {
		selectedIds.value = new Set( redirects.value.map( ( r ) => r.id ) );
	}
}

function toggleSelect( id: number ): void {
	const next = new Set( selectedIds.value );

	if ( next.has( id ) ) {
		next.delete( id );
	} else {
		next.add( id );
	}

	selectedIds.value = next;
}

async function handleTestUrl(): Promise<void> {
	if ( !testUrlValue.value.trim() ) {
		return;
	}

	const result = await testUrl( testUrlValue.value );

	if ( null === result ) {
		testResult.value = 'Error testing URL — please try again.';
	} else if ( result.matched ) {
		testResult.value = `Matches redirect #${ result.matched.id }: ${ result.matched.from_path } -> ${ result.resolved_destination }`;
	} else {
		testResult.value = 'No matching redirect found.';
	}
}

function handleSort( field: RedirectSortField ): void {
	const direction: SortDirection = sort.value.field === field && 'asc' === sort.value.direction ? 'desc' : 'asc';
	setSort( { field, direction } );
}

function sortIndicator( field: RedirectSortField ): string {
	if ( sort.value.field !== field ) {
		return '';
	}

	return 'asc' === sort.value.direction ? ' \u2191' : ' \u2193';
}

function handleSortKeydown( event: KeyboardEvent, field: RedirectSortField ): void {
	if ( 'Enter' === event.key || ' ' === event.key ) {
		event.preventDefault();
		handleSort( field );
	}
}

const allSelected = computed( () => redirects.value.length > 0 && selectedIds.value.size === redirects.value.length );
</script>

<template>
	<div :class="className">
		<Alert v-if="error" color="error" class="mb-4">{{ error }}</Alert>

		<!-- Toolbar -->
		<div class="flex flex-wrap items-center gap-2 mb-4">
			<Input
				:model-value="searchValue"
				@update:model-value="handleSearchChange( String( $event ) )"
				placeholder="Search redirects..."
				class="w-64"
			/>

			<Select
				:model-value="filters.status_code ? String( filters.status_code ) : ''"
				@update:model-value="setFilters( { ...filters, status_code: $event ? parseInt( String( $event ), 10 ) as RedirectStatusCode : undefined } )"
				:options="STATUS_CODE_OPTIONS"
				option-value="value"
				option-label="name"
				class="w-48"
			/>

			<Select
				:model-value="filters.match_type ?? ''"
				@update:model-value="setFilters( { ...filters, match_type: ( String( $event ) || undefined ) as RedirectMatchType | undefined } )"
				:options="MATCH_TYPE_OPTIONS"
				option-value="value"
				option-label="name"
				class="w-44"
			/>

			<div class="ml-auto flex gap-2">
				<Button
					v-if="selectedIds.size > 0"
					color="error"
					size="sm"
					@click="handleBulkDelete"
					:disabled="mutating"
				>
					Delete Selected ({{ selectedIds.size }})
				</Button>

				<Button color="primary" size="sm" @click="openCreate">
					Add Redirect
				</Button>
			</div>
		</div>

		<!-- URL Test Tool -->
		<Card class="mb-4">
			<div class="p-3 flex gap-2 items-end">
				<Input
					label="Test URL"
					v-model="testUrlValue"
					placeholder="Enter a URL path to test..."
					class="flex-1"
				/>
				<Button size="sm" @click="handleTestUrl">Test</Button>
			</div>
			<div v-if="testResult" class="px-3 pb-3">
				<Alert color="info">{{ testResult }}</Alert>
			</div>
		</Card>

		<!-- Table -->
		<Loading v-if="loading" />

		<template v-else>
			<div class="overflow-x-auto">
				<table class="table table-zebra w-full">
					<thead>
						<tr>
							<th class="w-10">
								<Checkbox
									:model-value="allSelected"
									@update:model-value="toggleSelectAll"
									aria-label="Select all"
								/>
							</th>
							<th
								class="cursor-pointer select-none"
								tabindex="0"
								@click="handleSort( 'from_path' )"
								@keydown="handleSortKeydown( $event, 'from_path' )"
								:aria-sort="sort.field === 'from_path' ? ( sort.direction === 'asc' ? 'ascending' : 'descending' ) : undefined"
							>
								From{{ sortIndicator( 'from_path' ) }}
							</th>
							<th
								class="cursor-pointer select-none"
								tabindex="0"
								@click="handleSort( 'to_path' )"
								@keydown="handleSortKeydown( $event, 'to_path' )"
								:aria-sort="sort.field === 'to_path' ? ( sort.direction === 'asc' ? 'ascending' : 'descending' ) : undefined"
							>
								To{{ sortIndicator( 'to_path' ) }}
							</th>
							<th
								class="cursor-pointer select-none w-24"
								tabindex="0"
								@click="handleSort( 'status_code' )"
								@keydown="handleSortKeydown( $event, 'status_code' )"
								:aria-sort="sort.field === 'status_code' ? ( sort.direction === 'asc' ? 'ascending' : 'descending' ) : undefined"
							>
								Status{{ sortIndicator( 'status_code' ) }}
							</th>
							<th class="w-24">Type</th>
							<th
								class="cursor-pointer select-none w-20"
								tabindex="0"
								@click="handleSort( 'hits' )"
								@keydown="handleSortKeydown( $event, 'hits' )"
								:aria-sort="sort.field === 'hits' ? ( sort.direction === 'asc' ? 'ascending' : 'descending' ) : undefined"
							>
								Hits{{ sortIndicator( 'hits' ) }}
							</th>
							<th class="w-28"></th>
						</tr>
					</thead>
					<tbody>
						<tr v-if="redirects.length === 0">
							<td colspan="7" class="text-center text-base-content/50 py-8">
								No redirects found.
							</td>
						</tr>
						<tr v-for="redirect in redirects" :key="redirect.id">
							<td>
								<Checkbox
									:model-value="selectedIds.has( redirect.id )"
									@update:model-value="toggleSelect( redirect.id )"
									:aria-label="`Select redirect ${ redirect.id }`"
								/>
							</td>
							<td class="font-mono text-sm break-all">
								{{ redirect.from_path }}
								<Badge v-if="!redirect.is_active" color="warning" size="xs" class="ml-1">Inactive</Badge>
							</td>
							<td class="font-mono text-sm break-all">{{ redirect.to_path }}</td>
							<td>
								<Badge :color="redirect.is_permanent ? 'info' : 'warning'" size="sm">
									{{ redirect.status_code }}
								</Badge>
							</td>
							<td class="text-sm">{{ redirect.match_type_label }}</td>
							<td class="text-sm">{{ redirect.hits }}</td>
							<td>
								<div class="flex gap-1">
									<Button size="xs" @click="openEdit( redirect )" aria-label="Edit">Edit</Button>
									<Button size="xs" color="error" @click="handleDelete( redirect.id )" :disabled="mutating" aria-label="Delete">Delete</Button>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div v-if="meta && meta.last_page > 1" class="mt-4 flex justify-center">
				<Pagination
					:current-page="page"
					:total-pages="meta.last_page"
					@update:current-page="setPage"
				/>
			</div>
		</template>

		<!-- Create Modal -->
		<Modal :open="showCreateModal" @update:open="closeModals" title="Create Redirect">
			<div class="flex flex-col gap-4">
				<Input v-model="formData.from_path" label="From Path" hint="The URL path to redirect from (e.g. /old-page)" :error="validationErrors.from_path?.[0]" required />
				<Input v-model="formData.to_path" label="To Path" hint="The destination URL or path" :error="validationErrors.to_path?.[0]" required />
				<div class="grid grid-cols-2 gap-4">
					<Select v-model="formData.status_code" label="Status Code" :options="FORM_STATUS_OPTIONS" option-value="value" option-label="name" :error="validationErrors.status_code?.[0]" />
					<Select v-model="formData.match_type" label="Match Type" :options="FORM_MATCH_OPTIONS" option-value="value" option-label="name" :error="validationErrors.match_type?.[0]" />
				</div>
				<Checkbox v-model="formData.is_active" label="Active" />
				<Textarea v-model="formData.notes" label="Notes" :rows="2" :error="validationErrors.notes?.[0]" />
			</div>

			<template #actions>
				<Button @click="closeModals">Cancel</Button>
				<Button color="primary" @click="handleCreate" :disabled="mutating">
					{{ mutating ? 'Creating...' : 'Create' }}
				</Button>
			</template>
		</Modal>

		<!-- Edit Modal -->
		<Modal :open="editingRedirect !== null" @update:open="closeModals" title="Edit Redirect">
			<div class="flex flex-col gap-4">
				<Input v-model="formData.from_path" label="From Path" hint="The URL path to redirect from (e.g. /old-page)" :error="validationErrors.from_path?.[0]" required />
				<Input v-model="formData.to_path" label="To Path" hint="The destination URL or path" :error="validationErrors.to_path?.[0]" required />
				<div class="grid grid-cols-2 gap-4">
					<Select v-model="formData.status_code" label="Status Code" :options="FORM_STATUS_OPTIONS" option-value="value" option-label="name" :error="validationErrors.status_code?.[0]" />
					<Select v-model="formData.match_type" label="Match Type" :options="FORM_MATCH_OPTIONS" option-value="value" option-label="name" :error="validationErrors.match_type?.[0]" />
				</div>
				<Checkbox v-model="formData.is_active" label="Active" />
				<Textarea v-model="formData.notes" label="Notes" :rows="2" :error="validationErrors.notes?.[0]" />
			</div>

			<template #actions>
				<Button @click="closeModals">Cancel</Button>
				<Button color="primary" @click="handleUpdate" :disabled="mutating">
					{{ mutating ? 'Saving...' : 'Save Changes' }}
				</Button>
			</template>
		</Modal>
	</div>
</template>
