/**
 * useSeoMeta hook for fetching and updating SEO metadata.
 *
 * Manages SEO data lifecycle for a specific model, including
 * loading, saving, and preview data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import type { UseApiOptions } from './useApi';
import { useApi } from './useApi';

import type { SeoMetaResponse, SeoPreviewResponse } from '../../types/seo-data';

/** Options for the useSeoMeta hook. */
export interface UseSeoMetaOptions extends UseApiOptions {
	/** The model type (e.g. "post", "page"). */
	modelType: string;
	/** The model ID. */
	modelId: number;
	/** Optional initial data to avoid an initial fetch. */
	initialData?: SeoMetaResponse;
}

/** Return type of the useSeoMeta hook. */
export interface UseSeoMetaReturn {
	/** The current SEO meta data. */
	data: SeoMetaResponse | null;
	/** Preview data for search and social. */
	preview: SeoPreviewResponse | null;
	/** Whether the initial load is in progress. */
	loading: boolean;
	/** Whether a save is in progress. */
	saving: boolean;
	/** Error message from the last operation. */
	error: string | null;
	/** Validation errors from the last save. */
	validationErrors: Record<string, string[]>;
	/** Fetch or refresh SEO meta data. */
	fetchMeta: () => Promise<void>;
	/** Fetch preview data. */
	fetchPreview: () => Promise<void>;
	/** Update SEO meta data. */
	updateMeta: ( updates: Record<string, unknown> ) => Promise<SeoMetaResponse | null>;
}

/**
 * React hook for managing SEO metadata for a model.
 *
 * @example
 * ```tsx
 * const { data, loading, updateMeta } = useSeoMeta({
 *     baseUrl: '/api/seo',
 *     modelType: 'post',
 *     modelId: 1,
 * });
 * ```
 */
export function useSeoMeta( options: UseSeoMetaOptions ): UseSeoMetaReturn {
	const { modelType, modelId, initialData } = options;
	const api = useApi( options );

	const [data, setData] = useState<SeoMetaResponse | null>( initialData ?? null );
	const [preview, setPreview] = useState<SeoPreviewResponse | null>( null );
	const [loading, setLoading] = useState( !initialData );
	const [saving, setSaving] = useState( false );
	const [error, setError] = useState<string | null>( null );
	const [validationErrors, setValidationErrors] = useState<Record<string, string[]>>( {} );
	const mountedRef = useRef( true );
	const encodedModelType = useMemo( () => encodeURIComponent( modelType ), [modelType] );

	useEffect( () => {
		mountedRef.current = true;

		return () => {
			mountedRef.current = false;
		};
	}, [] );

	const fetchMeta = useCallback( async (): Promise<void> => {
		setLoading( true );
		setError( null );

		try {
			const response = await api.get<{ data: SeoMetaResponse | null }>(
				`/meta/${ encodedModelType }/${ modelId }`,
			);

			if ( mountedRef.current ) {
				setData( response.data );
			}
		} catch ( err ) {
			if ( mountedRef.current ) {
				setError( err instanceof Error ? err.message : 'Failed to load SEO data.' );
			}
		} finally {
			if ( mountedRef.current ) {
				setLoading( false );
			}
		}
	}, [api, encodedModelType, modelId] );

	const fetchPreview = useCallback( async (): Promise<void> => {
		try {
			const response = await api.get<{ data: SeoPreviewResponse }>(
				`/meta/${ encodedModelType }/${ modelId }/preview`,
			);

			if ( mountedRef.current ) {
				setPreview( response.data );
			}
		} catch {
			// Preview failures are non-critical
		}
	}, [api, encodedModelType, modelId] );

	const updateMeta = useCallback( async (
		updates: Record<string, unknown>,
	): Promise<SeoMetaResponse | null> => {
		setSaving( true );
		setError( null );
		setValidationErrors( {} );

		try {
			const response = await api.put<{ data: SeoMetaResponse }>(
				`/meta/${ encodedModelType }/${ modelId }`,
				updates,
			);

			if ( mountedRef.current ) {
				setData( response.data );
				setSaving( false );
			}

			return response.data;
		} catch ( err: unknown ) {
			if ( mountedRef.current ) {
				if ( err && typeof err === 'object' && 'errors' in err ) {
					const validationErr = err as { errors: Record<string, string[]>; message: string };
					setValidationErrors( validationErr.errors );
					setError( validationErr.message );
				} else {
					setError( err instanceof Error ? err.message : 'Failed to save SEO data.' );
				}

				setSaving( false );
			}

			return null;
		}
	}, [api, encodedModelType, modelId] );

	useEffect( () => {
		if ( !initialData ) {
			fetchMeta();
		}
	}, [fetchMeta, initialData] );

	return {
		data,
		preview,
		loading,
		saving,
		error,
		validationErrors,
		fetchMeta,
		fetchPreview,
		updateMeta,
	};
}
