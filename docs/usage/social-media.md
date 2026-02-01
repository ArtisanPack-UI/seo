---
title: Social Media Optimization
---

# Social Media Optimization

Optimize how your content appears when shared on social media platforms. This guide covers Open Graph (Facebook, LinkedIn), Twitter Cards, Pinterest, and Slack.

## Open Graph Tags

Open Graph tags control how your content appears on Facebook, LinkedIn, and other platforms.

### Setting Open Graph Data

```php
$post->updateSeoMeta([
    'og_title' => 'Amazing Post for Social',
    'og_description' => 'This description appears when shared on Facebook.',
    'og_image' => 'https://example.com/images/social-share.jpg',
    'og_type' => 'article',
    'og_locale' => 'en_US',
    'og_site_name' => 'My Site',
]);
```

### Available Open Graph Types

| Type | Use Case |
|------|----------|
| `website` | Homepage and general pages |
| `article` | Blog posts and articles |
| `book` | Book pages |
| `profile` | Author/user profiles |
| `music.song` | Individual music tracks |
| `music.album` | Music albums |
| `video.movie` | Movies |
| `video.episode` | TV show episodes |
| `product` | Product pages |

### Using Media Library for Images

If you have the `artisanpack-ui/media-library` package installed:

```php
$post->updateSeoMeta([
    'og_image_id' => $media->id,  // Uses optimized social image
]);
```

### Open Graph Fallbacks

When OG fields aren't set, the package falls back to:

1. `og_title` → `meta_title` → model title → site name
2. `og_description` → `meta_description` → model description
3. `og_image` → `og_image_id` media → model image → default from config

### Rendering Open Graph Tags

```blade
<x-seo-open-graph :model="$post" />
```

### Generated Output

```html
<meta property="og:type" content="article">
<meta property="og:title" content="Amazing Post for Social">
<meta property="og:description" content="This description appears when shared on Facebook.">
<meta property="og:url" content="https://example.com/posts/amazing-post">
<meta property="og:image" content="https://example.com/images/social-share.jpg">
<meta property="og:site_name" content="My Site">
<meta property="og:locale" content="en_US">
```

## Twitter Cards

Twitter Cards enhance how your content appears in tweets.

### Setting Twitter Card Data

```php
$post->updateSeoMeta([
    'twitter_card' => 'summary_large_image',
    'twitter_title' => 'Post Title for Twitter',
    'twitter_description' => 'Description optimized for Twitter.',
    'twitter_image' => 'https://example.com/images/twitter-card.jpg',
    'twitter_site' => '@mysite',
    'twitter_creator' => '@author',
]);
```

### Twitter Card Types

| Type | Description | Image Size |
|------|-------------|------------|
| `summary` | Title, description, thumbnail | 144x144 minimum |
| `summary_large_image` | Large image above content | 300x157 minimum |
| `app` | App download card | N/A |
| `player` | Video/audio player | N/A |

### Twitter-Specific Settings

```php
// Site-wide Twitter handle (in config)
'twitter' => [
    'site' => '@mysite',
    'creator' => '@defaultauthor',
],

// Per-content creator
$post->updateSeoMeta([
    'twitter_creator' => '@articleauthor',
]);
```

### Rendering Twitter Cards

```blade
<x-seo-twitter-card :model="$post" />
```

### Generated Output

```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Post Title for Twitter">
<meta name="twitter:description" content="Description optimized for Twitter.">
<meta name="twitter:image" content="https://example.com/images/twitter-card.jpg">
<meta name="twitter:site" content="@mysite">
<meta name="twitter:creator" content="@author">
```

## Pinterest

Optimize for Pinterest rich pins.

### Setting Pinterest Data

```php
$post->updateSeoMeta([
    'pinterest_description' => 'Pin this amazing content!',
    'pinterest_image' => 'https://example.com/images/pinterest.jpg',
]);
```

### Pinterest Best Practices

- Use vertical images (2:3 ratio recommended)
- Include compelling descriptions
- Rich pins automatically use Open Graph data as fallback

## Slack

Customize how links appear when shared in Slack.

### Setting Slack Data

```php
$post->updateSeoMeta([
    'slack_title' => 'Title for Slack',
    'slack_description' => 'Description shown in Slack previews.',
]);
```

## Social Preview Component

Preview how your content will appear on social platforms:

```blade
<livewire:social-preview :model="$post" />
```

This component shows live previews for:
- Facebook/LinkedIn (Open Graph)
- Twitter
- Search engine results

## Image Recommendations

### Open Graph Images

- **Minimum size**: 1200 x 630 pixels
- **Aspect ratio**: 1.91:1
- **Format**: JPG or PNG
- **Max file size**: 8MB

### Twitter Images

- **Summary card**: 144 x 144 pixels minimum
- **Large image card**: 300 x 157 pixels minimum
- **Aspect ratio**: 2:1 for large images
- **Format**: JPG, PNG, GIF (no animated GIFs)
- **Max file size**: 5MB

### Pinterest Images

- **Recommended ratio**: 2:3 (600 x 900 pixels)
- **Minimum width**: 600 pixels
- **Format**: JPG or PNG

## Fallback Chain

The social media fallback system ensures content is always available:

```
Twitter Title
    → Open Graph Title
        → Meta Title
            → Model Title
                → Site Name

Twitter Description
    → Open Graph Description
        → Meta Description
            → Model Description/Excerpt
                → Site Description

Twitter Image
    → Open Graph Image
        → Model Image
            → Default Image
```

## Combining All Social Tags

Use the main component to output all social tags:

```blade
<head>
    {{-- Outputs meta tags, OG, Twitter, and more --}}
    <x-seo-meta :model="$post" />
</head>
```

Or selectively:

```blade
<head>
    <x-seo-meta-tags :model="$post" />
    <x-seo-open-graph :model="$post" />
    <x-seo-twitter-card :model="$post" />
</head>
```

## Testing Social Shares

Use these tools to validate your social meta tags:

- **Facebook**: [Sharing Debugger](https://developers.facebook.com/tools/debug/)
- **Twitter**: [Card Validator](https://cards-dev.twitter.com/validator)
- **LinkedIn**: [Post Inspector](https://www.linkedin.com/post-inspector/)
- **Pinterest**: [Rich Pins Validator](https://developers.pinterest.com/tools/url-debugger/)

## Next Steps

- [Schema.org](Schema) - Add structured data
- [Meta Tags](Meta-Tags) - Basic meta tag management
- [SEO Meta Editor](Components-Seo-Meta-Editor) - Admin component
- [Configuration](Installation-Configuration) - Default settings
