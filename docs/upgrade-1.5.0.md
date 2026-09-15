---
title: Upgrading to 1.5.0
---

# Upgrading to 1.5.0

This guide covers upgrading from v1.4.x to v1.5.0. This release lands
the AI meta-agent quality pass: both `MetaTitleSuggestionAgent` and
`MetaDescriptionAgent` now return multiple deduplicated variants,
accept an optional `h1` to reject verbatim restates, and share a
uniform `{ variants: [...] }` output shape. The prompts were rewritten
with front-loading, filler-opener bans, and few-shot SERP examples,
pinned by a golden test.

## Summary

- **New**: Both meta agents accept `n` (default `5`, clamped to
  `[1, 10]`) and return that many deduplicated variants (#94).
- **New**: Both meta agents accept an optional `h1` and drop any
  variant that restates it verbatim (#94).
- **Breaking**: `MetaDescriptionAgent` output is now
  `{ variants: [...] }`. The old single-object shape is gone.
- **Changed**: Meta prompts rewritten with a quality pass — front-load
  the specific value, no brand-first titles, no filler openers, active
  voice, few-shot examples. Pinned by a golden test.
- **Changed**: `MetaDescriptionAgent::MIN_LENGTH` raised from `120` to
  `150` so the validator matches the prompt contract.
- **Changed**: `MetaDescriptionSuggestor` Livewire component + view
  render the variants list, matching `MetaTitleSuggestor`.
- **Fixed**: Both agents raise `FeatureError` when zero variants
  survive validation, instead of returning an empty list.
- **Fixed**: Variants that overshoot the length cap are rejected
  outright instead of truncated.

## Breaking: `MetaDescriptionAgent` output shape

`MetaDescriptionAgent` no longer returns the single meta description
object at the top level. It now returns the same
`{ variants: [...] }` shape as `MetaTitleSuggestionAgent`.

### Before (v1.4.x)

```php
$result = app( MetaDescriptionAgent::class )->run( [
	'content'         => $post->body,
	'primary_keyword' => $post->focus_keyword,
] );

$description = $result['output']['meta_description'];
$characters  = $result['output']['character_count'];
$rationale   = $result['output']['rationale'];
```

### After (v1.5.0)

```php
$result = app( MetaDescriptionAgent::class )->run( [
	'content'         => $post->body,
	'primary_keyword' => $post->focus_keyword,
] );

// Single-variant callers: take the first entry.
$first       = $result['output']['variants'][0];
$description = $first['meta_description'];
$characters  = $result['output']['variants'][0]['character_count'];
$rationale   = $result['output']['variants'][0]['rationale'];

// Or iterate to render all variants:
foreach ( $result['output']['variants'] as $variant ) {
	// ...
}
```

The individual variant keys (`meta_description`, `character_count`,
`rationale`) are unchanged — only the top-level shape moved into a
`variants` array.

### API endpoint

`POST /api/seo/ai/suggest-meta-description` reflects the same change.
The `data` envelope now contains a `variants` array:

```json
{
	"data": {
		"variants": [
			{
				"meta_description": "…",
				"character_count": 156,
				"rationale": "Leans on the practical outcome angle."
			}
		]
	},
	"feature_key": "seo.suggest_meta_description"
}
```

## New: `n` and `h1` inputs

Both meta agents now accept two additional inputs:

- **`n`** (int, optional; default `5`, clamped to `[1, 10]`) — how
  many variants to return.
- **`h1`** (string, optional) — the on-page H1. Any variant that
  restates it verbatim is dropped so the meta line adds context
  instead of echoing the visible headline.

```php
$result = app( MetaTitleSuggestionAgent::class )->run( [
	'content'         => $post->body,
	'primary_keyword' => $post->focus_keyword,
	'brand'           => config( 'app.name' ),
	'h1'              => $post->h1,
	'n'               => 8,
] );
```

The `/api/seo/ai/suggest-meta-title` and
`/api/seo/ai/suggest-meta-description` endpoints accept the same
`n` and `h1` fields in the JSON body.

## New: zero-variant `FeatureError`

If every candidate variant fails validation (length window, verbatim
H1 restate, duplicate) both agents now raise a
`\ArtisanPackUI\Ai\Exceptions\FeatureError`. Previously
`MetaDescriptionAgent` would return an empty list silently. Wrap
agent calls if you need to fall back to an author-supplied string:

```php
use ArtisanPackUI\Ai\Exceptions\FeatureError;

try {
	$result = app( MetaDescriptionAgent::class )->run( $input );
} catch ( FeatureError $e ) {
	// Log and fall back to the author-supplied description.
}
```

## Prompt contract change

The prompts themselves were rewritten:

- Front-load the specific value (keyword or benefit) in the first
  three tokens.
- Forbid brand-first titles unless the page is about the brand.
- Ban common filler openers: "Discover…", "Learn…",
  "Ultimate guide…", "Welcome to…".
- Require active voice.
- Include few-shot examples pulled from representative SERP snippets.

If you had pinned exact prompt text in your own tests or fixtures,
update your expected output. The golden file that the package uses
to detect drift lives at
`tests/Feature/Ai/MetaAgentPromptGoldenTest.php`.

## Livewire component change

`MetaDescriptionSuggestor` and its Blade view now render a variants
list (matching `MetaTitleSuggestor`) and dispatch the selected
variant via a browser event instead of populating a single field.
If you had overridden the component's view, republish it or adapt
your override to the new prop shape (`variants` in place of the old
`meta_description` / `character_count` / `rationale` scalars).

## No dependency changes

`v1.5.0` ships with the same dependency floor as `v1.4.0`:

- `php: ^8.2`
- `illuminate/support: ^11.0|^12.0|^13.0`
- `artisanpack-ui/core: ^1.0`
- `artisanpack-ui/hooks: ^1.3`
- `artisanpack-ui/ai: ^1.2` (optional dev dependency; required for the
  AI Feature Suite)
