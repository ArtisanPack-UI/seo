/**
 * OpenGraph type definitions.
 *
 * TypeScript types for Open Graph meta tag data matching the OpenGraphDTO
 * and OpenGraphResource API response shapes.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

/**
 * Valid Open Graph type values.
 */
export type OpenGraphType =
    | 'website'
    | 'article'
    | 'book'
    | 'profile'
    | 'music.song'
    | 'music.album'
    | 'music.playlist'
    | 'music.radio_station'
    | 'video.movie'
    | 'video.episode'
    | 'video.tv_show'
    | 'video.other';

/**
 * Open Graph data matching OpenGraphDTO properties.
 */
export interface OpenGraph {
    title: string;
    description: string | null;
    image: string | null;
    url: string;
    type: OpenGraphType;
    site_name: string;
    locale: string;
}

/**
 * Open Graph API response from OpenGraphResource.
 */
export interface OpenGraphResponse {
    title: string;
    description: string | null;
    image: string | null;
    url: string;
    type: OpenGraphType;
    site_name: string;
    locale: string;
}
