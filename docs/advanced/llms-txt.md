---
title: llms.txt
---

# llms.txt

The package generates a
[`llms.txt`](https://llmstxt.org/) AI-discovery manifest from the same
indexable `SitemapEntry` source as the XML sitemap. When you regenerate
sitemap entries the manifest is refreshed alongside them.

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
    'summary'       => null,        // falls back to seo.site.description
    'intro'         => null,        // optional intro paragraph
    'include_types' => [],          // restrict entry types (empty = all)
    'exclude_types' => [],          // skip entry types
    'max_entries'   => null,        // soft cap
],
```

If you serve it from a custom controller instead, leave
`route_enabled` at `false` and call `SitemapService::generateLlmsTxt()`
yourself.

## Programmatic access

```php
use ArtisanPackUI\SEO\Services\SitemapService;

$content = app( SitemapService::class )->generateLlmsTxt();
```

## Filter the entries

Use the `ap.seo.llmsTxtEntries` filter to add, drop, or reorder
entries before rendering:

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
