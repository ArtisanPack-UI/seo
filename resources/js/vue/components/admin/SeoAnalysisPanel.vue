<!--
  SeoAnalysisPanel component.

  Displays real-time SEO content analysis results with per-analyzer
  scores, overall score, grade, and actionable recommendations.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.1.0
-->

<script setup lang="ts">
import { computed } from 'vue';

import { Alert, Badge, Button, Card, Collapse, Input, Loading, Progress } from '@artisanpack-ui/vue';

import { useSeoAnalysis } from '../../composables/useSeoAnalysis';

import type { UseApiOptions } from '../../composables/useApi';
import type { AnalysisGradeColor, AnalyzerName } from '../../../types/analysis';

const GRADE_COLOR_MAP: Record<AnalysisGradeColor, 'success' | 'warning' | 'error'> = {
	green: 'success',
	yellow: 'warning',
	red: 'error',
};

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

const props = withDefaults( defineProps<UseApiOptions & {
	modelType: string;
	modelId: number;
	focusKeyword?: string;
	debounceMs?: number;
	className?: string;
}>(), {
	csrfToken: undefined,
	authorization: undefined,
	credentials: undefined,
	focusKeyword: undefined,
	debounceMs: undefined,
	className: undefined,
} );

const emit = defineEmits<{
	'update:focusKeyword': [value: string];
}>();

const {
	result,
	analyzing,
	loading,
	error,
	analyze,
} = useSeoAnalysis( {
	baseUrl: props.baseUrl,
	csrfToken: props.csrfToken,
	authorization: props.authorization,
	credentials: props.credentials,
	modelType: props.modelType,
	modelId: props.modelId,
	debounceMs: props.debounceMs,
} );

const gradeColor = computed( () => result.value ? GRADE_COLOR_MAP[result.value.grade_color] : 'info' );
const scoreColor = computed( () => {
	if ( !result.value ) {
		return 'error';
	}

	return result.value.overall_score >= 70 ? 'success' : ( result.value.overall_score >= 40 ? 'warning' : 'error' );
} );

const analyzerEntries = computed( () => {
	if ( !result.value?.analyzer_results ) {
		return [];
	}

	return Object.entries( result.value.analyzer_results ) as [AnalyzerName, Record<string, unknown>][];
} );

function handleAnalyze(): void {
	analyze( props.focusKeyword );
}

function getAnalyzerLabel( name: AnalyzerName ): string {
	return ANALYZER_LABELS[name] ?? name;
}

function getAnalyzerScore( item: Record<string, unknown> ): number {
	return ( item.score as number ) ?? 0;
}

function getAnalyzerStatus( item: Record<string, unknown> ): string {
	const score = getAnalyzerScore( item );

	return score >= 70 ? 'pass' : ( score >= 40 ? 'warning' : 'fail' );
}

function getAnalyzerStatusColor( item: Record<string, unknown> ): 'success' | 'warning' | 'error' {
	const score = getAnalyzerScore( item );

	return score >= 70 ? 'success' : ( score >= 40 ? 'warning' : 'error' );
}

function getAnalyzerDetails( item: Record<string, unknown> ): string[] {
	return Object.entries( item )
		.filter( ( [key] ) => 'score' !== key )
		.map( ( [key, value] ) => `${ key.replace( /_/g, ' ' ) }: ${ value }` );
}

function getScoreColor( score: number ): 'success' | 'warning' | 'error' {
	return score >= 70 ? 'success' : ( score >= 40 ? 'warning' : 'error' );
}
</script>

<template>
	<Card :class="className">
		<div class="p-4 flex flex-col gap-4">
			<div class="flex items-center justify-between">
				<h3 class="font-semibold text-lg">SEO Analysis</h3>
				<Button size="sm" color="primary" @click="handleAnalyze" :disabled="analyzing">
					{{ analyzing ? 'Analyzing...' : 'Run Analysis' }}
				</Button>
			</div>

			<Alert v-if="error" color="error">{{ error }}</Alert>

			<Input
				v-if="focusKeyword !== undefined"
				label="Focus Keyword"
				:model-value="focusKeyword ?? ''"
				@update:model-value="emit( 'update:focusKeyword', String( $event ) )"
				hint="The primary keyword to analyze against"
			/>

			<Loading v-if="loading && !result" />

			<p v-else-if="!result && !error" class="text-sm text-base-content/60">
				No analysis results yet. Click "Run Analysis" to analyze your content.
			</p>

			<template v-if="result">
				<!-- Overall score -->
				<div class="flex items-center gap-4">
					<div
						class="radial-progress"
						:class="`text-${ scoreColor }`"
						:style="{ '--value': result.overall_score, '--size': '5rem', '--thickness': '0.5rem' }"
						role="progressbar"
					>
						<span class="text-lg font-bold">{{ result.overall_score }}</span>
					</div>

					<div>
						<Badge :color="gradeColor" size="lg">{{ result.grade_label }}</Badge>
						<p v-if="result.word_count > 0" class="text-sm text-base-content/60 mt-1">
							{{ result.word_count }} words
						</p>
					</div>
				</div>

				<!-- Category scores -->
				<div class="flex flex-col gap-2">
					<div class="flex items-center gap-2">
						<span class="text-sm w-24">Readability</span>
						<Progress :value="result.scores.readability" :max="100" :color="getScoreColor( result.scores.readability )" class="flex-1" />
						<span class="text-sm font-mono w-8 text-right">{{ result.scores.readability }}</span>
					</div>
					<div class="flex items-center gap-2">
						<span class="text-sm w-24">Keyword</span>
						<Progress :value="result.scores.keyword" :max="100" :color="getScoreColor( result.scores.keyword )" class="flex-1" />
						<span class="text-sm font-mono w-8 text-right">{{ result.scores.keyword }}</span>
					</div>
					<div class="flex items-center gap-2">
						<span class="text-sm w-24">Meta Tags</span>
						<Progress :value="result.scores.meta" :max="100" :color="getScoreColor( result.scores.meta )" class="flex-1" />
						<span class="text-sm font-mono w-8 text-right">{{ result.scores.meta }}</span>
					</div>
					<div class="flex items-center gap-2">
						<span class="text-sm w-24">Content</span>
						<Progress :value="result.scores.content" :max="100" :color="getScoreColor( result.scores.content )" class="flex-1" />
						<span class="text-sm font-mono w-8 text-right">{{ result.scores.content }}</span>
					</div>
				</div>

				<!-- Issues -->
				<div v-if="result.issue_count > 0">
					<h4 class="font-medium text-sm mb-2 text-error">Issues ({{ result.issue_count }})</h4>
					<ul class="list-disc list-inside text-sm space-y-1">
						<li v-for="( issue, i ) in result.issues" :key="i">{{ issue.message }}</li>
					</ul>
				</div>

				<!-- Suggestions -->
				<div v-if="result.suggestion_count > 0">
					<h4 class="font-medium text-sm mb-2 text-warning">Suggestions ({{ result.suggestion_count }})</h4>
					<ul class="list-disc list-inside text-sm space-y-1">
						<li v-for="( sug, i ) in result.suggestions" :key="i">{{ sug.message }}</li>
					</ul>
				</div>

				<!-- Passed -->
				<div v-if="result.passed_count > 0">
					<h4 class="font-medium text-sm mb-2 text-success">Passed ({{ result.passed_count }})</h4>
					<ul class="list-disc list-inside text-sm space-y-1">
						<li v-for="( check, i ) in result.passed_checks" :key="i">{{ check }}</li>
					</ul>
				</div>

				<!-- Per-analyzer results -->
				<div v-if="analyzerEntries.length > 0">
					<h4 class="font-medium text-sm mb-2">Detailed Results</h4>
					<div class="flex flex-col gap-1">
						<Collapse
							v-for="[name, analyzerResult] in analyzerEntries"
							:key="name"
							:title="`${ getAnalyzerLabel( name ) } — ${ getAnalyzerScore( analyzerResult ) }/100`"
						>
							<template #title>
								<div class="flex items-center gap-2">
									<Badge :color="getAnalyzerStatusColor( analyzerResult )" size="xs">
										{{ getAnalyzerStatus( analyzerResult ) }}
									</Badge>
									<span>{{ getAnalyzerLabel( name ) }}</span>
									<span class="text-xs text-base-content/50 ml-auto">{{ getAnalyzerScore( analyzerResult ) }}/100</span>
								</div>
							</template>

							<ul v-if="getAnalyzerDetails( analyzerResult ).length > 0" class="list-disc list-inside text-sm space-y-1">
								<li v-for="( detail, i ) in getAnalyzerDetails( analyzerResult )" :key="i">{{ detail }}</li>
							</ul>
							<p v-else class="text-sm text-success">All checks passed.</p>
						</Collapse>
					</div>
				</div>
			</template>
		</div>
	</Card>
</template>
