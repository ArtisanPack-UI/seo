---
title: RSS and Atom Feeds
---

# RSS and Atom Feeds

The package can render RSS 2.0 and Atom 1.0 feeds from any
consumer-supplied entry source (an Eloquent archive, a per-type
collection, a provider class, or plain arrays). Character escaping,
date encoding, and XML-1.0 control-char sanitization are handled for
you.

## Configuration

```php
'feeds' => [
    'enabled'       => env( 'SEO_FEEDS_ENABLED', true ),
    'route_enabled' => env( 'SEO_FEEDS_ROUTE_ENABLED', false ),
    'rss_path'      => 'feed.xml',
    'atom_path'     => 'feed.atom',
    'title'         => env( 'SEO_FEEDS_TITLE', env( 'APP_NAME', 'Site' ) ),
    'description'   => '',
    'per_page'      => 50,
    'cache_ttl'     => 300,
    'feed_id'       => env( 'SEO_FEEDS_FEED_ID' ), // stable IRI for Atom <id>
],
```

## Route registration

With `route_enabled=true` the package wires:

- `GET /feed.xml` → RSS 2.0
- `GET /feed.atom` → Atom 1.0

Both routes resolve a `FeedProviderContract` implementation from the
container. Bind your own:

```php
use ArtisanPackUI\SEO\Contracts\FeedProviderContract;

class BlogFeedProvider implements FeedProviderContract
{
    public function getTitle(): string       { return 'Acme Blog'; }
    public function getLink(): string        { return url( '/blog' ); }
    public function getFeedUrl(): string     { return url( '/feed.xml' ); }
    public function getDescription(): string { return 'Latest posts from Acme.'; }

    public function getEntries(): \Illuminate\Support\Collection
    {
        return Post::published()->latest()->take( 50 )->get()
            ->map( fn ( $post ) => new \ArtisanPackUI\SEO\DTOs\FeedEntryDTO(
                title: $post->title,
                link:  $post->url,
                summary: $post->summary,
                publishedAt: $post->published_at,
                author: $post->author?->name,
                categories: $post->tags->pluck( 'name' )->all(),
            ) );
    }
}

// AppServiceProvider::register
$this->app->bind( FeedProviderContract::class, BlogFeedProvider::class );
```

If no provider is bound, both routes return 404.

## Programmatic generation

```php
use ArtisanPackUI\SEO\Feed\Generators\FeedGenerator;

$xml = ( new FeedGenerator() )->generateRss(
    'Acme Blog',
    'https://example.com/blog',
    'Latest posts',
    $entries,
    [ 'feed_url' => 'https://example.com/feed.xml' ],
);
```

## Safety guarantees

- **XML control chars**: every consumer-supplied string is stripped
  of the XML 1.0 forbidden range (`\x00-\x08\x0B\x0C\x0E-\x1F`)
  before write, so a stray form-feed pasted from Word can no longer
  invalidate the feed.
- **Link scheme**: entries whose `link` is not `http`/`https` are
  dropped with a `Log::warning`; older feed readers no longer render
  `javascript:` or `data:` links.
- **CDATA terminator**: `]]>` inside summaries is split across
  adjacent CDATA sections so hostile payloads can never break out.
- **Atom `<id>`**: reads `seo.feeds.feed_id`. When unset, the
  generator falls back to the feed URL and logs a `Log::notice` so
  you can upgrade to a stable `tag:` IRI when you're ready.

## Filter the entries

The `ap.seo.feedEntries` filter fires before rendering, giving you a
last chance to mutate the list per feed type:

```php
addFilter( 'ap.seo.feedEntries', function ( array $entries, string $feedType ): array {
    return array_slice( $entries, 0, 20 );
} );
```
