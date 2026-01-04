# Visual Editor Integration

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::Low"

## Task Description

Create the optional integration with the `artisanpack-ui/visual-editor` package for pre-publish SEO checks.

## Acceptance Criteria

- [ ] `PackageDetector::hasVisualEditor()` check
- [ ] `VisualEditorIntegration` service class
- [ ] Register pre-publish checks via hooks
- [ ] SEO checks before publishing content
- [ ] Check for missing meta title
- [ ] Check for missing meta description
- [ ] Check for missing focus keyword
- [ ] Check for low SEO score (below 50)
- [ ] Check for missing OG image
- [ ] Info notice when noindex is enabled
- [ ] Run full SEO analysis for editor
- [ ] Graceful handling when visual editor not available
- [ ] Feature tests with mocked visual editor

## Context

This integration adds pre-publish SEO checks to the visual editor workflow, helping content creators ensure SEO best practices before publishing.

**Related Issues:**
- Depends on: #04-01-analysis-infrastructure

## Notes

### VisualEditorIntegration
```php
class VisualEditorIntegration
{
    public function __construct(
        protected AnalysisService $analysisService,
    ) {}

    public function registerPrePublishChecks(): void;
    public function getSeoChecks(Model $page): Collection;
    public function analyzeForEditor(Model $page): array;
}
```

### Pre-Publish Check Registration
```php
public function registerPrePublishChecks(): void
{
    if (!PackageDetector::hasVisualEditor()) {
        return;
    }

    addFilter('visual_editor.pre_publish_checks', function (Collection $checks, Model $page) {
        return $checks->merge($this->getSeoChecks($page));
    });
}
```

### Check Structure
```php
$checks->push([
    'type' => 'warning',        // warning, suggestion, info
    'category' => 'seo',
    'message' => 'Page is missing a meta title',
    'action' => 'Add a meta title for better search visibility',
]);
```

### Service Provider Registration
```php
public function boot(): void
{
    if (PackageDetector::hasVisualEditor()) {
        $integration = app(VisualEditorIntegration::class);
        $integration->registerPrePublishChecks();
    }
}
```

**Reference:** [08-integrations.md](../08-integrations.md)
