/**
 * TwitterCardTab component.
 *
 * Tab for editing Twitter Card meta fields including card type,
 * title, description, image, site handle, and creator handle.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import React from 'react';

import { Input, Select, Textarea } from '@artisanpack-ui/react';

import type { SeoMetaResponse } from '../../../types/seo-data';

/** Available Twitter Card type options. */
const CARD_TYPE_OPTIONS = [
	{ value: 'summary', label: 'Summary' },
	{ value: 'summary_large_image', label: 'Summary with Large Image' },
	{ value: 'app', label: 'App' },
	{ value: 'player', label: 'Player' },
];

/** Props for the TwitterCardTab component. */
export interface TwitterCardTabProps {
	/** Current SEO meta data. */
	data: SeoMetaResponse;
	/** Callback when a field changes. */
	onChange: ( field: string, value: unknown ) => void;
	/** Validation errors keyed by field name. */
	errors?: Record<string, string[]>;
}

/**
 * Twitter Card meta editing tab.
 *
 * Provides fields for card type selector (4 types), title,
 * description, image URL, site handle, and creator handle.
 */
export function TwitterCardTab( { data, onChange, errors = {} }: TwitterCardTabProps ): React.ReactElement {
	return (
		<div className="flex flex-col gap-4">
			<Select
				label="Card Type"
				value={ data.twitter_card ?? 'summary' }
				onChange={ ( e ) => onChange( 'twitter_card', e.target.value ) }
				options={ CARD_TYPE_OPTIONS }
				optionValue="value"
				optionLabel="label"
				error={ errors.twitter_card?.[0] }
			/>

			<Input
				label="Twitter Title"
				value={ data.twitter_title ?? '' }
				onChange={ ( e ) => onChange( 'twitter_title', e.target.value ) }
				hint="Leave blank to use the meta title"
				error={ errors.twitter_title?.[0] }
			/>

			<Textarea
				label="Twitter Description"
				value={ data.twitter_description ?? '' }
				onChange={ ( e ) => onChange( 'twitter_description', e.target.value ) }
				hint="Leave blank to use the meta description"
				error={ errors.twitter_description?.[0] }
				rows={ 3 }
			/>

			<Input
				label="Twitter Image URL"
				value={ data.twitter_image ?? '' }
				onChange={ ( e ) => onChange( 'twitter_image', e.target.value ) }
				hint="Minimum size: 120x120 for Summary, 300x157 for Large Image"
				error={ errors.twitter_image?.[0] }
				type="url"
			/>

			<Input
				label="Site Handle"
				value={ data.twitter_site ?? '' }
				onChange={ ( e ) => onChange( 'twitter_site', e.target.value ) }
				hint="The @username of the website (e.g. @example)"
				error={ errors.twitter_site?.[0] }
			/>

			<Input
				label="Creator Handle"
				value={ data.twitter_creator ?? '' }
				onChange={ ( e ) => onChange( 'twitter_creator', e.target.value ) }
				hint="The @username of the content creator"
				error={ errors.twitter_creator?.[0] }
			/>
		</div>
	);
}
