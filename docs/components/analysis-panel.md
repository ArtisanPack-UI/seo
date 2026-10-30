---
title: Analysis Panel
---

# Analysis Panel

The SEO Analysis Panel is a Livewire component that displays content analysis results and SEO recommendations.

## Basic Usage

```blade
<livewire:seo::seo-analysis-panel :model="$post" />
```

## Features

- Overall SEO score
- Individual analyzer results
- Pass/fail indicators
- Actionable recommendations
- Refresh capability

## Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `model` | Model | Yes | Eloquent model with HasSeo trait |
| `showScore` | bool | No | Show overall score |
| `showDetails` | bool | No | Show detailed results |
| `autoRefresh` | bool | No | Auto-refresh on model update |

## Examples

### Standard Usage

```blade
<div class="card">
    <div class="card-header">
        <h3>SEO Analysis</h3>
    </div>
    <div class="card-body">
        <livewire:seo::seo-analysis-panel :model="$post" />
    </div>
</div>
```

### Compact View (Score Only)

```blade
<livewire:seo::seo-analysis-panel
    :model="$post"
    :show-details="false"
/>
```

### With Auto-Refresh

```blade
<livewire:seo::seo-analysis-panel
    :model="$post"
    :auto-refresh="true"
/>
```

## Score Display

The overall score is calculated from all enabled analyzers:

```text
Score: 75/100

███████████░░░░ 75%
```

### Score Ranges

| Range | Status | Color |
|-------|--------|-------|
| 80-100 | Excellent | Green |
| 60-79 | Good | Yellow |
| 40-59 | Needs Work | Orange |
| 0-39 | Poor | Red |

## Analyzer Results

Each analyzer displays:

- **Status** - Pass, Warning, or Fail
- **Title** - Analyzer name
- **Message** - Explanation/recommendation
- **Details** - Additional information (expandable)

### Example Results

```text
✓ Meta Title Length
  Title is 45 characters (recommended: 50-60)

⚠ Keyword Density
  Focus keyword density is 0.5% (recommended: 1-3%)

✗ Image Alt Text
  3 images missing alt text
```

## Available Analyzers

### Readability

Checks content readability using Flesch-Kincaid:

```text
✓ Readability
  Grade level: 7.2 (target: ≤8)
```

### Keyword Density

Checks focus keyword usage:

```text
⚠ Keyword Density
  "laravel seo" appears 0.5% (recommended: 1-3%)
```

### Focus Keyword

Checks keyword placement:

```text
✓ Focus Keyword in Title
  Focus keyword found in title

✗ Focus Keyword in Description
  Focus keyword not found in meta description
```

### Meta Length

Validates title and description length:

```text
✓ Meta Title Length
  45 characters (50-60 recommended)

⚠ Meta Description Length
  180 characters (150-160 recommended)
```

### Heading Structure

Analyzes H1-H6 usage:

```text
✓ Heading Structure
  1 H1, 3 H2s, 5 H3s - Good hierarchy
```

### Image Alt Text

Checks for missing alt attributes:

```text
✗ Image Alt Text
  3 of 5 images missing alt text
```

### Internal Links

Counts internal links:

```text
⚠ Internal Links
  1 internal link (minimum 2 recommended)
```

### Content Length

Checks word count:

```text
✓ Content Length
  1,250 words (minimum 300)
```

## Refresh Analysis

```blade
<livewire:seo::seo-analysis-panel :model="$post">
    <x-slot:actions>
        <button wire:click="refresh">
            Re-analyze
        </button>
    </x-slot:actions>
</livewire:seo::seo-analysis-panel>
```

## Events

| Event | Payload | Description |
|-------|---------|-------------|
| `analysis-complete` | `['score' => int]` | After analysis runs |

## Customization

### Publishing Views

```bash
php artisan vendor:publish --tag=seo-views
```

### Extending the Component

```php
namespace App\Livewire;

use ArtisanPackUI\SEO\Livewire\SeoAnalysisPanel as BaseSeoAnalysisPanel;

class CustomSeoAnalysisPanel extends BaseSeoAnalysisPanel
{
    protected function getCustomAnalyzers(): array
    {
        return [
            'custom_check' => new CustomAnalyzer(),
        ];
    }
}
```

### Custom Analyzer

```php
namespace App\Seo\Analyzers;

use ArtisanPackUI\SEO\Contracts\AnalyzerContract;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

class CustomAnalyzer implements AnalyzerContract
{
    public function analyze(Model $model, string $content, ?string $focusKeyword, ?SeoMeta $seoMeta): array
    {
        $value = $this->calculateValue($content);

        if ($value >= 5) {
            return [
                'score' => 100,
                'issues' => [],
                'suggestions' => [],
                'passed' => ["Value is {$value}."],
                'details' => ['value' => $value],
            ];
        }

        return [
            'score' => $value * 20,
            'issues' => [
                ['type' => 'warning', 'message' => "Value is {$value}, should be at least 5."],
            ],
            'suggestions' => [],
            'passed' => [],
            'details' => ['value' => $value],
        ];
    }

    public function getName(): string
    {
        return 'custom';
    }

    public function getCategory(): string
    {
        return 'content'; // readability, keyword, meta, or content
    }

    public function getWeight(): int
    {
        return 25;
    }

    protected function calculateValue(string $content): int
    {
        // Your custom logic
        return 10;
    }
}
```

See [SEO Analysis](Advanced-Analysis#custom-analyzers) for registration.

## Integration with Editor

The analysis panel is often used alongside the SEO Meta Editor:

```blade
<div class="grid grid-cols-3 gap-6">
    <div class="col-span-2">
        <livewire:seo::seo-meta-editor :model="$post" />
    </div>

    <div>
        <livewire:seo::seo-analysis-panel
            :model="$post"
            :auto-refresh="true"
        />
    </div>
</div>
```

## Caching

Analysis results are cached for performance:

```php
// In config/seo.php
'analysis' => [
    'cache' => true,
    'cache_ttl' => 86400, // 24 hours
],
```

Force refresh clears the cache:

```php
$this->dispatch('refresh-analysis');
```

## Queue Support

For large content, analysis can run in the background:

```php
// In config/seo.php
'analysis' => [
    'queue' => true,
],
```

When queued, the panel shows a loading state until complete.

## Next Steps

- [SEO Analysis](Advanced-Analysis) - Analysis documentation
- [SEO Meta Editor](Seo-Meta-Editor) - Main SEO editor
- [Configuration](Installation-Configuration) - Analyzer settings
