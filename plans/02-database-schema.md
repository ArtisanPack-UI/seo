# Database Schema

**Purpose:** Define all database tables, migrations, and model relationships
**Last Updated:** January 3, 2026

---

## Overview

The SEO package uses four primary tables:

| Table | Purpose |
|-------|---------|
| `seo_meta` | Polymorphic SEO metadata for any model |
| `redirects` | URL redirect rules with regex/wildcard support |
| `sitemap_entries` | Cached sitemap entries for performance |
| `seo_analysis_cache` | Cached SEO analysis results |

---

## Table: seo_meta

Stores SEO metadata for any model using polymorphic relationships.

### Schema

```sql
CREATE TABLE seo_meta (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Polymorphic relationship
    seoable_type VARCHAR(255) NOT NULL,
    seoable_id BIGINT UNSIGNED NOT NULL,

    -- Basic Meta Tags
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    canonical_url VARCHAR(500) NULL,
    no_index BOOLEAN DEFAULT FALSE,
    no_follow BOOLEAN DEFAULT FALSE,
    robots_meta VARCHAR(255) NULL,           -- Additional robots directives

    -- Open Graph
    og_title VARCHAR(255) NULL,
    og_description TEXT NULL,
    og_image VARCHAR(500) NULL,              -- URL or media library ID
    og_image_id BIGINT UNSIGNED NULL,        -- Reference to media library
    og_type VARCHAR(50) DEFAULT 'website',   -- website, article, product, etc.
    og_locale VARCHAR(10) NULL,              -- e.g., en_US
    og_site_name VARCHAR(255) NULL,          -- Override app name

    -- Twitter Card
    twitter_card VARCHAR(50) DEFAULT 'summary_large_image',
    twitter_title VARCHAR(255) NULL,
    twitter_description TEXT NULL,
    twitter_image VARCHAR(500) NULL,
    twitter_image_id BIGINT UNSIGNED NULL,
    twitter_site VARCHAR(50) NULL,           -- @username
    twitter_creator VARCHAR(50) NULL,        -- @username

    -- Pinterest
    pinterest_description TEXT NULL,
    pinterest_image VARCHAR(500) NULL,
    pinterest_image_id BIGINT UNSIGNED NULL,

    -- Slack
    slack_title VARCHAR(255) NULL,
    slack_description TEXT NULL,
    slack_image VARCHAR(500) NULL,
    slack_image_id BIGINT UNSIGNED NULL,

    -- Schema.org
    schema_type VARCHAR(100) NULL,           -- Article, Product, LocalBusiness, etc.
    schema_markup JSON NULL,                 -- Custom/override schema data

    -- Focus Keyword (for analysis)
    focus_keyword VARCHAR(255) NULL,
    secondary_keywords JSON NULL,            -- Array of secondary keywords

    -- Multi-language (hreflang)
    hreflang JSON NULL,                      -- {"en": "url", "es": "url", ...}

    -- Sitemap
    sitemap_priority DECIMAL(2,1) DEFAULT 0.5,
    sitemap_changefreq VARCHAR(20) DEFAULT 'weekly',
    exclude_from_sitemap BOOLEAN DEFAULT FALSE,

    -- Timestamps
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    -- Indexes
    INDEX seoable_index (seoable_type, seoable_id),
    INDEX focus_keyword_index (focus_keyword),
    INDEX sitemap_index (exclude_from_sitemap, sitemap_priority)
);
```

### Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship
            $table->morphs('seoable');

            // Basic Meta Tags
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->boolean('no_index')->default(false);
            $table->boolean('no_follow')->default(false);
            $table->string('robots_meta', 255)->nullable();

            // Open Graph
            $table->string('og_title', 255)->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image', 500)->nullable();
            $table->unsignedBigInteger('og_image_id')->nullable();
            $table->string('og_type', 50)->default('website');
            $table->string('og_locale', 10)->nullable();
            $table->string('og_site_name', 255)->nullable();

            // Twitter Card
            $table->string('twitter_card', 50)->default('summary_large_image');
            $table->string('twitter_title', 255)->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image', 500)->nullable();
            $table->unsignedBigInteger('twitter_image_id')->nullable();
            $table->string('twitter_site', 50)->nullable();
            $table->string('twitter_creator', 50)->nullable();

            // Pinterest
            $table->text('pinterest_description')->nullable();
            $table->string('pinterest_image', 500)->nullable();
            $table->unsignedBigInteger('pinterest_image_id')->nullable();

            // Slack
            $table->string('slack_title', 255)->nullable();
            $table->text('slack_description')->nullable();
            $table->string('slack_image', 500)->nullable();
            $table->unsignedBigInteger('slack_image_id')->nullable();

            // Schema.org
            $table->string('schema_type', 100)->nullable();
            $table->json('schema_markup')->nullable();

            // Focus Keyword
            $table->string('focus_keyword', 255)->nullable();
            $table->json('secondary_keywords')->nullable();

            // Multi-language
            $table->json('hreflang')->nullable();

            // Sitemap
            $table->decimal('sitemap_priority', 2, 1)->default(0.5);
            $table->string('sitemap_changefreq', 20)->default('weekly');
            $table->boolean('exclude_from_sitemap')->default(false);

            $table->timestamps();

            // Additional indexes
            $table->index('focus_keyword');
            $table->index(['exclude_from_sitemap', 'sitemap_priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_meta');
    }
};
```

### Model

```php
<?php

namespace ArtisanPackUI\SEO\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'seoable_type',
        'seoable_id',
        'meta_title',
        'meta_description',
        'canonical_url',
        'no_index',
        'no_follow',
        'robots_meta',
        'og_title',
        'og_description',
        'og_image',
        'og_image_id',
        'og_type',
        'og_locale',
        'og_site_name',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'twitter_image_id',
        'twitter_site',
        'twitter_creator',
        'pinterest_description',
        'pinterest_image',
        'pinterest_image_id',
        'slack_title',
        'slack_description',
        'slack_image',
        'slack_image_id',
        'schema_type',
        'schema_markup',
        'focus_keyword',
        'secondary_keywords',
        'hreflang',
        'sitemap_priority',
        'sitemap_changefreq',
        'exclude_from_sitemap',
    ];

    protected function casts(): array
    {
        return [
            'no_index' => 'boolean',
            'no_follow' => 'boolean',
            'exclude_from_sitemap' => 'boolean',
            'schema_markup' => 'array',
            'secondary_keywords' => 'array',
            'hreflang' => 'array',
            'sitemap_priority' => 'decimal:1',
        ];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function analysisCache(): HasOne
    {
        return $this->hasOne(SeoAnalysisCache::class, 'seo_meta_id');
    }

    // Helper methods
    public function getEffectiveTitle(): string
    {
        return $this->meta_title
            ?? $this->seoable?->title
            ?? config('app.name');
    }

    public function getEffectiveDescription(): ?string
    {
        return $this->meta_description
            ?? $this->seoable?->excerpt
            ?? null;
    }

    public function getEffectiveOgImage(): ?string
    {
        if ($this->og_image_id && class_exists('ArtisanPackUI\MediaLibrary\Models\Media')) {
            return \ArtisanPackUI\MediaLibrary\Models\Media::find($this->og_image_id)?->url;
        }

        return $this->og_image
            ?? $this->seoable?->featured_image
            ?? null;
    }

    public function getRobotsContent(): string
    {
        $directives = [];

        if ($this->no_index) {
            $directives[] = 'noindex';
        }

        if ($this->no_follow) {
            $directives[] = 'nofollow';
        }

        if ($this->robots_meta) {
            $directives[] = $this->robots_meta;
        }

        return empty($directives) ? 'index, follow' : implode(', ', $directives);
    }
}
```

---

## Table: redirects

Stores URL redirects with support for exact matches, regex patterns, and wildcards.

### Schema

```sql
CREATE TABLE redirects (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Redirect paths
    from_path VARCHAR(500) NOT NULL,
    to_path VARCHAR(500) NOT NULL,

    -- Redirect type
    status_code SMALLINT UNSIGNED DEFAULT 301,  -- 301, 302, 307, 308

    -- Match type
    match_type ENUM('exact', 'regex', 'wildcard') DEFAULT 'exact',

    -- Status
    is_active BOOLEAN DEFAULT TRUE,

    -- Analytics
    hits INT UNSIGNED DEFAULT 0,
    last_hit_at TIMESTAMP NULL,

    -- Metadata
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,           -- User who created

    -- For chains/loops detection
    checked_at TIMESTAMP NULL,
    chain_status ENUM('ok', 'chain', 'loop') DEFAULT 'ok',
    chain_details JSON NULL,

    -- Timestamps
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    -- Indexes
    UNIQUE KEY unique_from_path (from_path),
    INDEX active_redirects (is_active, match_type),
    INDEX hit_tracking (hits, last_hit_at)
);
```

### Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();

            // Redirect paths
            $table->string('from_path', 500);
            $table->string('to_path', 500);

            // Redirect type
            $table->unsignedSmallInteger('status_code')->default(301);

            // Match type
            $table->enum('match_type', ['exact', 'regex', 'wildcard'])->default('exact');

            // Status
            $table->boolean('is_active')->default(true);

            // Analytics
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            // Chain detection
            $table->timestamp('checked_at')->nullable();
            $table->enum('chain_status', ['ok', 'chain', 'loop'])->default('ok');
            $table->json('chain_details')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique('from_path');
            $table->index(['is_active', 'match_type']);
            $table->index(['hits', 'last_hit_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
```

### Model

```php
<?php

namespace ArtisanPackUI\SEO\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Redirect extends Model
{
    protected $fillable = [
        'from_path',
        'to_path',
        'status_code',
        'match_type',
        'is_active',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'status_code' => 'integer',
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
            'checked_at' => 'datetime',
            'chain_details' => 'array',
        ];
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeExactMatch(Builder $query): Builder
    {
        return $query->where('match_type', 'exact');
    }

    public function scopePatterns(Builder $query): Builder
    {
        return $query->whereIn('match_type', ['regex', 'wildcard']);
    }

    public function scopeHasIssues(Builder $query): Builder
    {
        return $query->whereIn('chain_status', ['chain', 'loop']);
    }

    // Methods
    public function incrementHit(): void
    {
        $this->increment('hits');
        $this->update(['last_hit_at' => now()]);
    }

    public function matches(string $path): bool
    {
        return match ($this->match_type) {
            'exact' => $this->from_path === $path,
            'regex' => (bool) preg_match($this->from_path, $path),
            'wildcard' => $this->matchWildcard($path),
            default => false,
        };
    }

    protected function matchWildcard(string $path): bool
    {
        $pattern = str_replace(['*', '?'], ['.*', '.'], preg_quote($this->from_path, '/'));
        return (bool) preg_match('/^' . $pattern . '$/i', $path);
    }

    public function getResolvedDestination(string $originalPath): string
    {
        if ($this->match_type === 'exact') {
            return $this->to_path;
        }

        // For regex, support backreferences
        if ($this->match_type === 'regex') {
            return preg_replace($this->from_path, $this->to_path, $originalPath);
        }

        // For wildcard, replace * with captured content
        if ($this->match_type === 'wildcard') {
            $pattern = str_replace(['*', '?'], ['(.*)', '(.)'], preg_quote($this->from_path, '/'));
            return preg_replace('/^' . $pattern . '$/i', $this->to_path, $originalPath);
        }

        return $this->to_path;
    }

    public function isChain(): bool
    {
        return $this->chain_status === 'chain';
    }

    public function isLoop(): bool
    {
        return $this->chain_status === 'loop';
    }
}
```

---

## Table: sitemap_entries

Cached sitemap entries for improved performance on large sites.

### Schema

```sql
CREATE TABLE sitemap_entries (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Sitemap identification
    sitemap_type VARCHAR(50) NOT NULL,        -- pages, posts, products, images, video, news

    -- Entry data
    loc VARCHAR(500) NOT NULL,                -- URL
    lastmod TIMESTAMP NULL,
    changefreq VARCHAR(20) DEFAULT 'weekly',
    priority DECIMAL(2,1) DEFAULT 0.5,

    -- For image sitemaps
    images JSON NULL,                          -- Array of image data

    -- For video sitemaps
    video JSON NULL,                           -- Video metadata

    -- For news sitemaps
    news JSON NULL,                            -- News metadata

    -- Source tracking
    source_type VARCHAR(255) NULL,            -- Model class
    source_id BIGINT UNSIGNED NULL,

    -- Timestamps
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    -- Indexes
    INDEX sitemap_type_index (sitemap_type),
    INDEX source_index (source_type, source_id),
    INDEX loc_index (loc)
);
```

### Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitemap_entries', function (Blueprint $table) {
            $table->id();

            // Sitemap identification
            $table->string('sitemap_type', 50);

            // Entry data
            $table->string('loc', 500);
            $table->timestamp('lastmod')->nullable();
            $table->string('changefreq', 20)->default('weekly');
            $table->decimal('priority', 2, 1)->default(0.5);

            // Extended data
            $table->json('images')->nullable();
            $table->json('video')->nullable();
            $table->json('news')->nullable();

            // Source tracking
            $table->string('source_type', 255)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('sitemap_type');
            $table->index(['source_type', 'source_id']);
            $table->index('loc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitemap_entries');
    }
};
```

### Model

```php
<?php

namespace ArtisanPackUI\SEO\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SitemapEntry extends Model
{
    protected $fillable = [
        'sitemap_type',
        'loc',
        'lastmod',
        'changefreq',
        'priority',
        'images',
        'video',
        'news',
        'source_type',
        'source_id',
    ];

    protected function casts(): array
    {
        return [
            'lastmod' => 'datetime',
            'priority' => 'decimal:1',
            'images' => 'array',
            'video' => 'array',
            'news' => 'array',
        ];
    }

    // Scopes
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('sitemap_type', $type);
    }

    public function scopeForModel(Builder $query, Model $model): Builder
    {
        return $query->where('source_type', get_class($model))
                     ->where('source_id', $model->getKey());
    }

    // Methods
    public function toSitemapArray(): array
    {
        return [
            'loc' => $this->loc,
            'lastmod' => $this->lastmod?->toIso8601String(),
            'changefreq' => $this->changefreq,
            'priority' => (string) $this->priority,
        ];
    }

    public function hasImages(): bool
    {
        return !empty($this->images);
    }

    public function hasVideo(): bool
    {
        return !empty($this->video);
    }

    public function hasNews(): bool
    {
        return !empty($this->news);
    }
}
```

---

## Table: seo_analysis_cache

Stores cached SEO analysis results for quick retrieval.

### Schema

```sql
CREATE TABLE seo_analysis_cache (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Relationship
    seo_meta_id BIGINT UNSIGNED NOT NULL,

    -- Analysis results
    overall_score TINYINT UNSIGNED DEFAULT 0,  -- 0-100
    readability_score TINYINT UNSIGNED NULL,
    keyword_score TINYINT UNSIGNED NULL,
    meta_score TINYINT UNSIGNED NULL,
    content_score TINYINT UNSIGNED NULL,

    -- Detailed results
    issues JSON NULL,                          -- Array of issues found
    suggestions JSON NULL,                     -- Array of improvement suggestions
    passed_checks JSON NULL,                   -- Array of passed checks

    -- Analysis metadata
    analyzed_at TIMESTAMP NULL,
    focus_keyword_used VARCHAR(255) NULL,
    content_word_count INT UNSIGNED NULL,

    -- Timestamps
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    -- Indexes
    FOREIGN KEY (seo_meta_id) REFERENCES seo_meta(id) ON DELETE CASCADE,
    INDEX score_index (overall_score)
);
```

### Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_analysis_cache', function (Blueprint $table) {
            $table->id();

            // Relationship
            $table->foreignId('seo_meta_id')
                  ->constrained('seo_meta')
                  ->cascadeOnDelete();

            // Analysis results
            $table->unsignedTinyInteger('overall_score')->default(0);
            $table->unsignedTinyInteger('readability_score')->nullable();
            $table->unsignedTinyInteger('keyword_score')->nullable();
            $table->unsignedTinyInteger('meta_score')->nullable();
            $table->unsignedTinyInteger('content_score')->nullable();

            // Detailed results
            $table->json('issues')->nullable();
            $table->json('suggestions')->nullable();
            $table->json('passed_checks')->nullable();

            // Analysis metadata
            $table->timestamp('analyzed_at')->nullable();
            $table->string('focus_keyword_used', 255)->nullable();
            $table->unsignedInteger('content_word_count')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('overall_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_analysis_cache');
    }
};
```

### Model

```php
<?php

namespace ArtisanPackUI\SEO\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoAnalysisCache extends Model
{
    protected $table = 'seo_analysis_cache';

    protected $fillable = [
        'seo_meta_id',
        'overall_score',
        'readability_score',
        'keyword_score',
        'meta_score',
        'content_score',
        'issues',
        'suggestions',
        'passed_checks',
        'analyzed_at',
        'focus_keyword_used',
        'content_word_count',
    ];

    protected function casts(): array
    {
        return [
            'overall_score' => 'integer',
            'readability_score' => 'integer',
            'keyword_score' => 'integer',
            'meta_score' => 'integer',
            'content_score' => 'integer',
            'issues' => 'array',
            'suggestions' => 'array',
            'passed_checks' => 'array',
            'analyzed_at' => 'datetime',
            'content_word_count' => 'integer',
        ];
    }

    public function seoMeta(): BelongsTo
    {
        return $this->belongsTo(SeoMeta::class, 'seo_meta_id');
    }

    // Score interpretation
    public function getScoreGrade(): string
    {
        return match (true) {
            $this->overall_score >= 80 => 'good',
            $this->overall_score >= 50 => 'ok',
            default => 'poor',
        };
    }

    public function getScoreColor(): string
    {
        return match ($this->getScoreGrade()) {
            'good' => 'green',
            'ok' => 'yellow',
            'poor' => 'red',
        };
    }

    public function hasIssues(): bool
    {
        return !empty($this->issues);
    }

    public function getIssueCount(): int
    {
        return count($this->issues ?? []);
    }

    public function getSuggestionCount(): int
    {
        return count($this->suggestions ?? []);
    }

    public function isStale(int $hours = 24): bool
    {
        if (!$this->analyzed_at) {
            return true;
        }

        return $this->analyzed_at->lt(now()->subHours($hours));
    }
}
```

---

## Entity Relationship Diagram

```
┌──────────────────┐       ┌──────────────────────┐
│   Any Model      │       │      seo_meta        │
│   (Page, Post,   │       │                      │
│    Product, etc) │       │  id                  │
│                  │◄──────│  seoable_type        │
│  + HasSeo trait  │       │  seoable_id          │
│                  │       │  meta_title          │
└──────────────────┘       │  meta_description    │
                           │  og_* fields         │
                           │  twitter_* fields    │
                           │  schema_markup       │
                           │  focus_keyword       │
                           │  hreflang            │
                           └──────────┬───────────┘
                                      │
                                      │ 1:1
                                      ▼
                           ┌──────────────────────┐
                           │  seo_analysis_cache  │
                           │                      │
                           │  id                  │
                           │  seo_meta_id         │
                           │  overall_score       │
                           │  issues              │
                           │  suggestions         │
                           └──────────────────────┘

┌──────────────────────┐
│      redirects       │
│                      │
│  id                  │
│  from_path           │
│  to_path             │
│  status_code         │
│  match_type          │
│  hits                │
│  chain_status        │
└──────────────────────┘

┌──────────────────────┐
│   sitemap_entries    │
│                      │
│  id                  │
│  sitemap_type        │
│  loc                 │
│  lastmod             │
│  images              │
│  video               │
│  news                │
└──────────────────────┘
```

---

## Related Documents

- [01-architecture.md](01-architecture.md) - Package architecture
- [04-traits-and-models.md](04-traits-and-models.md) - HasSeo trait implementation
