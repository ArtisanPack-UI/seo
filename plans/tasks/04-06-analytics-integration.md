# Analytics Integration

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::Low"

## Task Description

Create the optional integration with the `artisanpack-ui/analytics` package for Google Search Console data.

## Acceptance Criteria

- [ ] `PackageDetector::hasAnalytics()` check
- [ ] `AnalyticsIntegration` service class
- [ ] Get Search Console data for a URL
- [ ] Get top queries for a page
- [ ] Check if Search Console is configured
- [ ] Get SEO performance summary for dashboard
- [ ] `SeoDashboard` Livewire component
- [ ] Period selector (7d, 30d, 90d)
- [ ] Stats display: clicks, impressions, avg position, CTR
- [ ] Top pages and top queries tables
- [ ] Graceful handling when analytics not available
- [ ] All strings wrapped in `__()` for translation
- [ ] Uses `<x-artisanpack-*>` components throughout
- [ ] Feature tests with mocked analytics

## Context

This integration displays SEO performance data from Google Search Console alongside on-page SEO analysis.

**Related Issues:**
- Depends on: Phase 1 completion

## Notes

### AnalyticsIntegration
```php
class AnalyticsIntegration
{
    public function getSearchConsoleData(string $url, string $period = '30d'): ?array;
    public function getTopQueries(string $url, int $limit = 10): Collection;
    protected function hasSearchConsoleAccess(): bool;
    public function getSeoPerformanceSummary(string $period = '30d'): array;
}
```

### Performance Summary Structure
```php
return [
    'clicks' => $searchConsole->getTotalClicks($period),
    'impressions' => $searchConsole->getTotalImpressions($period),
    'avgPosition' => $searchConsole->getAveragePosition($period),
    'avgCtr' => $searchConsole->getAverageCtr($period),
    'topPages' => $searchConsole->getTopPages($period, 5),
    'topQueries' => $searchConsole->getTopQueries($period, 5),
];
```

### SeoDashboard Component
```php
class SeoDashboard extends Component
{
    public string $period = '30d';
    public array $performance = [];

    public function mount(): void;
    public function loadPerformance(): void;
    public function updatedPeriod(): void;
}
```

### View Uses ArtisanPack Components
```blade
<x-artisanpack-select wire:model.live="period" :options="$periodOptions" />
<x-artisanpack-stat label="{{ __('Clicks') }}" :value="$performance['clicks']" />
<x-artisanpack-table :headers="$pageHeaders" :rows="$performance['topPages']" />
```

**Reference:** [08-integrations.md](../08-integrations.md)
