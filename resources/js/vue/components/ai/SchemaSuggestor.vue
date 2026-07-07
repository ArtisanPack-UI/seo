<!--
  Vue trigger for the SchemaGenerationAgent.

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
	title: string;
	url?: string;
}

export interface SchemaSuggestion {
	suggested_type: string;
	confidence: number;
	jsonld: Record<string, unknown>;
	missing_required_fields: string[];
}

const props = withDefaults(
	defineProps<{
		api: UseApiOptions;
		content: string;
		title: string;
		url?: string;
	}>(),
	{
		url: '',
	},
);

const emit = defineEmits<{
	( event: 'accept', suggestion: SchemaSuggestion ): void;
}>();

const { output, isLoading, error, run } = useAiAgent<Input, SchemaSuggestion>(
	'seo.generate_schema',
	props.api,
);

const disabled = computed(
	() =>
		isLoading.value ||
		props.content.trim() === '' ||
		props.title.trim() === '',
);

const handleClick = (): void => {
	void run( { content: props.content, title: props.title, url: props.url } );
};

const jsonldFormatted = computed( () =>
	output.value ? JSON.stringify( output.value.jsonld, null, 2 ) : '',
);

const confidencePercent = computed( () =>
	output.value ? Math.round( output.value.confidence * 100 ) : 0,
);
</script>

<template>
	<div class="seo-ai-suggestor" data-feature="seo.generate_schema">
		<button
			type="button"
			class="seo-ai-suggestor__button"
			:disabled="disabled"
			@click="handleClick"
		>
			<template v-if="isLoading">Generating…</template>
			<template v-else>Suggest schema</template>
		</button>

		<p v-if="error" class="seo-ai-suggestor__error" role="alert">
			{{ error }}
		</p>

		<div v-if="output" class="seo-ai-suggestor__suggestion">
			<div class="seo-ai-suggestor__suggestion-header">
				<strong>{{ output.suggested_type }}</strong>
				<span>Confidence: {{ confidencePercent }}%</span>
			</div>
			<pre class="seo-ai-suggestor__jsonld"><code>{{ jsonldFormatted }}</code></pre>
			<div
				v-if="output.missing_required_fields.length > 0"
				class="seo-ai-suggestor__missing"
			>
				<strong>Missing required fields:</strong>
				<ul>
					<li
						v-for="( field, index ) in output.missing_required_fields"
						:key="index"
					>
						{{ field }}
					</li>
				</ul>
			</div>
			<button
				type="button"
				class="seo-ai-suggestor__variant-apply"
				@click="emit( 'accept', output )"
			>
				Populate schema builder
			</button>
		</div>
	</div>
</template>
