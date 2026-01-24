---
title: ArtisanPack UI SEO Documentation Home
---

# ArtisanPack UI SEO Documentation

Welcome to the documentation for **ArtisanPack UI SEO**, a comprehensive Laravel SEO management package. This package provides everything you need to optimize your Laravel application for search engines, including meta tag management, social media optimization, structured data, URL redirects, XML sitemaps, and more.

## Table of Contents

- **Getting Started**
  - [Quick Start Guide](./getting-started.md)

- **Installation**
  - [Installation Overview](./installation.md)
  - [Requirements](./installation/requirements.md)
  - [Configuration](./installation/configuration.md)

- **Usage**
  - [Usage Overview](./usage.md)
  - [Meta Tags](./usage/meta-tags.md)
  - [Social Media (Open Graph & Twitter)](./usage/social-media.md)
  - [Schema.org / JSON-LD](./usage/schema.md)
  - [Hreflang (Multi-language)](./usage/hreflang.md)
  - [Model Integration](./usage/model-integration.md)

- **Components**
  - [Components Overview](./components.md)
  - [Blade Components](./components/blade-components.md)
  - [SEO Meta Editor](./components/seo-meta-editor.md)
  - [Redirect Manager](./components/redirect-manager.md)
  - [SEO Dashboard](./components/seo-dashboard.md)
  - [Analysis Panel](./components/analysis-panel.md)

- **API Reference**
  - [API Overview](./api.md)
  - [Models](./api/models.md)
  - [Services](./api/services.md)
  - [Helper Functions](./api/helpers.md)
  - [Events](./api/events.md)

- **Advanced**
  - [Advanced Overview](./advanced.md)
  - [URL Redirects](./advanced/redirects.md)
  - [XML Sitemaps](./advanced/sitemaps.md)
  - [Robots.txt](./advanced/robots.md)
  - [SEO Analysis](./advanced/analysis.md)
  - [Caching](./advanced/caching.md)
  - [Artisan Commands](./advanced/artisan-commands.md)

- **[FAQ](./faq.md)**
- **[Troubleshooting](./troubleshooting.md)**

## Features

- **Meta Tag Management** - Automatically generate or manually customize meta titles and descriptions for any Eloquent model
- **Social Media Optimization** - Full support for Open Graph (Facebook, LinkedIn), Twitter Cards, Pinterest, and Slack
- **Schema.org / JSON-LD** - 14 built-in schema types with customizable JSON-LD output
- **URL Redirects** - Manage 301/302/307/308 redirects with exact, regex, and wildcard matching
- **XML Sitemaps** - Generate standard, image, video, and news sitemaps with automatic sitemap index
- **Dynamic Robots.txt** - Configure robots.txt with bot-specific rules and automatic sitemap inclusion
- **Multi-language Support** - Hreflang tag management for international SEO
- **SEO Analysis** - Built-in content analysis with 8 analyzers for SEO scoring
- **Livewire Components** - Pre-built admin UI components for managing all SEO features
- **Blade Components** - Simple view components for rendering SEO tags in your templates
- **Caching** - Comprehensive caching system for optimal performance
- **Media Library Integration** - Seamless integration with ArtisanPack UI Media Library for social images

## Quick Example

```php
// Add SEO capabilities to any model
use ArtisanPackUI\Seo\Traits\HasSeo;

class Post extends Model
{
    use HasSeo;
}

// Update SEO data
$post->updateSeoMeta([
    'meta_title' => 'My Amazing Post',
    'meta_description' => 'Learn about amazing things in this post.',
    'og_title' => 'My Amazing Post on Social',
    'schema_type' => 'Article',
]);
```

```blade
{{-- Render all SEO tags in your layout --}}
<head>
    <x-seo-meta :model="$post" />
</head>
```

## Requirements

- PHP 8.2 or higher
- Laravel 10, 11, or 12
- Livewire 3.x (for admin components)

## Support

For bugs and feature requests, please open an issue on [GitLab](https://gitlab.com/jacob-martella-web-design/artisanpack-ui/seo/-/issues).

## License

ArtisanPack UI SEO is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
