<!--
  MetaPreview component.

  Displays a live Google search result snippet preview showing
  how the page will appear in search engine results.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.1.0
-->

<script setup lang="ts">
import { computed } from 'vue';

import { Card } from '@artisanpack-ui/vue';

import type { SeoMetaResponse } from '../../../types/seo-data';

const MAX_TITLE_LENGTH = 60;
const MAX_DESC_LENGTH  = 160;

const props = withDefaults( defineProps<{
	data: SeoMetaResponse;
	defaultUrl?: string;
	className?: string;
}>(), {
	defaultUrl: '',
	className: undefined,
} );

const title = computed( () => props.data.meta_title || 'Page Title' );
const description = computed( () => props.data.meta_description || 'No description set. Search engines will generate a snippet from the page content.' );
const url = computed( () => props.data.canonical_url || props.defaultUrl || 'https://example.com' );

const truncatedTitle = computed( () => {
	if ( title.value.length <= MAX_TITLE_LENGTH ) {
		return title.value;
	}

	return title.value.slice( 0, MAX_TITLE_LENGTH - 3 ) + '...';
} );

const truncatedDesc = computed( () => {
	if ( description.value.length <= MAX_DESC_LENGTH ) {
		return description.value;
	}

	return description.value.slice( 0, MAX_DESC_LENGTH - 3 ) + '...';
} );

const displayUrl = computed( () => {
	try {
		const parsed = new URL( url.value );

		return `${ parsed.hostname }${ parsed.pathname }`;
	} catch {
		return url.value;
	}
} );
</script>

<template>
	<Card :class="className">
		<div class="p-4">
			<p class="text-xs text-base-content/50 mb-2">Google Search Preview</p>
			<div class="max-w-xl">
				<p class="text-sm text-base-content/60 mb-0.5">{{ displayUrl }}</p>
				<h3 class="text-lg text-primary leading-tight mb-1">
					{{ truncatedTitle }}
				</h3>
				<p class="text-sm text-base-content/70 leading-snug">{{ truncatedDesc }}</p>
			</div>
		</div>
	</Card>
</template>
