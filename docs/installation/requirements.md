---
title: Requirements
---

# Requirements

This page outlines the system requirements and dependencies for ArtisanPack UI SEO.

## PHP Version

- **PHP 8.2** or higher

## Laravel Version

- **Laravel 10.x**, **11.x**, or **12.x**

## Required Dependencies

The following packages are automatically installed as dependencies:

| Package | Version | Purpose |
|---------|---------|---------|
| `artisanpack-ui/core` | ^1.0 | Core utilities and helpers |
| `illuminate/support` | ^10.0\|^11.0\|^12.0 | Laravel support package |

## Optional Dependencies

These packages enhance functionality when installed:

| Package | Purpose |
|---------|---------|
| `artisanpack-ui/media-library` | Social image management and optimization |
| `artisanpack-ui/cms-framework` | CMS content integration |
| `artisanpack-ui/visual-editor` | Visual editor integration |
| `artisanpack-ui/analytics` | Analytics tracking integration |
| `livewire/livewire` | Required for Livewire admin components |

## Database Requirements

The package supports any database driver supported by Laravel:

- MySQL 5.7+ / MariaDB 10.3+
- PostgreSQL 10+
- SQLite 3.8.8+
- SQL Server 2017+

### Required Tables

The package creates the following tables via migrations:

| Table | Purpose |
|-------|---------|
| `seo_meta` | Polymorphic SEO metadata storage |
| `redirects` | URL redirect rules |
| `sitemap_entries` | Sitemap entry tracking |
| `seo_analysis_cache` | Analysis result caching |

## Browser Requirements (Admin UI)

The Livewire admin components require modern browsers:

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Optional System Requirements

### For Sitemap Submission

To use the automatic sitemap submission feature, your server needs:

- Outbound HTTP access to search engine ping endpoints
- `curl` extension enabled in PHP

### For Image Sitemaps

If using image sitemaps with the Media Library integration:

- GD or Imagick PHP extension
- Adequate disk space for image storage

## Development Dependencies

For package development and testing:

| Package | Version | Purpose |
|---------|---------|---------|
| `pestphp/pest` | ^3.8 | Testing framework |
| `pestphp/pest-plugin-laravel` | ^3.2 | Laravel Pest plugin |
| `orchestra/testbench` | ^10.2 | Laravel package testing |
| `artisanpack-ui/code-style` | ^1.1 | Code style checking |
| `artisanpack-ui/code-style-pint` | ^1.1 | Code formatting |

## Next Steps

- [Installation](./installation.md) - Complete installation guide
- [Configuration](./configuration.md) - Configure the package
