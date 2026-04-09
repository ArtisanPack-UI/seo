/**
 * HreflangTab component.
 *
 * Tab for editing hreflang language/region URL mappings
 * with add/remove rows and x-default support.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import React, { useCallback } from 'react';

import { Alert, Button, Input } from '@artisanpack-ui/react';

import type { HreflangEntry } from '../../../types/hreflang';
import type { SeoMetaResponse } from '../../../types/seo-data';

/** Props for the HreflangTab component. */
export interface HreflangTabProps {
	/** Current SEO meta data. */
	data: SeoMetaResponse;
	/** Callback when hreflang entries change. */
	onChange: ( entries: HreflangEntry[] ) => void;
	/** Validation errors keyed by field name. */
	errors?: Record<string, string[]>;
}

/**
 * Hreflang entry editing tab.
 *
 * Provides a multi-row editor for hreflang entries with language code
 * and URL fields. Supports x-default and add/remove functionality.
 */
export function HreflangTab( { data, onChange, errors = {} }: HreflangTabProps ): React.ReactElement {
	const entries: HreflangEntry[] = data.hreflang ?? [];

	const addEntry = useCallback( (): void => {
		onChange( [...entries, { hreflang: '', href: '' }] );
	}, [entries, onChange] );

	const addXDefault = useCallback( (): void => {
		const hasXDefault = entries.some( ( e ) => 'x-default' === e.hreflang );

		if ( !hasXDefault ) {
			onChange( [...entries, { hreflang: 'x-default', href: '' }] );
		}
	}, [entries, onChange] );

	const updateEntry = useCallback( ( index: number, field: keyof HreflangEntry, value: string ): void => {
		const updated = entries.map( ( entry, i ) => {
			if ( i === index ) {
				return { ...entry, [field]: value };
			}

			return entry;
		} );
		onChange( updated );
	}, [entries, onChange] );

	const removeEntry = useCallback( ( index: number ): void => {
		onChange( entries.filter( ( _, i ) => i !== index ) );
	}, [entries, onChange] );

	const hasXDefault = entries.some( ( e ) => 'x-default' === e.hreflang );

	return (
		<div className="flex flex-col gap-4">
			<Alert color="info">
				Hreflang tags tell search engines about alternate language versions of your content.
				Use ISO 639-1 language codes (e.g. &quot;en&quot;, &quot;fr&quot;, &quot;de&quot;)
				optionally combined with ISO 3166-1 country codes (e.g. &quot;en-US&quot;, &quot;fr-CA&quot;).
			</Alert>

			{ 0 === entries.length && (
				<p className="text-base-content/60 text-sm">
					No hreflang entries. Click &quot;Add Entry&quot; to add alternate language URLs.
				</p>
			) }

			{ entries.map( ( entry, index ) => (
				<div key={ index } className="flex gap-2 items-end">
					<Input
						label={ 0 === index ? 'Language Code' : undefined }
						aria-label="Language Code"
						value={ entry.hreflang }
						onChange={ ( e ) => updateEntry( index, 'hreflang', e.target.value ) }
						placeholder="e.g. en-US"
						error={ errors[`hreflang.${ index }.hreflang`]?.[0] }
						disabled={ 'x-default' === entry.hreflang }
						className="w-36"
					/>

					<div className="flex-1">
						<Input
							label={ 0 === index ? 'URL' : undefined }
							aria-label="URL"
							value={ entry.href }
							onChange={ ( e ) => updateEntry( index, 'href', e.target.value ) }
							placeholder="https://example.com/page"
							error={ errors[`hreflang.${ index }.href`]?.[0] }
							type="url"
						/>
					</div>

					<Button
						color="error"
						size="sm"
						onClick={ () => removeEntry( index ) }
						aria-label={ `Remove entry ${ index + 1 }` }
					>
						Remove
					</Button>
				</div>
			) ) }

			<div className="flex gap-2">
				<Button
					color="primary"
					size="sm"
					onClick={ addEntry }
				>
					Add Entry
				</Button>

				{ !hasXDefault && (
					<Button
						size="sm"
						onClick={ addXDefault }
					>
						Add x-default
					</Button>
				) }
			</div>
		</div>
	);
}
