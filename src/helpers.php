<?php

/**
 * SEO helper functions.
 *
 * This file contains global helper functions for the SEO package.
 * Add your custom helper functions below.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

use ArtisanPackUI\SEO\SEO;

if ( ! function_exists( 'seo' ) ) {
    /**
     * Get the SEO instance.
     *
     * @since 1.0.0
     *
     * @return SEO
     */
    function seo(): SEO
    {
        return app( 'seo' );
    }
}

// Add your custom helper functions below
