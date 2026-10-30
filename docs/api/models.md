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
    $table->string('canonical_url', 500)->nullable();
    $table->boolean('no_index')->default(false);
    $table->boolean('no_follow')->default(false);
    $table->string('robots_meta')->nullable();

    // Open Graph
    $table->string('og_title')->nullable();
    $table->text('og_description')->nullable();
    $table->string('og_image', 500)->nullable();
    $table->unsignedBigInteger('og_image_id')->nullable();
    $table->string('og_type', 50)->nullable();
    $table->string('og_locale', 10)->nullable();
    $table->string('og_site_name')->nullable();

    // Twitter
    $table->string('twitter_card', 50)->default('summary_large_image');
    $table->string('twitter_title')->nullable();
    $table->text('twitter_description')->nullable();
    $table->string('twitter_image', 500)->nullable();
    $table->unsignedBigInteger('twitter_image_id')->nullable();
    $table->string('twitter_site', 50)->nullable();
    $table->string('twitter_creator', 50)->nullable();

    // Pinterest & Slack
    $table->text('pinterest_description')->nullable();
    $table->string('pinterest_image', 500)->nullable();
    $table->unsignedBigInteger('pinterest_image_id')->nullable();
    $table->string('slack_title')->nullable();
    $table->text('slack_description')->nullable();
    $table->string('slack_image', 500)->nullable();
    $table->unsignedBigInteger('slack_image_id')->nullable();

    // Schema
    $table->string('schema_type', 100)->nullable();
    $table->json('schema_markup')->nullable();

    // Keywords
    $table->string('focus_keyword')->nullable();
    $table->json('secondary_keywords')->nullable();

    // Hreflang
    $table->json('hreflang')->nullable();

    // Sitemap
    $table->decimal('sitemap_priority', 2, 1)->nullable();
    $table->string('sitemap_changefreq', 20)->nullable();
    $table->boolean('exclude_from_sitemap')->default(false);

    $table->timestamps();
});
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `meta_title` | string\|null | Custom meta title |
| `meta_description` | string\|null | Custom meta description |
| `canonical_url` | string\|null | Custom canonical URL |
| `no_index` | bool | Prevent indexing |
| `no_follow` | bool | Prevent link following |
| `og_title` | string\|null | Open Graph title |
| `og_description` | string\|null | Open Graph description |
| `og_image` | string\|null | Open Graph image URL |
| `og_image_id` | int\|null | Media Library image ID |
| `og_type` | string\|null | Open Graph type, inferred when null |
| `og_locale` | string\|null | Open Graph locale |
| `og_site_name` | string\|null | Open Graph site name |
| `twitter_card` | string\|null | Twitter card type |
| `twitter_title` | string\|null | Twitter title |
| `twitter_description` | string\|null | Twitter description |
| `twitter_image` | string\|null | Twitter image URL |
| `twitter_site` | string\|null | Twitter @site handle |
| `twitter_creator` | string\|null | Twitter @creator handle |
| `schema_type` | string\|null | Schema.org type |
| `schema_markup` | array\|null | Custom schema data |
| `focus_keyword` | string\|null | Primary keyword |
| `secondary_keywords` | array\|null | Additional keywords |
| `hreflang` | array\|null | Hreflang URLs |
| `sitemap_priority` | float\|null | Sitemap priority (0.0-1.0), left out of the sitemap when null |
| `sitemap_changefreq` | string\|null | Sitemap change frequency, left out of the sitemap when null |
| `exclude_from_sitemap` | bool | Exclude from sitemap |

### Relationships

```php
// Polymorphic parent
public function seoable(): MorphTo

// Cached analysis
public function analysisCache(): HasOne
```

### Casts

```php
protected $casts = [
    'no_index' => 'boolean',
    'no_follow' => 'boolean',
    'exclude_from_sitemap' => 'boolean',
    'schema_markup' => 'array',
    'secondary_keywords' => 'array',
    'hreflang' => 'array',
    'sitemap_priority' => 'decimal:1',
];
```

### Usage Examples

```php
use ArtisanPackUI\SEO\Models\SeoMeta;

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
    $table->string('from_path');
    $table->string('to_path');
    $table->string('match_type')->default('exact');
    $table->integer('status_code')->default(301);
    $table->boolean('is_active')->default(true);
    $table->unsignedBigInteger('hits')->default(0);
    $table->timestamp('last_hit_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `from_path` | string | Source path/pattern |
| `to_path` | string | Destination URL |
| `match_type` | string | Match type (exact, regex, wildcard) |
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

// By match type
Redirect::ofType('regex')->get();
```

### Methods

```php
$redirect = Redirect::find(1);

// Check if matches a path
$matches = $redirect->matches('/some/path');

// Get the redirect target for a path
$destination = $redirect->getResolvedDestination('/some/path');

// Increment hit counter
$redirect->recordHit();
```

### Usage Examples

```php
use ArtisanPackUI\SEO\Models\Redirect;

// Create a redirect
$redirect = Redirect::create([
    'from_path' => '/old-page',
    'to_path' => '/new-page',
    'match_type' => 'exact',
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
    $table->morphs('sitemapable');
    $table->string('url', 500)->unique();
    $table->string('type', 50)->default('page');
    $table->timestamp('last_modified')->nullable();
    $table->decimal('priority', 2, 1)->nullable();
    $table->string('changefreq', 20)->nullable();
    $table->boolean('is_indexable')->default(true);
    $table->json('images')->nullable();
    $table->json('videos')->nullable();
    $table->timestamps();
});
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `url` | string | Page URL |
| `type` | string | Sitemap type |
| `last_modified` | Carbon\|null | Last modification |
| `priority` | float\|null | Priority (0.0-1.0), left out of the sitemap when null |
| `changefreq` | string\|null | Change frequency, left out of the sitemap when null |
| `is_indexable` | bool | Whether the URL is listed |
| `images` | array\|null | Images for the image sitemap |
| `videos` | array\|null | Video data for the video sitemap |

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
// Indexable entries
SitemapEntry::indexable()->get();

// By sitemap type
SitemapEntry::byType('post')->get();

// Entries with images or videos
SitemapEntry::withImages()->get();
SitemapEntry::withVideos()->get();

// Ordered
SitemapEntry::orderByPriority()->get();
SitemapEntry::orderByLastModified()->get();

// Recently updated
SitemapEntry::recentlyUpdated(7)->get();
```

---

## SeoAnalysisCache

The `SeoAnalysisCache` model caches SEO analysis results.

### Table Structure

```php
Schema::create('seo_analysis_cache', function (Blueprint $table) {
    $table->id();
    $table->foreignId('seo_meta_id')->unique()->constrained('seo_meta')->cascadeOnDelete();
    $table->unsignedTinyInteger('overall_score')->default(0);
    $table->unsignedTinyInteger('readability_score')->default(0);
    $table->unsignedTinyInteger('keyword_score')->default(0);
    $table->unsignedTinyInteger('meta_score')->default(0);
    $table->unsignedTinyInteger('content_score')->default(0);
    $table->json('issues')->nullable();
    $table->json('suggestions')->nullable();
    $table->json('passed_checks')->nullable();
    $table->json('analyzer_results')->nullable();
    $table->timestamp('analyzed_at')->nullable();
    $table->string('focus_keyword_used')->nullable();
    $table->unsignedInteger('content_word_count')->default(0);
    $table->string('content_hash')->nullable();
    $table->timestamps();
});
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `seo_meta_id` | int | Related SeoMeta ID |
| `overall_score` | int | Overall SEO score |
| `readability_score`, `keyword_score`, `meta_score`, `content_score` | int | Category scores |
| `issues`, `suggestions`, `passed_checks` | array\|null | Analysis findings |
| `analyzer_results` | array\|null | Per-analyzer results |
| `content_hash` | string\|null | Fingerprint of the analyzed content |

### Relationships

```php
public function seoMeta(): BelongsTo
{
    return $this->belongsTo(SeoMeta::class);
}
```

## Next Steps

- [Services](Api-Services) - Service documentation
- [Helper Functions](Api-Helpers) - Helper reference
- [Model Integration](Usage-Model-Integration) - HasSeo trait
