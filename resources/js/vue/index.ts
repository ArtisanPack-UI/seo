/**
 * ArtisanPack UI SEO - Vue Components.
 *
 * Central export for all Vue SEO admin components and composables.
 * Publish these components to your application with:
 *
 *   php artisan vendor:publish --tag=seo-vue
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

// Composables
export {
	useApi,
	ApiError,
	ApiValidationError,
	useSeoMeta,
	useSeoAnalysis,
	useRedirects,
} from './composables';

export type {
	UseApiOptions,
	UseApiReturn,
	UseSeoMetaOptions,
	UseSeoMetaReturn,
	UseSeoAnalysisOptions,
	UseSeoAnalysisReturn,
	UseRedirectsReturn,
	PaginatedRedirects,
} from './composables';

export { useAiAgent } from './composables/useAiAgent';
export type { SeoAiFeatureKey, UseAiAgentReturn, AgentResponse } from './composables/useAiAgent';

// Admin Components
export {
	BasicMetaTab,
	OpenGraphTab,
	TwitterCardTab,
	SchemaTab,
	HreflangTab,
	SitemapTab,
	SeoMetaEditor,
	MetaPreview,
	SocialPreview,
	SeoAnalysisPanel,
	RedirectManager,
	SeoDashboard,
} from './components/admin';

// AI Trigger Components
export {
	MetaTitleSuggestor,
	MetaDescriptionSuggestor,
	ContentAnalyzer,
	SchemaSuggestor,
	HreflangSuggestor,
} from './components/ai';
