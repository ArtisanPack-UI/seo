---
title: Services
---

# Services

This page documents the service classes provided by ArtisanPack UI SEO.

## SeoService

The main orchestrator service that coordinates all SEO functionality.

### Access

```php
use ArtisanPackUI\SEO\Services\SeoService;

// Via dependency injection
public function __construct(protected SeoService $seo) {}

// Via helper
$seo = seo();

// Via container
$seo = app(SeoService::class);
```

> The `SEO` facade and the `app('seo')` binding resolve the `ArtisanPackUI\SEO\SEO` registry class (analyzers, schema types, sitemap providers), not `SeoService`.

### Methods

```php
// Get the SeoMeta record (null if none exists)
$meta = $seo->getSeoMeta($post);

// Create or update SEO meta (the model must be persisted)
$meta = $seo->updateSeoMeta($post, [
    'meta_title' => 'Title',
    'meta_description' => 'Description',
]);

// Get all SEO data (meta, Open Graph, Twitter Card, hreflang), cached
$data = $seo->getAll($post);

// Get individual pieces as DTOs
$metaTags = $seo->getMetaTags($post);       // MetaTagsDTO
$openGraph = $seo->getOpenGraph($post);     // OpenGraphDTO
$twitterCard = $seo->getTwitterCard($post); // TwitterCardDTO

// Get hreflang tags (includes x-default when configured)
$hreflang = $seo->getHreflang($post);
// Returns: [['hreflang' => 'en', 'href' => '...'], ...]

// Title helpers
$suffix = $seo->getTitleSuffix();
$separator = $seo->getTitleSeparator();
$title = $seo->buildTitle('My Post');        // "My Post | Site Name"
$title = $seo->buildTitle('My Post', false); // "My Post"

// Clear caches
$seo->clearCache($post);
$seo->clearAllCaches();
```

`getSeoMeta()` and `updateSeoMeta()` work with models that use the `HasSeo` trait and with models whose `seoMeta` relation is registered via `Model::resolveRelationUsing()` (since 1.7.1). `updateSeoMeta()` throws an `InvalidArgumentException` when the model has neither or hasn't been saved yet.

---

## MetaTagService

Handles basic meta tag generation.

### Access

```php
use ArtisanPackUI\SEO\Services\MetaTagService;

$metaService = app(MetaTagService::class);
```

### Methods

```php
// Generate tags for a model
$dto = $metaService->generate($post); // MetaTagsDTO
$dto->title;
$dto->description;
$dto->canonical;
$dto->robots;
$dto->additionalMeta; // ['author' => '...'], when the model has one

// Build a title, with or without the site name
$title = $metaService->buildTitle('My Post');
$title = $metaService->buildTitle('My Post', false);

// Build the robots directive
$robots = $metaService->buildRobotsDirective($seoMeta);

// Title parts
$suffix = $metaService->getTitleSuffix();
$separator = $metaService->getTitleSeparator();
```

`buildTitle()` leaves the suffix off when it would pass `seo.defaults.title_max_length`. Saved descriptions keep their full length, and generated ones stay within `seo.defaults.description_max_length`.

---

## SocialMetaService

Handles Open Graph and Twitter Card generation.

### Access

```php
use ArtisanPackUI\SEO\Services\SocialMetaService;

$socialService = app(SocialMetaService::class);
```

### Methods

```php
// Generate Open Graph tags
$og = $socialService->generateOpenGraph($post); // OpenGraphDTO
$og->image;
$og->imageWidth;
$og->imageHeight;
$og->imageAlt;
$og->publishedTime; // Only for the article type
$og->modifiedTime;
$og->toArray();     // ['og:title' => '...', 'og:image:width' => 1200, ...]

// Generate Twitter Card tags
$twitter = $socialService->generateTwitterCard($post); // TwitterCardDTO
$twitter->imageAlt;

// Resolve the OG type, inferring it when none is saved
$type = $socialService->resolveOgType($post);

// Look up the known details of an image
$details = $socialService->resolveImageDetails($url, $mediaId, $title);
// Returns: ['width' => 1200, 'height' => 630, 'alt' => '...']

// Get a model's article times
$times = $socialService->resolveArticleTimes($post);
// Returns: ['published' => '...', 'modified' => '...']
```

---

## SchemaService

Handles Schema.org JSON-LD generation.

### Access

```php
use ArtisanPackUI\SEO\Services\SchemaService;

$schemaService = app(SchemaService::class);
```

### Methods

```php
// Generate schema for a model
$schema = $schemaService->generate($post);
// Returns complete JSON-LD array

// Site-wide schemas
$organization = $schemaService->generateOrganizationSchema();
$website = $schemaService->generateWebsiteSchema();
$breadcrumbs = $schemaService->generateBreadcrumbs($items);

// Get JSON-LD string
$jsonLd = $schemaService->toJsonLd($schema);
```

### Available Schema Types

The following 15 schema types are available via the SchemaFactory:

- `Organization` - Company/organization info
- `LocalBusiness` - Local business details
- `WebSite` - Website-level schema
- `WebPage` - Individual page schema
- `Article` - News or scholarly articles
- `BlogPosting` - Blog post entries
- `Product` - E-commerce products
- `Service` - Professional services
- `Event` - Events and occurrences
- `FAQPage` - FAQ pages
- `BreadcrumbList` - Navigation breadcrumbs
- `Review` - Reviews and ratings
- `AggregateRating` - Aggregate rating data
- `SoftwareSourceCode` - Code libraries and packages
- `SoftwareApplication` - Applications and tools

Get all supported types programmatically:

```php
$factory = app(\ArtisanPackUI\SEO\Schema\SchemaFactory::class);
$types = $factory->getSupportedTypes();
```

### Schema Type Definitions (v1.1.0)

Get rich metadata for all schema types, including descriptions and field definitions for building dynamic editor forms:

```php
$factory = app(\ArtisanPackUI\SEO\Schema\SchemaFactory::class);

// Get type definitions with descriptions and field metadata
$definitions = $factory->getTypeDefinitions();
// Returns: [['name' => 'Article', 'label' => 'Article', 'description' => '...', 'fields' => [...]]]
```

Or via the API:

```
GET /api/seo/schema/types
```

See [Schema.org / JSON-LD — Schema Type Definitions API](Usage-Schema#schema-type-definitions-api) for full documentation.

---

## SitemapService

Handles XML sitemap generation.

### Access

```php
use ArtisanPackUI\SEO\Services\SitemapService;

$sitemapService = app(SitemapService::class);
```

### Methods

```php
// Generate all types, or one type and page
$xml = $sitemapService->generate();
$xml = $sitemapService->generate('post', 2);

// One type and page, or null when there's nothing to list
$xml = $sitemapService->generateTypeSitemap('post', 2);

// Specialized sitemaps
$xml = $sitemapService->generateImages();
$xml = $sitemapService->generateVideos();
$xml = $sitemapService->generateNews();

// Sitemap index
$index = $sitemapService->generateIndex();
$needed = $sitemapService->needsIndex();

// llms.txt
$manifest = $sitemapService->generateLlmsTxt();

// Types and pages
$types = $sitemapService->getTypes();
$pages = $sitemapService->getTotalPages('post');

// Register a sitemap provider
$sitemapService->registerProvider('archive', new ArchiveSitemapProvider());

// Ping configured search engines (none by default)
$results = $sitemapService->submit();

// Submit URLs to IndexNow
$results = $sitemapService->submitIndexNow($urls);

// IndexNow and pings together, each behind its own setting
$results = $sitemapService->notifyOnPublish($urls);

// Clear every cached sitemap
$sitemapService->clearCache();
```

---

## RedirectService

Handles URL redirect management.

### Access

```php
use ArtisanPackUI\SEO\Services\RedirectService;

$redirectService = app(RedirectService::class);

// Or via helper
$redirectService = seoRedirect();
```

### Methods

```php
// Create a redirect
$redirect = $redirectService->create([
    'from_path' => '/old',
    'to_path' => '/new',
    'match_type' => 'exact',
    'status_code' => 301,
]);

// Update a redirect
$redirect = $redirectService->update($redirect, [
    'to_path' => '/updated',
]);

// Delete a redirect
$redirectService->delete($redirect);

// Find matching redirect for path
$redirect = $redirectService->findMatch('/some/path');

// Check whether a redirect is part of a chain
$hasChain = $redirectService->checkForChains($redirect);

// Get statistics
$stats = $redirectService->getStatistics();

// Import and export
$results = $redirectService->import($rows);
$rows = $redirectService->export();
```

---

## RobotsService

Handles dynamic robots.txt generation.

### Access

```php
use ArtisanPackUI\SEO\Services\RobotsService;

$robotsService = app(RobotsService::class);
```

### Methods

```php
// Generate robots.txt content
$content = $robotsService->generate();

// Add disallow rule (default user-agent: *)
$robotsService->disallow('/admin');
$robotsService->disallow('/api', 'Googlebot');

// Add allow rule
$robotsService->allow('/api/public');

// Set crawl delay
$robotsService->crawlDelay(10);
$robotsService->crawlDelay(5, 'Bingbot');

// Add sitemap URL
$robotsService->addSitemap('https://example.com/sitemap.xml');

// Set host directive
$robotsService->setHost('example.com');

// Get rules for a user-agent
$rules = $robotsService->getRulesForUserAgent('Googlebot');

// Get all user agents with rules
$userAgents = $robotsService->getUserAgents();

// Clear all rules
$robotsService->clearRules();

// Remove rules for specific user-agent
$robotsService->removeUserAgent('GPTBot');

// Check if enabled
$enabled = $robotsService->isEnabled();
$routeEnabled = $robotsService->isRouteEnabled();
$staticEnabled = $robotsService->isStaticWriteEnabled();

// Bring public/robots.txt in line with the settings
$status = $robotsService->syncStaticFile();
// Returns written, unchanged, removed, disabled, or unmanaged
```

Call `syncStaticFile()` from a console command or queued job, because the `Sitemap:` line uses the current host. It throws a `RuntimeException` if the file can't be removed. See [Writing a Static File](Advanced-Robots#writing-a-static-file).

---

## HreflangService

Handles multi-language hreflang management.

### Access

```php
use ArtisanPackUI\SEO\Services\HreflangService;

$hreflangService = app(HreflangService::class);
```

### Methods

```php
// Get hreflang tags for a model (returns array ready for rendering)
$tags = $hreflangService->getHreflangTags($post);
// Returns: [['hreflang' => 'en', 'href' => '...'], ...]

// Set alternate URL for a specific locale (requires SeoMeta)
$seoMeta = $post->getOrCreateSeoMeta();
$hreflangService->setAlternateUrl($seoMeta, 'en', 'https://example.com/post');
$hreflangService->setAlternateUrl($seoMeta, 'fr', 'https://example.fr/article');

// Set multiple alternate URLs at once
$hreflangService->setAlternateUrls($seoMeta, [
    'en' => 'https://example.com/post',
    'fr' => 'https://example.fr/article',
    'de' => 'https://example.de/beitrag',
], replace: false); // false = merge, true = replace all

// Remove an alternate URL
$hreflangService->removeAlternateUrl($seoMeta, 'de');

// Clear all alternate URLs
$hreflangService->clearAlternateUrls($seoMeta);

// Validate a locale code
$isValid = $hreflangService->validateLocale('en-US'); // Returns bool

// Get available locales for selection
$locales = $hreflangService->getAvailableLocales();
// Returns: [['value' => 'en', 'label' => 'English'], ...]

// Check if model has hreflang data
$hasData = $hreflangService->hasHreflangData($post);

// Get hreflang count
$count = $hreflangService->getHreflangCount($post);

// Check if hreflang is enabled
$enabled = $hreflangService->isEnabled();

// Get default locale
$defaultLocale = $hreflangService->getDefaultLocale();
```

---

## AnalysisService

Handles SEO content analysis.

### Access

```php
use ArtisanPackUI\SEO\Services\AnalysisService;

$analysisService = app(AnalysisService::class);
```

### Methods

```php
// Run full analysis (uses the persisted focus keyword and the cache)
$result = $analysisService->analyze($post); // AnalysisResultDTO
$result->overallScore;   // 75
$result->getGrade();     // Letter grade
$result->issues;         // Also ->suggestions, ->passedChecks, getErrors(), getWarnings()

// Override the focus keyword and skip the cache
$result = $analysisService->analyze($post, 'laravel seo', useCache: false);

// Manage analyzers
$analyzers = $analysisService->getAnalyzers(); // array<string, AnalyzerContract>
$analysisService->registerAnalyzer('custom', new CustomAnalyzer());
$analysisService->unregisterAnalyzer('custom');
$analysisService->hasAnalyzer('readability');
$contentAnalyzers = $analysisService->getAnalyzersByCategory('content');

// Category weights used for the overall score (readability, keyword, meta, content)
$analysisService->setCategoryWeights(['readability' => 30, 'keyword' => 25]);
$weights = $analysisService->getCategoryWeights();
$weight = $analysisService->getCategoryWeight('readability');

// Cache management
$analysisService->clearCache($post);           // bool
$cleared = $analysisService->clearAllCaches(); // int, records cleared

// Aggregate statistics across cached analyses
$stats = $analysisService->getStatistics();
// Returns: ['total' => ..., 'good_count' => ..., 'average_score' => ..., ...]
```

---

## CacheService

Handles SEO-specific caching.

### Access

```php
use ArtisanPackUI\SEO\Services\CacheService;

$cacheService = app(CacheService::class);
```

### Methods

```php
// Read and write
$value = $cacheService->get('key');
$cacheService->put('key', $data, 3600);
$value = $cacheService->remember('key', fn () => $data);
$cacheService->forget('key');

// Clear caches for a model
$cacheService->clearMetaCache($post);
$cacheService->clearAnalysisCache($post);
$cacheService->clearAllForModel($post);

// Clear all SEO caches
$cacheService->clearAll();

// Settings
$enabled = $cacheService->isEnabled();
$ttl = $cacheService->getTtl();
$prefix = $cacheService->getPrefix();
```

## Next Steps

- [Helper Functions](Api-Helpers) - Helper reference
- [Filter Hooks](Api-Filter-Hooks) - Filter reference
- [Models](Api-Models) - Model documentation
