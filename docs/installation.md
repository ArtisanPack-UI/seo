---
title: Installation
---

# Installation

This guide covers the complete installation process for ArtisanPack UI SEO.

## Install via Composer

```bash
composer require artisanpack-ui/seo
```

Composer installs `artisanpack-ui/core` and (starting in v1.2.0) `artisanpack-ui/ai` as required dependencies. `artisanpack-ui/ai` powers the [AI Feature Suite](Usage-Ai-Features); you can leave it inert if you do not plan to use those features.

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

The AI agents run through `artisanpack-ui/ai`. Publish its configuration and set credentials for the provider(s) you plan to use:

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
    ArtisanPackUI\Seo\SeoServiceProvider::class,
],
```

## Facade (Optional)

The package provides a `Seo` facade for convenient access:

```php
use ArtisanPackUI\Seo\Facades\Seo;

$meta = Seo::getMetaForModel($post);
```

## Middleware Setup

To enable automatic URL redirect handling, add the redirect middleware to your `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \ArtisanPackUI\Seo\Http\Middleware\HandleRedirects::class,
    ]);
})
```

Or add it to specific route groups as needed.

## Verify Installation

To verify the installation is working:

```php
// In tinker or a controller
use ArtisanPackUI\Seo\Facades\Seo;

// Should return the SeoService instance
$seo = seo();

// Check configuration
$config = seoConfig('site.name');
```

## Next Steps

- [Requirements](Installation-Requirements) - System requirements and dependencies
- [Configuration](Installation-Configuration) - Detailed configuration options
- [Quick Start Guide](Getting-Started) - Get started quickly
