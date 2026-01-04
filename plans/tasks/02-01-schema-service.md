# Schema.org Service and Builders

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::High"

## Task Description

Implement the `SchemaService` and individual schema type builders for generating JSON-LD structured data.

## Acceptance Criteria

- [ ] `SchemaService` coordinates schema generation
- [ ] `SchemaFactory` creates appropriate builder based on type
- [ ] Schema builders implemented for all types (see list below)
- [ ] Each builder implements `SchemaTypeContract`
- [ ] JSON-LD output is valid per schema.org spec
- [ ] Organization schema pulls from config or cms-framework
- [ ] Unit tests for each schema type

## Context

Schema.org structured data helps search engines understand content and enables rich results.

**Related Issues:**
- Depends on: Phase 1 completion
- Required by: #02-02-schema-blade-component

## Notes

### Schema Types to Implement
1. `OrganizationSchema`
2. `LocalBusinessSchema`
3. `WebsiteSchema`
4. `WebPageSchema`
5. `ArticleSchema`
6. `BlogPostingSchema`
7. `ProductSchema`
8. `ServiceSchema`
9. `EventSchema`
10. `FAQPageSchema`
11. `BreadcrumbListSchema`
12. `ReviewSchema`
13. `AggregateRatingSchema`

### SchemaService Interface
```php
class SchemaService
{
    public function generate(Model $model, ?SeoMeta $seoMeta = null): array;
    public function generateOrganizationSchema(): array;
    public function generateWebsiteSchema(): array;
    public function generateBreadcrumbs(array $items): array;
}
```

### Builder Contract
```php
interface SchemaTypeContract
{
    public function generate(?Model $model = null): array;
    public function getType(): string;
}
```

**Reference:** [03-core-services.md](../03-core-services.md)
