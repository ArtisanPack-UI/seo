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

### Available Schema Types

The following 13 schema types are available via the SchemaFactory:

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

Get all supported types programmatically:

```php
$factory = app(\ArtisanPackUI\SEO\Schema\SchemaFactory::class);
$types = $factory->getSupportedTypes();
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

- [Helper Functions](Helpers) - Helper reference
- [Events](Events) - Event reference
- [Models](Models) - Model documentation
