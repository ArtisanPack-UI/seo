/**
 * useAiAgent composable — thin wrapper over useApi for the SEO AI endpoints.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */

import { ref, type Ref } from 'vue';

import { ApiError, ApiValidationError, useApi, type UseApiOptions } from './useApi';

/** Supported AI feature keys owned by the SEO package. */
export type SeoAiFeatureKey =
	| 'seo.suggest_meta_title'
	| 'seo.suggest_meta_description'
	| 'seo.analyze_content'
	| 'seo.generate_schema'
	| 'seo.suggest_hreflang';

const ENDPOINT_MAP: Record<SeoAiFeatureKey, string> = {
	'seo.suggest_meta_title': '/ai/suggest-meta-title',
	'seo.suggest_meta_description': '/ai/suggest-meta-description',
	'seo.analyze_content': '/ai/analyze-content',
	'seo.generate_schema': '/ai/generate-schema',
	'seo.suggest_hreflang': '/ai/suggest-hreflang',
};

/** Envelope every agent endpoint returns. */
export interface AgentResponse<TOutput> {
	data: TOutput;
	feature_key: SeoAiFeatureKey;
}

/** Return type of the useAiAgent composable. */
export interface UseAiAgentReturn<TInput, TOutput> {
	output: Ref<TOutput | null>;
	isLoading: Ref<boolean>;
	error: Ref<string | null>;
	run: ( input: TInput ) => Promise<TOutput | null>;
	reset: () => void;
}

/**
 * Vue composable for dispatching a single SEO AI agent.
 */
export function useAiAgent<TInput, TOutput>(
	feature: SeoAiFeatureKey,
	options: UseApiOptions,
): UseAiAgentReturn<TInput, TOutput> {
	const api = useApi( options );
	const output = ref<TOutput | null>( null ) as Ref<TOutput | null>;
	const isLoading = ref<boolean>( false );
	const error = ref<string | null>( null );

	const reset = (): void => {
		output.value = null;
		error.value = null;
	};

	const run = async ( input: TInput ): Promise<TOutput | null> => {
		isLoading.value = true;
		error.value = null;

		try {
			const response = await api.post<AgentResponse<TOutput>>(
				ENDPOINT_MAP[feature],
				input,
			);
			output.value = response.data;
			return response.data;
		} catch ( caught ) {
			if ( caught instanceof ApiValidationError ) {
				const firstField = Object.keys( caught.errors )[0];
				error.value = firstField ? caught.errors[firstField][0] : caught.message;
			} else if ( caught instanceof ApiError ) {
				error.value = caught.message;
			} else if ( caught instanceof Error ) {
				error.value = caught.message;
			} else {
				error.value = 'Request failed.';
			}

			return null;
		} finally {
			isLoading.value = false;
		}
	};

	return { output, isLoading, error, run, reset };
}
