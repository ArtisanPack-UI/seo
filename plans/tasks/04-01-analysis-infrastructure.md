# Analysis Infrastructure

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::High"

## Task Description

Create the foundational infrastructure for the SEO analysis system including database, DTOs, and contracts.

## Acceptance Criteria

- [ ] Migration creates `seo_analysis_cache` table
- [ ] `SeoAnalysisCache` model with proper relationships
- [ ] `AnalysisResultDTO` data transfer object
- [ ] `AnalyzerContract` interface for all analyzers
- [ ] `AnalysisService` orchestrator class
- [ ] Category weighting system (readability 25%, keyword 30%, meta 20%, content 25%)
- [ ] Results caching mechanism
- [ ] Ability to register custom analyzers
- [ ] Unit tests for DTO and service

## Context

This provides the foundation for the Yoast-style SEO analysis features.

**Related Issues:**
- Depends on: Phase 1 & 2 completion
- Required by: #04-02-analyzer-classes

## Notes

### Table Schema
```php
Schema::create('seo_analysis_cache', function (Blueprint $table) {
    $table->id();
    $table->foreignId('seo_meta_id')->constrained()->cascadeOnDelete();
    $table->unsignedTinyInteger('overall_score')->default(0);
    $table->unsignedTinyInteger('readability_score')->default(0);
    $table->unsignedTinyInteger('keyword_score')->default(0);
    $table->unsignedTinyInteger('meta_score')->default(0);
    $table->unsignedTinyInteger('content_score')->default(0);
    $table->json('issues')->nullable();
    $table->json('suggestions')->nullable();
    $table->json('passed_checks')->nullable();
    $table->timestamp('analyzed_at')->nullable();
    $table->string('focus_keyword_used')->nullable();
    $table->unsignedInteger('content_word_count')->default(0);
    $table->timestamps();
});
```

### AnalyzerContract
```php
interface AnalyzerContract
{
    public function analyze(Model $model, string $content, ?string $focusKeyword, ?SeoMeta $seoMeta): array;
    public function getName(): string;
    public function getWeight(): int;
}
```

### AnalysisResultDTO
```php
class AnalysisResultDTO
{
    public function __construct(
        public readonly int $overallScore,
        public readonly int $readabilityScore,
        public readonly int $keywordScore,
        public readonly int $metaScore,
        public readonly int $contentScore,
        public readonly array $issues,
        public readonly array $suggestions,
        public readonly array $passedChecks,
        public readonly ?string $focusKeyword,
        public readonly int $wordCount,
        public readonly array $analyzerResults = [],
    ) {}

    public function getGrade(): string;
    public function toArray(): array;
}
```

**Reference:** [05-seo-analysis.md](../05-seo-analysis.md)
