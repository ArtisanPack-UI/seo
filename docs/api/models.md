---
title: Models
---

# Models

This page documents the Eloquent models provided by ArtisanPack UI SEO.

## SeoMeta

The `SeoMeta` model stores SEO metadata for any Eloquent model using a polymorphic relationship.

### Table Structure

```php
Schema::create('seo_meta', function (Blueprint $table) {
    $table->id();
    $table->morphs('seoable');

    // Basic meta
    $table->string('meta_title')->nullable();
    $table->text('meta_description')->nullable();
    $table->string('canonical_url')->nullable();

    // Robots
    $table->boolean('noindex')->default(false);
    $table->boolean('nofollow')->default(false);

    // Open Graph
    $table->string('og_title')->nullable();
    $table->text('og_description')->nullable();
    $table->string('og_image')->nullable();
    $table->unsignedBigInteger('og_image_id')->nullable();
    $table->string('og_type')->nullable();
    $table->string('og_locale')->nullable();
    $table->string('og_site_name')->nullable();

    // Twitter
    $table->string('twitter_card')->nullable();
    $table->string('twitter_title')->nullable();
    $table->text('twitter_description')->nullable();
    $table->string('twitter_image')->nullable();
    $table->unsignedBigInteger('twitter_image_id')->nullable();
    $table->string('twitter_site')->nullable();
    $table->string('twitter_creator')->nullable();

    // Pinterest & Slack
    $table->text('pinterest_description')->nullable();
    $table->string('pinterest_image')->nullable();
    $table->string('slack_title')->nullable();
    $table->text('slack_description')->nullable();

    // Schema
    $table->string('schema_type')->nullable();
    $table->json('schema_data')->nullable();

    // Keywords
    $table->string('focus_keyword')->nullable();
    $table->json('secondary_keywords')->nullable();

    // Hreflang
    $table->json('hreflang')->nullable();

    // Sitemap
    $table->boolean('exclude_from_sitemap')->default(false);
    $table->decimal('sitemap_priority', 2, 1)->default(0.5);
    $table->string('sitemap_changefreq')->default('weekly');

    $table->timestamps();
});
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `meta_title` | string\|null | Custom meta title |
| `meta_description` | string\|null | Custom meta description |
| `canonical_url` | string\|null | Custom canonical URL |
| `noindex` | bool | Prevent indexing |
| `nofollow` | bool | Prevent link following |
| `og_title` | string\|null | Open Graph title |
| `og_description` | string\|null | Open Graph description |
| `og_image` | string\|null | Open Graph image URL |
| `og_image_id` | int\|null | Media Library image ID |
| `og_type` | string\|null | Open Graph type |
| `og_locale` | string\|null | Open Graph locale |
| `og_site_name` | string\|null | Open Graph site name |
| `twitter_card` | string\|null | Twitter card type |
| `twitter_title` | string\|null | Twitter title |
| `twitter_description` | string\|null | Twitter description |
| `twitter_image` | string\|null | Twitter image URL |
| `twitter_site` | string\|null | Twitter @site handle |
| `twitter_creator` | string\|null | Twitter @creator handle |
| `schema_type` | string\|null | Schema.org type |
| `schema_data` | array\|null | Custom schema data |
| `focus_keyword` | string\|null | Primary keyword |
| `secondary_keywords` | array\|null | Additional keywords |
| `hreflang` | array\|null | Hreflang URLs |
| `sitemap_priority` | float | Sitemap priority (0.0-1.0) |
| `sitemap_changefreq` | string | Sitemap change frequency |
| `exclude_from_sitemap` | bool | Exclude from sitemap |

### Relationships

```php
// Polymorphic parent
public function seoable(): MorphTo
{
    return $this->morphTo();
}

// Media Library integration (if installed)
public function ogImage(): BelongsTo
{
    return $this->belongsTo(Media::class, 'og_image_id');
}

public function twitterImage(): BelongsTo
{
    return $this->belongsTo(Media::class, 'twitter_image_id');
}
```

### Casts

```php
protected $casts = [
    'noindex' => 'boolean',
    'nofollow' => 'boolean',
    'exclude_from_sitemap' => 'boolean',
    'schema_data' => 'array',
    'secondary_keywords' => 'array',
    'hreflang' => 'array',
    'sitemap_priority' => 'float',
];
```

### Usage Examples

```php
use ArtisanPackUI\Seo\Models\SeoMeta;

// Find by model
$meta = SeoMeta::where('seoable_type', Post::class)
    ->where('seoable_id', $post->id)
    ->first();

// Get all meta for a model type
$postMeta = SeoMeta::where('seoable_type', Post::class)->get();

// Check if model has meta
$hasMeta = SeoMeta::where('seoable_type', Post::class)
    ->where('seoable_id', $post->id)
    ->exists();
```

---

## Redirect

The `Redirect` model stores URL redirect rules.

### Table Structure

```php
Schema::create('redirects', function (Blueprint $table) {
    $table->id();
    $table->string('source');
    $table->string('target');
    $table->enum('type', ['exact', 'regex', 'wildcard'])->default('exact');
    $table->integer('status_code')->default(301);
    $table->boolean('is_active')->default(true);
    $table->unsignedBigInteger('hits')->default(0);
    $table->timestamp('last_hit_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['source', 'is_active']);
});
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `source` | string | Source path/pattern |
| `target` | string | Destination URL |
| `type` | string | Match type (exact, regex, wildcard) |
| `status_code` | int | HTTP status (301, 302, 307, 308) |
| `is_active` | bool | Whether redirect is active |
| `hits` | int | Number of times triggered |
| `last_hit_at` | Carbon\|null | Last triggered timestamp |
| `notes` | string\|null | Documentation notes |

### Scopes

```php
// Active redirects only
Redirect::active()->get();

// Inactive redirects only
Redirect::inactive()->get();

// Permanent redirects (301, 308)
Redirect::permanent()->get();

// Temporary redirects (302, 307)
Redirect::temporary()->get();

// With hits
Redirect::withHits()->get();

// Most popular
Redirect::mostHits()->limit(10)->get();

// Recently hit
Redirect::recentlyHit()->get();

// By type
Redirect::byType('regex')->get();
```

### Methods

```php
$redirect = Redirect::find(1);

// Check if matches a path
$matches = $redirect->matches('/some/path');

// Get the redirect target for a path
$destination = $redirect->getDestination('/some/path');

// Increment hit counter
$redirect->recordHit();

// Toggle active status
$redirect->toggleActive();
```

### Usage Examples

```php
use ArtisanPackUI\Seo\Models\Redirect;

// Create a redirect
$redirect = Redirect::create([
    'source' => '/old-page',
    'target' => '/new-page',
    'type' => 'exact',
    'status_code' => 301,
]);

// Find matching redirect
$redirect = Redirect::active()
    ->get()
    ->first(fn ($r) => $r->matches($path));
```

---

## SitemapEntry

The `SitemapEntry` model tracks sitemap entries.

### Table Structure

```php
Schema::create('sitemap_entries', function (Blueprint $table) {
    $table->id();
    $table->nullableMorphs('sitemapable');
    $table->string('url');
    $table->decimal('priority', 2, 1)->default(0.5);
    $table->string('changefreq')->default('weekly');
    $table->timestamp('lastmod')->nullable();
    $table->json('images')->nullable();
    $table->json('videos')->nullable();
    $table->timestamps();

    $table->unique('url');
});
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `url` | string | Page URL |
| `priority` | float | Priority (0.0-1.0) |
| `changefreq` | string | Change frequency |
| `lastmod` | Carbon\|null | Last modification |
| `images` | array\|null | Image URLs for image sitemap |
| `videos` | array\|null | Video data for video sitemap |

### Relationships

```php
// Polymorphic parent (optional)
public function sitemapable(): MorphTo
{
    return $this->morphTo();
}
```

### Scopes

```php
// Entries with images
SitemapEntry::withImages()->get();

// Entries with videos
SitemapEntry::withVideos()->get();

// By priority
SitemapEntry::where('priority', '>=', 0.8)->get();

// Recently modified
SitemapEntry::where('lastmod', '>=', now()->subDays(7))->get();
```

---

## SeoAnalysisCache

The `SeoAnalysisCache` model caches SEO analysis results.

### Table Structure

```php
Schema::create('seo_analysis_cache', function (Blueprint $table) {
    $table->id();
    $table->foreignId('seo_meta_id')->constrained('seo_meta')->cascadeOnDelete();
    $table->json('results');
    $table->integer('score');
    $table->timestamps();
});
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `seo_meta_id` | int | Related SeoMeta ID |
| `results` | array | Analysis results |
| `score` | int | Overall SEO score |

### Relationships

```php
public function seoMeta(): BelongsTo
{
    return $this->belongsTo(SeoMeta::class);
}
```

## Next Steps

- [Services](./services.md) - Service documentation
- [Helper Functions](./helpers.md) - Helper reference
- [Model Integration](../usage/model-integration.md) - HasSeo trait
