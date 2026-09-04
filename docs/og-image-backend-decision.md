# OG Image Generator — Image Backend Decision

Related: issue #74.

## Options considered

| Backend | Availability | Fidelity | Ops cost | Test friendliness |
|---------|--------------|---------|---------|-------------------|
| **GD** (PHP ext) | Ships with virtually every PHP build; enabled on Herd, most shared hosts, every Laravel Docker base image | Solid for solid-color bg + logo + wrapped title/subtitle. TTF via `imagettftext`, bitmap fallback via `imagestring` | Zero — no daemon, no binary to install | High — works headlessly in CI without any font asset when using the bitmap fallback |
| **Imagick** (PHP ext) | Not installed by default on many hosts; requires the ImageMagick binary + PHP ext | Higher — better anti-aliasing, gradients, blend modes, effects | Extra ext + system package; version drift between hosts is common | Medium — needs Imagick installed in every CI image |
| **Headless render** (Chromium / Playwright / Browsershot) | Requires Node + a Chromium binary on the server | Highest — full CSS/webfont fidelity, arbitrary HTML templates | Heavy — 200MB+ Chromium install, cold-start latency (100–500ms), fragile in low-memory containers | Low — needs Chromium in CI, flaky in constrained runners |

## Decision

**Ship GD as the default renderer** for v1.4. Bind the renderer behind
`OgImageRendererContract` so alternative backends can be swapped in without
touching the service or its callers.

### Why GD

- **Universal availability.** GD is present on every environment this
  package already supports; requiring nothing new keeps the feature
  install-free.
- **Sufficient fidelity.** A branded title card is background + logo +
  1–2 lines of text. GD's `imagettftext` handles that cleanly when the
  installer supplies a TTF path, and the bitmap fallback keeps the
  service functional (and testable) when no font is configured.
- **Zero ops surface.** No sidecar process, no browser, no external
  binary — the service runs inside the same PHP worker that handled
  the publish event.
- **Deterministic output.** GD gives us byte-stable PNGs for a given
  input, which pairs well with the deterministic caching path.

### Why not Imagick / headless

- Imagick's incremental fidelity gain doesn't justify making the ext a
  hard requirement of an SEO package. Users who need it can drop in a
  custom `OgImageRendererContract` binding — the plumbing is already
  there.
- Headless rendering solves a bigger problem than we have (arbitrary
  HTML templates). For a small set of branded card layouts, the cost
  (Chromium binary + boot latency + memory) is disproportionate.

## Extension points

- `config('seo.og_image.renderer')` — swap the concrete class.
- `OgImageRendererContract::render(OgImageTemplate, string, ?string): string`
  — implement to return raw PNG binary; the service handles caching,
  paths, and disk writes.
- `OgImageTemplate::fromConfig()` — extend the template DTO if a custom
  backend needs additional fields (e.g. gradient stops for Imagick).
