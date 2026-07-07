/**
 * React trigger for the MetaDescriptionAgent.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */

import type { FC } from 'react';

import { useAiAgent } from '../../hooks/useAiAgent';
import type { UseApiOptions } from '../../hooks/useApi';

interface MetaDescriptionInput {
	content: string;
	primary_keyword?: string;
}

export interface MetaDescriptionSuggestion {
	meta_description: string;
	character_count: number;
	rationale: string;
}

export interface MetaDescriptionSuggestorProps {
	api: UseApiOptions;
	content: string;
	primaryKeyword?: string;
	onAccept: ( description: string ) => void;
}

export const MetaDescriptionSuggestor: FC<MetaDescriptionSuggestorProps> = ( {
	api,
	content,
	primaryKeyword,
	onAccept,
} ) => {
	const { output, isLoading, error, run } = useAiAgent<MetaDescriptionInput, MetaDescriptionSuggestion>(
		'seo.suggest_meta_description',
		api,
	);

	const disabled = isLoading || content.trim() === '';

	const handleClick = (): void => {
		void run( { content, primary_keyword: primaryKeyword } );
	};

	return (
		<div className="seo-ai-suggestor" data-feature="seo.suggest_meta_description">
			<button
				type="button"
				onClick={ handleClick }
				disabled={ disabled }
				className="seo-ai-suggestor__button"
			>
				{ isLoading ? 'Generating…' : 'Suggest meta description' }
			</button>

			{ error && (
				<p className="seo-ai-suggestor__error" role="alert">
					{ error }
				</p>
			) }

			{ output && output.meta_description !== '' && (
				<div className="seo-ai-suggestor__suggestion">
					<p className="seo-ai-suggestor__suggestion-text">{ output.meta_description }</p>
					<div className="seo-ai-suggestor__suggestion-meta">
						<span className="seo-ai-suggestor__suggestion-count">
							{ output.character_count }/160
						</span>
						{ output.rationale && (
							<span className="seo-ai-suggestor__suggestion-rationale">{ output.rationale }</span>
						) }
					</div>
					<button
						type="button"
						onClick={ () => onAccept( output.meta_description ) }
						className="seo-ai-suggestor__variant-apply"
					>
						Use this description
					</button>
				</div>
			) }
		</div>
	);
};

export default MetaDescriptionSuggestor;
