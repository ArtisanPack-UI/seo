---
title: OG Image Generator
---

# OG Image Generator

The package renders branded OG social-share images (MightyShare-style)
via PHP's bundled GD extension and caches them on your configured
storage disk. Given the same title, subtitle, and template, the
service always resolves to the same file — no re-render on cache hits.

> Backend rationale is documented in
> [og-image-backend-decision.md](../og-image-backend-decision.md).

## Requirements

The default renderer (`GdOgImageRenderer`) requires PHP's **`ext-gd`**
extension. Without it, `imagecreatetruecolor()` is unavailable and the
renderer throws `RuntimeException` on first use. `ext-gd` is listed in
`composer.json`'s `suggest` block — required only when this feature is
active. Confirm the extension is loaded in production (`php -m | grep -i gd`)
and in CI (`extensions: gd` under `shivammathur/setup-php`). Swap in a
custom `OgImageRendererContract` binding to avoid the dependency.

## Configuration

```php
'og_image' => [
    'disk'     => env( 'SEO_OG_IMAGE_DISK', 'public' ),
    'path'     => 'og-images',                 // relative to the disk root
    'template' => [
        'width'                     => 1200,
        'height'                    => 630,
        'background_color'          => '#0f172a',
        'text_color'                => '#ffffff',
        'subtitle_color'            => '#cbd5e1',
        'padding'                   => 60,
        'title_font_size'           => 56,
        'subtitle_font_size'        => 28,
        'font_path'                 => storage_path( 'app/fonts/Inter-Bold.ttf' ),
        'logo_path'                 => public_path( 'brand/logo.png' ),
        'logo_width'                => 96,
        'background_image_path'    => null,
        // Scrim (1.6.0): darkens the background image so light text stays
        // readable over any photo. Only applied when `background_image_path`
        // is set.
        'background_scrim_color'    => '#000000',
        'background_scrim_opacity'  => 60,     // percent 0-100; 0 disables
        'background_scrim_gradient' => true,   // fade lighter at top → darker at bottom
    ],
],
```

## Generating an image

```php
use ArtisanPackUI\SEO\Services\OgImageService;

$url = app( OgImageService::class )->generate(
    title: 'How we shipped v1.4.0',
    subtitle: 'A guided tour of the release',
);
```

Pass per-page `overrides` to swap the template inline, or set
`$forceRegenerate=true` to rebuild the cached file.

**Never populate `$overrides` from untrusted input** — `logo_path`,
`background_image_path`, and `font_path` are file-system paths the
renderer opens directly. Resolve any user-visible template choice to a
whitelist of server-controlled paths first.

## Layout (1.6.0)

The default layout anchors the title stack to the **bottom-left** of
the canvas, with the site logo in the top-left corner and the subtitle
at the padded bottom edge. When only a title is supplied it sits
directly against the padded bottom; when a subtitle is present the
title lifts to leave a small gap above it. Both text elements
left-align at the template `padding` so they share the logo's left
edge — MightyShare-style cards.

Anchor points will move onto `OgImageTemplate` in a later release for
`bottom / center / top` layout toggles.

## Scrimming a background image (1.6.0)

When you set `background_image_path` — either on the site-wide template
or per page via `overrides` — the renderer composites a semi-transparent
scrim between the image and the text so a bright photo doesn't wash the
title out. The scrim is only applied when a background image was actually
drawn: a missing / undecodable path falls through to the flat
`background_color`, which is left untouched (the operator picked that
palette explicitly for text readability).

Configure through `background_scrim_*` in the template array:

- `background_scrim_color` — hex string (default `#000000`)
- `background_scrim_opacity` — percentage `0-100` (default `60`). Set
  to `0` to disable the scrim entirely.
- `background_scrim_gradient` — bool (default `true`). When true, the
  scrim fades from ~25% of the configured opacity at the top down to
  full opacity at the bottom so the darkening concentrates where the
  title/subtitle sit and the top of the photo (where the logo lives)
  stays visible.

## Guarantees added in 1.4.0

- **Alpha preservation**: transparent PNG (and WEBP) logos and
  backgrounds composite cleanly — no black halo around anti-aliased
  edges.
- **Resource release**: `render()` wraps its body in `try/finally` and
  calls `imagedestroy()` on the canvas plus loaded logo/background
  handles, so queue workers rendering many images no longer leak GD
  memory.
- **Multibyte width**: `imagefontwidth * mb_strlen` for width
  measurement. The bitmap fallback still renders only Latin-1, so it
  logs a `Log::warning` when the text is non-ASCII — configure a TTF
  `font_path` in production.
- **Cache lock**: concurrent misses coalesce behind a `Cache::lock`
  so ten simultaneous requests spend one render's worth of CPU, not
  ten.
- **Colour parsing**: a malformed hex value logs a `Log::warning` and
  falls back to white rather than silently producing black-on-black
  cards.

## Cache invalidation

When the source content (title, subtitle, or template) changes, drop
the cached image so the next request re-renders:

```php
app( OgImageService::class )->forget( 'How we shipped v1.4.0' );
```

## Serving the image

OG images resolve through `Storage::url()`. Configure a public disk
(`public` by default) so the generated files sit behind an accessible
URL, then reference the returned URL from your meta tags:

```blade
<meta property="og:image" content="{{ $ogImageUrl }}">
```
