---
title: llms.txt
---

# llms.txt

The package generates a
[`llms.txt`](https://llmstxt.org/) AI-discovery manifest from the same
indexable `SitemapEntry` source as the XML sitemap, plus URLs from
registered [sitemap providers](Advanced-Sitemaps#sitemap-providers).
When you regenerate sitemap entries the manifest is refreshed alongside
them.

## Enable the route

Route registration is opt-in — set `seo.llms_txt.route_enabled` to
`true` in `config/seo.php` (or via `SEO_LLMS_TXT_ROUTE_ENABLED=true`)
so the package serves `/llms.txt`:

```php
'llms_txt' => [
    'enabled'       => true,
    'route_enabled' => env( 'SEO_LLMS_TXT_ROUTE_ENABLED', true ),
    'route_path'    => 'llms.txt',
    'title'         => null,        // falls back to seo.site.name
    'summary'       => null,        // falls back to seo.site.description, then the homepage
    'intro'         => null,        // optional intro paragraph
    'include_types' => [],          // restrict entry types (empty = all)
    'exclude_types' => [],          // skip entry types
    'max_entries'   => null,        // soft cap
],
```

If you serve it from a custom controller instead, leave
`route_enabled` at `false` and call `SitemapService::generateLlmsTxt()`
yourself.

## Site summary

The manifest opens with a blockquote explaining the site. It uses the
first of these that has text, collapsed to one line:

1. `seo.llms_txt.summary`
2. `seo.site.description`
3. The description of the homepage's sitemap entry, whose URL must match
   `app.url`

With none of these, the blockquote is left out.

## Provider URLs

Provider URLs are listed under their registered type, alongside any
stored entries of that type. Providers can add `title` and `description`
to each URL; a title otherwise comes from the URL.

```php
public function getUrls(): Collection
{
    return collect( [
        [
            'loc'         => url( '/blog' ),
            'title'       => 'Blog',
            'description' => 'All of our articles.',
        ],
    ] );
}
```

A URL with a stored sitemap entry is skipped, so a provider can't list a
page twice or bring back a non-indexable one. `include_types`,
`exclude_types` and `max_entries` apply to provider URLs too.

## Programmatic access

```php
use ArtisanPackUI\SEO\Services\SitemapService;

$content = app( SitemapService::class )->generateLlmsTxt();
```

## Filter the entries

Use the `ap.seo.llmsTxtEntries` filter to add, drop, or reorder
entries before rendering. Entries are `SitemapEntry` models or arrays,
and provider URLs arrive as arrays, so handle both:

```php
addFilter( 'ap.seo.llmsTxtEntries', function ( array $entries ): array {
    $entries[] = [
        'url'         => 'https://example.com/manual',
        'type'        => 'page',
        'title'       => 'Manual',
        'description' => 'The full product manual.',
    ];

    return $entries;
} );
```

## Escaping notes

Titles that contain a `\n` are collapsed to a single space so a
Markdown list item can never shatter across lines. `[` and `]` in
descriptions are escaped so a description like `see [here]` renders as
`see \[here\]` instead of a broken Markdown link.
