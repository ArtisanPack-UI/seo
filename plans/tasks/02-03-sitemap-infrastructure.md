# Sitemap Infrastructure

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::High"

## Task Description

Create the database migration, model, and base infrastructure for sitemap generation.

## Acceptance Criteria

- [ ] Migration creates `sitemap_entries` table
- [ ] `SitemapEntry` model with proper relationships
- [ ] Model scopes: `indexable()`, `byType()`, `recentlyUpdated()`
- [ ] Auto-create/update entries when models with HasSeo are saved
- [ ] Entries track: URL, last modified, priority, change frequency
- [ ] Unit tests for model

## Context

The sitemap infrastructure stores and manages sitemap entries for efficient generation.

**Related Issues:**
- Depends on: Phase 1 completion
- Required by: #02-04-sitemap-service

## Notes

### Table Schema
```php
Schema::create('sitemap_entries', function (Blueprint $table) {
    $table->id();
    $table->morphs('sitemapable');
    $table->string('url', 500)->unique();
    $table->string('type')->default('page'); // page, post, product, etc.
    $table->timestamp('last_modified')->nullable();
    $table->decimal('priority', 2, 1)->default(0.5);
    $table->string('changefreq')->default('weekly');
    $table->boolean('is_indexable')->default(true);
    $table->json('images')->nullable();
    $table->json('videos')->nullable();
    $table->timestamps();

    $table->index(['type', 'is_indexable']);
    $table->index('last_modified');
});
```

### Model
```php
class SitemapEntry extends Model
{
    public function sitemapable(): MorphTo;

    public function scopeIndexable($query);
    public function scopeByType($query, string $type);
}
```

**Reference:** [02-database-schema.md](../02-database-schema.md)
