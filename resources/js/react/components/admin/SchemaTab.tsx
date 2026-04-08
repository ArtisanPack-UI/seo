/**
 * SchemaTab component.
 *
 * Tab for configuring Schema.org structured data with a schema
 * type selector and dynamic form fields based on the selected type.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import React, { useCallback, useEffect, useMemo, useState } from 'react';

import { Alert, Input, Loading, Select, Textarea } from '@artisanpack-ui/react';

import type { UseApiOptions } from '../../hooks/useApi';
import { useApi } from '../../hooks/useApi';

import type { SchemaResponse, SchemaType } from '../../../types/schema';
import type { SeoMetaResponse } from '../../../types/seo-data';

/** Field definition for dynamic schema forms. */
interface SchemaFieldDef {
	key: string;
	label: string;
	type: 'text' | 'textarea' | 'url' | 'number' | 'date';
}

/** Schema type field definitions. */
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

/** Props for the SchemaTab component. */
export interface SchemaTabProps {
	/** Current SEO meta data. */
	data: SeoMetaResponse;
	/** API options for fetching schema types. */
	apiOptions: UseApiOptions;
	/** The model type. */
	modelType: string;
	/** The model ID. */
	modelId: number;
	/** Callback when schema type changes. */
	onSchemaTypeChange: ( schemaType: string | null ) => void;
	/** Callback when schema markup changes. */
	onSchemaMarkupChange: ( markup: Record<string, unknown> ) => void;
	/** Validation errors keyed by field name. */
	errors?: Record<string, string[]>;
}

/**
 * Schema.org structured data editing tab.
 *
 * Fetches available schema types from the API and renders dynamic
 * form fields based on the selected schema type.
 */
export function SchemaTab( {
	data,
	apiOptions,
	modelType,
	modelId,
	onSchemaTypeChange,
	onSchemaMarkupChange,
	errors = {},
}: SchemaTabProps ): React.ReactElement {
	const api = useApi( apiOptions );
	const [schemaData, setSchemaData] = useState<SchemaResponse | null>( null );
	const [loadingSchema, setLoadingSchema] = useState( true );
	const [schemaError, setSchemaError] = useState<string | null>( null );
	const encodedModelType = useMemo( () => encodeURIComponent( modelType ), [modelType] );

	const fetchSchema = useCallback( async (): Promise<void> => {
		setLoadingSchema( true );

		try {
			const response = await api.get<{ data: SchemaResponse }>(
				`/schema/${ encodedModelType }/${ modelId }`,
			);
			setSchemaData( response.data );
		} catch {
			setSchemaError( 'Failed to load schema data.' );
		} finally {
			setLoadingSchema( false );
		}
	}, [api, encodedModelType, modelId] );

	useEffect( () => {
		fetchSchema();
	}, [fetchSchema] );

	const selectedType = data.schema_type as SchemaType | null;
	const markup       = ( data.schema_markup ?? {} ) as Record<string, unknown>;
	const fields       = selectedType ? ( SCHEMA_FIELDS[selectedType] ?? [] ) : [];

	const availableTypes = schemaData?.available_types ?? Object.keys( SCHEMA_FIELDS ) as SchemaType[];

	const typeOptions = [
		{ value: '', label: 'None' },
		...availableTypes.map( ( type ) => ( { value: type, label: type } ) ),
	];

	const handleFieldChange = useCallback( ( key: string, value: unknown ): void => {
		const updated = { ...markup, [key]: value };
		onSchemaMarkupChange( updated );
	}, [markup, onSchemaMarkupChange] );

	if ( loadingSchema ) {
		return <Loading />;
	}

	if ( schemaError ) {
		return <Alert color="error">{ schemaError }</Alert>;
	}

	return (
		<div className="flex flex-col gap-4">
			<Select
				label="Schema Type"
				value={ selectedType ?? '' }
				onChange={ ( e ) => {
					const value = e.target.value || null;
					onSchemaTypeChange( value );

					if ( !value ) {
						onSchemaMarkupChange( {} );
					}
				} }
				options={ typeOptions }
				optionValue="value"
				optionLabel="label"
				error={ errors.schema_type?.[0] }
			/>

			{ selectedType && 0 === fields.length && (
				<Alert color="info">
					This schema type uses automatic data. No additional fields needed.
				</Alert>
			) }

			{ selectedType && fields.map( ( field ) => {
				const value = ( markup[field.key] as string | number ) ?? '';

				if ( 'textarea' === field.type ) {
					return (
						<Textarea
							key={ field.key }
							label={ field.label }
							value={ String( value ) }
							onChange={ ( e ) => handleFieldChange( field.key, e.target.value ) }
							error={ errors[`schema_markup.${ field.key }`]?.[0] }
							rows={ 3 }
						/>
					);
				}

				return (
					<Input
						key={ field.key }
						label={ field.label }
						value={ String( value ) }
						onChange={ ( e ) => {
							const newValue = 'number' === field.type
								? ( e.target.value ? Number( e.target.value ) : '' )
								: e.target.value;
							handleFieldChange( field.key, newValue );
						} }
						type={ field.type }
						error={ errors[`schema_markup.${ field.key }`]?.[0] }
					/>
				);
			} ) }

			{ schemaData?.generated && (
				<div className="mt-2">
					<label className="label">
						<span className="label-text font-semibold">Generated JSON-LD Preview</span>
					</label>
					<pre className="bg-base-200 rounded-lg p-4 text-xs overflow-x-auto max-h-64">
						{ JSON.stringify( schemaData.generated, null, 2 ) }
					</pre>
				</div>
			) }
		</div>
	);
}
