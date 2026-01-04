# RedirectManager Livewire Component

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::Medium"

## Task Description

Create the full redirect management interface Livewire component for admin use.

## Acceptance Criteria

- [ ] Paginated redirect list with sorting
- [ ] Search functionality for from/to paths
- [ ] Filter by status (active, inactive, issues)
- [ ] Filter by match type (exact, regex, wildcard)
- [ ] Create/edit redirect modal/form
- [ ] Inline toggle for active/inactive
- [ ] Delete functionality with confirmation
- [ ] Hit statistics display
- [ ] Chain detection warning
- [ ] All strings wrapped in `__()` for translation
- [ ] Uses `<x-artisanpack-*>` components throughout
- [ ] Livewire tests for CRUD operations

## Context

This is a standalone admin component for managing all URL redirects.

**Related Issues:**
- Depends on: #03-01-redirect-system, #03-02-redirect-middleware

## Notes

### Component Structure
```php
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

    public function create(): void;
    public function edit(Redirect $redirect): void;
    public function save(): void;
    public function delete(Redirect $redirect): void;
    public function toggleActive(Redirect $redirect): void;
    public function checkChains(): void;
}
```

### View Uses ArtisanPack Components
```blade
<x-artisanpack-input wire:model.live="search" placeholder="{{ __('Search redirects...') }}" />
<x-artisanpack-select wire:model.live="filterStatus" :options="$statusOptions" />
<x-artisanpack-table :headers="$headers" :rows="$redirects" />
<x-artisanpack-modal wire:model="showEditor" title="{{ __('Edit Redirect') }}">
```

**Reference:** [06-admin-components.md](../06-admin-components.md)
