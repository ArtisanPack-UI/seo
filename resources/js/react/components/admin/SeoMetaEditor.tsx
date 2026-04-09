/**
 * SeoMetaEditor component.
 *
 * Main tabbed editor for all SEO metadata including basic meta,
 * Open Graph, Twitter Card, Schema.org, hreflang, and sitemap settings.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import React, { useCallback, useRef, useState } from 'react';

import { Alert, Button, Loading, Tabs } from '@artisanpack-ui/react';

import type { UseApiOptions } from '../../hooks/useApi';
import { useSeoMeta } from '../../hooks/useSeoMeta';

import type { SeoMetaResponse } from '../../../types/seo-data';
import type { HreflangEntry } from '../../../types/hreflang';

import { BasicMetaTab } from './BasicMetaTab';
import { HreflangTab } from './HreflangTab';
import { OpenGraphTab } from './OpenGraphTab';
import { SchemaTab } from './SchemaTab';
import { SitemapTab } from './SitemapTab';
import { TwitterCardTab } from './TwitterCardTab';

/** Props for the SeoMetaEditor component. */
export interface SeoMetaEditorProps extends UseApiOptions {
	/** The model type (e.g. "post", "page"). */
	modelType: string;
	/** The model ID. */
	modelId: number;
	/** Optional initial data to avoid an initial fetch. */
	initialData?: SeoMetaResponse;
	/** Callback after successful save. */
	onSave?: ( data: SeoMetaResponse ) => void;
	/** Additional CSS class name. */
	className?: string;
}

/**
 * Full SEO meta editor with tabbed interface.
 *
 * Wraps all tab sub-components (Basic, Open Graph, Twitter Card,
 * Schema, Hreflang, Sitemap) and manages save/load lifecycle.
 *
 * @example
 * ```tsx
 * <SeoMetaEditor
 *     baseUrl="/api/seo"
 *     modelType="post"
 *     modelId={1}
 *     onSave={(data) => console.log('Saved:', data)}
 * />
 * ```
 */
export function SeoMetaEditor( {
	modelType,
	modelId,
	initialData,
	onSave,
	className,
	...apiOptions
}: SeoMetaEditorProps ): React.ReactElement {
	const {
		data,
		loading,
		saving,
		error,
		validationErrors,
		updateMeta,
	} = useSeoMeta( { ...apiOptions, modelType, modelId, initialData } );

	const [pendingChanges, setPendingChanges] = useState<Record<string, unknown>>( {} );
	const [dirty, setDirty] = useState( false );
	const pendingRef = useRef( pendingChanges );
	pendingRef.current = pendingChanges;

	const handleFieldChange = useCallback( ( field: string, value: unknown ): void => {
		setPendingChanges( ( prev ) => ( { ...prev, [field]: value } ) );
		setDirty( true );
	}, [] );

	const handleHreflangChange = useCallback( ( entries: HreflangEntry[] ): void => {
		setPendingChanges( ( prev ) => ( { ...prev, hreflang: entries } ) );
		setDirty( true );
	}, [] );

	const handleSchemaTypeChange = useCallback( ( schemaType: string | null ): void => {
		setPendingChanges( ( prev ) => ( { ...prev, schema_type: schemaType } ) );
		setDirty( true );
	}, [] );

	const handleSchemaMarkupChange = useCallback( ( markup: Record<string, unknown> ): void => {
		setPendingChanges( ( prev ) => ( { ...prev, schema_markup: markup } ) );
		setDirty( true );
	}, [] );

	const handleSave = useCallback( async (): Promise<void> => {
		const result = await updateMeta( pendingRef.current );

		if ( result ) {
			setPendingChanges( {} );
			setDirty( false );
			onSave?.( result );
		}
	}, [updateMeta, onSave] );

	if ( loading ) {
		return <Loading />;
	}

	if ( !data ) {
		return (
			<Alert color="error">
				{ error ?? 'Failed to load SEO data.' }
			</Alert>
		);
	}

	// Merge pending changes with current data for display
	const displayData: SeoMetaResponse = { ...data, ...pendingChanges } as SeoMetaResponse;

	const tabs = [
		{
			name: 'basic',
			label: 'Basic SEO',
			content: (
				<BasicMetaTab
					data={ displayData }
					onChange={ handleFieldChange }
					errors={ validationErrors }
				/>
			),
		},
		{
			name: 'opengraph',
			label: 'Open Graph',
			content: (
				<OpenGraphTab
					data={ displayData }
					onChange={ handleFieldChange }
					errors={ validationErrors }
				/>
			),
		},
		{
			name: 'twitter',
			label: 'Twitter Card',
			content: (
				<TwitterCardTab
					data={ displayData }
					onChange={ handleFieldChange }
					errors={ validationErrors }
				/>
			),
		},
		{
			name: 'schema',
			label: 'Schema',
			content: (
				<SchemaTab
					data={ displayData }
					apiOptions={ apiOptions }
					modelType={ modelType }
					modelId={ modelId }
					onSchemaTypeChange={ handleSchemaTypeChange }
					onSchemaMarkupChange={ handleSchemaMarkupChange }
					errors={ validationErrors }
				/>
			),
		},
		{
			name: 'hreflang',
			label: 'Hreflang',
			content: (
				<HreflangTab
					data={ displayData }
					onChange={ handleHreflangChange }
					errors={ validationErrors }
				/>
			),
		},
		{
			name: 'sitemap',
			label: 'Sitemap',
			content: (
				<SitemapTab
					data={ displayData }
					onChange={ handleFieldChange }
					errors={ validationErrors }
				/>
			),
		},
	];

	return (
		<div className={ className }>
			{ error && (
				<Alert color="error" className="mb-4">
					{ error }
				</Alert>
			) }

			<Tabs tabs={ tabs } variant="bordered" />

			<div className="mt-4 flex items-center gap-2">
				<Button
					color="primary"
					onClick={ handleSave }
					disabled={ saving || !dirty }
				>
					{ saving ? 'Saving...' : 'Save SEO Settings' }
				</Button>

				{ dirty && (
					<span className="text-sm text-warning">Unsaved changes</span>
				) }
			</div>
		</div>
	);
}
