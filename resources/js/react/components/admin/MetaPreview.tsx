/**
 * MetaPreview component.
 *
 * Displays a live Google search result snippet preview showing
 * how the page will appear in search engine results.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import React, { useMemo } from 'react';

import { Card } from '@artisanpack-ui/react';

import type { SeoMetaResponse } from '../../../types/seo-data';

const MAX_TITLE_LENGTH = 60;
const MAX_DESC_LENGTH  = 160;

/** Props for the MetaPreview component. */
export interface MetaPreviewProps {
	/** Current SEO meta data. */
	data: SeoMetaResponse;
	/** The default URL to show if no canonical is set. */
	defaultUrl?: string;
	/** Additional CSS class name. */
	className?: string;
}

/**
 * Google search result snippet preview.
 *
 * Renders a realistic SERP preview with truncated title, URL breadcrumb,
 * and truncated description matching Google's display rules.
 */
export function MetaPreview( { data, defaultUrl = '', className }: MetaPreviewProps ): React.ReactElement {
	const title = data.meta_title || 'Page Title';
	const description = data.meta_description || 'No description set. Search engines will generate a snippet from the page content.';
	const url = data.canonical_url || defaultUrl || 'https://example.com';

	const truncatedTitle = useMemo( () => {
		if ( title.length <= MAX_TITLE_LENGTH ) {
			return title;
		}

		return title.slice( 0, MAX_TITLE_LENGTH - 3 ) + '...';
	}, [title] );

	const truncatedDesc = useMemo( () => {
		if ( description.length <= MAX_DESC_LENGTH ) {
			return description;
		}

		return description.slice( 0, MAX_DESC_LENGTH - 3 ) + '...';
	}, [description] );

	const displayUrl = useMemo( () => {
		try {
			const parsed = new URL( url );

			return `${ parsed.hostname }${ parsed.pathname }`;
		} catch {
			return url;
		}
	}, [url] );

	return (
		<Card className={ className }>
			<div className="p-4">
				<p className="text-xs text-base-content/50 mb-2">Google Search Preview</p>
				<div className="max-w-xl">
					<p className="text-sm text-base-content/60 mb-0.5">
						{ displayUrl }
					</p>
					<h3 className="text-lg text-primary hover:underline cursor-pointer leading-tight mb-1">
						{ truncatedTitle }
					</h3>
					<p className="text-sm text-base-content/70 leading-snug">
						{ truncatedDesc }
					</p>
				</div>
			</div>
		</Card>
	);
}
