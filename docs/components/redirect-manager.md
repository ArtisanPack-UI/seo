---
title: Redirect Manager
---

# Redirect Manager

The Redirect Manager is a Livewire component for creating and managing URL redirects through an admin interface.

## Basic Usage

```blade
<livewire:redirect-manager />
```

## Features

- Create, edit, and delete redirects
- Support for exact, regex, and wildcard matching
- Filter by type, status, and search
- View redirect statistics (hits, last hit)
- Bulk actions

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `perPage` | int | 20 | Items per page |
| `sortBy` | string | 'created_at' | Sort column |
| `sortDirection` | string | 'desc' | Sort direction |

## Examples

### Standard Usage

```blade
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">URL Redirects</h1>
    <livewire:redirect-manager />
</div>
```

### With Custom Pagination

```blade
<livewire:redirect-manager :per-page="50" />
```

## Interface Elements

### Redirect List

The main view displays a table with:

| Column | Description |
|--------|-------------|
| Source | The source path/pattern |
| Target | The destination URL |
| Type | exact, regex, or wildcard |
| Status Code | 301, 302, 307, or 308 |
| Hits | Number of times triggered |
| Last Hit | When last triggered |
| Status | Active/Inactive toggle |
| Actions | Edit, Delete buttons |

### Create/Edit Form

The form includes:

- **Source Path** - The path to redirect from
- **Target URL** - The destination URL
- **Match Type** - Exact, Regex, or Wildcard
- **Status Code** - HTTP redirect code
- **Active** - Enable/disable toggle
- **Notes** - Optional notes for documentation

### Filters

Available filters:

- **Search** - Search source and target paths
- **Type** - Filter by match type
- **Status** - Active or inactive
- **Status Code** - Filter by HTTP code

## Redirect Types

### Exact Match

Matches the exact path:

```text
Source: /old-page
Target: /new-page

/old-page → /new-page ✓
/old-page/ → No match ✗
/old-page?q=1 → No match ✗
```

### Wildcard Match

Uses `*` and `?` wildcards:

```text
Source: /blog/*
Target: /articles/$1

/blog/my-post → /articles/my-post ✓
/blog/2024/post → /articles/2024/post ✓
```

### Regex Match

Uses regular expressions:

```text
Source: ^/products/(\d+)$
Target: /items/$1

/products/123 → /items/123 ✓
/products/abc → No match ✗
```

## Events

| Event | Payload | Description |
|-------|---------|-------------|
| `redirect-created` | `['redirect' => Redirect]` | After creating redirect |
| `redirect-updated` | `['redirect' => Redirect]` | After updating redirect |
| `redirect-deleted` | `['id' => int]` | After deleting redirect |

## API Methods

The component exposes these wire methods:

```php
// Create a redirect
$this->create([
    'source' => '/old',
    'target' => '/new',
    'type' => 'exact',
    'status_code' => 301,
]);

// Update a redirect
$this->update($id, [
    'target' => '/updated-url',
]);

// Delete a redirect
$this->delete($id);

// Toggle active status
$this->toggleActive($id);

// Test a path
$this->testPath('/some/path');
// Returns matching redirect or null
```

## Testing Redirects

The component includes a path tester:

```blade
{{-- Built into the component --}}
<input type="text" wire:model="testPath" placeholder="Enter path to test...">
<button wire:click="runTest">Test</button>

{{-- Results show matching redirect and destination --}}
```

## Statistics

View redirect statistics:

- Total redirects
- Active vs inactive
- Most hit redirects
- Recent hits
- Redirects by type

## Bulk Actions

Select multiple redirects for:

- **Activate** - Enable selected redirects
- **Deactivate** - Disable selected redirects
- **Delete** - Remove selected redirects
- **Export** - Download as CSV

## Customization

### Publishing Views

```bash
php artisan vendor:publish --tag=seo-views
```

### Extending the Component

```php
namespace App\Livewire;

use ArtisanPackUI\Seo\Livewire\RedirectManager as BaseRedirectManager;

class CustomRedirectManager extends BaseRedirectManager
{
    public $perPage = 50;

    protected function afterCreate($redirect): void
    {
        // Custom logic after creating
    }
}
```

## Security

The component respects authorization:

```php
// In AuthServiceProvider or Policy
Gate::define('manage-redirects', function ($user) {
    return $user->hasRole('admin');
});
```

## Import/Export

### Importing Redirects

```blade
<livewire:redirect-manager>
    <x-slot:actions>
        <button wire:click="showImport">Import CSV</button>
    </x-slot:actions>
</livewire:redirect-manager>
```

CSV format:
```csv
source,target,type,status_code,active
/old-page,/new-page,exact,301,1
/blog/*,/articles/$1,wildcard,301,1
```

### Exporting Redirects

The export button generates a CSV with all redirect data.

## Next Steps

- [URL Redirects](Advanced-Redirects) - Redirect documentation
- [SEO Meta Editor](Seo-Meta-Editor) - Main SEO editor
- [SEO Dashboard](Seo-Dashboard) - Overview dashboard
