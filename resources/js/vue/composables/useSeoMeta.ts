/**
 * useSeoMeta composable for fetching and updating SEO metadata.
 *
 * Manages SEO data lifecycle for a specific model, including
 * loading, saving, and preview data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 *
 * @example
 * ```ts
 * const { data, loading, updateMeta } = useSeoMeta({
 *     baseUrl: '/api/seo',
 *     modelType: 'App\\Models\\Post',
 *     modelId: 1,
 * });
 * ```
 */

import { onUnmounted, ref, watch } from 'vue';

import type { Ref } from 'vue';

import type { UseApiOptions } from './useApi';
import { useApi } from './useApi';

import type { SeoMetaResponse, SeoPreviewResponse } from '../../types/seo-data';

/** Options for the useSeoMeta composable. */
export interface UseSeoMetaOptions extends UseApiOptions {
	/** The model type (e.g. "App\\Models\\Post"). */
	modelType: string;
	/** The model ID. */
	modelId: number;
	/** Optional initial data to avoid an initial fetch. */
	initialData?: SeoMetaResponse;
}

/** Return type of the useSeoMeta composable. */
export interface UseSeoMetaReturn {
	data: Ref<SeoMetaResponse | null>;
	preview: Ref<SeoPreviewResponse | null>;
	loading: Ref<boolean>;
	saving: Ref<boolean>;
	error: Ref<string | null>;
	validationErrors: Ref<Record<string, string[]>>;
	fetchMeta: () => Promise<void>;
	fetchPreview: () => Promise<void>;
	updateMeta: ( updates: Record<string, unknown> ) => Promise<SeoMetaResponse | null>;
}

export function useSeoMeta( options: UseSeoMetaOptions ): UseSeoMetaReturn {
	const { modelType, modelId, initialData } = options;
	const api = useApi( options );
	const encodedModelType = encodeURIComponent( modelType );

	const data             = ref<SeoMetaResponse | null>( initialData ?? null );
	const preview          = ref<SeoPreviewResponse | null>( null );
	const loading          = ref( !initialData );
	const saving           = ref( false );
	const error            = ref<string | null>( null );
	const validationErrors = ref<Record<string, string[]>>( {} );
	let mounted            = true;

	onUnmounted( () => {
		mounted = false;
	} );

	async function fetchMeta(): Promise<void> {
		loading.value = true;
		error.value   = null;

		try {
			const response = await api.get<{ data: SeoMetaResponse | null }>(
				`/meta/${ encodedModelType }/${ modelId }`,
			);

			if ( mounted ) {
				data.value = response.data;
			}
		} catch ( err ) {
			if ( mounted ) {
				error.value = err instanceof Error ? err.message : 'Failed to load SEO data.';
			}
		} finally {
			if ( mounted ) {
				loading.value = false;
			}
		}
	}

	async function fetchPreview(): Promise<void> {
		try {
			const response = await api.get<{ data: SeoPreviewResponse }>(
				`/meta/${ encodedModelType }/${ modelId }/preview`,
			);

			if ( mounted ) {
				preview.value = response.data;
			}
		} catch {
			// Preview failures are non-critical
		}
	}

	async function updateMeta( updates: Record<string, unknown> ): Promise<SeoMetaResponse | null> {
		saving.value           = true;
		error.value            = null;
		validationErrors.value = {};

		try {
			const response = await api.put<{ data: SeoMetaResponse }>(
				`/meta/${ encodedModelType }/${ modelId }`,
				updates,
			);

			if ( mounted ) {
				data.value   = response.data;
				saving.value = false;
			}

			return response.data;
		} catch ( err: unknown ) {
			if ( mounted ) {
				if ( err && typeof err === 'object' && 'errors' in err ) {
					const validationErr = err as { errors: Record<string, string[]>; message?: string };
					validationErrors.value = validationErr.errors;
					error.value            = validationErr.message ?? 'Validation failed.';
				} else {
					error.value = err instanceof Error ? err.message : 'Failed to save SEO data.';
				}

				saving.value = false;
			}

			return null;
		}
	}

	if ( !initialData ) {
		fetchMeta();
	}

	return {
		data: data as Ref<SeoMetaResponse | null>,
		preview: preview as Ref<SeoPreviewResponse | null>,
		loading,
		saving,
		error,
		validationErrors,
		fetchMeta,
		fetchPreview,
		updateMeta,
	};
}
