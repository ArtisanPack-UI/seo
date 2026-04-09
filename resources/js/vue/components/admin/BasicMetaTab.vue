<!--
  BasicMetaTab component.

  Tab for editing basic SEO meta fields: title, description,
  canonical URL, focus keyword, and robots directives.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.1.0
-->

<script setup lang="ts">
import { computed } from 'vue';

import { Alert, Checkbox, Divider, Input, Textarea } from '@artisanpack-ui/vue';

import type { SeoMetaResponse } from '../../../types/seo-data';

const MAX_TITLE_LENGTH = 60;
const MAX_DESC_LENGTH  = 160;

const props = defineProps<{
	/** Current SEO meta data. */
	data: SeoMetaResponse;
	/** Validation errors keyed by field name. */
	errors?: Record<string, string[]>;
}>();

const emit = defineEmits<{
	'change': [field: string, value: unknown];
}>();

const titleLength = computed( () => props.data.meta_title?.length ?? 0 );
const descLength  = computed( () => props.data.meta_description?.length ?? 0 );

const titleHint = computed( () =>
	`${ titleLength.value }/${ MAX_TITLE_LENGTH } characters${ titleLength.value > MAX_TITLE_LENGTH ? ' (too long)' : '' }`,
);

const descHint = computed( () =>
	`${ descLength.value }/${ MAX_DESC_LENGTH } characters${ descLength.value > MAX_DESC_LENGTH ? ' (too long)' : '' }`,
);
</script>

<template>
	<div class="flex flex-col gap-4">
		<Input
			label="Meta Title"
			:model-value="data.meta_title ?? ''"
			@update:model-value="emit( 'change', 'meta_title', $event )"
			:hint="titleHint"
			:error="errors?.meta_title?.[0]"
			:maxlength="255"
		/>

		<Textarea
			label="Meta Description"
			:model-value="data.meta_description ?? ''"
			@update:model-value="emit( 'change', 'meta_description', $event )"
			:hint="descHint"
			:error="errors?.meta_description?.[0]"
			:rows="3"
		/>

		<Input
			label="Canonical URL"
			:model-value="data.canonical_url ?? ''"
			@update:model-value="emit( 'change', 'canonical_url', $event )"
			hint="Leave blank to use the default URL"
			:error="errors?.canonical_url?.[0]"
			type="url"
		/>

		<Input
			label="Focus Keyword"
			:model-value="data.focus_keyword ?? ''"
			@update:model-value="emit( 'change', 'focus_keyword', $event )"
			hint="The primary keyword to optimize for"
			:error="errors?.focus_keyword?.[0]"
		/>

		<Input
			label="Secondary Keywords"
			:model-value="data.secondary_keywords?.join( ', ' ) ?? ''"
			@update:model-value="emit( 'change', 'secondary_keywords', String( $event ).split( ',' ).map( k => k.trim() ).filter( Boolean ) )"
			hint="Comma-separated list of additional keywords"
			:error="errors?.secondary_keywords?.[0]"
		/>

		<Divider label="Robots Directives" />

		<div class="flex flex-col gap-2">
			<Checkbox
				label="No Index"
				:model-value="data.no_index"
				@update:model-value="emit( 'change', 'no_index', $event )"
				hint="Prevent search engines from indexing this page"
			/>

			<Checkbox
				label="No Follow"
				:model-value="data.no_follow"
				@update:model-value="emit( 'change', 'no_follow', $event )"
				hint="Prevent search engines from following links on this page"
			/>
		</div>

		<Alert v-if="data.no_index" color="warning">
			This page will not appear in search engine results.
		</Alert>
	</div>
</template>
