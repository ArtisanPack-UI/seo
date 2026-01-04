# HasSeo Trait Implementation

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::High"

## Task Description

Create the `HasSeo` trait that can be added to any Eloquent model to enable SEO functionality.

## Acceptance Criteria

- [ ] Trait defines `morphOne` relationship to `SeoMeta`
- [ ] `getSeoData()` method returns formatted SEO data
- [ ] `seoMeta` relationship auto-creates on first access if needed
- [ ] Trait registers `SeoObserver` for cache invalidation
- [ ] Helper methods: `getSeoTitle()`, `getSeoDescription()`, `getSeoImage()`
- [ ] Works with any Eloquent model
- [ ] Unit tests for trait functionality

## Context

The `HasSeo` trait is the primary way developers integrate SEO into their models.

**Related Issues:**
- Depends on: #01-02-database-layer
- Required by: #01-04-core-services

## Notes

### Trait Structure
```php
trait HasSeo
{
    public static function bootHasSeo(): void
    {
        static::observe(SeoObserver::class);
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function getOrCreateSeoMeta(): SeoMeta
    {
        return $this->seoMeta ?? $this->seoMeta()->create([]);
    }

    public function getSeoData(): array
    {
        // Returns formatted array of all SEO data
    }

    public function getSeoTitle(): string
    {
        return $this->seoMeta?->meta_title
            ?? $this->title
            ?? '';
    }
}
```

**Reference:** [04-traits-and-models.md](../04-traits-and-models.md)
