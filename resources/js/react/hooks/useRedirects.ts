/**
 * useRedirects hook for managing URL redirects.
 *
 * Provides CRUD operations, filtering, sorting, pagination,
 * bulk actions, and URL testing for redirects.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import { useCallback, useEffect, useRef, useState } from 'react';

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

/** Return type of the useRedirects hook. */
export interface UseRedirectsReturn {
	/** List of redirects for the current page. */
	redirects: Redirect[];
	/** Pagination metadata. */
	meta: PaginatedRedirects['meta'] | null;
	/** Whether the list is loading. */
	loading: boolean;
	/** Whether a mutation is in progress. */
	mutating: boolean;
	/** Error message from the last operation. */
	error: string | null;
	/** Validation errors from the last mutation. */
	validationErrors: Record<string, string[]>;
	/** Current filter options. */
	filters: RedirectFilterOptions;
	/** Current sort options. */
	sort: RedirectSortOptions;
	/** Current page number. */
	page: number;
	/** Fetch the redirect list. */
	fetchRedirects: () => Promise<void>;
	/** Create a new redirect. */
	createRedirect: ( data: Record<string, unknown> ) => Promise<Redirect | null>;
	/** Update an existing redirect. */
	updateRedirect: ( id: number, data: Record<string, unknown> ) => Promise<Redirect | null>;
	/** Delete a redirect. */
	deleteRedirect: ( id: number ) => Promise<boolean>;
	/** Perform a bulk action on selected redirects. */
	bulkAction: ( action: string, ids: number[], statusCode?: number ) => Promise<boolean>;
	/** Test a URL against redirect rules. */
	testUrl: ( url: string ) => Promise<RedirectTestResult | null>;
	/** Update filter options and refetch. */
	setFilters: ( filters: RedirectFilterOptions ) => void;
	/** Update sort options and refetch. */
	setSort: ( sort: RedirectSortOptions ) => void;
	/** Navigate to a page. */
	setPage: ( page: number ) => void;
}

/**
 * React hook for managing URL redirects.
 *
 * @example
 * ```tsx
 * const {
 *     redirects, loading, createRedirect, deleteRedirect,
 *     filters, setFilters, sort, setSort,
 * } = useRedirects({ baseUrl: '/api/seo' });
 * ```
 */
export function useRedirects( options: UseApiOptions ): UseRedirectsReturn {
	const api = useApi( options );

	const [redirects, setRedirects] = useState<Redirect[]>( [] );
	const [meta, setMeta] = useState<PaginatedRedirects['meta'] | null>( null );
	const [loading, setLoading] = useState( true );
	const [mutating, setMutating] = useState( false );
	const [error, setError] = useState<string | null>( null );
	const [validationErrors, setValidationErrors] = useState<Record<string, string[]>>( {} );
	const [filters, setFiltersState] = useState<RedirectFilterOptions>( {} );
	const [sort, setSortState] = useState<RedirectSortOptions>( {
		field: 'created_at',
		direction: 'desc',
	} );
	const [page, setPageState] = useState( 1 );
	const mountedRef = useRef( true );

	useEffect( () => {
		mountedRef.current = true;

		return () => {
			mountedRef.current = false;
		};
	}, [] );

	const fetchRedirects = useCallback( async (): Promise<void> => {
		setLoading( true );
		setError( null );

		const params: Record<string, string> = {
			page: String( page ),
			sort_by: sort.field,
			sort_direction: sort.direction,
		};

		if ( filters.search ) {
			params.search = filters.search;
		}

		if ( filters.status_code ) {
			params.status_code = String( filters.status_code );
		}

		if ( filters.match_type ) {
			params.match_type = filters.match_type;
		}

		if ( undefined !== filters.is_active ) {
			params.is_active = filters.is_active ? '1' : '0';
		}

		try {
			const response = await api.get<PaginatedRedirects>( '/redirects', params );

			if ( mountedRef.current ) {
				setRedirects( response.data );
				setMeta( response.meta );
			}
		} catch ( err ) {
			if ( mountedRef.current ) {
				setError( err instanceof Error ? err.message : 'Failed to load redirects.' );
			}
		} finally {
			if ( mountedRef.current ) {
				setLoading( false );
			}
		}
	}, [api, page, sort, filters] );

	const createRedirect = useCallback( async (
		data: Record<string, unknown>,
	): Promise<Redirect | null> => {
		setMutating( true );
		setError( null );
		setValidationErrors( {} );

		try {
			const response = await api.post<{ data: Redirect }>( '/redirects', data );

			if ( mountedRef.current ) {
				setMutating( false );
				await fetchRedirects();
			}

			return response.data;
		} catch ( err: unknown ) {
			if ( mountedRef.current ) {
				if ( err && typeof err === 'object' && 'errors' in err ) {
					const validationErr = err as { errors: Record<string, string[]>; message: string };
					setValidationErrors( validationErr.errors );
					setError( validationErr.message );
				} else {
					setError( err instanceof Error ? err.message : 'Failed to create redirect.' );
				}

				setMutating( false );
			}

			return null;
		}
	}, [api, fetchRedirects] );

	const updateRedirect = useCallback( async (
		id: number,
		data: Record<string, unknown>,
	): Promise<Redirect | null> => {
		setMutating( true );
		setError( null );
		setValidationErrors( {} );

		try {
			const response = await api.put<{ data: Redirect }>( `/redirects/${ id }`, data );

			if ( mountedRef.current ) {
				setMutating( false );
				await fetchRedirects();
			}

			return response.data;
		} catch ( err: unknown ) {
			if ( mountedRef.current ) {
				if ( err && typeof err === 'object' && 'errors' in err ) {
					const validationErr = err as { errors: Record<string, string[]>; message: string };
					setValidationErrors( validationErr.errors );
					setError( validationErr.message );
				} else {
					setError( err instanceof Error ? err.message : 'Failed to update redirect.' );
				}

				setMutating( false );
			}

			return null;
		}
	}, [api, fetchRedirects] );

	const deleteRedirect = useCallback( async ( id: number ): Promise<boolean> => {
		setMutating( true );
		setError( null );

		try {
			await api.del( `/redirects/${ id }` );

			if ( mountedRef.current ) {
				setMutating( false );
				await fetchRedirects();
			}

			return true;
		} catch ( err ) {
			if ( mountedRef.current ) {
				setError( err instanceof Error ? err.message : 'Failed to delete redirect.' );
				setMutating( false );
			}

			return false;
		}
	}, [api, fetchRedirects] );

	const bulkAction = useCallback( async (
		action: string,
		ids: number[],
		statusCode?: number,
	): Promise<boolean> => {
		setMutating( true );
		setError( null );

		try {
			const body: Record<string, unknown> = { action, ids };

			if ( statusCode ) {
				body.status_code = statusCode;
			}

			await api.post( '/redirects/bulk', body );

			if ( mountedRef.current ) {
				setMutating( false );
				await fetchRedirects();
			}

			return true;
		} catch ( err ) {
			if ( mountedRef.current ) {
				setError( err instanceof Error ? err.message : 'Bulk action failed.' );
				setMutating( false );
			}

			return false;
		}
	}, [api, fetchRedirects] );

	const testUrl = useCallback( async ( url: string ): Promise<RedirectTestResult | null> => {
		setError( null );

		try {
			const response = await api.post<{ data: RedirectTestResult }>(
				'/redirects/test',
				{ url },
			);

			return response.data;
		} catch ( err ) {
			if ( mountedRef.current ) {
				setError( err instanceof Error ? err.message : 'URL test failed.' );
			}

			return null;
		}
	}, [api] );

	const setFilters = useCallback( ( newFilters: RedirectFilterOptions ): void => {
		setFiltersState( newFilters );
		setPageState( 1 );
	}, [] );

	const setSort = useCallback( ( newSort: RedirectSortOptions ): void => {
		setSortState( newSort );
		setPageState( 1 );
	}, [] );

	const setPage = useCallback( ( newPage: number ): void => {
		setPageState( newPage );
	}, [] );

	useEffect( () => {
		fetchRedirects();
	}, [fetchRedirects] );

	return {
		redirects,
		meta,
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
