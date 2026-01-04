# Redirect Middleware

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::High"

## Task Description

Create the HTTP middleware that intercepts requests and handles redirects.

## Acceptance Criteria

- [ ] Middleware checks all incoming requests against redirect rules
- [ ] Proper redirect response with correct status code
- [ ] Hit counter incremented on redirect
- [ ] Cached redirect lookup for performance
- [ ] Chain detection prevents infinite loops
- [ ] Configurable max chain length
- [ ] Middleware can be disabled via config
- [ ] Feature tests for redirect behavior

## Context

The middleware is how redirects are actually applied to incoming requests.

**Related Issues:**
- Depends on: #03-01-redirect-system

## Notes

### Middleware Implementation
```php
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('seo.redirects.enabled')) {
            return $next($request);
        }

        $redirect = app(RedirectService::class)->findMatch(
            $request->path()
        );

        if ($redirect) {
            $redirect->increment('hits');
            $redirect->update(['last_hit_at' => now()]);

            return redirect(
                $this->resolveDestination($redirect, $request),
                $redirect->status_code
            );
        }

        return $next($request);
    }
}
```

### Registration
```php
// In SEOServiceProvider
$this->app['router']->pushMiddlewareToGroup(
    'web',
    HandleRedirects::class
);
```

### Configuration
```php
'redirects' => [
    'enabled' => true,
    'cache_enabled' => true,
    'max_chain_length' => 5,
    'regex_timeout' => 100, // ms
],
```

**Reference:** [03-core-services.md](../03-core-services.md)
