/**
 * useSeoAnalysis hook for running and displaying SEO content analysis.
 *
 * Manages analysis state with debounced API calls for real-time feedback.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import type { UseApiOptions } from './useApi';
import { useApi } from './useApi';

import type { AnalysisGrade, AnalysisGradeColor, AnalysisResultResponse } from '../../types/analysis';

/** Grade label lookup. */
const GRADE_LABELS: Record<AnalysisGrade, string> = {
	good: 'Good',
	ok: 'OK',
	poor: 'Poor',
};

/** Grade color lookup. */
const GRADE_COLORS: Record<AnalysisGrade, AnalysisGradeColor> = {
	good: 'green',
	ok: 'yellow',
	poor: 'red',
};

/**
 * Determines the grade from an overall score.
 */
function gradeFromScore( score: number ): AnalysisGrade {
	if ( score >= 70 ) {
		return 'good';
	}

	return score >= 40 ? 'ok' : 'poor';
}

/**
 * Normalizes API responses into a consistent AnalysisResultResponse shape.
 *
 * The cached analysis endpoint returns flat score fields (readability_score, etc.)
 * while the analyze endpoint returns a nested scores object. This function
 * handles both formats.
 */
function normalizeResult( raw: Record<string, unknown> ): AnalysisResultResponse {
	const overallScore = ( raw.overall_score as number ) ?? 0;
	const grade        = ( raw.grade as AnalysisGrade ) ?? gradeFromScore( overallScore );

	// Check if scores are in nested format or flat format
	const scores = raw.scores as { readability: number; keyword: number; meta: number; content: number } | undefined;

	const readability = scores?.readability ?? ( raw.readability_score as number ) ?? 0;
	const keyword     = scores?.keyword ?? ( raw.keyword_score as number ) ?? 0;
	const meta        = scores?.meta ?? ( raw.meta_score as number ) ?? 0;
	const content     = scores?.content ?? ( raw.content_score as number ) ?? 0;

	// Normalize passed_checks — may be string[] or { type, message }[]
	const rawPassed  = ( raw.passed_checks ?? [] ) as ( string | { message: string } )[];
	const passedList = rawPassed.map( ( item ) =>
		'string' === typeof item ? item : item.message,
	);

	// Normalize issues and suggestions — may be string[] or { type, message }[]
	const rawIssues = ( raw.issues ?? [] ) as ( string | { type: string; message: string } )[];
	const issues    = rawIssues.map( ( item ) =>
		'string' === typeof item ? { type: 'general', message: item } : item,
	);

	const rawSuggestions = ( raw.suggestions ?? [] ) as ( string | { type: string; message: string } )[];
	const suggestions    = rawSuggestions.map( ( item ) =>
		'string' === typeof item ? { type: 'general', message: item } : item,
	);

	return {
		overall_score: overallScore,
		grade,
		grade_label: ( raw.grade_label as string ) ?? GRADE_LABELS[grade],
		grade_color: ( raw.grade_color as AnalysisGradeColor ) ?? GRADE_COLORS[grade],
		scores: { readability, keyword, meta, content },
		focus_keyword: ( raw.focus_keyword as string ) ?? null,
		word_count: ( raw.word_count as number ) ?? 0,
		issues,
		issue_count: ( raw.issue_count as number ) ?? issues.length,
		suggestions,
		suggestion_count: ( raw.suggestion_count as number ) ?? suggestions.length,
		passed_checks: passedList,
		passed_count: ( raw.passed_count as number ) ?? passedList.length,
		analyzer_results: ( raw.analyzer_results ?? {} ) as AnalysisResultResponse['analyzer_results'],
	};
}

/** Options for the useSeoAnalysis hook. */
export interface UseSeoAnalysisOptions extends UseApiOptions {
	/** The model type (e.g. "post", "page"). */
	modelType: string;
	/** The model ID. */
	modelId: number;
	/** Debounce delay in milliseconds for analyze calls. Defaults to 1500. */
	debounceMs?: number;
}

/** Return type of the useSeoAnalysis hook. */
export interface UseSeoAnalysisReturn {
	/** The current analysis result. */
	result: AnalysisResultResponse | null;
	/** Whether analysis is currently running. */
	analyzing: boolean;
	/** Whether the initial fetch is loading. */
	loading: boolean;
	/** Error message from the last operation. */
	error: string | null;
	/** Run analysis immediately. */
	analyze: ( focusKeyword?: string ) => Promise<void>;
	/** Run analysis with debouncing (for real-time use). */
	analyzeDebounced: ( focusKeyword?: string ) => void;
	/** Fetch cached analysis results. */
	fetchCached: () => Promise<void>;
}

/**
 * React hook for managing SEO content analysis.
 *
 * @example
 * ```tsx
 * const { result, analyzing, analyzeDebounced } = useSeoAnalysis({
 *     baseUrl: '/api/seo',
 *     modelType: 'post',
 *     modelId: 1,
 * });
 *
 * // Trigger debounced analysis as user types
 * analyzeDebounced(focusKeyword);
 * ```
 */
export function useSeoAnalysis( options: UseSeoAnalysisOptions ): UseSeoAnalysisReturn {
	const { modelType, modelId, debounceMs = 1500 } = options;
	const api = useApi( options );

	const [result, setResult] = useState<AnalysisResultResponse | null>( null );
	const [analyzing, setAnalyzing] = useState( false );
	const [loading, setLoading] = useState( true );
	const [error, setError] = useState<string | null>( null );
	const mountedRef = useRef( true );
	const debounceTimerRef = useRef<ReturnType<typeof setTimeout> | null>( null );
	const encodedModelType = useMemo( () => encodeURIComponent( modelType ), [modelType] );

	useEffect( () => {
		mountedRef.current = true;

		return () => {
			mountedRef.current = false;

			if ( debounceTimerRef.current ) {
				clearTimeout( debounceTimerRef.current );
			}
		};
	}, [] );

	const fetchCached = useCallback( async (): Promise<void> => {
		setLoading( true );
		setError( null );

		try {
			const response = await api.get<{ data: Record<string, unknown> }>(
				`/analysis/${ encodedModelType }/${ modelId }`,
			);

			if ( mountedRef.current ) {
				setResult( normalizeResult( response.data ) );
			}
		} catch ( err: unknown ) {
			// 404 means no cached results — not an error
			const is404 = err && typeof err === 'object' && 'status' in err && 404 === ( err as { status: number } ).status;

			if ( !is404 && mountedRef.current ) {
				setError( err instanceof Error ? err.message : 'Failed to load analysis.' );
			}
		} finally {
			if ( mountedRef.current ) {
				setLoading( false );
			}
		}
	}, [api, encodedModelType, modelId] );

	const analyze = useCallback( async ( focusKeyword?: string ): Promise<void> => {
		setAnalyzing( true );
		setError( null );

		try {
			const response = await api.post<{ data: Record<string, unknown> }>(
				'/analysis/analyze',
				{
					model_type: modelType,
					model_id: modelId,
					focus_keyword: focusKeyword ?? undefined,
				},
			);

			if ( mountedRef.current ) {
				setResult( normalizeResult( response.data ) );
			}
		} catch ( err ) {
			if ( mountedRef.current ) {
				setError( err instanceof Error ? err.message : 'Analysis failed.' );
			}
		} finally {
			if ( mountedRef.current ) {
				setAnalyzing( false );
			}
		}
	}, [api, modelType, modelId] );

	const analyzeDebounced = useCallback( ( focusKeyword?: string ): void => {
		if ( debounceTimerRef.current ) {
			clearTimeout( debounceTimerRef.current );
		}

		debounceTimerRef.current = setTimeout( () => {
			analyze( focusKeyword );
		}, debounceMs );
	}, [analyze, debounceMs] );

	useEffect( () => {
		fetchCached();
	}, [fetchCached] );

	return {
		result,
		analyzing,
		loading,
		error,
		analyze,
		analyzeDebounced,
		fetchCached,
	};
}
