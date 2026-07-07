/**
 * React trigger for the MetaTitleSuggestionAgent.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */

import type { FC } from 'react';

import { useAiAgent } from '../../hooks/useAiAgent';
import type { UseApiOptions } from '../../hooks/useApi';

export interface TitleVariant {
	title: string;
	char_count: number;
	rationale: string;
}

interface MetaTitleSuggestorInput {
	content: string;
	primary_keyword?: string;
	brand?: string;
}

interface MetaTitleSuggestorOutput {
	variants: TitleVariant[];
}

export interface MetaTitleSuggestorProps {
	/** API configuration passed through to useApi. */
	api: UseApiOptions;
	/** Page content the agent should operate on. */
	content: string;
	/** Optional primary keyword. */
	primaryKeyword?: string;
	/** Optional brand suffix. */
	brand?: string;
	/** Called when the user picks a variant. */
	onSelect: ( title: string ) => void;
}

/**
 * Button + variant picker that runs the meta title suggestion agent.
 */
export const MetaTitleSuggestor: FC<MetaTitleSuggestorProps> = ( {
	api,
	content,
	primaryKeyword,
	brand,
	onSelect,
} ) => {
	const { output, isLoading, error, run } = useAiAgent<MetaTitleSuggestorInput, MetaTitleSuggestorOutput>(
		'seo.suggest_meta_title',
		api,
	);

	const disabled = isLoading || content.trim() === '';

	const handleClick = (): void => {
		void run( {
			content,
			primary_keyword: primaryKeyword,
			brand,
		} );
	};

	return (
		<div className="seo-ai-suggestor" data-feature="seo.suggest_meta_title">
			<button
				type="button"
				onClick={ handleClick }
				disabled={ disabled }
				className="seo-ai-suggestor__button"
			>
				{ isLoading ? 'Generating…' : 'Suggest titles' }
			</button>

			{ error && (
				<p className="seo-ai-suggestor__error" role="alert">
					{ error }
				</p>
			) }

			{ output && output.variants.length > 0 && (
				<ul className="seo-ai-suggestor__variants">
					{ output.variants.map( ( variant, index ) => (
						<li key={ index } className="seo-ai-suggestor__variant">
							<div className="seo-ai-suggestor__variant-header">
								<strong className="seo-ai-suggestor__variant-title">{ variant.title }</strong>
								<span className="seo-ai-suggestor__variant-count">
									{ variant.char_count }/60
								</span>
							</div>
							{ variant.rationale && (
								<p className="seo-ai-suggestor__variant-rationale">{ variant.rationale }</p>
							) }
							<button
								type="button"
								onClick={ () => onSelect( variant.title ) }
								className="seo-ai-suggestor__variant-apply"
							>
								Use this title
							</button>
						</li>
					) ) }
				</ul>
			) }
		</div>
	);
};

export default MetaTitleSuggestor;
