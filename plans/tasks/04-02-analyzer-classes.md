# Analyzer Classes

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::High"

## Task Description

Implement the 8 individual analyzer classes that perform specific SEO checks.

## Acceptance Criteria

- [ ] `ReadabilityAnalyzer` - Flesch-Kincaid score, sentence/paragraph length
- [ ] `KeywordDensityAnalyzer` - Keyword density calculation (0.5-2.5% ideal)
- [ ] `FocusKeywordAnalyzer` - Keyword placement in title, description, URL, H1, subheadings, alt text
- [ ] `MetaLengthAnalyzer` - Title (30-60 chars) and description (120-160 chars) length
- [ ] `ContentLengthAnalyzer` - Word count analysis (300 min, 600 good, 1000+ excellent)
- [ ] `HeadingStructureAnalyzer` - H1-H6 hierarchy and usage
- [ ] `ImageAltAnalyzer` - Image alt text presence and quality
- [ ] `InternalLinkAnalyzer` - Internal linking analysis
- [ ] All analyzers implement `AnalyzerContract`
- [ ] Each analyzer returns score, issues, suggestions, passed checks
- [ ] Unit tests for each analyzer

## Context

These analyzers power the SEO score calculations and recommendations.

**Related Issues:**
- Depends on: #04-01-analysis-infrastructure

## Notes

### ReadabilityAnalyzer
```php
class ReadabilityAnalyzer implements AnalyzerContract
{
    protected const IDEAL_SENTENCE_LENGTH = 20;
    protected const MAX_SENTENCE_LENGTH = 25;

    public function analyze(Model $model, string $content, ?string $focusKeyword, ?SeoMeta $seoMeta): array
    {
        // Flesch Reading Ease = 206.835 - (1.015 × ASL) - (84.6 × ASW)
        // ASL = Average Sentence Length
        // ASW = Average Syllables per Word
    }
}
```

### KeywordDensityAnalyzer
```php
class KeywordDensityAnalyzer implements AnalyzerContract
{
    protected const MIN_DENSITY = 0.5;
    protected const MAX_DENSITY = 2.5;
    protected const IDEAL_DENSITY = 1.5;

    // Returns score based on keyword occurrences vs word count
}
```

### FocusKeywordAnalyzer
Checks keyword presence in:
- Meta title
- Meta description
- URL/slug
- H1 heading
- Subheadings (H2-H6)
- Image alt text

**Reference:** [05-seo-analysis.md](../05-seo-analysis.md)
