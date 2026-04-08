<!--
  SchemaTab component.

  Tab for configuring Schema.org structured data with a schema
  type selector and dynamic form fields based on the selected type.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.1.0
-->

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

import { Alert, Input, Loading, Select, Textarea } from '@artisanpack-ui/vue';

import type { UseApiOptions } from '../../composables/useApi';
import { useApi } from '../../composables/useApi';

import type { SchemaResponse, SchemaType } from '../../../types/schema';
import type { SeoMetaResponse } from '../../../types/seo-data';

interface SchemaFieldDef {
	key: string;
	label: string;
	type: 'text' | 'textarea' | 'url' | 'number' | 'date';
}

const SCHEMA_FIELDS: Record<string, SchemaFieldDef[]> = {
	Article: [
		{ key: 'headline', label: 'Headline', type: 'text' },
		{ key: 'description', label: 'Description', type: 'textarea' },
		{ key: 'url', label: 'URL', type: 'url' },
		{ key: 'image', label: 'Image URL', type: 'url' },
		{ key: 'datePublished', label: 'Date Published', type: 'date' },
		{ key: 'dateModified', label: 'Date Modified', type: 'date' },
		{ key: 'keywords', label: 'Keywords', type: 'text' },
		{ key: 'articleSection', label: 'Article Section', type: 'text' },
		{ key: 'wordCount', label: 'Word Count', type: 'number' },
		{ key: 'inLanguage', label: 'Language', type: 'text' },
	],
	BlogPosting: [
		{ key: 'headline', label: 'Headline', type: 'text' },
		{ key: 'description', label: 'Description', type: 'textarea' },
		{ key: 'url', label: 'URL', type: 'url' },
		{ key: 'image', label: 'Image URL', type: 'url' },
		{ key: 'datePublished', label: 'Date Published', type: 'date' },
		{ key: 'dateModified', label: 'Date Modified', type: 'date' },
		{ key: 'keywords', label: 'Keywords', type: 'text' },
		{ key: 'wordCount', label: 'Word Count', type: 'number' },
	],
	Product: [
		{ key: 'name', label: 'Name', type: 'text' },
		{ key: 'description', label: 'Description', type: 'textarea' },
		{ key: 'image', label: 'Image URL', type: 'url' },
		{ key: 'url', label: 'URL', type: 'url' },
		{ key: 'sku', label: 'SKU', type: 'text' },
		{ key: 'gtin', label: 'GTIN', type: 'text' },
		{ key: 'mpn', label: 'MPN', type: 'text' },
		{ key: 'brand', label: 'Brand', type: 'text' },
		{ key: 'category', label: 'Category', type: 'text' },
		{ key: 'color', label: 'Color', type: 'text' },
		{ key: 'material', label: 'Material', type: 'text' },
	],
	Event: [
		{ key: 'name', label: 'Event Name', type: 'text' },
		{ key: 'description', label: 'Description', type: 'textarea' },
		{ key: 'url', label: 'URL', type: 'url' },
		{ key: 'image', label: 'Image URL', type: 'url' },
		{ key: 'startDate', label: 'Start Date', type: 'date' },
		{ key: 'endDate', label: 'End Date', type: 'date' },
		{ key: 'virtualLocation', label: 'Virtual Location URL', type: 'url' },
		{ key: 'eventStatus', label: 'Event Status', type: 'text' },
		{ key: 'eventAttendanceMode', label: 'Attendance Mode', type: 'text' },
	],
	Organization: [
		{ key: 'name', label: 'Name', type: 'text' },
		{ key: 'url', label: 'URL', type: 'url' },
		{ key: 'logo', label: 'Logo URL', type: 'url' },
		{ key: 'email', label: 'Email', type: 'text' },
		{ key: 'phone', label: 'Phone', type: 'text' },
		{ key: 'description', label: 'Description', type: 'textarea' },
	],
	LocalBusiness: [
		{ key: 'name', label: 'Business Name', type: 'text' },
		{ key: 'url', label: 'URL', type: 'url' },
		{ key: 'logo', label: 'Logo URL', type: 'url' },
		{ key: 'email', label: 'Email', type: 'text' },
		{ key: 'phone', label: 'Phone', type: 'text' },
		{ key: 'description', label: 'Description', type: 'textarea' },
		{ key: 'priceRange', label: 'Price Range', type: 'text' },
		{ key: 'areaServed', label: 'Area Served', type: 'text' },
		{ key: 'paymentAccepted', label: 'Payment Accepted', type: 'text' },
		{ key: 'currenciesAccepted', label: 'Currencies Accepted', type: 'text' },
	],
	WebSite: [
		{ key: 'name', label: 'Site Name', type: 'text' },
		{ key: 'url', label: 'URL', type: 'url' },
		{ key: 'description', label: 'Description', type: 'textarea' },
		{ key: 'searchUrl', label: 'Search URL', type: 'url' },
		{ key: 'alternateName', label: 'Alternate Name', type: 'text' },
		{ key: 'inLanguage', label: 'Language', type: 'text' },
	],
	WebPage: [
		{ key: 'name', label: 'Page Name', type: 'text' },
		{ key: 'url', label: 'URL', type: 'url' },
		{ key: 'description', label: 'Description', type: 'textarea' },
		{ key: 'datePublished', label: 'Date Published', type: 'date' },
		{ key: 'dateModified', label: 'Date Modified', type: 'date' },
		{ key: 'image', label: 'Image URL', type: 'url' },
		{ key: 'isPartOf', label: 'Is Part Of', type: 'url' },
		{ key: 'inLanguage', label: 'Language', type: 'text' },
	],
	Service: [
		{ key: 'name', label: 'Service Name', type: 'text' },
		{ key: 'description', label: 'Description', type: 'textarea' },
		{ key: 'url', label: 'URL', type: 'url' },
		{ key: 'image', label: 'Image URL', type: 'url' },
		{ key: 'serviceType', label: 'Service Type', type: 'text' },
		{ key: 'category', label: 'Category', type: 'text' },
		{ key: 'areaServed', label: 'Area Served', type: 'text' },
		{ key: 'brand', label: 'Brand', type: 'text' },
	],
	FAQPage: [
		{ key: 'name', label: 'Page Name', type: 'text' },
		{ key: 'description', label: 'Description', type: 'textarea' },
		{ key: 'url', label: 'URL', type: 'url' },
	],
	Review: [
		{ key: 'name', label: 'Review Title', type: 'text' },
		{ key: 'reviewBody', label: 'Review Body', type: 'textarea' },
		{ key: 'datePublished', label: 'Date Published', type: 'date' },
	],
	AggregateRating: [
		{ key: 'ratingValue', label: 'Rating Value', type: 'number' },
		{ key: 'bestRating', label: 'Best Rating', type: 'number' },
		{ key: 'worstRating', label: 'Worst Rating', type: 'number' },
		{ key: 'ratingCount', label: 'Rating Count', type: 'number' },
		{ key: 'reviewCount', label: 'Review Count', type: 'number' },
	],
	BreadcrumbList: [],
};

const props = defineProps<{
	data: SeoMetaResponse;
	apiOptions: UseApiOptions;
	modelType: string;
	modelId: number;
	errors?: Record<string, string[]>;
}>();

const emit = defineEmits<{
	'schema-type-change': [schemaType: string | null];
	'schema-markup-change': [markup: Record<string, unknown>];
}>();

const api              = useApi( props.apiOptions );
const encodedModelType = encodeURIComponent( props.modelType );
const schemaData       = ref<SchemaResponse | null>( null );
const loadingSchema    = ref( true );
const schemaError      = ref<string | null>( null );
let requestId          = 0;

const selectedType  = computed( () => ( props.data.schema_type as SchemaType | null ) );
const markup        = computed( () => ( props.data.schema_markup ?? {} ) as Record<string, unknown> );
const fields        = computed( () => selectedType.value ? ( SCHEMA_FIELDS[selectedType.value] ?? [] ) : [] );
const availableTypes = computed( () => schemaData.value?.available_types ?? Object.keys( SCHEMA_FIELDS ) as SchemaType[] );

const typeOptions = computed( () => [
	{ value: '', name: 'None' },
	...availableTypes.value.map( ( type ) => ( { value: type, name: type } ) ),
] );

async function fetchSchema(): Promise<void> {
	requestId += 1;
	const currentRequestId = requestId;

	loadingSchema.value = true;
	schemaError.value   = null;

	try {
		const response = await api.get<{ data: SchemaResponse }>(
			`/schema/${ encodedModelType }/${ props.modelId }`,
		);

		if ( currentRequestId === requestId ) {
			schemaData.value = response.data;
		}
	} catch {
		if ( currentRequestId === requestId ) {
			schemaError.value = 'Failed to load schema data.';
		}
	} finally {
		if ( currentRequestId === requestId ) {
			loadingSchema.value = false;
		}
	}
}

function handleTypeChange( value: string ): void {
	const type = value || null;
	emit( 'schema-type-change', type );
	emit( 'schema-markup-change', {} );
}

function handleFieldChange( key: string, value: unknown ): void {
	const updated = { ...markup.value, [key]: value };
	emit( 'schema-markup-change', updated );
}

function handleNumberInput( key: string, rawValue: string ): void {
	if ( '' === rawValue ) {
		handleFieldChange( key, '' );

		return;
	}

	const isComplete = /^-?\d+(\.\d+)?$/.test( rawValue );

	if ( isComplete ) {
		const parsed = Number( rawValue );
		handleFieldChange( key, Number.isFinite( parsed ) ? parsed : rawValue );
	} else {
		handleFieldChange( key, rawValue );
	}
}

onMounted( fetchSchema );
</script>

<template>
	<Loading v-if="loadingSchema" />

	<Alert v-else-if="schemaError" color="error">{{ schemaError }}</Alert>

	<div v-else class="flex flex-col gap-4">
		<Select
			label="Schema Type"
			:model-value="selectedType ?? ''"
			@update:model-value="handleTypeChange"
			:options="typeOptions"
			option-value="value"
			option-label="name"
			:error="errors?.schema_type?.[0]"
		/>

		<Alert v-if="selectedType && fields.length === 0" color="info">
			This schema type uses automatic data. No additional fields needed.
		</Alert>

		<template v-if="selectedType">
			<template v-for="field in fields" :key="field.key">
				<Textarea
					v-if="field.type === 'textarea'"
					:label="field.label"
					:model-value="String( ( markup[field.key] as string | number ) ?? '' )"
					@update:model-value="handleFieldChange( field.key, $event )"
					:error="errors?.[`schema_markup.${ field.key }`]?.[0]"
					:rows="3"
				/>

				<Input
					v-else
					:label="field.label"
					:model-value="String( ( markup[field.key] as string | number ) ?? '' )"
					@update:model-value="field.type === 'number' ? handleNumberInput( field.key, String( $event ) ) : handleFieldChange( field.key, $event )"
					:type="field.type"
					:error="errors?.[`schema_markup.${ field.key }`]?.[0]"
				/>
			</template>
		</template>

		<div v-if="schemaData?.generated" class="mt-2">
			<label class="label">
				<span class="label-text font-semibold">Generated JSON-LD Preview</span>
			</label>
			<pre class="bg-base-200 rounded-lg p-4 text-xs overflow-x-auto max-h-64">{{ JSON.stringify( schemaData.generated, null, 2 ) }}</pre>
		</div>
	</div>
</template>
