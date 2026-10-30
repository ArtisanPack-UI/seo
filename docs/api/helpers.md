---
title: Helper Functions
---

# Helper Functions

ArtisanPack UI SEO provides global helper functions for common SEO tasks.

## SEO Service Helpers

### seo()

Get the `SeoService` instance.

```php
$seo = seo();

$metaTags = seo()->getMetaTags($post);
$openGraph = seo()->getOpenGraph($post);
```

### seoMeta()

Get the SEO meta record for a model, or `null` if the model has no record or no `seoMeta` relation. Works with the `HasSeo` trait and, since 1.7.1, with relations registered via `Model::resolveRelationUsing()`.

```php
$meta = seoMeta($post);

// Access properties
$title = $meta?->meta_title;
$description = $meta?->meta_description;
```

### seoConfig()

Get SEO configuration values.

```php
// Get single value
$siteName = seoConfig('site.name');
$maxLength = seoConfig('defaults.title_max_length');

// With default
$value = seoConfig('custom.key', 'default');
```

### seoIsEnabled()

Check if an SEO feature is enabled. This reads `seo.{feature}.enabled`.

```php
if (seoIsEnabled('sitemap')) {
    // Generate sitemap
}

if (seoIsEnabled('redirects')) {
    // Handle redirects
}

if (seoIsEnabled('analysis')) {
    // Run analysis
}
```

## Text Formatting Helpers

### seoTitle()

Format a page title with the site name suffix, using `seo.site.separator`.

```php
$title = seoTitle('My Page');
// Result: "My Page | My Site"

// Without suffix
$title = seoTitle('My Page', false);
// Result: "My Page"
```

The suffix is left off when the title already contains the site name, or when adding it would pass `seo.defaults.title_max_length`.

### seoDescription()

Shorten text to an SEO-friendly length. The limit includes the `...` added to shortened text, and text within the limit is returned unchanged.

```php
// Default 160 characters
$desc = seoDescription($longText);

// Custom length
$desc = seoDescription($longText, 150);
```

## Redirect Helpers

### seoRedirect()

Get the redirect service instance.

```php
$redirectService = seoRedirect();

// Use service methods
$redirect = seoRedirect()->findMatch('/old-path');
```

### seoFindRedirect()

Find a redirect for a given path.

```php
$redirect = seoFindRedirect('/old-page');

if ($redirect) {
    $destination = $redirect->to_path;
    $statusCode = $redirect->status_code;
}
```

### seoCreateRedirect()

Create a new redirect.

```php
$redirect = seoCreateRedirect('/old-page', '/new-page');

// With a status code and match type
$redirect = seoCreateRedirect(
    fromPath: '/blog/*',
    toPath: '/articles/$1',
    statusCode: 301,
    matchType: 'wildcard',
);
```

### seoDeleteRedirect()

Delete a redirect by model or ID.

```php
seoDeleteRedirect(123);
seoDeleteRedirect($redirect);
```

### seoRedirectStatistics()

Get redirect statistics from `RedirectService::getStatistics()`.

```php
$stats = seoRedirectStatistics();
```

## Schema Helpers

### apSeoAddSchema()

Add a schema to the current page's JSON-LD graph.

```php
apSeoAddSchema([
    '@type' => 'FAQPage',
    'mainEntity' => $questions,
]);
```

It also accepts an `AbstractSchema` instance.

## Usage Examples

### In Controllers

```php
class PostController extends Controller
{
    public function show(Post $post)
    {
        return view('posts.show', [
            'post' => $post,
            'seoTitle' => seoTitle($post->title),
            'seoDescription' => seoDescription(strip_tags($post->content)),
        ]);
    }
}
```

### In Views

```blade
@php
    $pageTitle = seoTitle($post->title ?? 'Home');
    $pageDesc = seoDescription($post->content ?? seoConfig('site.description'));
@endphp

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDesc }}">
```

## Next Steps

- [Services](Api-Services) - Full service documentation
- [Filter Hooks](Api-Filter-Hooks) - Filter reference
- [Configuration](Installation-Configuration) - Config options
