---
title: Artisan Commands
---

# Artisan Commands

ArtisanPack UI SEO provides four Artisan commands. Other tasks, such as clearing caches, importing redirects, and running analysis, are done through the [services](Api-Services).

| Command | Purpose |
|---------|---------|
| `seo:generate-sitemap` | Generate XML sitemaps |
| `seo:submit-sitemap` | Ping configured search engines with your sitemap URL |
| `seo:write-robots` | Sync `public/robots.txt` with the `seo.robots` settings |
| `seo:install-frontend` | Publish React or Vue components |

## Sitemap Commands

### Generate Sitemap

Without `--output`, this command prints the sitemaps it would generate. Pass `--output` to write files. The sitemap routes don't need this command, because they generate sitemaps on request.

```bash
# Report on the sitemaps for every type
php artisan seo:generate-sitemap

# Write every type to a directory
php artisan seo:generate-sitemap --output=public/sitemaps

# Write one type
php artisan seo:generate-sitemap --type=post --output=public/sitemaps

# Include image, video, and news sitemaps
php artisan seo:generate-sitemap --all --output=public/sitemaps
```

Files are named `sitemap-{type}-{page}.xml`, matching the URLs in the sitemap index.

**Options:**

| Option | Description |
|--------|-------------|
| `--type` | Generate one sitemap type |
| `--all` | Also generate the enabled image, video, and news sitemaps |
| `--output` | Directory to write sitemap files to |
| `--no-cache` | Bypass the cache while generating |
| `--clear-cache` | Clear the sitemap cache after generating |

### Submit Sitemap

Pings each engine in `seo.sitemap.search_engines`. That list is empty by default, because Google and Bing have both retired their ping endpoints. With no engines configured, the command prints a warning and exits successfully. List your sitemap in robots.txt and use [IndexNow](Advanced-Indexnow) to announce updates.

```bash
# Submit to all configured engines
php artisan seo:submit-sitemap

# Submit to one configured engine
php artisan seo:submit-sitemap --engine=custom

# Submit a specific sitemap URL
php artisan seo:submit-sitemap --url=https://example.com/sitemap.xml
```

Passing `--engine` for an engine that isn't configured prints an error and exits with a failure.

**Options:**

| Option | Description |
|--------|-------------|
| `--engine` | Name of a configured search engine |
| `--url` | Sitemap URL to submit |

## Robots Commands

### Write Robots

Writes the generated rules to `public/robots.txt` when `seo.robots.write_static` is on, and removes a file the package wrote when it's off. The command also runs during `php artisan optimize` on Laravel releases that let packages hook into that command, so early Laravel 11 releases need to run it directly.

```bash
php artisan seo:write-robots

# Replace a robots.txt this package didn't write
php artisan seo:write-robots --force
```

If `public/robots.txt` exists and the package didn't write it, the file is left alone and the command exits with a failure. Delete the file or pass `--force`. Run the command again after changing the rules. See [Robots.txt](Advanced-Robots#writing-a-static-file) for details.

**Options:**

| Option | Description |
|--------|-------------|
| `--force` | Overwrite a robots.txt this package didn't write |

## Frontend Commands

### Install Frontend Components

> Added in v1.1.0

Publish React or Vue SEO components and TypeScript type definitions to your application.

```bash
# Interactive prompt to choose stack
php artisan seo:install-frontend

# Install React components directly
php artisan seo:install-frontend --stack=react

# Install Vue components directly
php artisan seo:install-frontend --stack=vue

# Overwrite existing files
php artisan seo:install-frontend --stack=react --force
```

**Options:**

| Option | Description |
|--------|-------------|
| `--stack` | Frontend stack to install (`react` or `vue`) |
| `--force` | Overwrite existing published files |

**Published Files:**

| Tag | Destination |
|-----|-------------|
| `seo-react` | `resources/js/vendor/seo/react/` |
| `seo-vue` | `resources/js/vendor/seo/vue/` |
| `seo-types` | `resources/js/types/seo/` |

The command publishes both the framework-specific components and shared TypeScript type definitions. See the [Frontend Scaffolding](Advanced-Frontend-Scaffolding) guide for detailed usage.

## Scheduling Commands

```php
// In routes/console.php

use Illuminate\Support\Facades\Schedule;

// Write sitemap files daily, if you serve them as static files
Schedule::command('seo:generate-sitemap --output=public/sitemaps')->daily();
```

## Next Steps

- [Sitemaps](Advanced-Sitemaps) - Sitemap configuration
- [Robots.txt](Advanced-Robots) - Robots.txt configuration
- [Caching](Advanced-Caching) - Cache configuration
- [Configuration](Installation-Configuration) - Full config reference
