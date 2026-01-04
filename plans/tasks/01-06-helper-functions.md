# Helper Functions

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::Medium"

## Task Description

Create global helper functions for common SEO operations.

## Acceptance Criteria

- [ ] `seo()` - Returns SeoService instance
- [ ] `seoMeta($model)` - Get model's SEO meta data
- [ ] `seoTitle($title, $suffix)` - Format page title with suffix
- [ ] `seoDescription($description)` - Format meta description
- [ ] Helpers are autoloaded via Composer
- [ ] Unit tests for each helper

## Context

Helper functions provide convenient shortcuts for common operations.

**Related Issues:**
- Depends on: #01-04-core-services
- Part of Phase 1 deliverables

## Notes

### Helper Definitions
```php
if (!function_exists('seo')) {
    function seo(): SeoService
    {
        return app(SeoService::class);
    }
}

if (!function_exists('seoMeta')) {
    function seoMeta(Model $model): ?SeoMeta
    {
        return $model->seoMeta;
    }
}

if (!function_exists('seoTitle')) {
    function seoTitle(string $title, bool $includeSuffix = true): string
    {
        return app(SeoService::class)->buildTitle($title, $includeSuffix);
    }
}

if (!function_exists('seoDescription')) {
    function seoDescription(string $description): string
    {
        return Str::limit($description, 160);
    }
}
```

### Composer Autoload
```json
{
    "autoload": {
        "files": ["src/helpers.php"]
    }
}
```

**Reference:** [03-core-services.md](../03-core-services.md)
