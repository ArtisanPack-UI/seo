---
title: Components Overview
---

# Components Overview

ArtisanPack UI SEO provides two types of components: Blade components for rendering SEO tags in your views, and Livewire components for managing SEO in your admin interface.

## Blade Components

Blade components render SEO tags in your HTML `<head>` section:

| Component | Purpose |
|-----------|---------|
| `<x-seo-meta>` | All-in-one SEO output (meta, OG, Twitter, schema) |
| `<x-seo-meta-tags>` | Basic meta tags only |
| `<x-seo-open-graph>` | Open Graph tags |
| `<x-seo-twitter-card>` | Twitter Card tags |
| `<x-seo-schema>` | Schema.org JSON-LD |
| `<x-seo-hreflang>` | Hreflang link tags |

[Learn more about Blade Components →](Components-Blade-Components)

## Livewire Components

Livewire components provide interactive admin interfaces:

| Component | Purpose |
|-----------|---------|
| `<livewire:seo-meta-editor>` | Full SEO editing interface |
| `<livewire:redirect-manager>` | URL redirect management |
| `<livewire:seo-dashboard>` | SEO overview dashboard |
| `<livewire:seo-analysis-panel>` | Content analysis results |
| `<livewire:hreflang-editor>` | Multi-language URL editor |
| `<livewire:meta-preview>` | Search result preview |
| `<livewire:social-preview>` | Social share preview |

## Quick Usage

### In Your Layout

```blade
<!DOCTYPE html>
<html>
<head>
    {{-- Render all SEO tags at once --}}
    <x-seo-meta :model="$model ?? null" />
</head>
<body>
    {{ $slot }}
</body>
</html>
```

### In Your Admin Panel

```blade
<div class="p-6">
    <h1>Edit Post SEO</h1>

    {{-- Full SEO editor --}}
    <livewire:seo-meta-editor :model="$post" />
</div>
```

## Component Categories

### Output Components (Blade)

These components are designed for rendering SEO tags in your public-facing pages:

- Output valid HTML meta tags
- Support model binding with fallbacks
- Can be used without a model for static pages
- Lightweight with no JavaScript dependencies

[View Blade Components Documentation →](Components-Blade-Components)

### Admin Components (Livewire)

These components provide full admin functionality:

- Interactive editing with real-time updates
- Form validation and error handling
- Preview capabilities
- Requires Livewire 3.x

[View SEO Meta Editor Documentation →](Components-Seo-Meta-Editor)

## Installation Requirements

### Blade Components

Blade components work out of the box after installing the package. No additional setup required.

### Livewire Components

Livewire components require:

1. Livewire 3.x installed in your project
2. Livewire scripts/styles included in your layout

```blade
<html>
<head>
    @livewireStyles
</head>
<body>
    {{ $slot }}

    @livewireScripts
</body>
</html>
```

## React & Vue Components

> Added in v1.1.0

The package also provides publishable React and Vue components for building custom SEO admin interfaces in JavaScript frontends. These are published to your application using the `seo:install-frontend` Artisan command.

```bash
# Install React components
php artisan seo:install-frontend --stack=react

# Install Vue components
php artisan seo:install-frontend --stack=vue
```

Components are published to `resources/js/vendor/seo/{react|vue}/`, with shared TypeScript type definitions in `resources/js/types/seo/`.

[Learn more about Frontend Scaffolding →](Advanced-Frontend-Scaffolding)

## Customizing Components

### Publishing Views

To customize component views:

```bash
php artisan vendor:publish --tag=seo-views
```

Views are published to `resources/views/vendor/seo/`.

### Extending Components

You can extend Livewire components:

```php
namespace App\Livewire;

use ArtisanPackUI\Seo\Livewire\SeoMetaEditor as BaseSeoMetaEditor;

class CustomSeoMetaEditor extends BaseSeoMetaEditor
{
    // Add custom functionality
}
```

## Next Steps

- [Blade Components](Components-Blade-Components) - Complete Blade component reference
- [SEO Meta Editor](Components-Seo-Meta-Editor) - Main admin editing component
- [Redirect Manager](Components-Redirect-Manager) - URL redirect management
- [SEO Dashboard](Components-Seo-Dashboard) - Dashboard component
- [Analysis Panel](Components-Analysis-Panel) - SEO analysis results
