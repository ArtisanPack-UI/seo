/**
 * SeoAnalysisPanel component.
 *
 * Displays real-time SEO content analysis results with per-analyzer
 * scores, overall score, grade, and actionable recommendations.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import React, { useCallback } from 'react';

import { Alert, Badge, Button, Card, Collapse, Input, Loading, Progress } from '@artisanpack-ui/react';

import type { UseApiOptions } from '../../hooks/useApi';
import { useSeoAnalysis } from '../../hooks/useSeoAnalysis';

import type { AnalysisGradeColor, AnalyzerName } from '../../../types/analysis';

/** Map of grade to DaisyUI color. */
const GRADE_COLOR_MAP: Record<AnalysisGradeColor, 'success' | 'warning' | 'error'> = {
	green: 'success',
	yellow: 'warning',
	red: 'error',
};

/** Human-readable analyzer labels. */
const ANALYZER_LABELS: Record<AnalyzerName, string> = {
	content_length: 'Content Length',
	focus_keyword: 'Focus Keyword',
	heading_structure: 'Heading Structure',
	image_alt: 'Image Alt Text',
	internal_links: 'Internal Links',
	keyword_density: 'Keyword Density',
	meta_length: 'Meta Tag Length',
	readability: 'Readability',
};

/** Props for the SeoAnalysisPanel component. */
export interface SeoAnalysisPanelProps extends UseApiOptions {
	/** The model type (e.g. "post", "page"). */
	modelType: string;
	/** The model ID. */
	modelId: number;
	/** Focus keyword for analysis. */
	focusKeyword?: string;
	/** Debounce delay in ms for auto-analysis. Defaults to 1500. */
	debounceMs?: number;
	/** Callback when focus keyword changes. */
	onFocusKeywordChange?: ( keyword: string ) => void;
	/** Additional CSS class name. */
	className?: string;
}

/** Score category sub-component. */
function ScoreCategory( { label, score }: { label: string; score: number } ): React.ReactElement {
	const color = score >= 70 ? 'success' : ( score >= 40 ? 'warning' : 'error' );

	return (
		<div className="flex items-center gap-2">
			<span className="text-sm w-24">{ label }</span>
			<Progress value={ score } max={ 100 } color={ color } className="flex-1" />
			<span className="text-sm font-mono w-8 text-right">{ score }</span>
		</div>
	);
}

/** Analyzer result item sub-component. */
function AnalyzerItem( { name, result }: { name: AnalyzerName; result: Record<string, unknown> } ): React.ReactElement {
	const score       = ( result.score as number ) ?? 0;
	const statusColor = score >= 70 ? 'success' : ( score >= 40 ? 'warning' : 'error' );
	const statusLabel = score >= 70 ? 'pass' : ( score >= 40 ? 'warning' : 'fail' );
	const label       = ANALYZER_LABELS[name] ?? name;

	// Build detail lines from the remaining fields (exclude score)
	const details = Object.entries( result )
		.filter( ( [key] ) => 'score' !== key )
		.map( ( [key, value] ) => `${ key.replace( /_/g, ' ' ) }: ${ value }` );

	return (
		<Collapse title={
			<div className="flex items-center gap-2">
				<Badge color={ statusColor } size="xs">
					{ statusLabel }
				</Badge>
				<span>{ label }</span>
				<span className="text-xs text-base-content/50 ml-auto">{ score }/100</span>
			</div>
		}>
			{ details.length > 0 ? (
				<ul className="list-disc list-inside text-sm space-y-1">
					{ details.map( ( detail, i ) => (
						<li key={ i }>{ detail }</li>
					) ) }
				</ul>
			) : (
				<p className="text-sm text-success">All checks passed.</p>
			) }
		</Collapse>
	);
}

/**
 * SEO content analysis panel.
 *
 * Displays analysis scores, grade, per-analyzer results with
 * expandable recommendations, and provides controls to run
 * analysis manually or auto-trigger as content changes.
 *
 * @example
 * ```tsx
 * <SeoAnalysisPanel
 *     baseUrl="/api/seo"
 *     modelType="post"
 *     modelId={1}
 *     focusKeyword="react seo"
 * />
 * ```
 */
export function SeoAnalysisPanel( {
	modelType,
	modelId,
	focusKeyword,
	debounceMs,
	onFocusKeywordChange,
	className,
	...apiOptions
}: SeoAnalysisPanelProps ): React.ReactElement {
	const {
		result,
		analyzing,
		loading,
		error,
		analyze,
	} = useSeoAnalysis( { ...apiOptions, modelType, modelId, debounceMs } );

	const handleAnalyze = useCallback( (): void => {
		analyze( focusKeyword );
	}, [analyze, focusKeyword] );

	if ( loading && !result ) {
		return <Loading />;
	}

	const gradeColor  = result ? GRADE_COLOR_MAP[result.grade_color] : 'info';
	const scoreColor  = result && result.overall_score >= 70 ? 'success' : ( result && result.overall_score >= 40 ? 'warning' : 'error' );

	return (
		<Card className={ className }>
			<div className="p-4 flex flex-col gap-4">
				<div className="flex items-center justify-between">
					<h3 className="font-semibold text-lg">SEO Analysis</h3>
					<Button
						size="sm"
						color="primary"
						onClick={ handleAnalyze }
						disabled={ analyzing }
					>
						{ analyzing ? 'Analyzing...' : 'Run Analysis' }
					</Button>
				</div>

				{ error && (
					<Alert color="error">{ error }</Alert>
				) }

				{ onFocusKeywordChange && (
					<Input
						label="Focus Keyword"
						value={ focusKeyword ?? '' }
						onChange={ ( e ) => onFocusKeywordChange( e.target.value ) }
						hint="The primary keyword to analyze against"
					/>
				) }

				{ !result && !error && (
					<p className="text-sm text-base-content/60">
						No analysis results yet. Click &quot;Run Analysis&quot; to analyze your content.
					</p>
				) }

				{ result && (
					<>
						{/* Overall score */}
						<div className="flex items-center gap-4">
							<div className={ `radial-progress text-${ scoreColor }` } style={ {
								'--value': result.overall_score,
								'--size': '5rem',
								'--thickness': '0.5rem',
							} as React.CSSProperties } role="progressbar">
								<span className="text-lg font-bold">{ result.overall_score }</span>
							</div>

							<div>
								<Badge color={ gradeColor } size="lg">
									{ result.grade_label }
								</Badge>
								{ result.word_count > 0 && (
									<p className="text-sm text-base-content/60 mt-1">
										{ result.word_count } words
									</p>
								) }
							</div>
						</div>

						{/* Category scores */}
						<div className="flex flex-col gap-2">
							<ScoreCategory label="Readability" score={ result.scores.readability } />
							<ScoreCategory label="Keyword" score={ result.scores.keyword } />
							<ScoreCategory label="Meta Tags" score={ result.scores.meta } />
							<ScoreCategory label="Content" score={ result.scores.content } />
						</div>

						{/* Issues and suggestions */}
						{ result.issue_count > 0 && (
							<div>
								<h4 className="font-medium text-sm mb-2 text-error">
									Issues ({ result.issue_count })
								</h4>
								<ul className="list-disc list-inside text-sm space-y-1">
									{ result.issues.map( ( issue, i ) => (
										<li key={ i }>{ issue.message }</li>
									) ) }
								</ul>
							</div>
						) }

						{ result.suggestion_count > 0 && (
							<div>
								<h4 className="font-medium text-sm mb-2 text-warning">
									Suggestions ({ result.suggestion_count })
								</h4>
								<ul className="list-disc list-inside text-sm space-y-1">
									{ result.suggestions.map( ( sug, i ) => (
										<li key={ i }>{ sug.message }</li>
									) ) }
								</ul>
							</div>
						) }

						{ result.passed_count > 0 && (
							<div>
								<h4 className="font-medium text-sm mb-2 text-success">
									Passed ({ result.passed_count })
								</h4>
								<ul className="list-disc list-inside text-sm space-y-1">
									{ result.passed_checks.map( ( check, i ) => (
										<li key={ i }>{ check }</li>
									) ) }
								</ul>
							</div>
						) }

						{/* Per-analyzer results */}
						{ result.analyzer_results && Object.keys( result.analyzer_results ).length > 0 && (
							<div>
								<h4 className="font-medium text-sm mb-2">Detailed Results</h4>
								<div className="flex flex-col gap-1">
									{ ( Object.entries( result.analyzer_results ) as [AnalyzerName, Record<string, unknown>][] ).map(
										( [name, analyzerResult] ) => (
											<AnalyzerItem
												key={ name }
												name={ name }
												result={ analyzerResult }
											/>
										),
									) }
								</div>
							</div>
						) }
					</>
				) }
			</div>
		</Card>
	);
}
