# Package Integrations

**Purpose:** Define integrations with other ArtisanPack UI packages
**Last Updated:** January 3, 2026

---

## Overview

The SEO package integrates with four other ArtisanPack UI packages:

| Package | Integration Purpose |
|---------|---------------------|
| **media-library** | OG image selection from media library |
| **cms-framework** | GlobalContent for organization schema, Page/Post models |
| **analytics** | SEO performance dashboard with Search Console data |
| **visual-editor** | Pre-publish SEO checks |

---

## Integration: media-library

### Purpose
Enable selecting og:image and other social images from the media library instead of entering URLs manually.

### Detection

```php
<?php

namespace ArtisanPackUI\SEO\Support;

class PackageDetector
{
    public static function hasMediaLibrary(): bool
    {
        return class_exists(\ArtisanPackUI\MediaLibrary\Models\Media::class);
    }

    public static function hasCmsFramework(): bool
    {
        return class_exists(\ArtisanPackUI\CmsFramework\Models\Page::class);
    }

    public static function hasAnalytics(): bool
    {
        return class_exists(\ArtisanPackUI\Analytics\Services\AnalyticsService::class);
    }

    public static function hasVisualEditor(): bool
    {
        return class_exists(\ArtisanPackUI\VisualEditor\Services\EditorService::class);
    }
}
```

### Media Library Integration

```php
<?php

namespace ArtisanPackUI\SEO\Integrations;

use ArtisanPackUI\SEO\Support\PackageDetector;

class MediaLibraryIntegration
{
    /**
     * Get a media item URL by ID.
     */
    public function getMediaUrl(?int $mediaId, string $size = 'large'): ?string
    {
        if (!$mediaId || !PackageDetector::hasMediaLibrary()) {
            return null;
        }

        $media = \ArtisanPackUI\MediaLibrary\Models\Media::find($mediaId);

        if (!$media) {
            return null;
        }

        return $media->imageUrl($size);
    }

    /**
     * Get optimized image URL for social sharing.
     * Social platforms prefer 1200x630 images.
     */
    public function getSocialImageUrl(?int $mediaId): ?string
    {
        if (!$mediaId || !PackageDetector::hasMediaLibrary()) {
            return null;
        }

        $media = \ArtisanPackUI\MediaLibrary\Models\Media::find($mediaId);

        if (!$media) {
            return null;
        }

        // Try to get a social-optimized size, fall back to large
        return $media->imageUrl('social') ?? $media->imageUrl('large') ?? $media->url();
    }

    /**
     * Register a social-optimized image size with media library.
     */
    public function registerSocialImageSize(): void
    {
        if (!PackageDetector::hasMediaLibrary()) {
            return;
        }

        // Register 1200x630 size for Open Graph
        if (function_exists('apRegisterImageSize')) {
            apRegisterImageSize('social', 1200, 630, true);
        }
    }
}
```

### SeoMeta Model Enhancement

```php
<?php

// In SeoMeta model

public function getEffectiveOgImage(): ?string
{
    // First try media library integration
    if ($this->og_image_id) {
        $integration = app(MediaLibraryIntegration::class);
        $url = $integration->getSocialImageUrl($this->og_image_id);

        if ($url) {
            return $url;
        }
    }

    // Fall back to URL
    return $this->og_image;
}

public function getEffectiveTwitterImage(): ?string
{
    if ($this->twitter_image_id) {
        $integration = app(MediaLibraryIntegration::class);
        return $integration->getSocialImageUrl($this->twitter_image_id);
    }

    return $this->twitter_image ?? $this->getEffectiveOgImage();
}
```

### Livewire Component Enhancement

```php
<?php

// In SeoMetaEditor component

protected $listeners = [
    'media-selected' => 'handleMediaSelected',
];

public function handleMediaSelected(array $event): void
{
    $media = $event['media'][0] ?? null;
    $context = $event['context'] ?? '';

    if (!$media) {
        return;
    }

    match ($context) {
        'og_image' => $this->setOgImage($media),
        'twitter_image' => $this->setTwitterImage($media),
        'pinterest_image' => $this->setPinterestImage($media),
        default => null,
    };
}

protected function setOgImage(array $media): void
{
    $this->ogImageId = $media['id'];
    $this->ogImage = $media['url'];
}

public function openMediaLibrary(string $context): void
{
    $this->dispatch('open-media-modal', [
        'context' => $context,
        'multiSelect' => false,
        'acceptedTypes' => ['image/*'],
    ]);
}
```

---

## Integration: cms-framework

### Purpose
Use GlobalContent for organization schema (business name, address, phone), integrate with Page/Post models.

### GlobalContent Integration

```php
<?php

namespace ArtisanPackUI\SEO\Integrations;

use ArtisanPackUI\SEO\Support\PackageDetector;

class CmsFrameworkIntegration
{
    /**
     * Get GlobalContent value.
     */
    public function getGlobalContent(string $key, mixed $default = null): mixed
    {
        if (!PackageDetector::hasCmsFramework()) {
            return $default;
        }

        return \ArtisanPackUI\CmsFramework\Models\GlobalContent::get($key, $default);
    }

    /**
     * Get organization data for schema.
     */
    public function getOrganizationData(): array
    {
        if (!PackageDetector::hasCmsFramework()) {
            return $this->getDefaultOrganizationData();
        }

        return [
            'name' => $this->getGlobalContent('business_name', config('app.name')),
            'url' => config('app.url'),
            'logo' => $this->getGlobalContent('logo_url'),
            'telephone' => $this->getGlobalContent('phone'),
            'email' => $this->getGlobalContent('email'),
            'address' => [
                'streetAddress' => $this->getGlobalContent('address'),
                'addressLocality' => $this->getGlobalContent('city'),
                'addressRegion' => $this->getGlobalContent('state'),
                'postalCode' => $this->getGlobalContent('zip'),
                'addressCountry' => $this->getGlobalContent('country', 'US'),
            ],
            'openingHours' => $this->getGlobalContent('business_hours'),
            'priceRange' => $this->getGlobalContent('price_range'),
            'sameAs' => $this->getSocialProfiles(),
        ];
    }

    /**
     * Get social profile URLs.
     */
    protected function getSocialProfiles(): array
    {
        $profiles = [];

        $socialKeys = ['facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url', 'youtube_url'];

        foreach ($socialKeys as $key) {
            $url = $this->getGlobalContent($key);
            if ($url) {
                $profiles[] = $url;
            }
        }

        return $profiles;
    }

    /**
     * Get default organization data from config.
     */
    protected function getDefaultOrganizationData(): array
    {
        return [
            'name' => config('seo.organization.name', config('app.name')),
            'url' => config('app.url'),
            'logo' => config('seo.organization.logo'),
            'telephone' => config('seo.organization.phone'),
            'email' => config('seo.organization.email'),
        ];
    }

    /**
     * Get sitemap-eligible pages.
     */
    public function getSitemapPages(): \Illuminate\Support\Collection
    {
        if (!PackageDetector::hasCmsFramework()) {
            return collect();
        }

        return \ArtisanPackUI\CmsFramework\Models\Page::query()
            ->where('status', 'published')
            ->forSitemap()
            ->get();
    }

    /**
     * Get sitemap-eligible posts.
     */
    public function getSitemapPosts(): \Illuminate\Support\Collection
    {
        if (!PackageDetector::hasCmsFramework()) {
            return collect();
        }

        return \ArtisanPackUI\CmsFramework\Models\Post::query()
            ->where('status', 'published')
            ->forSitemap()
            ->get();
    }
}
```

### OrganizationSchema Enhancement

```php
<?php

namespace ArtisanPackUI\SEO\Schema\Types;

use ArtisanPackUI\SEO\Contracts\SchemaTypeContract;
use ArtisanPackUI\SEO\Integrations\CmsFrameworkIntegration;
use Illuminate\Database\Eloquent\Model;

class OrganizationSchema implements SchemaTypeContract
{
    public function __construct(
        protected CmsFrameworkIntegration $cmsIntegration,
    ) {}

    public function generate(?Model $model): array
    {
        $data = $this->cmsIntegration->getOrganizationData();
        $type = config('seo.schema.organization.type', 'Organization');

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $data['name'],
            'url' => $data['url'],
        ];

        if ($data['logo'] ?? null) {
            $schema['logo'] = $data['logo'];
        }

        if ($data['telephone'] ?? null) {
            $schema['telephone'] = $data['telephone'];
        }

        if ($data['email'] ?? null) {
            $schema['email'] = $data['email'];
        }

        // Add address if available
        if (!empty(array_filter($data['address'] ?? []))) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                ...array_filter($data['address']),
            ];
        }

        // Add social profiles
        if (!empty($data['sameAs'] ?? [])) {
            $schema['sameAs'] = $data['sameAs'];
        }

        // LocalBusiness-specific fields
        if ($type === 'LocalBusiness' || is_subclass_of($type, 'LocalBusiness')) {
            if ($data['openingHours'] ?? null) {
                $schema['openingHours'] = $data['openingHours'];
            }

            if ($data['priceRange'] ?? null) {
                $schema['priceRange'] = $data['priceRange'];
            }
        }

        return $schema;
    }

    public function getType(): string
    {
        return config('seo.schema.organization.type', 'Organization');
    }
}
```

---

## Integration: analytics

### Purpose
Display SEO performance data from Google Search Console alongside on-page SEO analysis.

### Analytics Integration

```php
<?php

namespace ArtisanPackUI\SEO\Integrations;

use ArtisanPackUI\SEO\Support\PackageDetector;
use Illuminate\Support\Collection;

class AnalyticsIntegration
{
    /**
     * Get Search Console data for a URL.
     */
    public function getSearchConsoleData(string $url, string $period = '30d'): ?array
    {
        if (!PackageDetector::hasAnalytics()) {
            return null;
        }

        if (!$this->hasSearchConsoleAccess()) {
            return null;
        }

        // Use the analytics package's Search Console service
        $searchConsole = app(\ArtisanPackUI\Analytics\Services\SearchConsoleService::class);

        return $searchConsole->getPageData($url, $period);
    }

    /**
     * Get top queries for a page.
     */
    public function getTopQueries(string $url, int $limit = 10): Collection
    {
        if (!PackageDetector::hasAnalytics()) {
            return collect();
        }

        $searchConsole = app(\ArtisanPackUI\Analytics\Services\SearchConsoleService::class);

        return $searchConsole->getPageQueries($url, $limit);
    }

    /**
     * Check if Search Console is configured.
     */
    protected function hasSearchConsoleAccess(): bool
    {
        return config('analytics.search_console.enabled', false)
            && config('analytics.search_console.property');
    }

    /**
     * Get SEO performance summary for dashboard.
     */
    public function getSeoPerformanceSummary(string $period = '30d'): array
    {
        if (!PackageDetector::hasAnalytics() || !$this->hasSearchConsoleAccess()) {
            return [];
        }

        $searchConsole = app(\ArtisanPackUI\Analytics\Services\SearchConsoleService::class);

        return [
            'clicks' => $searchConsole->getTotalClicks($period),
            'impressions' => $searchConsole->getTotalImpressions($period),
            'avgPosition' => $searchConsole->getAveragePosition($period),
            'avgCtr' => $searchConsole->getAverageCtr($period),
            'topPages' => $searchConsole->getTopPages($period, 5),
            'topQueries' => $searchConsole->getTopQueries($period, 5),
        ];
    }
}
```

### SEO Dashboard Widget

```php
<?php

namespace ArtisanPackUI\SEO\Http\Livewire;

use ArtisanPackUI\SEO\Integrations\AnalyticsIntegration;
use Livewire\Component;

class SeoDashboard extends Component
{
    public string $period = '30d';
    public array $performance = [];

    public function mount(): void
    {
        $this->loadPerformance();
    }

    public function loadPerformance(): void
    {
        $integration = app(AnalyticsIntegration::class);
        $this->performance = $integration->getSeoPerformanceSummary($this->period);
    }

    public function updatedPeriod(): void
    {
        $this->loadPerformance();
    }

    public function render()
    {
        return view('seo::livewire.seo-dashboard');
    }
}
```

### Dashboard View

```blade
{{-- resources/views/livewire/seo-dashboard.blade.php --}}
<div class="seo-dashboard">
    {{-- Period Selector --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">SEO Performance</h2>
        <select wire:model.live="period" class="select select-bordered select-sm">
            <option value="7d">Last 7 days</option>
            <option value="30d">Last 30 days</option>
            <option value="90d">Last 90 days</option>
        </select>
    </div>

    @if(empty($performance))
        <div class="alert alert-info">
            <x-heroicon-o-information-circle class="w-5 h-5" />
            <span>Connect Google Search Console in Analytics settings to see SEO performance data.</span>
        </div>
    @else
        {{-- Stats Grid --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="stat bg-base-200 rounded-box">
                <div class="stat-title">Clicks</div>
                <div class="stat-value text-primary">{{ number_format($performance['clicks'] ?? 0) }}</div>
            </div>

            <div class="stat bg-base-200 rounded-box">
                <div class="stat-title">Impressions</div>
                <div class="stat-value">{{ number_format($performance['impressions'] ?? 0) }}</div>
            </div>

            <div class="stat bg-base-200 rounded-box">
                <div class="stat-title">Avg. Position</div>
                <div class="stat-value">{{ number_format($performance['avgPosition'] ?? 0, 1) }}</div>
            </div>

            <div class="stat bg-base-200 rounded-box">
                <div class="stat-title">CTR</div>
                <div class="stat-value">{{ number_format(($performance['avgCtr'] ?? 0) * 100, 1) }}%</div>
            </div>
        </div>

        {{-- Top Pages & Queries --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Top Pages --}}
            <div class="card bg-base-200">
                <div class="card-body">
                    <h3 class="card-title text-base">Top Pages</h3>
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th class="text-right">Clicks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($performance['topPages'] ?? [] as $page)
                                    <tr>
                                        <td class="truncate max-w-xs">{{ $page['page'] }}</td>
                                        <td class="text-right">{{ number_format($page['clicks']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Top Queries --}}
            <div class="card bg-base-200">
                <div class="card-body">
                    <h3 class="card-title text-base">Top Search Queries</h3>
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Query</th>
                                    <th class="text-right">Clicks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($performance['topQueries'] ?? [] as $query)
                                    <tr>
                                        <td class="truncate max-w-xs">{{ $query['query'] }}</td>
                                        <td class="text-right">{{ number_format($query['clicks']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
```

---

## Integration: visual-editor

### Purpose
Add pre-publish SEO checks to the visual editor workflow.

### Visual Editor Integration

```php
<?php

namespace ArtisanPackUI\SEO\Integrations;

use ArtisanPackUI\SEO\Services\AnalysisService;
use ArtisanPackUI\SEO\Support\PackageDetector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class VisualEditorIntegration
{
    public function __construct(
        protected AnalysisService $analysisService,
    ) {}

    /**
     * Register pre-publish checks with the visual editor.
     */
    public function registerPrePublishChecks(): void
    {
        if (!PackageDetector::hasVisualEditor()) {
            return;
        }

        // Register SEO checks via hooks
        addFilter('visual_editor.pre_publish_checks', function (Collection $checks, Model $page) {
            return $checks->merge($this->getSeoChecks($page));
        });
    }

    /**
     * Get SEO-related pre-publish checks.
     */
    public function getSeoChecks(Model $page): Collection
    {
        $checks = collect();
        $seoMeta = $page->seoMeta;

        // Check for meta title
        if (empty($seoMeta?->meta_title) && empty($page->title)) {
            $checks->push([
                'type' => 'warning',
                'category' => 'seo',
                'message' => 'Page is missing a meta title',
                'action' => 'Add a meta title for better search visibility',
            ]);
        }

        // Check for meta description
        if (empty($seoMeta?->meta_description)) {
            $checks->push([
                'type' => 'suggestion',
                'category' => 'seo',
                'message' => 'Page is missing a meta description',
                'action' => 'Add a meta description to improve click-through rates',
            ]);
        }

        // Check for focus keyword
        if (empty($seoMeta?->focus_keyword)) {
            $checks->push([
                'type' => 'suggestion',
                'category' => 'seo',
                'message' => 'No focus keyword set',
                'action' => 'Set a focus keyword to optimize your content',
            ]);
        }

        // Check SEO score if analysis exists
        if ($page->seoAnalysis && $page->seoAnalysis->overall_score < 50) {
            $checks->push([
                'type' => 'warning',
                'category' => 'seo',
                'message' => sprintf('Low SEO score (%d/100)', $page->seoAnalysis->overall_score),
                'action' => 'Review SEO suggestions to improve your score',
            ]);
        }

        // Check for OG image
        if (empty($seoMeta?->og_image) && empty($seoMeta?->og_image_id) && empty($page->featured_image)) {
            $checks->push([
                'type' => 'suggestion',
                'category' => 'seo',
                'message' => 'No social sharing image set',
                'action' => 'Add an Open Graph image for better social sharing',
            ]);
        }

        // Check noindex
        if ($seoMeta?->no_index) {
            $checks->push([
                'type' => 'info',
                'category' => 'seo',
                'message' => 'This page is set to noindex',
                'action' => 'Search engines will not index this page',
            ]);
        }

        return $checks;
    }

    /**
     * Run full SEO analysis for editor.
     */
    public function analyzeForEditor(Model $page): array
    {
        $result = $this->analysisService->analyze(
            $page,
            $page->seoMeta?->focus_keyword
        );

        return [
            'score' => $result->overallScore,
            'grade' => $result->getGrade(),
            'issues' => $result->issues,
            'suggestions' => $result->suggestions,
            'passed' => $result->passedChecks,
        ];
    }
}
```

### Service Provider Registration

```php
<?php

// In SEOServiceProvider boot method

public function boot(): void
{
    // ... other boot code

    // Register visual editor integration
    if (PackageDetector::hasVisualEditor()) {
        $integration = app(VisualEditorIntegration::class);
        $integration->registerPrePublishChecks();
    }

    // Register media library image size
    if (PackageDetector::hasMediaLibrary()) {
        $integration = app(MediaLibraryIntegration::class);
        $integration->registerSocialImageSize();
    }
}
```

---

## Integration Events

The SEO package dispatches events that other packages can listen to:

```php
<?php

namespace ArtisanPackUI\SEO\Events;

class SeoMetaUpdated
{
    public function __construct(
        public Model $model,
        public SeoMeta $seoMeta,
    ) {}
}

class SitemapGenerated
{
    public function __construct(
        public string $type,
        public int $urlCount,
    ) {}
}

class RedirectMatched
{
    public function __construct(
        public Redirect $redirect,
        public string $requestPath,
    ) {}
}

class AnalysisCompleted
{
    public function __construct(
        public Model $model,
        public AnalysisResultDTO $result,
    ) {}
}
```

---

## Related Documents

- [01-architecture.md](01-architecture.md) - Package architecture
- [03-core-services.md](03-core-services.md) - Service implementations
- [09-configuration.md](09-configuration.md) - Configuration options
