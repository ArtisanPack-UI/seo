---
title: Upgrading to 1.2.0
---

# Upgrading to 1.2.0

This guide covers upgrading from v1.1.x to v1.2.0. This release is **additive** — it introduces the AI feature suite without breaking existing APIs.

## Summary

- **New**: Five AI agents for meta title generation, meta description generation, content analysis, JSON-LD schema suggestion, and hreflang gap analysis.
- **New**: Livewire, React, and Vue trigger components for each agent, plus a shared `useAiAgent` hook (React) and composable (Vue).
- **New**: `POST /api/seo/ai/*` endpoints for the five agents.
- **New**: Default `seo.ai.use` authorization gate.
- **Changed**: `artisanpack-ui/ai: ^1.0` is now a required dependency.

There are no breaking changes to models, services, contracts, or existing endpoints.

## Update the Package

```bash
composer update artisanpack-ui/seo
```

Composer will pull in the new `artisanpack-ui/ai` dependency alongside the SEO package.

## Configure AI Credentials

`artisanpack-ui/ai` needs at least one provider configured before the agents can run. Publish and edit its configuration file:

```bash
php artisan vendor:publish --tag=ai-config
```

Set the credentials for the provider(s) you want to use (e.g. Anthropic, OpenAI). See the [`artisanpack-ui/ai` documentation](https://github.com/ArtisanPack-UI/ai) for provider-specific setup.

## Grant the `seo.ai.use` Ability

Every AI endpoint gates on the `seo.ai.use` Laravel ability. The service provider ships a default gate that allows any authenticated user; scope it to the roles that should be able to burn AI quota in your own `AuthServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define( 'seo.ai.use', function ( User $user ) {
	return $user->hasRole( 'editor' ) || $user->hasRole( 'admin' );
} );
```

## Publish the Frontend Components (Optional)

If you use the React or Vue admin components, re-run the install command to publish the five new AI components and the `useAiAgent` hook/composable:

```bash
# React
php artisan seo:install-frontend --stack=react --force

# Vue
php artisan seo:install-frontend --stack=vue --force
```

`--force` is required if you previously published components — otherwise the new AI components will not be copied. Files you have not modified will be overwritten cleanly.

## Optional: Register the Agents in Your UI

The AI features integrate with the SEO Meta Editor by dispatching browser events when they return. The bundled Livewire, React, and Vue admin components pick these events up automatically. If you have a custom editor, wire the components in wherever the corresponding field lives:

```blade
<x-artisanpack-input wire:model="form.meta_title" label="Meta Title" />

<livewire:seo::ai-meta-title-suggestor
	:content="$form->content"
	:primary-keyword="$form->focus_keyword"
	:brand="config( 'app.name' )"
/>
```

See [AI Features](Usage-Ai-Features) for the full component and endpoint reference.

## Verify

1. Confirm `POST /api/seo/ai/suggest-meta-title` returns a 401/403 when no auth or ability is present, and a 200 with `variants` when both are present.
2. Confirm the five features appear in the AI feature registry (`php artisan ai:features`).
3. If you use the frontend scaffolding, confirm `resources/js/vendor/seo/{react,vue}/components/ai/` exists and contains the five components.

## No Action Required For

- Existing `HasSeo` models, `SeoMeta` records, redirects, sitemaps, robots.txt, schema builders, and analyzers.
- The existing `/api/seo/*` endpoints (meta, analysis, redirects, schema).
- Custom analyzers, custom schema builders, and custom sitemap providers.
- Blade components (`x-seo-meta`, `x-seo-schema`, etc.).

## Rolling Back

Because 1.2.0 only adds new files and one new dependency, rolling back is safe:

```bash
composer require artisanpack-ui/seo:^1.1
```

`artisanpack-ui/ai` will remain in `composer.lock` but is inert without the SEO agents.

## Next Steps

- [AI Features](Usage-Ai-Features) — full agent, endpoint, and component reference
- [Configuration](Installation-Configuration) — configure the SEO package
