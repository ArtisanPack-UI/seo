/**
 * OpenGraphTab component.
 *
 * Tab for editing Open Graph meta fields including type, title,
 * description, image, URL, site name, and locale.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import React from 'react';

import { Input, Select, Textarea } from '@artisanpack-ui/react';

import type { SeoMetaResponse } from '../../../types/seo-data';

/** Available Open Graph type options. */
const OG_TYPE_OPTIONS = [
	{ value: 'website', label: 'Website' },
	{ value: 'article', label: 'Article' },
	{ value: 'book', label: 'Book' },
	{ value: 'profile', label: 'Profile' },
	{ value: 'music.song', label: 'Music - Song' },
	{ value: 'music.album', label: 'Music - Album' },
	{ value: 'music.playlist', label: 'Music - Playlist' },
	{ value: 'music.radio_station', label: 'Music - Radio Station' },
	{ value: 'video.movie', label: 'Video - Movie' },
	{ value: 'video.episode', label: 'Video - Episode' },
	{ value: 'video.tv_show', label: 'Video - TV Show' },
	{ value: 'video.other', label: 'Video - Other' },
];

/** Props for the OpenGraphTab component. */
export interface OpenGraphTabProps {
	/** Current SEO meta data. */
	data: SeoMetaResponse;
	/** Callback when a field changes. */
	onChange: ( field: string, value: unknown ) => void;
	/** Validation errors keyed by field name. */
	errors?: Record<string, string[]>;
}

/**
 * Open Graph meta editing tab.
 *
 * Provides fields for OG type selector (12 types), title, description,
 * image URL, URL, site name, and locale.
 */
export function OpenGraphTab( { data, onChange, errors = {} }: OpenGraphTabProps ): React.ReactElement {
	return (
		<div className="flex flex-col gap-4">
			<Select
				label="OG Type"
				value={ data.og_type ?? 'website' }
				onChange={ ( e ) => onChange( 'og_type', e.target.value ) }
				options={ OG_TYPE_OPTIONS }
				optionValue="value"
				optionLabel="label"
				error={ errors.og_type?.[0] }
			/>

			<Input
				label="OG Title"
				value={ data.og_title ?? '' }
				onChange={ ( e ) => onChange( 'og_title', e.target.value ) }
				hint="Leave blank to use the meta title"
				error={ errors.og_title?.[0] }
			/>

			<Textarea
				label="OG Description"
				value={ data.og_description ?? '' }
				onChange={ ( e ) => onChange( 'og_description', e.target.value ) }
				hint="Leave blank to use the meta description"
				error={ errors.og_description?.[0] }
				rows={ 3 }
			/>

			<Input
				label="OG Image URL"
				value={ data.og_image ?? '' }
				onChange={ ( e ) => onChange( 'og_image', e.target.value ) }
				hint="Recommended size: 1200x630 pixels"
				error={ errors.og_image?.[0] }
				type="url"
			/>

			<Input
				label="OG URL"
				value={ data.canonical_url ?? '' }
				onChange={ ( e ) => onChange( 'canonical_url', e.target.value ) }
				hint="The canonical URL for this content"
				error={ errors.canonical_url?.[0] }
				type="url"
			/>

			<Input
				label="Site Name"
				value={ data.og_site_name ?? '' }
				onChange={ ( e ) => onChange( 'og_site_name', e.target.value ) }
				hint="Leave blank to use the site default"
				error={ errors.og_site_name?.[0] }
			/>

			<Input
				label="Locale"
				value={ data.og_locale ?? '' }
				onChange={ ( e ) => onChange( 'og_locale', e.target.value ) }
				hint="e.g. en_US, fr_FR"
				error={ errors.og_locale?.[0] }
			/>
		</div>
	);
}
