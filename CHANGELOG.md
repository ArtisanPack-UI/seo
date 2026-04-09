# ArtisanPack UI SEO Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - Unreleased

### Added

- **Schema Type Field Definitions API**: `GET /api/seo/schema/types` now returns rich metadata including descriptions and field definitions for each schema type, enabling dynamic form rendering in React/Vue editors (#35)
- **`seo:install-frontend` Artisan Command**: Publishes React or Vue SEO components and TypeScript type definitions with `--stack` and `--force` options (#34)
- **Publishable Asset Scaffolding**: Publish tags `seo-react`, `seo-vue`, and `seo-types` for frontend component distribution

### Changed

- **Breaking: `SchemaTypeContract` interface gained two new methods** — `getDescription(): string` and `getFieldDefinitions(): array` were added in 1.1.0. External classes that directly implement `SchemaTypeContract` must add these methods. Classes that extend `AbstractSchema` (the recommended approach) are unaffected, as `AbstractSchema` provides default implementations returning an empty string and empty array respectively. To migrate, either implement both methods in your custom class, or switch to extending `AbstractSchema` instead of implementing `SchemaTypeContract` directly.

## [1.0.0] - 2026-01-23

### First Stable Release

This is the first stable release of ArtisanPack UI SEO, a comprehensive SEO management package for Laravel applications built on Livewire 3.

### Highlights

- **Complete SEO Management**: Meta tags, Open Graph, Twitter Cards, and Schema.org markup
- **14 Schema Types**: Built-in JSON-LD structured data for rich search results
- **URL Redirect System**: Exact, regex, and wildcard redirects with hit tracking
- **XML Sitemap Generation**: Standard, image, video, and news sitemaps
- **SEO Content Analysis**: 8 built-in analyzers for content quality scoring
- **Laravel 10, 11, and 12 Support**: Compatible with current Laravel versions (`illuminate/support ^10.0|^11.0|^12.0`)
- **Livewire 3.6+ Compatible** (Optional): Livewire components (SeoMetaEditor, RedirectManager, etc.) require Livewire 3.6+; Blade components work without Livewire
- **Comprehensive Documentation**: Detailed guides and API reference

### Added

#### Core Features

- **HasSeo Trait**: Polymorphic SEO metadata attachment for any Eloquent model
- **Automatic Fallbacks**: Smart fallback system using model properties for SEO values
- **SeoMeta Model**: Store and manage SEO metadata with full field support
- **Feature Toggles**: Enable/disable individual features via configuration

#### Meta Tags

- **Title Management**: Custom titles with site name and separator
- **Description Management**: Custom descriptions with automatic truncation
- **Robots Directives**: Noindex, nofollow, and custom robots meta tags
- **Canonical URLs**: Set canonical URLs to prevent duplicate content issues
- **Viewport Meta**: Configurable viewport settings

#### Social Media

- **Open Graph Tags**: Full Open Graph protocol support for Facebook/LinkedIn
- **Twitter Cards**: Summary and summary_large_image card types
- **Image Support**: Automatic image URL generation with fallbacks
- **Custom Social Titles**: Separate titles/descriptions for social platforms

#### Schema.org / JSON-LD

- **14 Built-in Schema Types**: Article, BlogPosting, Product, Organization, Person, LocalBusiness, Event, Recipe, FAQPage, HowTo, BreadcrumbList, WebSite, WebPage, VideoObject
- **SchemaBuilderInterface**: Contract for creating custom schema builders
- **Automatic Schema Generation**: Generate schema from model data
- **Nested Schema Support**: Support for complex nested schema structures

#### Multi-Language (Hreflang)

- **Hreflang Tags**: Generate hreflang link tags for multi-language sites
- **HreflangService**: Service for managing hreflang configurations
- **x-default Support**: Support for default language fallback

#### URL Redirects

- **Redirect Model**: Store redirect rules with source, destination, and type
- **Match Types**: Exact match, regex patterns, and wildcard matching
- **Status Codes**: Support for 301, 302, 307, and 308 redirects
- **Hit Tracking**: Track redirect usage with hit counts and timestamps
- **Loop Prevention**: Automatic detection and prevention of redirect loops
- **HandleRedirects Middleware**: Automatic redirect handling via middleware

#### XML Sitemaps

- **Standard Sitemaps**: Generate XML sitemaps with URLs, priorities, and frequencies
- **Image Sitemaps**: Include image URLs in sitemaps for image search
- **Video Sitemaps**: Include video metadata in sitemaps
- **News Sitemaps**: Generate Google News-compatible sitemaps
- **Sitemap Index**: Automatic sitemap index generation for large sites
- **SitemapProviderInterface**: Contract for custom sitemap entry providers
- **Cache Support**: Cache generated sitemaps for performance

#### Robots.txt

- **Dynamic Generation**: Generate robots.txt dynamically from configuration
- **Global Rules**: Set default allow/disallow rules for all bots
- **Bot-Specific Rules**: Configure rules for specific user agents
- **Sitemap Inclusion**: Automatically include sitemap URLs in robots.txt
- **RobotsService**: Service for managing robots.txt configuration

#### SEO Analysis

- **8 Built-in Analyzers**: Title, description, headings, keyword density, readability, images, links, and meta robots
- **AnalyzerInterface**: Contract for creating custom analyzers
- **Quality Scoring**: Score content quality with good/warning/error status
- **Suggestions**: Actionable suggestions for improving SEO
- **Caching**: Cache analysis results for performance

#### Caching

- **Meta Tag Caching**: Cache generated meta tags per model
- **Sitemap Caching**: Cache generated sitemaps
- **Redirect Caching**: Cache redirect lookups for performance
- **Analysis Caching**: Cache SEO analysis results
- **CacheService**: Centralized cache management service
- **Configurable TTL**: Set cache duration per feature

### Blade Components

- **x-seo-meta**: All-in-one component rendering meta, OG, Twitter, and schema
- **x-seo-meta-tags**: Basic meta tags component
- **x-seo-open-graph**: Open Graph tags component
- **x-seo-twitter-card**: Twitter Card tags component
- **x-seo-schema**: Schema.org JSON-LD component
- **x-seo-hreflang**: Hreflang link tags component

### Livewire Components

- **SeoMetaEditor**: Full SEO editing interface with tabbed sections
- **RedirectManager**: URL redirect management with CRUD operations
- **SeoDashboard**: Overview dashboard with SEO statistics
- **SeoAnalysisPanel**: Content analysis results display
- **HreflangEditor**: Multi-language URL editor
- **MetaPreview**: Search result preview component
- **SocialPreview**: Social share preview component

### Services

- **SeoService**: Main orchestrator for all SEO operations
- **MetaTagService**: Generate and manage meta tags
- **SocialMetaService**: Generate Open Graph and Twitter Card tags
- **SchemaService**: Generate Schema.org JSON-LD markup
- **SitemapService**: Generate XML sitemaps
- **RedirectService**: Manage URL redirects
- **RobotsService**: Generate robots.txt content
- **HreflangService**: Manage hreflang configurations
- **AnalysisService**: Run SEO content analysis
- **CacheService**: Centralized cache management

### Events

- **SeoMetaCreated**: Dispatched when SEO meta is created
- **SeoMetaUpdated**: Dispatched when SEO meta is updated
- **SitemapGenerated**: Dispatched after sitemap generation
- **RedirectHit**: Dispatched when a redirect is triggered

### Helper Functions

- **seo()**: Get the SeoService instance
- **seoMeta()**: Get SeoMeta for a model
- **seoTitle()**: Format page title with site name
- **seoDescription()**: Truncate description to SEO length
- **seoIsEnabled()**: Check if a feature is enabled
- **seoConfig()**: Get configuration value

### Artisan Commands

- **seo:generate-sitemap**: Generate XML sitemap files
- **seo:submit-sitemap**: Submit sitemap to search engines
- **seo:clear-cache**: Clear all SEO caches

### Facades

- **Seo**: Facade for SeoService
- **Redirect**: Facade for RedirectService

### Contracts/Interfaces

- **SeoableInterface**: Contract for SEO-enabled models
- **AnalyzerInterface**: Contract for SEO analyzers
- **SchemaBuilderInterface**: Contract for schema builders
- **SitemapProviderInterface**: Contract for sitemap entry providers

### Exceptions

- **RedirectLoopException**: Thrown when redirect creates a loop
- **SitemapGenerationException**: Thrown when sitemap generation fails
- **InvalidSchemaTypeException**: Thrown when schema type is invalid
- **InvalidConfigurationException**: Thrown when configuration is invalid

### Extensibility

- **Filter Hooks**: WordPress-style hooks via artisanpack-ui/hooks
- **Custom Schema Types**: Register custom schema builders
- **Custom Analyzers**: Register custom SEO analyzers
- **Custom Sitemap Providers**: Register custom sitemap entry providers
- **Publishable Views**: Customize all Blade views
- **Publishable Config**: Full configuration customization

### Database

- **seo_meta Migration**: Polymorphic SEO metadata storage
- **redirects Migration**: URL redirect rules storage
- **sitemap_entries Migration**: Sitemap entry tracking
- **seo_analysis_cache Migration**: Cached analysis results

### Infrastructure

- **Code Style**: PHP-CS-Fixer and PHPCS with ArtisanPackUI standards
- **GitLab CI/CD**: Multi-PHP version testing (8.2, 8.3, 8.4)
- **Documentation**: Comprehensive markdown documentation
- **WordPress-Style Documentation**: Full PHPDoc blocks on all classes and methods
