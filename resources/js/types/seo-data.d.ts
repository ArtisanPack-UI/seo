/**
 * SeoData type definitions.
 *
 * TypeScript types for combined SEO data matching the SeoMetaResource
 * and SeoPreviewResource API response shapes.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import type { HreflangEntry } from './hreflang';
import type { MetaTags, MetaTagsResponse } from './meta-tags';
import type { OpenGraph, OpenGraphResponse } from './open-graph';
import type { TwitterCard, TwitterCardResponse } from './twitter-card';

/**
 * Combined SEO data wrapping all meta tag types.
 */
export interface SeoData {
    meta_tags: MetaTags;
    open_graph: OpenGraph;
    twitter_card: TwitterCard;
    hreflang: HreflangEntry[];
}

/**
 * Analysis cache summary included in SeoMeta responses.
 */
export interface AnalysisCacheSummary {
    overall_score: number;
    grade: 'good' | 'ok' | 'poor';
    analyzed_at: string | null;
    is_stale: boolean;
}

/**
 * Full SEO meta API response from SeoMetaResource.
 */
export interface SeoMetaResponse {
    id: number;
    seoable_type: string;
    seoable_id: number;
    meta_title: string | null;
    meta_title_length: number;
    meta_title_warning: string | null;
    meta_description: string | null;
    meta_description_length: number;
    meta_description_warning: string | null;
    canonical_url: string | null;
    no_index: boolean;
    no_follow: boolean;
    robots_meta: string | null;
    robots_content: string;
    is_indexable: boolean;
    is_followable: boolean;
    og_title: string | null;
    og_description: string | null;
    og_image: string | null;
    og_image_id: number | null;
    og_type: string | null;
    og_locale: string | null;
    og_site_name: string | null;
    has_open_graph: boolean;
    twitter_card: string | null;
    twitter_title: string | null;
    twitter_description: string | null;
    twitter_image: string | null;
    twitter_image_id: number | null;
    twitter_site: string | null;
    twitter_creator: string | null;
    has_twitter_card: boolean;
    pinterest_description: string | null;
    pinterest_image: string | null;
    pinterest_image_id: number | null;
    slack_title: string | null;
    slack_description: string | null;
    slack_image: string | null;
    slack_image_id: number | null;
    schema_type: string | null;
    schema_markup: Record<string, unknown> | null;
    has_schema: boolean;
    focus_keyword: string | null;
    secondary_keywords: string[] | null;
    all_keywords: string[];
    hreflang: HreflangEntry[] | null;
    sitemap_priority: number | null;
    sitemap_changefreq: string | null;
    exclude_from_sitemap: boolean;
    in_sitemap: boolean;
    analysis_cache?: AnalysisCacheSummary;
    created_at: string | null;
    updated_at: string | null;
}

/**
 * SEO preview API response from SeoPreviewResource.
 */
export interface SeoPreviewResponse {
    search: SearchPreview;
    social: {
        open_graph: OpenGraphPreview;
        twitter_card: TwitterCardPreview;
    };
    meta: MetaTagsResponse;
    hreflang: HreflangEntry[];
}

/**
 * Google search snippet preview.
 */
export interface SearchPreview {
    title: string;
    title_truncated: string;
    title_length: number;
    title_is_truncated: boolean;
    description: string;
    description_truncated: string;
    description_length: number;
    description_is_truncated: boolean;
    url: string;
}

/**
 * Open Graph social card preview.
 */
export interface OpenGraphPreview {
    title: string;
    description: string | null;
    image: string | null;
    url: string;
    site_name: string;
    type: string;
}

/**
 * Twitter Card preview.
 */
export interface TwitterCardPreview {
    card: string;
    title: string;
    description: string | null;
    image: string | null;
    site: string | null;
    creator: string | null;
}
