/**
 * Schema type definitions.
 *
 * TypeScript types for Schema.org configuration matching the SchemaFactory,
 * schema builders, and SchemaResource API response shapes.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

/**
 * Union of the 13 built-in Schema.org type names.
 */
export type SchemaType =
    | 'Organization'
    | 'LocalBusiness'
    | 'WebSite'
    | 'WebPage'
    | 'Article'
    | 'BlogPosting'
    | 'Product'
    | 'Service'
    | 'Event'
    | 'FAQPage'
    | 'BreadcrumbList'
    | 'Review'
    | 'AggregateRating';

/**
 * Schema field types for dynamic form rendering.
 */
export type SchemaFieldType = 'text' | 'textarea' | 'url' | 'number' | 'date' | 'datetime' | 'boolean' | 'array' | 'object' | 'select';

/**
 * Schema field definition for dynamic form rendering.
 */
export interface SchemaFieldDefinition {
    name: string;
    type: SchemaFieldType;
    required: boolean;
    description: string;
}

/**
 * Article schema configuration fields.
 */
export interface ArticleSchemaConfig {
    name?: string;
    headline?: string;
    description?: string;
    url?: string;
    image?: string;
    author?: PersonConfig[];
    publisher?: OrganizationConfig[];
    datePublished?: string;
    dateModified?: string;
    dateCreated?: string;
    articleBody?: string;
    wordCount?: number;
    keywords?: string;
    articleSection?: string;
    inLanguage?: string;
}

/**
 * Product schema configuration fields.
 */
export interface ProductSchemaConfig {
    name?: string;
    description?: string;
    image?: string;
    url?: string;
    sku?: string;
    gtin?: string;
    mpn?: string;
    brand?: string;
    offers?: OfferConfig[];
    aggregateRating?: AggregateRatingConfig;
    reviews?: ReviewItemConfig[];
    category?: string;
    color?: string;
    material?: string;
}

/**
 * Event schema configuration fields.
 */
export interface EventSchemaConfig {
    name?: string;
    description?: string;
    url?: string;
    image?: string;
    startDate: string;
    endDate?: string;
    location?: LocationConfig;
    virtualLocation?: string;
    eventStatus?: string;
    eventAttendanceMode?: string;
    organizer?: OrganizationConfig[];
    performer?: PersonConfig;
    offers?: OfferConfig[];
}

/**
 * FAQ page schema configuration fields.
 */
export interface FAQPageSchemaConfig {
    name?: string;
    description?: string;
    url?: string;
    questions: FAQQuestionConfig[];
}

/**
 * BreadcrumbList schema configuration fields.
 */
export interface BreadcrumbListSchemaConfig {
    items: BreadcrumbItemConfig[];
}

/**
 * Organization schema configuration fields.
 */
export interface OrganizationSchemaConfig {
    name?: string;
    url?: string;
    logo?: string;
    email?: string;
    phone?: string;
    telephone?: string;
    description?: string;
    address?: PostalAddressConfig;
    sameAs?: string[];
}

/**
 * LocalBusiness schema configuration fields (extends Organization).
 */
export interface LocalBusinessSchemaConfig extends OrganizationSchemaConfig {
    priceRange?: string;
    openingHours?: OpeningHoursConfig[];
    geo?: GeoCoordinatesConfig;
    areaServed?: string;
    paymentAccepted?: string;
    currenciesAccepted?: string;
}

/**
 * WebSite schema configuration fields.
 */
export interface WebSiteSchemaConfig {
    name?: string;
    url?: string;
    description?: string;
    publisher?: OrganizationConfig[];
    searchUrl?: string;
    alternateName?: string;
    inLanguage?: string;
}

/**
 * WebPage schema configuration fields.
 */
export interface WebPageSchemaConfig {
    name?: string;
    url?: string;
    description?: string;
    datePublished?: string;
    dateModified?: string;
    image?: string;
    author?: PersonConfig[];
    publisher?: OrganizationConfig[];
    breadcrumb?: BreadcrumbItemConfig[];
    isPartOf?: string;
    inLanguage?: string;
}

/**
 * Service schema configuration fields.
 */
export interface ServiceSchemaConfig {
    name?: string;
    description?: string;
    url?: string;
    image?: string;
    provider?: OrganizationConfig[];
    areaServed?: string;
    serviceType?: string;
    category?: string;
    offers?: OfferConfig[];
    aggregateRating?: AggregateRatingConfig;
    brand?: string;
}

/**
 * Review schema configuration fields.
 */
export interface ReviewSchemaConfig {
    name?: string;
    reviewBody?: string;
    body?: string;
    content?: string;
    author: PersonConfig;
    rating?: RatingConfig;
    reviewRating?: RatingConfig;
    datePublished?: string;
    itemReviewed?: ItemReviewedConfig;
    publisher?: OrganizationConfig[];
}

/**
 * AggregateRating schema configuration fields.
 */
export interface AggregateRatingSchemaConfig {
    ratingValue: number;
    value?: number;
    bestRating?: number;
    worstRating?: number;
    ratingCount: number;
    count?: number;
    reviewCount?: number;
    itemReviewed?: ItemReviewedConfig;
}

/**
 * Union of all schema configuration types.
 */
export type SchemaConfig =
    | ArticleSchemaConfig
    | ProductSchemaConfig
    | EventSchemaConfig
    | FAQPageSchemaConfig
    | BreadcrumbListSchemaConfig
    | OrganizationSchemaConfig
    | LocalBusinessSchemaConfig
    | WebSiteSchemaConfig
    | WebPageSchemaConfig
    | ServiceSchemaConfig
    | ReviewSchemaConfig
    | AggregateRatingSchemaConfig;

/**
 * Schema API response from SchemaResource.
 */
export interface SchemaResponse {
    schema_type: SchemaType | null;
    schema_markup: Record<string, unknown> | null;
    generated: Record<string, unknown> | null;
    available_types: SchemaType[];
}

/**
 * Helper types used across schema configurations.
 */

export interface PersonConfig {
    name: string;
    url?: string;
}

export interface OrganizationConfig {
    name: string;
    url?: string;
    logo?: string;
}

export interface OfferConfig {
    price?: string | number;
    priceCurrency?: string;
    availability?: string;
    url?: string;
    validFrom?: string;
}

export interface AggregateRatingConfig {
    ratingValue: number;
    reviewCount?: number;
    bestRating?: number;
    worstRating?: number;
}

export interface ReviewItemConfig {
    author: string;
    reviewBody: string;
    reviewRating?: RatingConfig;
    datePublished?: string;
}

export interface RatingConfig {
    ratingValue: number;
    bestRating?: number;
    worstRating?: number;
}

export interface LocationConfig {
    name?: string;
    address?: PostalAddressConfig;
}

export interface PostalAddressConfig {
    streetAddress?: string;
    addressLocality?: string;
    addressRegion?: string;
    postalCode?: string;
    addressCountry?: string;
}

export interface GeoCoordinatesConfig {
    latitude: number;
    longitude: number;
}

export interface OpeningHoursConfig {
    dayOfWeek: string;
    opens: string;
    closes: string;
}

export interface FAQQuestionConfig {
    question: string;
    answer: string;
}

export interface BreadcrumbItemConfig {
    name: string;
    url: string;
}

export interface ItemReviewedConfig {
    name: string;
    type?: string;
    url?: string;
}
