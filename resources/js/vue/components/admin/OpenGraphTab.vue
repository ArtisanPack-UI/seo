<!--
  OpenGraphTab component.

  Tab for editing Open Graph meta fields including type, title,
  description, image, URL, site name, and locale.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.1.0
-->

<script setup lang="ts">
import { Input, Select, Textarea } from '@artisanpack-ui/vue';

import type { SeoMetaResponse } from '../../../types/seo-data';

const OG_TYPE_OPTIONS = [
	{ value: 'website', name: 'Website' },
	{ value: 'article', name: 'Article' },
	{ value: 'book', name: 'Book' },
	{ value: 'profile', name: 'Profile' },
	{ value: 'music.song', name: 'Music - Song' },
	{ value: 'music.album', name: 'Music - Album' },
	{ value: 'music.playlist', name: 'Music - Playlist' },
	{ value: 'music.radio_station', name: 'Music - Radio Station' },
	{ value: 'video.movie', name: 'Video - Movie' },
	{ value: 'video.episode', name: 'Video - Episode' },
	{ value: 'video.tv_show', name: 'Video - TV Show' },
	{ value: 'video.other', name: 'Video - Other' },
];

defineProps<{
	data: SeoMetaResponse;
	errors?: Record<string, string[]>;
}>();

const emit = defineEmits<{
	'change': [field: string, value: unknown];
}>();
</script>

<template>
	<div class="flex flex-col gap-4">
		<Select
			label="OG Type"
			:model-value="data.og_type ?? 'website'"
			@update:model-value="emit( 'change', 'og_type', $event )"
			:options="OG_TYPE_OPTIONS"
			option-value="value"
			option-label="name"
			:error="errors?.og_type?.[0]"
		/>

		<Input
			label="OG Title"
			:model-value="data.og_title ?? ''"
			@update:model-value="emit( 'change', 'og_title', $event )"
			hint="Leave blank to use the meta title"
			:error="errors?.og_title?.[0]"
		/>

		<Textarea
			label="OG Description"
			:model-value="data.og_description ?? ''"
			@update:model-value="emit( 'change', 'og_description', $event )"
			hint="Leave blank to use the meta description"
			:error="errors?.og_description?.[0]"
			:rows="3"
		/>

		<Input
			label="OG Image URL"
			:model-value="data.og_image ?? ''"
			@update:model-value="emit( 'change', 'og_image', $event )"
			hint="Recommended size: 1200x630 pixels"
			:error="errors?.og_image?.[0]"
			type="url"
		/>

		<Input
			label="Site Name"
			:model-value="data.og_site_name ?? ''"
			@update:model-value="emit( 'change', 'og_site_name', $event )"
			hint="Leave blank to use the site default"
			:error="errors?.og_site_name?.[0]"
		/>

		<Input
			label="Locale"
			:model-value="data.og_locale ?? ''"
			@update:model-value="emit( 'change', 'og_locale', $event )"
			hint="e.g. en_US, fr_FR"
			:error="errors?.og_locale?.[0]"
		/>
	</div>
</template>
