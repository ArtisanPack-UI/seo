---
title: Upgrading to 1.4.0
---

# Upgrading to 1.4.0

This guide covers upgrading from v1.3.x to v1.4.0. This release adds AI-first
discovery surfaces (llms.txt, IndexNow, AI-crawler robots controls,
RSS/Atom feeds, OG image generator), promotes the AI Feature Suite to
require `artisanpack-ui/ai: ^1.2`, and hardens the schema graph, feed
generator, and analyzers uncovered by review of the prior betas.

## Summary

- **New**: `llms.txt` AI-discovery manifest generator (#68).
- **New**: IndexNow submitter + sitemap-ping path (#69).
- **New**: Explicit AI-crawler robots controls, including a working
  `default_allow` kill-switch (#70, hardened here).
- **New**: Focus-keyword + first-paragraph AI-readiness analyzers (#71).
- **New**: RSS 2.0 and Atom 1.0 feed generator (#72).
- **New**: Organization `sameAs` + `logo` (with width/height support) on the
  schema graph (#73).
- **New**: OG-image generator service — MightyShare-style social cards
  rendered via GD, deterministically cached, alpha-preserving (#74).
- **New**: LocalBusiness dated / holiday `OpeningHoursSpecification`
  support (#75).
- **New**: `apSeoAddSchema()` helper + `SchemaCollector` service (#77).
- **New**: `SchemaService` render-pipeline centralization (#78).
- **New**: Every graph node emits a stable `@id`; `Article.publisher`
  now references the Organization by `@id` so Search Console links the
  entities across the graph.
- **New**: Route toggles for `llms.txt`, `/feed.xml`, `/feed.atom`, and
  the IndexNow key file (`seo.llms_txt.route_enabled`,
  `seo.feeds.route_enabled`, `seo.indexnow.route_enabled`).
- **New**: `seo.feeds` config block (title, description, per_page,
  cache_ttl, `feed_id` for Atom).
- **Changed**: `artisanpack-ui/ai` constraint bumped to `^1.2`. The SEO
  AI Livewire components now compose the `ChecksFeatureToggle` and
  `InteractsWithAiFeature` traits (#76 / #92). Downstream components
  that extend the SEO AI components must not redeclare
  `public bool $isLoading` or `public ?string $error` — the trait
  declares them and hydration throws a `TypeError` on type conflicts.
  See "Breaking (soft)" below.
- **Fixed**: `default_allow=false` on the AI-crawler controls now
  actually blocks every group unless `blocked` is explicitly false;
  previously a silent no-op.
- **Fixed**: RSS/Atom output now strips XML 1.0 forbidden control
  characters and rejects `javascript:` / `data:` link schemes so feed
  validators no longer choke and feed readers no longer render
  stored-XSS URLs.
- **Fixed**: `LlmsTxtGenerator` collapses newlines in titles and
  escapes `[` / `]` in descriptions so a Markdown list item can no
  longer shatter mid-line.
- **Fixed**: IndexNow surfaces per-URL rejections carried in an
  otherwise-200 response body (e.g. `UnverifiedHost`) as `success=false`.
- **Fixed**: `FocusKeywordAnalyzer` and `AiReadinessAnalyzer` now
  handle non-ASCII text correctly (multibyte case folding, non-Latin
  word counting).

## Update the Package

```bash
composer update artisanpack-ui/seo
```

If you use the AI Feature Suite, ensure your project is on PHP 8.3+
and Laravel 12+ before upgrading:

```bash
composer require artisanpack-ui/ai:^1.2 --dev
```

## Breaking (soft): AI Livewire trait refactor

`ChecksFeatureToggle` + `InteractsWithAiFeature` now supply the
`public bool $isLoading` and `public ?string $error` properties on
every SEO AI Livewire component (Meta Title Assistant, Meta Description
Assistant, Content Analysis, Schema Generation, Hreflang Suggester).

If you extended one of these components and **redeclared either
property with a different type** (for example `public string $error = ''`),
Livewire's hydration will now throw a `TypeError`. Remove the
redeclared property from your subclass — the trait already provides it
with the correct type.

## New Config Keys

Add these blocks to your published `config/seo.php` if you want to opt
into the new surfaces (or leave them at their defaults):

- `seo.llms_txt.route_enabled` (default `false`) — serve
  `/llms.txt` from the package.
- `seo.llms_txt.route_path` (default `llms.txt`).
- `seo.feeds` — full block (`enabled`, `route_enabled`, `rss_path`,
  `atom_path`, `title`, `description`, `per_page`, `cache_ttl`,
  `feed_id`). Bind a `FeedProviderContract` implementation to the
  container to supply entries.
- `seo.indexnow.route_enabled` (default `false`) — serve the
  `/{key}.txt` key-verification file when the filename matches the
  bound key provider.
- Expanded `seo.robots.ai_crawlers` (added in this line): the
  `default_allow` flag is now a real kill switch (see the fix note
  above), so if you flip it to `false` every group without an explicit
  `blocked => false` will receive a `Disallow: /` directive.

## Route Wiring

The three new surfaces below register **only when their config toggle
is true** — opt-in so that consumers who serve them via a custom
controller are not double-registered:

- `GET /llms.txt` — `LlmsTxtController@index`
- `GET /feed.xml` — `FeedController@rss`
- `GET /feed.atom` — `FeedController@atom`
- `GET /{key}.txt` — `IndexNowKeyController@show`

`FeedController` and `IndexNowKeyController` both return 404 when no
provider (`FeedProviderContract`, `IndexNowKeyProviderContract`) is
bound in the container.

OG images resolve through the storage disk configured in
`seo.og_image.disk` (default `public`) via `Storage::url()` — no route
is registered by the package.

## No Migration Required

No new database tables, columns, or indexes; no existing model
signatures have changed.

## New Filter Hooks

- `ap.seo.feedEntries($entries, $feedType)` — mutate the entry list
  before RSS/Atom output.
- `ap.seo.llmsTxtEntries($entries)` — mutate the entry list before the
  llms.txt manifest is rendered.

Both use the array-of-arrays convention: return whatever the callback
accepts, filtered or extended.
