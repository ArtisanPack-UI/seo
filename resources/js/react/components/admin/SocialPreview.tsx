/**
 * SocialPreview component.
 *
 * Displays live Open Graph and Twitter Card previews showing
 * how shared links will appear on social media platforms.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import React, { useState } from 'react';

import { Card, Tabs } from '@artisanpack-ui/react';

import type { SeoMetaResponse } from '../../../types/seo-data';

/** Props for the SocialPreview component. */
export interface SocialPreviewProps {
	/** Current SEO meta data. */
	data: SeoMetaResponse;
	/** The default URL for display. */
	defaultUrl?: string;
	/** Additional CSS class name. */
	className?: string;
}

/** Open Graph card preview sub-component. */
function OpenGraphPreviewCard( { data, defaultUrl }: { data: SeoMetaResponse; defaultUrl: string } ): React.ReactElement {
	const title       = data.og_title || data.meta_title || 'Page Title';
	const description = data.og_description || data.meta_description || '';
	const image       = data.og_image;
	const siteName    = data.og_site_name || '';
	const url         = data.canonical_url || defaultUrl;

	let displayDomain = '';

	try {
		displayDomain = new URL( url ).hostname;
	} catch {
		displayDomain = url;
	}

	return (
		<div className="border border-base-300 rounded-lg overflow-hidden max-w-lg">
			{ image && (
				<div className="aspect-[1.91/1] bg-base-200 overflow-hidden">
					<img
						src={ image }
						alt="Open Graph preview"
						className="w-full h-full object-cover"
					/>
				</div>
			) }

			{ !image && (
				<div className="aspect-[1.91/1] bg-base-200 flex items-center justify-center">
					<span className="text-base-content/30 text-sm">No image set</span>
				</div>
			) }

			<div className="p-3 bg-base-100">
				<p className="text-xs text-base-content/50 uppercase tracking-wide mb-1">
					{ displayDomain }
				</p>
				<h4 className="font-semibold text-base-content leading-tight mb-1 line-clamp-2">
					{ title }
				</h4>
				{ description && (
					<p className="text-sm text-base-content/60 line-clamp-2">
						{ description }
					</p>
				) }
				{ siteName && (
					<p className="text-xs text-base-content/40 mt-1">{ siteName }</p>
				) }
			</div>
		</div>
	);
}

/** Twitter Card preview sub-component. */
function TwitterCardPreviewCard( { data, defaultUrl }: { data: SeoMetaResponse; defaultUrl: string } ): React.ReactElement {
	const title       = data.twitter_title || data.meta_title || 'Page Title';
	const description = data.twitter_description || data.meta_description || '';
	const image       = data.twitter_image || data.og_image;
	const cardType    = data.twitter_card || 'summary';
	const url         = data.canonical_url || defaultUrl;

	let displayDomain = '';

	try {
		displayDomain = new URL( url ).hostname;
	} catch {
		displayDomain = url;
	}

	const isLargeImage = 'summary_large_image' === cardType;

	if ( isLargeImage ) {
		return (
			<div className="border border-base-300 rounded-2xl overflow-hidden max-w-lg">
				{ image && (
					<div className="aspect-[2/1] bg-base-200 overflow-hidden">
						<img
							src={ image }
							alt="Twitter Card preview"
							className="w-full h-full object-cover"
						/>
					</div>
				) }

				{ !image && (
					<div className="aspect-[2/1] bg-base-200 flex items-center justify-center">
						<span className="text-base-content/30 text-sm">No image set</span>
					</div>
				) }

				<div className="p-3 bg-base-100">
					<h4 className="font-semibold text-base-content leading-tight mb-0.5 line-clamp-1">
						{ title }
					</h4>
					{ description && (
						<p className="text-sm text-base-content/60 line-clamp-2 mb-1">
							{ description }
						</p>
					) }
					<p className="text-xs text-base-content/40">{ displayDomain }</p>
				</div>
			</div>
		);
	}

	// Summary card - side-by-side layout
	return (
		<div className="border border-base-300 rounded-2xl overflow-hidden max-w-lg flex">
			<div className="w-32 h-32 shrink-0 bg-base-200 overflow-hidden">
				{ image ? (
					<img
						src={ image }
						alt="Twitter Card preview"
						className="w-full h-full object-cover"
					/>
				) : (
					<div className="w-full h-full flex items-center justify-center">
						<span className="text-base-content/30 text-xs">No image</span>
					</div>
				) }
			</div>

			<div className="p-3 bg-base-100 flex flex-col justify-center min-w-0">
				<p className="text-xs text-base-content/40 mb-0.5">{ displayDomain }</p>
				<h4 className="font-semibold text-base-content leading-tight mb-0.5 line-clamp-2 text-sm">
					{ title }
				</h4>
				{ description && (
					<p className="text-xs text-base-content/60 line-clamp-2">
						{ description }
					</p>
				) }
			</div>
		</div>
	);
}

/**
 * Social media sharing preview.
 *
 * Shows tabbed previews for how shared links will appear on
 * Facebook (Open Graph) and Twitter/X (Twitter Card).
 */
export function SocialPreview( { data, defaultUrl = '', className }: SocialPreviewProps ): React.ReactElement {
	const [activeTab, setActiveTab] = useState( 'facebook' );

	const tabs = [
		{
			name: 'facebook',
			label: 'Facebook / Open Graph',
			content: <OpenGraphPreviewCard data={ data } defaultUrl={ defaultUrl } />,
		},
		{
			name: 'twitter',
			label: 'Twitter / X',
			content: <TwitterCardPreviewCard data={ data } defaultUrl={ defaultUrl } />,
		},
	];

	return (
		<Card className={ className }>
			<div className="p-4">
				<p className="text-xs text-base-content/50 mb-2">Social Media Preview</p>
				<Tabs
					tabs={ tabs }
					activeTab={ activeTab }
					onChange={ setActiveTab }
					size="sm"
				/>
			</div>
		</Card>
	);
}
