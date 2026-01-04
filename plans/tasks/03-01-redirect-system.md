# Redirect System - Database and Model

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::High"

## Task Description

Create the redirect management system including database migration, model, and service.

## Acceptance Criteria

- [ ] Migration creates `redirects` table
- [ ] `Redirect` model with proper scopes
- [ ] `RedirectService` for CRUD operations
- [ ] Support for exact, regex, and wildcard matching
- [ ] Status codes: 301, 302, 307, 308
- [ ] Hit tracking with counter
- [ ] Chain detection and prevention
- [ ] Regex timeout protection (ReDoS prevention)
- [ ] Unit tests for matching logic

## Context

The redirect system allows managing URL redirects without editing server config.

**Related Issues:**
- Depends on: Phase 1 completion
- Required by: #03-02-redirect-middleware

## Notes

### Table Schema
```php
Schema::create('redirects', function (Blueprint $table) {
    $table->id();
    $table->string('from_path', 500);
    $table->string('to_path', 500);
    $table->smallInteger('status_code')->default(301);
    $table->string('match_type')->default('exact'); // exact, regex, wildcard
    $table->boolean('is_active')->default(true);
    $table->unsignedBigInteger('hits')->default(0);
    $table->timestamp('last_hit_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['from_path', 'is_active']);
    $table->index('match_type');
});
```

### RedirectService
```php
class RedirectService
{
    public function findMatch(string $path): ?Redirect;
    public function create(array $data): Redirect;
    public function update(Redirect $redirect, array $data): Redirect;
    public function delete(Redirect $redirect): void;
    public function checkForChains(Redirect $redirect): bool;
    public function getStatistics(): array;
}
```

**Reference:** [03-core-services.md](../03-core-services.md)
