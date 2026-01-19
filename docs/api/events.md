---
title: Events
---

# Events

ArtisanPack UI SEO dispatches events throughout its lifecycle, allowing you to hook into SEO operations.

## Available Events

### SeoMetaCreated

Dispatched when SEO meta is created for a model.

```php
use ArtisanPackUI\Seo\Events\SeoMetaCreated;

// Event properties
$event->seoMeta;  // The SeoMeta model
$event->model;    // The parent model
```

### SeoMetaUpdated

Dispatched when SEO meta is updated.

```php
use ArtisanPackUI\Seo\Events\SeoMetaUpdated;

// Event properties
$event->seoMeta;  // The SeoMeta model
$event->model;    // The parent model
$event->changes;  // Array of changed fields
```

### SeoMetaDeleted

Dispatched when SEO meta is deleted.

```php
use ArtisanPackUI\Seo\Events\SeoMetaDeleted;

// Event properties
$event->model;    // The parent model
$event->data;     // The deleted meta data
```

### RedirectCreated

Dispatched when a redirect is created.

```php
use ArtisanPackUI\Seo\Events\RedirectCreated;

// Event properties
$event->redirect; // The Redirect model
```

### RedirectUpdated

Dispatched when a redirect is updated.

```php
use ArtisanPackUI\Seo\Events\RedirectUpdated;

// Event properties
$event->redirect; // The Redirect model
$event->changes;  // Array of changed fields
```

### RedirectDeleted

Dispatched when a redirect is deleted.

```php
use ArtisanPackUI\Seo\Events\RedirectDeleted;

// Event properties
$event->data;     // The deleted redirect data
```

### RedirectHit

Dispatched when a redirect is triggered.

```php
use ArtisanPackUI\Seo\Events\RedirectHit;

// Event properties
$event->redirect;    // The Redirect model
$event->sourcePath;  // The requested path
$event->targetUrl;   // The destination URL
$event->request;     // The HTTP request
```

### SitemapGenerated

Dispatched when sitemaps are generated.

```php
use ArtisanPackUI\Seo\Events\SitemapGenerated;

// Event properties
$event->type;     // Sitemap type (standard, images, etc.)
$event->count;    // Number of URLs
$event->path;     // File path (if saved)
```

### SitemapSubmitted

Dispatched when sitemap is submitted to search engines.

```php
use ArtisanPackUI\Seo\Events\SitemapSubmitted;

// Event properties
$event->engine;   // Search engine name
$event->url;      // Sitemap URL
$event->success;  // Boolean success status
$event->response; // Response data
```

### SeoAnalysisCompleted

Dispatched when SEO analysis is completed.

```php
use ArtisanPackUI\Seo\Events\SeoAnalysisCompleted;

// Event properties
$event->model;    // The analyzed model
$event->results;  // Analysis results array
$event->score;    // Overall score
```

### SeoCacheCleared

Dispatched when SEO cache is cleared.

```php
use ArtisanPackUI\Seo\Events\SeoCacheCleared;

// Event properties
$event->model;    // The model (or null for all)
$event->keys;     // Cleared cache keys
```

## Listening to Events

### Using Event Listeners

Create a listener class:

```php
namespace App\Listeners;

use ArtisanPackUI\Seo\Events\SeoMetaUpdated;

class HandleSeoMetaUpdated
{
    public function handle(SeoMetaUpdated $event): void
    {
        // Clear external cache
        Cache::tags('seo')->flush();

        // Log the change
        Log::info('SEO meta updated', [
            'model' => get_class($event->model),
            'id' => $event->model->id,
            'changes' => $event->changes,
        ]);

        // Notify admin
        if (in_array('noindex', array_keys($event->changes))) {
            // Send notification about indexing change
        }
    }
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    \ArtisanPackUI\Seo\Events\SeoMetaUpdated::class => [
        \App\Listeners\HandleSeoMetaUpdated::class,
    ],
    \ArtisanPackUI\Seo\Events\RedirectHit::class => [
        \App\Listeners\LogRedirectHit::class,
    ],
    \ArtisanPackUI\Seo\Events\SitemapGenerated::class => [
        \App\Listeners\NotifySitemapGenerated::class,
    ],
];
```

### Using Closures

```php
use Illuminate\Support\Facades\Event;
use ArtisanPackUI\Seo\Events\SeoMetaUpdated;

Event::listen(SeoMetaUpdated::class, function ($event) {
    // Handle the event
});
```

### Using Subscribers

```php
namespace App\Listeners;

use ArtisanPackUI\Seo\Events\SeoMetaCreated;
use ArtisanPackUI\Seo\Events\SeoMetaUpdated;
use ArtisanPackUI\Seo\Events\SeoMetaDeleted;
use Illuminate\Events\Dispatcher;

class SeoEventSubscriber
{
    public function handleCreated(SeoMetaCreated $event): void
    {
        // Handle creation
    }

    public function handleUpdated(SeoMetaUpdated $event): void
    {
        // Handle update
    }

    public function handleDeleted(SeoMetaDeleted $event): void
    {
        // Handle deletion
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            SeoMetaCreated::class => 'handleCreated',
            SeoMetaUpdated::class => 'handleUpdated',
            SeoMetaDeleted::class => 'handleDeleted',
        ];
    }
}
```

Register the subscriber:

```php
protected $subscribe = [
    \App\Listeners\SeoEventSubscriber::class,
];
```

## Common Use Cases

### Clearing External Caches

```php
class ClearExternalCache
{
    public function handle(SeoMetaUpdated $event): void
    {
        // Clear CDN cache for the URL
        $url = $event->model->url;
        Http::delete("https://cdn.example.com/purge?url={$url}");

        // Clear Varnish
        Http::request('PURGE', $url);
    }
}
```

### Logging Redirect Analytics

```php
class LogRedirectAnalytics
{
    public function handle(RedirectHit $event): void
    {
        // Log to analytics service
        Analytics::track('redirect_hit', [
            'source' => $event->sourcePath,
            'target' => $event->targetUrl,
            'user_agent' => $event->request->userAgent(),
            'ip' => $event->request->ip(),
            'referer' => $event->request->header('referer'),
        ]);
    }
}
```

### Auto-Submitting Sitemap

```php
class AutoSubmitSitemap
{
    public function handle(SitemapGenerated $event): void
    {
        if ($event->type === 'standard') {
            // Auto-submit to search engines
            seoSubmitSitemap();
        }
    }
}
```

### Sending Notifications

```php
class NotifySeoChanges
{
    public function handle(SeoMetaUpdated $event): void
    {
        // Check for significant changes
        $significantFields = ['noindex', 'nofollow', 'canonical_url'];
        $hasSignificantChange = !empty(
            array_intersect($significantFields, array_keys($event->changes))
        );

        if ($hasSignificantChange) {
            $admin = User::where('is_admin', true)->first();
            $admin->notify(new SeoChangeNotification($event));
        }
    }
}
```

### Syncing with External Services

```php
class SyncSeoWithExternalService
{
    public function handle(SeoMetaUpdated $event): void
    {
        // Sync with headless CMS
        Http::put("https://cms.example.com/api/seo/{$event->model->id}", [
            'title' => $event->seoMeta->meta_title,
            'description' => $event->seoMeta->meta_description,
            'og_image' => $event->seoMeta->og_image,
        ]);
    }
}
```

## Queueing Event Listeners

For heavy operations, queue your listeners:

```php
class ProcessSeoAnalysis implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(SeoMetaUpdated $event): void
    {
        // Heavy processing runs in background
        seoAnalyze($event->model);
    }
}
```

## Disabling Events

Temporarily disable events for bulk operations:

```php
use ArtisanPackUI\Seo\Models\SeoMeta;

// Disable events
SeoMeta::withoutEvents(function () {
    // Bulk operations without triggering events
    Post::chunk(100, function ($posts) {
        foreach ($posts as $post) {
            $post->updateSeoMeta([...]);
        }
    });
});
```

## Next Steps

- [Services](./services.md) - Service documentation
- [Helper Functions](./helpers.md) - Helper reference
- [Models](./models.md) - Model reference
