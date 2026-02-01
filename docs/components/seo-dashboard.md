---
title: SEO Dashboard
---

# SEO Dashboard

The SEO Dashboard is a Livewire component that provides an overview of your site's SEO health and key metrics.

## Basic Usage

```blade
<livewire:seo-dashboard />
```

## Features

- Overview statistics for SEO coverage
- Content without SEO meta
- Redirect statistics
- Sitemap status
- Recent SEO updates
- Quick actions

## Dashboard Sections

### SEO Coverage

Shows the percentage of content with SEO metadata:

- Total models with HasSeo trait
- Models with meta title set
- Models with meta description set
- Models with Open Graph configured
- Models with schema markup

### Missing SEO Data

Lists content that needs SEO attention:

- Pages without meta title
- Pages without meta description
- Pages without Open Graph image
- Pages with noindex (intentional exclusions)

### Redirect Statistics

Overview of URL redirects:

- Total redirects
- Active redirects
- Total hits
- Most popular redirects
- Recent 404s (if tracking enabled)

### Sitemap Status

Information about your sitemaps:

- Last generation time
- Total URLs in sitemap
- Sitemap types enabled
- Submission status to search engines

### Recent Activity

Timeline of recent SEO updates:

- Recently modified SEO meta
- New redirects created
- Sitemap regenerations

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `showCoverage` | bool | true | Show coverage stats |
| `showRedirects` | bool | true | Show redirect stats |
| `showSitemap` | bool | true | Show sitemap status |
| `showActivity` | bool | true | Show recent activity |
| `limit` | int | 10 | Items per list |

## Examples

### Full Dashboard

```blade
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">SEO Dashboard</h1>
    <livewire:seo-dashboard />
</div>
```

### Selective Sections

```blade
<livewire:seo-dashboard
    :show-redirects="false"
    :show-sitemap="false"
/>
```

### Widget Style

```blade
<div class="grid grid-cols-2 gap-6">
    <div class="card">
        <livewire:seo-dashboard
            :show-redirects="false"
            :show-activity="false"
        />
    </div>

    <div class="card">
        {{-- Other dashboard widgets --}}
    </div>
</div>
```

## Quick Actions

The dashboard provides quick action buttons:

- **Generate Sitemap** - Regenerate all sitemaps
- **Submit Sitemap** - Submit to search engines
- **Clear Cache** - Clear SEO cache
- **View All Redirects** - Link to redirect manager
- **Run Analysis** - Bulk SEO analysis

## Metrics Breakdown

### Coverage Metrics

```php
// Metrics provided
[
    'total_models' => 150,
    'with_title' => 142,
    'with_description' => 138,
    'with_og_image' => 95,
    'with_schema' => 80,
    'coverage_percent' => 92,
]
```

### Redirect Metrics

```php
[
    'total' => 45,
    'active' => 42,
    'inactive' => 3,
    'total_hits' => 12543,
    'top_redirects' => [...],
]
```

### Sitemap Metrics

```php
[
    'last_generated' => '2024-01-15 10:30:00',
    'total_urls' => 2345,
    'types' => ['standard', 'image', 'video'],
    'submitted_to' => ['google', 'bing'],
]
```

## Customization

### Publishing Views

```bash
php artisan vendor:publish --tag=seo-views
```

### Extending the Component

```php
namespace App\Livewire;

use ArtisanPackUI\Seo\Livewire\SeoDashboard as BaseSeoDashboard;

class CustomSeoDashboard extends BaseSeoDashboard
{
    public function getCustomMetrics(): array
    {
        // Add custom metrics
        return [
            'custom_stat' => $this->calculateCustomStat(),
        ];
    }
}
```

## Events

| Event | Description |
|-------|-------------|
| `sitemap-generated` | After sitemap regeneration |
| `cache-cleared` | After cache clear |
| `sitemap-submitted` | After sitemap submission |

## Refresh Behavior

The dashboard can auto-refresh:

```blade
<livewire:seo-dashboard wire:poll.30s />
```

Or manual refresh:

```blade
<button wire:click="$refresh">Refresh Stats</button>
```

## Integration

### With Other Dashboard Tools

```blade
<div class="admin-dashboard">
    {{-- Main dashboard --}}
    <livewire:admin-dashboard />

    {{-- SEO widget --}}
    <div class="dashboard-widget">
        <livewire:seo-dashboard
            :show-activity="false"
            :limit="5"
        />
    </div>
</div>
```

## Next Steps

- [SEO Meta Editor](Seo-Meta-Editor) - Edit SEO settings
- [Redirect Manager](Redirect-Manager) - Manage redirects
- [Analysis Panel](Analysis-Panel) - Content analysis
- [Sitemaps](Advanced-Sitemaps) - Sitemap documentation
