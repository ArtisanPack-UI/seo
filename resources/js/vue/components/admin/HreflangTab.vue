<!--
  HreflangTab component.

  Tab for editing hreflang language/region URL mappings
  with add/remove rows and x-default support.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.1.0
-->

<script setup lang="ts">
import { computed } from 'vue';

import { Alert, Button, Input } from '@artisanpack-ui/vue';

import type { HreflangEntry } from '../../../types/hreflang';
import type { SeoMetaResponse } from '../../../types/seo-data';

const props = defineProps<{
	data: SeoMetaResponse;
	errors?: Record<string, string[]>;
}>();

const emit = defineEmits<{
	'change': [entries: HreflangEntry[]];
}>();

const entries    = computed( () => props.data.hreflang ?? [] );
const hasXDefault = computed( () => entries.value.some( ( e ) => 'x-default' === e.hreflang ) );

function addEntry(): void {
	emit( 'change', [...entries.value, { hreflang: '', href: '' }] );
}

function addXDefault(): void {
	if ( !hasXDefault.value ) {
		emit( 'change', [...entries.value, { hreflang: 'x-default', href: '' }] );
	}
}

function updateEntry( index: number, field: keyof HreflangEntry, value: string ): void {
	const updated = entries.value.map( ( entry, i ) =>
		i === index ? { ...entry, [field]: value } : entry,
	);
	emit( 'change', updated );
}

function removeEntry( index: number ): void {
	emit( 'change', entries.value.filter( ( _, i ) => i !== index ) );
}
</script>

<template>
	<div class="flex flex-col gap-4">
		<Alert color="info">
			Hreflang tags tell search engines about alternate language versions of your content.
			Use ISO 639-1 language codes (e.g. "en", "fr", "de")
			optionally combined with ISO 3166-1 country codes (e.g. "en-US", "fr-CA").
		</Alert>

		<p v-if="entries.length === 0" class="text-base-content/60 text-sm">
			No hreflang entries. Click "Add Entry" to add alternate language URLs.
		</p>

		<div
			v-for="( entry, index ) in entries"
			:key="index"
			class="flex gap-2 items-end"
		>
			<Input
				:label="index === 0 ? 'Language Code' : undefined"
				aria-label="Language Code"
				:model-value="entry.hreflang"
				@update:model-value="updateEntry( index, 'hreflang', String( $event ) )"
				placeholder="e.g. en-US"
				:error="errors?.[`hreflang.${ index }.hreflang`]?.[0]"
				:disabled="entry.hreflang === 'x-default'"
				class="w-36"
			/>

			<div class="flex-1">
				<Input
					:label="index === 0 ? 'URL' : undefined"
					aria-label="URL"
					:model-value="entry.href"
					@update:model-value="updateEntry( index, 'href', String( $event ) )"
					placeholder="https://example.com/page"
					:error="errors?.[`hreflang.${ index }.href`]?.[0]"
					type="url"
				/>
			</div>

			<Button
				color="error"
				size="sm"
				@click="removeEntry( index )"
				:aria-label="`Remove entry ${ index + 1 }`"
			>
				Remove
			</Button>
		</div>

		<div class="flex gap-2">
			<Button color="primary" size="sm" @click="addEntry">
				Add Entry
			</Button>

			<Button v-if="!hasXDefault" size="sm" @click="addXDefault">
				Add x-default
			</Button>
		</div>
	</div>
</template>
