---
title: Blade Components
---

# Blade Components

Blade components render SEO tags in your HTML. These are designed for use in your layout's `<head>` section.

## x-seo:meta

The all-in-one component that renders meta, social, and hreflang tags:

```blade
<x-seo:meta :model="$post" />
```

### What It Renders

- Title tag
- Meta description
- Robots meta
- Canonical URL
- Author meta, when the model has one
- Open Graph tags, including `article:*` tags and image details
- Twitter Card tags
- Hreflang links

Schema.org JSON-LD isn't included. Add `<x-seo:schema>` alongside it.

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `model` | Model\|null | `null` | Eloquent model with HasSeo trait |
| `title` | string\|null | `null` | Override title |
| `description` | string\|null | `null` | Override description |
| `image` | string\|null | `null` | Override image URL |
| `canonical` | string\|null | `null` | Override canonical URL |
| `includeOpenGraph` | bool | `true` | Include Open Graph tags |
| `includeTwitterCard` | bool | `true` | Include Twitter Card tags |
| `includeHreflang` | bool | `true` | Include hreflang links |

### Examples

```blade
{{-- With model --}}
<x-seo:meta :model="$post" />

{{-- Without model (static page) --}}
<x-seo:meta
    title="About Us | My Site"
    description="Learn more about our company and mission."
    image="https://example.com/about-og.jpg"
/>

{{-- With overrides --}}
<x-seo:meta
    :model="$post"
    :title="$customTitle"
    :include-hreflang="false"
/>
```

---

## x-seo:meta-tags

Renders only basic meta tags (title, description, robots, canonical):

```blade
<x-seo:meta-tags :model="$post" />
```

### Generated Output

```html
<title>Post Title | My Site</title>
<meta name="description" content="Post description here.">
<link rel="canonical" href="https://example.com/posts/post-slug">
<meta name="robots" content="index, follow">
```

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `model` | Model\|null | `null` | Eloquent model with HasSeo trait |
| `title` | string\|null | `null` | Override title |
| `description` | string\|null | `null` | Override description |
| `robots` | string\|null | `null` | Override robots directive |
| `canonical` | string\|null | `null` | Override canonical URL |

### Examples

```blade
{{-- Basic usage --}}
<x-seo:meta-tags :model="$post" />

{{-- Static page --}}
<x-seo:meta-tags
    title="Contact Us"
    description="Get in touch with our team."
/>

{{-- Noindex page --}}
<x-seo:meta-tags
    :model="$draftPost"
    robots="noindex, nofollow"
/>
```

---

## x-seo:open-graph

Renders Open Graph meta tags for social sharing:

```blade
<x-seo:open-graph :model="$post" />
```

### Generated Output

```html
<meta property="og:title" content="Post Title">
<meta property="og:description" content="Post description.">
<meta property="og:image" content="https://example.com/images/post.jpg">
<meta property="og:url" content="https://example.com/posts/slug">
<meta property="og:type" content="article">
<meta property="og:site_name" content="My Site">
<meta property="og:locale" content="en_US">
```

`og:image:width`, `og:image:height`, and `og:image:alt` are added when known. On an `article`, so are `article:published_time` and `article:modified_time`. Setting `type` changes this too, and overriding `image` looks up the details for that image.

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `model` | Model\|null | `null` | Eloquent model with HasSeo trait |
| `type` | string\|null | `null` | Override og:type |
| `title` | string\|null | `null` | Override og:title |
| `description` | string\|null | `null` | Override og:description |
| `image` | string\|null | `null` | Override og:image |
| `url` | string\|null | `null` | Override og:url |
| `siteName` | string\|null | `null` | Override og:site_name |
| `locale` | string\|null | `null` | Override og:locale |

### Examples

```blade
{{-- Basic usage --}}
<x-seo:open-graph :model="$post" />

{{-- Product page --}}
<x-seo:open-graph
    :model="$product"
    type="product"
/>

{{-- Static page --}}
<x-seo:open-graph
    type="website"
    title="My Site - Home"
    description="Welcome to my site."
    image="https://example.com/og-default.jpg"
/>
```

---

## x-seo:twitter-card

Renders Twitter Card meta tags:

```blade
<x-seo:twitter-card :model="$post" />
```

### Generated Output

```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Post Title">
<meta name="twitter:description" content="Post description.">
<meta name="twitter:image" content="https://example.com/images/post.jpg">
<meta name="twitter:site" content="@mysite">
<meta name="twitter:creator" content="@author">
```

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `model` | Model\|null | `null` | Eloquent model with HasSeo trait |
| `card` | string\|null | `null` | Override card type |
| `title` | string\|null | `null` | Override title |
| `description` | string\|null | `null` | Override description |
| `image` | string\|null | `null` | Override image |
| `site` | string\|null | `null` | Override @site handle |
| `creator` | string\|null | `null` | Override @creator handle |

### Examples

```blade
{{-- Basic usage --}}
<x-seo:twitter-card :model="$post" />

{{-- Summary card --}}
<x-seo:twitter-card
    :model="$post"
    card="summary"
/>

{{-- Static page --}}
<x-seo:twitter-card
    card="summary_large_image"
    title="My Site"
    description="Welcome to my site."
    site="@mysite"
/>
```

---

## x-seo:schema

Renders Schema.org JSON-LD structured data:

```blade
<x-seo:schema :model="$post" />
```

### Generated Output

```html
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "Post Title",
    "description": "Post description.",
    "author": {
        "@type": "Person",
        "name": "Author Name"
    },
    "datePublished": "2024-01-15T10:00:00Z"
}
</script>
```

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `model` | Model\|null | `null` | Eloquent model with HasSeo trait |
| `schemas` | array | `[]` | Additional schemas |
| `includeOrganization` | bool | `false` | Include the Organization schema |
| `includeWebsite` | bool | `false` | Include the WebSite schema |
| `useGraph` | bool | `true` | Output one `@graph` block |
| `breadcrumbs` | array\|null | `null` | Breadcrumb items |

### Examples

```blade
{{-- Basic usage --}}
<x-seo:schema :model="$post" />

{{-- With site-wide schemas --}}
<x-seo:schema
    :model="$post"
    :include-organization="true"
    :include-website="true"
/>

{{-- Custom schema --}}
<x-seo:schema :schemas="[
    ['@type' => 'FAQPage', 'mainEntity' => $faqItems],
]" />
```

---

## x-seo:hreflang

Renders hreflang link tags for international SEO:

```blade
<x-seo:hreflang :model="$post" />
```

### Generated Output

```html
<link rel="alternate" hreflang="en" href="https://example.com/posts/slug">
<link rel="alternate" hreflang="fr" href="https://example.fr/articles/slug">
<link rel="alternate" hreflang="de" href="https://example.de/beitrage/slug">
<link rel="alternate" hreflang="x-default" href="https://example.com/posts/slug">
```

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `model` | Model\|null | `null` | Eloquent model with HasSeo trait |
| `urls` | array\|null | `null` | Override hreflang URLs |
| `defaultUrl` | string\|null | `null` | URL for x-default |
| `includeXDefault` | bool | `true` | Include x-default |

### Examples

```blade
{{-- From model --}}
<x-seo:hreflang :model="$post" />

{{-- Manual URLs --}}
<x-seo:hreflang :urls="[
    'en' => 'https://example.com/page',
    'fr' => 'https://example.fr/page',
    'x-default' => 'https://example.com/page',
]" />
```

---

## Usage in Layouts

### Complete Example

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO tags --}}
    <x-seo:meta :model="$model ?? null" />
    <x-seo:schema :model="$model ?? null" />

    {{-- Or individual components --}}
    {{--
    <x-seo:meta-tags :model="$model ?? null" />
    <x-seo:open-graph :model="$model ?? null" />
    <x-seo:twitter-card :model="$model ?? null" />
    <x-seo:schema :model="$model ?? null" />
    <x-seo:hreflang :model="$model ?? null" />
    --}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}
</body>
</html>
```

### Passing Model from Controller

```php
class PostController extends Controller
{
    public function show(Post $post)
    {
        return view('posts.show', [
            'post' => $post,
            'model' => $post, // For SEO component
        ]);
    }
}
```

## Next Steps

- [SEO Meta Editor](Components-Seo-Meta-Editor) - Admin editing component
- [Meta Tags](Usage-Meta-Tags) - Meta tag management
- [Social Media](Usage-Social-Media) - Social optimization
- [Schema.org](Usage-Schema) - Structured data
