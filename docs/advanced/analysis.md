---
title: SEO Analysis
---

# SEO Analysis

ArtisanPack UI SEO includes a content analysis system with 8 built-in analyzers to help optimize your content for search engines.

## Overview

The analysis system evaluates content against SEO best practices and provides:
- Overall SEO score (0-100)
- Individual analyzer results
- Actionable recommendations
- Caching for performance

## Configuration

```php
// In config/seo.php
'analysis' => [
    'enabled' => true,
    'queue' => false,
    'cache' => true,
    'cache_ttl' => 86400,
    'analyzers' => [
        'readability' => ['enabled' => true, 'max_grade' => 8],
        'keyword_density' => ['enabled' => true, 'min' => 1, 'max' => 3],
        'focus_keyword' => ['enabled' => true],
        'meta_length' => ['enabled' => true],
        'heading_structure' => ['enabled' => true],
        'image_alt' => ['enabled' => true],
        'internal_links' => ['enabled' => true, 'min' => 2],
        'content_length' => ['enabled' => true, 'min' => 300],
    ],
],
```

| Option | Description |
|--------|-------------|
| `enabled` | Enable/disable analysis |
| `queue` | Run analysis in background |
| `cache` | Cache analysis results |
| `cache_ttl` | Cache duration in seconds |
| `analyzers` | Individual analyzer settings |

## Running Analysis

### Using the Model

```php
// Get analysis results
$results = $post->getSeoAnalysis();

// Force refresh
$results = $post->refreshSeoAnalysis();
```

### Using Helper

```php
$results = seoAnalyze($post);
$score = seoScore($post);
```

### Using the Service

```php
use ArtisanPackUI\Seo\Services\AnalysisService;

$analysisService = app('seo.analysis');

// Full analysis
$results = $analysisService->analyze($post);

// Single analyzer
$readability = $analysisService->runAnalyzer('readability', $post);
```

## Result Structure

```php
$results = [
    'score' => 75,
    'readability' => [
        'status' => 'pass',        // pass, warning, fail
        'title' => 'Readability',
        'message' => 'Grade level: 7.2 (target: ≤8)',
        'score' => 100,
        'data' => ['grade' => 7.2],
    ],
    'keyword_density' => [
        'status' => 'warning',
        'title' => 'Keyword Density',
        'message' => 'Focus keyword density is 0.5% (recommended: 1-3%)',
        'score' => 50,
        'data' => ['density' => 0.5],
    ],
    // ... more analyzers
];
```

## Built-in Analyzers

### Readability

Analyzes content readability using Flesch-Kincaid grade level.

```php
'readability' => [
    'enabled' => true,
    'max_grade' => 8,  // Target grade level
],
```

| Grade | Reading Level |
|-------|---------------|
| 5-6 | Elementary |
| 7-8 | Middle School |
| 9-10 | High School |
| 11-12 | College |
| 13+ | Graduate |

### Keyword Density

Checks focus keyword usage percentage.

```php
'keyword_density' => [
    'enabled' => true,
    'min' => 1,   // Minimum percentage
    'max' => 3,   // Maximum percentage
],
```

### Focus Keyword

Checks focus keyword presence in key locations.

```php
'focus_keyword' => [
    'enabled' => true,
    'check_title' => true,
    'check_description' => true,
    'check_content' => true,
    'check_url' => true,
],
```

Locations checked:
- Meta title
- Meta description
- First paragraph
- Headings
- URL slug

### Meta Length

Validates title and description length.

```php
'meta_length' => [
    'enabled' => true,
    'title_min' => 30,
    'title_max' => 60,
    'description_min' => 120,
    'description_max' => 160,
],
```

### Heading Structure

Analyzes H1-H6 heading hierarchy.

```php
'heading_structure' => [
    'enabled' => true,
    'require_h1' => true,
    'max_h1' => 1,
],
```

Checks:
- Exactly one H1
- Proper hierarchy (no skipping levels)
- Headings contain keywords

### Image Alt Text

Checks for missing alt attributes.

```php
'image_alt' => [
    'enabled' => true,
    'min_alt_length' => 5,
],
```

### Internal Links

Counts internal links.

```php
'internal_links' => [
    'enabled' => true,
    'min' => 2,  // Minimum internal links
],
```

### Content Length

Checks minimum word count.

```php
'content_length' => [
    'enabled' => true,
    'min' => 300,        // Minimum words
    'recommended' => 1000,
],
```

## Custom Analyzers

### Creating an Analyzer

```php
namespace App\Seo\Analyzers;

use ArtisanPackUI\Seo\Contracts\AnalyzerInterface;

class CustomAnalyzer implements AnalyzerInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'threshold' => 5,
        ], $config);
    }

    public function analyze($model): array
    {
        $content = $model->content ?? '';
        $value = $this->calculateValue($content);
        $threshold = $this->config['threshold'];

        if ($value >= $threshold) {
            return [
                'status' => 'pass',
                'title' => 'Custom Check',
                'message' => "Value is {$value} (threshold: {$threshold})",
                'score' => 100,
                'data' => ['value' => $value],
            ];
        }

        return [
            'status' => 'fail',
            'title' => 'Custom Check',
            'message' => "Value is {$value}, should be at least {$threshold}",
            'score' => ($value / $threshold) * 100,
            'data' => ['value' => $value],
        ];
    }

    protected function calculateValue(string $content): int
    {
        // Your custom logic
        return 10;
    }
}
```

### Registering Analyzers

```php
// In a service provider
use ArtisanPackUI\Seo\Services\AnalysisService;
use App\Seo\Analyzers\CustomAnalyzer;

public function boot(): void
{
    $analysis = app(AnalysisService::class);

    $analysis->registerAnalyzer('custom', new CustomAnalyzer([
        'threshold' => 10,
    ]));
}
```

### Configuration

```php
'analysis' => [
    'analyzers' => [
        'custom' => [
            'enabled' => true,
            'threshold' => 10,
        ],
    ],
],
```

## Analysis Panel Component

Display analysis results with the Livewire component:

```blade
<livewire:seo-analysis-panel :model="$post" />
```

[See Analysis Panel Component →](Components-Analysis-Panel)

## Queue Processing

For large content, run analysis in the background:

```php
'analysis' => [
    'queue' => true,
    'queue_connection' => 'redis',
    'queue_name' => 'seo',
],
```

## Caching

Analysis results are cached:

```php
// Clear cache for a model
$analysisService->clearCache($post);

// Clear all analysis caches
$analysisService->clearAllCaches();
```

## Events

```php
use ArtisanPackUI\Seo\Events\SeoAnalysisCompleted;

Event::listen(SeoAnalysisCompleted::class, function ($event) {
    Log::info("Analysis completed", [
        'model' => get_class($event->model),
        'score' => $event->score,
    ]);

    if ($event->score < 50) {
        // Notify content editor of poor SEO
    }
});
```

## Bulk Analysis

```php
// Analyze all posts
Post::chunk(100, function ($posts) {
    foreach ($posts as $post) {
        seoAnalyze($post);
    }
});
```

## Score Calculation

The overall score is calculated as:

```php
$totalScore = 0;
$analyzersRun = 0;

foreach ($results as $analyzer => $result) {
    $totalScore += $result['score'];
    $analyzersRun++;
}

$overallScore = round($totalScore / $analyzersRun);
```

Each analyzer returns a score from 0-100.

## Best Practices

1. **Set a focus keyword** - Many analyzers depend on it
2. **Run analysis before publishing** - Catch issues early
3. **Don't over-optimize** - Natural content is better
4. **Use the queue for bulk** - Avoid timeouts
5. **Cache results** - Improve performance

## Next Steps

- [Analysis Panel Component](Components-Analysis-Panel) - Display results
- [Configuration](Installation-Configuration) - Full config reference
- [Caching](Caching) - Cache configuration
