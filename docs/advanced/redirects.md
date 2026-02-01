---
title: URL Redirects
---

# URL Redirects

ArtisanPack UI SEO provides comprehensive URL redirect management with support for exact matching, regular expressions, and wildcards.

## Overview

Redirects are essential for:
- Maintaining SEO equity when URLs change
- Handling legacy URLs after site migrations
- Creating short URLs or vanity URLs
- Fixing broken links

## Configuration

```php
// In config/seo.php
'redirects' => [
    'enabled' => true,
    'cache' => true,
    'cache_ttl' => 3600,
    'track_hits' => true,
    'max_chain_depth' => 5,
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable/disable redirect handling |
| `cache` | Cache redirect lookups |
| `cache_ttl` | Cache time-to-live in seconds |
| `track_hits` | Track redirect hit statistics |
| `max_chain_depth` | Maximum redirect chain depth |

## Middleware Setup

Add the redirect middleware to handle redirects automatically:

```php
// In bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \ArtisanPackUI\Seo\Http\Middleware\HandleRedirects::class,
    ]);
})
```

## Match Types

### Exact Match

Matches the exact source path:

```php
use ArtisanPackUI\Seo\Models\Redirect;

Redirect::create([
    'source' => '/old-page',
    'target' => '/new-page',
    'type' => 'exact',
    'status_code' => 301,
]);

// Matches: /old-page
// Does NOT match: /old-page/, /old-page?q=1, /old-page/sub
```

### Wildcard Match

Uses `*` (any characters) and `?` (single character) wildcards:

```php
// Match any path under /blog/
Redirect::create([
    'source' => '/blog/*',
    'target' => '/articles/$1',
    'type' => 'wildcard',
    'status_code' => 301,
]);

// /blog/my-post → /articles/my-post
// /blog/2024/01/post → /articles/2024/01/post
```

```php
// Match specific pattern
Redirect::create([
    'source' => '/product-???',
    'target' => '/products/$1',
    'type' => 'wildcard',
    'status_code' => 301,
]);

// /product-abc → /products/abc
// /product-123 → /products/123
```

### Regex Match

Uses full regular expression patterns:

```php
Redirect::create([
    'source' => '^/products/(\d+)$',
    'target' => '/items/$1',
    'type' => 'regex',
    'status_code' => 301,
]);

// /products/123 → /items/123
// /products/456 → /items/456
```

```php
// Case-insensitive matching
Redirect::create([
    'source' => '(?i)^/About$',
    'target' => '/about-us',
    'type' => 'regex',
    'status_code' => 301,
]);

// /About → /about-us
// /ABOUT → /about-us
// /about → /about-us
```

## HTTP Status Codes

| Code | Type | Use Case |
|------|------|----------|
| 301 | Permanent | URL permanently moved (SEO equity transfers) |
| 302 | Temporary | Temporary redirect (no SEO transfer) |
| 307 | Temporary | Temporary redirect, preserves method |
| 308 | Permanent | Permanent redirect, preserves method |

```php
// Permanent redirect (most common)
Redirect::create([
    'source' => '/old',
    'target' => '/new',
    'status_code' => 301,
]);

// Temporary redirect
Redirect::create([
    'source' => '/maintenance',
    'target' => '/temp-page',
    'status_code' => 302,
]);
```

## Programmatic Management

### Creating Redirects

```php
use ArtisanPackUI\Seo\Services\RedirectService;

$redirectService = app('seo.redirect');

$redirect = $redirectService->create([
    'source' => '/old-page',
    'target' => '/new-page',
    'type' => 'exact',
    'status_code' => 301,
    'is_active' => true,
    'notes' => 'Migration from old site',
]);
```

### Using Helper Functions

```php
// Create redirect
$redirect = seoCreateRedirect(
    source: '/old',
    target: '/new',
    type: 'exact',
    statusCode: 301
);

// Find redirect for path
$redirect = seoFindRedirect('/old-page');

// Delete redirect
seoDeleteRedirect($redirect->id);
```

### Updating Redirects

```php
$redirectService->update($redirect->id, [
    'target' => '/updated-page',
    'is_active' => true,
]);
```

### Testing Paths

```php
// Find which redirect matches a path
$redirect = $redirectService->findMatch('/some/path');

if ($redirect) {
    echo "Redirects to: " . $redirect->getDestination('/some/path');
}
```

## Redirect Chain Prevention

The package prevents infinite redirect loops:

```php
// config/seo.php
'redirects' => [
    'max_chain_depth' => 5,
],
```

When creating redirects, the system checks:
1. Target doesn't redirect back to source
2. Chain depth doesn't exceed maximum
3. No circular references

```php
// This would be detected as a loop
// /a → /b
// /b → /c
// /c → /a  ← Loop detected!

try {
    $redirectService->create([
        'source' => '/c',
        'target' => '/a',
    ]);
} catch (RedirectLoopException $e) {
    // Handle loop detection
}
```

## Hit Tracking

Track redirect usage for analytics:

```php
// Get redirect statistics
$stats = seoRedirectStatistics();

// Returns:
// [
//     'total' => 45,
//     'active' => 42,
//     'inactive' => 3,
//     'total_hits' => 12543,
//     'most_hit' => [/* top redirects */],
// ]
```

```php
// Query by hits
$topRedirects = Redirect::mostHits()->limit(10)->get();

$recentlyHit = Redirect::recentlyHit()->get();

$unused = Redirect::where('hits', 0)->get();
```

## Bulk Operations

### Import from CSV

```php
$redirectService->importFromCsv('/path/to/redirects.csv');

// CSV format:
// source,target,type,status_code,active
// /old-page,/new-page,exact,301,1
// /blog/*,/articles/$1,wildcard,301,1
```

### Export to CSV

```php
$csv = $redirectService->exportToCsv();
file_put_contents('redirects.csv', $csv);
```

### Bulk Create

```php
$redirects = [
    ['source' => '/page1', 'target' => '/new1'],
    ['source' => '/page2', 'target' => '/new2'],
    ['source' => '/page3', 'target' => '/new3'],
];

foreach ($redirects as $data) {
    $redirectService->create(array_merge($data, [
        'type' => 'exact',
        'status_code' => 301,
    ]));
}
```

## Security Considerations

### ReDoS Protection

Regex patterns are validated to prevent ReDoS attacks:

```php
// These patterns are rejected:
// /(a+)+$/
// /([a-zA-Z]+)*$/
// /(a|aa)+$/
```

### External URL Validation

By default, redirects to external URLs are allowed. To restrict:

```php
// In a custom validation rule
if (Str::startsWith($target, ['http://', 'https://'])) {
    $host = parse_url($target, PHP_URL_HOST);
    if (!in_array($host, config('seo.redirects.allowed_hosts'))) {
        throw new InvalidArgumentException('External redirects not allowed');
    }
}
```

## Admin Interface

Use the Livewire Redirect Manager component:

```blade
<livewire:redirect-manager />
```

Features:
- Create, edit, delete redirects
- Filter by type, status, hits
- Test paths
- View statistics
- Bulk actions

[See Redirect Manager Component →](Components-Redirect-Manager)

## Events

Listen for redirect events:

```php
use ArtisanPackUI\Seo\Events\RedirectCreated;
use ArtisanPackUI\Seo\Events\RedirectHit;

Event::listen(RedirectCreated::class, function ($event) {
    Log::info("Redirect created: {$event->redirect->source}");
});

Event::listen(RedirectHit::class, function ($event) {
    Analytics::track('redirect', [
        'from' => $event->sourcePath,
        'to' => $event->targetUrl,
    ]);
});
```

## Next Steps

- [Redirect Manager Component](Components-Redirect-Manager) - Admin UI
- [XML Sitemaps](Sitemaps) - Sitemap generation
- [Events](Api-Events) - Event reference
