---
title: SEO Dashboard
---

# SEO Dashboard

The SEO Dashboard is a Livewire component that provides an overview of your site's SEO health and key metrics.

## Basic Usage

```blade
<livewire:seo::seo-dashboard />
```

## Features

The dashboard shows search performance from the analytics integration:

- **Clicks** and **impressions**
- **Average position** and **average click-through rate**
- **Top pages** and **top queries**
- A **period** selector, defaulting to the last 30 days

Performance data needs a configured analytics package. Without one, the dashboard shows that analytics isn't available.

## Customization

### Publishing Views

```bash
php artisan vendor:publish --tag=seo-views
```

### Extending the Component

```php
namespace App\Livewire;

use ArtisanPackUI\SEO\Livewire\SeoDashboard as BaseSeoDashboard;

class CustomSeoDashboard extends BaseSeoDashboard
{
    public string $period = '7d';
}
```

## Next Steps

- [SEO Meta Editor](Components-Seo-Meta-Editor) - Edit SEO data
- [Analysis Panel](Components-Analysis-Panel) - SEO analysis
- [Redirect Manager](Components-Redirect-Manager) - Manage redirects
