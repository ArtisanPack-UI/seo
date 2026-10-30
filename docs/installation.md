---
title: Installation
---

# Installation

This guide covers the complete installation process for ArtisanPack UI SEO.

## Install via Composer

```bash
composer require artisanpack-ui/seo
```

Composer installs `artisanpack-ui/core` as the only required dependency. To unlock the [AI Feature Suite](Usage-Ai-Features) added in v1.2.0, also install `artisanpack-ui/ai`:

```bash
composer require artisanpack-ui/ai
```

`artisanpack-ui/ai` requires PHP 8.3+ and Laravel 12+. If you're on PHP 8.2 or Laravel 11, the rest of the SEO package still works — the AI Livewire components, AI API endpoints (`/api/seo/ai/*`), and AI feature registry entries simply are not registered.

## Run Migrations

The package includes migrations for the SEO database tables:

```bash
php artisan migrate
```

This creates the following tables:

- `seo_meta` - Stores SEO metadata for your models
- `redirects` - Stores URL redirect rules
- `sitemap_entries` - Tracks sitemap entries
- `seo_analysis_cache` - Caches SEO analysis results

## Publish Assets

### Configuration File

Publish the configuration file to customize default settings:

```bash
php artisan vendor:publish --tag=seo-config
```

This creates `config/seo.php` with all available options.

### Views (Optional)

If you need to customize the Blade components or Livewire component views:

```bash
php artisan vendor:publish --tag=seo-views
```

### Frontend Components (Optional)

> Added in v1.1.0, expanded in v1.2.0 with the AI trigger components

Publish React or Vue SEO components for building custom admin interfaces:

```bash
# React components
php artisan seo:install-frontend --stack=react

# Vue components
php artisan seo:install-frontend --stack=vue
```

See [Frontend Scaffolding](Advanced-Frontend-Scaffolding) for details.

### AI Feature Suite (Optional)

> Added in v1.2.0

The AI agents run through `artisanpack-ui/ai`, which is an optional dependency. First install it:

```bash
composer require artisanpack-ui/ai
```

Requires PHP 8.3+ and Laravel 12+. Then publish its configuration and set credentials for the provider(s) you plan to use:

```bash
php artisan vendor:publish --tag=ai-config
```

Then scope the `seo.ai.use` ability in your `AuthServiceProvider` to control who can burn AI quota:

```php
Gate::define( 'seo.ai.use', fn ( User $user ) => $user->hasRole( 'editor' ) );
```

See [AI Features](Usage-Ai-Features) for the full agent and endpoint reference.

### Migrations (Optional)

If you need to modify the database schema:

```bash
php artisan vendor:publish --tag=seo-migrations
```

## Service Provider

The package auto-discovers its service provider. If you have disabled auto-discovery, add the service provider to your `config/app.php`:

```php
'providers' => [
    // ...
    ArtisanPackUI\SEO\Providers\SEOServiceProvider::class,
],
```

## Facade (Optional)

The package provides an `SEO` facade for registering analyzers, schema types, and sitemap providers:

```php
use ArtisanPackUI\SEO\Facades\SEO;

SEO::registerSchemaType('Recipe', RecipeSchema::class);
```

## Middleware

URL redirects work with no setup. `HandleRedirects` is added to the `web` middleware group when `seo.redirects.enabled` and `seo.redirects.middleware_enabled` are true. Set `middleware_enabled` to `false` to add it to specific route groups yourself.

## Verify Installation

To verify the installation is working:

```php
// In tinker or a controller

// Should return the SeoService instance
$seo = seo();

// Check configuration
$config = seoConfig('site.name');
```

## Next Steps

- [Requirements](Installation-Requirements) - System requirements and dependencies
- [Configuration](Installation-Configuration) - Detailed configuration options
- [Quick Start Guide](Getting-Started) - Get started quickly
