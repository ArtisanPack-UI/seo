---
title: Robots.txt
---

# Robots.txt

ArtisanPack UI SEO provides dynamic robots.txt generation with support for global rules, bot-specific directives, and automatic sitemap inclusion.

## Overview

The robots.txt file tells search engine crawlers which pages they can or cannot crawl. The package generates this file dynamically based on your configuration.

## Configuration

```php
// In config/seo.php
'robots' => [
    'enabled'       => true,
    'route_enabled' => true,
    'route_path'    => 'robots.txt',
    'cache_ttl'     => 3600,
    'write_static'  => env('SEO_ROBOTS_WRITE_STATIC', false),
    'disallow'      => ['/admin', '/api'],
    'allow'         => [],
    'rules'         => [
        'GPTBot' => ['disallow' => ['/']],
        'Googlebot' => [
            'allow'       => ['/api/public'],
            'disallow'    => ['/api/internal'],
            'crawl_delay' => 1,
        ],
    ],
    'sitemap_url'   => null,
    'sitemaps'      => [],
    'host'          => null,
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
| `rules` | Rules per user-agent, with `disallow`, `allow`, and `crawl_delay` |
| `sitemap_url` | Sitemap URL, built from the sitemap route when null |
| `sitemaps` | Additional sitemap URLs |
| `host` | Host directive (for Yandex) |

## Serving Robots.txt

### Via the Route

With `route_enabled` on, the package serves `GET /robots.txt`. A physical `public/robots.txt` takes precedence on most servers, so delete Laravel's stock file to use the dynamic route.

### Via a Controller

```php
use ArtisanPackUI\SEO\Services\RobotsService;

class RobotsController extends Controller
{
    public function __invoke(RobotsService $robots)
    {
        return response($robots->generate())
            ->header('Content-Type', 'text/plain');
    }
}
```

### Forge and Stock Laravel Nginx Templates

The dynamic route returns a 404 status on Nginx templates that contain `location = /robots.txt { log_not_found off; }` and `error_page 404 /index.php;`. A missing physical file reaches PHP but keeps the 404. Crawlers treat a 4xx robots file as having no rules, so your `Sitemap:` line is never read. The body looks correct, so check the status:

```bash
curl -I https://example.com/robots.txt
```

There are two fixes. Change the Nginx location:

```nginx
location = /robots.txt { access_log off; log_not_found off; try_files $uri /index.php?$query_string; }
```

Or write a physical file, as described below.

### Writing a Static File

Set `SEO_ROBOTS_WRITE_STATIC=true`, delete any existing `public/robots.txt`, and run:

```bash
php artisan seo:write-robots
```

The command also runs during `php artisan optimize`, except on early Laravel 11 releases, which have no hook for it. Run it again after changing the rules. Setting `write_static` back to `false` and running it removes the file.

- The file starts with a marker comment. A `robots.txt` without the marker is never replaced or removed unless you pass `--force`, and the command exits with a failure so a deploy can't hide it.
- The file is never written from a web request, because the `Sitemap:` line uses the current host and a spoofed `Host` header would be saved into it.
- Every host sharing the public directory gets the same file, so sites served from several hosts should use the Nginx fix.

If your rules are stored at runtime, call `syncStaticFile()` from a console command or queued job after saving:

```php
use ArtisanPackUI\SEO\Services\RobotsService;

// In a queued job, after your settings are applied to config
app()->forgetInstance(RobotsService::class);
$status = app(RobotsService::class)->syncStaticFile();
// Returns written, unchanged, removed, disabled, or unmanaged
```

An instance keeps the rules it loaded when it was built, which is why a long-running worker needs a new one. A new instance doesn't include rules added with `disallow()` or `allow()` on the old one.

## Generated Output

```text
User-agent: GPTBot
Disallow: /

User-agent: Googlebot
Allow: /api/public
Disallow: /api/internal
Crawl-delay: 1

User-agent: *
Disallow: /admin
Disallow: /api

Sitemap: https://example.com/sitemap.xml
```

## Programmatic Management

### Using the Service

```php
use ArtisanPackUI\SEO\Services\RobotsService;

$robotsService = app(RobotsService::class);

// Generate content
$content = $robotsService->generate();

// Add disallow rule (default user-agent: *)
$robotsService->disallow('/secret');

// Add allow rule
$robotsService->allow('/public');

// Add bot-specific rules
$robotsService->disallow('/archive', 'Bingbot');
$robotsService->crawlDelay(5, 'Bingbot');

// Add sitemap
$robotsService->addSitemap('https://example.com/sitemap.xml');

// Set crawl delay for all bots
$robotsService->crawlDelay(10);

// Set host directive
$robotsService->setHost('example.com');

// Get rules for a specific user-agent
$rules = $robotsService->getRulesForUserAgent('Googlebot');

// Get all user agents with rules
$userAgents = $robotsService->getUserAgents();

// Clear all rules and start fresh
$robotsService->clearRules();

// Remove rules for a specific user-agent
$robotsService->removeUserAgent('GPTBot');
```

## Bot-Specific Rules

### Common Bots

| Bot | Description |
|-----|-------------|
| `Googlebot` | Google's crawler |
| `Bingbot` | Microsoft Bing's crawler |
| `Slurp` | Yahoo's crawler |
| `DuckDuckBot` | DuckDuckGo's crawler |
| `Baiduspider` | Baidu's crawler |
| `YandexBot` | Yandex's crawler |

### AI/LLM Bots

| Bot | Description |
|-----|-------------|
| `GPTBot` | OpenAI's crawler |
| `ChatGPT-User` | ChatGPT browsing |
| `CCBot` | Common Crawl |
| `anthropic-ai` | Anthropic's crawler |
| `Claude-Web` | Claude browsing |

### Blocking AI Crawlers (config-driven)

Since 1.4.0 the package ships a dedicated AI-crawler surface at
`seo.robots.ai_crawlers`. It groups related bots (OpenAI, Anthropic,
Google-Extended, Perplexity, Common Crawl, ByteDance, Meta) so a
single toggle blocks every user-agent in the group, and the same
resolution logic powers host UA middleware — the robots.txt output
and the middleware can never disagree about whether a bot is
allowed.

```php
'robots' => [
    'ai_crawlers' => [
        // Default policy for groups that haven't set a `blocked` flag.
        // true  = allow unless a group is explicitly blocked.
        // false = block every group unless it explicitly opts back in
        //         with `blocked => false`.
        'default_allow' => true,

        'groups' => [
            'openai' => [
                'label'       => 'OpenAI',
                'user_agents' => ['GPTBot', 'ChatGPT-User', 'OAI-SearchBot'],
                'blocked'     => true, // Disallow: / for every user-agent in the group
            ],
            'anthropic' => [
                'label'       => 'Anthropic',
                'user_agents' => ['ClaudeBot', 'Claude-Web', 'anthropic-ai'],
                'blocked'     => false, // Explicit opt-in (still allowed even if default_allow=false)
            ],
            // ...google-extended, perplexity, common-crawl, bytedance, meta
        ],
    ],
],
```

**`default_allow` semantics (fixed in 1.4.0)**

- `default_allow=true` + `blocked=true` on a group → that group is
  disallowed; other groups are allowed.
- `default_allow=true` + `blocked=false` on a group → allowed.
- `default_allow=false` + `blocked=false` on a group → still allowed
  (explicit opt-in survives the kill switch).
- `default_allow=false` + no `blocked` key on a group → **blocked**
  (the kill switch is what makes this useful).

If you flip `default_allow` to `false` on a fresh install with the
shipped defaults, every group has `blocked => false` explicitly, so
you'll see no directives change — remove the `blocked => false` from
a group's config to opt it back into the kill switch.

If you prefer to manage individual user-agents directly, use `rules`:

```php
'rules' => [
    'GPTBot' => ['disallow' => ['/']],
    'ChatGPT-User' => ['disallow' => ['/']],
    'CCBot' => ['disallow' => ['/']],
    'anthropic-ai' => ['disallow' => ['/']],
],
```

## Crawl Delay

```php
'robots' => [
    'rules' => [
        '*'       => ['crawl_delay' => 10],
        'Bingbot' => ['crawl_delay' => 5],
    ],
],
```

## Environment-Based Configuration

### Development/Staging

Block all crawlers in non-production environments:

```php
'robots' => [
    'enabled' => true,
    'disallow' => env('APP_ENV') === 'production' ? ['/admin'] : ['/'],
],
```

Or in the service:

```php
$robotsService = app(RobotsService::class);

if (!app()->environment('production')) {
    $robotsService->disallow('/');
}
```

### Generated Output (Staging)

```text
User-agent: *
Disallow: /
```

## Multiple Sitemaps

Include multiple sitemap URLs:

```php
'robots' => [
    'sitemaps' => [
        'https://example.com/sitemap-news.xml',
        'https://example.com/sitemap-images.xml',
    ],
],
```

Or programmatically:

```php
$robotsService->addSitemap('https://example.com/sitemap.xml');
$robotsService->addSitemap('https://example.com/sitemap-news.xml');
```

## Clean URLs

Ensure proper disallow patterns:

```php
// Correct - blocks directory
'disallow' => ['/admin/'],

// Also blocks single file
'disallow' => ['/admin'],

// Block with wildcard
'disallow' => ['/admin/*'],
```

## Common Patterns

### Standard Website

```php
'robots' => [
    'disallow' => [
        '/admin/',
        '/api/',
        '/private/',
        '/cart/',
        '/checkout/',
        '/account/',
        '/*.pdf$',
        '/*?*',  // Block URLs with query strings
    ],
    'allow' => [
        '/api/public/',
    ],
],
```

### E-commerce Site

```php
'robots' => [
    'disallow' => [
        '/admin/',
        '/cart/',
        '/checkout/',
        '/account/',
        '/wishlist/',
        '/compare/',
        '/search/',
        '/*?sort=',
        '/*?filter=',
    ],
],
```

### Blog/Content Site

```php
'robots' => [
    'disallow' => [
        '/admin/',
        '/wp-admin/',  // If migrated from WordPress
        '/tag/',       // Avoid duplicate content
        '/author/',
        '/search/',
    ],
],
```

## Testing Robots.txt

### Google's Robots.txt Tester

Use Google Search Console to test your robots.txt.

### Programmatic Testing

```php
$robotsService = app(RobotsService::class);

// Get rules for a specific user-agent
$rules = $robotsService->getRulesForUserAgent('Googlebot');

// Check the disallow and allow arrays
$disallowedPaths = $rules['disallow'] ?? [];
$allowedPaths = $rules['allow'] ?? [];

// Get all configured user agents
$userAgents = $robotsService->getUserAgents();
```

## Caching

The route sends a `Cache-Control` header using `seo.robots.cache_ttl`. The generated content isn't cached on the server.

## Next Steps

- [XML Sitemaps](Advanced-Sitemaps) - Sitemap configuration
- [Configuration](Installation-Configuration) - Full config reference
- [Artisan Commands](Advanced-Artisan-Commands) - CLI tools
