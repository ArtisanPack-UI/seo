# SeoAnalysisPanel Livewire Component

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::Medium"

## Task Description

Create the Livewire component that displays SEO analysis results with scoring and recommendations.

## Acceptance Criteria

- [ ] Displays overall SEO score with radial progress indicator
- [ ] Expandable/collapsible panel design
- [ ] Category scores for readability, keywords, meta, content
- [ ] Issues list with error styling
- [ ] Suggestions list with warning styling
- [ ] Passed checks list with success styling
- [ ] Score color coding (good/ok/poor)
- [ ] All strings wrapped in `__()` for translation
- [ ] Uses `<x-artisanpack-*>` components throughout
- [ ] Livewire tests for component behavior

## Context

This component is used inside SeoMetaEditor and can also be used standalone.

**Related Issues:**
- Depends on: Phase 1 & 2 completion
- Related: #03-03-seo-meta-editor

## Notes

### Component Structure
```php
class SeoAnalysisPanel extends Component
{
    public array $analysis = [];
    public bool $expanded = false;

    public function toggle(): void;
    protected function getScoreColor(): string;
    protected function getScoreLabel(): string;
    protected function getCategoryColor(string $key): string;
}
```

### View Uses ArtisanPack Components
```blade
<x-artisanpack-card class="seo-analysis-panel">
    <x-artisanpack-progress
        type="radial"
        :value="$analysis['overall_score'] ?? 0"
        :color="$this->getScoreColor()"
    />
    <x-artisanpack-icon name="o-chevron-down" />
</x-artisanpack-card>
```

**Reference:** [06-admin-components.md](../06-admin-components.md)
