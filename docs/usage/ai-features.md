---
title: AI Features
---

# AI Features

> Added in v1.2.0

ArtisanPack UI SEO ships a suite of AI-powered agents built on top of [`artisanpack-ui/ai`](https://github.com/ArtisanPack-UI/ai). Each agent solves a discrete SEO task — generating meta titles, meta descriptions, content quality scores, JSON-LD schema, and hreflang gap analysis — and every agent exposes a Livewire component, React and Vue components, and a JSON API endpoint under `/api/seo/ai/`.

## Requirements

- `artisanpack-ui/ai: ^1.0` (installed automatically as a dependency)
- Provider credentials configured through `artisanpack-ui/ai`
- The `seo.ai.use` authorization ability granted to the calling user

## The Feature Suite

| Feature key | Agent class | Purpose |
|---|---|---|
| `seo.suggest_meta_title` | `MetaTitleSuggestionAgent` | 3-5 CTR-optimized title variants ≤60 chars |
| `seo.suggest_meta_description` | `MetaDescriptionAgent` | One 150-160 char meta description |
| `seo.analyze_content` | `ContentAnalysisAgent` | Weighted 0-100 quality score across four dimensions with recommendations |
| `seo.generate_schema` | `SchemaGenerationAgent` | Suggests a JSON-LD type from the 14 supported schemas and returns a starter object |
| `seo.suggest_hreflang` | `HreflangSuggestionAgent` | Flags missing reciprocals, self-refs, x-default, and inconsistent lang codes |

All five features are auto-registered by `SEOServiceProvider::aiFeatures()`; the AI feature registry picks them up on boot.

## Authorization

The AI API endpoints and Livewire components gate on the `seo.ai.use` Laravel ability. The service provider ships a default gate that allows any authenticated user; override it in your own `AuthServiceProvider` or a policy to scope who can burn AI quota:

```php
Gate::define( 'seo.ai.use', function ( User $user ) {
	return $user->hasRole( 'editor' ) || $user->hasRole( 'admin' );
} );
```

## Livewire Components

Each agent has a matching Livewire trigger component that wraps the agent call, exposes loading/error state, and dispatches the result as a browser event. All five components live under the `seo` namespace:

```blade
{{-- Meta title suggestor --}}
<livewire:seo::ai-meta-title-suggestor
	:content="$post->body"
	:primary-keyword="$post->focus_keyword"
	:brand="config( 'app.name' )"
/>

{{-- Meta description suggestor --}}
<livewire:seo::ai-meta-description-suggestor
	:content="$post->body"
	:primary-keyword="$post->focus_keyword"
/>

{{-- Content quality analysis --}}
<livewire:seo::ai-content-analyzer
	:content="$post->body"
	:primary-keyword="$post->focus_keyword"
	:secondary-keywords="$post->secondary_keywords"
/>

{{-- Schema.org suggestion --}}
<livewire:seo::ai-schema-suggestor
	:title="$post->title"
	:content="$post->body"
	:url="route( 'posts.show', $post )"
/>

{{-- Hreflang gap analysis --}}
<livewire:seo::ai-hreflang-suggestor :pages="$hreflangGraph" />
```

Each component emits a browser event when the agent returns so a parent Livewire component or an Alpine listener can consume the result and populate the surrounding form.

## React & Vue Components

Publish the frontend scaffolding first:

```bash
php artisan seo:install-frontend --stack=react
# or
php artisan seo:install-frontend --stack=vue
```

Both stacks ship five components (one per agent) plus a shared `useAiAgent` hook (React) / composable (Vue) that dispatches the correct endpoint, tracks loading and errors, and returns the typed output.

### React

```tsx
import { MetaTitleSuggestor } from '@/vendor/seo/react/components/ai';

<MetaTitleSuggestor
	baseUrl="/api/seo"
	initialContent={post.body}
	primaryKeyword={post.focusKeyword}
	brand="Acme"
	onSelect={( variant ) => setValue( 'meta_title', variant.title )}
/>
```

Under the hood, each AI component uses `useAiAgent`:

```tsx
import { useAiAgent } from '@/vendor/seo/react/hooks/useAiAgent';

const { run, output, isLoading, error } = useAiAgent<Input, Output>(
	'seo.suggest_meta_title',
	{ baseUrl: '/api/seo' },
);

await run( { content: post.body, primary_keyword: post.focusKeyword } );
```

### Vue

```vue
<script setup lang="ts">
import { MetaTitleSuggestor } from '@/vendor/seo/vue/components/ai';
</script>

<template>
	<MetaTitleSuggestor
		base-url="/api/seo"
		:initial-content="post.body"
		:primary-keyword="post.focusKeyword"
		brand="Acme"
		@select="( variant ) => (metaTitle = variant.title)"
	/>
</template>
```

Under the hood, each AI component uses the `useAiAgent` composable:

```ts
import { useAiAgent } from '@/vendor/seo/vue/composables/useAiAgent';

const { run, output, isLoading, error } = useAiAgent<Input, Output>(
	'seo.suggest_meta_title',
	{ baseUrl: '/api/seo' },
);
```

## JSON API

Every agent exposes a single POST endpoint under `/api/seo/ai/`. All endpoints share the same authentication middleware as the rest of the SEO API and additionally require the `seo.ai.use` ability.

| Endpoint | Feature key |
|---|---|
| `POST /api/seo/ai/suggest-meta-title` | `seo.suggest_meta_title` |
| `POST /api/seo/ai/suggest-meta-description` | `seo.suggest_meta_description` |
| `POST /api/seo/ai/analyze-content` | `seo.analyze_content` |
| `POST /api/seo/ai/generate-schema` | `seo.generate_schema` |
| `POST /api/seo/ai/suggest-hreflang` | `seo.suggest_hreflang` |

Every response is wrapped in a `data` envelope with the feature key echoed back:

```json
{
	"data": { /* agent-specific output */ },
	"feature_key": "seo.suggest_meta_title"
}
```

### Meta title — `POST /api/seo/ai/suggest-meta-title`

**Input**

```json
{
	"content": "Full page content or draft body...",
	"primary_keyword": "laravel seo",
	"brand": "Acme"
}
```

**Output**

```json
{
	"variants": [
		{ "title": "Laravel SEO That Ranks | Acme", "char_count": 30, "rationale": "Front-loads the primary keyword." }
	]
}
```

Returns 3-5 variants, each ≤60 characters.

### Meta description — `POST /api/seo/ai/suggest-meta-description`

**Input**

```json
{
	"content": "...",
	"primary_keyword": "laravel seo"
}
```

**Output**

```json
{
	"meta_description": "...",
	"character_count": 156,
	"rationale": "Leans on the practical outcome angle."
}
```

Always 150-160 characters.

### Content analysis — `POST /api/seo/ai/analyze-content`

**Input**

```json
{
	"content": "...",
	"primary_keyword": "laravel seo",
	"secondary_keywords": [ "meta tags", "schema markup" ]
}
```

**Output**

```json
{
	"overall_score": 78,
	"dimensions": {
		"keyword_usage":         { "score": 82, "recommendations": [ "..." ] },
		"readability":           { "score": 74, "recommendations": [ "..." ] },
		"structure":             { "score": 80, "recommendations": [ "..." ] },
		"semantic_completeness": { "score": 76, "recommendations": [ "..." ] }
	}
}
```

`overall_score` is a rounded weighted average: keyword_usage 30%, readability 25%, structure 20%, semantic_completeness 25%. This agent streams by default (`stream = true`) so long analyses surface progress in real time.

### Schema suggestion — `POST /api/seo/ai/generate-schema`

**Input**

```json
{
	"content": "...",
	"title": "10 Ways to Improve Laravel SEO",
	"url": "https://example.com/blog/laravel-seo"
}
```

**Output**

```json
{
	"suggested_type": "BlogPosting",
	"confidence": 0.92,
	"jsonld": { "@context": "https://schema.org", "@type": "BlogPosting", "...": "..." },
	"missing_required_fields": [ "author", "datePublished" ]
}
```

`suggested_type` is guaranteed to be one of the 14 supported schema types the SEO package already builds for. Use `missing_required_fields` to prompt the editor for the values the agent could not infer.

### Hreflang gap analysis — `POST /api/seo/ai/suggest-hreflang`

**Input**

```json
{
	"pages": [
		{
			"url": "https://example.com/en/pricing",
			"lang": "en",
			"translations": [
				{ "url": "https://example.com/fr/pricing", "lang": "fr" }
			]
		}
	]
}
```

**Output**

```json
{
	"issues": [
		{
			"page_url": "https://example.com/fr/pricing",
			"issue_type": "missing_reciprocal",
			"suggested_hreflang": [
				{ "lang": "en", "url": "https://example.com/en/pricing" },
				{ "lang": "fr", "url": "https://example.com/fr/pricing" },
				{ "lang": "x-default", "url": "https://example.com/en/pricing" }
			]
		}
	]
}
```

Issue types: `missing_reciprocal`, `missing_translation`, `missing_self`, `missing_x_default`, `inconsistent_lang`.

## Directly Invoking an Agent

If you need to run an agent server-side (a queued job, an Artisan command, a webhook handler), resolve it from the container and run it directly:

```php
use ArtisanPackUI\SEO\Ai\Agents\MetaTitleSuggestionAgent;

$agent  = app( MetaTitleSuggestionAgent::class );
$result = $agent->run( [
	'content'         => $post->body,
	'primary_keyword' => $post->focus_keyword,
	'brand'           => config( 'app.name' ),
] );

foreach ( $result['output']['variants'] as $variant ) {
	// ...
}
```

The returned array follows the `artisanpack-ui/ai` convention: `output` (typed structure), `input_tokens`, and `output_tokens`.

## Choosing a Model

Each agent declares a sensible default (`claude-haiku-4-5` for the short-generation agents, `claude-sonnet-4-5` for content analysis). Override the model per feature via the AI feature registry configuration exposed by `artisanpack-ui/ai`, or per call by passing a `model` option when dispatching from your own code.

## See Also

- [Configuration](Installation-Configuration) — enabling/disabling AI features
- [Frontend Scaffolding](Advanced-Frontend-Scaffolding) — publishing the React/Vue components
- [Upgrading to 1.2.0](Upgrade-1.2.0) — migration notes
