---
title: SEO Meta Editor
---

# SEO Meta Editor

The SEO Meta Editor is a comprehensive Livewire component for managing all SEO settings in your admin interface.

## Basic Usage

```blade
<livewire:seo::seo-meta-editor :model="$post" />
```

## Features

The editor provides a tabbed interface with the following sections:

1. **Basic** - Meta title, description, focus keywords
2. **Social** - Open Graph, Twitter Card, Pinterest, Slack
3. **Schema** - Schema.org type and custom data
4. **Advanced** - Robots, indexing, sitemap, and hreflang settings

## Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `model` | Model | Yes | Eloquent model with HasSeo trait |
| `activeTab` | string | No | Initial tab: `basic`, `social`, `schema`, or `advanced` |

## Examples

### Standard Usage

```blade
<livewire:seo::seo-meta-editor :model="$post" />
```

### Opening a Tab

```blade
<livewire:seo::seo-meta-editor :model="$post" active-tab="social" />
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
- Type selection. **Automatic** infers the type from the model
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
  - Priority number field (0-1). Leave it blank to use the site default, if there is one
  - Change frequency selection. **Not specified** uses the site default, if there is one
- **Hreflang** - Language variants for the page

## Events

The component dispatches these events:

| Event | Payload | Description |
|-------|---------|-------------|
| `seo-meta-saved` | `id` | After saving SEO data |
| `seo-analysis-complete` | `results` | After running analysis |

```blade
<div x-data x-on:seo-meta-saved.window="alert('SEO settings saved!')">
    <livewire:seo::seo-meta-editor :model="$post" />
</div>
```

## Form Validation

The editor validates field lengths, the sitemap priority range (0-1), the change frequency value, and URL formats. Long titles and descriptions show a counter rather than an error.

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

use ArtisanPackUI\SEO\Livewire\SeoMetaEditor as BaseSeoMetaEditor;
use Illuminate\Database\Eloquent\Model;

class CustomSeoMetaEditor extends BaseSeoMetaEditor
{
    public function mount(Model $model, ?string $activeTab = null): void
    {
        parent::mount($model, $activeTab);

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
        <livewire:seo::seo-meta-editor :model="$post" />
    </div>

    <button type="submit">Save Post</button>
</form>
```

### In a Slide-over Panel

```blade
<x-artisanpack-drawer wire:model="showSeoPanel" title="SEO Settings">
    <livewire:seo::seo-meta-editor :model="$post" />
</x-artisanpack-drawer>
```

## Styling

The component uses Tailwind CSS classes and daisyUI components. To customize styling, publish and modify the views or override CSS classes.

## Next Steps

- [Redirect Manager](Components-Redirect-Manager) - Manage URL redirects
- [Analysis Panel](Components-Analysis-Panel) - SEO analysis component
- [SEO Dashboard](Components-Seo-Dashboard) - Overview dashboard
- [Meta Tags](Usage-Meta-Tags) - Meta tag reference
