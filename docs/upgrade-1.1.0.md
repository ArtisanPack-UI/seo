---
title: Upgrading to 1.1.0
---

# Upgrading to 1.1.0

This guide covers upgrading from v1.0.0 to v1.1.0. This release introduces new features and contains one breaking change for custom schema type implementations.

## Breaking Changes

### `SchemaTypeContract` Interface Gained Two New Methods

The `SchemaTypeContract` interface now requires two additional methods:

```php
public function getDescription(): string;
public function getFieldDefinitions(): array;
```

**Who is affected?** Only classes that directly implement `SchemaTypeContract`. Classes that extend `AbstractSchema` (the recommended approach) are **not** affected, as `AbstractSchema` provides default implementations that return an empty string and empty array respectively.

**How to migrate:**

**Option A** (recommended): Switch to extending `AbstractSchema` instead of implementing the interface directly:

```php
// Before
use ArtisanPackUI\SEO\Contracts\SchemaTypeContract;

class MyCustomSchema implements SchemaTypeContract
{
    public function generate(?Model $model = null): array { /* ... */ }
    public function getType(): string { return 'CustomType'; }
}

// After
use ArtisanPackUI\SEO\Schema\Builders\AbstractSchema;

class MyCustomSchema extends AbstractSchema
{
    public function generate(?Model $model = null): array { /* ... */ }
    public function getType(): string { return 'CustomType'; }

    // Optional: override for richer API responses
    public function getDescription(): string
    {
        return 'Description of your custom schema type.';
    }

    public function getFieldDefinitions(): array
    {
        return [
            [
                'name' => 'customField',
                'type' => 'text',
                'label' => 'Custom Field',
                'required' => false,
                'description' => 'A custom field for your schema type.',
            ],
        ];
    }
}
```

**Option B**: Add both methods directly to your class:

```php
class MyCustomSchema implements SchemaTypeContract
{
    // ... existing methods ...

    public function getDescription(): string
    {
        return '';
    }

    public function getFieldDefinitions(): array
    {
        return [];
    }
}
```

## New Features

### Schema Type Definitions API

A new API endpoint returns rich metadata for all registered schema types, including descriptions and field definitions:

```
GET /api/seo/schema/types
```

This enables dynamic form rendering in React/Vue editors — the `SchemaTab` frontend component uses this endpoint to build schema editing forms on the fly.

See [Schema.org / JSON-LD — Schema Type Definitions API](Usage-Schema#schema-type-definitions-api) for full documentation.

### Frontend Component Scaffolding

A new Artisan command publishes React or Vue SEO admin components and shared TypeScript type definitions:

```bash
php artisan seo:install-frontend --stack=react
php artisan seo:install-frontend --stack=vue
```

See [Frontend Scaffolding](Advanced-Frontend-Scaffolding) for full documentation.

### Publishable Asset Tags

Three new publish tags are available:

| Tag | Contents |
|-----|----------|
| `seo-react` | React admin components and hooks |
| `seo-vue` | Vue admin components and composables |
| `seo-types` | Shared TypeScript type definitions |

## Upgrade Steps

1. Update the package:

```bash
composer update artisanpack-ui/seo
```

2. If you have custom schema types that directly implement `SchemaTypeContract`, migrate them as described above.

3. No database migrations are required for this release.

4. Optionally, install the new frontend components:

```bash
php artisan seo:install-frontend --stack=react
# or
php artisan seo:install-frontend --stack=vue
```
