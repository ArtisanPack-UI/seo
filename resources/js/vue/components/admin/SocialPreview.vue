<!--
  SocialPreview component.

  Displays live Open Graph and Twitter Card previews showing
  how shared links will appear on social media platforms.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.1.0
-->

<script setup lang="ts">
import { computed, ref } from 'vue';

import { Card, Tabs } from '@artisanpack-ui/vue';

import type { SeoMetaResponse } from '../../../types/seo-data';

const props = withDefaults( defineProps<{
	data: SeoMetaResponse;
	defaultUrl?: string;
	className?: string;
}>(), {
	defaultUrl: '',
	className: undefined,
} );

const activeTab = ref( 'facebook' );

const ogTitle       = computed( () => props.data.og_title || props.data.meta_title || 'Page Title' );
const ogDescription = computed( () => props.data.og_description || props.data.meta_description || '' );
const ogImage       = computed( () => props.data.og_image );
const ogSiteName    = computed( () => props.data.og_site_name || '' );

const twTitle       = computed( () => props.data.twitter_title || props.data.meta_title || 'Page Title' );
const twDescription = computed( () => props.data.twitter_description || props.data.meta_description || '' );
const twImage       = computed( () => props.data.twitter_image || props.data.og_image );
const twCardType    = computed( () => props.data.twitter_card || 'summary' );
const isLargeImage  = computed( () => 'summary_large_image' === twCardType.value );

const displayDomain = computed( () => {
	const url = props.data.canonical_url || props.defaultUrl;

	try {
		return new URL( url ).hostname;
	} catch {
		return url || 'example.com';
	}
} );

const tabs = [
	{ name: 'facebook', label: 'Facebook / Open Graph' },
	{ name: 'twitter', label: 'Twitter / X' },
];
</script>

<template>
	<Card :class="className">
		<div class="p-4">
			<p class="text-xs text-base-content/50 mb-2">Social Media Preview</p>

			<Tabs :tabs="tabs" v-model:active-tab="activeTab" size="sm">
				<template #facebook>
					<div class="border border-base-300 rounded-lg overflow-hidden max-w-lg mt-3">
						<div v-if="ogImage" class="aspect-[1.91/1] bg-base-200 overflow-hidden">
							<img :src="ogImage" alt="Open Graph preview" class="w-full h-full object-cover" />
						</div>
						<div v-else class="aspect-[1.91/1] bg-base-200 flex items-center justify-center">
							<span class="text-base-content/30 text-sm">No image set</span>
						</div>

						<div class="p-3 bg-base-100">
							<p class="text-xs text-base-content/50 uppercase tracking-wide mb-1">{{ displayDomain }}</p>
							<h4 class="font-semibold text-base-content leading-tight mb-1 line-clamp-2">{{ ogTitle }}</h4>
							<p v-if="ogDescription" class="text-sm text-base-content/60 line-clamp-2">{{ ogDescription }}</p>
							<p v-if="ogSiteName" class="text-xs text-base-content/40 mt-1">{{ ogSiteName }}</p>
						</div>
					</div>
				</template>

				<template #twitter>
					<!-- Large image card -->
					<div v-if="isLargeImage" class="border border-base-300 rounded-2xl overflow-hidden max-w-lg mt-3">
						<div v-if="twImage" class="aspect-[2/1] bg-base-200 overflow-hidden">
							<img :src="twImage" alt="Twitter Card preview" class="w-full h-full object-cover" />
						</div>
						<div v-else class="aspect-[2/1] bg-base-200 flex items-center justify-center">
							<span class="text-base-content/30 text-sm">No image set</span>
						</div>

						<div class="p-3 bg-base-100">
							<h4 class="font-semibold text-base-content leading-tight mb-0.5 line-clamp-1">{{ twTitle }}</h4>
							<p v-if="twDescription" class="text-sm text-base-content/60 line-clamp-2 mb-1">{{ twDescription }}</p>
							<p class="text-xs text-base-content/40">{{ displayDomain }}</p>
						</div>
					</div>

					<!-- Summary card (side-by-side) -->
					<div v-else class="border border-base-300 rounded-2xl overflow-hidden max-w-lg flex mt-3">
						<div class="w-32 h-32 shrink-0 bg-base-200 overflow-hidden">
							<img v-if="twImage" :src="twImage" alt="Twitter Card preview" class="w-full h-full object-cover" />
							<div v-else class="w-full h-full flex items-center justify-center">
								<span class="text-base-content/30 text-xs">No image</span>
							</div>
						</div>

						<div class="p-3 bg-base-100 flex flex-col justify-center min-w-0">
							<p class="text-xs text-base-content/40 mb-0.5">{{ displayDomain }}</p>
							<h4 class="font-semibold text-base-content leading-tight mb-0.5 line-clamp-2 text-sm">{{ twTitle }}</h4>
							<p v-if="twDescription" class="text-xs text-base-content/60 line-clamp-2">{{ twDescription }}</p>
						</div>
					</div>
				</template>
			</Tabs>
		</div>
	</Card>
</template>
