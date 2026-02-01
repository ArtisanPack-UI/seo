---
title: Schema.org / JSON-LD
---

# Schema.org / JSON-LD

Structured data helps search engines understand your content and can enable rich search results. This guide covers implementing Schema.org markup using JSON-LD format.

## Overview

The package provides 14 built-in schema types with customizable JSON-LD generation. Schema markup is stored with your SEO metadata and rendered automatically.

## Setting Schema Type

### Basic Usage

```php
$post->updateSeoMeta([
    'schema_type' => 'Article',
]);
```

### With Custom Data

```php
$post->updateSeoMeta([
    'schema_type' => 'Article',
    'schema_data' => [
        'author' => [
            '@type' => 'Person',
            'name' => 'John Doe',
            'url' => 'https://example.com/authors/john',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'My Site',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => 'https://example.com/logo.png',
            ],
        ],
        'datePublished' => $post->created_at->toIso8601String(),
        'dateModified' => $post->updated_at->toIso8601String(),
    ],
]);
```

## Available Schema Types

### Article

For blog posts and news articles:

```php
$post->updateSeoMeta([
    'schema_type' => 'Article',
    'schema_data' => [
        'headline' => 'Article Headline',
        'author' => ['@type' => 'Person', 'name' => 'Author Name'],
        'datePublished' => '2024-01-15T10:00:00Z',
        'dateModified' => '2024-01-16T14:30:00Z',
        'image' => 'https://example.com/article-image.jpg',
        'articleSection' => 'Technology',
        'wordCount' => 1500,
    ],
]);
```

### BlogPosting

Specifically for blog posts:

```php
$post->updateSeoMeta([
    'schema_type' => 'BlogPosting',
    'schema_data' => [
        'headline' => 'My Blog Post Title',
        'author' => ['@type' => 'Person', 'name' => 'Blogger Name'],
        'datePublished' => '2024-01-15',
        'mainEntityOfPage' => $post->url,
    ],
]);
```

### Product

For e-commerce products:

```php
$product->updateSeoMeta([
    'schema_type' => 'Product',
    'schema_data' => [
        'name' => 'Amazing Product',
        'description' => 'Product description here.',
        'image' => 'https://example.com/product.jpg',
        'brand' => ['@type' => 'Brand', 'name' => 'Brand Name'],
        'sku' => 'SKU123',
        'offers' => [
            '@type' => 'Offer',
            'price' => '99.99',
            'priceCurrency' => 'USD',
            'availability' => 'https://schema.org/InStock',
            'url' => $product->url,
        ],
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => '4.5',
            'reviewCount' => '89',
        ],
    ],
]);
```

### Event

For events and happenings:

```php
$event->updateSeoMeta([
    'schema_type' => 'Event',
    'schema_data' => [
        'name' => 'Tech Conference 2024',
        'startDate' => '2024-06-15T09:00:00-05:00',
        'endDate' => '2024-06-17T18:00:00-05:00',
        'location' => [
            '@type' => 'Place',
            'name' => 'Convention Center',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '123 Main St',
                'addressLocality' => 'Chicago',
                'addressRegion' => 'IL',
                'postalCode' => '60601',
                'addressCountry' => 'US',
            ],
        ],
        'offers' => [
            '@type' => 'Offer',
            'price' => '299',
            'priceCurrency' => 'USD',
            'availability' => 'https://schema.org/InStock',
            'validFrom' => '2024-01-01',
        ],
        'performer' => [
            '@type' => 'Person',
            'name' => 'Keynote Speaker',
        ],
    ],
]);
```

### Organization

For company/organization pages:

```php
$page->updateSeoMeta([
    'schema_type' => 'Organization',
    'schema_data' => [
        'name' => 'My Company',
        'url' => 'https://example.com',
        'logo' => 'https://example.com/logo.png',
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'telephone' => '+1-800-555-1234',
            'contactType' => 'customer service',
        ],
        'sameAs' => [
            'https://facebook.com/mycompany',
            'https://twitter.com/mycompany',
            'https://linkedin.com/company/mycompany',
        ],
    ],
]);
```

### LocalBusiness

For local businesses:

```php
$business->updateSeoMeta([
    'schema_type' => 'LocalBusiness',
    'schema_data' => [
        'name' => 'My Local Shop',
        'image' => 'https://example.com/shop.jpg',
        'telephone' => '+1-555-123-4567',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => '456 Oak Street',
            'addressLocality' => 'Springfield',
            'addressRegion' => 'IL',
            'postalCode' => '62701',
            'addressCountry' => 'US',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => '39.7817',
            'longitude' => '-89.6501',
        ],
        'openingHoursSpecification' => [
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'opens' => '09:00',
                'closes' => '17:00',
            ],
        ],
        'priceRange' => '$$',
    ],
]);
```

### WebSite

For the main website (usually homepage):

```php
$homepage->updateSeoMeta([
    'schema_type' => 'WebSite',
    'schema_data' => [
        'name' => 'My Website',
        'url' => 'https://example.com',
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => 'https://example.com/search?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ],
]);
```

### WebPage

For general web pages:

```php
$page->updateSeoMeta([
    'schema_type' => 'WebPage',
    'schema_data' => [
        'name' => 'About Us',
        'description' => 'Learn about our company.',
        'isPartOf' => ['@type' => 'WebSite', 'name' => 'My Website'],
    ],
]);
```

### Service

For service offerings:

```php
$service->updateSeoMeta([
    'schema_type' => 'Service',
    'schema_data' => [
        'name' => 'Web Development',
        'description' => 'Professional web development services.',
        'provider' => [
            '@type' => 'Organization',
            'name' => 'My Agency',
        ],
        'serviceType' => 'Web Development',
        'areaServed' => 'United States',
    ],
]);
```

### FAQPage

For FAQ sections:

```php
$faqPage->updateSeoMeta([
    'schema_type' => 'FAQPage',
    'schema_data' => [
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => 'What is your return policy?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'We offer 30-day returns on all items.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Do you ship internationally?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Yes, we ship to over 50 countries.',
                ],
            ],
        ],
    ],
]);
```

### BreadcrumbList

For breadcrumb navigation:

```php
$page->updateSeoMeta([
    'schema_type' => 'BreadcrumbList',
    'schema_data' => [
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://example.com'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Products', 'item' => 'https://example.com/products'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => 'Widget', 'item' => 'https://example.com/products/widget'],
        ],
    ],
]);
```

### Review

For review content:

```php
$review->updateSeoMeta([
    'schema_type' => 'Review',
    'schema_data' => [
        'itemReviewed' => ['@type' => 'Product', 'name' => 'Product Name'],
        'reviewRating' => ['@type' => 'Rating', 'ratingValue' => '4', 'bestRating' => '5'],
        'author' => ['@type' => 'Person', 'name' => 'Reviewer Name'],
        'reviewBody' => 'Great product, highly recommended!',
    ],
]);
```

### AggregateRating

For aggregate ratings:

```php
$product->updateSeoMeta([
    'schema_type' => 'AggregateRating',
    'schema_data' => [
        'itemReviewed' => ['@type' => 'Product', 'name' => 'Product Name'],
        'ratingValue' => '4.5',
        'bestRating' => '5',
        'ratingCount' => '127',
    ],
]);
```

## Rendering Schema

### Using Blade Component

```blade
<x-seo-schema :model="$post" />
```

### Generated Output

```html
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "My Article Title",
    "author": {
        "@type": "Person",
        "name": "John Doe"
    },
    "datePublished": "2024-01-15T10:00:00Z",
    "image": "https://example.com/article.jpg"
}
</script>
```

## Using the Schema Service

For programmatic schema generation:

```php
use ArtisanPackUI\Seo\Services\SchemaService;

$schemaService = app(SchemaService::class);

// Generate schema for a model
$jsonLd = $schemaService->generate($post);

// Generate specific schema type
$articleSchema = $schemaService->buildArticle($post, [
    'author' => ['@type' => 'Person', 'name' => 'Author'],
]);
```

## Testing Structured Data

Validate your schema markup using:

- [Google Rich Results Test](https://search.google.com/test/rich-results)
- [Schema.org Validator](https://validator.schema.org/)

## Best Practices

1. **Choose the most specific type** - Use `BlogPosting` instead of `Article` for blogs
2. **Include required properties** - Check Google's documentation for each type
3. **Use absolute URLs** - Always use full URLs for images and links
4. **Keep data accurate** - Schema should match visible page content
5. **Test regularly** - Validate after any changes

## Next Steps

- [Meta Tags](Meta-Tags) - Basic meta tag management
- [Social Media](Social-Media) - Open Graph and Twitter Cards
- [Hreflang](Hreflang) - Multi-language support
- [Configuration](Installation-Configuration) - Schema settings
