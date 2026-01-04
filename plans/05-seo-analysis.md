# SEO Analysis System

**Purpose:** Define the advanced SEO analysis features including readability, keyword density, and scoring
**Last Updated:** January 3, 2026

---

## Overview

The SEO analysis system provides Yoast-style content analysis with actionable recommendations. It evaluates content across multiple dimensions and provides an overall score.

### Analysis Categories

| Category | Weight | Analyzers |
|----------|--------|-----------|
| **Readability** | 25% | Flesch-Kincaid, sentence length, paragraph length |
| **Keyword Usage** | 30% | Density, placement, focus keyword |
| **Meta Tags** | 20% | Title length, description length, keyword in meta |
| **Content** | 25% | Word count, headings, images, internal links |

---

## AnalysisService

The main orchestrator for all analysis operations.

```php
<?php

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\DTOs\AnalysisResultDTO;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Models\SeoAnalysisCache;
use ArtisanPackUI\SEO\Services\Analysis\ReadabilityAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\KeywordDensityAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\FocusKeywordAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\MetaLengthAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\HeadingStructureAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\ImageAltAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\InternalLinkAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\ContentLengthAnalyzer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AnalysisService
{
    protected array $analyzers = [];
    protected array $analyzerWeights = [
        'readability' => 25,
        'keyword' => 30,
        'meta' => 20,
        'content' => 25,
    ];

    public function __construct(
        protected ReadabilityAnalyzer $readabilityAnalyzer,
        protected KeywordDensityAnalyzer $keywordDensityAnalyzer,
        protected FocusKeywordAnalyzer $focusKeywordAnalyzer,
        protected MetaLengthAnalyzer $metaLengthAnalyzer,
        protected HeadingStructureAnalyzer $headingAnalyzer,
        protected ImageAltAnalyzer $imageAltAnalyzer,
        protected InternalLinkAnalyzer $internalLinkAnalyzer,
        protected ContentLengthAnalyzer $contentLengthAnalyzer,
    ) {
        $this->analyzers = [
            'readability' => $this->readabilityAnalyzer,
            'keyword_density' => $this->keywordDensityAnalyzer,
            'focus_keyword' => $this->focusKeywordAnalyzer,
            'meta_length' => $this->metaLengthAnalyzer,
            'heading_structure' => $this->headingAnalyzer,
            'image_alt' => $this->imageAltAnalyzer,
            'internal_links' => $this->internalLinkAnalyzer,
            'content_length' => $this->contentLengthAnalyzer,
        ];
    }

    /**
     * Run full analysis on a model.
     */
    public function analyze(Model $model, ?string $focusKeyword = null): AnalysisResultDTO
    {
        $content = $this->extractContent($model);
        $seoMeta = $model->seoMeta;
        $focusKeyword = $focusKeyword ?? $seoMeta?->focus_keyword;

        $results = [];
        $issues = [];
        $suggestions = [];
        $passedChecks = [];

        // Run each analyzer
        foreach ($this->analyzers as $name => $analyzer) {
            $result = $analyzer->analyze($model, $content, $focusKeyword, $seoMeta);

            $results[$name] = $result;

            // Collect issues and suggestions
            $issues = array_merge($issues, $result['issues'] ?? []);
            $suggestions = array_merge($suggestions, $result['suggestions'] ?? []);
            $passedChecks = array_merge($passedChecks, $result['passed'] ?? []);
        }

        // Calculate category scores
        $readabilityScore = $this->calculateCategoryScore($results, 'readability');
        $keywordScore = $this->calculateCategoryScore($results, 'keyword');
        $metaScore = $this->calculateCategoryScore($results, 'meta');
        $contentScore = $this->calculateCategoryScore($results, 'content');

        // Calculate overall score
        $overallScore = $this->calculateOverallScore([
            'readability' => $readabilityScore,
            'keyword' => $keywordScore,
            'meta' => $metaScore,
            'content' => $contentScore,
        ]);

        // Create result DTO
        $analysisResult = new AnalysisResultDTO(
            overallScore: $overallScore,
            readabilityScore: $readabilityScore,
            keywordScore: $keywordScore,
            metaScore: $metaScore,
            contentScore: $contentScore,
            issues: $issues,
            suggestions: $suggestions,
            passedChecks: $passedChecks,
            focusKeyword: $focusKeyword,
            wordCount: str_word_count(strip_tags($content)),
            analyzerResults: $results,
        );

        // Cache the results
        $this->cacheResults($model, $analysisResult);

        return $analysisResult;
    }

    /**
     * Extract content from model.
     */
    protected function extractContent(Model $model): string
    {
        return $model->content
            ?? $model->body
            ?? $model->description
            ?? '';
    }

    /**
     * Calculate score for a category.
     */
    protected function calculateCategoryScore(array $results, string $category): int
    {
        $categoryAnalyzers = match ($category) {
            'readability' => ['readability'],
            'keyword' => ['keyword_density', 'focus_keyword'],
            'meta' => ['meta_length'],
            'content' => ['heading_structure', 'image_alt', 'internal_links', 'content_length'],
            default => [],
        };

        $scores = [];
        foreach ($categoryAnalyzers as $analyzer) {
            if (isset($results[$analyzer]['score'])) {
                $scores[] = $results[$analyzer]['score'];
            }
        }

        if (empty($scores)) {
            return 0;
        }

        return (int) round(array_sum($scores) / count($scores));
    }

    /**
     * Calculate overall weighted score.
     */
    protected function calculateOverallScore(array $categoryScores): int
    {
        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($categoryScores as $category => $score) {
            $weight = $this->analyzerWeights[$category] ?? 0;
            $weightedSum += $score * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight === 0) {
            return 0;
        }

        return (int) round($weightedSum / $totalWeight);
    }

    /**
     * Cache analysis results.
     */
    protected function cacheResults(Model $model, AnalysisResultDTO $result): void
    {
        $seoMeta = $model->seoMeta ?? $model->getOrCreateSeoMeta();

        SeoAnalysisCache::updateOrCreate(
            ['seo_meta_id' => $seoMeta->id],
            [
                'overall_score' => $result->overallScore,
                'readability_score' => $result->readabilityScore,
                'keyword_score' => $result->keywordScore,
                'meta_score' => $result->metaScore,
                'content_score' => $result->contentScore,
                'issues' => $result->issues,
                'suggestions' => $result->suggestions,
                'passed_checks' => $result->passedChecks,
                'analyzed_at' => now(),
                'focus_keyword_used' => $result->focusKeyword,
                'content_word_count' => $result->wordCount,
            ]
        );
    }

    /**
     * Register a custom analyzer.
     */
    public function registerAnalyzer(string $name, $analyzer): void
    {
        $this->analyzers[$name] = $analyzer;
    }
}
```

---

## Individual Analyzers

### ReadabilityAnalyzer

Calculates Flesch-Kincaid readability score and related metrics.

```php
<?php

namespace ArtisanPackUI\SEO\Services\Analysis;

use ArtisanPackUI\SEO\Contracts\AnalyzerContract;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

class ReadabilityAnalyzer implements AnalyzerContract
{
    protected const IDEAL_SENTENCE_LENGTH = 20;
    protected const MAX_SENTENCE_LENGTH = 25;
    protected const IDEAL_PARAGRAPH_LENGTH = 150;
    protected const MAX_PARAGRAPH_LENGTH = 200;

    public function analyze(Model $model, string $content, ?string $focusKeyword, ?SeoMeta $seoMeta): array
    {
        $text = strip_tags($content);
        $issues = [];
        $suggestions = [];
        $passed = [];

        // Flesch-Kincaid calculations
        $sentences = $this->getSentences($text);
        $words = $this->getWords($text);
        $syllables = $this->countSyllables($text);

        $sentenceCount = count($sentences);
        $wordCount = count($words);

        if ($wordCount === 0 || $sentenceCount === 0) {
            return [
                'score' => 0,
                'issues' => [['type' => 'error', 'message' => 'No content to analyze']],
                'suggestions' => [],
                'passed' => [],
            ];
        }

        // Flesch Reading Ease score (0-100, higher is easier)
        $avgSentenceLength = $wordCount / $sentenceCount;
        $avgSyllablesPerWord = $syllables / $wordCount;
        $fleschScore = 206.835 - (1.015 * $avgSentenceLength) - (84.6 * $avgSyllablesPerWord);
        $fleschScore = max(0, min(100, $fleschScore));

        // Flesch-Kincaid Grade Level
        $gradeLevel = (0.39 * $avgSentenceLength) + (11.8 * $avgSyllablesPerWord) - 15.59;

        // Check sentence length
        $longSentences = array_filter($sentences, fn($s) => str_word_count($s) > self::MAX_SENTENCE_LENGTH);
        $longSentencePercent = count($longSentences) / $sentenceCount * 100;

        if ($longSentencePercent > 25) {
            $issues[] = [
                'type' => 'warning',
                'message' => sprintf(
                    '%.0f%% of sentences are too long. Try to keep sentences under %d words.',
                    $longSentencePercent,
                    self::MAX_SENTENCE_LENGTH
                ),
            ];
        } else {
            $passed[] = 'Sentence length is appropriate';
        }

        // Check paragraph length
        $paragraphs = $this->getParagraphs($content);
        $longParagraphs = array_filter($paragraphs, fn($p) => str_word_count(strip_tags($p)) > self::MAX_PARAGRAPH_LENGTH);

        if (count($longParagraphs) > 0) {
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => sprintf(
                    '%d paragraph(s) are too long. Consider breaking them up for better readability.',
                    count($longParagraphs)
                ),
            ];
        } else {
            $passed[] = 'Paragraph length is appropriate';
        }

        // Interpret Flesch score
        if ($fleschScore >= 60) {
            $passed[] = sprintf('Good readability score: %.1f (Easy to read)', $fleschScore);
        } elseif ($fleschScore >= 30) {
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => sprintf(
                    'Readability score is %.1f (Fairly difficult). Consider simplifying your content.',
                    $fleschScore
                ),
            ];
        } else {
            $issues[] = [
                'type' => 'warning',
                'message' => sprintf(
                    'Readability score is %.1f (Very difficult). Your content may be hard for many readers.',
                    $fleschScore
                ),
            ];
        }

        // Convert Flesch score to 0-100 analysis score
        $score = (int) round($fleschScore);

        return [
            'score' => $score,
            'issues' => $issues,
            'suggestions' => $suggestions,
            'passed' => $passed,
            'details' => [
                'flesch_reading_ease' => round($fleschScore, 1),
                'flesch_kincaid_grade' => round($gradeLevel, 1),
                'avg_sentence_length' => round($avgSentenceLength, 1),
                'avg_syllables_per_word' => round($avgSyllablesPerWord, 2),
                'long_sentence_percent' => round($longSentencePercent, 1),
            ],
        ];
    }

    protected function getSentences(string $text): array
    {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_filter(array_map('trim', $sentences));
    }

    protected function getWords(string $text): array
    {
        return str_word_count($text, 1);
    }

    protected function getParagraphs(string $content): array
    {
        // Split by paragraph tags or double newlines
        $paragraphs = preg_split('/<\/p>|<br\s*\/?>\s*<br\s*\/?>|\n\n/', $content);
        return array_filter(array_map('trim', $paragraphs));
    }

    protected function countSyllables(string $text): int
    {
        $words = $this->getWords(strtolower($text));
        $count = 0;

        foreach ($words as $word) {
            $count += $this->countWordSyllables($word);
        }

        return $count;
    }

    protected function countWordSyllables(string $word): int
    {
        $word = preg_replace('/[^a-z]/', '', strtolower($word));

        if (strlen($word) <= 3) {
            return 1;
        }

        // Count vowel groups
        $count = preg_match_all('/[aeiouy]+/', $word);

        // Subtract silent e
        if (preg_match('/e$/', $word)) {
            $count--;
        }

        return max(1, $count);
    }

    public function getName(): string
    {
        return 'readability';
    }

    public function getWeight(): int
    {
        return 25;
    }
}
```

### KeywordDensityAnalyzer

Analyzes keyword density and placement.

```php
<?php

namespace ArtisanPackUI\SEO\Services\Analysis;

use ArtisanPackUI\SEO\Contracts\AnalyzerContract;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

class KeywordDensityAnalyzer implements AnalyzerContract
{
    protected const MIN_DENSITY = 0.5;
    protected const MAX_DENSITY = 2.5;
    protected const IDEAL_DENSITY = 1.5;

    public function analyze(Model $model, string $content, ?string $focusKeyword, ?SeoMeta $seoMeta): array
    {
        if (!$focusKeyword) {
            return [
                'score' => 0,
                'issues' => [['type' => 'warning', 'message' => 'No focus keyword set']],
                'suggestions' => [['type' => 'suggestion', 'message' => 'Set a focus keyword to analyze keyword density']],
                'passed' => [],
            ];
        }

        $text = strtolower(strip_tags($content));
        $keyword = strtolower(trim($focusKeyword));
        $wordCount = str_word_count($text);

        if ($wordCount === 0) {
            return [
                'score' => 0,
                'issues' => [['type' => 'error', 'message' => 'No content to analyze']],
                'suggestions' => [],
                'passed' => [],
            ];
        }

        // Count keyword occurrences
        $keywordCount = substr_count($text, $keyword);
        $keywordWordCount = str_word_count($keyword);
        $density = ($keywordCount * $keywordWordCount / $wordCount) * 100;

        $issues = [];
        $suggestions = [];
        $passed = [];

        // Evaluate density
        if ($density < self::MIN_DENSITY) {
            $issues[] = [
                'type' => 'warning',
                'message' => sprintf(
                    'Keyword density is too low (%.2f%%). Aim for %.1f-%.1f%%.',
                    $density,
                    self::MIN_DENSITY,
                    self::MAX_DENSITY
                ),
            ];
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => sprintf(
                    'Try to include "%s" more naturally in your content.',
                    $focusKeyword
                ),
            ];
        } elseif ($density > self::MAX_DENSITY) {
            $issues[] = [
                'type' => 'warning',
                'message' => sprintf(
                    'Keyword density is too high (%.2f%%). This may be seen as keyword stuffing.',
                    $density
                ),
            ];
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => 'Consider using synonyms or related terms instead.',
            ];
        } else {
            $passed[] = sprintf('Good keyword density: %.2f%%', $density);
        }

        // Check keyword in first paragraph
        $paragraphs = preg_split('/<\/p>|\n\n/', $content);
        $firstParagraph = strtolower(strip_tags($paragraphs[0] ?? ''));

        if (str_contains($firstParagraph, $keyword)) {
            $passed[] = 'Focus keyword appears in the first paragraph';
        } else {
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => 'Include your focus keyword in the first paragraph.',
            ];
        }

        // Calculate score
        $score = $this->calculateDensityScore($density);

        return [
            'score' => $score,
            'issues' => $issues,
            'suggestions' => $suggestions,
            'passed' => $passed,
            'details' => [
                'keyword' => $focusKeyword,
                'occurrences' => $keywordCount,
                'density' => round($density, 2),
                'word_count' => $wordCount,
            ],
        ];
    }

    protected function calculateDensityScore(float $density): int
    {
        if ($density >= self::MIN_DENSITY && $density <= self::MAX_DENSITY) {
            // Perfect range
            $deviation = abs($density - self::IDEAL_DENSITY);
            $maxDeviation = max(self::IDEAL_DENSITY - self::MIN_DENSITY, self::MAX_DENSITY - self::IDEAL_DENSITY);
            return (int) round(100 - ($deviation / $maxDeviation * 20));
        }

        if ($density < self::MIN_DENSITY) {
            return (int) round(($density / self::MIN_DENSITY) * 60);
        }

        // Over max density
        $overAmount = $density - self::MAX_DENSITY;
        return (int) max(0, round(60 - ($overAmount * 20)));
    }

    public function getName(): string
    {
        return 'keyword_density';
    }

    public function getWeight(): int
    {
        return 15;
    }
}
```

### FocusKeywordAnalyzer

Checks focus keyword placement in key locations.

```php
<?php

namespace ArtisanPackUI\SEO\Services\Analysis;

use ArtisanPackUI\SEO\Contracts\AnalyzerContract;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

class FocusKeywordAnalyzer implements AnalyzerContract
{
    public function analyze(Model $model, string $content, ?string $focusKeyword, ?SeoMeta $seoMeta): array
    {
        if (!$focusKeyword) {
            return [
                'score' => 0,
                'issues' => [],
                'suggestions' => [['type' => 'suggestion', 'message' => 'Set a focus keyword for better SEO analysis']],
                'passed' => [],
            ];
        }

        $keyword = strtolower(trim($focusKeyword));
        $issues = [];
        $suggestions = [];
        $passed = [];
        $checksTotal = 0;
        $checksPassed = 0;

        // Check in meta title
        $checksTotal++;
        $metaTitle = strtolower($seoMeta?->meta_title ?? $model->title ?? '');
        if (str_contains($metaTitle, $keyword)) {
            $passed[] = 'Focus keyword appears in the meta title';
            $checksPassed++;
        } else {
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => 'Include your focus keyword in the meta title.',
            ];
        }

        // Check in meta description
        $checksTotal++;
        $metaDescription = strtolower($seoMeta?->meta_description ?? '');
        if (str_contains($metaDescription, $keyword)) {
            $passed[] = 'Focus keyword appears in the meta description';
            $checksPassed++;
        } else {
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => 'Include your focus keyword in the meta description.',
            ];
        }

        // Check in URL/slug
        $checksTotal++;
        $slug = strtolower($model->slug ?? '');
        $keywordSlug = \Illuminate\Support\Str::slug($keyword);
        if (str_contains($slug, $keywordSlug)) {
            $passed[] = 'Focus keyword appears in the URL';
            $checksPassed++;
        } else {
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => 'Consider including your focus keyword in the URL slug.',
            ];
        }

        // Check in H1
        $checksTotal++;
        preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $content, $h1Match);
        $h1Content = strtolower($h1Match[1] ?? $model->title ?? '');
        if (str_contains($h1Content, $keyword)) {
            $passed[] = 'Focus keyword appears in H1 heading';
            $checksPassed++;
        } else {
            $issues[] = [
                'type' => 'warning',
                'message' => 'Focus keyword not found in H1 heading.',
            ];
        }

        // Check in subheadings (H2-H6)
        $checksTotal++;
        preg_match_all('/<h[2-6][^>]*>(.*?)<\/h[2-6]>/si', $content, $subheadings);
        $subheadingText = strtolower(implode(' ', $subheadings[1] ?? []));
        if (str_contains($subheadingText, $keyword)) {
            $passed[] = 'Focus keyword appears in subheadings';
            $checksPassed++;
        } else {
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => 'Use your focus keyword in at least one subheading.',
            ];
        }

        // Check in image alt text
        $checksTotal++;
        preg_match_all('/alt=["\']([^"\']*)["\']/', $content, $altTexts);
        $allAltText = strtolower(implode(' ', $altTexts[1] ?? []));
        if (str_contains($allAltText, $keyword)) {
            $passed[] = 'Focus keyword appears in image alt text';
            $checksPassed++;
        } else {
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => 'Include your focus keyword in at least one image alt text.',
            ];
        }

        // Calculate score
        $score = $checksTotal > 0 ? (int) round(($checksPassed / $checksTotal) * 100) : 0;

        return [
            'score' => $score,
            'issues' => $issues,
            'suggestions' => $suggestions,
            'passed' => $passed,
            'details' => [
                'checks_total' => $checksTotal,
                'checks_passed' => $checksPassed,
                'keyword' => $focusKeyword,
            ],
        ];
    }

    public function getName(): string
    {
        return 'focus_keyword';
    }

    public function getWeight(): int
    {
        return 15;
    }
}
```

### MetaLengthAnalyzer

Checks optimal lengths for meta title and description.

```php
<?php

namespace ArtisanPackUI\SEO\Services\Analysis;

use ArtisanPackUI\SEO\Contracts\AnalyzerContract;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

class MetaLengthAnalyzer implements AnalyzerContract
{
    protected const TITLE_MIN = 30;
    protected const TITLE_MAX = 60;
    protected const TITLE_IDEAL = 55;

    protected const DESC_MIN = 120;
    protected const DESC_MAX = 160;
    protected const DESC_IDEAL = 155;

    public function analyze(Model $model, string $content, ?string $focusKeyword, ?SeoMeta $seoMeta): array
    {
        $issues = [];
        $suggestions = [];
        $passed = [];
        $score = 100;

        // Get meta title
        $metaTitle = $seoMeta?->meta_title ?? $model->title ?? '';
        $titleLength = strlen($metaTitle);

        // Analyze title length
        if (empty($metaTitle)) {
            $issues[] = [
                'type' => 'error',
                'message' => 'Meta title is missing.',
            ];
            $score -= 25;
        } elseif ($titleLength < self::TITLE_MIN) {
            $issues[] = [
                'type' => 'warning',
                'message' => sprintf(
                    'Meta title is too short (%d characters). Aim for %d-%d characters.',
                    $titleLength,
                    self::TITLE_MIN,
                    self::TITLE_MAX
                ),
            ];
            $score -= 15;
        } elseif ($titleLength > self::TITLE_MAX) {
            $issues[] = [
                'type' => 'warning',
                'message' => sprintf(
                    'Meta title is too long (%d characters). It may be truncated in search results.',
                    $titleLength
                ),
            ];
            $score -= 10;
        } else {
            $passed[] = sprintf('Meta title length is good (%d characters)', $titleLength);
        }

        // Get meta description
        $metaDescription = $seoMeta?->meta_description ?? '';
        $descLength = strlen($metaDescription);

        // Analyze description length
        if (empty($metaDescription)) {
            $issues[] = [
                'type' => 'warning',
                'message' => 'Meta description is missing.',
            ];
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => 'Add a meta description to improve click-through rates from search results.',
            ];
            $score -= 25;
        } elseif ($descLength < self::DESC_MIN) {
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => sprintf(
                    'Meta description is short (%d characters). Aim for %d-%d characters.',
                    $descLength,
                    self::DESC_MIN,
                    self::DESC_MAX
                ),
            ];
            $score -= 10;
        } elseif ($descLength > self::DESC_MAX) {
            $issues[] = [
                'type' => 'warning',
                'message' => sprintf(
                    'Meta description is too long (%d characters). It may be truncated.',
                    $descLength
                ),
            ];
            $score -= 10;
        } else {
            $passed[] = sprintf('Meta description length is good (%d characters)', $descLength);
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'passed' => $passed,
            'details' => [
                'title_length' => $titleLength,
                'title_ideal' => self::TITLE_IDEAL,
                'description_length' => $descLength,
                'description_ideal' => self::DESC_IDEAL,
            ],
        ];
    }

    public function getName(): string
    {
        return 'meta_length';
    }

    public function getWeight(): int
    {
        return 20;
    }
}
```

### ContentLengthAnalyzer

Checks minimum word count for content.

```php
<?php

namespace ArtisanPackUI\SEO\Services\Analysis;

use ArtisanPackUI\SEO\Contracts\AnalyzerContract;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

class ContentLengthAnalyzer implements AnalyzerContract
{
    protected const MIN_WORDS = 300;
    protected const GOOD_WORDS = 600;
    protected const EXCELLENT_WORDS = 1000;

    public function analyze(Model $model, string $content, ?string $focusKeyword, ?SeoMeta $seoMeta): array
    {
        $text = strip_tags($content);
        $wordCount = str_word_count($text);

        $issues = [];
        $suggestions = [];
        $passed = [];

        if ($wordCount < self::MIN_WORDS) {
            $issues[] = [
                'type' => 'warning',
                'message' => sprintf(
                    'Content is too short (%d words). Aim for at least %d words.',
                    $wordCount,
                    self::MIN_WORDS
                ),
            ];
            $score = (int) round(($wordCount / self::MIN_WORDS) * 50);
        } elseif ($wordCount < self::GOOD_WORDS) {
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => sprintf(
                    'Content length is acceptable (%d words). Consider expanding to %d+ words for better ranking.',
                    $wordCount,
                    self::GOOD_WORDS
                ),
            ];
            $score = 60 + (int) round((($wordCount - self::MIN_WORDS) / (self::GOOD_WORDS - self::MIN_WORDS)) * 20);
        } elseif ($wordCount < self::EXCELLENT_WORDS) {
            $passed[] = sprintf('Good content length: %d words', $wordCount);
            $score = 80 + (int) round((($wordCount - self::GOOD_WORDS) / (self::EXCELLENT_WORDS - self::GOOD_WORDS)) * 15);
        } else {
            $passed[] = sprintf('Excellent content length: %d words', $wordCount);
            $score = 95 + min(5, (int) round(($wordCount - self::EXCELLENT_WORDS) / 500));
        }

        return [
            'score' => min(100, $score),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'passed' => $passed,
            'details' => [
                'word_count' => $wordCount,
                'min_words' => self::MIN_WORDS,
                'good_words' => self::GOOD_WORDS,
                'excellent_words' => self::EXCELLENT_WORDS,
            ],
        ];
    }

    public function getName(): string
    {
        return 'content_length';
    }

    public function getWeight(): int
    {
        return 10;
    }
}
```

---

## AnalysisResultDTO

Data transfer object for analysis results.

```php
<?php

namespace ArtisanPackUI\SEO\DTOs;

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

    public function getGrade(): string
    {
        return match (true) {
            $this->overallScore >= 80 => 'good',
            $this->overallScore >= 50 => 'ok',
            default => 'poor',
        };
    }

    public function getGradeColor(): string
    {
        return match ($this->getGrade()) {
            'good' => 'green',
            'ok' => 'yellow',
            'poor' => 'red',
        };
    }

    public function getIssueCount(): int
    {
        return count($this->issues);
    }

    public function getSuggestionCount(): int
    {
        return count($this->suggestions);
    }

    public function getPassedCount(): int
    {
        return count($this->passedChecks);
    }

    public function toArray(): array
    {
        return [
            'overall_score' => $this->overallScore,
            'grade' => $this->getGrade(),
            'readability_score' => $this->readabilityScore,
            'keyword_score' => $this->keywordScore,
            'meta_score' => $this->metaScore,
            'content_score' => $this->contentScore,
            'issues' => $this->issues,
            'suggestions' => $this->suggestions,
            'passed_checks' => $this->passedChecks,
            'focus_keyword' => $this->focusKeyword,
            'word_count' => $this->wordCount,
        ];
    }
}
```

---

## Related Documents

- [03-core-services.md](03-core-services.md) - AnalysisService integration
- [04-traits-and-models.md](04-traits-and-models.md) - HasSeoAnalysis trait
- [06-admin-components.md](06-admin-components.md) - Analysis UI components
