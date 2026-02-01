---
title: Model Integration
---

# Model Integration

The `HasSeo` trait is the foundation of ArtisanPack UI SEO. This guide covers all available methods and customization options for integrating SEO with your Eloquent models.

## Adding the Trait

```php
<?php

namespace App\Models;

use ArtisanPackUI\Seo\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasSeo;
}
```

## Relationship

The trait establishes a polymorphic relationship:

```php
// Access the SEO meta record directly
$seoMeta = $post->seoMeta;

// The relationship is defined as:
public function seoMeta(): MorphOne
{
    return $this->morphOne(SeoMeta::class, 'seoable');
}
```

## Core Methods

### getOrCreateSeoMeta()

Get the existing SEO meta record or create a new one:

```php
$meta = $post->getOrCreateSeoMeta();

// Returns SeoMeta model instance
// Creates one if it doesn't exist
```

### updateSeoMeta(array $data)

Update SEO metadata with the provided data:

```php
$post->updateSeoMeta([
    'meta_title' => 'Custom Title',
    'meta_description' => 'Custom description.',
    'og_title' => 'Social Title',
    'noindex' => false,
]);

// Only updates provided fields
// Returns the updated SeoMeta model
```

### getSeoData()

Get all SEO data as an array (with fallbacks applied):

```php
$data = $post->getSeoData();

// Returns array with all SEO fields:
// [
//     'title' => 'Effective Title',
//     'description' => 'Effective Description',
//     'image' => 'https://...',
//     'og_title' => '...',
//     'og_description' => '...',
//     'twitter_card' => '...',
//     'schema_type' => '...',
//     'noindex' => false,
//     'nofollow' => false,
//     ...
// ]
```

## Title Methods

### getSeoTitle()

Get the effective SEO title with fallbacks:

```php
$title = $post->getSeoTitle();

// Fallback order:
// 1. meta_title from SEO meta
// 2. Model's 'title' attribute
// 3. Model's 'name' attribute
// 4. Model class name
```

### getSeoTitleAttribute()

Override this method to customize title resolution:

```php
protected function getSeoTitleAttribute(): ?string
{
    return $this->headline ?? $this->title;
}
```

### getSeoTitleWithSuffix()

Get title with site name appended:

```php
$fullTitle = $post->getSeoTitleWithSuffix();
// Result: "Post Title | My Site"

// Custom suffix
$fullTitle = $post->getSeoTitleWithSuffix(' - Custom Site');
// Result: "Post Title - Custom Site"
```

## Description Methods

### getSeoDescription()

Get the effective SEO description with fallbacks:

```php
$description = $post->getSeoDescription();

// Fallback order:
// 1. meta_description from SEO meta
// 2. Model's 'description' attribute
// 3. Model's 'excerpt' attribute
// 4. Truncated 'content' attribute
// 5. Site default from config
```

### getSeoDescriptionAttribute()

Override this method to customize description resolution:

```php
protected function getSeoDescriptionAttribute(): ?string
{
    return $this->summary ?? $this->excerpt ?? $this->description;
}
```

## Image Methods

### getSeoImage()

Get the effective SEO image URL:

```php
$image = $post->getSeoImage();

// Fallback order:
// 1. og_image from SEO meta
// 2. Image from og_image_id (Media Library)
// 3. Model's 'image' attribute
// 4. Model's 'featured_image' attribute
// 5. Default image from config
```

### getSeoImageAttribute()

Override this method to customize image resolution:

```php
protected function getSeoImageAttribute(): ?string
{
    return $this->cover_image ?? $this->thumbnail;
}
```

## URL Methods

### getSeoUrl()

Get the canonical URL for the model:

```php
$url = $post->getSeoUrl();

// Default implementation:
// Returns the model's 'url' attribute
// Or generates from route if RouteModelBinding is used
```

### getSeoUrlAttribute()

Override to customize URL generation:

```php
protected function getSeoUrlAttribute(): ?string
{
    return route('posts.show', $this);
}
```

## Indexing Methods

### shouldBeIndexed()

Check if the page should be indexed by search engines:

```php
if ($post->shouldBeIndexed()) {
    // robots: index
}

// Returns true unless noindex is explicitly set
```

### shouldBeFollowed()

Check if links should be followed:

```php
if ($post->shouldBeFollowed()) {
    // robots: follow
}

// Returns true unless nofollow is explicitly set
```

### getRobotsDirective()

Get the full robots meta content:

```php
$robots = $post->getRobotsDirective();
// Result: "index, follow" or "noindex, nofollow" etc.
```

## Sitemap Methods

### shouldBeInSitemap()

Check if the model should be included in sitemaps:

```php
if ($post->shouldBeInSitemap()) {
    // Include in sitemap
}

// Returns false if:
// - noindex is true
// - exclude_from_sitemap is true
```

### getSitemapPriority()

Get the sitemap priority for this model:

```php
$priority = $post->getSitemapPriority();
// Returns: 0.0 to 1.0
```

### getSitemapChangefreq()

Get the sitemap change frequency:

```php
$changefreq = $post->getSitemapChangefreq();
// Returns: always, hourly, daily, weekly, monthly, yearly, never
```

### getSitemapLastmod()

Get the last modification date for sitemap:

```php
$lastmod = $post->getSitemapLastmod();
// Returns: Carbon instance or null
```

## Schema Methods

### getSchemaType()

Get the Schema.org type for this model:

```php
$type = $post->getSchemaType();
// Returns: Article, BlogPosting, Product, etc.
```

### getSchemaData()

Get the custom schema data:

```php
$schemaData = $post->getSchemaData();
// Returns: array of schema properties
```

### buildSchema()

Build the complete schema markup:

```php
$schema = $post->buildSchema();
// Returns: Complete JSON-LD ready array
```

## Open Graph Methods

### getOpenGraphData()

Get all Open Graph data:

```php
$og = $post->getOpenGraphData();
// Returns array:
// [
//     'title' => '...',
//     'description' => '...',
//     'image' => '...',
//     'type' => '...',
//     'url' => '...',
//     'site_name' => '...',
//     'locale' => '...',
// ]
```

## Twitter Card Methods

### getTwitterCardData()

Get all Twitter Card data:

```php
$twitter = $post->getTwitterCardData();
// Returns array:
// [
//     'card' => 'summary_large_image',
//     'title' => '...',
//     'description' => '...',
//     'image' => '...',
//     'site' => '@...',
//     'creator' => '@...',
// ]
```

## Hreflang Methods

### getHreflang()

Get hreflang URLs:

```php
$hreflang = $post->getHreflang();
// Returns array:
// [
//     'en' => 'https://...',
//     'fr' => 'https://...',
//     'x-default' => 'https://...',
// ]
```

### setHreflang(array $urls)

Set hreflang URLs:

```php
$post->setHreflang([
    'en' => 'https://example.com/post',
    'fr' => 'https://example.fr/article',
]);
```

## Analysis Methods

### getSeoAnalysis()

Get SEO analysis results:

```php
$analysis = $post->getSeoAnalysis();
// Returns array of analyzer results:
// [
//     'score' => 75,
//     'readability' => ['status' => 'pass', 'message' => '...'],
//     'keyword_density' => ['status' => 'warning', 'message' => '...'],
//     ...
// ]
```

### refreshSeoAnalysis()

Force refresh the SEO analysis:

```php
$analysis = $post->refreshSeoAnalysis();
// Clears cache and re-runs all analyzers
```

## Model Events

The trait automatically registers model observers:

```php
// On model created
// - Creates default SEO meta record (if auto_create enabled)

// On model updated
// - Clears SEO cache
// - Updates sitemap entry

// On model deleted
// - Deletes associated SEO meta
// - Removes from sitemap
```

## Customization Example

Here's a complete example of customizing SEO for a model:

```php
<?php

namespace App\Models;

use ArtisanPackUI\Seo\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasSeo;

    // Custom title resolution
    protected function getSeoTitleAttribute(): ?string
    {
        return $this->product_name . ' - ' . $this->brand->name;
    }

    // Custom description
    protected function getSeoDescriptionAttribute(): ?string
    {
        return $this->short_description ?? substr($this->description, 0, 160);
    }

    // Custom image
    protected function getSeoImageAttribute(): ?string
    {
        return $this->images->first()?->url ?? $this->category->default_image;
    }

    // Custom URL
    protected function getSeoUrlAttribute(): ?string
    {
        return route('products.show', [
            'category' => $this->category->slug,
            'product' => $this->slug,
        ]);
    }

    // Custom schema type
    public function getSchemaType(): string
    {
        return 'Product';
    }

    // Custom schema data
    public function getSchemaData(): array
    {
        return [
            'name' => $this->product_name,
            'description' => $this->description,
            'sku' => $this->sku,
            'brand' => ['@type' => 'Brand', 'name' => $this->brand->name],
            'offers' => [
                '@type' => 'Offer',
                'price' => $this->price,
                'priceCurrency' => 'USD',
                'availability' => $this->in_stock
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ];
    }
}
```

## Next Steps

- [Meta Tags](Meta-Tags) - Meta tag management
- [Social Media](Social-Media) - Open Graph and Twitter Cards
- [Schema.org](Schema) - Structured data
- [Models Reference](Api-Models) - SeoMeta model documentation
