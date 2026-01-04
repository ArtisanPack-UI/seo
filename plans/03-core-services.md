# Core Services

**Purpose:** Define the main service classes and their responsibilities
**Last Updated:** January 3, 2026

---

## Overview

The SEO package uses a service-oriented architecture with specialized services for each domain:

| Service | Purpose |
|---------|---------|
| `SeoService` | Main orchestrator, facade target |
| `MetaTagService` | Generate HTML meta tags |
| `SocialMetaService` | Open Graph, Twitter, Pinterest, Slack |
| `SchemaService` | Schema.org structured data |
| `SitemapService` | Sitemap generation and submission |
| `RedirectService` | Redirect matching and management |
| `AnalysisService` | SEO analysis and scoring |
| `HreflangService` | Multi-language support |
| `CacheService` | SEO-specific caching |

---

## SeoService

The main orchestrator service that coordinates all other services.

```php
<?php

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\DTOs\MetaTagsDTO;
use ArtisanPackUI\SEO\DTOs\OpenGraphDTO;
use ArtisanPackUI\SEO\DTOs\SchemaDTO;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SeoService
{
    public function __construct(
        protected MetaTagService $metaTagService,
        protected SocialMetaService $socialMetaService,
        protected SchemaService $schemaService,
        protected HreflangService $hreflangService,
        protected CacheService $cacheService,
    ) {}

    /**
     * Get all SEO data for a model.
     */
    public function getAll(Model $model): array
    {
        $cacheKey = $this->cacheService->getMetaCacheKey($model);

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($model) {
            return [
                'meta' => $this->getMetaTags($model),
                'openGraph' => $this->getOpenGraph($model),
                'twitterCard' => $this->getTwitterCard($model),
                'schema' => $this->getSchema($model),
                'hreflang' => $this->getHreflang($model),
            ];
        });
    }

    /**
     * Get meta tags for a model.
     */
    public function getMetaTags(Model $model): MetaTagsDTO
    {
        $seoMeta = $this->getSeoMeta($model);
        return $this->metaTagService->generate($model, $seoMeta);
    }

    /**
     * Get Open Graph tags for a model.
     */
    public function getOpenGraph(Model $model): OpenGraphDTO
    {
        $seoMeta = $this->getSeoMeta($model);
        return $this->socialMetaService->generateOpenGraph($model, $seoMeta);
    }

    /**
     * Get Twitter Card tags for a model.
     */
    public function getTwitterCard(Model $model): array
    {
        $seoMeta = $this->getSeoMeta($model);
        return $this->socialMetaService->generateTwitterCard($model, $seoMeta);
    }

    /**
     * Get Pinterest meta for a model.
     */
    public function getPinterest(Model $model): array
    {
        $seoMeta = $this->getSeoMeta($model);
        return $this->socialMetaService->generatePinterest($model, $seoMeta);
    }

    /**
     * Get Slack meta for a model.
     */
    public function getSlack(Model $model): array
    {
        $seoMeta = $this->getSeoMeta($model);
        return $this->socialMetaService->generateSlack($model, $seoMeta);
    }

    /**
     * Get schema.org structured data for a model.
     */
    public function getSchema(Model $model): array
    {
        $seoMeta = $this->getSeoMeta($model);
        return $this->schemaService->generate($model, $seoMeta);
    }

    /**
     * Get hreflang tags for a model.
     */
    public function getHreflang(Model $model): array
    {
        $seoMeta = $this->getSeoMeta($model);
        return $this->hreflangService->generate($seoMeta);
    }

    /**
     * Get or create SeoMeta for a model.
     */
    public function getSeoMeta(Model $model): ?SeoMeta
    {
        if (method_exists($model, 'seoMeta')) {
            return $model->seoMeta;
        }

        return null;
    }

    /**
     * Update SEO meta for a model.
     */
    public function updateSeoMeta(Model $model, array $data): SeoMeta
    {
        $seoMeta = $model->seoMeta ?? new SeoMeta([
            'seoable_type' => get_class($model),
            'seoable_id' => $model->getKey(),
        ]);

        $seoMeta->fill($data);
        $seoMeta->save();

        // Clear cache
        $this->cacheService->clearMetaCache($model);

        // Trigger analysis if enabled
        if (config('seo.analysis.auto_analyze', true)) {
            dispatch(new \ArtisanPackUI\SEO\Jobs\AnalyzeContentJob($model));
        }

        return $seoMeta;
    }

    /**
     * Get the default title suffix.
     */
    public function getTitleSuffix(): string
    {
        return config('seo.defaults.title_suffix', config('app.name', ''));
    }

    /**
     * Get the title separator.
     */
    public function getTitleSeparator(): string
    {
        return config('seo.defaults.title_separator', ' | ');
    }

    /**
     * Build a full page title.
     */
    public function buildTitle(string $title, bool $includeSuffix = true): string
    {
        if (!$includeSuffix) {
            return $title;
        }

        $suffix = $this->getTitleSuffix();
        $separator = $this->getTitleSeparator();

        return $title . $separator . $suffix;
    }

    protected function getCacheTtl(): int
    {
        return config('seo.cache.ttl', 3600);
    }
}
```

---

## MetaTagService

Generates HTML meta tags.

```php
<?php

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\DTOs\MetaTagsDTO;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

class MetaTagService
{
    public function generate(Model $model, ?SeoMeta $seoMeta): MetaTagsDTO
    {
        $title = $this->resolveTitle($model, $seoMeta);
        $description = $this->resolveDescription($model, $seoMeta);
        $canonical = $this->resolveCanonical($model, $seoMeta);
        $robots = $this->resolveRobots($seoMeta);

        return new MetaTagsDTO(
            title: $title,
            description: $description,
            canonical: $canonical,
            robots: $robots,
            additionalMeta: $this->getAdditionalMeta($model, $seoMeta),
        );
    }

    protected function resolveTitle(Model $model, ?SeoMeta $seoMeta): string
    {
        // Priority: SeoMeta -> Model title -> App name
        $baseTitle = $seoMeta?->meta_title
            ?? $model->meta_title
            ?? $model->title
            ?? $model->name
            ?? config('app.name');

        return $this->buildFullTitle($baseTitle);
    }

    protected function resolveDescription(Model $model, ?SeoMeta $seoMeta): ?string
    {
        $description = $seoMeta?->meta_description
            ?? $model->meta_description
            ?? $model->excerpt
            ?? $model->description
            ?? null;

        if ($description) {
            // Limit to 160 characters for optimal SEO
            return \Illuminate\Support\Str::limit(strip_tags($description), 160);
        }

        return config('seo.defaults.meta_description');
    }

    protected function resolveCanonical(Model $model, ?SeoMeta $seoMeta): string
    {
        if ($seoMeta?->canonical_url) {
            return $seoMeta->canonical_url;
        }

        // Try to get URL from model
        if (method_exists($model, 'getUrl')) {
            return $model->getUrl();
        }

        if (isset($model->slug)) {
            return url($model->slug);
        }

        return url()->current();
    }

    protected function resolveRobots(?SeoMeta $seoMeta): string
    {
        if (!$seoMeta) {
            return 'index, follow';
        }

        return $seoMeta->getRobotsContent();
    }

    protected function buildFullTitle(string $title): string
    {
        $suffix = config('seo.defaults.title_suffix', config('app.name'));
        $separator = config('seo.defaults.title_separator', ' | ');

        // Don't add suffix if title already contains app name
        if (str_contains($title, $suffix)) {
            return $title;
        }

        return $title . $separator . $suffix;
    }

    protected function getAdditionalMeta(Model $model, ?SeoMeta $seoMeta): array
    {
        $meta = [];

        // Author meta
        if ($author = $model->author?->name ?? null) {
            $meta['author'] = $author;
        }

        // Published/Modified dates for articles
        if ($model->published_at ?? null) {
            $meta['article:published_time'] = $model->published_at->toIso8601String();
        }

        if ($model->updated_at ?? null) {
            $meta['article:modified_time'] = $model->updated_at->toIso8601String();
        }

        return $meta;
    }
}
```

---

## SocialMetaService

Generates Open Graph, Twitter Card, Pinterest, and Slack meta tags.

```php
<?php

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\DTOs\OpenGraphDTO;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

class SocialMetaService
{
    /**
     * Generate Open Graph meta tags.
     */
    public function generateOpenGraph(Model $model, ?SeoMeta $seoMeta): OpenGraphDTO
    {
        return new OpenGraphDTO(
            title: $this->resolveOgTitle($model, $seoMeta),
            description: $this->resolveOgDescription($model, $seoMeta),
            image: $this->resolveOgImage($model, $seoMeta),
            url: $this->resolveCanonical($model, $seoMeta),
            type: $seoMeta?->og_type ?? $this->inferOgType($model),
            siteName: $seoMeta?->og_site_name ?? config('app.name'),
            locale: $seoMeta?->og_locale ?? config('seo.defaults.og_locale', 'en_US'),
        );
    }

    /**
     * Generate Twitter Card meta tags.
     */
    public function generateTwitterCard(Model $model, ?SeoMeta $seoMeta): array
    {
        return [
            'twitter:card' => $seoMeta?->twitter_card ?? 'summary_large_image',
            'twitter:title' => $seoMeta?->twitter_title ?? $this->resolveOgTitle($model, $seoMeta),
            'twitter:description' => $seoMeta?->twitter_description ?? $this->resolveOgDescription($model, $seoMeta),
            'twitter:image' => $seoMeta?->getEffectiveTwitterImage() ?? $this->resolveOgImage($model, $seoMeta),
            'twitter:site' => $seoMeta?->twitter_site ?? config('seo.social.twitter.site'),
            'twitter:creator' => $seoMeta?->twitter_creator ?? config('seo.social.twitter.creator'),
        ];
    }

    /**
     * Generate Pinterest meta tags.
     */
    public function generatePinterest(Model $model, ?SeoMeta $seoMeta): array
    {
        $meta = [];

        // Pinterest rich pins
        $meta['og:type'] = 'article'; // Pinterest uses OG tags

        if ($description = $seoMeta?->pinterest_description) {
            $meta['og:description'] = $description;
        }

        if ($image = $seoMeta?->getEffectivePinterestImage() ?? $this->resolveOgImage($model, $seoMeta)) {
            $meta['og:image'] = $image;
        }

        // Pinterest-specific: disable hover buttons if configured
        if (config('seo.social.pinterest.disable_hover_buttons', false)) {
            $meta['pinterest:nopin'] = 'true';
        }

        return $meta;
    }

    /**
     * Generate Slack unfurling meta tags.
     */
    public function generateSlack(Model $model, ?SeoMeta $seoMeta): array
    {
        return [
            'slack-app-id' => config('seo.social.slack.app_id'),
            'og:title' => $seoMeta?->slack_title ?? $this->resolveOgTitle($model, $seoMeta),
            'og:description' => $seoMeta?->slack_description ?? $this->resolveOgDescription($model, $seoMeta),
            'og:image' => $seoMeta?->getEffectiveSlackImage() ?? $this->resolveOgImage($model, $seoMeta),
        ];
    }

    protected function resolveOgTitle(Model $model, ?SeoMeta $seoMeta): string
    {
        return $seoMeta?->og_title
            ?? $seoMeta?->meta_title
            ?? $model->title
            ?? $model->name
            ?? config('app.name');
    }

    protected function resolveOgDescription(Model $model, ?SeoMeta $seoMeta): ?string
    {
        $description = $seoMeta?->og_description
            ?? $seoMeta?->meta_description
            ?? $model->excerpt
            ?? $model->description
            ?? null;

        if ($description) {
            return \Illuminate\Support\Str::limit(strip_tags($description), 200);
        }

        return null;
    }

    protected function resolveOgImage(Model $model, ?SeoMeta $seoMeta): ?string
    {
        // Check SeoMeta first (with media library integration)
        if ($image = $seoMeta?->getEffectiveOgImage()) {
            return $image;
        }

        // Try model's featured image
        if (method_exists($model, 'getFeaturedImageUrl')) {
            return $model->getFeaturedImageUrl();
        }

        if ($model->featured_image ?? null) {
            return $model->featured_image;
        }

        // Default OG image from config
        return config('seo.defaults.og_image');
    }

    protected function resolveCanonical(Model $model, ?SeoMeta $seoMeta): string
    {
        if ($seoMeta?->canonical_url) {
            return $seoMeta->canonical_url;
        }

        if (method_exists($model, 'getUrl')) {
            return $model->getUrl();
        }

        if (isset($model->slug)) {
            return url($model->slug);
        }

        return url()->current();
    }

    protected function inferOgType(Model $model): string
    {
        $class = class_basename($model);

        return match (strtolower($class)) {
            'post', 'article', 'blogpost' => 'article',
            'product' => 'product',
            'event' => 'event',
            'profile', 'user' => 'profile',
            default => 'website',
        };
    }
}
```

---

## SchemaService

Generates Schema.org structured data.

```php
<?php

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\Schema\SchemaFactory;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

class SchemaService
{
    public function __construct(
        protected SchemaFactory $schemaFactory,
    ) {}

    /**
     * Generate all schema for a model.
     */
    public function generate(Model $model, ?SeoMeta $seoMeta): array
    {
        $schemas = [];

        // Always include organization/website schema if enabled
        if (config('seo.schema.organization.enabled', true)) {
            $schemas[] = $this->schemaFactory->make('organization')->generate($model);
        }

        if (config('seo.schema.website.enabled', true)) {
            $schemas[] = $this->schemaFactory->make('website')->generate($model);
        }

        // Add breadcrumbs if enabled
        if (config('seo.schema.breadcrumbs.enabled', true)) {
            $breadcrumbs = $this->schemaFactory->make('breadcrumb')->generate($model);
            if (!empty($breadcrumbs['itemListElement'])) {
                $schemas[] = $breadcrumbs;
            }
        }

        // Add model-specific schema
        $modelSchema = $this->generateModelSchema($model, $seoMeta);
        if ($modelSchema) {
            $schemas[] = $modelSchema;
        }

        // Add custom schema from SeoMeta
        if ($seoMeta?->schema_markup) {
            $schemas[] = $seoMeta->schema_markup;
        }

        return $schemas;
    }

    /**
     * Generate schema specific to the model type.
     */
    protected function generateModelSchema(Model $model, ?SeoMeta $seoMeta): ?array
    {
        // Use explicitly set schema type
        if ($schemaType = $seoMeta?->schema_type) {
            return $this->schemaFactory->make($schemaType)->generate($model);
        }

        // Infer schema type from model class
        $inferredType = $this->inferSchemaType($model);
        if ($inferredType) {
            return $this->schemaFactory->make($inferredType)->generate($model);
        }

        // Default to WebPage
        return $this->schemaFactory->make('webpage')->generate($model);
    }

    /**
     * Infer schema type from model class.
     */
    protected function inferSchemaType(Model $model): ?string
    {
        $class = class_basename($model);

        return match (strtolower($class)) {
            'post' => 'blogposting',
            'article' => 'article',
            'product' => 'product',
            'service' => 'service',
            'event' => 'event',
            'page' => 'webpage',
            'faq', 'faqpage' => 'faqpage',
            default => null,
        };
    }

    /**
     * Generate organization schema.
     */
    public function generateOrganizationSchema(): array
    {
        return $this->schemaFactory->make('organization')->generate(null);
    }

    /**
     * Generate local business schema.
     */
    public function generateLocalBusinessSchema(): array
    {
        return $this->schemaFactory->make('localbusiness')->generate(null);
    }

    /**
     * Generate FAQ page schema from Q&A pairs.
     */
    public function generateFaqSchema(array $faqs): array
    {
        return $this->schemaFactory->make('faqpage')->generateFromArray($faqs);
    }

    /**
     * Generate review/rating schema.
     */
    public function generateReviewSchema(Model $model, array $reviews): array
    {
        return $this->schemaFactory->make('review')->generateFromArray($model, $reviews);
    }
}
```

---

## SitemapService

Orchestrates sitemap generation.

```php
<?php

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\Services\Sitemap\SitemapGenerator;
use ArtisanPackUI\SEO\Services\Sitemap\SitemapIndexGenerator;
use ArtisanPackUI\SEO\Services\Sitemap\ImageSitemapGenerator;
use ArtisanPackUI\SEO\Services\Sitemap\VideoSitemapGenerator;
use ArtisanPackUI\SEO\Services\Sitemap\NewsSitemapGenerator;
use ArtisanPackUI\SEO\Services\Sitemap\SitemapSubmitter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SitemapService
{
    public function __construct(
        protected SitemapGenerator $sitemapGenerator,
        protected SitemapIndexGenerator $indexGenerator,
        protected ImageSitemapGenerator $imageSitemapGenerator,
        protected VideoSitemapGenerator $videoSitemapGenerator,
        protected NewsSitemapGenerator $newsSitemapGenerator,
        protected SitemapSubmitter $submitter,
    ) {}

    /**
     * Generate all sitemaps.
     */
    public function generateAll(): array
    {
        $generated = [];

        // Generate standard sitemaps
        foreach ($this->getEnabledTypes() as $type) {
            $generated[$type] = $this->generateSitemap($type);
        }

        // Generate specialized sitemaps
        if (config('seo.sitemap.image.enabled', true)) {
            $generated['image'] = $this->generateImageSitemap();
        }

        if (config('seo.sitemap.video.enabled', false)) {
            $generated['video'] = $this->generateVideoSitemap();
        }

        if (config('seo.sitemap.news.enabled', false)) {
            $generated['news'] = $this->generateNewsSitemap();
        }

        // Generate sitemap index
        $generated['index'] = $this->generateIndex($generated);

        return $generated;
    }

    /**
     * Generate a specific sitemap type.
     */
    public function generateSitemap(string $type): string
    {
        $xml = $this->sitemapGenerator->generate($type);

        // Store the sitemap
        $filename = "sitemap-{$type}.xml";
        Storage::disk('public')->put("sitemaps/{$filename}", $xml);

        // Cache the sitemap
        Cache::put("seo:sitemap:{$type}", $xml, config('seo.sitemap.cache_duration', 3600));

        return $xml;
    }

    /**
     * Generate image sitemap.
     */
    public function generateImageSitemap(): string
    {
        $xml = $this->imageSitemapGenerator->generate();
        Storage::disk('public')->put('sitemaps/sitemap-images.xml', $xml);
        Cache::put('seo:sitemap:images', $xml, config('seo.sitemap.cache_duration', 3600));

        return $xml;
    }

    /**
     * Generate video sitemap.
     */
    public function generateVideoSitemap(): string
    {
        $xml = $this->videoSitemapGenerator->generate();
        Storage::disk('public')->put('sitemaps/sitemap-video.xml', $xml);
        Cache::put('seo:sitemap:video', $xml, config('seo.sitemap.cache_duration', 3600));

        return $xml;
    }

    /**
     * Generate news sitemap.
     */
    public function generateNewsSitemap(): string
    {
        $xml = $this->newsSitemapGenerator->generate();
        Storage::disk('public')->put('sitemaps/sitemap-news.xml', $xml);
        Cache::put('seo:sitemap:news', $xml, config('seo.sitemap.cache_duration', 3600));

        return $xml;
    }

    /**
     * Generate sitemap index.
     */
    public function generateIndex(array $sitemaps): string
    {
        $xml = $this->indexGenerator->generate(array_keys($sitemaps));
        Storage::disk('public')->put('sitemaps/sitemap.xml', $xml);
        Cache::put('seo:sitemap:index', $xml, config('seo.sitemap.cache_duration', 3600));

        return $xml;
    }

    /**
     * Get a sitemap (from cache or regenerate).
     */
    public function getSitemap(string $type): string
    {
        return Cache::remember("seo:sitemap:{$type}", config('seo.sitemap.cache_duration', 3600), function () use ($type) {
            return $this->generateSitemap($type);
        });
    }

    /**
     * Submit sitemaps to search engines.
     */
    public function submitToSearchEngines(): array
    {
        return $this->submitter->submitAll();
    }

    /**
     * Clear all sitemap caches.
     */
    public function clearCache(): void
    {
        foreach ($this->getEnabledTypes() as $type) {
            Cache::forget("seo:sitemap:{$type}");
        }

        Cache::forget('seo:sitemap:index');
        Cache::forget('seo:sitemap:images');
        Cache::forget('seo:sitemap:video');
        Cache::forget('seo:sitemap:news');
    }

    /**
     * Get enabled sitemap types from config.
     */
    protected function getEnabledTypes(): array
    {
        $types = [];

        if (config('seo.sitemap.include_pages', true)) {
            $types[] = 'pages';
        }

        if (config('seo.sitemap.include_posts', true)) {
            $types[] = 'posts';
        }

        if (config('seo.sitemap.include_products', true) && class_exists('ArtisanPackUI\Ecommerce\Models\Product')) {
            $types[] = 'products';
        }

        // Add custom types from config
        foreach (config('seo.sitemap.custom_types', []) as $type => $config) {
            if ($config['enabled'] ?? true) {
                $types[] = $type;
            }
        }

        return $types;
    }
}
```

---

## RedirectService

Handles redirect matching and management.

```php
<?php

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\Models\Redirect;
use ArtisanPackUI\SEO\Services\Redirect\RedirectMatcher;
use ArtisanPackUI\SEO\Services\Redirect\RedirectChainDetector;
use ArtisanPackUI\SEO\Services\Redirect\BrokenLinkSuggester;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RedirectService
{
    public function __construct(
        protected RedirectMatcher $matcher,
        protected RedirectChainDetector $chainDetector,
        protected BrokenLinkSuggester $suggester,
    ) {}

    /**
     * Find a matching redirect for a path.
     */
    public function findMatch(string $path): ?Redirect
    {
        return $this->matcher->match($path);
    }

    /**
     * Create a new redirect.
     */
    public function create(array $data): Redirect
    {
        $redirect = Redirect::create($data);

        // Check for chains/loops
        $this->checkForChains($redirect);

        // Clear cache
        $this->clearCache();

        return $redirect;
    }

    /**
     * Update a redirect.
     */
    public function update(Redirect $redirect, array $data): Redirect
    {
        $redirect->update($data);

        // Re-check for chains/loops
        $this->checkForChains($redirect);

        // Clear cache
        $this->clearCache();

        return $redirect;
    }

    /**
     * Delete a redirect.
     */
    public function delete(Redirect $redirect): bool
    {
        $result = $redirect->delete();
        $this->clearCache();

        return $result;
    }

    /**
     * Import redirects from CSV.
     */
    public function importFromCsv(string $csvPath): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            try {
                $existing = Redirect::where('from_path', $data['from_path'])->first();

                if ($existing) {
                    $existing->update($data);
                    $results['updated']++;
                } else {
                    Redirect::create($data);
                    $results['created']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'row' => $data,
                    'error' => $e->getMessage(),
                ];
            }
        }

        fclose($handle);
        $this->clearCache();

        return $results;
    }

    /**
     * Export redirects to CSV.
     */
    public function exportToCsv(): string
    {
        $redirects = Redirect::all();

        $csv = "from_path,to_path,status_code,match_type,is_active,hits\n";

        foreach ($redirects as $redirect) {
            $csv .= implode(',', [
                $redirect->from_path,
                $redirect->to_path,
                $redirect->status_code,
                $redirect->match_type,
                $redirect->is_active ? '1' : '0',
                $redirect->hits,
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Check all redirects for chains and loops.
     */
    public function checkAllForChains(): Collection
    {
        return $this->chainDetector->checkAll();
    }

    /**
     * Check a specific redirect for chains/loops.
     */
    public function checkForChains(Redirect $redirect): void
    {
        $this->chainDetector->check($redirect);
    }

    /**
     * Get suggested redirects for broken links.
     */
    public function suggestForBrokenLinks(): Collection
    {
        return $this->suggester->suggest();
    }

    /**
     * Get redirect statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total' => Redirect::count(),
            'active' => Redirect::active()->count(),
            'with_issues' => Redirect::hasIssues()->count(),
            'total_hits' => Redirect::sum('hits'),
            'top_redirects' => Redirect::orderByDesc('hits')->limit(10)->get(),
        ];
    }

    /**
     * Clear redirect cache.
     */
    public function clearCache(): void
    {
        Cache::forget('seo:redirects:all');
        Cache::forget('seo:redirects:patterns');
    }
}
```

---

## HreflangService

Handles multi-language support.

```php
<?php

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Support\Collection;

class HreflangService
{
    /**
     * Generate hreflang tags from SeoMeta.
     */
    public function generate(?SeoMeta $seoMeta): array
    {
        if (!$seoMeta?->hreflang) {
            return [];
        }

        $tags = [];

        foreach ($seoMeta->hreflang as $lang => $url) {
            $tags[] = [
                'hreflang' => $lang,
                'href' => $url,
            ];
        }

        // Add x-default if configured
        if ($default = config('seo.hreflang.default_language')) {
            if (isset($seoMeta->hreflang[$default])) {
                $tags[] = [
                    'hreflang' => 'x-default',
                    'href' => $seoMeta->hreflang[$default],
                ];
            }
        }

        return $tags;
    }

    /**
     * Build hreflang data for a model with translations.
     */
    public function buildFromTranslations($model, array $translations): array
    {
        $hreflang = [];

        foreach ($translations as $locale => $translatedModel) {
            if (method_exists($translatedModel, 'getUrl')) {
                $hreflang[$locale] = $translatedModel->getUrl();
            } elseif (isset($translatedModel->slug)) {
                $hreflang[$locale] = url($translatedModel->slug);
            }
        }

        return $hreflang;
    }

    /**
     * Validate hreflang configuration.
     */
    public function validate(array $hreflang): array
    {
        $errors = [];

        foreach ($hreflang as $lang => $url) {
            // Validate language code format
            if (!preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $lang) && $lang !== 'x-default') {
                $errors[] = "Invalid language code: {$lang}";
            }

            // Validate URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[] = "Invalid URL for {$lang}: {$url}";
            }
        }

        return $errors;
    }

    /**
     * Get supported languages from config.
     */
    public function getSupportedLanguages(): array
    {
        return config('seo.hreflang.supported_languages', ['en']);
    }
}
```

---

## CacheService

Manages SEO-specific caching.

```php
<?php

namespace ArtisanPackUI\SEO\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Get cache key for model's meta tags.
     */
    public function getMetaCacheKey(Model $model): string
    {
        return sprintf(
            'seo:meta:%s:%s',
            class_basename($model),
            $model->getKey()
        );
    }

    /**
     * Get cache key for model's analysis.
     */
    public function getAnalysisCacheKey(Model $model): string
    {
        return sprintf(
            'seo:analysis:%s:%s',
            class_basename($model),
            $model->getKey()
        );
    }

    /**
     * Clear meta cache for a model.
     */
    public function clearMetaCache(Model $model): void
    {
        Cache::forget($this->getMetaCacheKey($model));
    }

    /**
     * Clear analysis cache for a model.
     */
    public function clearAnalysisCache(Model $model): void
    {
        Cache::forget($this->getAnalysisCacheKey($model));
    }

    /**
     * Clear all SEO caches for a model.
     */
    public function clearAllForModel(Model $model): void
    {
        $this->clearMetaCache($model);
        $this->clearAnalysisCache($model);
    }

    /**
     * Clear all SEO caches.
     */
    public function clearAll(): void
    {
        // Clear pattern-based caches
        // Note: This requires Redis or a cache driver that supports tags
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(['seo'])->flush();
        }

        // Clear known keys
        Cache::forget('seo:redirects:all');
        Cache::forget('seo:redirects:patterns');
        Cache::forget('seo:sitemap:index');

        // Clear sitemap caches
        foreach (['pages', 'posts', 'products', 'images', 'video', 'news'] as $type) {
            Cache::forget("seo:sitemap:{$type}");
        }
    }

    /**
     * Get cache TTL from config.
     */
    public function getTtl(): int
    {
        return config('seo.cache.ttl', 3600);
    }

    /**
     * Check if caching is enabled.
     */
    public function isEnabled(): bool
    {
        return config('seo.cache.enabled', true);
    }
}
```

---

## Related Documents

- [01-architecture.md](01-architecture.md) - Package architecture
- [04-traits-and-models.md](04-traits-and-models.md) - HasSeo trait
- [05-seo-analysis.md](05-seo-analysis.md) - Analysis service details
