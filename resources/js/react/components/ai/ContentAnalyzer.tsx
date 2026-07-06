/**
 * React trigger for the ContentAnalysisAgent.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */

import type { FC } from 'react';

import { useAiAgent } from '../../hooks/useAiAgent';
import type { UseApiOptions } from '../../hooks/useApi';

interface ContentAnalysisInput {
	content: string;
	primary_keyword: string;
	secondary_keywords?: string[];
}

export interface DimensionScore {
	score: number;
	recommendations: string[];
}

export interface ContentAnalysisResult {
	overall_score: number;
	dimensions: {
		keyword_usage: DimensionScore;
		readability: DimensionScore;
		structure: DimensionScore;
		semantic_completeness: DimensionScore;
	};
}

export interface ContentAnalyzerProps {
	api: UseApiOptions;
	content: string;
	primaryKeyword: string;
	secondaryKeywords?: string[];
}

const DIMENSION_LABELS: Record<keyof ContentAnalysisResult['dimensions'], string> = {
	keyword_usage: 'Keyword usage',
	readability: 'Readability',
	structure: 'Structure',
	semantic_completeness: 'Semantic completeness',
};

export const ContentAnalyzer: FC<ContentAnalyzerProps> = ( {
	api,
	content,
	primaryKeyword,
	secondaryKeywords,
} ) => {
	const { output, isLoading, error, run } = useAiAgent<ContentAnalysisInput, ContentAnalysisResult>(
		'seo.analyze_content',
		api,
	);

	const disabled = isLoading || content.trim() === '' || primaryKeyword.trim() === '';

	const handleClick = (): void => {
		void run( {
			content,
			primary_keyword: primaryKeyword,
			secondary_keywords: secondaryKeywords,
		} );
	};

	return (
		<div className="seo-ai-analyzer" data-feature="seo.analyze_content">
			<button
				type="button"
				onClick={ handleClick }
				disabled={ disabled }
				className="seo-ai-analyzer__button"
			>
				{ isLoading ? 'Analyzing…' : 'Analyze content' }
			</button>

			{ error && (
				<p className="seo-ai-analyzer__error" role="alert">
					{ error }
				</p>
			) }

			{ output && (
				<div className="seo-ai-analyzer__result">
					<div className="seo-ai-analyzer__overall">
						<span className="seo-ai-analyzer__overall-label">Overall score</span>
						<span className="seo-ai-analyzer__overall-value">{ output.overall_score }/100</span>
					</div>
					<ul className="seo-ai-analyzer__dimensions">
						{ ( Object.keys( DIMENSION_LABELS ) as Array<keyof typeof DIMENSION_LABELS> ).map(
							( key ) => {
								const dim = output.dimensions[key];
								return (
									<li key={ key } className="seo-ai-analyzer__dimension">
										<div className="seo-ai-analyzer__dimension-header">
											<span className="seo-ai-analyzer__dimension-name">
												{ DIMENSION_LABELS[key] }
											</span>
											<span className="seo-ai-analyzer__dimension-score">
												{ dim.score }/100
											</span>
										</div>
										{ dim.recommendations.length > 0 && (
											<ul className="seo-ai-analyzer__recommendations">
												{ dim.recommendations.map( ( rec, idx ) => (
													<li key={ idx }>{ rec }</li>
												) ) }
											</ul>
										) }
									</li>
								);
							},
						) }
					</ul>
				</div>
			) }
		</div>
	);
};

export default ContentAnalyzer;
