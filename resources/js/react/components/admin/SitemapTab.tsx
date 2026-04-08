/**
 * SitemapTab component.
 *
 * Tab for configuring sitemap inclusion settings including
 * toggle, change frequency, and priority.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import React from 'react';

import { Checkbox, Input, Select } from '@artisanpack-ui/react';

import type { SeoMetaResponse } from '../../../types/seo-data';

/** Available sitemap change frequency options. */
const CHANGEFREQ_OPTIONS = [
	{ value: '', label: 'Default' },
	{ value: 'always', label: 'Always' },
	{ value: 'hourly', label: 'Hourly' },
	{ value: 'daily', label: 'Daily' },
	{ value: 'weekly', label: 'Weekly' },
	{ value: 'monthly', label: 'Monthly' },
	{ value: 'yearly', label: 'Yearly' },
	{ value: 'never', label: 'Never' },
];

/** Props for the SitemapTab component. */
export interface SitemapTabProps {
	/** Current SEO meta data. */
	data: SeoMetaResponse;
	/** Callback when a field changes. */
	onChange: ( field: string, value: unknown ) => void;
	/** Validation errors keyed by field name. */
	errors?: Record<string, string[]>;
}

/**
 * Sitemap settings editing tab.
 *
 * Provides controls for including/excluding from sitemap,
 * change frequency, and priority.
 */
export function SitemapTab( { data, onChange, errors = {} }: SitemapTabProps ): React.ReactElement {
	return (
		<div className="flex flex-col gap-4">
			<Checkbox
				label="Exclude from Sitemap"
				checked={ data.exclude_from_sitemap }
				onChange={ ( e ) => onChange( 'exclude_from_sitemap', e.target.checked ) }
				hint="When checked, this page will not appear in the XML sitemap"
			/>

			{ !data.exclude_from_sitemap && (
				<>
					<Select
						label="Change Frequency"
						value={ data.sitemap_changefreq ?? '' }
						onChange={ ( e ) => onChange( 'sitemap_changefreq', e.target.value || null ) }
						options={ CHANGEFREQ_OPTIONS }
						optionValue="value"
						optionLabel="label"
						hint="How often this page is likely to change"
						error={ errors.sitemap_changefreq?.[0] }
					/>

					<Input
						label="Priority"
						value={ data.sitemap_priority ?? '' }
						onChange={ ( e ) => {
							const value = e.target.value;
							onChange( 'sitemap_priority', value ? parseFloat( value ) : null );
						} }
						type="number"
						min={ 0 }
						max={ 1 }
						step={ 0.1 }
						hint="Value between 0.0 and 1.0 (default: 0.5)"
						error={ errors.sitemap_priority?.[0] }
					/>
				</>
			) }
		</div>
	);
}
