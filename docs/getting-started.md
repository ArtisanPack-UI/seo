---
title: Quick Start Guide
---

# Quick Start Guide

Get up and running with ArtisanPack UI SEO in just a few minutes. This guide covers the essential steps to add SEO capabilities to your Laravel application.

## Step 1: Install the Package

```bash
composer require artisanpack-ui/seo
```

## Step 2: Run Migrations

```bash
php artisan migrate
```

This creates the necessary database tables for storing SEO metadata, redirects, and sitemap entries.

## Step 3: Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=seo-config
```

This publishes the configuration file to `config/seo.php` where you can customize default settings.

## Step 4: Add the HasSeo Trait to Your Models

Add the `HasSeo` trait to any Eloquent model that needs SEO capabilities:

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

## Step 5: Render SEO Tags in Your Layout

Add the SEO meta component to your layout's `<head>` section:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Render all SEO tags --}}
    <x-seo-meta :model="$model ?? null" />

    {{-- Your other head content --}}
</head>
<body>
    {{ $slot }}
</body>
</html>
```

## Step 6: Update SEO Data

You can update SEO data programmatically:

```php
$post = Post::find(1);

$post->updateSeoMeta([
    'meta_title' => 'My Post Title | My Site',
    'meta_description' => 'A compelling description of my post.',
    'og_title' => 'My Post Title',
    'og_description' => 'Share this amazing content!',
    'twitter_card' => 'summary_large_image',
]);
```

Or use the Livewire SEO Meta Editor component in your admin panel:

```blade
<livewire:seo-meta-editor :model="$post" />
```

## What's Next?

Now that you have the basics set up, explore these topics:

- [Meta Tags](./usage/meta-tags.md) - Learn about meta tag customization and fallbacks
- [Social Media Optimization](./usage/social-media.md) - Configure Open Graph and Twitter Cards
- [Schema.org / JSON-LD](./usage/schema.md) - Add structured data to your pages
- [Blade Components](./components/blade-components.md) - All available view components
- [SEO Meta Editor](./components/seo-meta-editor.md) - Full-featured admin component
- [Configuration](./installation/configuration.md) - Customize default settings

## Quick Reference

### Helper Functions

```php
// Get the SEO service
$seo = seo();

// Get SEO meta for a model
$meta = seoMeta($post);

// Format a page title with separator
$title = seoTitle('My Page', ' | My Site');

// Truncate description to SEO-friendly length
$desc = seoDescription($longText, 160);

// Check if a feature is enabled
if (seoIsEnabled('sitemap')) {
    // ...
}
```

### Blade Components

```blade
{{-- All-in-one SEO output --}}
<x-seo-meta :model="$post" />

{{-- Individual components --}}
<x-seo-meta-tags :model="$post" />
<x-seo-open-graph :model="$post" />
<x-seo-twitter-card :model="$post" />
<x-seo-schema :model="$post" />
<x-seo-hreflang :model="$post" />
```

### Model Methods

```php
// Get or create SEO meta record
$meta = $post->getOrCreateSeoMeta();

// Update SEO data
$post->updateSeoMeta(['meta_title' => 'New Title']);

// Get effective values (with fallbacks)
$title = $post->getSeoTitle();
$description = $post->getSeoDescription();
$image = $post->getSeoImage();

// Check indexing settings
$post->shouldBeIndexed();    // true/false
$post->shouldBeFollowed();   // true/false
$post->shouldBeInSitemap();  // true/false
```
