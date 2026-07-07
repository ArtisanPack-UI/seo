<!--
  Vue trigger for the MetaDescriptionAgent.

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
	primary_keyword?: string;
}

export interface MetaDescriptionSuggestion {
	meta_description: string;
	character_count: number;
	rationale: string;
}

const props = withDefaults(
	defineProps<{
		api: UseApiOptions;
		content: string;
		primaryKeyword?: string;
	}>(),
	{
		primaryKeyword: '',
	},
);

const emit = defineEmits<{
	( event: 'accept', description: string ): void;
}>();

const { output, isLoading, error, run } = useAiAgent<Input, MetaDescriptionSuggestion>(
	'seo.suggest_meta_description',
	props.api,
);

const disabled = computed(
	() => isLoading.value || props.content.trim() === '',
);

const handleClick = (): void => {
	void run( {
		content: props.content,
		primary_keyword: props.primaryKeyword,
	} );
};
</script>

<template>
	<div class="seo-ai-suggestor" data-feature="seo.suggest_meta_description">
		<button
			type="button"
			class="seo-ai-suggestor__button"
			:disabled="disabled"
			@click="handleClick"
		>
			<template v-if="isLoading">Generating…</template>
			<template v-else>Suggest meta description</template>
		</button>

		<p v-if="error" class="seo-ai-suggestor__error" role="alert">
			{{ error }}
		</p>

		<div
			v-if="output && output.meta_description !== ''"
			class="seo-ai-suggestor__suggestion"
		>
			<p class="seo-ai-suggestor__suggestion-text">{{ output.meta_description }}</p>
			<div class="seo-ai-suggestor__suggestion-meta">
				<span class="seo-ai-suggestor__suggestion-count">
					{{ output.character_count }}/160
				</span>
				<span
					v-if="output.rationale"
					class="seo-ai-suggestor__suggestion-rationale"
				>
					{{ output.rationale }}
				</span>
			</div>
			<button
				type="button"
				class="seo-ai-suggestor__variant-apply"
				@click="emit( 'accept', output.meta_description )"
			>
				Use this description
			</button>
		</div>
	</div>
</template>
