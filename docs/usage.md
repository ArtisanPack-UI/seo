---
title: Usage Overview
---

# Usage Overview

ArtisanPack UI SEO provides multiple ways to manage SEO for your Laravel application. This overview covers the main concepts and points you to detailed guides for each feature.

## Core Concepts

### Model-Based SEO

The package uses a polymorphic relationship to attach SEO metadata to any Eloquent model. Simply add the `HasSeo` trait to your models:

```php
use ArtisanPackUI\SEO\Traits\HasSeo;

class Post extends Model
{
    use HasSeo;
}
```

### Automatic Fallbacks

When SEO fields aren't explicitly set, the package automatically generates values from your model's properties:

1. **Title**: Uses `title`, `name`, or the app name
2. **Description**: Uses `excerpt`, `description`, or `content`, shortened to fit
3. **Image**: Uses `featured_image` or the configured default

### Output Components

Render SEO tags in your views using Blade components:

```blade
{{-- Meta, social, and hreflang tags --}}
<x-seo:meta :model="$post" />
<x-seo:schema :model="$post" />

{{-- Or individual components --}}
<x-seo:meta-tags :model="$post" />
<x-seo:open-graph :model="$post" />
<x-seo:twitter-card :model="$post" />
<x-seo:schema :model="$post" />
```

## Feature Categories

### Meta Tags

Basic meta tag management including titles, descriptions, robots directives, and canonical URLs.

[Learn more about Meta Tags →](Usage-Meta-Tags)

### Social Media

Open Graph tags for Facebook/LinkedIn, Twitter Cards, and support for Pinterest and Slack previews.

[Learn more about Social Media →](Usage-Social-Media)

### Schema.org / JSON-LD

Structured data markup for rich search results, including 15 built-in schema types.

[Learn more about Schema.org →](Usage-Schema)

### Multi-language (Hreflang)

Manage hreflang tags for international SEO and multi-language sites.

[Learn more about Hreflang →](Usage-Hreflang)

### Model Integration

Deep dive into the `HasSeo` trait and all available model methods.

[Learn more about Model Integration →](Usage-Model-Integration)

### AI Features

Five AI agents that generate meta titles, meta descriptions, JSON-LD schema, content quality scores, and hreflang gap analysis, with Livewire, React, and Vue trigger components.

[Learn more about AI Features →](Usage-Ai-Features)

## Quick Examples

### Setting SEO Data Programmatically

```php
$post = Post::find(1);

// Update multiple fields at once
$post->updateSeoMeta([
    'meta_title' => 'Custom Title',
    'meta_description' => 'Custom description for search engines.',
    'og_title' => 'Title for Facebook',
    'og_description' => 'Description for social sharing.',
    'twitter_card' => 'summary_large_image',
    'schema_type' => 'Article',
    'no_index' => false,
    'no_follow' => false,
]);
```

### Reading SEO Data

```php
// Get the SEO meta record
$meta = $post->getOrCreateSeoMeta();

// Get effective values (with fallbacks applied)
$title = $post->getSeoTitle();
$description = $post->getSeoDescription();
$image = $post->getSeoImage();

// Get all SEO data as array
$data = $post->getSeoData();
```

### Using the SEO Service

```php
// Get meta tags for a model
$tags = seo()->getMetaTags($post);

// Get Open Graph tags
$openGraph = seo()->getOpenGraph($post);

// Get Twitter Card tags
$twitterCard = seo()->getTwitterCard($post);
```

### Helper Functions

```php
// Format title with site name
$title = seoTitle('My Page');
// Result: "My Page | My Site"

// Truncate description to SEO length
$desc = seoDescription($longText);
// Result: at most 160 characters, including the "..."

// Get SEO meta for any model
$meta = seoMeta($post);
```

## Rendering in Views

### Basic Layout Integration

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO tags --}}
    <x-seo:meta :model="$model ?? null" />
    <x-seo:schema :model="$model ?? null" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}
</body>
</html>
```

### Controller Example

```php
class PostController extends Controller
{
    public function show(Post $post)
    {
        return view('posts.show', [
            'post' => $post,
            'model' => $post, // For SEO component
        ]);
    }
}
```

## Admin Interface

The package includes Livewire components for managing SEO in your admin panel:

```blade
{{-- Full SEO editor with all tabs --}}
<livewire:seo::seo-meta-editor :model="$post" />

{{-- SEO analysis panel --}}
<livewire:seo::seo-analysis-panel :model="$post" />

{{-- Preview components --}}
<livewire:seo::meta-preview :title="$title" :description="$description" :url="$url" />
<livewire:seo::social-preview :title="$title" :description="$description" :url="$url" />
```

## Next Steps

- [Meta Tags](Usage-Meta-Tags) - Detailed meta tag documentation
- [Social Media](Usage-Social-Media) - Open Graph and Twitter Cards
- [Schema.org](Usage-Schema) - Structured data markup
- [Hreflang](Usage-Hreflang) - Multi-language support
- [Model Integration](Usage-Model-Integration) - HasSeo trait reference
- [AI Features](Usage-Ai-Features) - AI-powered meta title, description, content analysis, schema, and hreflang agents
