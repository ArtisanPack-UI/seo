/**
 * Hreflang type definitions.
 *
 * TypeScript types for hreflang language/region URL mappings
 * matching the HreflangResource API response shape.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

/**
 * A single hreflang entry mapping a language/region to a URL.
 */
export interface HreflangEntry {
    hreflang: string;
    href: string;
}
