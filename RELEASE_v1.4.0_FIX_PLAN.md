# artisanpack-ui/seo v1.4.0 — Ship-Readiness Fix Plan

**Target branch:** `release/1.4`
**Repo:** `github.com/ArtisanPack-UI/seo`
**Baseline:** 1505 Pest tests passing, php-cs-fixer clean, phpcs clean of real errors. This document is the complete punch list to bring the branch to a shippable state.

Work top-to-bottom. Each fix has: file path, exact defect, the change to make, and a verification step. When every P0/P1 item is checked, follow the release procedure at the bottom.

---

## Rules of engagement (READ FIRST)

1. **Do NOT run `vendor/bin/pint`** on this package. It fights `php-cs-fixer`'s WordPress spacing. This repo uses `./vendor/bin/php-cs-fixer fix` + `./vendor/bin/phpcs` only.
2. Delete `.php-cs-fixer.cache` before every `php-cs-fixer` run in this branch; a stale cache silently skips files.
3. Cut a working branch off `release/1.4` for this pass: `git checkout -b chore/v1.4.0-release-hardening`.
4. Every code fix needs a Pest test. Do not delete existing tests.
5. Every code fix that changes user-visible behavior needs a CHANGELOG entry AND an entry in the new `docs/upgrade-1.4.0.md`.
6. Commit in logical groups (see "Suggested commit sequence" at end). Push and open a PR into `release/1.4` — do not commit directly to `release/1.4`.

---

## P0 — Blockers (must fix before tag)

### P0-1. Security: `squizlabs/php_codesniffer` HIGH CVE (CVE-2026-67434)

Installed 3.13.5 has an OS-command-injection advisory; fixed in ≥3.13.6.

**Fix**
```bash
composer update squizlabs/php_codesniffer --with-all-dependencies
composer audit
```
`composer.json` doesn't need editing (the direct constraint on `dealerdirect/phpcodesniffer-composer-installer` pulls it transitively).

**Verify:** `composer audit` reports 0 advisories for `php_codesniffer`.

---

### P0-2. Security: `livewire/livewire` DOM-XSS MEDIUM CVE (CVE-2026-81887)

Installed 3.8.1 is affected. Fix range: `>3.8.2` on the 3.x line.

**Fix**
- Update `composer.json` require-dev: `"livewire/livewire": "^3.6.4 <3.9"` → `"livewire/livewire": "^3.9"` (or the lowest patched 3.x).
- Update the `suggest` block in `composer.json` to match the new lower bound.
- Run `composer update livewire/livewire`.

**Verify:** `composer audit` reports 0 livewire advisories. Full Pest suite still green.

---

### P0-3. Correctness: `seo.robots.ai_crawlers.default_allow=false` is a silent no-op

**File:** `src/Services/RobotsService.php` (around `loadAiCrawlerRules()`, ~line 368–375)
**Also touch:** `src/Services/AiCrawlerService.php` (see `getResolvedRules()`)

**Defect.** `default_allow=false` is documented as "block every AI crawler group unless explicitly allowed". The generator only emits `Disallow: /` when a group's `blocked=true` flag is set, so `default_allow=false` produces the same robots.txt as `default_allow=true`. The kill switch does not work.

**Fix.** In `loadAiCrawlerRules()`:
- Read `AiCrawlerService::defaultAllow()`.
- When `defaultAllow() === false`, iterate every AI-crawler group in config and emit a full `Disallow: /` block for each user-agent unless the group's `blocked` flag is `explicitly` false.
- When `defaultAllow() === true`, keep current behavior (opt-in blocks via `blocked=true`).

**Test.** Add cases to `tests/Unit/Services/AiCrawlerServiceTest.php` (and/or `RobotsServiceTest.php`):
- `default_allow=false` + all defaults → every configured group appears with `Disallow: /`.
- `default_allow=false` + one group with `blocked=false` explicitly → that group is NOT disallowed.
- `default_allow=true` + one group with `blocked=true` → only that group disallowed (existing case; keep).

---

### P0-4. Correctness: RSS/Atom feeds emit XML-1.0-forbidden control characters

**File:** `src/Feed/Generators/FeedGenerator.php` (writeElement / writeCdata sites around lines 306, 310, 355, 389, plus `escapeCdata()`)

**Defect.** `escapeCdata()` handles `]]>` but not the XML 1.0 forbidden control-char range (`\x00-\x08\x0B\x0C\x0E-\x1F`). A stray form-feed pasted from Word or invalid UTF-8 leftover corrupts the entire feed — feed validators (W3C, Miniflux, Feedly) reject with a fatal parse error.

**Fix.** Add a private helper:
```php
private function sanitizeXmlText( string $value ): string {
    return preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value ) ?? '';
}
```
Apply it to every consumer-supplied string (title, summary, content, author, category) BEFORE `writeElement`/`writeCdata`. Do the same in `escapeCdata()`.

**Test.** Add to `tests/Unit/Feed/Generators/FeedGeneratorTest.php`:
- Entry title containing `"\x00\x0B\x0Chello"` → output must contain `hello`, no control chars, and pass `simplexml_load_string()`.
- Entry with `]]>` inside CDATA — keep existing coverage passing.

---

### P0-5. Correctness: Feed `<link>` accepts `javascript:` / `data:` schemes (stored XSS via feed)

**File:** `src/Feed/Generators/FeedGenerator.php` (link write sites around lines 98, 111, 170, 176, 307, 360)
**Also touch:** `src/Feed/DTO/FeedEntryDTO.php`

**Defect.** `FeedEntryDTO::fromArray()` accepts any string as `link` / `feed_url`. XMLWriter attribute-escapes but does not scheme-check. Older feed clients (Feedly's in-app WebView, RSS readers embedded in email clients) render `javascript:alert(1)` as an anchor.

**Fix.** In `FeedEntryDTO::__construct` (or a validator right before write) reject links whose `parse_url(...)['scheme']` is not `http` or `https`. Log-and-drop the entry (do NOT throw; a bad link should not kill the whole feed).

**Test.** `FeedGeneratorTest` — entry with `link => 'javascript:alert(1)'` produces a feed with that entry omitted (or the link stripped) plus a `Log::warning` assertion.

---

### P0-6. Release metadata: CHANGELOG.md incomplete and undated

**File:** `CHANGELOG.md`

**Defect.** No `## [1.4.0] - YYYY-MM-DD` heading yet; the merged PRs #78, #84, #85, #86, #88, #89, #90, #92 are missing from `## [Unreleased]`. `#92` (adopt ai `ChecksFeatureToggle`/`InteractsWithAiFeature`) also bumps the `artisanpack-ui/ai` constraint to `^1.2` — that's a Changed entry.

**Fix.** Rename `## [Unreleased]` to `## [1.4.0] - <release date>` and add the missing entries. Suggested skeleton (fill in from `git log v1.3.0..release/1.4 --oneline`):

```markdown
## [1.4.0] - 2026-09-XX

### Added
- llms.txt AI-discovery manifest generator (#68).
- IndexNow submitter + sitemap ping path (#69).
- Explicit AI-crawler robots controls with `default_allow` kill-switch (#70).
- Focus-keyword + first-paragraph AI-readiness analyzers (#71).
- RSS 2.0 and Atom 1.0 feed generator (#72).
- Organization `sameAs` + `logo` on the schema graph (#73).
- OG-image generator service (MightyShare-style social cards) (#74).
- LocalBusiness dated/holiday `OpeningHoursSpecification` support (#75).
- `apSeoAddSchema()` helper + `SchemaCollector` service (#77).
- `SchemaService` render-pipeline centralization (#78).

### Changed
- `artisanpack-ui/ai` constraint bumped to `^1.2`; SEO AI Livewire components now
  compose the `ChecksFeatureToggle` + `InteractsWithAiFeature` traits (#76/#92).
  See `docs/upgrade-1.4.0.md` for consumer-facing changes.

### Fixed
- (Add entries for each P0/P1 fix from this document as they land.)
```

---

### P0-7. Release metadata: `docs/upgrade-1.4.0.md` missing

**File to create:** `docs/upgrade-1.4.0.md` (prior releases each have one)

**Fix.** Ship a page covering, at minimum:
- The `artisanpack-ui/ai` `^1.0` → `^1.2` constraint bump; PHP 8.3+ / Laravel 12+ required to use the AI features.
- The AI-Livewire-trait refactor's BC note (see P1-9 below) — downstream components must not redeclare `$error` or `$isLoading`.
- New config keys added in 1.4: `seo.llms_txt`, `seo.indexnow`, `seo.og_image`, expanded `seo.robots.ai_crawlers`, plus the new `seo.feeds` block added by P0-8.
- New consumer-registered routes required (llms.txt, feeds, IndexNow key file) — link to the sections in the config file / new docs pages.

---

### P0-8. Release metadata: missing `seo.feeds` config block

**File:** `config/seo.php`

**Defect.** RSS/Atom generator (#88) ships with no config entry. No defaults for feed title, description, per-page count, cache TTL, whether the route is auto-registered.

**Fix.** Add a `feeds` block. Mirror the shape of the existing `sitemap` / `llms_txt` blocks so consumers have a consistent surface. At minimum:
```php
'feeds' => [
    'enabled'         => env( 'SEO_FEEDS_ENABLED', true ),
    'route_enabled'   => env( 'SEO_FEEDS_ROUTE_ENABLED', false ),
    'rss_path'        => '/feed.xml',
    'atom_path'       => '/feed.atom',
    'title'           => env( 'APP_NAME', 'Site' ),
    'description'     => '',
    'per_page'        => 50,
    'cache_ttl'       => 300,
    'feed_id'         => null, // stable IRI for Atom <id>; falls back to feed URL with a warning
],
```

Wire it in `SEOServiceProvider`. Update `FeedGenerator` to read defaults from config where it currently requires them at call site.

---

## P1 — Should fix (correctness + user-visible bugs)

### P1-1. `FocusKeywordAnalyzer` uses `strtolower` — multibyte keywords silently fail

**File:** `src/Services/Analysis/FocusKeywordAnalyzer.php` (every `strtolower` — lines 69, 79, 94, 109, 126, 142, 159, 177 per fork review)

**Defect.** German "Straße", Turkish "İSTANBUL", any non-ASCII keyword under-matches. All 7 placement checks over-report failure for non-ASCII sites.

**Fix.** Replace every `strtolower(...)` with `mb_strtolower($x, 'UTF-8')`. Same fix in the H1/subheading/alt-text extractors this analyzer uses.

**Test.** Add to `tests/Unit/Services/Analysis/FocusKeywordAnalyzerTest.php` — dataset with `['Straße', 'straße'], ['İSTANBUL', 'istanbul'], ['大阪', '大阪']` each asserting detection.

---

### P1-2. `AiReadinessAnalyzer` uses `str_word_count` — zero words for CJK/Cyrillic

**File:** `src/Services/Analysis/AiReadinessAnalyzer.php:370` (`countWords`)

**Defect.** `str_word_count` is ASCII-only. Non-Latin content returns `0`, so `$isSummaryReady` is always false and the analyzer emits false "no opening paragraph" warnings.

**Fix.** Replace with:
```php
return count( preg_split( '/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY ) ?: [] );
```

**Test.** `AiReadinessAnalyzerTest` — content in Japanese and Cyrillic returns a non-zero word count.

---

### P1-3. `LlmsTxtGenerator` newline injection in titles breaks list items

**File:** `src/Sitemap/Generators/LlmsTxtGenerator.php:228, 459` (`escapeMarkdown`)

**Defect.** `escapeMarkdown` handles only `[` and `]`. A title containing `\n` (multi-line CMS heading) shatters the `- [title](url)` list item. Descriptions with `[]` render as broken Markdown links because `escapeMarkdown` isn't applied to descriptions.

**Fix.**
- In `escapeMarkdown`, also replace `\r?\n` with a single space.
- Apply `escapeMarkdown` (or a description-specific escaper that also collapses whitespace) to descriptions before concatenation.

**Test.** `LlmsTxtGeneratorTest` — title with `"foo\nbar"` yields one-line list item; description with `"see [here]"` renders with escaped brackets.

---

### P1-4. IndexNow trusts HTTP 200 without inspecting error body → silent partial failures

**File:** `src/IndexNow/IndexNowSubmitter.php:318-335` (result construction), `410` (log line)

**Defect.** IndexNow endpoints return `200 OK` with structured `warnings`/`code` in the body for per-URL rejections (unverified key, wrong host). Callers reading `success===true` treat these as indexed.

**Fix.**
- On a 200 response, parse the body when it's JSON, and merge any `warnings`/`code` fields into the result payload.
- Log the response body at `Log::debug` on success paths and `Log::warning` when the body contains error indicators.
- Cap logged body via `Str::limit($response->body(), 512)` (also addresses P2-2 leak).

**Test.** `IndexNowSubmitterTest` — mock a 200 response with `{"code":"UnverifiedHost"}` and assert the returned result flags the failure.

---

### P1-5. Schema graph nodes missing `@id` → Google won't cross-reference entities

**File:** `src/Services/SchemaService.php:217-242` (`renderGraph`) + every builder in `src/Schema/Builders/`

**Defect.** `@graph` entries have no stable `@id`, so Search Console can't link an Article's `publisher` to the Organization on the same page. When Organization and LocalBusiness share the same real-world identity they render as two separate businesses.

**Fix.** Each builder must emit `@id` derived from a stable URL. Suggested convention:
- Organization: `url('/#organization')`
- LocalBusiness: `url('/#localbusiness')`
- WebSite: `url('/#website')`
- WebPage: `request()->url() . '#webpage'`
- Article: `request()->url() . '#article'`

Then cross-reference by `@id`: `Article.publisher = ['@id' => url('/#organization')]`, etc.

**Test.** `tests/Unit/Services/SchemaServicePipelineTest.php` (or a new `SchemaGraphIdTest.php`) — asserts every graph node has `@id` and that `publisher`/`about`/`isPartOf` references resolve to another node's `@id` in the same graph.

---

### P1-6. OG image renderer leaks GD resources → queue-worker OOM

**File:** `src/Services/OgImage/GdOgImageRenderer.php` — `render()` (~57), `drawBackgroundImage()` (~119), `drawLogo()` (~157)

**Defect.** `imagedestroy()` is never called. Queue worker that generates 500 OG images per process grows unboundedly.

**Fix.** Wrap `render()` in `try { ... } finally { ... }`; in `finally` call `imagedestroy()` on the main canvas plus any loaded logo / background handles. Use nullable locals so the `finally` can skip unset handles.

**Test.** No direct memory test; add a smoke test that renders 20 images in a loop under `pest --profile` and asserts a rendered file exists each iteration. The real verification is code-review.

---

### P1-7. OG image renderer produces black halos on transparent PNG logos

**File:** `src/Services/OgImage/GdOgImageRenderer.php:57, 173, 411-413, 72-74`

**Defect.** `imagecreatetruecolor()` returns a canvas with no saved alpha and blending on; transparent regions of a logo composite onto black, and the output PNG isn't alpha-preserving.

**Fix.**
- After `imagecreatetruecolor($w, $h)`: call `imagesavealpha($image, true)` and fill with a fully-opaque background color (or a fully-transparent one if `bg_color` is null).
- When loading a PNG (logo OR background): `imagealphablending($src, false); imagesavealpha($src, true);` BEFORE `imagecopyresampled`.

**Test.** `tests/Unit/Services/OgImage/GdOgImageRendererTest.php` — render with a fixture transparent PNG logo; sample the output at a known-transparent pixel and assert the pixel color matches the canvas `bg_color` (not `#000`).

---

### P1-8. OG image bitmap fallback miscounts multibyte width + renders mojibake

**File:** `src/Services/OgImage/GdOgImageRenderer.php:359`

**Defect.** `imagefontwidth($f) * strlen($text)` over-counts multibyte chars; `imagestring()` renders only Latin-1 so the bitmap fallback prints garbage for accented content.

**Fix.**
- Use `mb_strlen($text, 'UTF-8')` for the width calculation.
- Document in the class docblock and README that a TTF `font_path` is required in production; keep the bitmap path only as a no-font degrade for CI.
- Log a `Log::warning` when the bitmap fallback executes with non-ASCII text.

**Test.** `GdOgImageRendererTest` — non-ASCII title with a fixture TTF renders successfully; the bitmap fallback emits a warning when given non-ASCII content.

---

### P1-9. AI Livewire trait refactor: BC risk for consumers that extend components

**File:** `src/Livewire/Ai/*.php` (all components adopting the trait after #92)

**Defect.** `InteractsWithAiFeature` declares `public bool $isLoading` and `public ?string $error`. Downstream projects that extended a component and redeclared either property with a different type (e.g. `public string $error = ''`) now throw a `TypeError` on Livewire hydration.

**Fix.** No code change — this is a BC note. Document in `docs/upgrade-1.4.0.md` (P0-7) under a "Breaking (soft)" section: consumers extending SEO AI Livewire components must not redeclare `$error` or `$isLoading`. Consider adding `@method` / `@property` docblocks on the SEO AI components pointing to the trait so IDE users see the source.

---

### P1-10. LocalBusiness emits both flat `openingHours` and structured `openingHoursSpecification`

**File:** `src/Schema/Builders/LocalBusinessSchema.php:100-104`; parent at `src/Schema/Builders/OrganizationSchema.php:139-142`

**Defect.** When integration returns a flat string AND a structured array, both keys ship in the JSON-LD. Google's Rich Results Test flags redundancy.

**Fix.** In `LocalBusinessSchema::generate()`, after calling `parent::generate()`, `unset($schema['openingHours'])` before adding `openingHoursSpecification` from the structured array. If the structured array is empty, retain the flat string.

**Test.** `LocalBusinessSchemaTest` (see P2-6) — both inputs supplied → output contains only `openingHoursSpecification`.

---

### P1-11. `LocalBusiness` `validFrom` / `validThrough` not validated as ISO-8601

**File:** `src/Schema/Builders/LocalBusinessSchema.php:175-181` (`buildOpeningHours`)

**Defect.** Values are echoed verbatim into JSON-LD. `Carbon` instance triggers `Object of class DateTime could not be converted to string` on `json_encode`; a string like `"12/25"` ships as invalid schema.

**Fix.** Accept `DateTimeInterface|string`, normalize via `->format('Y-m-d')`, and validate strings against `/^\d{4}-\d{2}-\d{2}$/` before insertion (log-and-drop malformed).

**Test.** `LocalBusinessSchemaTest` — pass a `Carbon::create(2026, 12, 25)`, assert output `"2026-12-25"`. Pass `"12/25"`, assert the entry is dropped with a warning logged.

---

### P1-12. Missing consumer-facing routes for llms.txt / feeds / IndexNow key file

**Files:** `routes/web.php`, `config/seo.php`, `src/Providers/SEOServiceProvider.php`

**Defect.** `#68`, `#69`, `#72`, `#74` each add a service but not a route. `llms.txt`, `/feed.xml`, `/feed.atom`, and the IndexNow key-verification file all NEED to be served at fixed URLs. The current pattern (`sitemap.route_enabled`, `robots.route_enabled`) has no counterparts.

**Fix.** For each new surface, add:
1. A `<feature>.route_enabled` config key (default `false` — opt-in for BC).
2. A controller (`src/Http/Controllers/LlmsTxtController.php`, `FeedController.php`, `IndexNowKeyController.php`).
3. Registration inside `routes/web.php` gated on the config toggle.
4. A short section in each config block's docblock explaining how to enable.

For OG images: they resolve via `Storage::url` — no route needed, but document that in the OG image config comments.

**Test.** Feature tests under `tests/Feature/Http/` for each new route: 200 with expected body when enabled, 404 when disabled.

---

## P2 — Nice-to-have (polish + hygiene)

### P2-1. Atom feed-level `<id>` defaults to feed URL, breaks entry identity on URL change

**File:** `src/Feed/Generators/FeedGenerator.php:152, 165`

**Fix.** Read `feed_id` from the new `seo.feeds.feed_id` config (P0-8). When absent, fall back to the URL AND `Log::notice("Atom feed-level <id> defaulted to feed URL; set seo.feeds.feed_id to a stable tag: IRI.")`.

---

### P2-2. IndexNow error log leaks unbounded response body

**File:** `src/IndexNow/IndexNowSubmitter.php:410`

**Fix.** `Str::limit($response->body(), 512)`. Rolled into P1-4 above.

---

### P2-3. OG image renderer: cache-key TOCTOU wastes CPU

**File:** `src/Services/OgImageService.php:90-91`

**Fix.** Wrap the render section in `Cache::lock("og-image:{$path}", 30)->block(5, fn() => ...)`. Not critical (the second write is atomic and idempotent) but wastes ~200ms of GD work per concurrent miss.

---

### P2-4. Logo `ImageObject` lacks `width`/`height` → Google Search Console warnings

**File:** `src/Schema/Builders/AbstractSchema.php:164-174` (`buildImageObject`)

**Fix.** Accept optional `{url, width, height}` array; emit `width`/`height` when provided. Keep string overload for BC.

---

### P2-5. `sameAs` accepts non-URL strings

**File:** `src/Schema/Builders/OrganizationSchema.php:127-136`

**Fix.** Add `filter_var($url, FILTER_VALIDATE_URL)` inside the `array_filter` callback.

---

### P2-6. Move LocalBusiness tests into their own file

**File:** none currently; commit `ea27bcd` folded holiday-hours tests into `tests/Unit/Schema/Builders/OrganizationSchemaTest.php`.

**Fix.** Extract into `tests/Unit/Schema/Builders/LocalBusinessSchemaTest.php`. Improves discoverability and stops regressions from hiding under an unexpected suite name.

---

### P2-7. `hexToRgb` silent fallback to black yields black-on-black cards

**File:** `src/Services/OgImage/GdOgImageRenderer.php:447`

**Fix.** On parse failure, either throw in dev + return a contrasting default in prod, or log a `Log::warning` and pick a contrasting default based on which color slot is being resolved.

---

### P2-8. `phpcs` prints 20 `Internal.NoCodeFound` warnings on Blade stubs

**File:** `phpcs.xml`

**Defect.** `tests/stubs/views/**/*.blade.php` matches the phpcs include; blade files are noise for phpcs.

**Fix.** Add `<exclude-pattern>*.blade.php</exclude-pattern>` to `phpcs.xml` OR tighten the `<file>` element to `<file>src</file><file>tests/Feature</file><file>tests/Unit</file>`.

**Verify.** `./vendor/bin/phpcs` prints zero warnings.

---

### P2-9. CI: lint job swallows PHPCS failures

**File:** `.github/workflows/ci.yml:47`

**Defect.** `continue-on-error: true` on the PHPCS step means sniff failures never block CI.

**Fix.** Remove `continue-on-error: true`. Only merge after P2-8 lands so CI is green.

---

### P2-10. CI matrix doesn't vary Laravel version

**File:** `.github/workflows/ci.yml`

**Defect.** `composer.json` allows `illuminate/support: ^11.0|^12.0|^13.0`. CI only tests the auto-resolved (latest) version.

**Fix.** Add a `laravel: ['11.*', '12.*', '13.*']` dimension and a step that `composer require illuminate/support:${{ matrix.laravel }} --no-update` before `composer update`. Exclude combos your minimum PHP can't support.

---

### P2-11. `composer.json` inline `version` field

**File:** `composer.json:7`

**Defect.** Composer/Packagist recommend removing `version` from library `composer.json` when Git tags drive versioning. Historically absent from prior 1.x tags.

**Fix.** Decide once and stay consistent. Recommendation: remove it, because a stale inline version has caused mismatches with the tag in past releases. If kept, bump to `1.4.0` and add a checklist item to bump it every release.

---

### P2-12. Docs pages missing for 1.4 features

**Fix.** Create:
- `docs/advanced/llms-txt.md`
- `docs/advanced/indexnow.md`
- `docs/advanced/feeds.md`
- `docs/advanced/og-image.md` (usage; the existing `og-image-backend-decision.md` is a decision doc)
- Rewrite `docs/advanced/robots.md` (~line 168 "Blocking AI Crawlers") for the config-driven controls from #86. Cover the P0-3 `default_allow` semantics precisely so future regressions get caught in docs review.
- Update `docs/usage/schema.md` with Organization `sameAs` + `logo` and LocalBusiness `validFrom`/`validThrough`/`closed`.
- Update `docs/advanced/analysis.md` with `AiReadinessAnalyzer` + first-paragraph focus-keyword coverage.

---

### P2-13. README missing feature sections

**File:** `README.md`

**Fix.** Add short subsections (link to `docs/`) for: llms.txt, IndexNow, AI-crawler robots controls, RSS/Atom feeds, Organization sameAs+logo, OG image generator, LocalBusiness holiday hours, AI-readiness analyzer, SchemaService pipeline centralization. Update the "What's New" / "Feature Suites" table.

---

## Suggested commit sequence

Group related fixes; keep each commit small enough to review. Suggested order:

1. `chore(deps): patch security advisories (php_codesniffer, livewire)` — P0-1, P0-2
2. `chore(phpcs): exclude blade stubs from sniff, fail CI on real errors` — P2-8, P2-9
3. `fix(feeds): sanitize XML control chars and reject non-http links` — P0-4, P0-5, P2-1
4. `fix(robots): honor ai_crawlers.default_allow=false as global block` — P0-3
5. `fix(analysis): multibyte lowercasing in focus-keyword + AI-readiness word count` — P1-1, P1-2
6. `fix(llms-txt): escape newlines in titles and brackets in descriptions` — P1-3
7. `fix(indexnow): surface partial failures from 200 responses; cap log body` — P1-4, P2-2
8. `fix(schema): emit stable @id on all graph nodes and cross-reference` — P1-5
9. `fix(schema): dedupe openingHours + validate ISO-8601 dates in LocalBusiness` — P1-10, P1-11
10. `fix(schema): validate sameAs URLs; support width/height on logo ImageObject` — P2-4, P2-5
11. `fix(og-image): release GD resources, preserve PNG alpha, multibyte width` — P1-6, P1-7, P1-8, P2-3, P2-7
12. `feat(config): add seo.feeds block and route toggles for new discovery surfaces` — P0-8, P1-12
13. `test(schema): split LocalBusiness cases out of OrganizationSchemaTest` — P2-6
14. `ci: matrix Laravel 11/12/13; drop composer.json inline version` — P2-10, P2-11
15. `docs: 1.4.0 upgrade guide + per-feature pages + README updates` — P0-7, P2-12, P2-13
16. `chore(release): CHANGELOG.md — 1.4.0 <date>` — P0-6

Commits 3–11 each need a Pest test; do not skip. If any test would touch AI features that require the `artisanpack-ui/ai` optional dep, gate it with `->skip(!class_exists(...), 'ai optional')`.

---

## Release procedure (after all P0/P1 are green)

Run from inside `~/Code/ArtisanPack UI Packages/seo`:

```bash
# 1. Clean lint pass
rm -f .php-cs-fixer.cache
./vendor/bin/php-cs-fixer fix
./vendor/bin/phpcs

# 2. Full test pass
./vendor/bin/pest

# 3. Security re-check
composer audit
composer outdated --direct

# 4. Confirm CHANGELOG date and composer.json (or absence of) version match
grep '^## \[1.4.0\]' CHANGELOG.md
grep '"version"' composer.json  # empty is intentional after P2-11

# 5. Push the release-hardening PR to release/1.4; wait for CI green.
# 6. After merge, from release/1.4:
git checkout release/1.4
git pull
git tag -a v1.4.0 -m "v1.4.0"
git push origin v1.4.0

# 7. Open a follow-up PR merging release/1.4 into release/1.x, then delete release/1.4 per repo convention.
```

Do NOT tag until every P0 item is checked and Pest is green on both PHP 8.2 and 8.4 CI matrices (P2-10 adds Laravel dimension — that also needs to be green).

---

## Out of scope (noted, not in this release)

- Upgrade `pestphp/pest` 3.x → 5.x, `orchestra/testbench` 10.x → 11.x, `livewire/livewire` 3.x → 4.x — each is a major bump; punt to a v1.5 or v2.0 milestone.
- `SchemaService::getOrganizationData` reads `class_exists` while `OrganizationSchema` uses `CmsFrameworkIntegration` — two detection paths that can drift; consolidate in a follow-up.
- Sitemap ping SSRF: `SitemapSubmitter` fires user-configurable engine URLs. Not changed in 1.4 but the same-shape concern as IndexNow — audit in v1.5.

---

**End of plan.** Follow it top-to-bottom. When you have questions the plan doesn't answer, prefer the safer/more-defensive option and note the deviation in the PR description.
