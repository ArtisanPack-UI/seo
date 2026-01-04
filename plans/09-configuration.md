# Configuration

**Purpose:** Define the configuration file structure and all available options
**Last Updated:** January 3, 2026

---

## Configuration File

The main configuration file is published to `config/seo.php`.

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Meta Tag Settings
    |--------------------------------------------------------------------------
    |
    | These are the default values used when no specific values are set
    | for a page or model.
    |
    */

    'defaults' => [
        // Appended to all page titles: "Page Title | Site Name"
        'title_suffix' => env('SEO_TITLE_SUFFIX', env('APP_NAME', 'My Website')),

        // Separator between page title and site name
        'title_separator' => env('SEO_TITLE_SEPARATOR', ' | '),

        // Default meta description for pages without one
        'meta_description' => env('SEO_META_DESCRIPTION', ''),

        // Default Open Graph type
        'og_type' => 'website',

        // Default Open Graph locale
        'og_locale' => 'en_US',

        // Default image for Open Graph/Twitter when none is set
        'og_image' => env('SEO_DEFAULT_OG_IMAGE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Platform Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for social media platforms.
    |
    */

    'social' => [
        'twitter' => [
            // Your Twitter @username
            'site' => env('SEO_TWITTER_SITE'),

            // Default content creator @username
            'creator' => env('SEO_TWITTER_CREATOR'),
        ],

        'pinterest' => [
            // Disable Pinterest hover buttons on images
            'disable_hover_buttons' => false,
        ],

        'slack' => [
            // Slack App ID for rich unfurling
            'app_id' => env('SEO_SLACK_APP_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema.org Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for structured data output.
    |
    */

    'schema' => [
        // Include organization schema on all pages
        'organization' => [
            'enabled' => true,
            // Organization, LocalBusiness, Restaurant, etc.
            'type' => env('SEO_SCHEMA_ORG_TYPE', 'Organization'),
        ],

        // Include website schema on all pages
        'website' => [
            'enabled' => true,
        ],

        // Include breadcrumb schema on content pages
        'breadcrumbs' => [
            'enabled' => true,
        ],

        // Organization details (used if cms-framework not installed)
        'organization_details' => [
            'name' => env('SEO_ORG_NAME', env('APP_NAME')),
            'logo' => env('SEO_ORG_LOGO'),
            'phone' => env('SEO_ORG_PHONE'),
            'email' => env('SEO_ORG_EMAIL'),
            'address' => [
                'street' => env('SEO_ORG_ADDRESS'),
                'city' => env('SEO_ORG_CITY'),
                'state' => env('SEO_ORG_STATE'),
                'zip' => env('SEO_ORG_ZIP'),
                'country' => env('SEO_ORG_COUNTRY', 'US'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for XML sitemap generation.
    |
    */

    'sitemap' => [
        'enabled' => true,

        // Cache duration in seconds (default: 1 hour)
        'cache_duration' => 3600,

        // Auto-update sitemap entries when content is saved
        'auto_update' => true,

        // Include different content types
        'include_pages' => true,
        'include_posts' => true,
        'include_products' => true,

        // Paths to exclude from sitemap (supports wildcards)
        'exclude_paths' => [
            '/admin/*',
            '/login',
            '/register',
            '/password/*',
        ],

        // Image sitemap settings
        'image' => [
            'enabled' => true,
        ],

        // Video sitemap settings
        'video' => [
            'enabled' => false,
        ],

        // News sitemap settings (for Google News)
        'news' => [
            'enabled' => false,
            'publication_name' => env('SEO_NEWS_PUBLICATION'),
            'publication_language' => 'en',
        ],

        // Maximum URLs per sitemap file (Google limit: 50,000)
        'max_urls_per_sitemap' => 10000,

        // Search engine ping URLs for sitemap submission
        'ping_urls' => [
            'google' => 'https://www.google.com/ping?sitemap=',
            'bing' => 'https://www.bing.com/ping?sitemap=',
        ],

        // Custom sitemap providers (class => config)
        'custom_types' => [
            // 'events' => [
            //     'enabled' => true,
            //     'provider' => \App\Seo\EventSitemapProvider::class,
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Robots.txt Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for dynamic robots.txt generation.
    |
    */

    'robots' => [
        // Enable dynamic robots.txt
        'enabled' => true,

        // Paths to disallow for all bots
        'disallow' => [
            '/admin',
            '/api',
            '/*.json',
        ],

        // Bot-specific rules
        'rules' => [
            // 'Googlebot' => [
            //     'disallow' => ['/private'],
            // ],
        ],

        // Additional directives
        'additional' => [
            // 'Crawl-delay: 10',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirect Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for URL redirects.
    |
    */

    'redirects' => [
        // Enable redirect middleware
        'enabled' => true,

        // Cache all redirects for performance
        'cache_enabled' => true,
        'cache_duration' => 3600,

        // Track redirect hits
        'track_hits' => true,

        // Regex timeout in milliseconds (prevent ReDoS)
        'regex_timeout' => 100,

        // Maximum redirect chain length before error
        'max_chain_length' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Analysis Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for content analysis and scoring.
    |
    */

    'analysis' => [
        // Auto-run analysis when content is saved
        'auto_analyze' => true,

        // Run analysis as a queued job
        'queue_analysis' => true,
        'queue_name' => 'default',

        // Minimum word count thresholds
        'word_count' => [
            'minimum' => 300,
            'good' => 600,
            'excellent' => 1000,
        ],

        // Keyword density thresholds (percentage)
        'keyword_density' => [
            'minimum' => 0.5,
            'maximum' => 2.5,
            'ideal' => 1.5,
        ],

        // Meta length thresholds
        'meta_title' => [
            'min_length' => 30,
            'max_length' => 60,
            'ideal_length' => 55,
        ],

        'meta_description' => [
            'min_length' => 120,
            'max_length' => 160,
            'ideal_length' => 155,
        ],

        // Readability settings
        'readability' => [
            // Target Flesch Reading Ease score (higher = easier)
            'target_score' => 60,

            // Maximum sentence length in words
            'max_sentence_length' => 25,

            // Maximum paragraph length in words
            'max_paragraph_length' => 200,
        ],

        // Analyzer weights for overall score (must total 100)
        'weights' => [
            'readability' => 25,
            'keyword' => 30,
            'meta' => 20,
            'content' => 25,
        ],

        // Custom analyzers to register
        'custom_analyzers' => [
            // 'custom' => \App\Seo\CustomAnalyzer::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-language (hreflang) Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for multi-language support.
    |
    */

    'hreflang' => [
        // Enable hreflang tag output
        'enabled' => true,

        // Default language code
        'default_language' => 'en',

        // Supported languages (for validation)
        'supported_languages' => ['en'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for SEO data caching.
    |
    */

    'cache' => [
        // Enable caching
        'enabled' => true,

        // Default cache TTL in seconds
        'ttl' => 3600,

        // Cache driver (null = default)
        'driver' => null,

        // Cache key prefix
        'prefix' => 'seo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for SEO-related routes.
    |
    */

    'routes' => [
        // Route prefix for admin routes
        'admin_prefix' => 'admin/seo',

        // Admin middleware
        'admin_middleware' => ['web', 'auth'],

        // Enable sitemap.xml route
        'sitemap' => true,

        // Enable robots.txt route
        'robots' => true,
    ],

];
```

---

## Environment Variables

Add these to your `.env` file:

```env
# Basic SEO Settings
SEO_TITLE_SUFFIX="My Website"
SEO_TITLE_SEPARATOR=" | "
SEO_META_DESCRIPTION="Default site description"
SEO_DEFAULT_OG_IMAGE="https://example.com/og-image.jpg"

# Social Media
SEO_TWITTER_SITE="@mywebsite"
SEO_TWITTER_CREATOR="@author"
SEO_SLACK_APP_ID=""

# Organization Schema
SEO_SCHEMA_ORG_TYPE="Organization"
SEO_ORG_NAME="My Company"
SEO_ORG_LOGO="https://example.com/logo.png"
SEO_ORG_PHONE="+1-555-555-5555"
SEO_ORG_EMAIL="contact@example.com"
SEO_ORG_ADDRESS="123 Main St"
SEO_ORG_CITY="Anytown"
SEO_ORG_STATE="ST"
SEO_ORG_ZIP="12345"
SEO_ORG_COUNTRY="US"

# News Sitemap
SEO_NEWS_PUBLICATION="My News Site"
```

---

## Publishing Configuration

```bash
# Publish config file
php artisan vendor:publish --tag=seo-config

# Publish migrations
php artisan vendor:publish --tag=seo-migrations

# Publish views (for customization)
php artisan vendor:publish --tag=seo-views

# Publish all
php artisan vendor:publish --provider="ArtisanPackUI\SEO\SEOServiceProvider"
```

---

## Runtime Configuration

You can also modify configuration at runtime:

```php
// In a service provider or controller

// Change title suffix dynamically
config(['seo.defaults.title_suffix' => 'Different Site Name']);

// Disable analysis for certain operations
config(['seo.analysis.auto_analyze' => false]);

// Add custom sitemap type
config(['seo.sitemap.custom_types.events' => [
    'enabled' => true,
    'provider' => EventSitemapProvider::class,
]]);
```

---

## Configuration Validation

The package validates configuration on boot:

```php
<?php

namespace ArtisanPackUI\SEO\Support;

class ConfigValidator
{
    public function validate(): array
    {
        $errors = [];

        // Check weight totals
        $weights = config('seo.analysis.weights');
        if (array_sum($weights) !== 100) {
            $errors[] = 'Analysis weights must total 100';
        }

        // Check required defaults
        if (empty(config('seo.defaults.title_suffix'))) {
            $errors[] = 'SEO title suffix is not configured';
        }

        // Check sitemap settings
        if (config('seo.sitemap.max_urls_per_sitemap') > 50000) {
            $errors[] = 'Sitemap max URLs cannot exceed 50,000';
        }

        return $errors;
    }
}
```

---

## Related Documents

- [01-architecture.md](01-architecture.md) - Package architecture
- [03-core-services.md](03-core-services.md) - Service implementations
