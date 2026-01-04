# Hreflang Service

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::Low"

## Task Description

Create the hreflang service for multi-language SEO support.

## Acceptance Criteria

- [ ] `HreflangService` class for managing alternate language URLs
- [ ] Database column for storing hreflang data in seo_meta
- [ ] `HreflangEditor` Livewire component for admin UI
- [ ] Blade component `x-seo:hreflang` for output
- [ ] Support for language and region codes (e.g., en-US, fr-FR)
- [ ] Support for x-default
- [ ] Validation of hreflang format
- [ ] All strings wrapped in `__()` for translation
- [ ] Uses `<x-artisanpack-*>` components in editor
- [ ] Unit and feature tests

## Context

Hreflang tags help search engines serve the correct language version of pages.

**Related Issues:**
- Depends on: Phase 1 completion

## Notes

### Database Addition
```php
// Add to seo_meta migration or create separate
$table->json('hreflang_urls')->nullable();
```

### HreflangService
```php
class HreflangService
{
    public function getHreflangTags(Model $model): array;
    public function setAlternateUrl(SeoMeta $seoMeta, string $locale, string $url): void;
    public function removeAlternateUrl(SeoMeta $seoMeta, string $locale): void;
    public function validateLocale(string $locale): bool;
}
```

### Blade Component Output
```blade
{{-- x-seo:hreflang --}}
@foreach($hreflangUrls as $locale => $url)
    <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}" />
@endforeach
@if($defaultUrl)
    <link rel="alternate" hreflang="x-default" href="{{ $defaultUrl }}" />
@endif
```

### HreflangEditor Component
```blade
<x-artisanpack-card>
    <x-slot:header>{{ __('Alternate Language URLs') }}</x-slot:header>

    @foreach($hreflangUrls as $index => $item)
        <div class="flex gap-2">
            <x-artisanpack-select
                wire:model="hreflangUrls.{{ $index }}.locale"
                :options="$availableLocales"
            />
            <x-artisanpack-input
                wire:model="hreflangUrls.{{ $index }}.url"
                placeholder="{{ __('URL for this language') }}"
            />
            <x-artisanpack-button wire:click="removeHreflang({{ $index }})" color="error" size="sm" icon="o-trash" />
        </div>
    @endforeach

    <x-artisanpack-button wire:click="addHreflang" size="sm" icon="o-plus">
        {{ __('Add Language') }}
    </x-artisanpack-button>
</x-artisanpack-card>
```

**Reference:** [03-core-services.md](../03-core-services.md)
