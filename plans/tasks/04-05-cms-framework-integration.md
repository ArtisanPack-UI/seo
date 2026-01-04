# CMS Framework Integration

/label ~"Type::Feature" ~"Status::Backlog" ~"Priority::Medium"

## Task Description

Create the optional integration with the `artisanpack-ui/cms-framework` package for GlobalContent and Page/Post models.

## Acceptance Criteria

- [ ] `PackageDetector::hasCmsFramework()` check
- [ ] `CmsFrameworkIntegration` service class
- [ ] Get GlobalContent values for organization schema
- [ ] Get organization data (name, address, phone, email, social profiles)
- [ ] Get sitemap-eligible pages from CMS
- [ ] Get sitemap-eligible posts from CMS
- [ ] OrganizationSchema uses CMS data when available
- [ ] Graceful fallback to config when package not installed
- [ ] Feature tests with mocked CMS framework

## Context

This integration enables pulling business information from GlobalContent for schema markup.

**Related Issues:**
- Depends on: #02-01-schema-service

## Notes

### CmsFrameworkIntegration
```php
class CmsFrameworkIntegration
{
    public function getGlobalContent(string $key, mixed $default = null): mixed;
    public function getOrganizationData(): array;
    protected function getSocialProfiles(): array;
    protected function getDefaultOrganizationData(): array;
    public function getSitemapPages(): Collection;
    public function getSitemapPosts(): Collection;
}
```

### Organization Data Structure
```php
return [
    'name' => $this->getGlobalContent('business_name', config('app.name')),
    'url' => config('app.url'),
    'logo' => $this->getGlobalContent('logo_url'),
    'telephone' => $this->getGlobalContent('phone'),
    'email' => $this->getGlobalContent('email'),
    'address' => [
        'streetAddress' => $this->getGlobalContent('address'),
        'addressLocality' => $this->getGlobalContent('city'),
        'addressRegion' => $this->getGlobalContent('state'),
        'postalCode' => $this->getGlobalContent('zip'),
        'addressCountry' => $this->getGlobalContent('country', 'US'),
    ],
    'openingHours' => $this->getGlobalContent('business_hours'),
    'priceRange' => $this->getGlobalContent('price_range'),
    'sameAs' => $this->getSocialProfiles(),
];
```

### Social Profile Keys
- facebook_url
- twitter_url
- instagram_url
- linkedin_url
- youtube_url

**Reference:** [08-integrations.md](../08-integrations.md)
