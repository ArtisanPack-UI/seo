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
use ArtisanPackUI\Seo\Facades\Seo;

$meta = Seo::updateMeta($post, [
    'meta_title' => 'Custom Title',
    'meta_description' => 'Custom description.',
]);
```

## Automatic Title Generation

When no custom title is set, the package generates one from your model:

```php
// Priority order for title fallback:
// 1. meta_title field on SEO meta record
// 2. Model's 'title' attribute
// 3. Model's 'name' attribute
// 4. Model class name

$post->title = 'My Blog Post';
$title = $post->getSeoTitle();
// Result: "My Blog Post | My Site"
```

### Customizing the Title Suffix

```php
// In config/seo.php
'site' => [
    'name' => 'My Site',
    'separator' => ' | ',
],

// Or per-request
$title = seoTitle('My Page', ' - ');
// Result: "My Page - My Site"
```

## Automatic Description Generation

When no custom description is set:

```php
// Priority order for description fallback:
// 1. meta_description field on SEO meta record
// 2. Model's 'description' attribute
// 3. Model's 'excerpt' attribute
// 4. Truncated 'content' attribute
// 5. Site default description from config

$post->content = 'Long article content here...';
$desc = $post->getSeoDescription();
// Result: First 160 characters of content
```

### Description Length

```php
// In config/seo.php
'defaults' => [
    'description_max_length' => 160,
],

// Using helper function
$desc = seoDescription($longText, 155);
// Truncates to 155 characters
```

## Robots Directives

Control how search engines index and follow links on your pages.

### Setting Robots Meta

```php
$post->updateSeoMeta([
    'noindex' => true,   // Don't index this page
    'nofollow' => true,  // Don't follow links on this page
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

By default, the package generates canonical URLs based on the model's URL:

```php
// In config/seo.php
'defaults' => [
    'canonical' => true,
],
```

### Custom Canonical URLs

```php
$post->updateSeoMeta([
    'canonical_url' => 'https://example.com/preferred-url',
]);
```

### Disabling Canonical

```php
$post->updateSeoMeta([
    'canonical_url' => null,
]);

// Or globally in config
'defaults' => [
    'canonical' => false,
],
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

## Rendering Meta Tags

### Using the Blade Component

```blade
{{-- Renders title, description, robots, and canonical --}}
<x-seo-meta-tags :model="$post" />
```

### Generated Output

```html
<title>My Blog Post | My Site</title>
<meta name="description" content="Learn about amazing things in this comprehensive guide.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://example.com/posts/my-blog-post">
```

### Without a Model

For pages without an associated model:

```blade
<x-seo-meta-tags
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

- Keep descriptions between 150-160 characters
- Include a call-to-action when appropriate
- Include your primary keyword naturally
- Make each description unique and compelling

### Canonical URLs

- Always use absolute URLs
- Be consistent with trailing slashes
- Include canonical on paginated content pointing to page 1
- Use canonical to consolidate similar content

## Meta Tag Validation

The SEO analysis feature validates meta tags:

```php
// Get analysis results
$analysis = $post->getSeoAnalysis();

// Check meta length issues
$metaLength = $analysis['meta_length'];
// Returns: pass/fail status and recommendations
```

## Next Steps

- [Social Media](./social-media.md) - Open Graph and Twitter Cards
- [Schema.org](./schema.md) - Structured data markup
- [SEO Analysis](../advanced/analysis.md) - Content analysis features
- [Configuration](../installation/configuration.md) - Default settings
