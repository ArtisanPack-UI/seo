---
title: API Overview
---

# API Overview

This section documents the programmatic API for ArtisanPack UI SEO, including models, services, helper functions, and events.

## Quick Reference

### Models

| Model | Purpose |
|-------|---------|
| `SeoMeta` | Stores SEO metadata for models |
| `Redirect` | URL redirect rules |
| `SitemapEntry` | Sitemap entry tracking |
| `SeoAnalysisCache` | Cached analysis results |

[View Models Documentation →](Api-Models)

### Services

| Service | Purpose |
|---------|---------|
| `SeoService` | Main orchestrator |
| `MetaTagService` | Meta tag generation |
| `SocialMetaService` | Open Graph, Twitter |
| `SchemaService` | Schema.org markup |
| `SitemapService` | Sitemap generation |
| `RedirectService` | Redirect management |
| `RobotsService` | Robots.txt generation |
| `HreflangService` | Multi-language support |
| `AnalysisService` | SEO content analysis |
| `CacheService` | Cache management |

[View Services Documentation →](Api-Services)

### Helper Functions

```php
seo()                    // Get SeoService
seoMeta($model)          // Get SeoMeta for model
seoTitle($title)         // Format page title
seoDescription($text)    // Truncate description
seoIsEnabled($feature)   // Check feature status
seoConfig($key)          // Get config value
```

[View Helper Functions Documentation →](Api-Helpers)

### Events

| Event | When Fired |
|-------|------------|
| `SeoMetaCreated` | After SEO meta created |
| `SeoMetaUpdated` | After SEO meta updated |
| `SitemapGenerated` | After sitemap generation |
| `RedirectHit` | When redirect is triggered |

[View Events Documentation →](Api-Events)

## Facades

The package provides these facades:

```php
use ArtisanPackUI\Seo\Facades\Seo;
use ArtisanPackUI\Seo\Facades\Redirect;

// Using Seo facade
$meta = Seo::getMetaForModel($post);
$tags = Seo::generateMetaTags($post);

// Using Redirect facade
$redirect = Redirect::create([...]);
$match = Redirect::findMatch('/old-path');
```

## Dependency Injection

All services can be injected via Laravel's container:

```php
use ArtisanPackUI\Seo\Services\SeoService;
use ArtisanPackUI\Seo\Services\SitemapService;

class MyController extends Controller
{
    public function __construct(
        protected SeoService $seo,
        protected SitemapService $sitemap,
    ) {}

    public function index()
    {
        $this->sitemap->generate();
    }
}
```

## Container Bindings

The package registers these bindings:

```php
// Singletons
$this->app->singleton('seo', SeoService::class);
$this->app->singleton('seo.redirect', RedirectService::class);
$this->app->singleton('seo.sitemap', SitemapService::class);
$this->app->singleton('seo.robots', RobotsService::class);
$this->app->singleton('seo.analysis', AnalysisService::class);
$this->app->singleton('seo.cache', CacheService::class);
```

Access via app helper:

```php
$seo = app('seo');
$redirectService = app('seo.redirect');
```

## Contracts/Interfaces

The package defines these contracts:

```php
namespace ArtisanPackUI\Seo\Contracts;

interface SeoableInterface
{
    public function getSeoTitle(): ?string;
    public function getSeoDescription(): ?string;
    public function getSeoImage(): ?string;
    public function getSeoUrl(): ?string;
}

interface AnalyzerInterface
{
    public function analyze($model): array;
}

interface SchemaBuilderInterface
{
    public function build($model, array $data = []): array;
}

interface SitemapProviderInterface
{
    public function getEntries(): Collection;
}
```

## Exception Classes

```php
namespace ArtisanPackUI\Seo\Exceptions;

// Thrown when redirect creates a loop
RedirectLoopException::class;

// Thrown when sitemap generation fails
SitemapGenerationException::class;

// Thrown when schema type is invalid
InvalidSchemaTypeException::class;

// Thrown when configuration is invalid
InvalidConfigurationException::class;
```

## Next Steps

- [Models](Api-Models) - Model reference
- [Services](Api-Services) - Service documentation
- [Helper Functions](Api-Helpers) - Helper reference
- [Events](Api-Events) - Event reference
