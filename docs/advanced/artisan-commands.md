---
title: Artisan Commands
---

# Artisan Commands

ArtisanPack UI SEO provides several Artisan commands for managing SEO features from the command line.

## Sitemap Commands

### Generate Sitemap

Generate XML sitemaps for your site.

```bash
# Generate all enabled sitemaps
php artisan seo:generate-sitemap

# Generate specific type
php artisan seo:generate-sitemap --type=standard
php artisan seo:generate-sitemap --type=images
php artisan seo:generate-sitemap --type=videos
php artisan seo:generate-sitemap --type=news

# Force regeneration (bypass cache)
php artisan seo:generate-sitemap --force

# Save to specific path
php artisan seo:generate-sitemap --path=/var/www/public/sitemaps/
```

**Options:**

| Option | Description |
|--------|-------------|
| `--type` | Sitemap type to generate |
| `--force` | Bypass cache and regenerate |
| `--path` | Output directory path |
| `--quiet` | Suppress output |

### Submit Sitemap

Submit sitemaps to search engines.

```bash
# Submit to all configured engines
php artisan seo:submit-sitemap

# Submit to specific engine
php artisan seo:submit-sitemap --engine=google
php artisan seo:submit-sitemap --engine=bing

# Submit specific sitemap URL
php artisan seo:submit-sitemap --url=https://example.com/sitemap.xml
```

**Options:**

| Option | Description |
|--------|-------------|
| `--engine` | Search engine to submit to |
| `--url` | Specific sitemap URL to submit |
| `--quiet` | Suppress output |

## Cache Commands

### Clear SEO Cache

Clear all or specific SEO caches.

```bash
# Clear all SEO caches
php artisan seo:clear-cache

# Clear specific cache type
php artisan seo:clear-cache --type=meta
php artisan seo:clear-cache --type=analysis
php artisan seo:clear-cache --type=redirects
php artisan seo:clear-cache --type=sitemaps
php artisan seo:clear-cache --type=robots

# Clear cache for specific model
php artisan seo:clear-cache --model="App\Models\Post" --id=42
```

**Options:**

| Option | Description |
|--------|-------------|
| `--type` | Cache type to clear |
| `--model` | Model class for specific clearing |
| `--id` | Model ID for specific clearing |

### Warm SEO Cache

Pre-populate SEO caches for better performance.

```bash
# Warm all caches
php artisan seo:warm-cache

# Warm specific cache type
php artisan seo:warm-cache --type=meta
php artisan seo:warm-cache --type=analysis

# Warm for specific model
php artisan seo:warm-cache --model="App\Models\Post"
```

**Options:**

| Option | Description |
|--------|-------------|
| `--type` | Cache type to warm |
| `--model` | Model class to warm caches for |
| `--chunk` | Chunk size for processing (default: 100) |

## Analysis Commands

### Analyze Content

Run SEO analysis on content.

```bash
# Analyze all models with HasSeo trait
php artisan seo:analyze

# Analyze specific model
php artisan seo:analyze --model="App\Models\Post"

# Analyze specific record
php artisan seo:analyze --model="App\Models\Post" --id=42

# Output results as JSON
php artisan seo:analyze --json

# Only show failing checks
php artisan seo:analyze --failures-only
```

**Options:**

| Option | Description |
|--------|-------------|
| `--model` | Model class to analyze |
| `--id` | Specific model ID to analyze |
| `--json` | Output as JSON |
| `--failures-only` | Only show failing checks |
| `--queue` | Queue analysis jobs |

## Redirect Commands

### Import Redirects

Import redirects from a CSV file.

```bash
php artisan seo:import-redirects /path/to/redirects.csv

# Skip header row
php artisan seo:import-redirects /path/to/redirects.csv --skip-header

# Preview without importing
php artisan seo:import-redirects /path/to/redirects.csv --dry-run
```

**CSV Format:**
```csv
source,target,type,status_code,active
/old-page,/new-page,exact,301,1
/blog/*,/articles/$1,wildcard,301,1
```

**Options:**

| Option | Description |
|--------|-------------|
| `--skip-header` | Skip first row of CSV |
| `--dry-run` | Preview without importing |
| `--quiet` | Suppress output |

### Export Redirects

Export redirects to a CSV file.

```bash
php artisan seo:export-redirects /path/to/output.csv

# Export only active redirects
php artisan seo:export-redirects /path/to/output.csv --active-only

# Export specific type
php artisan seo:export-redirects /path/to/output.csv --type=exact
```

**Options:**

| Option | Description |
|--------|-------------|
| `--active-only` | Export only active redirects |
| `--type` | Filter by match type |

### Test Redirect

Test if a path matches a redirect.

```bash
php artisan seo:test-redirect /some/path

# Output:
# ✓ Redirect found
#   Source: /some/*
#   Target: /other/$1
#   Type: wildcard
#   Status: 301
#   Destination: /other/path
```

## Utility Commands

### SEO Status

Show SEO status and statistics.

```bash
php artisan seo:status
```

**Output:**
```
SEO Package Status
==================

Configuration:
  ✓ Redirects enabled
  ✓ Sitemaps enabled
  ✓ Robots.txt enabled
  ✓ Analysis enabled
  ✓ Caching enabled (driver: redis)

Statistics:
  Models with SEO: 1,234
  Missing meta title: 45
  Missing meta description: 67
  Total redirects: 89 (85 active)
  Sitemap URLs: 2,345
  Last sitemap generated: 2024-01-15 10:30:00
```

### Install SEO Package

Run installation steps (usually run once after package installation).

```bash
php artisan seo:install

# Options
php artisan seo:install --force      # Overwrite existing files
php artisan seo:install --migrate    # Run migrations
php artisan seo:install --seed       # Run seeders
```

## Scheduling Commands

Add commands to your scheduler:

```php
// In routes/console.php or app/Console/Kernel.php

use Illuminate\Support\Facades\Schedule;

// Generate sitemaps daily
Schedule::command('seo:generate-sitemap')->daily();

// Submit sitemaps weekly
Schedule::command('seo:submit-sitemap')->weekly();

// Warm caches after deployment
Schedule::command('seo:warm-cache')->dailyAt('04:00');

// Analyze content weekly
Schedule::command('seo:analyze --queue')->weekly();

// Clear old analysis cache monthly
Schedule::command('seo:clear-cache --type=analysis')->monthly();
```

## Creating Custom Commands

Extend SEO functionality with custom commands:

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Post;

class UpdateSeoCommand extends Command
{
    protected $signature = 'seo:update-posts';
    protected $description = 'Update SEO for all posts';

    public function handle(): int
    {
        $bar = $this->output->createProgressBar(Post::count());

        Post::chunk(100, function ($posts) use ($bar) {
            foreach ($posts as $post) {
                $post->updateSeoMeta([
                    'meta_title' => $post->title . ' | ' . config('app.name'),
                    'meta_description' => str()->limit($post->content, 160),
                ]);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('SEO updated for all posts.');

        return Command::SUCCESS;
    }
}
```

## Command Output Verbosity

All commands support Laravel's verbosity levels:

```bash
# Quiet (no output)
php artisan seo:generate-sitemap -q

# Normal
php artisan seo:generate-sitemap

# Verbose
php artisan seo:generate-sitemap -v

# Very verbose
php artisan seo:generate-sitemap -vv

# Debug
php artisan seo:generate-sitemap -vvv
```

## Next Steps

- [Sitemaps](./sitemaps.md) - Sitemap configuration
- [Redirects](./redirects.md) - Redirect management
- [Caching](./caching.md) - Cache configuration
- [Configuration](../installation/configuration.md) - Full config reference
