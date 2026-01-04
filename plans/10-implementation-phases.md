# Implementation Phases

**Purpose:** Define the phased implementation roadmap for the SEO package
**Last Updated:** January 3, 2026

---

## Overview

The SEO package will be implemented in four phases, each building upon the previous. This approach allows for early releases with core functionality while progressively adding advanced features.

---

## Phase 1: Core Foundation

**Goal:** Establish the foundational architecture and basic meta tag functionality.

### 1.1 Package Scaffolding

- [ ] Set up package structure and namespaces
- [ ] Configure service provider with proper registration
- [ ] Create configuration file with all options
- [ ] Set up Blade view paths and component registration
- [ ] Configure route registration (sitemap.xml, robots.txt)

### 1.2 Database Layer

- [ ] Create `seo_meta` migration
  - Polymorphic relationship columns
  - Title, description, canonical, robots fields
  - OG and Twitter card fields
  - Schema data JSON column
- [ ] Create `SeoMeta` Eloquent model
- [ ] Create `HasSeo` trait for models
- [ ] Create `SeoObserver` for automatic cache invalidation

### 1.3 Core Services

```php
// Services to implement in Phase 1
- SeoService (main facade service)
- MetaTagService (title, description, canonical, robots)
- SocialMetaService (Open Graph, Twitter Cards)
- CacheService (cache invalidation and retrieval)
```

### 1.4 Basic Blade Components

- [ ] `<x-seo:meta>` - All-in-one meta output
- [ ] `<x-seo:meta-tags>` - Standard meta tags
- [ ] `<x-seo:open-graph>` - OG tags
- [ ] `<x-seo:twitter-card>` - Twitter card tags

### 1.5 Helper Functions

```php
// Phase 1 helpers
seo()                           // Get SeoService facade
seoMeta($model)                 // Get model's SEO meta
seoTitle($title, $suffix)       // Format page title
seoDescription($description)    // Format meta description
```

### 1.6 Deliverables

- Working meta tag output for any Eloquent model
- Basic Open Graph and Twitter Card support
- Cache-on-save functionality
- Configuration-driven defaults
- Basic helper functions

---

## Phase 2: Schema.org & Sitemaps

**Goal:** Add structured data and sitemap generation capabilities.

### 2.1 Schema.org Service

```php
// Schema builders to implement
- SchemaService (main coordinator)
- Builders/OrganizationSchema
- Builders/WebsiteSchema
- Builders/ArticleSchema
- Builders/ProductSchema
- Builders/LocalBusinessSchema
- Builders/FAQPageSchema
- Builders/BreadcrumbSchema
- Builders/EventSchema
- Builders/PersonSchema
- Builders/VideoObjectSchema
```

### 2.2 Schema Blade Component

- [ ] `<x-seo:schema>` component with type parameter
- [ ] JSON-LD output formatting
- [ ] Multiple schema support per page

### 2.3 Sitemap Infrastructure

- [ ] Create `sitemap_entries` migration
- [ ] Create `SitemapEntry` model
- [ ] Create `SitemapService` with generator logic
- [ ] Implement sitemap caching

### 2.4 Sitemap Features

```php
// Sitemap capabilities
- Standard XML sitemap generation
- Sitemap index for large sites (>10,000 URLs)
- Automatic entry creation/update on model save
- Priority and change frequency settings
- Path exclusion patterns
```

### 2.5 Sitemap Routes

- [ ] `/sitemap.xml` - Main sitemap or index
- [ ] `/sitemap-{type}-{page}.xml` - Type-specific sitemaps
- [ ] Configurable route registration

### 2.6 Robots.txt

- [ ] Dynamic robots.txt generation
- [ ] Configurable disallow rules
- [ ] Bot-specific rules support
- [ ] Automatic sitemap reference

### 2.7 Deliverables

- Complete Schema.org support for 10 types
- XML sitemap generation with caching
- Sitemap index for large sites
- Dynamic robots.txt
- Automatic sitemap updates on content changes

---

## Phase 3: Redirects & Admin UI

**Goal:** Implement redirect management and Livewire admin components.

### 3.1 Redirect System

- [ ] Create `redirects` migration
- [ ] Create `Redirect` model with scopes
- [ ] Create `RedirectService` with matching logic
- [ ] Create `RedirectMiddleware`

### 3.2 Advanced Redirect Features

```php
// Redirect capabilities
- Exact path matching
- Wildcard patterns (*)
- Regex patterns with capture groups
- Status code selection (301, 302, 307, 308)
- Hit tracking and statistics
- Chain detection and prevention
- Regex timeout protection (ReDoS)
```

### 3.3 Livewire Admin Components

```php
// Full admin components
- SeoMetaEditor (complete meta editing)
- SeoAnalysisPanel (analysis display)
- RedirectManager (CRUD for redirects)
- SitemapManager (sitemap configuration)
- MetaPreview (Google/social previews)
```

### 3.4 Composable Components

```php
// Smaller reusable components
- TitleEditor
- DescriptionEditor
- CanonicalEditor
- RobotsSelector
- OgImagePicker
- FocusKeywordInput
- SeoScoreBadge
- PreviewCard
```

### 3.5 Component Assets

- [ ] Publish Livewire views
- [ ] Character count indicators
- [ ] Real-time validation feedback
- [ ] SERP preview styling

### 3.6 Deliverables

- Complete redirect management system
- Full-featured SEO meta editor
- Modular, composable admin components
- Real-time preview functionality
- Redirect analytics

---

## Phase 4: Advanced Analysis & Integrations

**Goal:** Implement SEO analysis system and package integrations.

### 4.1 Analysis Infrastructure

- [ ] Create `seo_analysis_cache` migration
- [ ] Create `SeoAnalysisCache` model
- [ ] Create `HasSeoAnalysis` trait
- [ ] Create `AnalysisService` coordinator

### 4.2 Analyzer Classes

```php
// Individual analyzers
- ReadabilityAnalyzer (Flesch-Kincaid)
- KeywordDensityAnalyzer
- FocusKeywordAnalyzer
- MetaLengthAnalyzer
- ContentLengthAnalyzer
- HeadingStructureAnalyzer
- InternalLinkAnalyzer
- ImageAltAnalyzer
```

### 4.3 Analysis Features

```php
// Analysis capabilities
- Overall SEO score (0-100)
- Category scores (readability, keyword, meta, content)
- Actionable recommendations
- Color-coded indicators (red/yellow/green)
- Queued analysis for performance
- Custom analyzer registration
```

### 4.4 Hreflang Support

- [ ] Create `HreflangService`
- [ ] Automatic language detection
- [ ] Manual language mapping
- [ ] `<x-seo:hreflang>` component

### 4.5 Package Integrations

```php
// Integration services
- MediaLibraryIntegration
  - OG image selection from media library
  - Image optimization for social sharing

- CmsFrameworkIntegration
  - Auto-fetch organization data
  - Page/Post SEO defaults
  - Template-based SEO rules

- AnalyticsIntegration
  - Search console data display
  - Keyword performance tracking
  - Page performance metrics

- VisualEditorIntegration
  - SEO sidebar panel
  - Real-time analysis updates
  - Focus keyword in editor context
```

### 4.6 Advanced Sitemap Types

```php
// Extended sitemap support
- Image sitemaps
- Video sitemaps
- News sitemaps (Google News)
- Custom sitemap providers
- Search engine ping on update
```

### 4.7 Deliverables

- Complete SEO analysis system with scoring
- Readability analysis with recommendations
- Keyword optimization tools
- All four package integrations
- Multi-language hreflang support
- Advanced sitemap types

---

## Testing Strategy

### Unit Tests

```php
// Test coverage per phase
Phase 1:
- MetaTagServiceTest
- SocialMetaServiceTest
- CacheServiceTest
- HasSeoTraitTest
- SeoMetaModelTest

Phase 2:
- SchemaServiceTest
- Schema builder tests (each type)
- SitemapServiceTest
- SitemapEntryModelTest
- RobotsTxtTest

Phase 3:
- RedirectServiceTest
- RedirectMiddlewareTest
- Livewire component tests
- Redirect matching tests

Phase 4:
- AnalysisServiceTest
- Individual analyzer tests
- Integration tests
- HreflangServiceTest
```

### Feature Tests

```php
// End-to-end scenarios
- Meta tag rendering in views
- Sitemap XML output
- Redirect following
- Analysis score calculation
- Admin component interactions
```

### Browser Tests (Dusk)

```php
// UI interaction tests
- SeoMetaEditor form submission
- Real-time preview updates
- Redirect manager CRUD
- Analysis panel display
```

---

## Migration Path

### From Existing SEO Packages

```php
// Artisan commands for migration
php artisan seo:migrate-from-spatie    // Spatie Laravel SEO
php artisan seo:migrate-from-artesaos  // SEOTools
php artisan seo:migrate-from-custom    // Custom implementations

// Migration steps
1. Export existing SEO data to JSON
2. Map fields to new schema
3. Import with validation
4. Verify and cleanup
```

### Database Migrations

```bash
# Run in order
php artisan migrate --path=vendor/artisanpack-ui/seo/database/migrations

# Or publish and run
php artisan vendor:publish --tag=seo-migrations
php artisan migrate
```

---

## Version Milestones

### v0.1.0 - Alpha (Phase 1)

- Core meta tag functionality
- Basic OG/Twitter support
- HasSeo trait
- Blade components for output
- Configuration system

### v0.2.0 - Alpha (Phase 2)

- Schema.org support
- XML sitemap generation
- Robots.txt generation
- Sitemap auto-updates

### v0.3.0 - Beta (Phase 3)

- Redirect management
- Admin Livewire components
- Composable UI elements
- Preview functionality

### v1.0.0 - Stable (Phase 4)

- SEO analysis system
- All package integrations
- Hreflang support
- Advanced sitemaps
- Full documentation

### v1.x - Future Enhancements

- Custom schema builder
- AI-powered suggestions
- Competitor analysis
- Bulk editing tools
- SEO audit reports

---

## Documentation Plan

### Package Documentation

```
docs/
├── installation.md
├── configuration.md
├── quick-start.md
├── meta-tags.md
├── schema-org.md
├── sitemaps.md
├── redirects.md
├── analysis.md
├── admin-components.md
├── blade-components.md
├── integrations/
│   ├── media-library.md
│   ├── cms-framework.md
│   ├── analytics.md
│   └── visual-editor.md
├── api-reference.md
└── migration-guide.md
```

### Code Examples

- Each documentation page includes working code examples
- Copy-paste ready snippets
- Common use case recipes
- Troubleshooting guides

---

## Dependencies

### Required

```json
{
    "php": "^8.2",
    "illuminate/support": "^11.0|^12.0",
    "spatie/schema-org": "^3.0"
}
```

### Optional (Dev)

```json
{
    "orchestra/testbench": "^9.0",
    "pestphp/pest": "^2.0|^3.0",
    "livewire/livewire": "^3.0"
}
```

### Suggested

```json
{
    "artisanpack-ui/media-library": "^1.0",
    "artisanpack-ui/cms-framework": "^1.0",
    "artisanpack-ui/analytics": "^1.0"
}
```

---

## Quality Checklist

### Per-Phase Completion Criteria

- [ ] All planned features implemented
- [ ] Unit test coverage > 80%
- [ ] Feature tests for key scenarios
- [ ] Documentation written
- [ ] Code formatted with Pint
- [ ] PHPCS passing
- [ ] No breaking changes from previous phase
- [ ] Performance benchmarks met
- [ ] Accessibility reviewed (admin UI)

### Release Criteria

- [ ] All phases complete
- [ ] Full test suite passing
- [ ] Documentation complete
- [ ] Migration tools tested
- [ ] Security audit passed
- [ ] Performance optimized
- [ ] Packagist ready

---

## Related Documents

- [README.md](README.md) - Plan overview
- [01-architecture.md](01-architecture.md) - Package architecture
- [02-database-schema.md](02-database-schema.md) - Database design
- [03-core-services.md](03-core-services.md) - Service classes
- [04-traits-and-models.md](04-traits-and-models.md) - Eloquent integration
- [05-seo-analysis.md](05-seo-analysis.md) - Analysis system
- [06-admin-components.md](06-admin-components.md) - Livewire components
- [07-blade-components.md](07-blade-components.md) - Output components
- [08-integrations.md](08-integrations.md) - Package integrations
- [09-configuration.md](09-configuration.md) - Configuration options
