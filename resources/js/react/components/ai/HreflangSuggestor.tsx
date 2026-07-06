/**
 * React trigger for the HreflangSuggestionAgent.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */

import type { FC } from 'react';

import { useAiAgent } from '../../hooks/useAiAgent';
import type { UseApiOptions } from '../../hooks/useApi';

export interface HreflangTranslation {
	url: string;
	lang: string;
}

export interface HreflangPage {
	url: string;
	lang: string;
	translations: HreflangTranslation[];
}

export interface HreflangIssue {
	page_url: string;
	issue_type: string;
	suggested_hreflang: HreflangTranslation[];
}

interface HreflangInput {
	pages: HreflangPage[];
}

interface HreflangOutput {
	issues: HreflangIssue[];
}

export interface HreflangSuggestorProps {
	api: UseApiOptions;
	pages: HreflangPage[];
	onApplyFix: ( issue: HreflangIssue ) => void;
}

export const HreflangSuggestor: FC<HreflangSuggestorProps> = ( {
	api,
	pages,
	onApplyFix,
} ) => {
	const { output, isLoading, error, run } = useAiAgent<HreflangInput, HreflangOutput>(
		'seo.suggest_hreflang',
		api,
	);

	const disabled = isLoading || pages.length === 0;

	const handleClick = (): void => {
		void run( { pages } );
	};

	return (
		<div className="seo-ai-suggestor" data-feature="seo.suggest_hreflang">
			<button
				type="button"
				onClick={ handleClick }
				disabled={ disabled }
				className="seo-ai-suggestor__button"
			>
				{ isLoading ? 'Analyzing…' : 'Check hreflang' }
			</button>

			{ error && (
				<p className="seo-ai-suggestor__error" role="alert">
					{ error }
				</p>
			) }

			{ output && output.issues.length > 0 && (
				<ul className="seo-ai-suggestor__issues">
					{ output.issues.map( ( issue, index ) => (
						<li key={ index } className="seo-ai-suggestor__issue">
							<div className="seo-ai-suggestor__issue-header">
								<code>{ issue.page_url }</code>
								<span className="seo-ai-suggestor__issue-type">{ issue.issue_type }</span>
							</div>
							{ issue.suggested_hreflang.length > 0 && (
								<ul className="seo-ai-suggestor__hreflang">
									{ issue.suggested_hreflang.map( ( entry, entryIdx ) => (
										<li key={ entryIdx }>
											<strong>{ entry.lang }</strong>
											<code>{ entry.url }</code>
										</li>
									) ) }
								</ul>
							) }
							<button
								type="button"
								onClick={ () => onApplyFix( issue ) }
								className="seo-ai-suggestor__variant-apply"
							>
								Apply suggested tags
							</button>
						</li>
					) ) }
				</ul>
			) }

			{ output && output.issues.length === 0 && ! isLoading && ! error && (
				<p className="seo-ai-suggestor__empty">No hreflang issues detected.</p>
			) }
		</div>
	);
};

export default HreflangSuggestor;
