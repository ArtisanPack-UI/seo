---
title: Upgrading to 1.7.0
---

# Upgrading to 1.7.0

This guide covers upgrading from v1.6.x to v1.7.0. This release lands
an extension seam for the analyzer content pipeline so SEO analyzers
can see template-provided markup (theme page-title bars, hero
components, `single-*.blade.php` wrappers) instead of scoring only the
model's content field. It ships as a filter hook, a per-model
contract, and a cache fingerprint so results invalidate the moment
template markup changes.

This is an **additive, non-breaking release**. A migration adds one
column to `seo_analysis_cache`.

## Summary

- **New**: `ap.seo.analysisContent` filter — resolves the HTML fed to
  every analyzer, so hosts can append template markup that lives
  outside the content body (#100).
- **New**: `ArtisanPackUI\SEO\Contracts\SeoAnalyzableContent`
  interface — models implementing it short-circuit
  `AnalysisService::extractContent()` and return the exact HTML
  analyzers should reason over (#100).
- **New**: `seo_analysis_cache.content_hash` column + fingerprint-aware
  cache lookup — template markup or filter output changes invalidate
  the cached result even when the model's content field is untouched
  (#100).
- **Fixed**: `HeadingStructureAnalyzer` false "No H1 heading found"
  flag on pages whose H1 lives in a page-title bar or hero component
  rather than the content body.

## Migration

Run migrations after upgrading:

```bash
php artisan migrate
```

This adds `content_hash` (nullable string) to `seo_analysis_cache`.
Existing cached rows without a hash are treated as a miss and refresh
on the next analysis run — no manual cache clear required.

## Feeding template markup into analyzers

Two paths, pick whichever fits.

### Filter hook (per-project, no model changes)

Register `ap.seo.analysisContent` from a service provider to append
template markup for every model, or for a specific model class:

```php
use Illuminate\Database\Eloquent\Model;

addFilter( 'ap.seo.analysisContent', function ( string $content, Model $model ): string {
	if ( ! $model instanceof \App\Models\Post ) {
		return $content;
	}

	$titleBar = view( 'partials.page-title-bar', [ 'post' => $model ] )->render();

	return $titleBar . $content;
} );
```

The callback receives the resolved content plus the model and must
return a string. Return a non-string value and the service falls back
to the original content untouched.

### Model contract (per-model, explicit)

Implement `SeoAnalyzableContent` on any model that already knows how
to render its full analyzable HTML:

```php
use ArtisanPackUI\SEO\Contracts\SeoAnalyzableContent;
use Illuminate\Database\Eloquent\Model;

class Post extends Model implements SeoAnalyzableContent
{
	public function getSeoAnalysisHtml(): string
	{
		return view( 'posts.seo-analysis', [ 'post' => $this ] )->render();
	}
}
```

`getSeoAnalysisHtml()` runs before the `ap.seo.analysisContent`
filter, so returned HTML flows through the filter chain and remains
customizable per project.

## Cache invalidation

The `content_hash` column stores an xxh3 fingerprint of the
resolved-and-filtered HTML on write and compares it on read. Change
the template markup or filter callback and the next analysis run
recomputes and re-caches without a manual `analysis:clear`.

## Compatibility

- No API breakage — models that don't implement the new contract, and
  projects with no filter callback registered, behave exactly as
  1.6.x.
- The migration is additive; the column is nullable so partial
  rollouts stay safe.

## Next Steps

- [Analysis service reference](Advanced-Analysis) — full analyzer
  pipeline documentation.
- [Filter hooks reference](Api-Filter-Hooks) — the full list of
  filter hooks the package fires.
