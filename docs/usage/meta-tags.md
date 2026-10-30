---
title: Meta Tags
---

# Meta Tags

Meta tags are the foundation of on-page SEO. This guide covers how to manage meta titles, descriptions, robots directives, and canonical URLs.

## Setting Meta Tags

### Using the Model

```php
$post->updateSeoMeta([
    'meta_title' => 'My Amazing Blog Post | My Site',
    'meta_description' => 'Learn about amazing things in this comprehensive guide.',
]);
```

### Using the SEO Service

```php
$meta = seo()->updateSeoMeta($post, [
    'meta_title' => 'Custom Title',
    'meta_description' => 'Custom description.',
]);
```

## Automatic Title Generation

When no custom title is set, the package generates one from your model:

```php
// Priority order for the title tag:
// 1. meta_title field on SEO meta record
// 2. Model's 'meta_title' attribute
// 3. Model's 'title' attribute
// 4. Model's 'name' attribute
// 5. The app name
```

`$post->getSeoTitle()` returns the base title. The title tag adds the site name.

### Customizing the Title Suffix

```php
// In config/seo.php
'site' => [
    'name' => 'My Site',
    'separator' => ' | ',
],
```

```php
seoTitle('My Page');        // "My Page | My Site"
seoTitle('My Page', false); // "My Page"
```

The suffix is left off when the title already contains the site name, and when it would push the title past `seo.defaults.title_max_length`, which is 60 by default. Set the limit to 0 to always add it.

## Automatic Description Generation

```php
// Priority order for the description tag:
// 1. meta_description field on SEO meta record
// 2. Model's 'meta_description' attribute
// 3. Model's 'excerpt' attribute
// 4. Model's 'description' attribute
// 5. Site default from seo.site.description
```

### Description Length

A saved description is left at its full length, so check long ones with the [SEO analysis](Advanced-Analysis). Text taken from an excerpt or description is shortened to `seo.defaults.description_max_length`, including the `...`.

```php
// In config/seo.php
'defaults' => [
    'description_max_length' => 160,
],

// Using helper function
$desc = seoDescription($longText, 155);
// At most 155 characters, including the "..."
```

## Robots Directives

Control how search engines index and follow links on your pages.

### Setting Robots Meta

```php
$post->updateSeoMeta([
    'no_index' => true,   // Don't index this page
    'no_follow' => true,  // Don't follow links on this page
]);
```

### Checking Indexing Status

```php
if ($post->shouldBeIndexed()) {
    // Page will be indexed
}

if ($post->shouldBeFollowed()) {
    // Links will be followed
}
```

### Robots Output

The component generates the appropriate robots meta tag:

```html
<!-- Default (indexable) -->
<meta name="robots" content="index, follow">

<!-- Noindex -->
<meta name="robots" content="noindex, follow">

<!-- Nofollow -->
<meta name="robots" content="index, nofollow">

<!-- Both -->
<meta name="robots" content="noindex, nofollow">
```

## Canonical URLs

Canonical URLs prevent duplicate content issues by specifying the preferred URL for a page.

### Automatic Canonicals

The canonical URL uses the first of these that has a value:

1. A saved `canonical_url`
2. `getPublicUrl()` from the `HasPublicUrl` contract
3. `getUrl()` on the model
4. A `permalink` attribute
5. The current request URL

URLs are never guessed from a model's slug. See [Public URLs](Usage-Model-Integration#public-urls).

### Custom Canonical URLs

```php
$post->updateSeoMeta([
    'canonical_url' => 'https://example.com/preferred-url',
]);
```

## Focus Keywords

Track focus keywords for SEO analysis:

```php
$post->updateSeoMeta([
    'focus_keyword' => 'laravel seo',
    'secondary_keywords' => ['seo package', 'meta tags', 'structured data'],
]);
```

The focus keyword is used by the SEO analysis feature to check:
- Keyword presence in title
- Keyword presence in description
- Keyword presence in content
- Keyword density

Focus keywords are only used for analysis. The package doesn't emit a `keywords` meta tag, because search engines ignore it and it would publish your targeting. To add one yourself, use the `ap.seo.metaTags` filter:

```php
addFilter( 'ap.seo.metaTags', function ( array $tags ): array {
    $tags['additionalMeta']['keywords'] = 'laravel, seo';

    return $tags;
} );
```

## Rendering Meta Tags

### Using the Blade Component

```blade
{{-- Renders title, description, robots, and canonical --}}
<x-seo:meta-tags :model="$post" />
```

### Generated Output

```html
<title>My Blog Post | My Site</title>
<meta name="description" content="Learn about amazing things in this comprehensive guide.">
<link rel="canonical" href="https://example.com/posts/my-blog-post">
<meta name="robots" content="index, follow">
```

### Without a Model

For pages without an associated model:

```blade
<x-seo:meta-tags
    :title="'About Us | My Site'"
    :description="'Learn more about our company.'"
/>
```

## Best Practices

### Title Tags

- Keep titles under 60 characters
- Include your primary keyword near the beginning
- Make each title unique across your site
- Include your brand name (usually at the end)

### Meta Descriptions

- Keep descriptions at 160 characters or less
- Include a call-to-action when appropriate
- Include your primary keyword naturally
- Make each description unique and compelling

### Canonical URLs

- Always use absolute URLs
- Be consistent with trailing slashes
- Include canonical on paginated content pointing to page 1
- Use canonical to consolidate similar content

## Meta Tag Validation

The SEO analysis feature checks meta tag lengths with its `meta_length` analyzer:

```php
use ArtisanPackUI\SEO\Services\AnalysisService;

$result = app(AnalysisService::class)->analyze($post);
```

## Next Steps

- [Social Media](Social-Media) - Open Graph and Twitter Cards
- [Schema.org](Schema) - Structured data markup
- [SEO Analysis](Advanced-Analysis) - Content analysis features
- [Configuration](Installation-Configuration) - Default settings
