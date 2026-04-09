<!--
  TwitterCardTab component.

  Tab for editing Twitter Card meta fields including card type,
  title, description, image, site handle, and creator handle.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.1.0
-->

<script setup lang="ts">
import { Input, Select, Textarea } from '@artisanpack-ui/vue';

import type { SeoMetaResponse } from '../../../types/seo-data';

const CARD_TYPE_OPTIONS = [
	{ value: 'summary', name: 'Summary' },
	{ value: 'summary_large_image', name: 'Summary with Large Image' },
	{ value: 'app', name: 'App' },
	{ value: 'player', name: 'Player' },
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
			label="Card Type"
			:model-value="data.twitter_card ?? 'summary'"
			@update:model-value="emit( 'change', 'twitter_card', $event )"
			:options="CARD_TYPE_OPTIONS"
			option-value="value"
			option-label="name"
			:error="errors?.twitter_card?.[0]"
		/>

		<Input
			label="Twitter Title"
			:model-value="data.twitter_title ?? ''"
			@update:model-value="emit( 'change', 'twitter_title', $event )"
			hint="Leave blank to use the meta title"
			:error="errors?.twitter_title?.[0]"
		/>

		<Textarea
			label="Twitter Description"
			:model-value="data.twitter_description ?? ''"
			@update:model-value="emit( 'change', 'twitter_description', $event )"
			hint="Leave blank to use the meta description"
			:error="errors?.twitter_description?.[0]"
			:rows="3"
		/>

		<Input
			label="Twitter Image URL"
			:model-value="data.twitter_image ?? ''"
			@update:model-value="emit( 'change', 'twitter_image', $event )"
			hint="Minimum size: 120x120 for Summary, 300x157 for Large Image"
			:error="errors?.twitter_image?.[0]"
			type="url"
		/>

		<Input
			label="Site Handle"
			:model-value="data.twitter_site ?? ''"
			@update:model-value="emit( 'change', 'twitter_site', $event )"
			hint="The @username of the website (e.g. @example)"
			:error="errors?.twitter_site?.[0]"
		/>

		<Input
			label="Creator Handle"
			:model-value="data.twitter_creator ?? ''"
			@update:model-value="emit( 'change', 'twitter_creator', $event )"
			hint="The @username of the content creator"
			:error="errors?.twitter_creator?.[0]"
		/>
	</div>
</template>
