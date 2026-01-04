# Database Layer - SeoMeta Table and Model

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::High"

## Task Description

Create the `seo_meta` database migration and Eloquent model with polymorphic relationships for storing SEO metadata.

## Acceptance Criteria

- [ ] Migration creates `seo_meta` table with all required columns
- [ ] `SeoMeta` model has proper fillable/casts defined
- [ ] Polymorphic relationship (`seoable`) works correctly
- [ ] JSON columns properly cast for `secondary_keywords`, `schema_markup`, `hreflang`
- [ ] Model scopes for common queries (e.g., `indexable()`, `withFocusKeyword()`)
- [ ] Unit tests for model relationships and casts

## Context

The `seo_meta` table is the core data storage for all SEO metadata across models.

**Related Issues:**
- Depends on: #01-01-package-scaffolding
- Required by: #01-03-has-seo-trait

## Notes

### Table Schema
```php
Schema::create('seo_meta', function (Blueprint $table) {
    $table->id();
    $table->morphs('seoable');

    // Basic meta
    $table->string('meta_title')->nullable();
    $table->text('meta_description')->nullable();
    $table->string('canonical_url')->nullable();
    $table->boolean('no_index')->default(false);
    $table->boolean('no_follow')->default(false);

    // Focus keywords
    $table->string('focus_keyword')->nullable();
    $table->json('secondary_keywords')->nullable();

    // Open Graph
    $table->string('og_title')->nullable();
    $table->text('og_description')->nullable();
    $table->string('og_image')->nullable();
    $table->unsignedBigInteger('og_image_id')->nullable();
    $table->string('og_type')->default('website');

    // Twitter Card
    $table->string('twitter_card')->default('summary_large_image');
    $table->string('twitter_title')->nullable();
    $table->text('twitter_description')->nullable();

    // Schema
    $table->string('schema_type')->nullable();
    $table->json('schema_markup')->nullable();

    // Sitemap
    $table->decimal('sitemap_priority', 2, 1)->default(0.5);
    $table->string('sitemap_changefreq')->default('weekly');
    $table->boolean('exclude_from_sitemap')->default(false);

    // Multi-language
    $table->json('hreflang')->nullable();

    $table->timestamps();

    $table->unique(['seoable_type', 'seoable_id']);
});
```

**Reference:** [02-database-schema.md](../02-database-schema.md)
