/**
 * Component prop type definitions.
 *
 * TypeScript types for React/Vue SEO editor component props.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import type { SeoMetaResponse } from './seo-data';
import type { RedirectMatchType, RedirectStatusCode } from './redirect';

/**
 * Props for the SEO editor component.
 */
export interface SeoEditorProps {
    modelType: string;
    modelId: number;
    initialData?: SeoMetaResponse;
}

/**
 * Sort direction for redirect manager.
 */
export type SortDirection = 'asc' | 'desc';

/**
 * Sortable fields for the redirect manager.
 */
export type RedirectSortField = 'from_path' | 'to_path' | 'status_code' | 'hits' | 'last_hit_at' | 'created_at';

/**
 * Filter options for the redirect manager.
 */
export interface RedirectFilterOptions {
    status_code?: RedirectStatusCode;
    match_type?: RedirectMatchType;
    is_active?: boolean;
    search?: string;
}

/**
 * Sort options for the redirect manager.
 */
export interface RedirectSortOptions {
    field: RedirectSortField;
    direction: SortDirection;
}

/**
 * Props for the redirect manager component.
 */
export interface RedirectManagerProps {
    filters?: RedirectFilterOptions;
    sort?: RedirectSortOptions;
}
