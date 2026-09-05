---
title: IndexNow
---

# IndexNow

[IndexNow](https://www.indexnow.org/) is a protocol that lets you push
URL-change notifications to Bing, Yandex, Seznam, Naver, and other
participating engines. The package ships a submitter that batches and
posts URLs on your behalf.

## Configuration

```php
'indexnow' => [
    'enabled'       => env( 'SEO_INDEXNOW_ENABLED', false ),
    'key'           => env( 'SEO_INDEXNOW_KEY' ),
    'key_location'  => env( 'SEO_INDEXNOW_KEY_LOCATION' ),
    'endpoint'      => env( 'SEO_INDEXNOW_ENDPOINT', 'https://api.indexnow.org/IndexNow' ),
    'batch_size'    => 10000,
    'timeout'       => 10,
    'user_agent'    => 'ArtisanPackUI SEO IndexNow Submitter',
    'route_enabled' => env( 'SEO_INDEXNOW_ROUTE_ENABLED', false ),
],
```

The key must be a hex string 8–128 characters long. Host it at
`https://{your-host}/{key}.txt` — or set
`seo.indexnow.route_enabled=true` to have the package serve it for you
from `/{key}.txt` via a bound `IndexNowKeyProviderContract`.

## Submitting URLs

```php
use ArtisanPackUI\SEO\IndexNow\IndexNowSubmitter;
use ArtisanPackUI\SEO\IndexNow\ConfigIndexNowKeyProvider;

$results = ( new IndexNowSubmitter( new ConfigIndexNowKeyProvider() ) )
    ->submit( [
        'https://example.com/blog/hello-world',
        'https://example.com/blog/updated-post',
    ] );

foreach ( $results as $result ) {
    if ( ! $result['success'] ) {
        Log::warning( 'IndexNow rejection', $result );
    }
}
```

Wrap the call in a queued job dispatched from your publish observer
so slow requests never block the write.

## Partial-failure handling

IndexNow endpoints return HTTP 200 for the batch even when individual
URLs are rejected (e.g. `UnverifiedHost`, `InvalidUrl`). Since 1.4.0
the submitter parses the response body and marks the result as
`success=false` with a `warning` string when either a `code` or a
`warnings` array is present. Callers reading `$result['success']` no
longer treat quiet failures as indexed.
