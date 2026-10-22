---
title: Caching
---

# Caching

ArtisanPack UI SEO implements comprehensive caching to optimize performance. This guide covers cache configuration, usage, and management.

## Overview

The package caches:
- SEO meta data for models
- SEO analysis results
- Redirect lookups
- Sitemap content
- Robots.txt content

## Configuration

```php
// In config/seo.php
'cache' => [
    'enabled' => true,
    'ttl' => 3600,          // 1 hour default
    'driver' => null,       // null = default driver
    'prefix' => 'seo',
],
```

### Feature-Specific Caching

Each feature has its own cache settings:

```php
'redirects' => [
    'cache_enabled' => true,
    'cache_ttl' => 86400,   // 24 hours
],

'sitemap' => [
    'cache_enabled' => true,
    'cache_ttl' => 3600,    // 1 hour
],

'analysis' => [
    'cache_enabled' => true,
    'cache_ttl' => 86400,   // 24 hours
],
```

## Cache Service

### Access

```php
use ArtisanPackUI\Seo\Services\CacheService;

$cacheService = app('seo.cache');
```

### Methods

```php
// Get cached value
$value = $cacheService->get('meta', $model->id);

// Set cached value
$cacheService->set('meta', $model->id, $data, 3600);

// Check if cached
$exists = $cacheService->has('meta', $model->id);

// Remove from cache
$cacheService->forget('meta', $model->id);

// Clear all caches for a model
$cacheService->clearAllForModel($model);

// Clear all SEO caches
$cacheService->flush();

// Get cache key
$key = $cacheService->key('meta', $model->id);
// Returns: "seo_meta_123"
```

## Cache Keys

The package uses structured cache keys:

| Type | Key Pattern | Example |
|------|-------------|---------|
| Meta | `seo_meta_{id}` | `seo_meta_42` |
| Analysis | `seo_analysis_{id}` | `seo_analysis_42` |
| Redirect | `seo:redirects:g{generation}:match:{md5(path)}` | `seo:redirects:g01K…:match:5d41…` |
| Sitemap | `{prefix}:sitemap:g{generation}:{variant}[:{type}][:page:{n}]` | `seo:sitemap:g01K…:standard:page` |
| Robots | `seo_robots` | `seo_robots` |

Redirect and sitemap keys include a generation token: a ULID stored under
`seo:redirects:generation` and `{prefix}:sitemap:generation`. Clearing either
cache writes a new token in a single write, so every older key stops being
read and expires through its TTL. A token is never reused, so overlapping
clears or an evicted token key can't bring back stale entries. If the token
key is missing, the next lookup starts a new one.

## Automatic Cache Invalidation

The package automatically clears relevant caches when:

### Model Events

```php
// When model is updated
$post->update(['title' => 'New Title']);
// → Clears: seo_meta_42, seo_analysis_42

// When SEO meta is updated
$post->updateSeoMeta(['meta_title' => 'New']);
// → Clears: seo_meta_42

// When model is deleted
$post->delete();
// → Clears all related caches
```

### Redirect Changes

`RedirectService` clears the redirect lookup cache whenever it creates,
updates, deletes, imports, or bulk-updates redirects. This covers the admin
interface, the API, and the `seoCreateRedirect()` / `seoDeleteRedirect()`
helpers. Writes made directly on the `Redirect` model skip the service, so
clear the cache yourself afterwards:

```php
Redirect::where('status_code', 302)->update(['status_code' => 301]);

seoRedirect()->clearCache();
```

### Sitemap Changes

Cached sitemap XML and `llms.txt` are cleared whenever a model tracked by
`HasSeo` is saved, force deleted, or restored. `seo:generate-sitemap --output`
also clears the cache before regenerating. After changes that skip model
events, such as a bulk import, clear it yourself:

```php
use ArtisanPackUI\SEO\Services\SitemapService;

app(SitemapService::class)->clearCache();
```

## Manual Cache Clearing

### Using Service

```php
$cacheService = app('seo.cache');

// Clear specific cache type
$cacheService->forget('meta', $post->id);
$cacheService->forget('analysis', $post->id);

// Clear all caches for model
$cacheService->clearForModel($post);

// Flush all SEO caches
$cacheService->flush();
```

### Using Artisan

```bash
# Clear all SEO caches
php artisan seo:clear-cache

# Clear specific type
php artisan seo:clear-cache --type=redirects
php artisan seo:clear-cache --type=sitemaps
php artisan seo:clear-cache --type=analysis
```

## Cache Drivers

### Using Redis (Recommended)

```php
'cache' => [
    'driver' => 'redis',
],
```

### Using File Cache

```php
'cache' => [
    'driver' => 'file',
],
```

### Using Database Cache

```php
'cache' => [
    'driver' => 'database',
],
```

### Using Array (Testing)

```php
'cache' => [
    'driver' => 'array',
],
```

## Cache Tags

If using a driver that supports tags (Redis, Memcached):

```php
// The package uses these tags internally
'seo:meta'
'seo:analysis'

// Clear by tag
Cache::tags('seo:meta')->flush();
Cache::tags('seo:analysis')->flush();
```

Redirect and sitemap caches don't use tags. They use generation tokens, which
work on every cache store, so clear them with their services instead (see
[Automatic Cache Invalidation](#automatic-cache-invalidation)).

## Warming the Cache

The package provides `seo:generate-sitemap` and `seo:submit-sitemap` commands, but cache warming is application-specific. Create a custom command to warm your SEO caches:

### Custom Warm Command

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Post;

class WarmSeoCache extends Command
{
    protected $signature = 'seo:warm-cache';

    public function handle(): void
    {
        // Warm meta cache
        Post::chunk(100, function ($posts) {
            foreach ($posts as $post) {
                $post->getSeoData(); // Triggers caching
            }
        });

        // Warm sitemap cache
        app('seo.sitemap')->generate();

        // Warm analysis cache
        Post::chunk(100, function ($posts) {
            foreach ($posts as $post) {
                app('seo.analysis')->analyze($post);
            }
        });

        $this->info('SEO cache warmed.');
    }
}
```

### On Deployment

After creating the custom command above, use it in deployment scripts:

```php
// In a deployment script or command
Artisan::call('seo:warm-cache');
```

## Disabling Caching

### Globally

```php
'cache' => [
    'enabled' => false,
],
```

### Per Feature

```php
'redirects' => [
    'cache_enabled' => false,
],

'sitemap' => [
    'cache_enabled' => false,
],
```

### Temporarily

To bypass caching for a single operation, call the data method directly or temporarily disable caching:

```php
// Option 1: Disable caching temporarily via config
config(['seo.cache.enabled' => false]);
$data = $post->getSeoData();
config(['seo.cache.enabled' => true]);

// Option 2: Clear cache before fetching fresh data
$cacheService->clearAllForModel($post);
$data = $post->getSeoData();
```

## Cache Performance Tips

### 1. Use Redis in Production

```php
'cache' => [
    'driver' => 'redis',
],
```

### 2. Set Appropriate TTLs

```php
// Frequently changing data
'redirects' => [
    'cache_ttl' => 3600, // 1 hour
],

// Rarely changing data
'sitemap' => [
    'cache_ttl' => 86400, // 24 hours
],
```

### 3. Warm Cache After Bulk Operations

```php
// After bulk import
Post::chunk(100, function ($posts) {
    foreach ($posts as $post) {
        $post->getSeoData();
    }
});
```

### 4. Clear Only What Changed

```php
// Clear only the redirect cache after a bulk change that skips RedirectService
seoRedirect()->clearCache();
```

## Monitoring Cache

### Cache Hit Rate

Monitor your cache hit rate to ensure effectiveness. Use Laravel's built-in cache debugging:

```php
// Use Laravel's Cache spy in tests
Cache::spy();

// Or add custom event listeners for cache hits/misses
Event::listen('cache.hit', fn ($key) => Log::debug("Cache hit: {$key}"));
Event::listen('cache.missed', fn ($key) => Log::debug("Cache miss: {$key}"));
```

### Cache Size

For Redis:

```bash
redis-cli info memory
```

## Events

```php
use ArtisanPackUI\Seo\Events\SeoCacheCleared;

Event::listen(SeoCacheCleared::class, function ($event) {
    Log::info('SEO cache cleared', [
        'model' => $event->model,
        'keys' => $event->keys,
    ]);
});
```

## Next Steps

- [Configuration](Installation-Configuration) - Full configuration reference
- [Artisan Commands](Artisan-Commands) - CLI tools
- [Performance Tips](Troubleshooting) - Troubleshooting
