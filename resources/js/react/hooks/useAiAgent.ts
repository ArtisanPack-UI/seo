/**
 * useAiAgent hook — thin wrapper over useApi for the SEO AI agent endpoints.
 *
 * Each of the five agents surfaces a POST endpoint under `/api/seo/ai/*`. This
 * hook returns a typed dispatcher plus loading/error state so components stay
 * free of boilerplate.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */

import { useCallback, useState } from 'react';

import { ApiError, ApiValidationError, useApi, type UseApiOptions } from './useApi';

/** Supported AI feature keys owned by the SEO package. */
export type SeoAiFeatureKey =
	| 'seo.suggest_meta_title'
	| 'seo.suggest_meta_description'
	| 'seo.analyze_content'
	| 'seo.generate_schema'
	| 'seo.suggest_hreflang';

/** Map from feature key to the endpoint path segment (after `/ai/`). */
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

/** Return type of the useAiAgent hook. */
export interface UseAiAgentReturn<TInput, TOutput> {
	/** Latest successful output, or `null` before the first call. */
	output: TOutput | null;
	/** Whether a request is currently in flight. */
	isLoading: boolean;
	/** Latest error message, or `null` when the last run succeeded. */
	error: string | null;
	/** Dispatch a run against `input`. Returns the output on success. */
	run: ( input: TInput ) => Promise<TOutput | null>;
	/** Reset output/error to their initial values. */
	reset: () => void;
}

/**
 * React hook for dispatching a single SEO AI agent.
 *
 * @example
 * ```tsx
 * const { run, output, isLoading, error } = useAiAgent<TitleInput, TitleOutput>(
 *   'seo.suggest_meta_title',
 *   { baseUrl: '/api/seo' },
 * );
 * ```
 */
export function useAiAgent<TInput, TOutput>(
	feature: SeoAiFeatureKey,
	options: UseApiOptions,
): UseAiAgentReturn<TInput, TOutput> {
	const api = useApi( options );
	const [output, setOutput] = useState<TOutput | null>( null );
	const [isLoading, setIsLoading] = useState<boolean>( false );
	const [error, setError] = useState<string | null>( null );

	const reset = useCallback( () => {
		setOutput( null );
		setError( null );
	}, [] );

	const run = useCallback(
		async ( input: TInput ): Promise<TOutput | null> => {
			setIsLoading( true );
			setError( null );

			try {
				const response = await api.post<AgentResponse<TOutput>>(
					ENDPOINT_MAP[feature],
					input,
				);
				setOutput( response.data );
				return response.data;
			} catch ( caught ) {
				if ( caught instanceof ApiValidationError ) {
					const firstField = Object.keys( caught.errors )[0];
					const firstMessage = firstField ? caught.errors[firstField][0] : caught.message;
					setError( firstMessage );
				} else if ( caught instanceof ApiError ) {
					setError( caught.message );
				} else if ( caught instanceof Error ) {
					setError( caught.message );
				} else {
					setError( 'Request failed.' );
				}

				return null;
			} finally {
				setIsLoading( false );
			}
		},
		[api, feature],
	);

	return { output, isLoading, error, run, reset };
}
