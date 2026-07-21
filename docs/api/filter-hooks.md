---
title: Filter Hooks
---

# Filter Hooks

ArtisanPack UI SEO fires filter hooks at key points in its lifecycle so integrators can transform values before they reach the renderer. Filters are powered by the required [`artisanpack-ui/hooks`](https://github.com/ArtisanPack-UI/hooks) package.

Filters differ from [Events](Api-Events): a filter callback **must return a value** and can transform the payload; an event listener runs for side effects and cannot change the underlying data.

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

### `ap.seo.sitemapEntries`

Fired inside `SitemapGenerator::generate()` and `SitemapGenerator::generateFromProvider()` so integrators can append, drop, or reorder sitemap entries per sitemap type.

- **Since**: 1.3.0
- **Payload**: `(array $entries, string $sitemapType)`
- **Return**: `array` — the (possibly modified) list of `SitemapEntry` instances.

Example:

```php
addFilter( 'ap.seo.sitemapEntries', function ( array $entries, string $sitemapType ): array {
	if ( 'standard' !== $sitemapType ) {
		return $entries;
	}

	return array_values( array_filter( $entries, function ( $entry ) {
		return ! str_contains( $entry->url, '/preview/' );
	} ) );
} );
```

Common sitemap types passed as `$sitemapType`: `standard`, `images`, `videos`, `news`. Custom providers may pass their own identifiers.

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
		$tags['additionalMeta'][] = [
			'name'    => 'x-app-version',
			'content' => config( 'app.version' ),
		];

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

- [Events](Api-Events) — Laravel events dispatched by the package.
- [Services](Api-Services) — Service classes that fire these filters.
- [Helper Functions](Api-Helpers) — Helper reference.
