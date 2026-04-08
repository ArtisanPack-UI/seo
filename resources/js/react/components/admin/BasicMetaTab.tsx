/**
 * BasicMetaTab component.
 *
 * Tab for editing basic SEO meta fields: title, description,
 * canonical URL, and robots directives.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import React from 'react';

import { Alert, Checkbox, Input, Textarea } from '@artisanpack-ui/react';

import type { SeoMetaResponse } from '../../../types/seo-data';

const MAX_TITLE_LENGTH  = 60;
const MAX_DESC_LENGTH   = 160;

/** Props for the BasicMetaTab component. */
export interface BasicMetaTabProps {
	/** Current SEO meta data. */
	data: SeoMetaResponse;
	/** Callback when a field changes. */
	onChange: ( field: string, value: unknown ) => void;
	/** Validation errors keyed by field name. */
	errors?: Record<string, string[]>;
}

/**
 * Basic SEO meta editing tab.
 *
 * Provides fields for meta title (with character count), meta description
 * (with character count), canonical URL, focus keyword, secondary keywords,
 * and robots directives (noindex/nofollow).
 */
export function BasicMetaTab( { data, onChange, errors = {} }: BasicMetaTabProps ): React.ReactElement {
	const titleLength = data.meta_title?.length ?? 0;
	const descLength  = data.meta_description?.length ?? 0;

	const titleHint = `${ titleLength }/${ MAX_TITLE_LENGTH } characters${
		titleLength > MAX_TITLE_LENGTH ? ' (too long)' : ''
	}`;

	const descHint = `${ descLength }/${ MAX_DESC_LENGTH } characters${
		descLength > MAX_DESC_LENGTH ? ' (too long)' : ''
	}`;

	return (
		<div className="flex flex-col gap-4">
			<Input
				label="Meta Title"
				value={ data.meta_title ?? '' }
				onChange={ ( e ) => onChange( 'meta_title', e.target.value ) }
				hint={ titleHint }
				error={ errors.meta_title?.[0] }
				maxLength={ 255 }
			/>

			<Textarea
				label="Meta Description"
				value={ data.meta_description ?? '' }
				onChange={ ( e ) => onChange( 'meta_description', e.target.value ) }
				hint={ descHint }
				error={ errors.meta_description?.[0] }
				rows={ 3 }
			/>

			<Input
				label="Canonical URL"
				value={ data.canonical_url ?? '' }
				onChange={ ( e ) => onChange( 'canonical_url', e.target.value ) }
				hint="Leave blank to use the default URL"
				error={ errors.canonical_url?.[0] }
				type="url"
			/>

			<Input
				label="Focus Keyword"
				value={ data.focus_keyword ?? '' }
				onChange={ ( e ) => onChange( 'focus_keyword', e.target.value ) }
				hint="The primary keyword to optimize for"
				error={ errors.focus_keyword?.[0] }
			/>

			<Input
				label="Secondary Keywords"
				value={ data.secondary_keywords?.join( ', ' ) ?? '' }
				onChange={ ( e ) => {
					const keywords = e.target.value
						.split( ',' )
						.map( ( k ) => k.trim() )
						.filter( Boolean );
					onChange( 'secondary_keywords', keywords );
				} }
				hint="Comma-separated list of additional keywords"
				error={ errors.secondary_keywords?.[0] }
			/>

			<div className="divider">Robots Directives</div>

			<div className="flex flex-col gap-2">
				<Checkbox
					label="No Index"
					checked={ data.no_index }
					onChange={ ( e ) => onChange( 'no_index', e.target.checked ) }
					hint="Prevent search engines from indexing this page"
				/>

				<Checkbox
					label="No Follow"
					checked={ data.no_follow }
					onChange={ ( e ) => onChange( 'no_follow', e.target.checked ) }
					hint="Prevent search engines from following links on this page"
				/>
			</div>

			{ data.no_index && (
				<Alert color="warning">
					This page will not appear in search engine results.
				</Alert>
			) }
		</div>
	);
}
