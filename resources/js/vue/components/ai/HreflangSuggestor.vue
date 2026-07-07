<!--
  Vue trigger for the HreflangSuggestionAgent.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.2.0
-->

<script setup lang="ts">
import { computed } from 'vue';

import { useAiAgent } from '../../composables/useAiAgent';
import type { UseApiOptions } from '../../composables/useApi';

export interface HreflangTranslation {
	url: string;
	lang: string;
}

export interface HreflangPage {
	url: string;
	lang: string;
	translations: HreflangTranslation[];
}

export interface HreflangIssue {
	page_url: string;
	issue_type: string;
	suggested_hreflang: HreflangTranslation[];
}

interface Input {
	pages: HreflangPage[];
}

interface Output {
	issues: HreflangIssue[];
}

const props = defineProps<{
	api: UseApiOptions;
	pages: HreflangPage[];
}>();

const emit = defineEmits<{
	( event: 'apply-fix', issue: HreflangIssue ): void;
}>();

const { output, isLoading, error, run } = useAiAgent<Input, Output>(
	'seo.suggest_hreflang',
	props.api,
);

const disabled = computed( () => isLoading.value || props.pages.length === 0 );

const handleClick = (): void => {
	void run( { pages: props.pages } );
};
</script>

<template>
	<div class="seo-ai-suggestor" data-feature="seo.suggest_hreflang">
		<button
			type="button"
			class="seo-ai-suggestor__button"
			:disabled="disabled"
			@click="handleClick"
		>
			<template v-if="isLoading">Analyzing…</template>
			<template v-else>Check hreflang</template>
		</button>

		<p v-if="error" class="seo-ai-suggestor__error" role="alert">
			{{ error }}
		</p>

		<ul
			v-if="output && output.issues.length > 0"
			class="seo-ai-suggestor__issues"
		>
			<li
				v-for="( issue, index ) in output.issues"
				:key="index"
				class="seo-ai-suggestor__issue"
			>
				<div class="seo-ai-suggestor__issue-header">
					<code>{{ issue.page_url }}</code>
					<span class="seo-ai-suggestor__issue-type">{{ issue.issue_type }}</span>
				</div>
				<ul
					v-if="issue.suggested_hreflang.length > 0"
					class="seo-ai-suggestor__hreflang"
				>
					<li
						v-for="( entry, entryIndex ) in issue.suggested_hreflang"
						:key="entryIndex"
					>
						<strong>{{ entry.lang }}</strong>
						<code>{{ entry.url }}</code>
					</li>
				</ul>
				<button
					type="button"
					class="seo-ai-suggestor__variant-apply"
					@click="emit( 'apply-fix', issue )"
				>
					Apply suggested tags
				</button>
			</li>
		</ul>

		<p
			v-else-if="output && output.issues.length === 0 && ! isLoading && ! error"
			class="seo-ai-suggestor__empty"
		>
			No hreflang issues detected.
		</p>
	</div>
</template>
