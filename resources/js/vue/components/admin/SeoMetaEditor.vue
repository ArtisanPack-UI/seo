<!--
  SeoMetaEditor component.

  Main tabbed editor for all SEO metadata including basic meta,
  Open Graph, Twitter Card, Schema.org, hreflang, and sitemap settings.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.1.0
-->

<script setup lang="ts">
import { computed, reactive, ref } from 'vue';

import { Alert, Button, Loading, Tabs } from '@artisanpack-ui/vue';

import type { UseApiOptions } from '../../composables/useApi';
import { useSeoMeta } from '../../composables/useSeoMeta';

import type { SeoMetaResponse } from '../../../types/seo-data';
import type { HreflangEntry } from '../../../types/hreflang';

import BasicMetaTab from './BasicMetaTab.vue';
import HreflangTab from './HreflangTab.vue';
import OpenGraphTab from './OpenGraphTab.vue';
import SchemaTab from './SchemaTab.vue';
import SitemapTab from './SitemapTab.vue';
import TwitterCardTab from './TwitterCardTab.vue';

const props = withDefaults( defineProps<UseApiOptions & {
	modelType: string;
	modelId: number;
	initialData?: SeoMetaResponse;
	className?: string;
}>(), {
	csrfToken: undefined,
	authorization: undefined,
	credentials: undefined,
	initialData: undefined,
	className: undefined,
} );

const emit = defineEmits<{
	'save': [data: SeoMetaResponse];
}>();

const {
	data,
	loading,
	saving,
	error,
	validationErrors,
	updateMeta,
} = useSeoMeta( {
	baseUrl: props.baseUrl,
	csrfToken: props.csrfToken,
	authorization: props.authorization,
	credentials: props.credentials,
	modelType: props.modelType,
	modelId: props.modelId,
	initialData: props.initialData,
} );

const pendingChanges = reactive<Record<string, unknown>>( {} );
const dirty          = ref( false );

const apiOptions = computed<UseApiOptions>( () => ( {
	baseUrl: props.baseUrl,
	csrfToken: props.csrfToken,
	authorization: props.authorization,
	credentials: props.credentials,
} ) );

const displayData = computed<SeoMetaResponse | null>( () => {
	if ( !data.value ) {
		return null;
	}

	return { ...data.value, ...pendingChanges } as SeoMetaResponse;
} );

function handleFieldChange( field: string, value: unknown ): void {
	pendingChanges[field] = value;
	dirty.value           = true;
}

function handleHreflangChange( entries: HreflangEntry[] ): void {
	pendingChanges.hreflang = entries;
	dirty.value             = true;
}

function handleSchemaTypeChange( schemaType: string | null ): void {
	pendingChanges.schema_type = schemaType;
	dirty.value                = true;
}

function handleSchemaMarkupChange( markup: Record<string, unknown> ): void {
	pendingChanges.schema_markup = markup;
	dirty.value                  = true;
}

async function handleSave(): Promise<void> {
	const result = await updateMeta( { ...pendingChanges } );

	if ( result ) {
		Object.keys( pendingChanges ).forEach( ( key ) => delete pendingChanges[key] );
		dirty.value = false;
		emit( 'save', result );
	}
}

const tabs = [
	{ name: 'basic', label: 'Basic SEO' },
	{ name: 'opengraph', label: 'Open Graph' },
	{ name: 'twitter', label: 'Twitter Card' },
	{ name: 'schema', label: 'Schema' },
	{ name: 'hreflang', label: 'Hreflang' },
	{ name: 'sitemap', label: 'Sitemap' },
];
</script>

<template>
	<div :class="className">
		<Loading v-if="loading" />

		<Alert v-else-if="!data" color="error">
			{{ error ?? 'Failed to load SEO data.' }}
		</Alert>

		<template v-else-if="displayData">
			<Alert v-if="error" color="error" class="mb-4">
				{{ error }}
			</Alert>

			<Tabs :tabs="tabs" variant="bordered">
				<template #basic>
					<BasicMetaTab
						:data="displayData"
						:errors="validationErrors"
						@change="handleFieldChange"
					/>
				</template>

				<template #opengraph>
					<OpenGraphTab
						:data="displayData"
						:errors="validationErrors"
						@change="handleFieldChange"
					/>
				</template>

				<template #twitter>
					<TwitterCardTab
						:data="displayData"
						:errors="validationErrors"
						@change="handleFieldChange"
					/>
				</template>

				<template #schema>
					<SchemaTab
						:data="displayData"
						:api-options="apiOptions"
						:model-type="modelType"
						:model-id="modelId"
						:errors="validationErrors"
						@schema-type-change="handleSchemaTypeChange"
						@schema-markup-change="handleSchemaMarkupChange"
					/>
				</template>

				<template #hreflang>
					<HreflangTab
						:data="displayData"
						:errors="validationErrors"
						@change="handleHreflangChange"
					/>
				</template>

				<template #sitemap>
					<SitemapTab
						:data="displayData"
						:errors="validationErrors"
						@change="handleFieldChange"
					/>
				</template>
			</Tabs>

			<div class="mt-4 flex items-center gap-2">
				<Button
					color="primary"
					@click="handleSave"
					:disabled="saving || !dirty"
				>
					{{ saving ? 'Saving...' : 'Save SEO Settings' }}
				</Button>

				<span v-if="dirty" class="text-sm text-warning">Unsaved changes</span>
			</div>
		</template>
	</div>
</template>
