---
title: Troubleshooting
---

# Troubleshooting

Solutions to common issues with ArtisanPack UI SEO.

## Installation Issues

### Migration fails with "table already exists"

If you're migrating from another SEO package:

```bash
# Check existing tables
php artisan tinker
>>> Schema::hasTable('seo_meta')

# If the table exists, either:
# 1. Drop and recreate (loses data)
php artisan migrate:fresh

# 2. Or skip specific migrations
# Add to migrations/seo tables to ignore list
```

### Service provider not found

If auto-discovery isn't working:

```php
// Add to config/app.php
'providers' => [
    ArtisanPackUI\Seo\SeoServiceProvider::class,
],
```

### Composer dependency conflict

```bash
# Clear composer cache
composer clear-cache

# Update with verbose output
composer update artisanpack-ui/seo -vvv
```

## Meta Tags Not Showing

### Tags missing from page

1. **Check component is in `<head>`**:
```blade
<head>
    <x-seo-meta :model="$model" />
</head>
```

2. **Verify model is passed to view**:
```php
return view('page', ['model' => $post]);
```

3. **Check model has HasSeo trait**:
```php
class Post extends Model
{
    use HasSeo;
}
```

### Fallback values not working

Ensure your model has the expected attributes:

```php
// For title fallback, model needs one of:
$post->title
$post->name

// For description fallback:
$post->description
$post->excerpt
$post->content
```

Or override the methods:

```php
protected function getSeoTitleAttribute(): ?string
{
    return $this->headline;
}
```

### Title showing twice

Check you're not manually adding a `<title>` tag alongside the component:

```blade
{{-- Remove this if using component --}}
<title>{{ $title }}</title>

{{-- Component includes <title> tag --}}
<x-seo-meta :model="$model" />
```

## Social Sharing Issues

### Facebook not showing correct image

1. **Verify image is accessible**: Visit the URL directly
2. **Check image size**: Minimum 1200x630 pixels
3. **Use absolute URL**: `https://example.com/image.jpg`
4. **Clear Facebook cache**: Use [Sharing Debugger](https://developers.facebook.com/tools/debug/)

```bash
# Test image URL
curl -I https://example.com/your-image.jpg
```

### Twitter Card not appearing

1. **Validate card**: Use [Twitter Card Validator](https://cards-dev.twitter.com/validator)
2. **Check card type is valid**: `summary`, `summary_large_image`, `app`, `player`
3. **Ensure all required tags are present**

### LinkedIn showing old data

LinkedIn caches aggressively. Use the [Post Inspector](https://www.linkedin.com/post-inspector/) to clear cache.

## Redirect Issues

### Redirects not triggering

1. **Check middleware is registered**:
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \ArtisanPackUI\Seo\Http\Middleware\HandleRedirects::class,
    ]);
})
```

2. **Verify redirect is active**:
```php
$redirect = Redirect::where('source', '/old-path')->first();
dd($redirect->is_active); // Should be true
```

3. **Check match type**:
```bash
php artisan seo:test-redirect /your-path
```

### Regex redirect not matching

1. **Don't include delimiters**: Use `^/path/(\d+)$` not `/^\/path\/(\d+)$/`
2. **Escape special characters**: `/` doesn't need escaping
3. **Test pattern**:
```php
preg_match('/^\/path\/(\d+)$/', '/path/123', $matches);
dd($matches);
```

### Redirect loop detected

The package prevents loops automatically. If you see this error:

1. Check for circular redirects
2. Verify chain depth doesn't exceed `max_chain_depth` (default: 5)
3. Review redirect targets

## Sitemap Issues

### Sitemap returns 404

1. **Check routes are enabled**:
```php
// config/seo.php
'routes' => [
    'sitemap' => true,
],
```

2. **Or add custom route**:
```php
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
```

### Sitemap is empty

1. **Check providers are registered**
2. **Verify models are not excluded**:
```php
$post->shouldBeInSitemap(); // Should return true
```

3. **Check for noindex**:
```php
$post->seoMeta->noindex; // Should be false
$post->seoMeta->exclude_from_sitemap; // Should be false
```

### Sitemap URLs are wrong

1. **Check APP_URL in .env**:
```env
APP_URL=https://example.com
```

2. **Verify model URL generation**:
```php
$post->getSeoUrl();
```

## Analysis Issues

### Analysis not running

1. **Check analysis is enabled**:
```php
// config/seo.php
'analysis' => [
    'enabled' => true,
],
```

2. **Verify focus keyword is set**:
```php
$post->seoMeta->focus_keyword;
```

### Analysis queue not processing

```bash
# Check queue is running
php artisan queue:work

# Check failed jobs
php artisan queue:failed
```

## Cache Issues

### Changes not reflecting

```bash
# Clear SEO cache
php artisan seo:clear-cache

# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear
```

### Cache errors with Redis

```bash
# Check Redis connection
redis-cli ping

# Check Laravel can connect
php artisan tinker
>>> Cache::put('test', 'value', 60)
>>> Cache::get('test')
```

## Livewire Component Issues

### Component not loading

1. **Check Livewire is installed**:
```bash
composer show livewire/livewire
```

2. **Verify scripts are included**:
```blade
@livewireStyles
@livewireScripts
```

### Save not working

1. **Check browser console for errors**
2. **Verify CSRF token**:
```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

3. **Check Livewire endpoint is accessible**:
```bash
curl -X POST https://example.com/livewire/message/seo-meta-editor
```

### Preview not updating

The preview updates on wire:model changes. Ensure you're using:

```blade
wire:model.live="field"
```

## Database Issues

### Column not found error

Run migrations:

```bash
php artisan migrate
```

If migrations are out of sync:

```bash
php artisan migrate:status
```

### Data type mismatch

Check your model casts:

```php
protected $casts = [
    'schema_data' => 'array',
    'secondary_keywords' => 'array',
    'hreflang' => 'array',
];
```

## Performance Issues

### Pages loading slowly

1. **Enable caching**:
```php
'cache' => [
    'enabled' => true,
    'driver' => 'redis',
],
```

2. **Warm cache after deployments**:
```bash
php artisan seo:warm-cache
```

3. **Reduce query calls**:
```php
// Eager load SEO meta
$posts = Post::with('seoMeta')->get();
```

### Analysis taking too long

1. **Queue analysis**:
```php
'analysis' => [
    'queue' => true,
],
```

2. **Reduce analyzers**:
```php
'analyzers' => [
    'readability' => ['enabled' => false],
],
```

## Error Messages

### "Model does not use HasSeo trait"

Add the trait to your model:

```php
use ArtisanPackUI\Seo\Traits\HasSeo;

class Post extends Model
{
    use HasSeo;
}
```

### "Invalid schema type"

Check the schema type is supported:

```php
// Supported types
Article, BlogPosting, Product, Event, Organization,
LocalBusiness, WebSite, WebPage, Service, Review,
AggregateRating, BreadcrumbList, FAQPage
```

### "Redirect loop detected"

Review your redirects for circular references:

```bash
php artisan seo:test-redirect /path
```

## Getting More Help

### Enable Debug Mode

```php
// config/seo.php
'debug' => true,
```

### Check Logs

```bash
php artisan pail
# Or
tail -f storage/logs/laravel.log
```

### Report Issues

If you've found a bug:

1. Search existing issues first
2. Include Laravel and package versions
3. Provide reproduction steps
4. Include relevant config and code

[Report an Issue](https://gitlab.com/jacob-martella-web-design/artisanpack-ui/seo/-/issues)
