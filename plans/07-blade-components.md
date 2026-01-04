# Blade Components

**Purpose:** Define Blade components for outputting SEO tags in views
**Last Updated:** January 3, 2026

---

## Overview

The SEO package provides Blade components for rendering SEO tags in the `<head>` section of your layouts. All components use the `x-seo:` prefix.

### Available Components

| Component | Purpose |
|-----------|---------|
| `<x-seo:meta />` | All-in-one: meta tags, OG, Twitter, schema |
| `<x-seo:meta-tags />` | Basic meta tags only |
| `<x-seo:open-graph />` | Open Graph tags |
| `<x-seo:twitter-card />` | Twitter Card tags |
| `<x-seo:schema />` | Schema.org JSON-LD |
| `<x-seo:hreflang />` | Hreflang alternate links |
| `<x-seo:social-meta />` | All social meta (OG, Twitter, Pinterest, Slack) |

---

## Primary Component: x-seo:meta

The all-in-one component for rendering complete SEO output.

### Component Class

```php
<?php

namespace ArtisanPackUI\SEO\View\Components;

use ArtisanPackUI\SEO\Services\SeoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class Meta extends Component
{
    public array $meta;
    public array $openGraph;
    public array $twitterCard;
    public array $schema;
    public array $hreflang;

    public function __construct(
        public ?Model $model = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public ?string $canonical = null,
        public bool $includeSchema = true,
        public bool $includeOpenGraph = true,
        public bool $includeTwitterCard = true,
        public bool $includeHreflang = true,
    ) {
        $seoService = app(SeoService::class);

        if ($this->model) {
            // Get all SEO data from model
            $seoData = $seoService->getAll($this->model);

            $this->meta = $this->overrideMeta($seoData['meta']->toArray());
            $this->openGraph = $this->overrideOpenGraph($seoData['openGraph']->toArray());
            $this->twitterCard = $seoData['twitterCard'];
            $this->schema = $seoData['schema'];
            $this->hreflang = $seoData['hreflang'];
        } else {
            // Use provided values or defaults
            $this->meta = $this->buildDefaultMeta();
            $this->openGraph = $this->buildDefaultOpenGraph();
            $this->twitterCard = $this->buildDefaultTwitterCard();
            $this->schema = [];
            $this->hreflang = [];
        }
    }

    protected function overrideMeta(array $meta): array
    {
        if ($this->title) {
            $meta['title'] = $this->title;
        }
        if ($this->description) {
            $meta['description'] = $this->description;
        }
        if ($this->canonical) {
            $meta['canonical'] = $this->canonical;
        }

        return $meta;
    }

    protected function overrideOpenGraph(array $og): array
    {
        if ($this->title) {
            $og['title'] = $this->title;
        }
        if ($this->description) {
            $og['description'] = $this->description;
        }
        if ($this->image) {
            $og['image'] = $this->image;
        }

        return $og;
    }

    protected function buildDefaultMeta(): array
    {
        return [
            'title' => $this->title ?? config('seo.defaults.title_suffix', config('app.name')),
            'description' => $this->description ?? config('seo.defaults.meta_description'),
            'canonical' => $this->canonical ?? url()->current(),
            'robots' => 'index, follow',
        ];
    }

    protected function buildDefaultOpenGraph(): array
    {
        return [
            'title' => $this->title ?? config('app.name'),
            'description' => $this->description,
            'image' => $this->image ?? config('seo.defaults.og_image'),
            'url' => url()->current(),
            'type' => 'website',
            'site_name' => config('app.name'),
            'locale' => config('seo.defaults.og_locale', 'en_US'),
        ];
    }

    protected function buildDefaultTwitterCard(): array
    {
        return [
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $this->title ?? config('app.name'),
            'twitter:description' => $this->description,
            'twitter:image' => $this->image ?? config('seo.defaults.og_image'),
            'twitter:site' => config('seo.social.twitter.site'),
        ];
    }

    public function render()
    {
        return view('seo::components.meta');
    }
}
```

### View Template

```blade
{{-- resources/views/components/meta.blade.php --}}

{{-- Basic Meta Tags --}}
<title>{{ $meta['title'] }}</title>

@if($meta['description'])
    <meta name="description" content="{{ $meta['description'] }}">
@endif

<link rel="canonical" href="{{ $meta['canonical'] }}">

@if($meta['robots'] ?? null)
    <meta name="robots" content="{{ $meta['robots'] }}">
@endif

{{-- Additional Meta --}}
@foreach($meta['additional'] ?? [] as $name => $content)
    <meta name="{{ $name }}" content="{{ $content }}">
@endforeach

{{-- Open Graph --}}
@if($includeOpenGraph)
    <meta property="og:title" content="{{ $openGraph['title'] }}">
    @if($openGraph['description'])
        <meta property="og:description" content="{{ $openGraph['description'] }}">
    @endif
    @if($openGraph['image'])
        <meta property="og:image" content="{{ $openGraph['image'] }}">
    @endif
    <meta property="og:url" content="{{ $openGraph['url'] }}">
    <meta property="og:type" content="{{ $openGraph['type'] }}">
    <meta property="og:site_name" content="{{ $openGraph['site_name'] }}">
    <meta property="og:locale" content="{{ $openGraph['locale'] }}">
@endif

{{-- Twitter Card --}}
@if($includeTwitterCard)
    @foreach($twitterCard as $name => $content)
        @if($content)
            <meta name="{{ $name }}" content="{{ $content }}">
        @endif
    @endforeach
@endif

{{-- Hreflang --}}
@if($includeHreflang && !empty($hreflang))
    @foreach($hreflang as $tag)
        <link rel="alternate" hreflang="{{ $tag['hreflang'] }}" href="{{ $tag['href'] }}">
    @endforeach
@endif

{{-- Schema.org JSON-LD --}}
@if($includeSchema && !empty($schema))
    @foreach($schema as $schemaItem)
        <script type="application/ld+json">
            {!! json_encode($schemaItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endforeach
@endif
```

---

## Individual Components

### x-seo:meta-tags

Basic meta tags only.

```php
<?php

namespace ArtisanPackUI\SEO\View\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class MetaTags extends Component
{
    public string $title;
    public ?string $description;
    public string $canonical;
    public string $robots;

    public function __construct(
        ?Model $model = null,
        ?string $title = null,
        ?string $description = null,
        ?string $canonical = null,
        ?string $robots = null,
    ) {
        if ($model && method_exists($model, 'getSeoData')) {
            $seoData = $model->getSeoData();
            $meta = $seoData['meta'];

            $this->title = $title ?? $meta['title'];
            $this->description = $description ?? $meta['description'];
            $this->canonical = $canonical ?? $meta['canonical'];
            $this->robots = $robots ?? $meta['robots'];
        } else {
            $this->title = $title ?? config('seo.defaults.title_suffix', config('app.name'));
            $this->description = $description ?? config('seo.defaults.meta_description');
            $this->canonical = $canonical ?? url()->current();
            $this->robots = $robots ?? 'index, follow';
        }
    }

    public function render()
    {
        return view('seo::components.meta-tags');
    }
}
```

```blade
{{-- resources/views/components/meta-tags.blade.php --}}
<title>{{ $title }}</title>

@if($description)
    <meta name="description" content="{{ $description }}">
@endif

<link rel="canonical" href="{{ $canonical }}">
<meta name="robots" content="{{ $robots }}">
```

### x-seo:open-graph

Open Graph tags only.

```php
<?php

namespace ArtisanPackUI\SEO\View\Components;

use ArtisanPackUI\SEO\Services\SocialMetaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class OpenGraph extends Component
{
    public string $title;
    public ?string $description;
    public ?string $image;
    public string $url;
    public string $type;
    public string $siteName;
    public string $locale;

    public function __construct(
        ?Model $model = null,
        ?string $title = null,
        ?string $description = null,
        ?string $image = null,
        ?string $url = null,
        string $type = 'website',
        ?string $siteName = null,
        ?string $locale = null,
    ) {
        if ($model) {
            $og = app(SocialMetaService::class)->generateOpenGraph($model, $model->seoMeta);

            $this->title = $title ?? $og->title;
            $this->description = $description ?? $og->description;
            $this->image = $image ?? $og->image;
            $this->url = $url ?? $og->url;
            $this->type = $og->type;
            $this->siteName = $og->siteName;
            $this->locale = $og->locale;
        } else {
            $this->title = $title ?? config('app.name');
            $this->description = $description;
            $this->image = $image ?? config('seo.defaults.og_image');
            $this->url = $url ?? url()->current();
            $this->type = $type;
            $this->siteName = $siteName ?? config('app.name');
            $this->locale = $locale ?? config('seo.defaults.og_locale', 'en_US');
        }
    }

    public function render()
    {
        return view('seo::components.open-graph');
    }
}
```

```blade
{{-- resources/views/components/open-graph.blade.php --}}
<meta property="og:title" content="{{ $title }}">
@if($description)
    <meta property="og:description" content="{{ $description }}">
@endif
@if($image)
    <meta property="og:image" content="{{ $image }}">
@endif
<meta property="og:url" content="{{ $url }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ $locale }}">
```

### x-seo:twitter-card

Twitter Card tags.

```blade
{{-- resources/views/components/twitter-card.blade.php --}}
@props([
    'card' => 'summary_large_image',
    'title' => null,
    'description' => null,
    'image' => null,
    'site' => null,
    'creator' => null,
])

<meta name="twitter:card" content="{{ $card }}">
@if($title)
    <meta name="twitter:title" content="{{ $title }}">
@endif
@if($description)
    <meta name="twitter:description" content="{{ $description }}">
@endif
@if($image)
    <meta name="twitter:image" content="{{ $image }}">
@endif
@if($site ?? config('seo.social.twitter.site'))
    <meta name="twitter:site" content="{{ $site ?? config('seo.social.twitter.site') }}">
@endif
@if($creator ?? config('seo.social.twitter.creator'))
    <meta name="twitter:creator" content="{{ $creator ?? config('seo.social.twitter.creator') }}">
@endif
```

### x-seo:schema

Schema.org JSON-LD output.

```php
<?php

namespace ArtisanPackUI\SEO\View\Components;

use ArtisanPackUI\SEO\Services\SchemaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class Schema extends Component
{
    public array $schemas = [];

    public function __construct(
        ?Model $model = null,
        ?array $schemas = null,
        bool $includeOrganization = true,
        bool $includeWebsite = true,
        bool $includeBreadcrumbs = true,
    ) {
        if ($schemas) {
            $this->schemas = $schemas;
        } elseif ($model) {
            $schemaService = app(SchemaService::class);
            $this->schemas = $schemaService->generate($model, $model->seoMeta);
        } else {
            $schemaService = app(SchemaService::class);

            if ($includeOrganization) {
                $this->schemas[] = $schemaService->generateOrganizationSchema();
            }

            if ($includeWebsite) {
                $this->schemas[] = app(\ArtisanPackUI\SEO\Schema\SchemaFactory::class)
                    ->make('website')
                    ->generate(null);
            }
        }
    }

    public function render()
    {
        return view('seo::components.schema');
    }
}
```

```blade
{{-- resources/views/components/schema.blade.php --}}
@foreach($schemas as $schema)
    @if(!empty($schema))
        <script type="application/ld+json">
            {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
    @endif
@endforeach
```

### x-seo:hreflang

Hreflang alternate links for multi-language sites.

```blade
{{-- resources/views/components/hreflang.blade.php --}}
@props(['links' => [], 'model' => null])

@php
    $hreflangLinks = $links;

    if (empty($hreflangLinks) && $model && $model->seoMeta?->hreflang) {
        foreach ($model->seoMeta->hreflang as $lang => $url) {
            $hreflangLinks[] = ['hreflang' => $lang, 'href' => $url];
        }
    }
@endphp

@foreach($hreflangLinks as $link)
    <link rel="alternate" hreflang="{{ $link['hreflang'] }}" href="{{ $link['href'] }}">
@endforeach
```

---

## Usage Examples

### Basic Layout Integration

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO Meta Tags --}}
    @isset($page)
        <x-seo:meta :model="$page" />
    @else
        <x-seo:meta
            title="Welcome"
            description="This is my website description"
        />
    @endisset

    {{-- Other head elements --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}
</body>
</html>
```

### Page-Specific SEO

```blade
{{-- resources/views/pages/show.blade.php --}}
<x-layouts.app>
    <x-slot:head>
        <x-seo:meta :model="$page" />
    </x-slot:head>

    <article>
        <h1>{{ $page->title }}</h1>
        {!! $page->content !!}
    </article>
</x-layouts.app>
```

### Manual Tag Control

```blade
<head>
    {{-- Only basic meta tags --}}
    <x-seo:meta-tags :model="$page" />

    {{-- Custom Open Graph --}}
    <x-seo:open-graph
        title="Custom OG Title"
        description="Custom description for sharing"
        image="{{ $page->featuredImage->url }}"
    />

    {{-- Twitter with different content --}}
    <x-seo:twitter-card
        card="summary"
        title="Twitter-specific title"
    />

    {{-- Schema only --}}
    <x-seo:schema :model="$page" />
</head>
```

### Blog Post with Article Schema

```blade
{{-- resources/views/blog/show.blade.php --}}
<head>
    <x-seo:meta
        :model="$post"
        :include-schema="true"
    />
</head>
```

### Product Page

```blade
{{-- resources/views/products/show.blade.php --}}
<head>
    <x-seo:meta :model="$product" />

    {{-- Additional product-specific schema --}}
    <x-seo:schema :schemas="[$productSchema, $reviewSchema]" />
</head>
```

---

## Component Registration

```php
<?php

// In SEOServiceProvider boot method

use Illuminate\Support\Facades\Blade;

public function boot(): void
{
    // Register Blade components with prefix
    Blade::componentNamespace('ArtisanPackUI\\SEO\\View\\Components', 'seo');
}
```

---

## Helper Functions

For convenience, helper functions are also available:

```php
// In helpers.php

if (!function_exists('seoMeta')) {
    /**
     * Generate SEO meta tags HTML.
     */
    function seoMeta(?Model $model = null, array $options = []): string
    {
        return Blade::render(
            '<x-seo:meta :model="$model" />',
            ['model' => $model, ...$options]
        );
    }
}

if (!function_exists('seoTitle')) {
    /**
     * Build an SEO-friendly title.
     */
    function seoTitle(string $title, bool $includeSuffix = true): string
    {
        return app(SeoService::class)->buildTitle($title, $includeSuffix);
    }
}

if (!function_exists('seoSchema')) {
    /**
     * Generate schema JSON-LD.
     */
    function seoSchema(Model $model): array
    {
        return app(SchemaService::class)->generate($model, $model->seoMeta);
    }
}
```

---

## Localization Note

These Blade components are **output components** that render technical SEO meta tags in the HTML `<head>` section. They do not contain user-facing strings that require translation.

The content rendered by these components (titles, descriptions, schema data) comes from:
- The database (via `SeoMeta` model)
- The application configuration
- Parameters passed at render time

For translatable admin UI components, see [06-admin-components.md](06-admin-components.md).

---

## Related Documents

- [03-core-services.md](03-core-services.md) - Service implementations
- [06-admin-components.md](06-admin-components.md) - Admin UI components
