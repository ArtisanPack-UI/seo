<!--
  Vue trigger for the MetaTitleSuggestionAgent.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.2.0
-->

<script setup lang="ts">
import { computed } from 'vue';

import { useAiAgent } from '../../composables/useAiAgent';
import type { UseApiOptions } from '../../composables/useApi';

export interface TitleVariant {
	title: string;
	char_count: number;
	rationale: string;
}

interface Input {
	content: string;
	primary_keyword?: string;
	brand?: string;
}

interface Output {
	variants: TitleVariant[];
}

const props = withDefaults(
	defineProps<{
		api: UseApiOptions;
		content: string;
		primaryKeyword?: string;
		brand?: string;
	}>(),
	{
		primaryKeyword: '',
		brand: '',
	},
);

const emit = defineEmits<{
	( event: 'select', title: string ): void;
}>();

const { output, isLoading, error, run } = useAiAgent<Input, Output>(
	'seo.suggest_meta_title',
	props.api,
);

const disabled = computed(
	() => isLoading.value || props.content.trim() === '',
);

const handleClick = (): void => {
	void run( {
		content: props.content,
		primary_keyword: props.primaryKeyword,
		brand: props.brand,
	} );
};
</script>

<template>
	<div class="seo-ai-suggestor" data-feature="seo.suggest_meta_title">
		<button
			type="button"
			class="seo-ai-suggestor__button"
			:disabled="disabled"
			@click="handleClick"
		>
			<template v-if="isLoading">Generating…</template>
			<template v-else>Suggest titles</template>
		</button>

		<p v-if="error" class="seo-ai-suggestor__error" role="alert">
			{{ error }}
		</p>

		<ul
			v-if="output && output.variants.length > 0"
			class="seo-ai-suggestor__variants"
		>
			<li
				v-for="( variant, index ) in output.variants"
				:key="index"
				class="seo-ai-suggestor__variant"
			>
				<div class="seo-ai-suggestor__variant-header">
					<strong class="seo-ai-suggestor__variant-title">{{ variant.title }}</strong>
					<span class="seo-ai-suggestor__variant-count">{{ variant.char_count }}/60</span>
				</div>
				<p
					v-if="variant.rationale"
					class="seo-ai-suggestor__variant-rationale"
				>
					{{ variant.rationale }}
				</p>
				<button
					type="button"
					class="seo-ai-suggestor__variant-apply"
					@click="emit( 'select', variant.title )"
				>
					Use this title
				</button>
			</li>
		</ul>
	</div>
</template>
