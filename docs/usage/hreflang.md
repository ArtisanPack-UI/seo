---
title: Hreflang (Multi-language)
---

# Hreflang (Multi-language)

Hreflang tags help search engines understand the language and regional targeting of your pages. This guide covers implementing hreflang for international SEO.

## Overview

Hreflang attributes tell search engines which language versions of a page exist and help them serve the correct version to users based on their language and location.

## Configuration

### Enable Hreflang Support

```php
// In config/seo.php
'hreflang' => [
    'enabled' => true,
    'locales' => ['en', 'en-US', 'en-GB', 'fr', 'de', 'es', 'ja', 'zh-CN'],
    'x_default' => true,
],
```

### Supported Locale Formats

| Format | Example | Description |
|--------|---------|-------------|
| Language only | `en`, `fr`, `de` | General language targeting |
| Language-Region | `en-US`, `en-GB` | Specific regional targeting |
| Language_Region | `zh_CN`, `pt_BR` | Alternative format |

## Setting Hreflang URLs

### Using the Model

```php
$post->updateSeoMeta([
    'hreflang' => [
        'en' => 'https://example.com/post',
        'en-US' => 'https://example.com/en-us/post',
        'en-GB' => 'https://example.co.uk/post',
        'fr' => 'https://example.fr/article',
        'de' => 'https://example.de/beitrag',
        'x-default' => 'https://example.com/post',
    ],
]);
```

### Using the Hreflang Service

```php
use ArtisanPackUI\Seo\Services\HreflangService;

$hreflangService = app(HreflangService::class);

// Set hreflang for a model
$hreflangService->setHreflang($post, [
    'en' => 'https://example.com/post',
    'fr' => 'https://example.fr/article',
]);

// Add a single language
$hreflangService->addLanguage($post, 'de', 'https://example.de/beitrag');

// Remove a language
$hreflangService->removeLanguage($post, 'de');

// Get all hreflang URLs
$urls = $hreflangService->getHreflang($post);
```

## x-default Tag

The `x-default` tag specifies the default page for users whose language/region doesn't match any specified hreflang.

### Automatic x-default

When enabled in config, the package automatically adds `x-default`:

```php
'hreflang' => [
    'x_default' => true,  // Auto-add x-default
],
```

The `x-default` URL defaults to the `en` or first specified URL.

### Manual x-default

```php
$post->updateSeoMeta([
    'hreflang' => [
        'en' => 'https://example.com/post',
        'fr' => 'https://example.fr/article',
        'x-default' => 'https://example.com/post',
    ],
]);
```

## Rendering Hreflang Tags

### Using Blade Component

```blade
<x-seo-hreflang :model="$post" />
```

### Generated Output

```html
<link rel="alternate" hreflang="en" href="https://example.com/post">
<link rel="alternate" hreflang="en-US" href="https://example.com/en-us/post">
<link rel="alternate" hreflang="fr" href="https://example.fr/article">
<link rel="alternate" hreflang="de" href="https://example.de/beitrag">
<link rel="alternate" hreflang="x-default" href="https://example.com/post">
```

## Livewire Hreflang Editor

The package includes a Livewire component for managing hreflang URLs:

```blade
<livewire:hreflang-editor :model="$post" />
```

This provides:
- Add/remove language variants
- URL validation
- Locale selection from configured locales
- x-default management

## Dynamic Hreflang Generation

For multi-language sites with consistent URL patterns:

```php
// In your model
public function getHreflangUrls(): array
{
    $locales = ['en', 'fr', 'de', 'es'];
    $urls = [];

    foreach ($locales as $locale) {
        $urls[$locale] = route('posts.show', [
            'locale' => $locale,
            'post' => $this->slug,
        ]);
    }

    $urls['x-default'] = $urls['en'];

    return $urls;
}

// Update SEO meta
$post->updateSeoMeta([
    'hreflang' => $post->getHreflangUrls(),
]);
```

## Regional vs Language Targeting

### Language-Only Targeting

Use when content is the same for all regions speaking that language:

```php
'hreflang' => [
    'en' => 'https://example.com/post',
    'fr' => 'https://example.com/fr/post',
    'de' => 'https://example.com/de/post',
],
```

### Regional Targeting

Use when content differs by region (pricing, legal, cultural):

```php
'hreflang' => [
    'en-US' => 'https://example.com/post',
    'en-GB' => 'https://example.co.uk/post',
    'en-AU' => 'https://example.com.au/post',
],
```

### Combined Approach

```php
'hreflang' => [
    'en' => 'https://example.com/post',        // Generic English
    'en-US' => 'https://example.com/us/post',  // US-specific
    'en-GB' => 'https://example.co.uk/post',   // UK-specific
    'fr' => 'https://example.fr/article',      // Generic French
    'fr-CA' => 'https://example.ca/fr/post',   // Canadian French
],
```

## Common Patterns

### Subdomain Structure

```php
'hreflang' => [
    'en' => 'https://en.example.com/post',
    'fr' => 'https://fr.example.com/post',
    'de' => 'https://de.example.com/post',
],
```

### Subdirectory Structure

```php
'hreflang' => [
    'en' => 'https://example.com/en/post',
    'fr' => 'https://example.com/fr/post',
    'de' => 'https://example.com/de/post',
],
```

### Country-Code TLDs

```php
'hreflang' => [
    'en-US' => 'https://example.com/post',
    'en-GB' => 'https://example.co.uk/post',
    'fr' => 'https://example.fr/post',
    'de' => 'https://example.de/post',
],
```

## Validation

### Required Rules

1. **Self-referencing**: Each page must include itself in its hreflang set
2. **Reciprocal**: All pages in the set must link to each other
3. **Absolute URLs**: Always use full URLs, not relative paths
4. **Valid codes**: Use ISO 639-1 language codes and ISO 3166-1 alpha-2 country codes

### Validation Helper

```php
use ArtisanPackUI\Seo\Services\HreflangService;

$hreflangService = app(HreflangService::class);

// Validate hreflang configuration
$errors = $hreflangService->validate($post);

if (count($errors) > 0) {
    // Handle validation errors
}
```

## Best Practices

1. **Be consistent** - Use the same hreflang format across your entire site
2. **Include all versions** - List all language versions, including the current page
3. **Use x-default** - Always specify a default for unmatched users
4. **Validate URLs** - Ensure all hreflang URLs are accessible (200 status)
5. **Update together** - When adding a new language, update all existing pages
6. **Match content** - Hreflang pages should have equivalent content, not just translations of the homepage

## Troubleshooting

### Common Issues

**Pages not indexed in correct language**:
- Verify reciprocal linking (all pages link to each other)
- Check that URLs return 200 status
- Ensure self-referencing is in place

**Duplicate content warnings**:
- Add hreflang to all language versions
- Verify canonical URLs are set correctly

**x-default not working**:
- Ensure x-default URL is also included as a language-specific version
- Check that x-default URL is accessible

## Next Steps

- [Meta Tags](./meta-tags.md) - Basic meta tag management
- [Model Integration](./model-integration.md) - HasSeo trait reference
- [Configuration](../installation/configuration.md) - Hreflang settings
- [Troubleshooting](../troubleshooting.md) - Common issues and solutions
