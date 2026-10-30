---
title: Filter Hooks
---

# Filter Hooks

ArtisanPack UI SEO fires filter hooks at key points in its lifecycle so integrators can transform values before they reach the renderer. Filters are powered by the required [`artisanpack-ui/hooks`](https://github.com/ArtisanPack-UI/hooks) package.

A filter callback **must return a value** and can transform the payload. An action runs for side effects and can't change the underlying data. The package doesn't dispatch Laravel [events](Api-Events).

## Available Filters

### `ap.seo.metaTags`

Fired inside `MetaTagService::generate()` after the tag array is assembled and before the `MetaTagsDTO` is built. Use it to add, remove, or rewrite the values that end up in the rendered `<head>`.

- **Since**: 1.3.0
- **Payload**: `(array $tags, ?Model $subject, Request $request)`
- **Return**: `array` — the (possibly modified) `$tags` array.

The `$tags` array has the following shape:

```php
[
    'title'          => string,
    'description'    => ?string,
    'canonical'      => string,
    'robots'         => string,
    'additionalMeta' => array,
]
```

`additionalMeta` is an array of `name => content` pairs, each rendered as a `<meta name>` tag. It holds `author` by default. Since 1.9.0 it no longer includes `keywords` or `article:*` tags; those dates are Open Graph properties.

Example:

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

addFilter( 'ap.seo.metaTags', function ( array $tags, ?Model $subject, Request $request ): array {
	if ( $request->is( 'campaigns/*' ) ) {
		$tags['robots'] = 'noindex, follow';
	}

	return $tags;
} );
```

If a callback returns a non-array value, `MetaTagService` falls back to the original tag array so a broken filter cannot break page rendering.

### `ap.seo.analysisContent`

Fired inside `AnalysisService::extractContent()` after the model's content is resolved and before it reaches the analyzers. Use it to append template-provided markup (a page-title bar's H1, hero component copy, `single-*.blade.php` scaffolding) so analyzers score the page the way a visitor sees it.

- **Since**: 1.7.0
- **Payload**: `(string $content, Model $model)`
- **Return**: `string` — the (possibly modified) HTML the analyzers will receive. Non-string returns fall back to the unfiltered content.

Example:

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

Models that implement `ArtisanPackUI\SEO\Contracts\SeoAnalyzableContent` supply their full analyzable HTML through `getSeoAnalysisHtml()` before this filter runs, so the filter can still layer on additional markup for those models.

Filter output feeds the `seo_analysis_cache.content_hash` fingerprint so template markup changes invalidate cached analysis results automatically.

### `ap.seo.sitemapEntries`

Fired inside `SitemapGenerator::generate()` and `SitemapGenerator::generateFromProvider()` so integrators can append, drop, or reorder sitemap entries per sitemap type.

- **Since**: 1.3.0
- **Payload**: `(array $entries, string $sitemapType)`
- **Return**: `array` — the (possibly modified) list of entries. Entries are `SitemapEntry` models or, for provider URLs, arrays.

Example:

```php
addFilter( 'ap.seo.sitemapEntries', function ( array $entries, string $sitemapType ): array {
	if ( 'post' !== $sitemapType ) {
		return $entries;
	}

	return array_values( array_filter( $entries, function ( $entry ) {
		$url = is_array( $entry ) ? $entry['url'] : $entry->url;

		return ! str_contains( $url, '/preview/' );
	} ) );
} );
```

`$sitemapType` is the entry type, such as `post`, or a provider's type. The all-types `sitemap-{page}.xml` passes an empty string, and since 1.9.0 the sitemap index no longer links that sitemap, so put your logic on the per-type values. The image, video, and news sitemaps don't run this filter.

### `ap.seo.llmsTxtEntries`

Fired inside `LlmsTxtGenerator::generate()` so integrators can add, drop, or reorder [llms.txt](Advanced-Llms-Txt) entries.

- **Since**: 1.4.0
- **Payload**: `(array $entries)`
- **Return**: `array` — the (possibly modified) list of entries. Entries are `SitemapEntry` models or arrays. Since 1.9.0, provider URLs arrive as arrays, so handle both.

### `ap.seo.feedEntries`

Fired before RSS and Atom feed entries are rendered.

- **Payload**: `(array $entries, string $feedType)`
- **Return**: `array`

### `ap.seo.schemaGraph`

Fired before the JSON-LD graph is rendered.

- **Payload**: `(array $graph, ?Model $model)`
- **Return**: `array`

### `ap.seo.resolveMorphModel`

Resolves stored morph types that aren't class names. See [Custom Morph Types](Usage-Model-Integration#custom-morph-types).

- **Since**: 1.8.0
- **Payload**: `(?Model $model, string $type)`
- **Return**: `?Model`

## Available Actions

Actions run for side effects and return nothing.

### `ap.seo.schemaRendering` and `ap.seo.schemaRendered`

Fired before and after the JSON-LD graph is output.

- **Payload**: `(array $graph, ?Model $model)`

## Registering Filters

Register filter callbacks from any service provider's `boot()` method:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SeoFilterServiceProvider extends ServiceProvider
{
	public function boot(): void
	{
		addFilter( 'ap.seo.metaTags', [ $this, 'appendVersionMeta' ], 20 );
	}

	public function appendVersionMeta( array $tags ): array
	{
		$tags['additionalMeta']['x-app-version'] = config( 'app.version' );

		return $tags;
	}
}
```

Lower priority numbers run first; the default priority is `10`.

## Removing Filters

Use `removeFilter()` or `removeAllFilters()` from `artisanpack-ui/hooks` to detach callbacks when needed (for example, in tests):

```php
removeAllFilters( 'ap.seo.metaTags' );
```

## Next Steps

- [Events](Api-Events) — Browser events from the Livewire components.
- [Services](Api-Services) — Service classes that fire these filters.
- [Helper Functions](Api-Helpers) — Helper reference.
