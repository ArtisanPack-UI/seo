---
title: Configuration
---

# Configuration

ArtisanPack UI SEO provides extensive configuration options. After publishing the config file, you'll find it at `config/seo.php`.

## Site Settings

```php
'site' => [
    'name' => env('SEO_SITE_NAME', env('APP_NAME', 'Laravel')),
    'description' => env('SEO_SITE_DESCRIPTION', ''),
    'separator' => env('SEO_TITLE_SEPARATOR', ' | '),
],
```

| Option | Description |
|--------|-------------|
| `name` | Default site name appended to titles |
| `description` | Default site description for fallback |
| `separator` | Character(s) between page title and site name |

## Default Settings

```php
'defaults' => [
    'robots' => env('SEO_DEFAULT_ROBOTS', 'index, follow'),
    'title_max_length' => 60,
    'description_max_length' => 160,
],
```

| Option | Description |
|--------|-------------|
| `robots` | Default robots meta directive |
| `title_max_length` | The title suffix is skipped when it would pass this length. Set to 0 to always add it |
| `description_max_length` | Maximum length of generated descriptions, including `...`. Saved descriptions aren't shortened |

## Open Graph Settings

```php
'open_graph' => [
    'enabled' => true,
    'type' => 'website',
    'default_image' => null,
    'site_name' => env('APP_NAME', 'Laravel'),
    'default_image_width' => null,
    'default_image_height' => null,
    'default_image_alt' => null,
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable/disable Open Graph tags |
| `type` | OG type for models that aren't inferred as anything else |
| `default_image` | Default OG image URL |
| `site_name` | Site name for og:site_name |
| `default_image_width`, `default_image_height` | Emitted as og:image:width and og:image:height for the default image |
| `default_image_alt` | Emitted as og:image:alt and twitter:image:alt for the default image |

### Available Open Graph Types

- `website` - General websites
- `article` - Blog posts and articles
- `book` - Books
- `profile` - Personal profiles
- `music.song` - Music tracks
- `music.album` - Music albums
- `video.movie` - Movies
- `video.episode` - TV episodes
- `product` - Products

## Twitter Card Settings

```php
'twitter' => [
    'enabled' => true,
    'card_type' => 'summary_large_image',
    'site' => null,
    'creator' => null,
    'default_image' => null,
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable/disable Twitter Card tags |
| `card_type` | Default card type |
| `site` | Default @username for the site |
| `creator` | Default @username for content creator |
| `default_image` | Default image URL |

### Available Twitter Card Types

- `summary` - Title, description, and thumbnail
- `summary_large_image` - Large image above the content
- `app` - App download cards
- `player` - Video/audio player cards

## Schema.org Settings

```php
'schema' => [
    'enabled' => true,
    'organization' => [
        'name' => env('APP_NAME', 'Laravel'),
        'logo' => null,
        'url' => env('APP_URL', ''),
        'email' => null,
        'phone' => null,
        'sameAs' => [],
    ],
    'default_types' => [
        'page' => 'WebPage',
        'article' => 'Article',
        'product' => 'Product',
    ],
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable/disable Schema.org output |
| `organization` | Organization details for schema |
| `default_types` | Default schema type by content type |

## Sitemap Settings

```php
'sitemap' => [
    'enabled' => env('SEO_SITEMAP_ENABLED', true),
    'route_enabled' => true,
    'route_path' => 'sitemap.xml',
    'max_urls_per_file' => 10000,
    'default_frequency' => null,
    'default_priority' => null,
    'cache_enabled' => true,
    'cache_ttl' => 3600,
    'submit_enabled' => env('SEO_SITEMAP_SUBMIT_ENABLED', false),
    'submit_timeout' => 10,
    'providers' => [],
    'types' => [
        'standard' => true,
        'image' => true,
        'video' => false,
        'news' => false,
    ],
    'news' => [
        'types' => ['article', 'post', 'news'],
        'max_age_days' => 2,
    ],
    'search_engines' => [],
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable/disable sitemap generation |
| `route_enabled` | Register the sitemap routes |
| `route_path` | Path of the main sitemap |
| `max_urls_per_file` | Maximum URLs per sitemap file |
| `default_frequency` | Site-wide `changefreq`, left out when null |
| `default_priority` | Site-wide `priority` (0.0-1.0), left out when null |
| `cache_enabled` | Enable sitemap caching |
| `cache_ttl` | Cache time-to-live in seconds |
| `submit_enabled` | Ping search engines from `notifyOnPublish()` |
| `submit_timeout` | HTTP timeout for pings, in seconds |
| `providers` | Provider classes keyed by sitemap type |
| `types` | Enable/disable sitemap types |
| `news` | News sitemap configuration |
| `search_engines` | Ping URLs keyed by engine name, empty by default |

### Change Frequency Values

- `always`
- `hourly`
- `daily`
- `weekly`
- `monthly`
- `yearly`
- `never`

## llms.txt Settings

See [llms.txt](Advanced-Llms-Txt) for `seo.llms_txt`.

## Robots.txt Settings

```php
'robots' => [
    'enabled' => true,
    'route_enabled' => true,
    'route_path' => 'robots.txt',
    'cache_ttl' => 3600,
    'write_static' => env('SEO_ROBOTS_WRITE_STATIC', false),
    'disallow' => ['/admin', '/api'],
    'allow' => [],
    'rules' => [],
    'sitemap_url' => null,
    'sitemaps' => [],
    'host' => null,
    'ai_crawlers' => [
        'default_allow' => true,
        'groups' => [ /* ... */ ],
    ],
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable robots.txt generation |
| `route_enabled` | Register the dynamic route |
| `route_path` | Path of the dynamic route |
| `cache_ttl` | `max-age` of the response, in seconds |
| `write_static` | Write the rules to `public/robots.txt` |
| `disallow`, `allow` | Rules for all bots |
| `rules` | Rules per user-agent |
| `sitemap_url` | Sitemap URL, built from the sitemap route when null |
| `sitemaps` | Additional sitemap URLs |
| `host` | Host directive (for Yandex) |
| `ai_crawlers` | AI crawler groups, see [Robots.txt](Advanced-Robots) |

## Redirect Settings

```php
'redirects' => [
    'enabled' => env('SEO_REDIRECTS_ENABLED', true),
    'middleware_enabled' => true,
    'cache_enabled' => true,
    'cache_ttl' => 86400,
    'track_hits' => true,
    'max_chain_depth' => 5,
    'authorization_enabled' => false,
    'authorization_ability' => 'manage-redirects',
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable redirect handling |
| `middleware_enabled` | Add `HandleRedirects` to the web group automatically |
| `cache_enabled` | Cache redirect lookups |
| `cache_ttl` | Cache time-to-live |
| `track_hits` | Track redirect hit statistics |
| `max_chain_depth` | Maximum redirect chain depth |
| `authorization_enabled` | Require an ability in the Redirect Manager |
| `authorization_ability` | The gate ability to check |

## SEO Analysis Settings

```php
'analysis' => [
    'enabled' => env('SEO_ANALYSIS_ENABLED', true),
    'queue_enabled' => false,
    'queue_connection' => null,
    'queue_name' => 'seo',
    'cache_enabled' => true,
    'cache_ttl' => 86400,
    'analyzers' => [
        'readability' => true,
        'keyword_density' => true,
        'focus_keyword' => true,
        'meta_length' => true,
        'heading_structure' => true,
        'image_alt' => true,
        'internal_links' => true,
        'content_length' => true,
        'ai_readiness' => true,
    ],
    'thresholds' => [
        'min_word_count' => 300,
        'max_keyword_density' => 3.0,
        'min_internal_links' => 2,
    ],
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable SEO analysis |
| `queue_enabled` | Run analysis in a background queue |
| `queue_connection`, `queue_name` | Queue to use |
| `cache_enabled` | Cache analysis results |
| `cache_ttl` | Analysis cache TTL |
| `analyzers` | Turn individual analyzers on or off |
| `thresholds` | Analyzer thresholds |

## Hreflang Settings

```php
'hreflang' => [
    'enabled' => false,
    'default_locale' => 'en',
    'auto_add_x_default' => true,
    'supported_locales' => ['en', 'en-US', 'fr', 'de', 'es'],
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable hreflang support |
| `default_locale` | Primary locale, used for x-default |
| `auto_add_x_default` | Auto-add x-default fallback |
| `supported_locales` | Locale codes available for selection, empty allows all common locales |

## Cache Settings

```php
'cache' => [
    'enabled' => env('SEO_CACHE_ENABLED', true),
    'ttl' => env('SEO_CACHE_TTL', 3600),
    'driver' => null,
    'prefix' => 'seo',
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable global caching |
| `ttl` | Default cache TTL |
| `driver` | Cache driver (null = default) |
| `prefix` | Cache key prefix |

## Environment Variables

The package supports these environment variables:

```env
# Site Settings
SEO_SITE_NAME="Your Site"
SEO_SITE_DESCRIPTION="Your site description"
SEO_TITLE_SEPARATOR=" | "
SEO_DEFAULT_ROBOTS="index, follow"

# Feature Flags
SEO_SITEMAP_ENABLED=true
SEO_SITEMAP_SUBMIT_ENABLED=false
SEO_ROBOTS_WRITE_STATIC=false
SEO_REDIRECTS_ENABLED=true
SEO_ANALYSIS_ENABLED=true
SEO_CACHE_ENABLED=true
SEO_LLMS_TXT_ROUTE_ENABLED=false
SEO_INDEXNOW_ENABLED=false
```

## Next Steps

- [Installation](Installation) - Complete installation guide
- [Meta Tags](Usage-Meta-Tags) - Learn about meta tag management
- [Quick Start Guide](Getting-Started) - Get started quickly
