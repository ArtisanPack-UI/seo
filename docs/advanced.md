---
title: Advanced Overview
---

# Advanced Overview

This section covers advanced features of ArtisanPack UI SEO, including URL redirects, XML sitemaps, robots.txt management, SEO analysis, and caching strategies.

## Features

### URL Redirects

Manage URL redirects with support for exact matching, regex patterns, and wildcards. Track redirect hits and prevent redirect loops.

[Learn more about URL Redirects →](Advanced-Redirects)

### XML Sitemaps

Generate comprehensive XML sitemaps including standard sitemaps, image sitemaps, video sitemaps, and news sitemaps. Automatic sitemap index generation for large sites.

[Learn more about XML Sitemaps →](Advanced-Sitemaps)

### Dynamic Robots.txt

Generate robots.txt dynamically with global rules, bot-specific directives, and automatic sitemap inclusion.

[Learn more about Robots.txt →](Advanced-Robots)

### SEO Analysis

Analyze content for SEO quality with 8 built-in analyzers covering readability, keyword density, meta tags, headings, and more.

[Learn more about SEO Analysis →](Advanced-Analysis)

### Caching

Optimize performance with comprehensive caching for meta tags, analysis results, redirects, and sitemaps.

[Learn more about Caching →](Advanced-Caching)

### Artisan Commands

Command-line tools for sitemap generation, sitemap submission, and cache management.

[Learn more about Artisan Commands →](Advanced-Artisan-Commands)

## Quick Reference

### Enable/Disable Features

```php
// In config/seo.php

'redirects' => [
    'enabled' => true,
],

'sitemap' => [
    'enabled' => true,
],

'robots' => [
    'enabled' => true,
],

'analysis' => [
    'enabled' => true,
],
```

### Feature Check

```php
if (seoIsEnabled('redirects')) {
    // Redirect handling is active
}

if (seoIsEnabled('sitemap')) {
    // Sitemap generation is available
}
```

## Integration Points

### Middleware

The package provides middleware for redirect handling:

```php
// In bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \ArtisanPackUI\Seo\Http\Middleware\HandleRedirects::class,
    ]);
})
```

### Routes

The package can register routes for:

- `/sitemap.xml` - Main sitemap or sitemap index
- `/sitemap-{type}.xml` - Type-specific sitemaps
- `/robots.txt` - Dynamic robots.txt

```php
// In config/seo.php
'routes' => [
    'sitemap' => true,
    'robots' => true,
],
```

### Scheduled Tasks

Add sitemap generation to your scheduler:

```php
// In routes/console.php or app/Console/Kernel.php
Schedule::command('seo:generate-sitemap')->daily();
Schedule::command('seo:submit-sitemap')->weekly();
```

## Performance Considerations

### Caching Strategy

```php
'cache' => [
    'enabled' => true,
    'driver' => 'redis',  // Use Redis for production
    'ttl' => 3600,        // 1 hour default
],

'sitemap' => [
    'cache' => true,
    'cache_ttl' => 86400, // 24 hours for sitemaps
],

'redirects' => [
    'cache' => true,
    'cache_ttl' => 3600,  // 1 hour for redirects
],
```

### Queue Processing

```php
'analysis' => [
    'queue' => true,  // Run analysis in background
],
```

## Next Steps

- [URL Redirects](Advanced-Redirects) - Redirect management
- [XML Sitemaps](Advanced-Sitemaps) - Sitemap generation
- [Robots.txt](Advanced-Robots) - Robots.txt configuration
- [SEO Analysis](Advanced-Analysis) - Content analysis
- [Caching](Advanced-Caching) - Cache optimization
- [Artisan Commands](Advanced-Artisan-Commands) - CLI tools
