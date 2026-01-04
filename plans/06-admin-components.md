# Admin Components

**Purpose:** Define Livewire components for the admin interface
**Last Updated:** January 3, 2026

---

## Overview

The SEO package provides a full suite of Livewire components for admin interfaces. These components are designed to be:

1. **Composable**: Use individual components or the full suite
2. **Stylable**: Works with any CSS framework (optimized for daisyUI/Tailwind)
3. **Accessible**: WCAG 2.1 AA compliant
4. **Real-time**: Live preview and analysis updates

### Component Hierarchy

```
Full Admin Components
├── SeoMetaEditor          # Complete SEO editing panel
├── SeoAnalysisPanel       # Analysis results and scoring
├── RedirectManager        # Full redirect management
├── SitemapManager         # Sitemap generation and preview
└── SeoSettings            # Global SEO configuration

Partial/Composable Components
├── MetaPreview            # Google SERP preview
├── SocialPreview          # Social sharing preview
├── FocusKeywordInput      # Keyword input with analysis
├── SchemaEditor           # Schema type selection
├── HreflangEditor         # Multi-language URL editor
└── RedirectEditor         # Single redirect form
```

---

## SeoMetaEditor

The main SEO editing component for content forms.

```php
<?php

namespace ArtisanPackUI\SEO\Http\Livewire;

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\SeoService;
use ArtisanPackUI\SEO\Services\AnalysisService;
use Livewire\Component;
use Illuminate\Database\Eloquent\Model;

class SeoMetaEditor extends Component
{
    public Model $model;
    public ?SeoMeta $seoMeta = null;

    // Basic Meta
    public string $metaTitle = '';
    public string $metaDescription = '';
    public string $canonicalUrl = '';
    public bool $noIndex = false;
    public bool $noFollow = false;

    // Focus Keyword
    public string $focusKeyword = '';
    public array $secondaryKeywords = [];

    // Open Graph
    public string $ogTitle = '';
    public string $ogDescription = '';
    public ?string $ogImage = null;
    public ?int $ogImageId = null;
    public string $ogType = 'website';

    // Twitter
    public string $twitterCard = 'summary_large_image';
    public string $twitterTitle = '';
    public string $twitterDescription = '';

    // Schema
    public ?string $schemaType = null;

    // Sitemap
    public float $sitemapPriority = 0.5;
    public string $sitemapChangefreq = 'weekly';
    public bool $excludeFromSitemap = false;

    // UI State
    public string $activeTab = 'basic';
    public bool $showAdvanced = false;
    public array $analysisResult = [];

    protected $listeners = [
        'mediaSelected' => 'handleMediaSelected',
        'refreshAnalysis' => 'runAnalysis',
    ];

    public function mount(Model $model): void
    {
        $this->model = $model;
        $this->seoMeta = $model->seoMeta;
        $this->loadFromSeoMeta();
        $this->runAnalysis();
    }

    protected function loadFromSeoMeta(): void
    {
        if (!$this->seoMeta) {
            // Set defaults from model
            $this->metaTitle = $this->model->title ?? '';
            $this->canonicalUrl = $this->model->getUrl ?? url($this->model->slug ?? '');
            return;
        }

        $this->metaTitle = $this->seoMeta->meta_title ?? '';
        $this->metaDescription = $this->seoMeta->meta_description ?? '';
        $this->canonicalUrl = $this->seoMeta->canonical_url ?? '';
        $this->noIndex = $this->seoMeta->no_index ?? false;
        $this->noFollow = $this->seoMeta->no_follow ?? false;
        $this->focusKeyword = $this->seoMeta->focus_keyword ?? '';
        $this->secondaryKeywords = $this->seoMeta->secondary_keywords ?? [];
        $this->ogTitle = $this->seoMeta->og_title ?? '';
        $this->ogDescription = $this->seoMeta->og_description ?? '';
        $this->ogImage = $this->seoMeta->og_image;
        $this->ogImageId = $this->seoMeta->og_image_id;
        $this->ogType = $this->seoMeta->og_type ?? 'website';
        $this->twitterCard = $this->seoMeta->twitter_card ?? 'summary_large_image';
        $this->twitterTitle = $this->seoMeta->twitter_title ?? '';
        $this->twitterDescription = $this->seoMeta->twitter_description ?? '';
        $this->schemaType = $this->seoMeta->schema_type;
        $this->sitemapPriority = $this->seoMeta->sitemap_priority ?? 0.5;
        $this->sitemapChangefreq = $this->seoMeta->sitemap_changefreq ?? 'weekly';
        $this->excludeFromSitemap = $this->seoMeta->exclude_from_sitemap ?? false;
    }

    public function save(): void
    {
        $this->validate([
            'metaTitle' => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string|max:500',
            'canonicalUrl' => 'nullable|url|max:500',
            'focusKeyword' => 'nullable|string|max:255',
            'ogTitle' => 'nullable|string|max:255',
            'ogDescription' => 'nullable|string|max:500',
            'sitemapPriority' => 'numeric|min:0|max:1',
        ]);

        $data = [
            'meta_title' => $this->metaTitle ?: null,
            'meta_description' => $this->metaDescription ?: null,
            'canonical_url' => $this->canonicalUrl ?: null,
            'no_index' => $this->noIndex,
            'no_follow' => $this->noFollow,
            'focus_keyword' => $this->focusKeyword ?: null,
            'secondary_keywords' => $this->secondaryKeywords,
            'og_title' => $this->ogTitle ?: null,
            'og_description' => $this->ogDescription ?: null,
            'og_image' => $this->ogImage,
            'og_image_id' => $this->ogImageId,
            'og_type' => $this->ogType,
            'twitter_card' => $this->twitterCard,
            'twitter_title' => $this->twitterTitle ?: null,
            'twitter_description' => $this->twitterDescription ?: null,
            'schema_type' => $this->schemaType,
            'sitemap_priority' => $this->sitemapPriority,
            'sitemap_changefreq' => $this->sitemapChangefreq,
            'exclude_from_sitemap' => $this->excludeFromSitemap,
        ];

        $this->seoMeta = app(SeoService::class)->updateSeoMeta($this->model, $data);

        $this->dispatch('seo-saved');
        $this->runAnalysis();
    }

    public function runAnalysis(): void
    {
        $result = app(AnalysisService::class)->analyze(
            $this->model,
            $this->focusKeyword ?: null
        );

        $this->analysisResult = $result->toArray();
    }

    public function updated($property): void
    {
        // Debounced analysis on key fields
        if (in_array($property, ['focusKeyword', 'metaTitle', 'metaDescription'])) {
            $this->runAnalysis();
        }
    }

    public function handleMediaSelected(array $media, string $context): void
    {
        if ($context === 'og_image') {
            $this->ogImageId = $media['id'];
            $this->ogImage = $media['url'];
        }
    }

    public function openMediaLibrary(string $context): void
    {
        $this->dispatch('open-media-modal', context: $context);
    }

    public function removeOgImage(): void
    {
        $this->ogImage = null;
        $this->ogImageId = null;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('seo::livewire.seo-meta-editor', [
            'tabs' => $this->getTabs(),
            'schemaTypes' => $this->getSchemaTypes(),
            'ogTypes' => $this->getOgTypes(),
            'changefreqOptions' => $this->getChangefreqOptions(),
        ]);
    }

    protected function getTabs(): array
    {
        return [
            'basic' => __('Basic SEO'),
            'social' => __('Social'),
            'schema' => __('Schema'),
            'advanced' => __('Advanced'),
        ];
    }

    protected function getSchemaTypes(): array
    {
        return [
            '' => __('Auto-detect'),
            'article' => __('Article'),
            'blogposting' => __('Blog Post'),
            'product' => __('Product'),
            'service' => __('Service'),
            'event' => __('Event'),
            'localbusiness' => __('Local Business'),
            'faqpage' => __('FAQ Page'),
            'webpage' => __('Web Page'),
        ];
    }

    protected function getOgTypes(): array
    {
        return [
            'website' => __('Website'),
            'article' => __('Article'),
            'product' => __('Product'),
            'profile' => __('Profile'),
        ];
    }

    protected function getChangefreqOptions(): array
    {
        return [
            'always' => __('Always'),
            'hourly' => __('Hourly'),
            'daily' => __('Daily'),
            'weekly' => __('Weekly'),
            'monthly' => __('Monthly'),
            'yearly' => __('Yearly'),
            'never' => __('Never'),
        ];
    }
}
```

### View Template

```blade
{{-- resources/views/livewire/seo-meta-editor.blade.php --}}
<div class="seo-meta-editor">
    {{-- Tab Navigation --}}
    <x-artisanpack-tabs wire:model="activeTab">
        @foreach($tabs as $key => $label)
            <x-artisanpack-tab name="{{ $key }}" label="{{ $label }}" />
        @endforeach
    </x-artisanpack-tabs>

    {{-- Basic SEO Tab --}}
    @if($activeTab === 'basic')
        <div class="space-y-4">
            {{-- Focus Keyword --}}
            <livewire:seo::focus-keyword-input
                :keyword="$focusKeyword"
                :analysis="$analysisResult"
                @updated="$refresh"
            />

            {{-- Meta Title --}}
            <x-artisanpack-input
                wire:model.live.debounce.500ms="metaTitle"
                label="{{ __('Meta Title') }}"
                placeholder="{{ $model->title ?? __('Enter meta title...') }}"
                hint="{{ strlen($metaTitle) }}/60"
                :error="strlen($metaTitle) > 60 ? __('Title is too long (maximum 60 characters)') : null"
            />

            {{-- Meta Description --}}
            <x-artisanpack-textarea
                wire:model.live.debounce.500ms="metaDescription"
                label="{{ __('Meta Description') }}"
                placeholder="{{ __('Enter a compelling description for search results...') }}"
                hint="{{ strlen($metaDescription) }}/160"
                rows="3"
                :error="strlen($metaDescription) > 160 ? __('Description is too long (maximum 160 characters)') : null"
            />

            {{-- SERP Preview --}}
            <livewire:seo::meta-preview
                :title="$metaTitle ?: ($model->title ?? '')"
                :description="$metaDescription"
                :url="$canonicalUrl ?: url($model->slug ?? '')"
            />

            {{-- Analysis Panel --}}
            <livewire:seo::seo-analysis-panel :analysis="$analysisResult" />
        </div>
    @endif

    {{-- Social Tab --}}
    @if($activeTab === 'social')
        <div class="space-y-6">
            {{-- Open Graph --}}
            <x-artisanpack-card>
                <x-slot:header>{{ __('Open Graph (Facebook, LinkedIn)') }}</x-slot:header>

                <div class="grid gap-4">
                    <x-artisanpack-input
                        wire:model="ogTitle"
                        label="{{ __('OG Title') }}"
                        placeholder="{{ $metaTitle ?: $model->title ?? '' }}"
                    />

                    <x-artisanpack-textarea
                        wire:model="ogDescription"
                        label="{{ __('OG Description') }}"
                        placeholder="{{ $metaDescription }}"
                    />

                    <div>
                        <label class="label"><span class="label-text">{{ __('OG Image') }}</span></label>
                        @if($ogImage)
                            <div class="relative inline-block">
                                <img src="{{ $ogImage }}" alt="{{ __('Open Graph preview image') }}" class="w-48 h-auto rounded">
                                <x-artisanpack-button
                                    wire:click="removeOgImage"
                                    color="error"
                                    size="xs"
                                    circle
                                    class="absolute -top-2 -right-2"
                                    icon="o-x-mark"
                                />
                            </div>
                        @else
                            <x-artisanpack-button
                                wire:click="openMediaLibrary('og_image')"
                                outline
                                size="sm"
                                icon="o-photo"
                            >
                                {{ __('Select Image') }}
                            </x-artisanpack-button>
                        @endif
                    </div>

                    <x-artisanpack-select
                        wire:model="ogType"
                        label="{{ __('OG Type') }}"
                        :options="$ogTypes"
                    />
                </div>
            </x-artisanpack-card>

            {{-- Twitter Card --}}
            <x-artisanpack-card>
                <x-slot:header>{{ __('Twitter Card') }}</x-slot:header>

                <div class="grid gap-4">
                    <x-artisanpack-select
                        wire:model="twitterCard"
                        label="{{ __('Card Type') }}"
                        :options="[
                            'summary' => __('Summary'),
                            'summary_large_image' => __('Summary with Large Image'),
                        ]"
                    />

                    <x-artisanpack-input
                        wire:model="twitterTitle"
                        label="{{ __('Twitter Title') }}"
                        placeholder="{{ $ogTitle ?: $metaTitle ?: '' }}"
                    />

                    <x-artisanpack-textarea
                        wire:model="twitterDescription"
                        label="{{ __('Twitter Description') }}"
                        placeholder="{{ $ogDescription ?: $metaDescription }}"
                    />
                </div>
            </x-artisanpack-card>

            {{-- Social Preview --}}
            <livewire:seo::social-preview
                :title="$ogTitle ?: $metaTitle ?: ($model->title ?? '')"
                :description="$ogDescription ?: $metaDescription"
                :image="$ogImage"
                :url="$canonicalUrl ?: url($model->slug ?? '')"
            />
        </div>
    @endif

    {{-- Schema Tab --}}
    @if($activeTab === 'schema')
        <div class="space-y-4">
            <x-artisanpack-select
                wire:model="schemaType"
                label="{{ __('Schema Type') }}"
                :options="$schemaTypes"
                hint="{{ __('The schema type helps search engines understand your content better.') }}"
            />

            {{-- Schema Preview --}}
            <x-artisanpack-code language="json">
                {{ json_encode($this->getSchemaPreview(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
            </x-artisanpack-code>
        </div>
    @endif

    {{-- Advanced Tab --}}
    @if($activeTab === 'advanced')
        <div class="space-y-6">
            {{-- Robots --}}
            <x-artisanpack-card>
                <x-slot:header>{{ __('Indexing') }}</x-slot:header>

                <div class="space-y-4">
                    <x-artisanpack-checkbox
                        wire:model="noIndex"
                        label="{{ __('No Index') }}"
                        hint="{{ __('Prevent this page from appearing in search results') }}"
                    />

                    <x-artisanpack-checkbox
                        wire:model="noFollow"
                        label="{{ __('No Follow') }}"
                        hint="{{ __('Prevent search engines from following links on this page') }}"
                    />
                </div>
            </x-artisanpack-card>

            {{-- Canonical URL --}}
            <x-artisanpack-input
                wire:model="canonicalUrl"
                type="url"
                label="{{ __('Canonical URL') }}"
                placeholder="{{ url($model->slug ?? '') }}"
                hint="{{ __('Set a canonical URL if this content exists at multiple URLs.') }}"
            />

            {{-- Sitemap --}}
            <x-artisanpack-card>
                <x-slot:header>{{ __('Sitemap') }}</x-slot:header>

                <div class="space-y-4">
                    <x-artisanpack-checkbox
                        wire:model="excludeFromSitemap"
                        label="{{ __('Exclude from sitemap') }}"
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <x-artisanpack-range
                            wire:model="sitemapPriority"
                            label="{{ __('Priority') }}"
                            min="0"
                            max="1"
                            step="0.1"
                            :value="$sitemapPriority"
                        />

                        <x-artisanpack-select
                            wire:model="sitemapChangefreq"
                            label="{{ __('Change Frequency') }}"
                            :options="$changefreqOptions"
                        />
                    </div>
                </div>
            </x-artisanpack-card>
        </div>
    @endif

    {{-- Save Button --}}
    <div class="mt-6 flex justify-end">
        <x-artisanpack-button wire:click="save" color="primary">
            <span wire:loading.remove wire:target="save">{{ __('Save SEO Settings') }}</span>
            <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
        </x-artisanpack-button>
    </div>
</div>
```

---

## SeoAnalysisPanel

Displays SEO analysis results.

```php
<?php

namespace ArtisanPackUI\SEO\Http\Livewire;

use Livewire\Component;

class SeoAnalysisPanel extends Component
{
    public array $analysis = [];
    public bool $expanded = false;

    public function toggle(): void
    {
        $this->expanded = !$this->expanded;
    }

    public function render()
    {
        return view('seo::livewire.seo-analysis-panel');
    }
}
```

### View Template

```blade
{{-- resources/views/livewire/seo-analysis-panel.blade.php --}}
<x-artisanpack-card class="seo-analysis-panel">
    {{-- Score Header --}}
    <div class="flex items-center justify-between cursor-pointer" wire:click="toggle">
        <div class="flex items-center gap-3">
            {{-- Score Circle --}}
            <x-artisanpack-progress
                type="radial"
                :value="$analysis['overall_score'] ?? 0"
                :color="$this->getScoreColor()"
                size="sm"
            />

            <div>
                <h4 class="font-medium">{{ __('SEO Score') }}</h4>
                <p class="text-sm text-base-content/70">
                    {{ $this->getScoreLabel() }}
                </p>
            </div>
        </div>

        <x-artisanpack-icon name="o-chevron-down" class="w-5 h-5 transition-transform {{ $expanded ? 'rotate-180' : '' }}" />
    </div>

    {{-- Expanded Details --}}
    @if($expanded)
        <div class="mt-4 space-y-4">
            {{-- Category Scores --}}
            <div class="grid grid-cols-2 gap-2">
                @foreach([
                    'readability' => __('Readability'),
                    'keyword' => __('Keywords'),
                    'meta' => __('Meta Tags'),
                    'content' => __('Content'),
                ] as $key => $label)
                    <div class="flex items-center gap-2">
                        <x-artisanpack-progress
                            :value="$analysis[$key . '_score'] ?? 0"
                            :color="$this->getCategoryColor($key)"
                            size="xs"
                        />
                        <span class="text-xs whitespace-nowrap">{{ $label }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Issues --}}
            @if(!empty($analysis['issues']))
                <div>
                    <h5 class="text-sm font-medium text-error flex items-center gap-1 mb-2">
                        <x-artisanpack-icon name="o-exclamation-circle" class="w-4 h-4" />
                        {{ __('Issues') }} ({{ count($analysis['issues']) }})
                    </h5>
                    <ul class="space-y-1">
                        @foreach($analysis['issues'] as $issue)
                            <li class="text-sm text-base-content/80 flex items-start gap-2">
                                <span class="text-error mt-1">•</span>
                                {{ $issue['message'] }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Suggestions --}}
            @if(!empty($analysis['suggestions']))
                <div>
                    <h5 class="text-sm font-medium text-warning flex items-center gap-1 mb-2">
                        <x-artisanpack-icon name="o-light-bulb" class="w-4 h-4" />
                        {{ __('Suggestions') }} ({{ count($analysis['suggestions']) }})
                    </h5>
                    <ul class="space-y-1">
                        @foreach($analysis['suggestions'] as $suggestion)
                            <li class="text-sm text-base-content/80 flex items-start gap-2">
                                <span class="text-warning mt-1">•</span>
                                {{ $suggestion['message'] }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Passed Checks --}}
            @if(!empty($analysis['passed_checks']))
                <div>
                    <h5 class="text-sm font-medium text-success flex items-center gap-1 mb-2">
                        <x-artisanpack-icon name="o-check-circle" class="w-4 h-4" />
                        {{ __('Passed') }} ({{ count($analysis['passed_checks']) }})
                    </h5>
                    <ul class="space-y-1">
                        @foreach($analysis['passed_checks'] as $check)
                            <li class="text-sm text-base-content/80 flex items-start gap-2">
                                <span class="text-success mt-1">✓</span>
                                {{ $check }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
</x-artisanpack-card>
```

---

## RedirectManager

Full redirect management interface.

```php
<?php

namespace ArtisanPackUI\SEO\Http\Livewire;

use ArtisanPackUI\SEO\Models\Redirect;
use ArtisanPackUI\SEO\Services\RedirectService;
use Livewire\Component;
use Livewire\WithPagination;

class RedirectManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public string $filterMatchType = '';
    public string $sortField = 'hits';
    public string $sortDirection = 'desc';

    public bool $showEditor = false;
    public ?Redirect $editing = null;

    // Form fields
    public string $fromPath = '';
    public string $toPath = '';
    public int $statusCode = 301;
    public string $matchType = 'exact';
    public string $notes = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterMatchType' => ['except' => ''],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function create(): void
    {
        $this->reset(['fromPath', 'toPath', 'statusCode', 'matchType', 'notes']);
        $this->statusCode = 301;
        $this->matchType = 'exact';
        $this->editing = null;
        $this->showEditor = true;
    }

    public function edit(Redirect $redirect): void
    {
        $this->editing = $redirect;
        $this->fromPath = $redirect->from_path;
        $this->toPath = $redirect->to_path;
        $this->statusCode = $redirect->status_code;
        $this->matchType = $redirect->match_type;
        $this->notes = $redirect->notes ?? '';
        $this->showEditor = true;
    }

    public function save(): void
    {
        $this->validate([
            'fromPath' => 'required|string|max:500',
            'toPath' => 'required|string|max:500',
            'statusCode' => 'required|in:301,302,307,308',
            'matchType' => 'required|in:exact,regex,wildcard',
        ]);

        $data = [
            'from_path' => $this->fromPath,
            'to_path' => $this->toPath,
            'status_code' => $this->statusCode,
            'match_type' => $this->matchType,
            'notes' => $this->notes ?: null,
        ];

        if ($this->editing) {
            app(RedirectService::class)->update($this->editing, $data);
            $this->dispatch('notify', message: __('Redirect updated successfully'));
        } else {
            app(RedirectService::class)->create($data);
            $this->dispatch('notify', message: __('Redirect created successfully'));
        }

        $this->showEditor = false;
        $this->editing = null;
    }

    public function delete(Redirect $redirect): void
    {
        app(RedirectService::class)->delete($redirect);
        $this->dispatch('notify', message: __('Redirect deleted'));
    }

    public function toggleActive(Redirect $redirect): void
    {
        $redirect->update(['is_active' => !$redirect->is_active]);
    }

    public function checkChains(): void
    {
        $issues = app(RedirectService::class)->checkAllForChains();
        $this->dispatch('notify', message: __('Found :count redirect chain issues', ['count' => $issues->count()]));
    }

    public function getRedirectsProperty()
    {
        return Redirect::query()
            ->when($this->search, fn($q) => $q->where('from_path', 'like', "%{$this->search}%")
                ->orWhere('to_path', 'like', "%{$this->search}%"))
            ->when($this->filterStatus === 'active', fn($q) => $q->active())
            ->when($this->filterStatus === 'inactive', fn($q) => $q->where('is_active', false))
            ->when($this->filterStatus === 'issues', fn($q) => $q->hasIssues())
            ->when($this->filterMatchType, fn($q) => $q->where('match_type', $this->filterMatchType))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(20);
    }

    public function render()
    {
        return view('seo::livewire.redirect-manager', [
            'redirects' => $this->redirects,
            'statistics' => app(RedirectService::class)->getStatistics(),
        ]);
    }
}
```

---

## MetaPreview

Google SERP preview component.

```php
<?php

namespace ArtisanPackUI\SEO\Http\Livewire\Partials;

use Livewire\Component;

class MetaPreview extends Component
{
    public string $title = '';
    public string $description = '';
    public string $url = '';

    public function render()
    {
        return view('seo::livewire.partials.meta-preview');
    }
}
```

### View Template

```blade
{{-- resources/views/livewire/partials/meta-preview.blade.php --}}
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
            {{ \Illuminate\Support\Str::limit($description ?: __('Add a meta description to see it here...'), 160) }}
        </p>
    </div>
</x-artisanpack-card>
```

---

## Component Registration

```php
<?php

// In SEOServiceProvider boot method

public function boot(): void
{
    // Register Livewire components
    Livewire::component('seo::seo-meta-editor', SeoMetaEditor::class);
    Livewire::component('seo::seo-analysis-panel', SeoAnalysisPanel::class);
    Livewire::component('seo::redirect-manager', RedirectManager::class);
    Livewire::component('seo::redirect-editor', RedirectEditor::class);
    Livewire::component('seo::sitemap-manager', SitemapManager::class);
    Livewire::component('seo::seo-settings', SeoSettings::class);
    Livewire::component('seo::meta-preview', Partials\MetaPreview::class);
    Livewire::component('seo::social-preview', Partials\SocialPreview::class);
    Livewire::component('seo::focus-keyword-input', Partials\FocusKeywordInput::class);
    Livewire::component('seo::schema-editor', Partials\SchemaEditor::class);
    Livewire::component('seo::hreflang-editor', Partials\HreflangEditor::class);
}
```

---

## Usage Examples

### In a Page Editor

```blade
{{-- In your page edit form --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        {{-- Main content editor --}}
    </div>

    <div class="space-y-6">
        {{-- SEO Panel --}}
        <livewire:seo::seo-meta-editor :model="$page" />
    </div>
</div>
```

### Standalone Redirect Manager

```blade
{{-- In your admin routes --}}
<livewire:seo::redirect-manager />
```

### Just the Analysis Panel

```blade
{{-- When you only need analysis --}}
<livewire:seo::seo-analysis-panel :analysis="$page->seoAnalysis->toArray()" />
```

---

## Related Documents

- [05-seo-analysis.md](05-seo-analysis.md) - Analysis implementation
- [07-blade-components.md](07-blade-components.md) - Output components
- [08-integrations.md](08-integrations.md) - Media library integration
