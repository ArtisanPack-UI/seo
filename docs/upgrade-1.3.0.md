---
title: Upgrading to 1.3.0
---

# Upgrading to 1.3.0

This guide covers upgrading from v1.2.x to v1.3.0. This release introduces two new filter hooks for customizing meta tag and sitemap output, and promotes `artisanpack-ui/hooks` from an optional integration to a required dependency.

## Summary

- **New**: `ap.seo.metaTags` filter hook — mutate the assembled meta tag array before the `MetaTagsDTO` is built.
- **New**: `ap.seo.sitemapEntries` filter hook — add, drop, or reorder sitemap entries per sitemap type.
- **Changed**: `artisanpack-ui/hooks: ^1.3` is now a required dependency. The `PackageDetector::hasHooks()` guard has been removed.
- **Changed**: `VisualEditorIntegration` now subscribes to the renamed `ap.visualEditor.prePublishChecks` hook (was `visual_editor.pre_publish_checks`), matching the visual-editor Wave 3 rename.
- **Removed**: `PackageDetector::hasHooks()`.

There are no changes to models, services, contracts, controllers, or existing API endpoints. Existing code continues to work unchanged.

## Update the Package

```bash
composer update artisanpack-ui/seo
```

Composer will install `artisanpack-ui/hooks: ^1.3` automatically if you do not already have it.

## Breaking Changes

### `PackageDetector::hasHooks()` removed

The helper method used to check whether the optional hooks package was installed. Since hooks is now required, the guard no longer makes sense.

If you were calling it directly:

```php
// Before
if ( PackageDetector::hasHooks() ) {
	addFilter( 'my.hook', $callback );
}

// After
addFilter( 'my.hook', $callback );
```

### Visual Editor pre-publish hook renamed

If you were listening for the visual-editor pre-publish check event yourself, update your subscriber to use the new name:

```php
// Before
addFilter( 'visual_editor.pre_publish_checks', $callback );

// After
addFilter( 'ap.visualEditor.prePublishChecks', $callback );
```

`VisualEditorIntegration::registerPrePublishChecks()` inside the SEO package is already updated for you.

## Using the New Filter Hooks

### `ap.seo.metaTags`

Fires inside `MetaTagService::generate()` after the tag array is assembled and before the `MetaTagsDTO` is built. Callbacks can add, remove, or rewrite `title`, `description`, `canonical`, `robots`, and `additionalMeta`.

Payload: `(array $tags, ?Model $subject, Request $request)`

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

addFilter( 'ap.seo.metaTags', function ( array $tags, ?Model $subject, Request $request ): array {
	if ( $request->is( 'campaigns/*' ) ) {
		$tags['robots'] = 'noindex, follow';
	}

	$tags['additionalMeta'][] = [
		'name'    => 'x-app-version',
		'content' => config( 'app.version' ),
	];

	return $tags;
} );
```

### `ap.seo.sitemapEntries`

Fires inside `SitemapGenerator::generate()` and `SitemapGenerator::generateFromProvider()` so integrators can append, drop, or reorder sitemap entries per type.

Payload: `(array $entries, string $sitemapType)`

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

See [Filter Hooks](Api-Filter-Hooks) for the full hook reference.

## Verify the Upgrade

```bash
composer show artisanpack-ui/seo   # should report 1.3.0
composer show artisanpack-ui/hooks # should report ^1.3
```

Run your application's test suite to confirm nothing regressed.
