# ArtisanPack UI SEO Package - Development Plan

**Purpose:** Comprehensive planning documentation for the artisanpack-ui/seo package
**Created:** January 3, 2026
**Status:** Planning Phase

---

## Package Overview

The SEO package provides a complete search engine optimization toolkit for Laravel applications, with special integrations for the ArtisanPack UI ecosystem. It supports both standalone usage (any Laravel model) and deep integration with cms-framework.

### Core Capabilities

| Feature | Description |
|---------|-------------|
| **Meta Tags** | Title, description, robots, canonical URL management |
| **Open Graph** | Facebook, LinkedIn sharing optimization |
| **Twitter Cards** | Twitter-specific meta with image handling |
| **Social Platforms** | Pinterest, Slack unfurling optimization |
| **Schema.org** | Structured data for rich search results |
| **Sitemaps** | XML sitemaps with image, video, news support |
| **Redirects** | 301/302 management with regex, wildcards, chain detection |
| **SEO Analysis** | Readability scoring, keyword density, focus keywords |
| **Multi-language** | hreflang tags for multilingual sites |
| **Admin UI** | Full Livewire component suite for SEO management |

### Design Decisions

1. **Dual-mode architecture**: Works standalone with any model via `HasSeo` trait, plus enhanced integration with cms-framework
2. **Composable components**: Full admin UI available, but developers can use individual components
3. **Cache-on-save strategy**: Meta tags and sitemaps cached when content is saved for optimal performance
4. **Advanced analysis**: Yoast-style SEO scoring with actionable recommendations

---

## Plan Documents

| Document | Purpose |
|----------|---------|
| [01-architecture.md](01-architecture.md) | Package architecture, dependencies, directory structure |
| [02-database-schema.md](02-database-schema.md) | Database tables, migrations, model definitions |
| [03-core-services.md](03-core-services.md) | SeoService, SitemapGenerator, SchemaGenerator, RedirectService |
| [04-traits-and-models.md](04-traits-and-models.md) | HasSeo trait, SeoMeta model, Redirect model |
| [05-seo-analysis.md](05-seo-analysis.md) | Readability, keyword density, focus keywords, scoring |
| [06-admin-components.md](06-admin-components.md) | Livewire components for admin interfaces |
| [07-blade-components.md](07-blade-components.md) | Blade components for outputting SEO tags |
| [08-integrations.md](08-integrations.md) | Integration with media-library, cms-framework, analytics, visual-editor |
| [09-configuration.md](09-configuration.md) | Configuration file structure and options |
| [10-implementation-phases.md](10-implementation-phases.md) | Phased implementation roadmap |

---

## Package Dependencies

### Required
- `php: ^8.2`
- `illuminate/support: ^11.0|^12.0`
- `artisanpack-ui/core: ^1.0`
- `artisanpack-ui/livewire-ui-components: ^1.0` - Admin UI components
- `livewire/livewire: ^3.0`

### Optional (for enhanced features)
- `artisanpack-ui/media-library: ^1.0` - og:image selection from media library
- `artisanpack-ui/cms-framework: ^1.0` - GlobalContent for organization schema
- `artisanpack-ui/analytics: ^1.0` - SEO performance dashboard

### Localization

All user-facing strings use Laravel's `__()` translation function with the actual English string passed directly (e.g., `__('Meta Title')`).

### Development
- `pestphp/pest: ^3.0`
- `pestphp/pest-plugin-laravel: ^3.0`
- `orchestra/testbench: ^9.0`

---

## Quick Reference

### Namespace
```
ArtisanPackUI\SEO
```

### Composer Package
```
artisanpack-ui/seo
```

### Service Provider
```php
ArtisanPackUI\SEO\SEOServiceProvider
```

### Facade
```php
ArtisanPackUI\SEO\Facades\SEO
```

### Key Artisan Commands
```bash
php artisan seo:generate-sitemap     # Generate all sitemaps
php artisan seo:submit-sitemap       # Submit to search engines
php artisan seo:analyze {model}      # Run SEO analysis on content
php artisan seo:check-redirects      # Detect redirect chains/loops
php artisan seo:import-redirects     # Import redirects from CSV
```

---

## Related Documentation

- [ArtisanPack UI README](../../../artisanpack-ui-dev/plans/README.md) - Ecosystem overview
- [04-package-specifications.md](../../../artisanpack-ui-dev/plans/04-package-specifications.md) - Original SEO spec
- [05-ux-principles.md](../../../artisanpack-ui-dev/plans/05-ux-principles.md) - UX guidelines

---

*Last Updated: January 3, 2026*
