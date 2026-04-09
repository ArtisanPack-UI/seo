---
title: Frontend Scaffolding (React & Vue)
---

# Frontend Scaffolding (React & Vue)

> Added in v1.1.0

ArtisanPack UI SEO provides publishable React and Vue components for building custom SEO admin interfaces in JavaScript frontends. These components mirror the functionality of the Livewire components but are designed for use with Inertia.js, standalone SPAs, or any JavaScript-driven admin panel.

## Installation

Use the `seo:install-frontend` Artisan command to publish components to your application:

```bash
# Interactive stack selection
php artisan seo:install-frontend

# Install React components
php artisan seo:install-frontend --stack=react

# Install Vue components
php artisan seo:install-frontend --stack=vue

# Overwrite previously published files
php artisan seo:install-frontend --stack=react --force
```

The command publishes both the framework-specific components and shared TypeScript type definitions.

## Published Files

### React (`--stack=react`)

```
resources/js/
├── vendor/seo/react/
│   ├── index.ts                    # Main entry point
│   ├── hooks/
│   │   ├── index.ts
│   │   ├── useApi.ts               # Base API helper
│   │   ├── useSeoMeta.ts           # SEO metadata CRUD
│   │   ├── useSeoAnalysis.ts       # Content analysis
│   │   └── useRedirects.ts         # Redirect management
│   └── components/admin/
│       ├── index.ts
│       ├── SeoMetaEditor.tsx       # Full tabbed SEO editor
│       ├── BasicMetaTab.tsx        # Title, description, robots
│       ├── OpenGraphTab.tsx        # Open Graph fields
│       ├── TwitterCardTab.tsx      # Twitter Card fields
│       ├── SchemaTab.tsx           # Schema type selector + fields
│       ├── HreflangTab.tsx         # Multi-language URLs
│       ├── SitemapTab.tsx          # Sitemap settings
│       ├── MetaPreview.tsx         # Google SERP preview
│       ├── SocialPreview.tsx       # Social share preview
│       ├── SeoAnalysisPanel.tsx    # Analysis scores + suggestions
│       ├── RedirectManager.tsx     # Redirect CRUD table
│       └── SeoDashboard.tsx        # Overview statistics
└── types/seo/
    ├── index.d.ts                  # Main type exports
    ├── meta-tags.d.ts
    ├── open-graph.d.ts
    ├── twitter-card.d.ts
    ├── schema.d.ts
    ├── hreflang.d.ts
    ├── redirect.d.ts
    ├── analysis.d.ts
    ├── seo-data.d.ts
    └── components.d.ts
```

### Vue (`--stack=vue`)

```
resources/js/
├── vendor/seo/vue/
│   ├── index.ts                    # Main entry point
│   ├── composables/
│   │   ├── index.ts
│   │   ├── useApi.ts               # Base API helper
│   │   ├── useSeoMeta.ts           # SEO metadata CRUD
│   │   ├── useSeoAnalysis.ts       # Content analysis
│   │   └── useRedirects.ts         # Redirect management
│   └── components/admin/
│       ├── index.ts
│       ├── SeoMetaEditor.vue       # Full tabbed SEO editor
│       ├── BasicMetaTab.vue        # Title, description, robots
│       ├── OpenGraphTab.vue        # Open Graph fields
│       ├── TwitterCardTab.vue      # Twitter Card fields
│       ├── SchemaTab.vue           # Schema type selector + fields
│       ├── HreflangTab.vue         # Multi-language URLs
│       ├── SitemapTab.vue          # Sitemap settings
│       ├── MetaPreview.vue         # Google SERP preview
│       ├── SocialPreview.vue       # Social share preview
│       ├── SeoAnalysisPanel.vue    # Analysis scores + suggestions
│       ├── RedirectManager.vue     # Redirect CRUD table
│       └── SeoDashboard.vue        # Overview statistics
└── types/seo/                      # (same as React)
```

## Usage

### React

```tsx
import {
    SeoMetaEditor,
    SeoDashboard,
    RedirectManager,
    SeoAnalysisPanel,
} from '@/vendor/seo/react';

function PostEditor({ modelType, modelId }: { modelType: string; modelId: number }) {
    return (
        <div>
            <SeoMetaEditor modelType={modelType} modelId={modelId} />
            <SeoAnalysisPanel modelType={modelType} modelId={modelId} />
        </div>
    );
}
```

### Vue

```vue
<script setup lang="ts">
import {
    SeoMetaEditor,
    SeoDashboard,
    RedirectManager,
    SeoAnalysisPanel,
} from '@/vendor/seo/vue';

const props = defineProps<{
    modelType: string;
    modelId: number;
}>();
</script>

<template>
    <div>
        <SeoMetaEditor :model-type="modelType" :model-id="modelId" />
        <SeoAnalysisPanel :model-type="modelType" :model-id="modelId" />
    </div>
</template>
```

## Available Components

### Admin Components

| Component | Description |
|-----------|-------------|
| `SeoMetaEditor` | Full tabbed SEO editing interface (meta, OG, Twitter, schema, hreflang, sitemap) |
| `BasicMetaTab` | Title, description, canonical URL, and robots directive fields |
| `OpenGraphTab` | Open Graph title, description, image, and type fields |
| `TwitterCardTab` | Twitter Card type, title, description, and image fields |
| `SchemaTab` | Schema type selector with dynamic field rendering based on the [Schema Type Definitions API](Usage-Schema#schema-type-definitions-api) |
| `HreflangTab` | Multi-language alternate URL editor |
| `SitemapTab` | Sitemap priority, change frequency, and exclusion settings |
| `MetaPreview` | Google search result preview (SERP) |
| `SocialPreview` | Facebook/Twitter share preview |
| `SeoAnalysisPanel` | SEO content analysis scores and improvement suggestions |
| `RedirectManager` | URL redirect CRUD interface with search, filtering, and bulk operations |
| `SeoDashboard` | SEO statistics overview with counts and health indicators |

### Hooks (React) / Composables (Vue)

| Hook / Composable | Description |
|-------------------|-------------|
| `useApi` | Base HTTP client for the SEO API endpoints |
| `useSeoMeta` | Fetch and update SEO metadata for a model |
| `useSeoAnalysis` | Run and retrieve content analysis results |
| `useRedirects` | CRUD operations for URL redirects with pagination |

## TypeScript Types

The shared type definitions provide full type safety for all SEO data structures:

```typescript
import type {
    SeoMeta,
    OpenGraphData,
    TwitterCardData,
    SchemaData,
    HreflangEntry,
    RedirectData,
    AnalysisResult,
    SeoData,
} from '@/types/seo';
```

## API Integration

The frontend components communicate with the SEO package via its REST API endpoints. Ensure the API routes are enabled in your configuration:

```php
// config/seo.php
'api' => [
    'enabled' => true,
    'prefix' => 'api/seo',
    'middleware' => ['api', 'auth:sanctum'],
],
```

### API Endpoints Used

| Endpoint | Used By |
|----------|---------|
| `GET /api/seo/meta/{type}/{id}` | `useSeoMeta`, `SeoMetaEditor` |
| `PUT /api/seo/meta/{type}/{id}` | `useSeoMeta`, `SeoMetaEditor` |
| `GET /api/seo/meta/{type}/{id}/preview` | `MetaPreview`, `SocialPreview` |
| `GET /api/seo/schema/types` | `SchemaTab` |
| `GET /api/seo/schema/{type}/{id}` | `SchemaTab` |
| `PUT /api/seo/schema/{type}/{id}` | `SchemaTab` |
| `POST /api/seo/analysis/analyze` | `useSeoAnalysis`, `SeoAnalysisPanel` |
| `GET /api/seo/analysis/{type}/{id}` | `useSeoAnalysis`, `SeoAnalysisPanel` |
| `GET /api/seo/redirects` | `useRedirects`, `RedirectManager` |
| `POST /api/seo/redirects` | `useRedirects`, `RedirectManager` |
| `PUT /api/seo/redirects/{id}` | `useRedirects`, `RedirectManager` |
| `DELETE /api/seo/redirects/{id}` | `useRedirects`, `RedirectManager` |

## Customization

Since the components are published directly into your application, you can freely modify them to match your design system. The published files are fully yours to customize.

### Styling

The components use minimal default styling to be compatible with any CSS framework. Add your own Tailwind classes, CSS modules, or styled-components as needed.

### Extending Hooks / Composables

The `useApi` hook/composable provides a foundation that `useSeoMeta`, `useSeoAnalysis`, and `useRedirects` build upon. You can create additional hooks following the same pattern for custom functionality.

## Next Steps

- [Schema Type Definitions API](Usage-Schema#schema-type-definitions-api) - Dynamic schema form rendering
- [API Overview](Api) - Full API reference
- [Components Overview](Components) - All available components (Blade, Livewire, React, Vue)
