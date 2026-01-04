# SocialPreview Livewire Component

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::Medium"

## Task Description

Create the social media sharing preview component showing how content will appear on Facebook/Twitter.

## Acceptance Criteria

- [ ] Facebook/LinkedIn preview card layout
- [ ] Twitter card preview layout
- [ ] Displays OG image with proper aspect ratio (1200x630)
- [ ] Displays OG title and description
- [ ] Shows domain/URL
- [ ] Toggle between Facebook and Twitter preview
- [ ] Placeholder image when no image selected
- [ ] All strings wrapped in `__()` for translation
- [ ] Uses `<x-artisanpack-*>` components throughout
- [ ] Livewire tests for rendering

## Context

This is a composable sub-component used within SeoMetaEditor on the Social tab.

**Related Issues:**
- Depends on: Phase 1 completion
- Used by: #03-03-seo-meta-editor

## Notes

### Component Structure
```php
namespace ArtisanPackUI\SEO\Http\Livewire\Partials;

class SocialPreview extends Component
{
    public string $title = '';
    public string $description = '';
    public ?string $image = null;
    public string $url = '';
    public string $platform = 'facebook'; // facebook, twitter

    public function setPlatform(string $platform): void;
    public function render();
}
```

### View Template
```blade
<x-artisanpack-card class="social-preview">
    {{-- Platform Toggle --}}
    <x-artisanpack-tabs wire:model="platform" size="sm">
        <x-artisanpack-tab name="facebook" label="{{ __('Facebook') }}" />
        <x-artisanpack-tab name="twitter" label="{{ __('Twitter') }}" />
    </x-artisanpack-tabs>

    {{-- Facebook Preview --}}
    @if($platform === 'facebook')
        <div class="border rounded-lg overflow-hidden">
            @if($image)
                <img src="{{ $image }}" class="w-full h-auto aspect-[1200/630] object-cover" />
            @else
                <div class="w-full aspect-[1200/630] bg-base-200 flex items-center justify-center">
                    <span class="text-base-content/50">{{ __('No image selected') }}</span>
                </div>
            @endif
            <div class="p-3 bg-base-200">
                <p class="text-xs text-base-content/60 uppercase">{{ parse_url($url, PHP_URL_HOST) }}</p>
                <h4 class="font-semibold line-clamp-2">{{ $title }}</h4>
                <p class="text-sm text-base-content/70 line-clamp-2">{{ $description }}</p>
            </div>
        </div>
    @endif
</x-artisanpack-card>
```

**Reference:** [06-admin-components.md](../06-admin-components.md)
