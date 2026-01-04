# Schema Blade Component

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::Medium"

## Task Description

Create the `<x-seo:schema>` Blade component for outputting JSON-LD structured data.

## Acceptance Criteria

- [ ] Component outputs valid JSON-LD script tags
- [ ] Supports multiple schema items per page
- [ ] Can pass model for auto-generation
- [ ] Can pass custom schema arrays
- [ ] Options for organization/website schema inclusion
- [ ] Proper JSON escaping for security
- [ ] Feature tests for output

## Context

The schema component makes it easy to add structured data to any page.

**Related Issues:**
- Depends on: #02-01-schema-service

## Notes

### Usage Examples
```blade
{{-- Auto-generate from model --}}
<x-seo:schema :model="$page" />

{{-- Include org and website schema --}}
<x-seo:schema
    :include-organization="true"
    :include-website="true"
/>

{{-- Pass custom schemas --}}
<x-seo:schema :schemas="[$productSchema, $reviewSchema]" />
```

### Component Output
```html
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "...",
    ...
}
</script>
```

**Reference:** [07-blade-components.md](../07-blade-components.md)
