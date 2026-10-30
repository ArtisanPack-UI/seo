---
title: Events
---

# Events

ArtisanPack UI SEO doesn't dispatch Laravel events. To change what the package outputs, use the [filter hooks](Api-Filter-Hooks). To react to changes in your own data, use your models' events or observers.

The Livewire components do dispatch browser events, which you can listen for from Livewire or Alpine.

## Livewire Events

| Event | Component | Payload |
|-------|-----------|---------|
| `seo-meta-saved` | SEO Meta Editor | `id` |
| `seo-analysis-complete` | SEO Meta Editor | `results` |
| `hreflang-saved` | Hreflang Editor | `count` |
| `hreflang-cleared` | Hreflang Editor | None |
| `notify` | Redirect Manager and others | `message` |
| `seo-ai-title-selected` | AI Meta Title Suggestor | `title` |
| `seo-ai-description-selected` | AI Meta Description Suggestor | `description` |
| `seo-ai-schema-selected` | AI Schema Suggestor | `type` |

The AI components listen for `seo-ai-context-updated`, and the SEO Meta Editor listens for `media-selected` from the media library.

```blade
<div x-data x-on:seo-meta-saved.window="console.log($event.detail.id)">
    <livewire:seo::seo-meta-editor :model="$post" />
</div>
```

## Next Steps

- [Filter Hooks](Api-Filter-Hooks) - Change values before they're rendered
- [Services](Api-Services) - Service documentation
- [Helper Functions](Api-Helpers) - Helper reference
