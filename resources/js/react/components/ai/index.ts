/**
 * React AI trigger components barrel export.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */

export { MetaTitleSuggestor } from './MetaTitleSuggestor';
export type { MetaTitleSuggestorProps, TitleVariant } from './MetaTitleSuggestor';

export { MetaDescriptionSuggestor } from './MetaDescriptionSuggestor';
export type {
	MetaDescriptionSuggestorProps,
	MetaDescriptionSuggestion,
} from './MetaDescriptionSuggestor';

export { ContentAnalyzer } from './ContentAnalyzer';
export type { ContentAnalyzerProps, ContentAnalysisResult, DimensionScore } from './ContentAnalyzer';

export { SchemaSuggestor } from './SchemaSuggestor';
export type { SchemaSuggestorProps, SchemaSuggestion } from './SchemaSuggestor';

export { HreflangSuggestor } from './HreflangSuggestor';
export type {
	HreflangSuggestorProps,
	HreflangIssue,
	HreflangPage,
	HreflangTranslation,
} from './HreflangSuggestor';
