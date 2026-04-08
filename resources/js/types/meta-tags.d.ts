/**
 * MetaTags type definitions.
 *
 * TypeScript types for HTML meta tag data matching the MetaTagsDTO
 * and MetaTagsResource API response shapes.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

/**
 * Core meta tag data matching MetaTagsDTO properties.
 */
export interface MetaTags {
    title: string;
    description: string | null;
    canonical: string;
    robots: string;
    additional_meta: Record<string, unknown>;
}

/**
 * Meta tags API response from MetaTagsResource.
 *
 * Includes computed length and warning fields.
 */
export interface MetaTagsResponse {
    title: string;
    title_length: number;
    title_warning: string | null;
    description: string | null;
    description_length: number;
    description_warning: string | null;
    canonical: string;
    robots: string;
    additional_meta: Record<string, unknown>;
}
