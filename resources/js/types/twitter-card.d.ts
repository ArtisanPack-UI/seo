/**
 * TwitterCard type definitions.
 *
 * TypeScript types for Twitter Card meta tag data matching the TwitterCardDTO
 * and TwitterCardResource API response shapes.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

/**
 * Valid Twitter Card type values.
 */
export type TwitterCardType = 'summary' | 'summary_large_image' | 'app' | 'player';

/**
 * Twitter Card data matching TwitterCardDTO properties.
 */
export interface TwitterCard {
    card: TwitterCardType;
    title: string;
    description: string | null;
    image: string | null;
    site: string | null;
    creator: string | null;
}

/**
 * Twitter Card API response from TwitterCardResource.
 */
export interface TwitterCardResponse {
    card: TwitterCardType;
    title: string;
    description: string | null;
    image: string | null;
    site: string | null;
    creator: string | null;
}
