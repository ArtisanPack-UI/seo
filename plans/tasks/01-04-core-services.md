# Core Services Implementation

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::High"

## Task Description

Implement the core service classes: `SeoService`, `MetaTagService`, `SocialMetaService`, and `CacheService`.

## Acceptance Criteria

- [ ] `SeoService` acts as main orchestrator/facade
- [ ] `MetaTagService` generates title, description, canonical, robots tags
- [ ] `SocialMetaService` generates Open Graph and Twitter Card meta
- [ ] `CacheService` handles cache storage and invalidation
- [ ] Services are registered in service container
- [ ] DTOs created: `MetaTagsDTO`, `OpenGraphDTO`, `TwitterCardDTO`
- [ ] Title suffix/separator configuration works
- [ ] Unit tests for each service

## Context

These services form the backbone of the SEO package, handling all meta tag generation and caching.

**Related Issues:**
- Depends on: #01-03-has-seo-trait
- Required by: #01-05-blade-components

## Notes

### SeoService
Main orchestrator that coordinates other services:
```php
class SeoService
{
    public function getAll(Model $model): array;
    public function getMetaTags(Model $model): MetaTagsDTO;
    public function getOpenGraph(Model $model): OpenGraphDTO;
    public function updateSeoMeta(Model $model, array $data): SeoMeta;
    public function buildTitle(string $title, bool $includeSuffix = true): string;
}
```

### MetaTagService
```php
class MetaTagService
{
    public function generate(Model $model, ?SeoMeta $seoMeta = null): MetaTagsDTO;
    public function buildTitle(string $title): string;
    public function buildRobotsDirective(SeoMeta $seoMeta): string;
}
```

### CacheService
```php
class CacheService
{
    public function get(string $key): mixed;
    public function put(string $key, mixed $value): void;
    public function invalidate(Model $model): void;
    public function invalidateAll(): void;
}
```

**Reference:** [03-core-services.md](../03-core-services.md)
