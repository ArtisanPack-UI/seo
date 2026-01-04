# Media Library Integration

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::Medium"

## Task Description

Create the optional integration with the `artisanpack-ui/media-library` package for OG image selection.

## Acceptance Criteria

- [ ] `PackageDetector` class to check if media-library is installed
- [ ] `MediaLibraryIntegration` service class
- [ ] Get media URL by ID with size options
- [ ] Get social-optimized image URL (1200x630)
- [ ] Register social image size with media library
- [ ] SeoMeta model methods: `getEffectiveOgImage()`, `getEffectiveTwitterImage()`
- [ ] Livewire event handling for media selection
- [ ] Graceful fallback when package not installed
- [ ] Feature tests with mocked media library

## Context

This integration enables selecting OG images from the media library instead of entering URLs manually.

**Related Issues:**
- Depends on: Phase 1 completion
- Related: #03-03-seo-meta-editor

## Notes

### PackageDetector
```php
class PackageDetector
{
    public static function hasMediaLibrary(): bool
    {
        return class_exists(\ArtisanPackUI\MediaLibrary\Models\Media::class);
    }
}
```

### MediaLibraryIntegration
```php
class MediaLibraryIntegration
{
    public function getMediaUrl(?int $mediaId, string $size = 'large'): ?string;
    public function getSocialImageUrl(?int $mediaId): ?string;
    public function registerSocialImageSize(): void;
}
```

### SeoMeta Model Enhancement
```php
public function getEffectiveOgImage(): ?string
{
    if ($this->og_image_id) {
        $integration = app(MediaLibraryIntegration::class);
        $url = $integration->getSocialImageUrl($this->og_image_id);
        if ($url) {
            return $url;
        }
    }
    return $this->og_image;
}
```

### Livewire Event Handling
```php
protected $listeners = [
    'media-selected' => 'handleMediaSelected',
];

public function handleMediaSelected(array $event): void
{
    $media = $event['media'][0] ?? null;
    $context = $event['context'] ?? '';

    match ($context) {
        'og_image' => $this->setOgImage($media),
        'twitter_image' => $this->setTwitterImage($media),
        default => null,
    };
}
```

**Reference:** [08-integrations.md](../08-integrations.md)
