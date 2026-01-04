# Basic Blade Components

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::High"

## Task Description

Create the core Blade components for outputting SEO tags in the `<head>` section of layouts.

## Acceptance Criteria

- [ ] `<x-seo:meta>` - All-in-one component for complete SEO output
- [ ] `<x-seo:meta-tags>` - Basic meta tags only (title, description, canonical, robots)
- [ ] `<x-seo:open-graph>` - Open Graph tags only
- [ ] `<x-seo:twitter-card>` - Twitter Card tags only
- [ ] Components accept both model and manual parameters
- [ ] Proper escaping of all output values
- [ ] Components registered with `x-seo:` prefix
- [ ] Feature tests for component output

## Context

These components are how developers output SEO tags in their layouts.

**Related Issues:**
- Depends on: #01-04-core-services
- Part of Phase 1 deliverables

## Notes

### Usage Examples
```blade
{{-- All-in-one --}}
<x-seo:meta :model="$page" />

{{-- Manual values --}}
<x-seo:meta
    title="Page Title"
    description="Page description"
    image="/og-image.jpg"
/>

{{-- Individual components --}}
<x-seo:meta-tags :model="$page" />
<x-seo:open-graph :model="$page" />
<x-seo:twitter-card :model="$page" />
```

### Component Parameters
- `:model` - Eloquent model with HasSeo trait
- `title` - Override title
- `description` - Override description
- `image` - Override OG/Twitter image
- `canonical` - Override canonical URL
- `:include-schema` - Include JSON-LD (default: true)
- `:include-open-graph` - Include OG tags (default: true)
- `:include-twitter-card` - Include Twitter tags (default: true)

**Reference:** [07-blade-components.md](../07-blade-components.md)
