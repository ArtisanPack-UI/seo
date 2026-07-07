/**
 * React trigger for the SchemaGenerationAgent.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */

import type { FC } from 'react';

import { useAiAgent } from '../../hooks/useAiAgent';
import type { UseApiOptions } from '../../hooks/useApi';

interface SchemaInput {
	content: string;
	title: string;
	url?: string;
}

export interface SchemaSuggestion {
	suggested_type: string;
	confidence: number;
	jsonld: Record<string, unknown>;
	missing_required_fields: string[];
}

export interface SchemaSuggestorProps {
	api: UseApiOptions;
	content: string;
	title: string;
	url?: string;
	onAccept: ( suggestion: SchemaSuggestion ) => void;
}

export const SchemaSuggestor: FC<SchemaSuggestorProps> = ( {
	api,
	content,
	title,
	url,
	onAccept,
} ) => {
	const { output, isLoading, error, run } = useAiAgent<SchemaInput, SchemaSuggestion>(
		'seo.generate_schema',
		api,
	);

	const disabled = isLoading || content.trim() === '' || title.trim() === '';

	const handleClick = (): void => {
		void run( { content, title, url } );
	};

	return (
		<div className="seo-ai-suggestor" data-feature="seo.generate_schema">
			<button
				type="button"
				onClick={ handleClick }
				disabled={ disabled }
				className="seo-ai-suggestor__button"
			>
				{ isLoading ? 'Generating…' : 'Suggest schema' }
			</button>

			{ error && (
				<p className="seo-ai-suggestor__error" role="alert">
					{ error }
				</p>
			) }

			{ output && (
				<div className="seo-ai-suggestor__suggestion">
					<div className="seo-ai-suggestor__suggestion-header">
						<strong>{ output.suggested_type }</strong>
						<span>
							Confidence: { Math.round( output.confidence * 100 ) }%
						</span>
					</div>
					<pre className="seo-ai-suggestor__jsonld">
						<code>{ JSON.stringify( output.jsonld, null, 2 ) }</code>
					</pre>
					{ output.missing_required_fields.length > 0 && (
						<div className="seo-ai-suggestor__missing">
							<strong>Missing required fields:</strong>
							<ul>
								{ output.missing_required_fields.map( ( field, idx ) => (
									<li key={ idx }>{ field }</li>
								) ) }
							</ul>
						</div>
					) }
					<button
						type="button"
						onClick={ () => onAccept( output ) }
						className="seo-ai-suggestor__variant-apply"
					>
						Populate schema builder
					</button>
				</div>
			) }
		</div>
	);
};

export default SchemaSuggestor;
