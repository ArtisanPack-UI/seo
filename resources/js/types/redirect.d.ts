/**
 * Redirect type definitions.
 *
 * TypeScript types for URL redirect data matching the Redirect model
 * and RedirectResource API response shapes.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

/**
 * Valid redirect HTTP status codes.
 */
export type RedirectStatusCode = 301 | 302 | 307 | 308;

/**
 * Valid redirect match types.
 */
export type RedirectMatchType = 'exact' | 'regex' | 'wildcard';

/**
 * Redirect API response from RedirectResource.
 */
export interface Redirect {
    id: number;
    from_path: string;
    to_path: string;
    status_code: RedirectStatusCode;
    status_code_label: string;
    match_type: RedirectMatchType;
    match_type_label: string;
    is_active: boolean;
    is_permanent: boolean;
    is_temporary: boolean;
    hits: number;
    last_hit_at: string | null;
    notes: string | null;
    created_at: string | null;
    updated_at: string | null;
}

/**
 * Result from testing a URL path against redirects.
 */
export interface RedirectTestResult {
    matched: Redirect | null;
    resolved_destination: string | null;
}
