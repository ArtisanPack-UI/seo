---
title: Helper Functions
---

# Helper Functions

ArtisanPack UI SEO provides global helper functions for common SEO tasks.

## SEO Service Helpers

### seo()

Get the main SEO service instance.

```php
$seo = seo();

// Use service methods
$meta = seo()->getMetaForModel($post);
$tags = seo()->generateMetaTags($post);
```

### seoMeta()

Get the SEO meta record for a model.

```php
$meta = seoMeta($post);

// Access properties
$title = $meta->meta_title;
$description = $meta->meta_description;
```

### seoConfig()

Get SEO configuration values.

```php
// Get single value
$siteName = seoConfig('site.name');
$maxLength = seoConfig('defaults.title_max_length');

// With default
$value = seoConfig('custom.key', 'default');
```

### seoIsEnabled()

Check if a SEO feature is enabled.

```php
if (seoIsEnabled('sitemap')) {
    // Generate sitemap
}

if (seoIsEnabled('redirects')) {
    // Handle redirects
}

if (seoIsEnabled('analysis')) {
    // Run analysis
}
```

## Text Formatting Helpers

### seoTitle()

Format a page title with site name suffix.

```php
// With default separator from config
$title = seoTitle('My Page');
// Result: "My Page | My Site"

// With custom separator
$title = seoTitle('My Page', ' - ');
// Result: "My Page - My Site"

// Without suffix
$title = seoTitle('My Page', '');
// Result: "My Page"
```

### seoDescription()

Truncate text to SEO-friendly description length.

```php
// Default 160 characters
$desc = seoDescription($longText);

// Custom length
$desc = seoDescription($longText, 150);

// With ellipsis
$desc = seoDescription($longText, 160, '...');
```

## Redirect Helpers

### seoRedirect()

Get the redirect service instance.

```php
$redirectService = seoRedirect();

// Use service methods
$redirect = seoRedirect()->findMatch('/old-path');
```

### seoFindRedirect()

Find a redirect for a given path.

```php
$redirect = seoFindRedirect('/old-page');

if ($redirect) {
    $destination = $redirect->target;
    $statusCode = $redirect->status_code;
}
```

### seoCreateRedirect()

Create a new redirect.

```php
$redirect = seoCreateRedirect(
    source: '/old-page',
    target: '/new-page',
    type: 'exact',
    statusCode: 301
);

// With all options
$redirect = seoCreateRedirect(
    source: '/blog/*',
    target: '/articles/$1',
    type: 'wildcard',
    statusCode: 301,
    isActive: true,
    notes: 'Blog migration redirect'
);
```

### seoDeleteRedirect()

Delete a redirect.

```php
// By ID
seoDeleteRedirect(123);

// By source path
seoDeleteRedirect('/old-page', byPath: true);
```

### seoRedirectStatistics()

Get redirect statistics.

```php
$stats = seoRedirectStatistics();

// Returns:
// [
//     'total' => 45,
//     'active' => 42,
//     'inactive' => 3,
//     'total_hits' => 12543,
//     'most_hit' => [...],
// ]
```

## Sitemap Helpers

### seoSitemap()

Get the sitemap service instance.

```php
$sitemapService = seoSitemap();

// Generate sitemaps
seoSitemap()->generate();
```

### seoGenerateSitemap()

Generate sitemaps.

```php
// Generate all
seoGenerateSitemap();

// Generate specific type
seoGenerateSitemap('standard');
seoGenerateSitemap('images');
seoGenerateSitemap('videos');
seoGenerateSitemap('news');
```

### seoSubmitSitemap()

Submit sitemaps to search engines.

```php
// Submit to all configured engines
seoSubmitSitemap();

// Submit to specific engine
seoSubmitSitemap('google');
seoSubmitSitemap('bing');
```

## Analysis Helpers

### seoAnalyze()

Run SEO analysis on a model.

```php
$results = seoAnalyze($post);

// Returns:
// [
//     'score' => 75,
//     'readability' => ['status' => 'pass', ...],
//     'keyword_density' => ['status' => 'warning', ...],
//     ...
// ]
```

### seoScore()

Get the SEO score for a model.

```php
$score = seoScore($post);
// Returns: 0-100
```

## Schema Helpers

### seoSchema()

Generate schema markup for a model.

```php
$schema = seoSchema($post);

// Returns JSON-LD array
// [
//     '@context' => 'https://schema.org',
//     '@type' => 'Article',
//     ...
// ]
```

### seoSchemaJson()

Get schema as JSON string.

```php
$json = seoSchemaJson($post);

// Returns JSON string ready for output
```

## URL Helpers

### seoCanonical()

Get the canonical URL for a model.

```php
$canonical = seoCanonical($post);
// Returns: "https://example.com/posts/my-post"
```

### seoUrl()

Generate an SEO-friendly URL.

```php
$url = seoUrl($post);

// With options
$url = seoUrl($post, absolute: true);
```

## Robots Helpers

### seoRobots()

Generate robots meta content for a model.

```php
$robots = seoRobots($post);
// Returns: "index, follow" or "noindex, nofollow"
```

### seoRobotsTxt()

Get the robots.txt content.

```php
$content = seoRobotsTxt();
```

## Hreflang Helpers

### seoHreflang()

Get hreflang URLs for a model.

```php
$hreflang = seoHreflang($post);

// Returns:
// [
//     'en' => 'https://example.com/post',
//     'fr' => 'https://example.fr/article',
//     'x-default' => 'https://example.com/post',
// ]
```

## Cache Helpers

### seoClearCache()

Clear SEO cache.

```php
// Clear for specific model
seoClearCache($post);

// Clear all SEO caches
seoClearCache();
```

## Usage Examples

### In Controllers

```php
class PostController extends Controller
{
    public function show(Post $post)
    {
        // Check if post should be visible
        if (!seoIsEnabled('noindex_preview') && !$post->shouldBeIndexed()) {
            abort(404);
        }

        return view('posts.show', [
            'post' => $post,
            'seoTitle' => seoTitle($post->title),
            'seoDescription' => seoDescription($post->content),
        ]);
    }
}
```

### In Views

```blade
@php
    $pageTitle = seoTitle($post->title ?? 'Home');
    $pageDesc = seoDescription($post->content ?? seoConfig('site.description'));
@endphp

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDesc }}">
```

### In Artisan Commands

```php
class GenerateSeoCommand extends Command
{
    public function handle()
    {
        if (seoIsEnabled('sitemap')) {
            seoGenerateSitemap();
            $this->info('Sitemap generated.');
        }

        if (seoIsEnabled('analysis')) {
            Post::each(function ($post) {
                seoAnalyze($post);
            });
            $this->info('Analysis complete.');
        }
    }
}
```

## Next Steps

- [Services](./services.md) - Full service documentation
- [Events](./events.md) - Event reference
- [Configuration](../installation/configuration.md) - Config options
