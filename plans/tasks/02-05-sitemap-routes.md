# Sitemap and Robots.txt Routes

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::Medium"

## Task Description

Create controllers and routes for serving sitemap.xml and robots.txt dynamically.

## Acceptance Criteria

- [ ] `/sitemap.xml` route serves main sitemap or index
- [ ] `/sitemap-{type}-{page}.xml` for paginated type sitemaps
- [ ] `/robots.txt` serves dynamic robots.txt
- [ ] Routes can be enabled/disabled via config
- [ ] Proper XML content-type headers
- [ ] Caching headers for performance
- [ ] Feature tests for routes

## Context

These routes serve SEO files that search engines request.

**Related Issues:**
- Depends on: #02-04-sitemap-service

## Notes

### Routes
```php
// routes/web.php
Route::get('sitemap.xml', [SitemapController::class, 'index']);
Route::get('sitemap-{type}-{page}.xml', [SitemapController::class, 'show']);
Route::get('robots.txt', [RobotsController::class, 'index']);
```

### SitemapController
```php
class SitemapController extends Controller
{
    public function index(): Response
    {
        $content = app(SitemapService::class)->generateIndex();
        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }
}
```

### RobotsController
```php
class RobotsController extends Controller
{
    public function index(): Response
    {
        $content = $this->buildRobotsTxt();
        return response($content, 200)
            ->header('Content-Type', 'text/plain');
    }
}
```

### Robots.txt Generation
- Pull disallow rules from config
- Add sitemap reference
- Support bot-specific rules
- Additional directives from config

**Reference:** [03-core-services.md](../03-core-services.md)
