# ArtisanPack UI SEO

ArtisanPack UI SEO is a comprehensive SEO management package for Laravel applications. Built on Livewire 3, it provides complete control over meta tags, Open Graph, Twitter Cards, Schema.org markup, XML sitemaps, URL redirects, robots.txt generation, and SEO content analysis.

## Quick Start

### Installation

```bash
# Install the package
composer require artisanpack-ui/seo

# Run migrations
php artisan migrate

# Publish configuration (optional)
php artisan vendor:publish --tag=seo-config
```

### Basic Usage

```php
// Add the HasSeo trait to your model
use ArtisanPackUI\Seo\Traits\HasSeo;

class Post extends Model
{
    use HasSeo;
}
```

```blade
<!-- Render all SEO tags in your layout -->
<head>
    <x-seo-meta :model="$post" />
</head>
```

## Key Features

- **Meta Tag Management**: Title, description, robots directives, and canonical URLs with automatic fallbacks
- **Social Media Tags**: Open Graph for Facebook/LinkedIn and Twitter Cards with image support
- **Schema.org Markup**: 14 built-in JSON-LD schema types for rich search results
- **Multi-Language Support**: Hreflang tags for international SEO
- **URL Redirects**: Exact, regex, and wildcard redirects with hit tracking
- **XML Sitemaps**: Standard, image, video, and news sitemaps with automatic indexing
- **Dynamic Robots.txt**: Configurable rules with bot-specific directives
- **SEO Analysis**: 8 built-in analyzers for content quality scoring
- **Performance Caching**: Comprehensive caching for meta tags, sitemaps, and redirects
- **Admin Components**: Livewire components for visual SEO management
- **AI Features**: Five AI agents for title/description suggestions, content analysis, schema generation, and hreflang gap detection (requires `artisanpack-ui/ai`)

## Components

### Blade Components

| Component | Purpose |
|-----------|---------|
| `<x-seo-meta>` | All-in-one SEO output (meta, OG, Twitter, schema) |
| `<x-seo-meta-tags>` | Basic meta tags only |
| `<x-seo-open-graph>` | Open Graph tags for social sharing |
| `<x-seo-twitter-card>` | Twitter Card meta tags |
| `<x-seo-schema>` | Schema.org JSON-LD markup |
| `<x-seo-hreflang>` | Hreflang link tags for multi-language |

### Livewire Components

| Component | Purpose |
|-----------|---------|
| `<livewire:seo-meta-editor>` | Full SEO editing interface with tabs |
| `<livewire:redirect-manager>` | URL redirect management |
| `<livewire:seo-dashboard>` | SEO overview and statistics |
| `<livewire:seo-analysis-panel>` | Content analysis results |
| `<livewire:hreflang-editor>` | Multi-language URL editor |
| `<livewire:meta-preview>` | Search result preview |
| `<livewire:social-preview>` | Social share preview |
| `<livewire:seo::ai-meta-title-suggestor>` | AI-generated title variants |
| `<livewire:seo::ai-meta-description-suggestor>` | AI-generated meta description |
| `<livewire:seo::ai-content-analyzer>` | AI content quality scoring with recommendations |
| `<livewire:seo::ai-schema-suggestor>` | AI JSON-LD schema type suggestion + starter object |
| `<livewire:seo::ai-hreflang-suggestor>` | AI hreflang gap detection |

### Schema Types

Article, BlogPosting, Product, Organization, Person, LocalBusiness, Event, Recipe, FAQPage, HowTo, BreadcrumbList, WebSite, WebPage, VideoObject

## AI Features

Five agents are shipped for use with the `artisanpack-ui/ai` foundation package. When `artisanpack-ui/ai` is installed and configured with credentials, the SEO service provider auto-registers each feature via `aiFeatures()`.

| Feature key | Default model | Description |
|---|---|---|
| `seo.suggest_meta_title` | `claude-haiku-4-5` | Generate 3-5 SEO title variants (≤60 chars). |
| `seo.suggest_meta_description` | `claude-haiku-4-5` | Generate one 150-160 character meta description. |
| `seo.analyze_content` | `claude-sonnet-4-6` | Score content across keyword usage, readability, structure, and semantic completeness. |
| `seo.generate_schema` | `claude-haiku-4-5` | Pick a JSON-LD schema type from the supported list and produce a starter object. |
| `seo.suggest_hreflang` | `claude-haiku-4-5` | Cross-reference hreflang tags and surface missing or inconsistent relationships. |

### Trigger surfaces

Every feature ships trigger UI on all three supported frontends:

- **Livewire** — the components listed above. Drop them into your editor views.
- **React** — `MetaTitleSuggestor`, `MetaDescriptionSuggestor`, `ContentAnalyzer`, `SchemaSuggestor`, `HreflangSuggestor` under `@artisanpack-ui/seo/react` (or the published path). Backed by the `useAiAgent` hook.
- **Vue** — the same five components under `@artisanpack-ui/seo/vue`, backed by the `useAiAgent` composable.

React and Vue triggers call the JSON API at `/api/seo/ai/{feature}` — see the routes registered under `Route::prefix('ai')` in `routes/api.php` (`suggest-meta-title`, `suggest-meta-description`, `analyze-content`, `generate-schema`, `suggest-hreflang`).

### Feature toggles

Each feature honors the shared registry toggle. A disabled feature returns a `FeatureDisabledException` from the agent and a `409` from the API. Toggle at runtime via the `FeatureRegistry` facade:

```php
use ArtisanPackUI\Ai\Contracts\FeatureRegistry;

app( FeatureRegistry::class )->disable( 'seo.analyze_content' );
```

Or set the initial state in `config/artisanpack.php`:

```php
'ai' => [
    'features' => [
        'seo.suggest_meta_title' => [ 'enabled' => true, 'model' => 'claude-haiku-4-5' ],
        // ...
    ],
],
```

## Documentation

Comprehensive documentation is available at [docs.artisanpackui.dev](https://docs.artisanpackui.dev):

- **[Getting Started](https://docs.artisanpackui.dev/seo/getting-started)** - Quick start guide
- **[Installation](https://docs.artisanpackui.dev/seo/installation)** - Detailed setup instructions
- **[Configuration](https://docs.artisanpackui.dev/seo/installation/configuration)** - All configuration options
- **[Meta Tags](https://docs.artisanpackui.dev/seo/usage/meta-tags)** - Meta tag management
- **[Social Media](https://docs.artisanpackui.dev/seo/usage/social-media)** - Open Graph and Twitter Cards
- **[Schema.org](https://docs.artisanpackui.dev/seo/usage/schema)** - Structured data markup
- **[Components](https://docs.artisanpackui.dev/seo/components)** - Blade and Livewire components
- **[API Reference](https://docs.artisanpackui.dev/seo/api)** - Models, services, and events

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=seo-config
```

### Key Configuration Options

```php
// config/seo.php
return [
    // Site defaults
    'site' => [
        'name' => env('APP_NAME'),
        'separator' => ' | ',
    ],

    // Default meta values
    'defaults' => [
        'title' => null,
        'description' => null,
        'image' => null,
        'robots' => 'index, follow',
    ],

    // Feature toggles
    'redirects' => ['enabled' => true],
    'sitemap' => ['enabled' => true],
    'robots' => ['enabled' => true],
    'analysis' => ['enabled' => true],

    // Caching
    'cache' => [
        'enabled' => true,
        'driver' => null, // Uses default cache driver
        'ttl' => 3600,
    ],

    // Routes
    'routes' => [
        'sitemap' => true,
        'robots' => true,
    ],
];
```

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `SEO_SITE_NAME` | Site name for titles | `APP_NAME` |
| `SEO_TITLE_SEPARATOR` | Separator between title and site name | ` \| ` |
| `SEO_DEFAULT_ROBOTS` | Default robots directive | `index, follow` |
| `SEO_CACHE_ENABLED` | Enable SEO caching | `true` |
| `SEO_CACHE_TTL` | Cache TTL in seconds | `3600` |
| `SEO_REDIRECTS_ENABLED` | Enable redirect handling | `true` |
| `SEO_SITEMAP_ENABLED` | Enable sitemap generation | `true` |
| `SEO_ANALYSIS_ENABLED` | Enable SEO analysis | `true` |

## Artisan Commands

```bash
# Generate XML sitemap
php artisan seo:generate-sitemap

# Submit sitemap to search engines
php artisan seo:submit-sitemap

# Clear SEO cache
php artisan seo:clear-cache
```

## Requirements

- PHP 8.2 or higher
- Laravel 10, 11, 12, or 13
- Livewire 3.6+

## Dependencies

This package integrates with the ArtisanPack UI ecosystem:

- [artisanpack-ui/core](https://github.com/ArtisanPack-UI/core) - Core utilities
- [artisanpack-ui/livewire-ui-components](https://github.com/ArtisanPack-UI/livewire-ui-components) - UI components
- [artisanpack-ui/hooks](https://github.com/ArtisanPack-UI/hooks) - WordPress-style hooks for extensibility

## Events

The package dispatches events for key actions:

```php
use ArtisanPackUI\Seo\Events\SeoMetaCreated;
use ArtisanPackUI\Seo\Events\SeoMetaUpdated;
use ArtisanPackUI\Seo\Events\SitemapGenerated;
use ArtisanPackUI\Seo\Events\RedirectHit;

// Listen for SEO meta changes
Event::listen(SeoMetaUpdated::class, function ($event) {
    // $event->seoMeta contains the updated meta
    // $event->model contains the associated model
});

// Listen for redirect hits
Event::listen(RedirectHit::class, function ($event) {
    // $event->redirect contains the redirect record
    // $event->request contains the HTTP request
});
```

## Helper Functions

```php
// Get the SEO service
$seo = seo();

// Get SEO meta for a model
$meta = seoMeta($post);

// Format a page title with site name
$title = seoTitle('My Page'); // "My Page | Site Name"

// Truncate description to SEO length
$desc = seoDescription($longText); // Truncated to 160 chars

// Check if a feature is enabled
if (seoIsEnabled('sitemap')) {
    // Generate sitemap
}

// Get configuration value
$separator = seoConfig('site.separator');
```

## Extensibility

### Custom Schema Types

```php
use ArtisanPackUI\Seo\Contracts\SchemaBuilderInterface;

class CustomSchemaBuilder implements SchemaBuilderInterface
{
    public function build($model, array $data = []): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CustomType',
            // ... custom properties
        ];
    }
}

// Register via service provider
$this->app->bind('seo.schema.custom', CustomSchemaBuilder::class);
```

### Custom Analyzers

```php
use ArtisanPackUI\Seo\Contracts\AnalyzerInterface;

class CustomAnalyzer implements AnalyzerInterface
{
    public function analyze($model): array
    {
        return [
            'score' => 85,
            'status' => 'good',
            'message' => 'Content passes custom analysis.',
            'suggestions' => [],
        ];
    }
}
```

### Filter Hooks

```php
use function addFilter;

// Modify meta tags before output
addFilter('seo.meta_tags', function (array $tags, $model) {
    $tags['custom-meta'] = 'Custom value';
    return $tags;
});

// Add custom sitemap entries
addFilter('seo.sitemap_entries', function (Collection $entries) {
    $entries->push(new CustomSitemapEntry());
    return $entries;
});
```

## Middleware

Add the redirect middleware to handle URL redirects:

```php
// In bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \ArtisanPackUI\Seo\Http\Middleware\HandleRedirects::class,
    ]);
})
```

## Contributing

Contributions are welcome! To contribute:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Merge Request

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting merge requests.

## License

ArtisanPack UI SEO is open-sourced software licensed under the [MIT license](LICENSE).
