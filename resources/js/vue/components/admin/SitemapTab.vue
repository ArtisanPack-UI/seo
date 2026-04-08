<!--
  SitemapTab component.

  Tab for configuring sitemap inclusion settings including
  toggle, change frequency, and priority.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.1.0
-->

<script setup lang="ts">
import { Checkbox, Input, Select } from '@artisanpack-ui/vue';

import type { SeoMetaResponse } from '../../../types/seo-data';

const CHANGEFREQ_OPTIONS = [
	{ value: '', name: 'Default' },
	{ value: 'always', name: 'Always' },
	{ value: 'hourly', name: 'Hourly' },
	{ value: 'daily', name: 'Daily' },
	{ value: 'weekly', name: 'Weekly' },
	{ value: 'monthly', name: 'Monthly' },
	{ value: 'yearly', name: 'Yearly' },
	{ value: 'never', name: 'Never' },
];

defineProps<{
	data: SeoMetaResponse;
	errors?: Record<string, string[]>;
}>();

const emit = defineEmits<{
	'change': [field: string, value: unknown];
}>();

function handlePriorityChange( rawValue: string ): void {
	const trimmed = String( rawValue ).trim();

	if ( !trimmed ) {
		emit( 'change', 'sitemap_priority', null );

		return;
	}

	const parsed = parseFloat( trimmed );
	emit( 'change', 'sitemap_priority', Number.isFinite( parsed ) ? parsed : null );
}
</script>

<template>
	<div class="flex flex-col gap-4">
		<Checkbox
			label="Exclude from Sitemap"
			:model-value="data.exclude_from_sitemap"
			@update:model-value="emit( 'change', 'exclude_from_sitemap', $event )"
			hint="When checked, this page will not appear in the XML sitemap"
		/>

		<template v-if="!data.exclude_from_sitemap">
			<Select
				label="Change Frequency"
				:model-value="data.sitemap_changefreq ?? ''"
				@update:model-value="emit( 'change', 'sitemap_changefreq', $event || null )"
				:options="CHANGEFREQ_OPTIONS"
				option-value="value"
				option-label="name"
				hint="How often this page is likely to change"
				:error="errors?.sitemap_changefreq?.[0]"
			/>

			<Input
				label="Priority"
				:model-value="data.sitemap_priority ?? ''"
				@update:model-value="handlePriorityChange( String( $event ) )"
				type="number"
				:min="0"
				:max="1"
				:step="0.1"
				hint="Value between 0.0 and 1.0 (default: 0.5)"
				:error="errors?.sitemap_priority?.[0]"
			/>
		</template>
	</div>
</template>
