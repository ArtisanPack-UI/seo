# SeoMetaEditor Livewire Component

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::High"

## Task Description

Create the main Livewire component for editing SEO metadata in admin interfaces.

## Acceptance Criteria

- [ ] Tabbed interface: Basic, Social, Schema, Advanced
- [ ] All SeoMeta fields editable
- [ ] Real-time character counts for title/description
- [ ] SERP preview (via MetaPreview sub-component)
- [ ] Social preview (via SocialPreview sub-component)
- [ ] Focus keyword input with analysis trigger
- [ ] Media library integration for OG image selection
- [ ] Save functionality with validation
- [ ] All strings wrapped in `__()` for translation
- [ ] Uses `<x-artisanpack-*>` components throughout
- [ ] Livewire tests for component behavior

## Context

This is the primary admin component developers will use to manage SEO.

**Related Issues:**
- Depends on: Phase 1 & 2 completion
- Related: #03-04-analysis-panel, #03-06-meta-preview

## Notes

### Component Structure
```php
class SeoMetaEditor extends Component
{
    public Model $model;
    public ?SeoMeta $seoMeta = null;

    // Form fields for all SEO data
    public string $metaTitle = '';
    public string $metaDescription = '';
    // ... etc

    public string $activeTab = 'basic';
    public array $analysisResult = [];

    public function save(): void;
    public function runAnalysis(): void;
}
```

### View Uses ArtisanPack Components
```blade
<x-artisanpack-tabs wire:model="activeTab">
    <x-artisanpack-tab name="basic" label="{{ __('Basic SEO') }}" />
</x-artisanpack-tabs>

<x-artisanpack-input
    wire:model.live.debounce.500ms="metaTitle"
    label="{{ __('Meta Title') }}"
/>
```

**Reference:** [06-admin-components.md](../06-admin-components.md)
