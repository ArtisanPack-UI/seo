---
title: XML Sitemaps
---

# XML Sitemaps

ArtisanPack UI SEO provides comprehensive XML sitemap generation including standard sitemaps, image sitemaps, video sitemaps, and news sitemaps.

## Overview

XML sitemaps help search engines discover and index your content. The package supports:

- **Standard Sitemaps** - URLs with priority and change frequency
- **Image Sitemaps** - For image discovery
- **Video Sitemaps** - For video content
- **News Sitemaps** - For news articles
- **Sitemap Index** - For large sites with multiple sitemaps

## Configuration

```php
// In config/seo.php
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
| `max_urls` | Maximum URLs per sitemap file |
| `cache` | Enable sitemap caching |
| `cache_ttl` | Cache time-to-live in seconds |
| `default_priority` | Default URL priority (0.0-1.0) |
| `default_changefreq` | Default change frequency |
| `types` | Enable/disable sitemap types |

## Generating Sitemaps

### Using Artisan Command

```bash
# Generate all enabled sitemaps
php artisan seo:generate-sitemap

# Generate specific type
php artisan seo:generate-sitemap --type=standard
php artisan seo:generate-sitemap --type=image
php artisan seo:generate-sitemap --type=video
php artisan seo:generate-sitemap --type=news
```

### Programmatically

```php
use ArtisanPackUI\Seo\Services\SitemapService;

$sitemapService = app('seo.sitemap');

// Generate all sitemaps
$sitemapService->generate();

// Generate specific type
$sitemapService->generateStandard();
$sitemapService->generateImages();
$sitemapService->generateVideos();
$sitemapService->generateNews();

// Generate sitemap index
$sitemapService->generateIndex();
```

### Using Helper

```php
seoGenerateSitemap();
seoGenerateSitemap('standard');
```

## Sitemap Providers

Register models to be included in sitemaps using providers.

### Creating a Provider

```php
namespace App\Seo\Providers;

use ArtisanPackUI\Seo\Contracts\SitemapProviderInterface;
use App\Models\Post;
use Illuminate\Support\Collection;

class PostSitemapProvider implements SitemapProviderInterface
{
    public function getEntries(): Collection
    {
        return Post::query()
            ->published()
            ->with('seoMeta')
            ->get()
            ->filter(fn ($post) => $post->shouldBeInSitemap())
            ->map(fn ($post) => [
                'url' => $post->url,
                'lastmod' => $post->updated_at,
                'priority' => $post->seoMeta?->sitemap_priority ?? 0.5,
                'changefreq' => $post->seoMeta?->sitemap_changefreq ?? 'weekly',
                'images' => $this->getImages($post),
            ]);
    }

    protected function getImages($post): array
    {
        return $post->images->map(fn ($image) => [
            'loc' => $image->url,
            'title' => $image->title,
            'caption' => $image->caption,
        ])->toArray();
    }
}
```

### Registering Providers

```php
// In a service provider
use ArtisanPackUI\Seo\Services\SitemapService;

public function boot(): void
{
    $sitemap = app(SitemapService::class);

    $sitemap->registerProvider(PostSitemapProvider::class);
    $sitemap->registerProvider(ProductSitemapProvider::class);
    $sitemap->registerProvider(CategorySitemapProvider::class);
}
```

## Model Integration

Models with the `HasSeo` trait can automatically be included in sitemaps.

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

// Get sitemap priority
$post->getSitemapPriority();

// Get change frequency
$post->getSitemapChangefreq();

// Get last modification date
$post->getSitemapLastmod();
```

## Sitemap Types

### Standard Sitemap

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/posts/my-post</loc>
        <lastmod>2024-01-15T10:30:00+00:00</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
</urlset>
```

### Image Sitemap

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

For large sites with multiple sitemaps:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>https://example.com/sitemap-standard.xml</loc>
        <lastmod>2024-01-15T10:30:00+00:00</lastmod>
    </sitemap>
    <sitemap>
        <loc>https://example.com/sitemap-images.xml</loc>
        <lastmod>2024-01-15T10:30:00+00:00</lastmod>
    </sitemap>
</sitemapindex>
```

## Serving Sitemaps

### Via Routes

The package can register sitemap routes:

```php
// In config/seo.php
'routes' => [
    'sitemap' => true,
],

// Registers:
// GET /sitemap.xml
// GET /sitemap-{type}.xml
```

### Via Controller

```php
use ArtisanPackUI\Seo\Services\SitemapService;

class SitemapController extends Controller
{
    public function __construct(
        protected SitemapService $sitemap
    ) {}

    public function index()
    {
        return response($this->sitemap->getIndexContent())
            ->header('Content-Type', 'application/xml');
    }

    public function show(string $type)
    {
        return response($this->sitemap->getContent($type))
            ->header('Content-Type', 'application/xml');
    }
}
```

## Submitting Sitemaps

### Using Artisan Command

```bash
# Submit to all search engines
php artisan seo:submit-sitemap

# Submit to specific engine
php artisan seo:submit-sitemap --engine=google
php artisan seo:submit-sitemap --engine=bing
```

### Programmatically

```php
$sitemapService->submit();
$sitemapService->submitToGoogle();
$sitemapService->submitToBing();
```

### Using Helper

```php
seoSubmitSitemap();
seoSubmitSitemap('google');
```

## Scheduling

Add sitemap generation to your scheduler:

```php
// In routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('seo:generate-sitemap')->daily();
Schedule::command('seo:submit-sitemap')->weekly();
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

## Priority Guidelines

| Priority | Use Case |
|----------|----------|
| 1.0 | Homepage |
| 0.8 | Important pages (products, services) |
| 0.6 | Category pages |
| 0.5 | Regular content (default) |
| 0.3 | Archive pages |
| 0.1 | Low-priority pages |

## Caching

Sitemaps are cached for performance:

```php
// Clear sitemap cache
seoSitemap()->clearCache();

// Regenerate with fresh cache
seoSitemap()->generate();
```

## Events

Listen for sitemap events:

```php
use ArtisanPackUI\Seo\Events\SitemapGenerated;
use ArtisanPackUI\Seo\Events\SitemapSubmitted;

Event::listen(SitemapGenerated::class, function ($event) {
    Log::info("Sitemap generated: {$event->type}");
});

Event::listen(SitemapSubmitted::class, function ($event) {
    if ($event->success) {
        Log::info("Sitemap submitted to {$event->engine}");
    }
});
```

## Next Steps

- [Robots.txt](Robots) - Configure robots.txt
- [Artisan Commands](Artisan-Commands) - CLI reference
- [Caching](Caching) - Cache configuration
