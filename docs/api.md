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
seoRedirect()            // Get RedirectService
apSeoAddSchema($schema)  // Add a schema to the page
```

[View Helper Functions Documentation →](Api-Helpers)

### Events

The package doesn't dispatch Laravel events. The Livewire components dispatch browser events.

[View Events Documentation →](Api-Events)

### Filter Hooks

| Filter | When Fired | Since |
|--------|------------|-------|
| `ap.seo.metaTags` | Before meta tags are rendered | 1.3.0 |
| `ap.seo.sitemapEntries` | Before sitemap entries are serialized | 1.3.0 |
| `ap.seo.llmsTxtEntries` | Before llms.txt entries are rendered | 1.4.0 |
| `ap.seo.analysisContent` | Before content reaches the analyzers | 1.7.0 |

[View Filter Hooks Documentation →](Api-Filter-Hooks)

## Facades

The `SEO` facade resolves the `ArtisanPackUI\SEO\SEO` registry class, which holds analyzers, schema types, and sitemap providers:

```php
use ArtisanPackUI\SEO\Facades\SEO;

SEO::registerAnalyzer('custom', CustomAnalyzer::class);
SEO::registerSchemaType('Recipe', RecipeSchema::class);
SEO::registerSitemapProvider('archive', ArchiveSitemapProvider::class);

$title = SEO::title('My Page');
$enabled = SEO::isEnabled('sitemap');
```

For everything else, use the `seo()` helper or the services.

## Dependency Injection

All services can be injected via Laravel's container:

```php
use ArtisanPackUI\SEO\Services\SeoService;
use ArtisanPackUI\SEO\Services\SitemapService;

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

Services are singletons bound by class name, so resolve them with `app(SitemapService::class)`. The only string binding is `seo`, which resolves the `SEO` registry class.

## Contracts

| Contract | Purpose |
|----------|---------|
| `AnalyzerContract` | Custom analyzers: `analyze()`, `getName()`, `getCategory()`, `getWeight()` |
| `SchemaTypeContract` | Schema types, usually through `AbstractSchema` |
| `SitemapProviderContract` | Sitemap providers |
| `HasPublicUrl` | Declare a model's public URL |
| `SeoAnalyzableContent` | Supply a model's full HTML to analyzers |
| `FeedProviderContract` | RSS and Atom feed content |
| `IndexNowKeyProviderContract` | Supply the IndexNow key |
| `OgImageRendererContract` | Custom OG image renderer |

All contracts are in the `ArtisanPackUI\SEO\Contracts` namespace.

## Next Steps

- [Models](Api-Models) - Model reference
- [Services](Api-Services) - Service documentation
- [Helper Functions](Api-Helpers) - Helper reference
- [Events](Api-Events) - Event reference
