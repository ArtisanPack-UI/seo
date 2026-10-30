---
title: Upgrading to 1.9.0
---

# Upgrading to 1.9.0

This guide covers upgrading from v1.8.x to v1.9.0. This release gives every model one source for its public URL, corrects the meta tag output, trims sitemap defaults, adds software schema types, extends llms.txt, and adds an optional static `robots.txt`.

This release has **breaking changes**. Two migrations change existing data, and a published `config/seo.php` needs editing.

## Summary

- **New**: `HasPublicUrl` contract and `PublicUrlResolver`, shared by canonical tags, `og:url`, schema, and sitemaps (#117).
- **New**: `SoftwareSourceCode` and `SoftwareApplication` schema types (#119).
- **New**: `og:image:width`, `og:image:height`, `og:image:alt`, and `twitter:image:alt` when the details are known (#120).
- **New**: `seo.robots.write_static` and the `seo:write-robots` command (#123).
- **New**: llms.txt lists sitemap provider URLs and falls back to the homepage's description (#122).
- **Breaking**: URLs are no longer guessed from a model's slug (#117).
- **Breaking**: The sitemap index only lists per-type children (#116).
- **Breaking**: `article:*` tags moved to Open Graph, and `keywords` is no longer emitted (#120).
- **Breaking**: Sitemaps omit `priority` and `changefreq` unless set for a URL (#121).
- **Breaking**: No search engine is pinged by default, because Bing's endpoint returns 410 Gone (#121).
- **Changed**: The image sitemap is on by default (#121).
- **Fixed**: `og:type` inference now runs for models with a `seo_meta` row (#118).
- **Fixed**: Descriptions stay within their limit, and saved descriptions are no longer shortened (#120).

## Before You Upgrade

If you use `artisanpack-ui/cms-framework`, upgrade it to **2.17 or later first**. seo 1.9 reads a post's URL from its `permalink` attribute, and earlier cms-framework releases hardcode that to `/blog/{slug}`, which would move your post canonicals (cms-framework#440).

## Steps

### 1. Update the package

```bash
composer update artisanpack-ui/seo
```

### 2. Run the migrations

```bash
php artisan migrate
```

Two migrations change existing data, and neither can tell a default from a deliberate choice:

| Migration | What it does |
|-----------|--------------|
| `make_og_type_nullable_on_seo_meta_table` | Makes `og_type` nullable with no default, and clears every `website` value so the type is inferred |
| `make_sitemap_hints_nullable` | Makes `seo_meta.sitemap_priority`, `seo_meta.sitemap_changefreq`, `sitemap_entries.priority`, and `sitemap_entries.changefreq` nullable with no default, and clears every `0.5` and `weekly` value |

If you chose any of those values on purpose, set them again after migrating. Cached sitemaps can show the old values until they expire, up to `seo.sitemap.cache_ttl`, unless you clear them:

```php
app(\ArtisanPackUI\SEO\Services\SitemapService::class)->clearCache();
```

### 3. Update a published config

If you published `config/seo.php`, these changes won't reach you automatically.

**Set the sitemap defaults to null.** Otherwise the next save writes them back to every URL:

```php
'sitemap' => [
    'default_frequency' => null,
    'default_priority'  => null,
],
```

**Remove `bing` from `search_engines`.** Its endpoint returns 410 Gone:

```php
'sitemap' => [
    'search_engines' => [],
],
```

An empty list no longer falls back to Bing. List your sitemap in robots.txt and use [IndexNow](Advanced-Indexnow) to announce updates.

**Decide on the image sitemap.** The default is now on, but a published `false` stays off:

```php
'sitemap' => [
    'types' => [
        'image' => true,
    ],
],
```

It only joins the sitemap index when an indexable entry has images. `default_image` is no longer recorded as a page's image, though existing entries keep theirs until the model is saved again.

**Add the new keys if you want them:**

```php
'open_graph' => [
    'default_image_width'  => 1200,
    'default_image_height' => 630,
    'default_image_alt'    => 'Your site logo',
],

'robots' => [
    'write_static' => env('SEO_ROBOTS_WRITE_STATIC', false),
],
```

### 4. Give your models a public URL

A model that relied on the `url($model->slug)` fallback now gets the request URL for its canonical and `og:url`, no schema `url`, and no new sitemap entry unless it has a `{model}.show` route. Existing sitemap entries keep their URL.

The resolver tries a saved `canonical_url`, then `HasPublicUrl`, then `getUrl()`, then a `permalink` attribute:

```php
use ArtisanPackUI\SEO\Contracts\HasPublicUrl;

class Post extends Model implements HasPublicUrl
{
    use HasSeo;

    public function getPublicUrl(): string
    {
        return route('posts.show', $this);
    }
}
```

Don't read `canonical_url` inside `getPublicUrl()`, because that accessor runs the resolver. A custom `getCanonicalUrlAttribute()` accessor no longer feeds the sitemap. `getOrCreateSitemapEntry()` and `updateSitemapEntry()` throw `InvalidArgumentException` for a model with no URL.

`SitemapObserver` and `getOrCreateSitemapEntry()` no longer use the request URL, so saving a model from an admin screen can't put that screen's URL in the sitemap.

### 5. Update filter callbacks

**`ap.seo.sitemapEntries`**: a callback that checks for the all-types `''` value now only affects `sitemap-{page}.xml`, which the index no longer links. Move that logic to the per-type values.

**`ap.seo.llmsTxtEntries`**: once a provider is registered, callbacks receive provider URLs as arrays alongside `SitemapEntry` models. Handle both:

```php
addFilter( 'ap.seo.llmsTxtEntries', function ( array $entries ): array {
    return array_values( array_filter( $entries, function ( $entry ) {
        $url = is_array( $entry ) ? $entry['url'] : $entry->url;

        return ! str_contains( $url, '/preview/' );
    } ) );
} );
```

### 6. Update code that reads the tags

**`article:*` tags** moved out of `MetaTagsDTO::$additionalMeta`. They're rendered with `property` by `<x-seo:open-graph>` and `<x-seo:meta>`, and only when `og:type` is `article`. Read them from `OpenGraphDTO::$publishedTime` and `$modifiedTime`.

**`keywords`** is no longer emitted. To keep it, add it with a filter:

```php
addFilter( 'ap.seo.metaTags', function ( array $tags ): array {
    $tags['additionalMeta']['keywords'] = 'laravel, seo';

    return $tags;
} );
```

**`seoDescription()`** now counts the trailing `...` toward its limit.

**Titles** skip the site name when it would pass `seo.defaults.title_max_length`. Set the limit to 0 to always add it.

**`OpenGraphDTO::toArray()`** and **`TwitterCardDTO::toArray()`** include the new keys.

### 7. Update sitemap providers and type names

`SitemapProviderContract::getChangeFrequency()` and `getPriority()` may now return null. Existing providers keep working, because their narrower return types are still valid. Return null to leave the values out, and add `title` and `description` to each URL for llms.txt.

`SitemapGenerator::generateFromProvider()` takes optional `$page` and `$type` arguments and lists stored entries of the provider's type before its URLs.

`getSitemapPriority()` and `getSitemapChangefreq()` may return null. Update any code or override that expects a value.

A sitemap type must match `[A-Za-z0-9_.-]+`, or its child can't be reached. Empty `sitemap-{type}-{page}.xml` URLs now return a 404. `seo:generate-sitemap` always writes `sitemap-{type}-{page}.xml`, so update anything that reads the old `sitemap-{type}.xml` file for page 1.

### 8. Check your Livewire editor overrides

If you published the views, republish `seo-meta-editor.blade.php`. The priority slider is now a number field that can be left blank, and change frequency has a "Not specified" option. The `sitemapPriority` property is now nullable, and `sitemapChangefreq` defaults to an empty string.

### 9. Fix robots.txt on stock Nginx templates (optional)

Forge and stock Laravel Nginx templates serve the dynamic route with a 404 status, so crawlers never read your `Sitemap:` line. Check with `curl -I https://example.com/robots.txt`, then see [Robots.txt](Advanced-Robots#forge-and-stock-laravel-nginx-templates).

If you turn on `write_static`, delete Laravel's stock `public/robots.txt` first. If your robots rules are stored at runtime, call `RobotsService::syncStaticFile()` from a console command or queued job after saving.

### 10. Expect generated OG images to re-render

Generated file names now end in the image's size, such as `{hash}-1200x630.png`, so existing images render once more under the new name. Old files aren't removed. URLs you stored in `og_image` keep working, but report no dimensions until they're regenerated.

## Documentation Corrections

These docs were corrected in this release and don't reflect code changes:

- The package doesn't dispatch Laravel events. See [Events](Api-Events).
- Blade components use `<x-seo:meta>`, not `<x-seo-meta>`.
- Commands other than `seo:generate-sitemap`, `seo:submit-sitemap`, `seo:write-robots`, and `seo:install-frontend` never existed. See [Artisan Commands](Advanced-Artisan-Commands).

## Troubleshooting

**Post canonicals changed to `/blog/slug`.** Upgrade cms-framework to 2.17 or later.

**A model disappeared from new sitemap entries.** It has no public URL. See step 4.

**`priority` and `changefreq` are still in the sitemap.** Your published config still sets the defaults, or the sitemap is cached.

**`seo:write-robots` exits with a failure.** `public/robots.txt` exists and the package didn't write it. Delete it or pass `--force`.
