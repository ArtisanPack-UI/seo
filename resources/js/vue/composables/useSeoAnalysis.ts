/**
 * useSeoAnalysis composable for running and displaying SEO content analysis.
 *
 * Manages analysis state with debounced API calls for real-time feedback.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 *
 * @example
 * ```ts
 * const { result, analyzing, analyzeDebounced } = useSeoAnalysis({
 *     baseUrl: '/api/seo',
 *     modelType: 'App\\Models\\Post',
 *     modelId: 1,
 * });
 * ```
 */

import { onUnmounted, ref } from 'vue';

import type { Ref } from 'vue';

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

function gradeFromScore( score: number ): AnalysisGrade {
	if ( score >= 70 ) {
		return 'good';
	}

	return score >= 40 ? 'ok' : 'poor';
}

/**
 * Normalizes API responses into a consistent AnalysisResultResponse shape.
 * Handles both cached (flat) and fresh (nested scores) formats.
 */
function normalizeResult( raw: Record<string, unknown> ): AnalysisResultResponse {
	const overallScore = ( raw.overall_score as number ) ?? 0;
	const grade        = ( raw.grade as AnalysisGrade ) ?? gradeFromScore( overallScore );

	const scores = raw.scores as { readability: number; keyword: number; meta: number; content: number } | undefined;

	const readability = scores?.readability ?? ( raw.readability_score as number ) ?? 0;
	const keyword     = scores?.keyword ?? ( raw.keyword_score as number ) ?? 0;
	const meta        = scores?.meta ?? ( raw.meta_score as number ) ?? 0;
	const content     = scores?.content ?? ( raw.content_score as number ) ?? 0;

	const rawPassed  = ( raw.passed_checks ?? [] ) as ( string | { message: string } )[];
	const passedList = rawPassed.map( ( item ) =>
		'string' === typeof item ? item : item.message,
	);

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

/** Options for the useSeoAnalysis composable. */
export interface UseSeoAnalysisOptions extends UseApiOptions {
	/** The model type (e.g. "App\\Models\\Post"). */
	modelType: string;
	/** The model ID. */
	modelId: number;
	/** Debounce delay in milliseconds. Defaults to 1500. */
	debounceMs?: number;
}

/** Return type of the useSeoAnalysis composable. */
export interface UseSeoAnalysisReturn {
	result: Ref<AnalysisResultResponse | null>;
	analyzing: Ref<boolean>;
	loading: Ref<boolean>;
	error: Ref<string | null>;
	analyze: ( focusKeyword?: string ) => Promise<void>;
	analyzeDebounced: ( focusKeyword?: string ) => void;
	fetchCached: () => Promise<void>;
}

export function useSeoAnalysis( options: UseSeoAnalysisOptions ): UseSeoAnalysisReturn {
	const { modelType, modelId, debounceMs = 1500 } = options;
	const api              = useApi( options );
	const encodedModelType = encodeURIComponent( modelType );

	const result    = ref<AnalysisResultResponse | null>( null );
	const analyzing = ref( false );
	const loading   = ref( true );
	const error     = ref<string | null>( null );

	let mounted       = true;
	let debounceTimer: ReturnType<typeof setTimeout> | null = null;
	let requestId     = 0;

	onUnmounted( () => {
		mounted = false;

		if ( debounceTimer ) {
			clearTimeout( debounceTimer );
		}
	} );

	async function fetchCached(): Promise<void> {
		requestId += 1;
		const currentRequestId = requestId;

		loading.value = true;
		error.value   = null;

		try {
			const response = await api.get<{ data: Record<string, unknown> | null }>(
				`/analysis/${ encodedModelType }/${ modelId }`,
			);

			if ( mounted && currentRequestId === requestId ) {
				result.value = response.data ? normalizeResult( response.data ) : null;
			}
		} catch ( err: unknown ) {
			const is404 = err && typeof err === 'object' && 'status' in err && 404 === ( err as { status: number } ).status;

			if ( !is404 && mounted && currentRequestId === requestId ) {
				error.value = err instanceof Error ? err.message : 'Failed to load analysis.';
			}
		} finally {
			if ( mounted && currentRequestId === requestId ) {
				loading.value = false;
			}
		}
	}

	async function analyze( focusKeyword?: string ): Promise<void> {
		requestId += 1;
		const currentRequestId = requestId;

		analyzing.value = true;
		error.value     = null;

		try {
			const response = await api.post<{ data: Record<string, unknown> | null }>(
				'/analysis/analyze',
				{
					model_type: modelType,
					model_id: modelId,
					focus_keyword: focusKeyword ?? undefined,
				},
			);

			if ( mounted && currentRequestId === requestId ) {
				result.value = response.data ? normalizeResult( response.data ) : null;
			}
		} catch ( err ) {
			if ( mounted && currentRequestId === requestId ) {
				error.value = err instanceof Error ? err.message : 'Analysis failed.';
			}
		} finally {
			if ( mounted && currentRequestId === requestId ) {
				analyzing.value = false;
			}
		}
	}

	function analyzeDebounced( focusKeyword?: string ): void {
		if ( debounceTimer ) {
			clearTimeout( debounceTimer );
		}

		debounceTimer = setTimeout( () => {
			analyze( focusKeyword );
		}, debounceMs );
	}

	fetchCached();

	return {
		result: result as Ref<AnalysisResultResponse | null>,
		analyzing,
		loading,
		error,
		analyze,
		analyzeDebounced,
		fetchCached,
	};
}
