---
title: XML Sitemaps
---

# XML Sitemaps

ArtisanPack UI SEO provides XML sitemap generation, including standard, image, video, and news sitemaps.

## Overview

XML sitemaps help search engines discover and index your content. The package supports:

- **Standard Sitemaps** - URLs with their last modified date
- **Image Sitemaps** - For image discovery, on by default
- **Video Sitemaps** - For video content
- **News Sitemaps** - For news articles
- **Sitemap Index** - For sites with several sitemap types or pages

## Configuration

```php
// In config/seo.php
'sitemap' => [
    'enabled'           => env('SEO_SITEMAP_ENABLED', true),
    'route_enabled'     => true,
    'route_path'        => 'sitemap.xml',
    'max_urls_per_file' => 10000,
    'default_frequency' => null,
    'default_priority'  => null,
    'cache_enabled'     => true,
    'cache_ttl'         => 3600,
    'submit_enabled'    => env('SEO_SITEMAP_SUBMIT_ENABLED', false),
    'submit_timeout'    => 10,
    'providers'         => [],
    'types' => [
        'standard' => true,
        'image'    => true,
        'video'    => false,
        'news'     => false,
    ],
    'news' => [
        'types'        => ['article', 'post', 'news'],
        'max_age_days' => 2,
    ],
    'search_engines' => [],
],
```

| Option | Description |
|--------|-------------|
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
| `types` | Enable or disable the specialized sitemaps |
| `search_engines` | Ping URLs keyed by engine name, empty by default |

## Priority and Change Frequency

`priority` and `changefreq` are left out of a sitemap unless they're set for a URL. Identical values on every URL tell crawlers nothing.

A URL's value comes from its SEO meta, then the site-wide `default_priority` or `default_frequency` setting. A blank field inherits a site-wide default, so setting one means no page can opt out of it.

```php
$post->updateSeoMeta([
    'sitemap_priority' => 0.8,
    'sitemap_changefreq' => 'daily',
]);
```

## Generating Sitemaps

The routes generate sitemaps on request, so most sites don't need to run anything.

### Using the Artisan Command

```bash
# Write every type to a directory
php artisan seo:generate-sitemap --output=public/sitemaps

# Write one type
php artisan seo:generate-sitemap --type=post --output=public/sitemaps
```

See [Artisan Commands](Advanced-Artisan-Commands) for all options.

### Programmatically

```php
use ArtisanPackUI\SEO\Services\SitemapService;

$sitemapService = app(SitemapService::class);

// All types
$xml = $sitemapService->generate();

// One type and page
$xml = $sitemapService->generate('post', 2);

// One type and page, or null when there's nothing to list
$xml = $sitemapService->generateTypeSitemap('post', 2);

// Specialized sitemaps
$xml = $sitemapService->generateImages();
$xml = $sitemapService->generateVideos();
$xml = $sitemapService->generateNews();

// Sitemap index
$xml = $sitemapService->generateIndex();
```

## How URLs Are Resolved

Sitemap entries use the same resolver as canonical tags, Open Graph, and schema. It tries these in order:

1. A saved `canonical_url`
2. `getPublicUrl()` from the `HasPublicUrl` contract
3. `getUrl()` on the model
4. A `permalink` attribute
5. A `{model}.show` route

A model with none of these gets no sitemap entry. URLs are never guessed from a model's slug. A relative URL is built from `app.url`. See [Model Integration](Usage-Model-Integration#public-urls).

## Sitemap Providers

Providers add URLs that have no model, such as archive pages.

### Creating a Provider

```php
namespace App\Seo\Providers;

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use Illuminate\Support\Collection;

class ArchiveSitemapProvider implements SitemapProviderContract
{
    public function getUrls(): Collection
    {
        return collect([
            [
                'loc'         => url('/blog'),
                'lastmod'     => now(),
                'title'       => 'Blog',
                'description' => 'All of our articles.',
            ],
        ]);
    }

    public function getChangeFrequency(): ?string
    {
        return null;
    }

    public function getPriority(): ?float
    {
        return null;
    }

    public function getType(): string
    {
        return 'archive';
    }
}
```

Return `null` from `getChangeFrequency()` or `getPriority()` to leave them out of the sitemap. A `changefreq` or `priority` on an individual URL takes precedence. `title` and `description` are used by [llms.txt](Advanced-Llms-Txt).

### Registering Providers

```php
// In config/seo.php
'sitemap' => [
    'providers' => [
        'archive' => \App\Seo\Providers\ArchiveSitemapProvider::class,
    ],
],
```

Or in a service provider:

```php
use ArtisanPackUI\SEO\Services\SitemapService;

public function boot(): void
{
    app(SitemapService::class)->registerProvider('archive', new ArchiveSitemapProvider());
}
```

A provider's sitemap lists stored entries of its type, followed by its URLs that aren't already stored. Its pages stay within `max_urls_per_file`, and the index lists each one.

## Model Integration

Models with the `HasSeo` trait are tracked automatically.

### Sitemap Settings

```php
$post->updateSeoMeta([
    'exclude_from_sitemap' => false,
    'sitemap_priority' => 0.8,
    'sitemap_changefreq' => 'daily',
]);
```

### Model Methods

```php
// Check if model should be in sitemap
$post->shouldBeInSitemap();

// Get priority and change frequency, null when not set
$post->getSitemapPriority();
$post->getSitemapChangefreq();

// Get the sitemap type, images, and videos
$post->getSitemapType();
$post->getSitemapImages();
$post->getSitemapVideos();
```

Override `getSitemapImages()` to list a page's images. By default it lists the model's SEO image, leaving out `seo.open_graph.default_image` because that isn't the page's own image.

## Sitemap Types

### Standard Sitemap

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/posts/my-post</loc>
        <lastmod>2024-01-15T10:30:00+00:00</lastmod>
    </url>
</urlset>
```

### Image Sitemap

The image sitemap only joins the index when an indexable entry has images, so a site without images keeps its plain sitemap. Set `seo.sitemap.types.image` to `false` to turn it off.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    <url>
        <loc>https://example.com/posts/my-post</loc>
        <image:image>
            <image:loc>https://example.com/images/photo.jpg</image:loc>
            <image:title>Photo Title</image:title>
            <image:caption>Photo description</image:caption>
        </image:image>
    </url>
</urlset>
```

### Video Sitemap

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
    <url>
        <loc>https://example.com/videos/tutorial</loc>
        <video:video>
            <video:thumbnail_loc>https://example.com/thumb.jpg</video:thumbnail_loc>
            <video:title>Tutorial Video</video:title>
            <video:description>Learn how to...</video:description>
            <video:content_loc>https://example.com/video.mp4</video:content_loc>
            <video:duration>300</video:duration>
        </video:video>
    </url>
</urlset>
```

### News Sitemap

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
    <url>
        <loc>https://example.com/news/breaking-story</loc>
        <news:news>
            <news:publication>
                <news:name>My News Site</news:name>
                <news:language>en</news:language>
            </news:publication>
            <news:publication_date>2024-01-15</news:publication_date>
            <news:title>Breaking Story Headline</news:title>
        </news:news>
    </url>
</urlset>
```

### Sitemap Index

`/sitemap.xml` serves an index when there's more than one type, a provider, more than one page, images to list, or an enabled video or news sitemap. The index lists per-type children, and every image sitemap page:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>https://example.com/sitemap-post-1.xml</loc>
        <lastmod>2024-01-15T10:30:00+00:00</lastmod>
    </sitemap>
    <sitemap>
        <loc>https://example.com/sitemap-images.xml</loc>
        <lastmod>2024-01-15T10:30:00+00:00</lastmod>
    </sitemap>
</sitemapindex>
```

## Serving Sitemaps

The package registers these routes when `seo.sitemap.route_enabled` is true:

| Route | Serves |
|-------|--------|
| `/sitemap.xml` | The sitemap index, or the plain sitemap when no index is needed |
| `/sitemap-{page}.xml` | All types together, kept for URLs already submitted to search consoles |
| `/sitemap-{type}-{page}.xml` | One type |
| `/sitemap-images.xml`, `/sitemap-images-{page}.xml` | Images, when enabled |
| `/sitemap-videos.xml`, `/sitemap-videos-{page}.xml` | Videos, when enabled |
| `/sitemap-news.xml`, `/sitemap-news-{page}.xml` | News, when enabled |

A type must match `[A-Za-z0-9_.-]+`, or its child can't be reached. An empty `sitemap-{type}-{page}.xml` returns a 404, except page 1 of a type with a registered provider. A stored type named `images`, `videos`, or `news` takes over its numbered URLs.

## Submitting Sitemaps

No search engine is pinged by default. Google retired its ping endpoint in 2023 and Bing's returns 410 Gone. Instead:

- List your sitemap in robots.txt, which the package does automatically
- Add it in each engine's webmaster tools
- Use [IndexNow](Advanced-Indexnow) to announce updates

For an engine that still accepts pings, add it to `search_engines`:

```php
'search_engines' => [
    'custom' => 'https://search.example.com/ping?sitemap=%s',
],
```

```bash
php artisan seo:submit-sitemap
php artisan seo:submit-sitemap --engine=custom
```

```php
$results = app(SitemapService::class)->submit();
```

### Notifying on Publish

`notifyOnPublish()` runs IndexNow when `seo.indexnow.enabled` is on, and sitemap pings when `seo.sitemap.submit_enabled` is on. Call it from a queued job:

```php
app(SitemapService::class)->notifyOnPublish($post->canonical_url);
```

## Change Frequency Values

| Value | Description |
|-------|-------------|
| `always` | Changes constantly |
| `hourly` | Changes every hour |
| `daily` | Changes every day |
| `weekly` | Changes every week |
| `monthly` | Changes every month |
| `yearly` | Changes every year |
| `never` | Archived content |

## Caching

Sitemaps are cached for performance. Cached XML is invalidated automatically whenever a model tracked by `HasSeo` is saved, force deleted, or restored, so the next request regenerates fresh output. You don't need to run `clearCache()` yourself after content changes. `seo:generate-sitemap --output` also clears the cache before regenerating, so the cached copy is primed with the current content.

```php
use ArtisanPackUI\SEO\Services\SitemapService;

// Manually clear the sitemap cache (e.g. after a bulk import that bypasses model events)
app(SitemapService::class)->clearCache();
```

Clearing writes a new generation token instead of deleting keys one by one, so it is a single cache write. Pages for deleted content stop being served too. See [Caching](Advanced-Caching) for the key format.

## Filtering Entries

Use the `ap.seo.sitemapEntries` filter to add, remove, or reorder entries. See [Filter Hooks](Api-Filter-Hooks#apseositemapentries).

## Next Steps

- [Robots.txt](Advanced-Robots) - Configure robots.txt
- [Artisan Commands](Advanced-Artisan-Commands) - CLI reference
- [Caching](Advanced-Caching) - Cache configuration
