# SEO Package Architecture

**Purpose:** Define the overall architecture, directory structure, and component relationships
**Last Updated:** January 3, 2026

---

## Architecture Overview

The SEO package follows a layered architecture with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────────┐
│                        Application Layer                         │
│              (Consuming Laravel App / Keystone CMS)              │
└─────────────────────────────────────────────────────────────────┘
                                │
        ┌───────────────────────┼───────────────────────┐
        ▼                       ▼                       ▼
┌───────────────┐     ┌─────────────────┐     ┌───────────────┐
│ Blade Components │   │ Livewire Components │   │ Artisan Commands │
│ (Output Layer)   │   │ (Admin UI Layer)    │   │ (CLI Layer)      │
└───────────────┘     └─────────────────┘     └───────────────┘
        │                       │                       │
        └───────────────────────┼───────────────────────┘
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                         Service Layer                            │
│  SeoService │ SitemapService │ SchemaService │ RedirectService  │
│  AnalysisService │ SocialMetaService │ CacheService             │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                          Data Layer                              │
│         Models │ Traits │ Repositories │ Cache                  │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                        Database Layer                            │
│    seo_meta │ redirects │ sitemap_entries │ seo_analysis_cache  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Directory Structure

```
seo/
├── config/
│   └── seo.php                          # Main configuration file
├── database/
│   └── migrations/
│       ├── create_seo_meta_table.php
│       ├── create_redirects_table.php
│       ├── create_sitemap_entries_table.php
│       └── create_seo_analysis_cache_table.php
├── resources/
│   └── views/
│       ├── components/                  # Blade components
│       │   ├── meta-tags.blade.php
│       │   ├── open-graph.blade.php
│       │   ├── twitter-card.blade.php
│       │   ├── schema.blade.php
│       │   ├── social-meta.blade.php
│       │   └── hreflang.blade.php
│       └── livewire/                    # Livewire component views
│           ├── seo-meta-editor.blade.php
│           ├── seo-analysis-panel.blade.php
│           ├── redirect-manager.blade.php
│           ├── sitemap-manager.blade.php
│           └── seo-settings.blade.php
├── routes/
│   ├── web.php                          # Sitemap, robots.txt routes
│   └── api.php                          # API endpoints for analysis
├── src/
│   ├── Commands/
│   │   ├── GenerateSitemapCommand.php
│   │   ├── SubmitSitemapCommand.php
│   │   ├── AnalyzeContentCommand.php
│   │   ├── CheckRedirectsCommand.php
│   │   └── ImportRedirectsCommand.php
│   ├── Contracts/
│   │   ├── SeoableContract.php          # Interface for SEO-enabled models
│   │   ├── AnalyzerContract.php         # Interface for analysis plugins
│   │   ├── SchemaTypeContract.php       # Interface for schema types
│   │   └── SitemapProviderContract.php  # Interface for sitemap content
│   ├── DTOs/
│   │   ├── MetaTagsDTO.php
│   │   ├── OpenGraphDTO.php
│   │   ├── TwitterCardDTO.php
│   │   ├── SchemaDTO.php
│   │   ├── AnalysisResultDTO.php
│   │   └── RedirectDTO.php
│   ├── Events/
│   │   ├── SeoMetaUpdated.php
│   │   ├── RedirectCreated.php
│   │   ├── SitemapGenerated.php
│   │   └── AnalysisCompleted.php
│   ├── Exceptions/
│   │   ├── InvalidSchemaTypeException.php
│   │   ├── RedirectLoopException.php
│   │   └── SitemapGenerationException.php
│   ├── Facades/
│   │   └── SEO.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── SitemapController.php
│   │   │   ├── RobotsController.php
│   │   │   └── Api/
│   │   │       └── AnalysisController.php
│   │   ├── Livewire/
│   │   │   ├── SeoMetaEditor.php
│   │   │   ├── SeoAnalysisPanel.php
│   │   │   ├── RedirectManager.php
│   │   │   ├── RedirectEditor.php
│   │   │   ├── SitemapManager.php
│   │   │   ├── SeoSettings.php
│   │   │   └── Partials/
│   │   │       ├── MetaPreview.php
│   │   │       ├── SocialPreview.php
│   │   │       ├── SchemaEditor.php
│   │   │       └── FocusKeywordInput.php
│   │   └── Middleware/
│   │       └── HandleRedirects.php
│   ├── Models/
│   │   ├── SeoMeta.php
│   │   ├── Redirect.php
│   │   ├── SitemapEntry.php
│   │   └── SeoAnalysisCache.php
│   ├── Providers/
│   │   └── SEOServiceProvider.php
│   ├── Schema/
│   │   ├── SchemaFactory.php
│   │   ├── Types/
│   │   │   ├── OrganizationSchema.php
│   │   │   ├── LocalBusinessSchema.php
│   │   │   ├── WebsiteSchema.php
│   │   │   ├── WebPageSchema.php
│   │   │   ├── ArticleSchema.php
│   │   │   ├── BlogPostingSchema.php
│   │   │   ├── ProductSchema.php
│   │   │   ├── ServiceSchema.php
│   │   │   ├── EventSchema.php
│   │   │   ├── FAQPageSchema.php
│   │   │   ├── BreadcrumbListSchema.php
│   │   │   ├── ReviewSchema.php
│   │   │   └── AggregateRatingSchema.php
│   │   └── Builders/
│   │       └── SchemaBuilder.php
│   ├── Services/
│   │   ├── SeoService.php               # Main SEO orchestration
│   │   ├── MetaTagService.php           # Meta tag generation
│   │   ├── SocialMetaService.php        # OG, Twitter, Pinterest, Slack
│   │   ├── SchemaService.php            # Schema.org generation
│   │   ├── SitemapService.php           # Sitemap generation
│   │   ├── Sitemap/
│   │   │   ├── SitemapGenerator.php
│   │   │   ├── SitemapIndexGenerator.php
│   │   │   ├── ImageSitemapGenerator.php
│   │   │   ├── VideoSitemapGenerator.php
│   │   │   ├── NewsSitemapGenerator.php
│   │   │   └── SitemapSubmitter.php
│   │   ├── RedirectService.php          # Redirect management
│   │   ├── Redirect/
│   │   │   ├── RedirectMatcher.php
│   │   │   ├── RedirectChainDetector.php
│   │   │   └── BrokenLinkSuggester.php
│   │   ├── AnalysisService.php          # SEO analysis orchestration
│   │   ├── Analysis/
│   │   │   ├── ReadabilityAnalyzer.php
│   │   │   ├── KeywordDensityAnalyzer.php
│   │   │   ├── FocusKeywordAnalyzer.php
│   │   │   ├── MetaLengthAnalyzer.php
│   │   │   ├── HeadingStructureAnalyzer.php
│   │   │   ├── ImageAltAnalyzer.php
│   │   │   ├── InternalLinkAnalyzer.php
│   │   │   └── ContentLengthAnalyzer.php
│   │   ├── CacheService.php             # SEO cache management
│   │   └── HreflangService.php          # Multi-language support
│   ├── Traits/
│   │   ├── HasSeo.php                   # Main trait for models
│   │   ├── HasFocusKeyword.php          # Focus keyword tracking
│   │   └── HasSeoAnalysis.php           # Analysis integration
│   ├── helpers.php                      # Global helper functions
│   └── SEO.php                          # Main facade class
├── tests/
│   ├── Feature/
│   │   ├── SeoMetaTest.php
│   │   ├── SitemapTest.php
│   │   ├── RedirectTest.php
│   │   ├── SchemaTest.php
│   │   └── AnalysisTest.php
│   └── Unit/
│       ├── Services/
│       ├── Analyzers/
│       └── Schema/
├── composer.json
├── phpunit.xml
├── README.md
├── CHANGELOG.md
└── LICENSE
```

---

## Component Relationships

### Core Service Dependencies

```
SEO (Facade)
    └── SeoService
            ├── MetaTagService
            ├── SocialMetaService
            │       ├── OpenGraphGenerator
            │       ├── TwitterCardGenerator
            │       ├── PinterestMetaGenerator
            │       └── SlackMetaGenerator
            ├── SchemaService
            │       └── SchemaFactory
            │               └── Schema Types (Organization, Article, etc.)
            ├── SitemapService
            │       ├── SitemapGenerator
            │       ├── SitemapIndexGenerator
            │       ├── ImageSitemapGenerator
            │       ├── VideoSitemapGenerator
            │       └── NewsSitemapGenerator
            ├── RedirectService
            │       ├── RedirectMatcher
            │       ├── RedirectChainDetector
            │       └── BrokenLinkSuggester
            ├── AnalysisService
            │       ├── ReadabilityAnalyzer
            │       ├── KeywordDensityAnalyzer
            │       ├── FocusKeywordAnalyzer
            │       └── ... (other analyzers)
            ├── HreflangService
            └── CacheService
```

### Model Relationships

```
Model with HasSeo trait
    └── morphOne → SeoMeta
                      ├── meta_title
                      ├── meta_description
                      ├── og_* fields
                      ├── twitter_* fields
                      ├── schema_markup (JSON)
                      ├── focus_keyword
                      ├── hreflang (JSON)
                      └── analysis_cache → SeoAnalysisCache
```

---

## Request Flow Examples

### Rendering SEO Meta Tags

```
1. Page loads with <x-seo:meta :model="$page" />
2. Blade component calls SeoService::getMetaTags($page)
3. SeoService checks cache (CacheService)
4. If cached, return cached meta tags
5. If not cached:
   a. Get SeoMeta from model via HasSeo trait
   b. Generate meta tags via MetaTagService
   c. Generate Open Graph via SocialMetaService
   d. Generate Twitter Card via SocialMetaService
   e. Generate Schema via SchemaService
   f. Cache result
6. Blade component outputs HTML
```

### Handling Redirects

```
1. Request comes in for /old-url
2. HandleRedirects middleware intercepts
3. RedirectService::findMatch('/old-url')
4. RedirectMatcher checks:
   a. Exact match in redirects table
   b. Regex pattern match
   c. Wildcard match
5. If match found:
   a. Increment hit counter
   b. Return redirect response (301/302)
6. If no match, continue to normal routing
```

### Running SEO Analysis

```
1. User saves content in admin
2. SeoMetaUpdated event fired
3. AnalysisService triggered (sync or queued)
4. Each analyzer runs:
   a. ReadabilityAnalyzer → Flesch-Kincaid score
   b. KeywordDensityAnalyzer → keyword frequency
   c. FocusKeywordAnalyzer → keyword in title, headings, etc.
   d. MetaLengthAnalyzer → title/description length
   e. HeadingStructureAnalyzer → H1/H2/H3 structure
   f. ImageAltAnalyzer → missing alt text
   g. InternalLinkAnalyzer → internal link count
   h. ContentLengthAnalyzer → word count
5. Results aggregated into AnalysisResultDTO
6. Score calculated (0-100)
7. Results cached in SeoAnalysisCache
8. Event dispatched for UI update
```

---

## Caching Strategy

### Cache Keys

```php
// Meta tags cache
"seo:meta:{model_type}:{model_id}"

// Sitemap cache
"seo:sitemap:{type}"                    // pages, posts, products, etc.
"seo:sitemap:index"                     // sitemap index
"seo:sitemap:image:{model_type}"        // image sitemap
"seo:sitemap:video:{model_type}"        // video sitemap
"seo:sitemap:news"                      // news sitemap

// Analysis cache
"seo:analysis:{model_type}:{model_id}"

// Redirect cache
"seo:redirects:all"                     // all active redirects
"seo:redirects:patterns"                // regex patterns only
```

### Cache Invalidation

Cache is invalidated on:
1. Model save (via HasSeo trait observer)
2. SeoMeta update
3. Redirect create/update/delete
4. Manual cache clear command
5. Configuration change

---

## Extension Points

### Custom Analyzers

```php
// Register custom analyzer
SEO::registerAnalyzer('custom', CustomAnalyzer::class);

// CustomAnalyzer must implement AnalyzerContract
class CustomAnalyzer implements AnalyzerContract
{
    public function analyze(Model $model, ?string $focusKeyword = null): AnalysisResultDTO;
    public function getName(): string;
    public function getWeight(): int; // 0-100, affects overall score
}
```

### Custom Schema Types

```php
// Register custom schema type
SEO::registerSchemaType('recipe', RecipeSchema::class);

// RecipeSchema must implement SchemaTypeContract
class RecipeSchema implements SchemaTypeContract
{
    public function generate(Model $model): array;
    public function getType(): string; // e.g., 'Recipe'
}
```

### Custom Sitemap Providers

```php
// Register custom sitemap provider
SEO::registerSitemapProvider('events', EventSitemapProvider::class);

// EventSitemapProvider must implement SitemapProviderContract
class EventSitemapProvider implements SitemapProviderContract
{
    public function getUrls(): Collection;
    public function getChangeFrequency(): string;
    public function getPriority(): float;
}
```

---

## Security Considerations

1. **Redirect validation**: Prevent open redirects by validating destination URLs
2. **Schema sanitization**: Escape all user content in schema output
3. **Regex limits**: Prevent ReDoS attacks with regex timeout limits
4. **Rate limiting**: API endpoints for analysis are rate-limited
5. **Authorization**: Admin components check permissions via policies

---

## Performance Considerations

1. **Aggressive caching**: All generated output cached until content changes
2. **Lazy loading**: Schema types loaded on-demand
3. **Queued analysis**: Heavy analysis runs in background jobs
4. **Chunked sitemaps**: Large sitemaps split into smaller files
5. **Database indexes**: Optimized indexes on redirect paths, sitemap entries

---

## Localization

All user-facing strings use Laravel's `__()` translation function for internationalization support. The actual English string is passed directly to the function.

### Usage in Views

```blade
{{-- Livewire component view --}}
<x-artisanpack-input
    wire:model="meta_title"
    label="{{ __('SEO Title') }}"
/>

<x-artisanpack-textarea
    wire:model="meta_description"
    label="{{ __('Meta Description') }}"
/>
```

### Usage in PHP

```php
// In Livewire components or services
$this->addError('meta_title', __('Title is too long (maximum :max characters)', ['max' => 60]));
```

---

## UI Components

All admin UI views use components from the `artisanpack-ui/livewire-ui-components` package. This ensures consistent styling and behavior across the ArtisanPack UI ecosystem.

### Component Usage

```blade
{{-- Form inputs --}}
<x-artisanpack-input wire:model="title" label="{{ __('seo::seo.meta.title_label') }}" />
<x-artisanpack-textarea wire:model="description" label="{{ __('seo::seo.meta.description_label') }}" />
<x-artisanpack-select wire:model="robots" :options="$robotsOptions" />

{{-- Layout components --}}
<x-artisanpack-card>
    <x-slot:header>{{ __('seo::seo.sections.meta_tags') }}</x-slot:header>
    {{-- content --}}
</x-artisanpack-card>

{{-- Feedback components --}}
<x-artisanpack-alert type="warning">{{ __('seo::seo.warnings.title_length') }}</x-artisanpack-alert>
<x-artisanpack-badge color="success">{{ __('seo::analysis.score_good') }}</x-artisanpack-badge>

{{-- Actions --}}
<x-artisanpack-button wire:click="save" color="primary">{{ __('seo::seo.actions.save') }}</x-artisanpack-button>
```

---

## Related Documents

- [02-database-schema.md](02-database-schema.md) - Database structure
- [03-core-services.md](03-core-services.md) - Service implementations
- [09-configuration.md](09-configuration.md) - Configuration options
