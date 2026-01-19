---
title: Services
---

# Services

This page documents the service classes provided by ArtisanPack UI SEO.

## SeoService

The main orchestrator service that coordinates all SEO functionality.

### Access

```php
use ArtisanPackUI\Seo\Services\SeoService;

// Via dependency injection
public function __construct(protected SeoService $seo) {}

// Via helper
$seo = seo();

// Via facade
use ArtisanPackUI\Seo\Facades\Seo;
$meta = Seo::getMetaForModel($post);

// Via container
$seo = app('seo');
```

### Methods

```php
// Get or create SEO meta for a model
$meta = $seo->getMetaForModel($post);

// Update SEO meta
$meta = $seo->updateMeta($post, [
    'meta_title' => 'Title',
    'meta_description' => 'Description',
]);

// Generate all meta tags
$tags = $seo->generateMetaTags($post);

// Generate Open Graph tags
$ogTags = $seo->generateOpenGraphTags($post);

// Generate Twitter Card tags
$twitterTags = $seo->generateTwitterCardTags($post);

// Generate schema markup
$schema = $seo->generateSchema($post);

// Get SEO data with fallbacks
$data = $seo->getSeoData($post);

// Check if model should be indexed
$indexable = $seo->shouldBeIndexed($post);
```

---

## MetaTagService

Handles basic meta tag generation.

### Access

```php
use ArtisanPackUI\Seo\Services\MetaTagService;

$metaService = app(MetaTagService::class);
```

### Methods

```php
// Generate title tag
$title = $metaService->generateTitle($post);

// Generate with suffix
$title = $metaService->generateTitle($post, ' | My Site');

// Generate description
$description = $metaService->generateDescription($post);

// Generate robots directive
$robots = $metaService->generateRobots($post);

// Generate canonical URL
$canonical = $metaService->generateCanonical($post);

// Get effective title (with fallbacks)
$title = $metaService->getEffectiveTitle($post);

// Get effective description (with fallbacks)
$description = $metaService->getEffectiveDescription($post);
```

---

## SocialMetaService

Handles Open Graph and Twitter Card generation.

### Access

```php
use ArtisanPackUI\Seo\Services\SocialMetaService;

$socialService = app(SocialMetaService::class);
```

### Methods

```php
// Generate Open Graph tags
$og = $socialService->generateOpenGraph($post);
// Returns: ['og:title' => '...', 'og:description' => '...', ...]

// Generate Twitter Card tags
$twitter = $socialService->generateTwitterCard($post);
// Returns: ['twitter:card' => '...', 'twitter:title' => '...', ...]

// Get effective OG image
$image = $socialService->getEffectiveOgImage($post);

// Get OG data array
$ogData = $socialService->getOpenGraphData($post);

// Get Twitter data array
$twitterData = $socialService->getTwitterCardData($post);
```

---

## SchemaService

Handles Schema.org JSON-LD generation.

### Access

```php
use ArtisanPackUI\Seo\Services\SchemaService;

$schemaService = app(SchemaService::class);
```

### Methods

```php
// Generate schema for a model
$schema = $schemaService->generate($post);
// Returns complete JSON-LD array

// Build specific schema type
$article = $schemaService->buildArticle($post, [
    'author' => ['@type' => 'Person', 'name' => 'Author'],
]);

$product = $schemaService->buildProduct($product, [
    'offers' => [...],
]);

$event = $schemaService->buildEvent($event, [
    'location' => [...],
]);

// Get JSON-LD string
$jsonLd = $schemaService->toJsonLd($schema);

// Get available schema types
$types = $schemaService->getAvailableTypes();
```

### Schema Builders

Each schema type has a dedicated builder:

```php
$schemaService->buildArticle($model, $data);
$schemaService->buildBlogPosting($model, $data);
$schemaService->buildProduct($model, $data);
$schemaService->buildEvent($model, $data);
$schemaService->buildOrganization($model, $data);
$schemaService->buildLocalBusiness($model, $data);
$schemaService->buildWebSite($model, $data);
$schemaService->buildWebPage($model, $data);
$schemaService->buildService($model, $data);
$schemaService->buildFAQPage($model, $data);
$schemaService->buildBreadcrumbList($model, $data);
$schemaService->buildReview($model, $data);
$schemaService->buildAggregateRating($model, $data);
```

---

## SitemapService

Handles XML sitemap generation.

### Access

```php
use ArtisanPackUI\Seo\Services\SitemapService;

$sitemapService = app('seo.sitemap');
```

### Methods

```php
// Generate all sitemaps
$sitemapService->generate();

// Generate specific type
$sitemapService->generateStandard();
$sitemapService->generateImages();
$sitemapService->generateVideos();
$sitemapService->generateNews();

// Generate sitemap index
$index = $sitemapService->generateIndex();

// Get sitemap content
$xml = $sitemapService->getContent('sitemap.xml');

// Add entry to sitemap
$sitemapService->addEntry([
    'url' => 'https://example.com/page',
    'lastmod' => now(),
    'priority' => 0.8,
    'changefreq' => 'weekly',
]);

// Remove entry
$sitemapService->removeEntry('https://example.com/page');

// Register sitemap provider
$sitemapService->registerProvider(PostSitemapProvider::class);

// Submit to search engines
$sitemapService->submit();
$sitemapService->submitToGoogle();
$sitemapService->submitToBing();
```

---

## RedirectService

Handles URL redirect management.

### Access

```php
use ArtisanPackUI\Seo\Services\RedirectService;

$redirectService = app('seo.redirect');

// Or via helper
$redirectService = seoRedirect();
```

### Methods

```php
// Create a redirect
$redirect = $redirectService->create([
    'source' => '/old',
    'target' => '/new',
    'type' => 'exact',
    'status_code' => 301,
]);

// Update a redirect
$redirect = $redirectService->update($id, [
    'target' => '/updated',
]);

// Delete a redirect
$redirectService->delete($id);

// Find matching redirect for path
$redirect = $redirectService->findMatch('/some/path');

// Get destination URL
$destination = $redirectService->getDestination('/old-path');

// Check for redirect loops
$hasLoop = $redirectService->detectLoop('/path');

// Get statistics
$stats = $redirectService->getStatistics();

// Import from CSV
$redirectService->importFromCsv($filePath);

// Export to CSV
$csv = $redirectService->exportToCsv();
```

---

## RobotsService

Handles dynamic robots.txt generation.

### Access

```php
use ArtisanPackUI\Seo\Services\RobotsService;

$robotsService = app('seo.robots');
```

### Methods

```php
// Generate robots.txt content
$content = $robotsService->generate();

// Add disallow rule
$robotsService->addDisallow('/admin');
$robotsService->addDisallow('/api', 'Googlebot');

// Add allow rule
$robotsService->addAllow('/api/public');

// Add sitemap
$robotsService->addSitemap('https://example.com/sitemap.xml');

// Set crawl delay
$robotsService->setCrawlDelay(10);

// Add bot-specific rules
$robotsService->addBotRule('GPTBot', ['disallow' => ['/']]);

// Get rules for a bot
$rules = $robotsService->getRulesForBot('Googlebot');
```

---

## HreflangService

Handles multi-language hreflang management.

### Access

```php
use ArtisanPackUI\Seo\Services\HreflangService;

$hreflangService = app(HreflangService::class);
```

### Methods

```php
// Set hreflang URLs for a model
$hreflangService->setHreflang($post, [
    'en' => 'https://example.com/post',
    'fr' => 'https://example.fr/article',
]);

// Add a single language
$hreflangService->addLanguage($post, 'de', 'https://example.de/beitrag');

// Remove a language
$hreflangService->removeLanguage($post, 'de');

// Get all hreflang URLs
$urls = $hreflangService->getHreflang($post);

// Generate hreflang tags
$tags = $hreflangService->generateTags($post);

// Validate hreflang configuration
$errors = $hreflangService->validate($post);

// Get supported locales
$locales = $hreflangService->getSupportedLocales();
```

---

## AnalysisService

Handles SEO content analysis.

### Access

```php
use ArtisanPackUI\Seo\Services\AnalysisService;

$analysisService = app('seo.analysis');
```

### Methods

```php
// Run full analysis
$results = $analysisService->analyze($post);
// Returns: ['score' => 75, 'readability' => [...], ...]

// Run specific analyzer
$readability = $analysisService->runAnalyzer('readability', $post);

// Get overall score
$score = $analysisService->getScore($post);

// Get registered analyzers
$analyzers = $analysisService->getAnalyzers();

// Register custom analyzer
$analysisService->registerAnalyzer('custom', new CustomAnalyzer());

// Clear analysis cache
$analysisService->clearCache($post);

// Refresh analysis
$results = $analysisService->refresh($post);
```

---

## CacheService

Handles SEO-specific caching.

### Access

```php
use ArtisanPackUI\Seo\Services\CacheService;

$cacheService = app('seo.cache');
```

### Methods

```php
// Get cached value
$value = $cacheService->get('meta', $post->id);

// Set cached value
$cacheService->set('meta', $post->id, $data, 3600);

// Check if cached
$exists = $cacheService->has('meta', $post->id);

// Clear specific cache
$cacheService->forget('meta', $post->id);

// Clear all caches for a model
$cacheService->clearForModel($post);

// Clear all SEO caches
$cacheService->flush();

// Get cache key
$key = $cacheService->key('meta', $post->id);
```

## Next Steps

- [Helper Functions](./helpers.md) - Helper reference
- [Events](./events.md) - Event reference
- [Models](./models.md) - Model documentation
