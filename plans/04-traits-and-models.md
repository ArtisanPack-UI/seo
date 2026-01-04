# Traits and Models

**Purpose:** Define the HasSeo trait and related traits for model integration
**Last Updated:** January 3, 2026

---

## Overview

The SEO package provides traits that can be added to any Eloquent model to enable SEO functionality:

| Trait | Purpose |
|-------|---------|
| `HasSeo` | Main trait - adds SEO meta relationship and accessors |
| `HasFocusKeyword` | Focus keyword tracking for analysis |
| `HasSeoAnalysis` | SEO analysis integration |
| `HasSeoObserver` | Automatic cache invalidation on save |

---

## HasSeo Trait

The primary trait that enables SEO functionality on any model.

```php
<?php

namespace ArtisanPackUI\SEO\Traits;

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\SeoService;
use ArtisanPackUI\SEO\Observers\SeoObserver;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeo
{
    /**
     * Boot the trait.
     */
    public static function bootHasSeo(): void
    {
        static::observe(SeoObserver::class);
    }

    /**
     * Get the SEO meta relationship.
     */
    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /**
     * Get or create SEO meta for this model.
     */
    public function getOrCreateSeoMeta(): SeoMeta
    {
        if (!$this->seoMeta) {
            $this->seoMeta()->create([
                'seoable_type' => get_class($this),
                'seoable_id' => $this->getKey(),
            ]);

            $this->load('seoMeta');
        }

        return $this->seoMeta;
    }

    /**
     * Update SEO meta with given data.
     */
    public function updateSeoMeta(array $data): SeoMeta
    {
        return app(SeoService::class)->updateSeoMeta($this, $data);
    }

    /**
     * Get the meta title for this model.
     */
    public function getMetaTitleAttribute(): string
    {
        return $this->seoMeta?->meta_title
            ?? $this->title
            ?? $this->name
            ?? config('seo.defaults.title_suffix', config('app.name'));
    }

    /**
     * Get the meta description for this model.
     */
    public function getMetaDescriptionAttribute(): ?string
    {
        if ($this->seoMeta?->meta_description) {
            return $this->seoMeta->meta_description;
        }

        // Try to generate from model content
        $content = $this->excerpt ?? $this->description ?? $this->content ?? null;

        if ($content) {
            return \Illuminate\Support\Str::limit(strip_tags($content), 160);
        }

        return config('seo.defaults.meta_description');
    }

    /**
     * Get the canonical URL for this model.
     */
    public function getCanonicalUrlAttribute(): string
    {
        if ($this->seoMeta?->canonical_url) {
            return $this->seoMeta->canonical_url;
        }

        if (method_exists($this, 'getUrl')) {
            return $this->getUrl();
        }

        if ($this->slug ?? null) {
            return url($this->slug);
        }

        return url()->current();
    }

    /**
     * Get the OG image for this model.
     */
    public function getOgImageAttribute(): ?string
    {
        return $this->seoMeta?->getEffectiveOgImage()
            ?? $this->featured_image
            ?? config('seo.defaults.og_image');
    }

    /**
     * Get the focus keyword for this model.
     */
    public function getFocusKeywordAttribute(): ?string
    {
        return $this->seoMeta?->focus_keyword;
    }

    /**
     * Set the focus keyword for this model.
     */
    public function setFocusKeyword(string $keyword): self
    {
        $this->getOrCreateSeoMeta()->update(['focus_keyword' => $keyword]);
        return $this;
    }

    /**
     * Check if this model should be indexed.
     */
    public function shouldBeIndexed(): bool
    {
        return !($this->seoMeta?->no_index ?? false);
    }

    /**
     * Check if this model should be followed.
     */
    public function shouldBeFollowed(): bool
    {
        return !($this->seoMeta?->no_follow ?? false);
    }

    /**
     * Check if this model should be in the sitemap.
     */
    public function shouldBeInSitemap(): bool
    {
        return !($this->seoMeta?->exclude_from_sitemap ?? false)
            && $this->shouldBeIndexed();
    }

    /**
     * Get the sitemap priority for this model.
     */
    public function getSitemapPriority(): float
    {
        return $this->seoMeta?->sitemap_priority ?? 0.5;
    }

    /**
     * Get the sitemap change frequency for this model.
     */
    public function getSitemapChangefreq(): string
    {
        return $this->seoMeta?->sitemap_changefreq ?? 'weekly';
    }

    /**
     * Get robots meta content.
     */
    public function getRobotsMetaAttribute(): string
    {
        return $this->seoMeta?->getRobotsContent() ?? 'index, follow';
    }

    /**
     * Get all SEO data for this model.
     */
    public function getSeoData(): array
    {
        return app(SeoService::class)->getAll($this);
    }

    /**
     * Get hreflang tags for this model.
     */
    public function getHreflangAttribute(): array
    {
        return $this->seoMeta?->hreflang ?? [];
    }

    /**
     * Set hreflang tags for this model.
     */
    public function setHreflang(array $hreflang): self
    {
        $this->getOrCreateSeoMeta()->update(['hreflang' => $hreflang]);
        return $this;
    }

    /**
     * Get the schema type for this model.
     */
    public function getSchemaType(): ?string
    {
        return $this->seoMeta?->schema_type;
    }

    /**
     * Set the schema type for this model.
     */
    public function setSchemaType(string $type): self
    {
        $this->getOrCreateSeoMeta()->update(['schema_type' => $type]);
        return $this;
    }

    /**
     * Scope: Models that should be in sitemap.
     */
    public function scopeForSitemap($query)
    {
        return $query->whereDoesntHave('seoMeta', function ($q) {
            $q->where('exclude_from_sitemap', true)
              ->orWhere('no_index', true);
        });
    }

    /**
     * Scope: Models with focus keyword.
     */
    public function scopeWithFocusKeyword($query, string $keyword)
    {
        return $query->whereHas('seoMeta', function ($q) use ($keyword) {
            $q->where('focus_keyword', $keyword);
        });
    }
}
```

---

## HasSeoAnalysis Trait

Provides SEO analysis functionality.

```php
<?php

namespace ArtisanPackUI\SEO\Traits;

use ArtisanPackUI\SEO\Models\SeoAnalysisCache;
use ArtisanPackUI\SEO\Services\AnalysisService;
use ArtisanPackUI\SEO\DTOs\AnalysisResultDTO;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

trait HasSeoAnalysis
{
    /**
     * Get the SEO analysis cache.
     */
    public function seoAnalysis(): HasOneThrough
    {
        return $this->hasOneThrough(
            SeoAnalysisCache::class,
            \ArtisanPackUI\SEO\Models\SeoMeta::class,
            'seoable_id',      // Foreign key on seo_meta
            'seo_meta_id',     // Foreign key on seo_analysis_cache
            'id',              // Local key on this model
            'id'               // Local key on seo_meta
        )->where('seo_meta.seoable_type', get_class($this));
    }

    /**
     * Run SEO analysis on this model.
     */
    public function analyzeSeo(?string $focusKeyword = null): AnalysisResultDTO
    {
        $keyword = $focusKeyword ?? $this->seoMeta?->focus_keyword;
        return app(AnalysisService::class)->analyze($this, $keyword);
    }

    /**
     * Get the SEO score for this model.
     */
    public function getSeoScoreAttribute(): int
    {
        return $this->seoAnalysis?->overall_score ?? 0;
    }

    /**
     * Get the SEO score grade (good, ok, poor).
     */
    public function getSeoGradeAttribute(): string
    {
        $score = $this->seo_score;

        return match (true) {
            $score >= 80 => 'good',
            $score >= 50 => 'ok',
            default => 'poor',
        };
    }

    /**
     * Get SEO issues for this model.
     */
    public function getSeoIssuesAttribute(): array
    {
        return $this->seoAnalysis?->issues ?? [];
    }

    /**
     * Get SEO suggestions for this model.
     */
    public function getSeoSuggestionsAttribute(): array
    {
        return $this->seoAnalysis?->suggestions ?? [];
    }

    /**
     * Check if SEO analysis is stale.
     */
    public function isSeoAnalysisStale(): bool
    {
        return $this->seoAnalysis?->isStale() ?? true;
    }

    /**
     * Refresh SEO analysis if stale.
     */
    public function refreshSeoAnalysisIfStale(): ?AnalysisResultDTO
    {
        if ($this->isSeoAnalysisStale()) {
            return $this->analyzeSeo();
        }

        return null;
    }

    /**
     * Get word count of content.
     */
    public function getContentWordCountAttribute(): int
    {
        $content = $this->content ?? $this->body ?? '';
        $text = strip_tags($content);

        return str_word_count($text);
    }

    /**
     * Scope: Models with good SEO score.
     */
    public function scopeWithGoodSeo($query)
    {
        return $query->whereHas('seoAnalysis', function ($q) {
            $q->where('overall_score', '>=', 80);
        });
    }

    /**
     * Scope: Models with poor SEO score.
     */
    public function scopeWithPoorSeo($query)
    {
        return $query->whereHas('seoAnalysis', function ($q) {
            $q->where('overall_score', '<', 50);
        });
    }

    /**
     * Scope: Models needing SEO improvement.
     */
    public function scopeNeedsSeoImprovement($query)
    {
        return $query->whereHas('seoAnalysis', function ($q) {
            $q->where('overall_score', '<', 80);
        });
    }
}
```

---

## SeoObserver

Observer for automatic cache invalidation and analysis triggering.

```php
<?php

namespace ArtisanPackUI\SEO\Observers;

use ArtisanPackUI\SEO\Services\CacheService;
use ArtisanPackUI\SEO\Services\SitemapService;
use ArtisanPackUI\SEO\Jobs\AnalyzeContentJob;
use ArtisanPackUI\SEO\Jobs\UpdateSitemapEntryJob;
use ArtisanPackUI\SEO\Events\SeoMetaUpdated;
use Illuminate\Database\Eloquent\Model;

class SeoObserver
{
    public function __construct(
        protected CacheService $cacheService,
    ) {}

    /**
     * Handle the model "saved" event.
     */
    public function saved(Model $model): void
    {
        // Clear meta cache
        $this->cacheService->clearMetaCache($model);

        // Update sitemap entry
        if (config('seo.sitemap.auto_update', true)) {
            dispatch(new UpdateSitemapEntryJob($model));
        }

        // Trigger analysis if enabled
        if (config('seo.analysis.auto_analyze', true)) {
            dispatch(new AnalyzeContentJob($model));
        }

        // Dispatch event
        event(new SeoMetaUpdated($model));
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        // Clear all caches
        $this->cacheService->clearAllForModel($model);

        // Remove sitemap entry
        \ArtisanPackUI\SEO\Models\SitemapEntry::forModel($model)->delete();

        // Delete SEO meta (if not cascade deleted)
        if ($model->seoMeta) {
            $model->seoMeta->delete();
        }
    }
}
```

---

## Usage Examples

### Basic Model Setup

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ArtisanPackUI\SEO\Traits\HasSeo;
use ArtisanPackUI\SEO\Traits\HasSeoAnalysis;

class Page extends Model
{
    use HasSeo;
    use HasSeoAnalysis;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
    ];
}
```

### Using SEO Data in Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    public function show(Page $page)
    {
        // SEO data is automatically available
        return view('pages.show', [
            'page' => $page,
            'seoData' => $page->getSeoData(),
        ]);
    }
}
```

### Updating SEO Meta

```php
// Update via trait method
$page->updateSeoMeta([
    'meta_title' => 'Custom Title',
    'meta_description' => 'Custom description for this page.',
    'focus_keyword' => 'main keyword',
    'og_image' => 'https://example.com/image.jpg',
]);

// Or update individual fields
$page->setFocusKeyword('new keyword');
$page->setSchemaType('article');
$page->setHreflang([
    'en' => 'https://example.com/page',
    'es' => 'https://example.com/es/pagina',
]);
```

### Running Analysis

```php
// Run analysis with focus keyword
$result = $page->analyzeSeo('target keyword');

// Get score
$score = $page->seo_score; // 0-100
$grade = $page->seo_grade; // 'good', 'ok', 'poor'

// Get issues and suggestions
$issues = $page->seo_issues;
$suggestions = $page->seo_suggestions;

// Check if analysis is current
if ($page->isSeoAnalysisStale()) {
    $page->refreshSeoAnalysisIfStale();
}
```

### Querying Models

```php
// Get pages suitable for sitemap
$pages = Page::forSitemap()->published()->get();

// Get pages with good SEO
$wellOptimized = Page::withGoodSeo()->get();

// Get pages needing improvement
$needsWork = Page::needsSeoImprovement()->get();

// Get pages targeting a keyword
$targeted = Page::withFocusKeyword('target keyword')->get();
```

---

## Integration with CMS-Framework

When using with `artisanpack-ui/cms-framework`, additional functionality is available:

```php
<?php

namespace ArtisanPackUI\CmsFramework\Models;

use Illuminate\Database\Eloquent\Model;
use ArtisanPackUI\SEO\Traits\HasSeo;
use ArtisanPackUI\SEO\Traits\HasSeoAnalysis;
use ArtisanPackUI\SEO\Contracts\SeoableContract;

class Page extends Model implements SeoableContract
{
    use HasSeo;
    use HasSeoAnalysis;

    /**
     * Get the URL for this page (used by SEO package).
     */
    public function getUrl(): string
    {
        if ($this->is_homepage) {
            return config('app.url');
        }

        return url($this->full_slug);
    }

    /**
     * Get the featured image URL (used by SEO package).
     */
    public function getFeaturedImageUrl(): ?string
    {
        return $this->featuredMedia?->url;
    }

    /**
     * Get breadcrumb data for schema.
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => config('app.url')],
        ];

        if ($this->parent) {
            $breadcrumbs[] = [
                'name' => $this->parent->title,
                'url' => $this->parent->getUrl(),
            ];
        }

        $breadcrumbs[] = [
            'name' => $this->title,
            'url' => $this->getUrl(),
        ];

        return $breadcrumbs;
    }
}
```

---

## Related Documents

- [02-database-schema.md](02-database-schema.md) - SeoMeta model definition
- [03-core-services.md](03-core-services.md) - SeoService implementation
- [05-seo-analysis.md](05-seo-analysis.md) - Analysis details
