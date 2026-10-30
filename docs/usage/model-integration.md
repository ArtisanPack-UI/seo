---
title: Model Integration
---

# Model Integration

The `HasSeo` trait is the foundation of ArtisanPack UI SEO. This guide covers all available methods and customization options for integrating SEO with your Eloquent models.

## Adding the Trait

```php
<?php

namespace App\Models;

use ArtisanPackUI\SEO\Traits\HasSeo;
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

### Models Without the Trait

Since **1.7.1**, the package also recognizes a `seoMeta` relation registered at runtime with `Model::resolveRelationUsing()`. Use this for models you can't edit, such as models shipped by another package:

```php
use ArtisanPackUI\SEO\Models\SeoMeta;
use Vendor\Package\Models\Page;

// In a service provider's boot() method
Page::resolveRelationUsing('seoMeta', function (Page $page) {
    return $page->morphOne(SeoMeta::class, 'seoable');
});
```

Meta tag components, the `seoMeta()` helper, `SeoService`, `HreflangService`, content analysis, and the visual editor integration all pick up the dynamic relation. The `HasSeo` convenience methods (`getSeoTitle()`, `updateSeoMeta()`, and so on) aren't available on these models; use `seo()->updateSeoMeta($page, [...])` and `seo()->getSeoMeta($page)` instead.

To check for the relation in your own code, use `SeoMetaResolver` rather than `method_exists()`, which misses dynamic relations:

```php
use ArtisanPackUI\SEO\Support\SeoMetaResolver;

SeoMetaResolver::hasRelation($page); // true for HasSeo or resolveRelationUsing()
SeoMetaResolver::resolve($page);     // SeoMeta|null
```

### Custom Morph Types

`SeoMeta::seoable` and `SitemapEntry::sitemapable` normally instantiate the stored `seoable_type` / `sitemapable_type` as a class (or a morph-map alias). If one model is shared by several tables and its `getMorphClass()` encodes the table, such as `App\Models\Record:products`, that string isn't a class and the lookup throws.

Since **1.8.0**, you can resolve such types with the `ap.seo.resolveMorphModel` filter. Return a fresh, unsaved instance configured for the stored type, or pass the incoming value through for types you don't own:

```php
use App\Models\Record;
use Illuminate\Database\Eloquent\Model;

addFilter('ap.seo.resolveMorphModel', function (?Model $model, string $type): ?Model {
    $prefix = Record::class . ':';

    if (null !== $model || ! str_starts_with($type, $prefix)) {
        return $model;
    }

    return (new Record())->setTable(substr($type, strlen($prefix)));
});
```

The filter covers lazy loading (`$seoMeta->seoable`) and eager loading (`SeoMeta::with('seoable')`), so the llms.txt and news sitemap generators, hreflang, and the `getEffectiveTitle()` / `getEffectiveDescription()` / `getEffectiveOgImage()` fallbacks all work for these models. Use `MorphModelResolver::canResolve($type)` to check whether a stored type can be loaded.

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
    'no_index' => false,
]);

// Only updates provided fields
// Returns the updated SeoMeta model
```

### getSeoData()

Get all SEO data as an array, with fallbacks applied:

```php
$data = $post->getSeoData();

// Returns:
// [
//     'title' => '...',
//     'description' => '...',
//     'canonical_url' => '...',
//     'robots' => 'index, follow',
//     'focus_keyword' => '...',
//     'open_graph' => ['title' => '...', 'description' => '...', 'image' => '...', 'type' => '...', 'locale' => '...', 'site_name' => '...'],
//     'twitter' => ['card' => '...', 'title' => '...', 'description' => '...', 'image' => '...', 'site' => '...', 'creator' => '...'],
//     'schema' => ['type' => '...', 'markup' => [...]],
//     'hreflang' => [...],
//     'sitemap' => ['include' => true, 'priority' => null, 'changefreq' => null],
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
// 4. The app name
```

This is the base title. Use `seoTitle($title)` to add the site name.

## Description Methods

### getSeoDescription()

Get the effective SEO description with fallbacks:

```php
$description = $post->getSeoDescription();

// Fallback order:
// 1. meta_description from SEO meta, at its full length
// 2. Model's 'excerpt', 'description', or 'content' attribute, shortened
//    to seo.defaults.description_max_length
// 3. seo.defaults.meta_description from config
```

## Image Methods

### getSeoImage()

Get the effective SEO image URL:

```php
$image = $post->getSeoImage();

// Fallback order:
// 1. og_image_id (Media Library), then og_image from SEO meta
// 2. Model's 'featured_image' attribute
// 3. seo.open_graph.default_image from config
```

## Public URLs

Canonical tags, `og:url`, schema, and sitemap entries share one resolver, so they can't disagree. It tries these in order:

1. A saved `canonical_url`
2. `getPublicUrl()` from the `HasPublicUrl` contract
3. `getUrl()` on the model
4. A `permalink` attribute

After that, canonical tags and `og:url` use the current request URL, schema leaves `url` out, and sitemaps try a `{model}.show` route. A model with no URL gets no sitemap entry. URLs are never guessed from a model's slug. A relative URL is built from `app.url`.

Implement `HasPublicUrl` to declare a model's URL:

```php
use ArtisanPackUI\SEO\Contracts\HasPublicUrl;
use ArtisanPackUI\SEO\Traits\HasSeo;

class Post extends Model implements HasPublicUrl
{
    use HasSeo;

    public function getPublicUrl(): string
    {
        return route('posts.show', $this);
    }
}
```

Don't read `canonical_url` inside `getPublicUrl()`, because that accessor runs the resolver and would call the method again.

### canonical_url

Get the resolved URL for a model:

```php
$url = $post->canonical_url;
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

### robots_meta

Get the full robots meta content:

```php
$robots = $post->robots_meta;
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
// - no_index is true
// - exclude_from_sitemap is true
```

### getSitemapPriority()

Get the sitemap priority for this model:

```php
$priority = $post->getSitemapPriority();
// Returns: 0.0 to 1.0, or null when no priority is set
```

A saved value wins, then `seo.sitemap.default_priority`. With neither, `priority` is left out of the sitemap.

### getSitemapChangefreq()

Get the sitemap change frequency:

```php
$changefreq = $post->getSitemapChangefreq();
// Returns: always, hourly, daily, weekly, monthly, yearly, never, or null
```

A saved value wins, then `seo.sitemap.default_frequency`. With neither, `changefreq` is left out of the sitemap.

### getSitemapImages()

Get the images listed for this model in the image sitemap. Override it to list a page's images. By default it returns the SEO image, leaving out `seo.open_graph.default_image`.

## Schema Methods

### getSchemaType()

Get the Schema.org type for this model:

```php
$type = $post->getSchemaType();
// Returns: Article, BlogPosting, Product, etc.
```

### setSchemaType()

Set the Schema.org type for this model:

```php
$post->setSchemaType('Article');
```

Custom properties are stored in `schema_markup` and merged into the generated schema.

## Open Graph and Twitter Cards

Get DTOs through the SEO service:

```php
$openGraph = seo()->getOpenGraph($post);
$twitterCard = seo()->getTwitterCard($post);

$openGraph->title;
$openGraph->imageAlt;
$openGraph->publishedTime; // Only for the article type
$twitterCard->card;
```

## Hreflang Methods

### hreflang

Get hreflang URLs:

```php
$hreflang = $post->hreflang;
// Returns array:
// [
//     'en' => 'https://...',
//     'fr' => 'https://...',
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

## Analysis

Run analysis through `AnalysisService`:

```php
use ArtisanPackUI\SEO\Services\AnalysisService;

$result = app(AnalysisService::class)->analyze($post);
```

See [SEO Analysis](Advanced-Analysis).

## Model Events

Models with the trait are observed automatically. Saving a model updates its sitemap entry and clears the sitemap cache. Force deleting it removes its SEO meta and sitemap entry. Soft deletes keep them until the model is restored or force deleted.

## Customization Example

Here's a complete example of customizing SEO for a model:

```php
<?php

namespace App\Models;

use ArtisanPackUI\SEO\Contracts\HasPublicUrl;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;

class Product extends Model implements HasPublicUrl
{
    use HasSeo;

    // Custom title
    public function getSeoTitle(): string
    {
        return $this->seoMeta?->meta_title ?: $this->product_name . ' - ' . $this->brand->name;
    }

    // Custom image
    public function getSeoImage(): ?string
    {
        return $this->images->first()?->url ?? $this->category->default_image;
    }

    // Custom URL
    public function getPublicUrl(): string
    {
        return route('products.show', [
            'category' => $this->category->slug,
            'product' => $this->slug,
        ]);
    }

    // Custom sitemap images
    public function getSitemapImages(): ?array
    {
        return $this->images->map(fn ($image) => ['loc' => $image->url])->all();
    }
}
```

## Next Steps

- [Meta Tags](Usage-Meta-Tags) - Meta tag management
- [Social Media](Usage-Social-Media) - Open Graph and Twitter Cards
- [Schema.org](Usage-Schema) - Structured data
- [Models Reference](Api-Models) - SeoMeta model documentation
