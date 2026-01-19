---
title: Configuration
---

# Configuration

ArtisanPack UI SEO provides extensive configuration options. After publishing the config file, you'll find it at `config/seo.php`.

## Site Settings

```php
'site' => [
    'name' => env('APP_NAME', 'My Site'),
    'description' => env('SEO_SITE_DESCRIPTION', ''),
    'separator' => ' | ',
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
    'title_max_length' => 60,
    'description_max_length' => 160,
    'robots' => 'index, follow',
    'canonical' => true,
],
```

| Option | Description |
|--------|-------------|
| `title_max_length` | Maximum characters for meta titles |
| `description_max_length` | Maximum characters for meta descriptions |
| `robots` | Default robots meta directive |
| `canonical` | Whether to auto-generate canonical URLs |

## Open Graph Settings

```php
'open_graph' => [
    'enabled' => true,
    'type' => 'website',
    'image' => null,
    'site_name' => env('APP_NAME', 'My Site'),
    'locale' => 'en_US',
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable/disable Open Graph tags |
| `type` | Default OG type (website, article, etc.) |
| `image` | Default OG image URL |
| `site_name` | Site name for og:site_name |
| `locale` | Default locale for og:locale |

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
    'card' => 'summary_large_image',
    'site' => null,
    'creator' => null,
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable/disable Twitter Card tags |
| `card` | Default card type |
| `site` | Default @username for the site |
| `creator` | Default @username for content creator |

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
        'name' => env('APP_NAME', 'My Site'),
        'url' => env('APP_URL', 'https://example.com'),
        'logo' => null,
        'same_as' => [],
    ],
    'default_type' => 'WebPage',
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable/disable Schema.org output |
| `organization.name` | Organization name for schema |
| `organization.url` | Organization URL |
| `organization.logo` | Organization logo URL |
| `organization.same_as` | Array of social profile URLs |
| `default_type` | Default schema type for pages |

## Sitemap Settings

```php
'sitemap' => [
    'enabled' => true,
    'max_urls' => 10000,
    'cache' => true,
    'cache_ttl' => 3600,
    'default_priority' => 0.5,
    'default_changefreq' => 'weekly',
    'types' => [
        'standard' => true,
        'image' => true,
        'video' => true,
        'news' => false,
    ],
    'news' => [
        'publication_name' => env('APP_NAME'),
        'publication_language' => 'en',
    ],
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable/disable sitemap generation |
| `max_urls` | Maximum URLs per sitemap file |
| `cache` | Enable sitemap caching |
| `cache_ttl` | Cache time-to-live in seconds |
| `default_priority` | Default URL priority (0.0-1.0) |
| `default_changefreq` | Default change frequency |
| `types` | Enable/disable sitemap types |
| `news` | News sitemap configuration |

### Change Frequency Values

- `always`
- `hourly`
- `daily`
- `weekly`
- `monthly`
- `yearly`
- `never`

## Robots.txt Settings

```php
'robots' => [
    'enabled' => true,
    'global' => [
        'disallow' => ['/admin', '/api'],
        'allow' => ['/api/public'],
    ],
    'bots' => [
        'GPTBot' => ['disallow' => ['/']],
        'CCBot' => ['disallow' => ['/']],
    ],
    'crawl_delay' => null,
    'sitemaps' => true,
    'host' => null,
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable dynamic robots.txt |
| `global` | Rules for all bots |
| `bots` | Bot-specific rules |
| `crawl_delay` | Crawl delay in seconds |
| `sitemaps` | Auto-include sitemap URLs |
| `host` | Host directive (for Yandex) |

## Redirect Settings

```php
'redirects' => [
    'enabled' => true,
    'cache' => true,
    'cache_ttl' => 3600,
    'track_hits' => true,
    'max_chain_depth' => 5,
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable redirect handling |
| `cache` | Cache redirect lookups |
| `cache_ttl` | Cache time-to-live |
| `track_hits` | Track redirect hit statistics |
| `max_chain_depth` | Maximum redirect chain depth |

## SEO Analysis Settings

```php
'analysis' => [
    'enabled' => true,
    'queue' => false,
    'cache' => true,
    'cache_ttl' => 86400,
    'analyzers' => [
        'readability' => ['enabled' => true, 'max_grade' => 8],
        'keyword_density' => ['enabled' => true, 'min' => 1, 'max' => 3],
        'focus_keyword' => ['enabled' => true],
        'meta_length' => ['enabled' => true],
        'heading_structure' => ['enabled' => true],
        'image_alt' => ['enabled' => true],
        'internal_links' => ['enabled' => true, 'min' => 2],
        'content_length' => ['enabled' => true, 'min' => 300],
    ],
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable SEO analysis |
| `queue` | Run analysis in background queue |
| `cache` | Cache analysis results |
| `cache_ttl` | Analysis cache TTL |
| `analyzers` | Individual analyzer settings |

## Hreflang Settings

```php
'hreflang' => [
    'enabled' => true,
    'locales' => ['en', 'en-US', 'fr', 'de', 'es'],
    'x_default' => true,
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable hreflang support |
| `locales` | Supported locale codes |
| `x_default` | Auto-add x-default fallback |

## Cache Settings

```php
'cache' => [
    'enabled' => true,
    'driver' => null,
    'prefix' => 'seo_',
    'ttl' => 3600,
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable global caching |
| `driver` | Cache driver (null = default) |
| `prefix` | Cache key prefix |
| `ttl` | Default cache TTL |

## Environment Variables

The package supports these environment variables:

```env
# Site Settings
SEO_SITE_DESCRIPTION="Your site description"

# Twitter
SEO_TWITTER_SITE="@yourusername"
SEO_TWITTER_CREATOR="@creatorusername"

# Feature Flags
SEO_SITEMAP_ENABLED=true
SEO_ROBOTS_ENABLED=true
SEO_REDIRECTS_ENABLED=true
SEO_ANALYSIS_ENABLED=true
```

## Next Steps

- [Installation](./installation.md) - Complete installation guide
- [Meta Tags](../usage/meta-tags.md) - Learn about meta tag management
- [Quick Start Guide](../getting-started.md) - Get started quickly
