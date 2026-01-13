<?php

/**
 * SEO Package Configuration.
 *
 * This file contains the default configuration for the ArtisanPack UI SEO package.
 * These settings can be overridden in config/artisanpack.php under the 'seo' key.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Meta Tags
    |--------------------------------------------------------------------------
    |
    | These are the default meta tags that will be used when no specific
    | values are provided for a page.
    |
    */
    'defaults' => [
        'title'            => env( 'APP_NAME', 'Laravel' ),
        'title_separator'  => ' | ',
        'description'      => '',
        'keywords'         => [],
        'robots'           => 'index, follow',
        'canonical'        => null,
        'author'           => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Open Graph Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Open Graph (Facebook, LinkedIn, etc.) meta tags.
    |
    */
    'open_graph' => [
        'enabled'     => true,
        'type'        => 'website',
        'site_name'   => env( 'APP_NAME', 'Laravel' ),
        'locale'      => env( 'APP_LOCALE', 'en_US' ),
        'image'       => null,
        'image_width' => 1200,
        'image_height' => 630,
    ],

    /*
    |--------------------------------------------------------------------------
    | Twitter Card Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Twitter Card meta tags.
    |
    */
    'twitter' => [
        'enabled'  => true,
        'card'     => 'summary_large_image',
        'site'     => null,
        'creator'  => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON-LD Structured Data
    |--------------------------------------------------------------------------
    |
    | Configuration for JSON-LD structured data (Schema.org).
    |
    */
    'json_ld' => [
        'enabled' => true,
        'organization' => [
            'name'  => env( 'APP_NAME', 'Laravel' ),
            'url'   => env( 'APP_URL', 'http://localhost' ),
            'logo'  => null,
            'social_profiles' => [],
        ],
        'website' => [
            'name'         => env( 'APP_NAME', 'Laravel' ),
            'url'          => env( 'APP_URL', 'http://localhost' ),
            'search_url'   => null,
            'search_input' => 'search_term_string',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for XML sitemap generation.
    |
    */
    'sitemap' => [
        'enabled'           => true,
        'path'              => 'sitemap.xml',
        'cache_enabled'     => true,
        'cache_duration'    => 3600,
        'max_urls_per_file' => 50000,
        'default_priority'  => 0.5,
        'default_frequency' => 'weekly',
    ],

    /*
    |--------------------------------------------------------------------------
    | Robots.txt Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for dynamic robots.txt generation.
    |
    */
    'robots_txt' => [
        'enabled'       => false,
        'allow_all'     => true,
        'disallow'      => [],
        'sitemap_url'   => null,
        'custom_rules'  => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Title Templates
    |--------------------------------------------------------------------------
    |
    | Templates for generating page titles. Use :title as a placeholder
    | for the page-specific title.
    |
    */
    'title_templates' => [
        'default' => ':title:separator:site_name',
        'home'    => ':site_name',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model SEO Fields
    |--------------------------------------------------------------------------
    |
    | Configuration for model-level SEO fields.
    |
    */
    'models' => [
        'auto_generate_meta'      => true,
        'description_max_length'  => 160,
        'title_max_length'        => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for caching SEO data.
    |
    */
    'cache' => [
        'enabled'  => true,
        'driver'   => env( 'SEO_CACHE_DRIVER', 'file' ),
        'duration' => 3600,
        'prefix'   => 'artisanpack_seo_',
    ],

];
