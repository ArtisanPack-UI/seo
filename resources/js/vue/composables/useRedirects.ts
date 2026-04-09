/**
 * useRedirects composable for managing URL redirects.
 *
 * Provides CRUD operations, filtering, sorting, pagination,
 * bulk actions, and URL testing for redirects.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 *
 * @example
 * ```ts
 * const { redirects, loading, createRedirect, setFilters } = useRedirects({
 *     baseUrl: '/api/seo',
 * });
 * ```
 */

import { onUnmounted, ref, watch } from 'vue';

import type { Ref } from 'vue';

import type { UseApiOptions } from './useApi';
import { useApi } from './useApi';

import type { Redirect, RedirectTestResult } from '../../types/redirect';
import type { RedirectFilterOptions, RedirectSortOptions } from '../../types/components';

/** Paginated response shape from the redirects API. */
export interface PaginatedRedirects {
	data: Redirect[];
	meta: {
		current_page: number;
		last_page: number;
		per_page: number;
		total: number;
	};
}

/** Return type of the useRedirects composable. */
export interface UseRedirectsReturn {
	redirects: Ref<Redirect[]>;
	meta: Ref<PaginatedRedirects['meta'] | null>;
	loading: Ref<boolean>;
	mutating: Ref<boolean>;
	error: Ref<string | null>;
	validationErrors: Ref<Record<string, string[]>>;
	filters: Ref<RedirectFilterOptions>;
	sort: Ref<RedirectSortOptions>;
	page: Ref<number>;
	fetchRedirects: () => Promise<void>;
	createRedirect: ( data: Record<string, unknown> ) => Promise<Redirect | null>;
	updateRedirect: ( id: number, data: Record<string, unknown> ) => Promise<Redirect | null>;
	deleteRedirect: ( id: number ) => Promise<boolean>;
	bulkAction: ( action: string, ids: number[], statusCode?: number ) => Promise<boolean>;
	testUrl: ( url: string ) => Promise<RedirectTestResult | null>;
	setFilters: ( newFilters: RedirectFilterOptions ) => void;
	setSort: ( newSort: RedirectSortOptions ) => void;
	setPage: ( newPage: number ) => void;
}

export function useRedirects( options: UseApiOptions ): UseRedirectsReturn {
	const api = useApi( options );

	const redirects        = ref<Redirect[]>( [] );
	const meta             = ref<PaginatedRedirects['meta'] | null>( null );
	const loading          = ref( true );
	const mutating         = ref( false );
	const error            = ref<string | null>( null );
	const validationErrors = ref<Record<string, string[]>>( {} );
	const filters          = ref<RedirectFilterOptions>( {} );
	const sort             = ref<RedirectSortOptions>( { field: 'created_at', direction: 'desc' } );
	const page             = ref( 1 );

	let mounted        = true;
	let fetchRequestId = 0;

	onUnmounted( () => {
		mounted = false;
	} );

	async function fetchRedirects(): Promise<void> {
		fetchRequestId += 1;
		const currentRequestId = fetchRequestId;

		loading.value = true;
		error.value   = null;

		const params: Record<string, string> = {
			page: String( page.value ),
			sort_by: sort.value.field,
			sort_order: sort.value.direction,
		};

		if ( filters.value.search ) {
			params.search = filters.value.search;
		}

		if ( filters.value.status_code ) {
			params.status_code = String( filters.value.status_code );
		}

		if ( filters.value.match_type ) {
			params.match_type = filters.value.match_type;
		}

		if ( undefined !== filters.value.is_active ) {
			params.is_active = filters.value.is_active ? '1' : '0';
		}

		try {
			const response = await api.get<PaginatedRedirects>( '/redirects', params );

			if ( mounted && currentRequestId === fetchRequestId ) {
				redirects.value = response.data;
				meta.value      = response.meta;
			}
		} catch ( err ) {
			if ( mounted && currentRequestId === fetchRequestId ) {
				error.value = err instanceof Error ? err.message : 'Failed to load redirects.';
			}
		} finally {
			if ( mounted && currentRequestId === fetchRequestId ) {
				loading.value = false;
			}
		}
	}

	async function createRedirect( data: Record<string, unknown> ): Promise<Redirect | null> {
		mutating.value         = true;
		error.value            = null;
		validationErrors.value = {};

		try {
			const response = await api.post<{ data: Redirect }>( '/redirects', data );

			if ( mounted ) {
				await fetchRedirects();
				mutating.value = false;
			}

			return response.data;
		} catch ( err: unknown ) {
			if ( mounted ) {
				if ( err && typeof err === 'object' && 'errors' in err ) {
					const validationErr = err as { errors: Record<string, string[]>; message: string };
					validationErrors.value = validationErr.errors;
					error.value            = validationErr.message;
				} else {
					error.value = err instanceof Error ? err.message : 'Failed to create redirect.';
				}

				mutating.value = false;
			}

			return null;
		}
	}

	async function updateRedirect( id: number, data: Record<string, unknown> ): Promise<Redirect | null> {
		mutating.value         = true;
		error.value            = null;
		validationErrors.value = {};

		try {
			const response = await api.put<{ data: Redirect }>( `/redirects/${ id }`, data );

			if ( mounted ) {
				await fetchRedirects();
				mutating.value = false;
			}

			return response.data;
		} catch ( err: unknown ) {
			if ( mounted ) {
				if ( err && typeof err === 'object' && 'errors' in err ) {
					const validationErr = err as { errors: Record<string, string[]>; message: string };
					validationErrors.value = validationErr.errors;
					error.value            = validationErr.message;
				} else {
					error.value = err instanceof Error ? err.message : 'Failed to update redirect.';
				}

				mutating.value = false;
			}

			return null;
		}
	}

	async function deleteRedirect( id: number ): Promise<boolean> {
		mutating.value = true;
		error.value    = null;

		try {
			await api.del( `/redirects/${ id }` );

			if ( mounted ) {
				await fetchRedirects();
				mutating.value = false;
			}

			return true;
		} catch ( err ) {
			if ( mounted ) {
				error.value    = err instanceof Error ? err.message : 'Failed to delete redirect.';
				mutating.value = false;
			}

			return false;
		}
	}

	async function bulkAction( action: string, ids: number[], statusCode?: number ): Promise<boolean> {
		mutating.value = true;
		error.value    = null;

		try {
			const body: Record<string, unknown> = { action, ids };

			if ( statusCode ) {
				body.status_code = statusCode;
			}

			await api.post( '/redirects/bulk', body );

			if ( mounted ) {
				await fetchRedirects();
				mutating.value = false;
			}

			return true;
		} catch ( err ) {
			if ( mounted ) {
				error.value    = err instanceof Error ? err.message : 'Bulk action failed.';
				mutating.value = false;
			}

			return false;
		}
	}

	async function testUrl( url: string ): Promise<RedirectTestResult | null> {
		error.value = null;

		try {
			const response = await api.post<{ data: RedirectTestResult }>(
				'/redirects/test',
				{ url },
			);

			return response.data;
		} catch ( err ) {
			if ( mounted ) {
				error.value = err instanceof Error ? err.message : 'URL test failed.';
			}

			return null;
		}
	}

	function setFilters( newFilters: RedirectFilterOptions ): void {
		filters.value = newFilters;
		page.value    = 1;
	}

	function setSort( newSort: RedirectSortOptions ): void {
		sort.value = newSort;
		page.value = 1;
	}

	function setPage( newPage: number ): void {
		page.value = newPage;
	}

	// Auto-fetch when reactive dependencies change
	watch( [page, sort, filters], () => {
		fetchRedirects();
	}, { deep: true, immediate: true } );

	return {
		redirects: redirects as Ref<Redirect[]>,
		meta: meta as Ref<PaginatedRedirects['meta'] | null>,
		loading,
		mutating,
		error,
		validationErrors,
		filters,
		sort,
		page,
		fetchRedirects,
		createRedirect,
		updateRedirect,
		deleteRedirect,
		bulkAction,
		testUrl,
		setFilters,
		setSort,
		setPage,
	};
}
