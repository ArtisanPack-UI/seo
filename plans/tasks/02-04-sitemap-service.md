# Sitemap Service and Generators

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::High"

## Task Description

Implement the `SitemapService` and various sitemap generators for XML output.

## Acceptance Criteria

- [ ] `SitemapService` orchestrates sitemap generation
- [ ] `SitemapGenerator` creates standard XML sitemaps
- [ ] `SitemapIndexGenerator` creates sitemap index for large sites
- [ ] `ImageSitemapGenerator` for image sitemaps
- [ ] `VideoSitemapGenerator` for video sitemaps
- [ ] `NewsSitemapGenerator` for Google News
- [ ] Sitemaps cached based on config TTL
- [ ] Max 10,000 URLs per sitemap (configurable)
- [ ] `SitemapSubmitter` pings search engines
- [ ] Unit and feature tests

## Context

Sitemaps help search engines discover and index content efficiently.

**Related Issues:**
- Depends on: #02-03-sitemap-infrastructure
- Required by: #02-05-sitemap-routes

## Notes

### SitemapService
```php
class SitemapService
{
    public function generate(?string $type = null): string;
    public function generateIndex(): string;
    public function getTypes(): array;
    public function submit(): void;
}
```

### Custom Sitemap Providers
```php
interface SitemapProviderContract
{
    public function getUrls(): Collection;
    public function getChangeFrequency(): string;
    public function getPriority(): float;
}
```

### Artisan Commands
```bash
php artisan seo:generate-sitemap     # Generate all sitemaps
php artisan seo:submit-sitemap       # Ping search engines
```

**Reference:** [03-core-services.md](../03-core-services.md)
