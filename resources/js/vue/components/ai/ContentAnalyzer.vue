<!--
  Vue trigger for the ContentAnalysisAgent.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.2.0
-->

<script setup lang="ts">
import { computed } from 'vue';

import { useAiAgent } from '../../composables/useAiAgent';
import type { UseApiOptions } from '../../composables/useApi';

interface Input {
	content: string;
	primary_keyword: string;
	secondary_keywords?: string[];
}

export interface DimensionScore {
	score: number;
	recommendations: string[];
}

export interface ContentAnalysisResult {
	overall_score: number;
	dimensions: {
		keyword_usage: DimensionScore;
		readability: DimensionScore;
		structure: DimensionScore;
		semantic_completeness: DimensionScore;
	};
}

const DIMENSION_LABELS: Record<keyof ContentAnalysisResult['dimensions'], string> = {
	keyword_usage: 'Keyword usage',
	readability: 'Readability',
	structure: 'Structure',
	semantic_completeness: 'Semantic completeness',
};

const DIMENSION_KEYS = Object.keys( DIMENSION_LABELS ) as Array<
	keyof ContentAnalysisResult['dimensions']
>;

const props = withDefaults(
	defineProps<{
		api: UseApiOptions;
		content: string;
		primaryKeyword: string;
		secondaryKeywords?: string[];
	}>(),
	{
		secondaryKeywords: () => [],
	},
);

const { output, isLoading, error, run } = useAiAgent<Input, ContentAnalysisResult>(
	'seo.analyze_content',
	props.api,
);

const disabled = computed(
	() =>
		isLoading.value ||
		props.content.trim() === '' ||
		props.primaryKeyword.trim() === '',
);

const handleClick = (): void => {
	void run( {
		content: props.content,
		primary_keyword: props.primaryKeyword,
		secondary_keywords: props.secondaryKeywords,
	} );
};
</script>

<template>
	<div class="seo-ai-analyzer" data-feature="seo.analyze_content">
		<button
			type="button"
			class="seo-ai-analyzer__button"
			:disabled="disabled"
			@click="handleClick"
		>
			<template v-if="isLoading">Analyzing…</template>
			<template v-else>Analyze content</template>
		</button>

		<p v-if="error" class="seo-ai-analyzer__error" role="alert">
			{{ error }}
		</p>

		<div v-if="output" class="seo-ai-analyzer__result">
			<div class="seo-ai-analyzer__overall">
				<span class="seo-ai-analyzer__overall-label">Overall score</span>
				<span class="seo-ai-analyzer__overall-value">
					{{ output.overall_score }}/100
				</span>
			</div>

			<ul class="seo-ai-analyzer__dimensions">
				<li
					v-for="key in DIMENSION_KEYS"
					:key="key"
					class="seo-ai-analyzer__dimension"
				>
					<div class="seo-ai-analyzer__dimension-header">
						<span class="seo-ai-analyzer__dimension-name">
							{{ DIMENSION_LABELS[key] }}
						</span>
						<span class="seo-ai-analyzer__dimension-score">
							{{ output.dimensions[key].score }}/100
						</span>
					</div>
					<ul
						v-if="output.dimensions[key].recommendations.length > 0"
						class="seo-ai-analyzer__recommendations"
					>
						<li
							v-for="( rec, index ) in output.dimensions[key].recommendations"
							:key="index"
						>
							{{ rec }}
						</li>
					</ul>
				</li>
			</ul>
		</div>
	</div>
</template>
