---
title: SEO Meta Editor
---

# SEO Meta Editor

The SEO Meta Editor is a comprehensive Livewire component for managing all SEO settings in your admin interface.

## Basic Usage

```blade
<livewire:seo-meta-editor :model="$post" />
```

## Features

The editor provides a tabbed interface with the following sections:

1. **Basic** - Meta title, description, focus keywords
2. **Social** - Open Graph, Twitter Card, Pinterest, Slack
3. **Schema** - Schema.org type and custom data
4. **Advanced** - Robots, indexing, sitemap settings
5. **Hreflang** - Multi-language URL management

## Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `model` | Model | Yes | Eloquent model with HasSeo trait |
| `showPreview` | bool | No | Show/hide preview panels |
| `autoSave` | bool | No | Enable auto-save on changes |

## Examples

### Standard Usage

```blade
<div class="card">
    <div class="card-header">
        <h3>SEO Settings</h3>
    </div>
    <div class="card-body">
        <livewire:seo-meta-editor :model="$post" />
    </div>
</div>
```

### With Preview Panels

```blade
<livewire:seo-meta-editor
    :model="$post"
    :show-preview="true"
/>
```

### Auto-Save Mode

```blade
<livewire:seo-meta-editor
    :model="$post"
    :auto-save="true"
/>
```

## Tab Sections

### Basic Tab

The Basic tab includes:

- **Meta Title** - Page title with character counter
- **Meta Description** - Description with character counter and preview
- **Focus Keyword** - Primary keyword for analysis
- **Secondary Keywords** - Additional keywords for tracking

```php
// Fields managed
[
    'meta_title',
    'meta_description',
    'focus_keyword',
    'secondary_keywords',
]
```

### Social Tab

The Social tab is divided into sub-sections:

**Open Graph:**
- Title
- Description
- Image (with media library picker if available)
- Type selection
- Locale
- Site name

**Twitter Card:**
- Card type selection
- Title
- Description
- Image
- Site handle
- Creator handle

**Pinterest:**
- Description
- Image

**Slack:**
- Title
- Description

### Schema Tab

The Schema tab includes:

- **Schema Type** dropdown (Article, Product, Event, etc.)
- **Custom Schema Data** JSON editor
- **Preview** of generated JSON-LD

### Advanced Tab

The Advanced tab includes:

- **Noindex** toggle - Prevent search engine indexing
- **Nofollow** toggle - Prevent link following
- **Canonical URL** - Custom canonical URL
- **Sitemap Settings:**
  - Exclude from sitemap toggle
  - Priority slider (0.0-1.0)
  - Change frequency selection

### Hreflang Tab

The Hreflang tab includes:

- Add/remove language variants
- URL input for each language
- x-default setting
- Locale selection from configured options

## Events

The component emits these events:

| Event | Payload | Description |
|-------|---------|-------------|
| `seo-saved` | `['model' => Model]` | After saving SEO data |
| `seo-error` | `['errors' => array]` | When validation fails |

### Listening to Events

```blade
<livewire:seo-meta-editor
    :model="$post"
    @seo-saved="$refresh"
/>
```

```javascript
// In Alpine.js
<div x-data @seo-saved.window="alert('SEO settings saved!')">
    <livewire:seo-meta-editor :model="$post" />
</div>
```

## Form Validation

The editor validates:

- Title length (max 60 characters recommended)
- Description length (max 160 characters recommended)
- URL formats for canonical and hreflang
- Schema data JSON syntax
- Required fields based on schema type

## Customization

### Publishing Views

```bash
php artisan vendor:publish --tag=seo-views
```

Views are published to:
```
resources/views/vendor/seo/livewire/seo-meta-editor.blade.php
```

### Extending the Component

```php
namespace App\Livewire;

use ArtisanPackUI\Seo\Livewire\SeoMetaEditor as BaseSeoMetaEditor;

class CustomSeoMetaEditor extends BaseSeoMetaEditor
{
    public function mount($model): void
    {
        parent::mount($model);

        // Custom initialization
    }

    public function save(): void
    {
        parent::save();

        // Additional save logic
    }
}
```

Register your custom component:

```php
// In AppServiceProvider
use Livewire\Livewire;

public function boot(): void
{
    Livewire::component('custom-seo-meta-editor', CustomSeoMetaEditor::class);
}
```

## Integration Examples

### In a Form

```blade
<form wire:submit="save">
    {{-- Other form fields --}}

    <div class="mt-6">
        <h2>SEO Settings</h2>
        <livewire:seo-meta-editor :model="$post" />
    </div>

    <button type="submit">Save Post</button>
</form>
```

### In a Slide-over Panel

```blade
<x-artisanpack-drawer wire:model="showSeoPanel" title="SEO Settings">
    <livewire:seo-meta-editor :model="$post" />
</x-artisanpack-drawer>
```

### With Save Button Outside

```blade
<div x-data="{ seoEditor: null }">
    <livewire:seo-meta-editor
        :model="$post"
        x-ref="seoEditor"
    />

    <button wire:click="$dispatch('save-seo')">
        Save SEO Settings
    </button>
</div>
```

## Styling

The component uses Tailwind CSS classes and daisyUI components. To customize styling, publish and modify the views or override CSS classes.

## Next Steps

- [Redirect Manager](./redirect-manager.md) - Manage URL redirects
- [Analysis Panel](./analysis-panel.md) - SEO analysis component
- [SEO Dashboard](./seo-dashboard.md) - Overview dashboard
- [Meta Tags](../usage/meta-tags.md) - Meta tag reference
