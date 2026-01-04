# MetaPreview Livewire Component

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::Medium"

## Task Description

Create the Google SERP preview component showing how content will appear in search results.

## Acceptance Criteria

- [ ] Displays URL in Google-style green text
- [ ] Displays title in Google-style blue clickable text
- [ ] Displays description with proper truncation
- [ ] Title truncated at 60 characters with ellipsis
- [ ] Description truncated at 160 characters
- [ ] Live updates as user types (via Livewire props)
- [ ] All strings wrapped in `__()` for translation
- [ ] Uses `<x-artisanpack-*>` components throughout
- [ ] Livewire tests for rendering

## Context

This is a composable sub-component used within SeoMetaEditor on the Basic SEO tab.

**Related Issues:**
- Depends on: Phase 1 completion
- Used by: #03-03-seo-meta-editor

## Notes

### Component Structure
```php
namespace ArtisanPackUI\SEO\Http\Livewire\Partials;

class MetaPreview extends Component
{
    public string $title = '';
    public string $description = '';
    public string $url = '';

    public function render();
}
```

### View Template
```blade
<x-artisanpack-card class="meta-preview">
    <p class="text-xs text-gray-500 mb-2">{{ __('Google Search Preview') }}</p>

    <div class="space-y-1">
        {{-- URL --}}
        <div class="text-sm text-green-700 truncate">
            {{ $url }}
        </div>

        {{-- Title --}}
        <h3 class="text-xl text-blue-800 hover:underline cursor-pointer truncate">
            {{ \Illuminate\Support\Str::limit($title ?: __('Page Title'), 60) }}
        </h3>

        {{-- Description --}}
        <p class="text-sm text-gray-600 line-clamp-2">
            {{ \Illuminate\Support\Str::limit($description ?: __('Add a meta description...'), 160) }}
        </p>
    </div>
</x-artisanpack-card>
```

**Reference:** [06-admin-components.md](../06-admin-components.md)
