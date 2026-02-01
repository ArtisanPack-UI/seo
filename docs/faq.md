---
title: FAQ
---

# Frequently Asked Questions

Common questions about ArtisanPack UI SEO.

## General

### What Laravel versions are supported?

ArtisanPack UI SEO supports Laravel 10, 11, and 12. See the [Requirements](Installation-Requirements) page for full details.

### Do I need Livewire to use this package?

No. Livewire is only required for the admin components (SEO Meta Editor, Redirect Manager, etc.). The Blade components for rendering SEO tags work without Livewire.

### Can I use this with any Eloquent model?

Yes. Simply add the `HasSeo` trait to any model:

```php
use ArtisanPackUI\Seo\Traits\HasSeo;

class Product extends Model
{
    use HasSeo;
}
```

### Does this work with SPA/headless setups?

Yes. You can use the services and helpers to generate SEO data as JSON for your frontend:

```php
return response()->json([
    'seo' => $post->getSeoData(),
]);
```

## Meta Tags

### How do I set a custom meta title for a page?

```php
$post->updateSeoMeta([
    'meta_title' => 'My Custom Title | My Site',
]);
```

### What happens if I don't set meta tags?

The package automatically generates fallback values from your model's attributes:
- Title: Uses `title`, `name`, or model class name
- Description: Uses `description`, `excerpt`, or truncated `content`
- Image: Uses `image`, `featured_image`, or config default

### How do I remove the site name suffix from titles?

Set an empty separator in your config:

```php
'site' => [
    'separator' => '',
],
```

Or per-page:

```php
$title = seoTitle('My Page', '');
```

### How do I set noindex for draft posts?

```php
$post->updateSeoMeta([
    'noindex' => true,
]);
```

Or automatically in your model:

```php
protected static function boot()
{
    parent::boot();

    static::saving(function ($post) {
        if ($post->status === 'draft') {
            $post->getOrCreateSeoMeta()->update(['no_index' => true]);
        }
    });
}
```

## Open Graph & Twitter

### Why aren't my Open Graph images showing on Facebook?

1. Verify the image URL is publicly accessible
2. Image should be at least 1200x630 pixels
3. Use absolute URLs, not relative
4. Clear Facebook's cache using [Sharing Debugger](https://developers.facebook.com/tools/debug/)

### How do I set different images for different social platforms?

```php
$post->updateSeoMeta([
    'og_image' => 'https://example.com/og-image.jpg',
    'twitter_image' => 'https://example.com/twitter-image.jpg',
    'pinterest_image' => 'https://example.com/pinterest-image.jpg',
]);
```

### What Twitter card type should I use?

- `summary` - For general content with small thumbnail
- `summary_large_image` - For articles/blogs with large image (recommended)
- `app` - For app promotion
- `player` - For video/audio content

## Schema.org

### Which schema type should I use for my content?

| Content Type | Schema |
|--------------|--------|
| Blog posts | `BlogPosting` or `Article` |
| Products | `Product` |
| Events | `Event` |
| Local business | `LocalBusiness` |
| FAQ pages | `FAQPage` |
| Service pages | `Service` |
| General pages | `WebPage` |

### How do I add multiple schemas to one page?

Store multiple schemas in the schema_data:

```php
$post->updateSeoMeta([
    'schema_data' => [
        '@graph' => [
            ['@type' => 'Article', ...],
            ['@type' => 'BreadcrumbList', ...],
        ],
    ],
]);
```

### How do I validate my schema markup?

Use [Google's Rich Results Test](https://search.google.com/test/rich-results) or [Schema.org Validator](https://validator.schema.org/).

## Redirects

### What's the difference between 301 and 302 redirects?

- **301** (Permanent): SEO equity transfers to new URL. Use for permanent URL changes.
- **302** (Temporary): No SEO transfer. Use for temporary redirects.

### How do I create a redirect programmatically?

```php
seoCreateRedirect(
    source: '/old-page',
    target: '/new-page',
    type: 'exact',
    statusCode: 301
);
```

### Why isn't my regex redirect working?

1. Ensure the pattern is valid regex (no delimiters needed)
2. Check capture groups match the target placeholders
3. Test with `php artisan seo:test-redirect /your/path`

### How do I redirect entire sections?

Use wildcard redirects:

```php
Redirect::create([
    'source' => '/blog/*',
    'target' => '/articles/$1',
    'type' => 'wildcard',
]);
```

## Sitemaps

### How often should I regenerate sitemaps?

For most sites, daily regeneration is sufficient:

```php
Schedule::command('seo:generate-sitemap')->daily();
```

For news sites, consider more frequent updates.

### Why aren't all my pages in the sitemap?

Pages are excluded if:
- `noindex` is true
- `exclude_from_sitemap` is true
- Model's `shouldBeInSitemap()` returns false

### How do I add custom URLs to the sitemap?

Create a custom sitemap provider:

```php
class CustomSitemapProvider implements SitemapProviderInterface
{
    public function getEntries(): Collection
    {
        return collect([
            ['url' => '/custom-page', 'priority' => 0.8],
        ]);
    }
}
```

### How do I exclude certain pages from the sitemap?

```php
$page->updateSeoMeta([
    'exclude_from_sitemap' => true,
]);
```

## Performance

### How do I improve SEO package performance?

1. **Enable caching** - Use Redis in production
2. **Queue analysis** - Run analysis in background
3. **Cache sitemaps** - Set longer TTL for sitemaps
4. **Warm caches** - Pre-populate after deployments

See [Caching](Advanced-Caching) for details.

### How do I clear the cache?

```bash
php artisan seo:clear-cache
```

Or programmatically:

```php
seoClearCache($post);  // Single model
seoClearCache();       // All SEO caches
```

## Livewire Components

### Why isn't the SEO Meta Editor saving?

1. Ensure Livewire is properly installed
2. Check for JavaScript errors in console
3. Verify the model uses `HasSeo` trait
4. Check for validation errors

### How do I customize the SEO editor appearance?

Publish and modify the views:

```bash
php artisan vendor:publish --tag=seo-views
```

## Multi-language

### How do I set up hreflang tags?

```php
$post->updateSeoMeta([
    'hreflang' => [
        'en' => 'https://example.com/post',
        'fr' => 'https://example.fr/article',
        'x-default' => 'https://example.com/post',
    ],
]);
```

### Do I need separate SEO records for each language?

It depends on your setup:
- **Same model, different URLs**: Use hreflang array
- **Separate models per language**: Each model has its own SEO meta

## Integration

### Does this work with ArtisanPack Media Library?

Yes. If the Media Library is installed, you can select images from it for social sharing:

```php
$post->updateSeoMeta([
    'og_image_id' => $media->id,
]);
```

### Can I use this with other SEO packages?

We recommend using only one SEO package to avoid conflicts. If migrating, disable the old package first and migrate data to ArtisanPack SEO.

### How do I migrate from another SEO package?

1. Export existing SEO data
2. Create a migration command to import into `seo_meta` table
3. Map fields appropriately
4. Test thoroughly before removing old package

## Troubleshooting

For common issues and solutions, see the [Troubleshooting](Troubleshooting) guide.

## Getting Help

- **Documentation**: You're reading it!
- **Issues**: [GitLab Issues](https://gitlab.com/jacob-martella-web-design/artisanpack-ui/seo/-/issues)
- **Source Code**: [GitLab Repository](https://gitlab.com/jacob-martella-web-design/artisanpack-ui/seo)
