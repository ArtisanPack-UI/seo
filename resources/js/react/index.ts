/**
 * ArtisanPack UI SEO - React Components.
 *
 * Central export for all React SEO admin components and hooks.
 * Publish these components to your application with:
 *
 *   php artisan vendor:publish --tag=seo-react
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

// Hooks
export {
	useApi,
	ApiError,
	ApiValidationError,
	useSeoMeta,
	useSeoAnalysis,
	useRedirects,
} from './hooks';

export type {
	UseApiOptions,
	UseApiReturn,
	UseSeoMetaOptions,
	UseSeoMetaReturn,
	UseSeoAnalysisOptions,
	UseSeoAnalysisReturn,
	UseRedirectsReturn,
	PaginatedRedirects,
} from './hooks';

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

export type {
	BasicMetaTabProps,
	OpenGraphTabProps,
	TwitterCardTabProps,
	SchemaTabProps,
	HreflangTabProps,
	SitemapTabProps,
	SeoMetaEditorProps,
	MetaPreviewProps,
	SocialPreviewProps,
	SeoAnalysisPanelProps,
	RedirectManagerProps,
	SeoDashboardProps,
} from './components/admin';
