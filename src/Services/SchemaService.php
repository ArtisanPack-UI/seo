<?php

/**
 * SchemaService.
 *
 * Service for generating Schema.org JSON-LD structured data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Schema\SchemaFactory;
use ArtisanPackUI\SEO\Support\SchemaCollector;
use ArtisanPackUI\SEO\Traits\HasSeo;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use function applyFilters;
use function doAction;

/**
 * SchemaService class.
 *
 * Coordinates Schema.org structured data generation using
 * the factory pattern to create appropriate schema builders.
 * Owns the full render pipeline: merge -> filter -> render actions
 * -> JSON-LD encoding.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SchemaService
{
	/**
	 * Create a new SchemaService instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  SchemaFactory  $factory  The schema factory.
	 */
	public function __construct(
		protected SchemaFactory $factory,
	) {
	}

	/**
	 * Generate schema data for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model         $model    The model to generate schema for.
	 * @param  SeoMeta|null  $seoMeta  Optional SeoMeta instance.
	 *
	 * @return array<string, mixed>
	 */
	public function generate( Model $model, ?SeoMeta $seoMeta = null ): array
	{
		$type = $this->resolveSchemaType( $model, $seoMeta );

		if ( ! $this->factory->supports( $type ) ) {
			$type = 'WebPage';
		}

		$data = $this->extractModelData( $model, $seoMeta );

		return $this->factory->make( $type, $data )->generate( $model );
	}

	/**
	 * Generate Organization schema from config or CMS framework.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function generateOrganizationSchema(): array
	{
		$data = $this->getOrganizationData();

		return $this->factory->make( 'Organization', $data )->generate();
	}

	/**
	 * Generate WebSite schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function generateWebsiteSchema(): array
	{
		$data = [
			'name'        => config( 'seo.site.name', config( 'app.name', '' ) ),
			'description' => config( 'seo.site.description', '' ),
			'url'         => config( 'app.url', '' ),
		];

		return $this->factory->make( 'WebSite', $data )->generate();
	}

	/**
	 * Generate BreadcrumbList schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<int, array{name: string, url: string}>  $items  The breadcrumb items.
	 *
	 * @return array<string, mixed>
	 */
	public function generateBreadcrumbs( array $items ): array
	{
		return $this->factory->make( 'BreadcrumbList', [ 'items' => $items ] )->generate();
	}

	/**
	 * Build the merged, filtered schema graph for a page.
	 *
	 * Merges entries from every contributing source in the pipeline:
	 * component-supplied schemas, model schema (via `HasSeo`/`getSchemaType()`
	 * or class-name inference), optional Organization/WebSite/Breadcrumb
	 * schemas, arbitrary `SeoMeta::$schema_markup`, and entries pushed at
	 * render time via `apSeoAddSchema()` (drained from {@see SchemaCollector}).
	 * The merged list is then passed through the `ap.seo.schemaGraph` filter
	 * so integrations may add, remove, or rewrite entries before rendering.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<int, array<string, mixed>>                   $componentSchemas    Extra schemas provided by the caller.
	 * @param  Model|null                                         $model               Model to derive schema from, if any.
	 * @param  bool                                               $includeOrganization Include Organization schema.
	 * @param  bool                                               $includeWebsite      Include WebSite schema.
	 * @param  array<int, array{name: string, url: string}>|null  $breadcrumbs         Breadcrumb items, if any.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function generateGraph(
		array $componentSchemas,
		?Model $model = null,
		bool $includeOrganization = false,
		bool $includeWebsite = false,
		?array $breadcrumbs = null,
	): array {
		$graph = [];

		if ( $includeOrganization ) {
			$schema = $this->generateOrganizationSchema();
			if ( ! empty( $schema ) ) {
				$graph[] = $schema;
			}
		}

		if ( $includeWebsite ) {
			$schema = $this->generateWebsiteSchema();
			if ( ! empty( $schema ) ) {
				$graph[] = $schema;
			}
		}

		if ( null !== $breadcrumbs && ! empty( $breadcrumbs ) ) {
			$schema = $this->generateBreadcrumbs( $breadcrumbs );
			if ( ! empty( $schema ) ) {
				$graph[] = $schema;
			}
		}

		if ( null !== $model ) {
			$schema = $this->generate( $model, $this->resolveModelSeoMeta( $model ) );
			if ( ! empty( $schema ) ) {
				$graph[] = $schema;
			}
		}

		foreach ( $componentSchemas as $schema ) {
			if ( is_array( $schema ) && ! empty( $schema ) ) {
				$graph[] = $schema;
			}
		}

		foreach ( app( SchemaCollector::class )->flush() as $entry ) {
			if ( is_array( $entry ) && ! empty( $entry ) ) {
				$graph[] = $entry;
			}
		}

		/** @var array<int, array<string, mixed>> $filtered */
		$filtered = applyFilters( 'ap.seo.schemaGraph', $graph, $model );

		return array_values( $filtered );
	}

	/**
	 * Render a schema graph as a JSON-LD script tag.
	 *
	 * Fires `ap.seo.schemaRendering` before encoding and
	 * `ap.seo.schemaRendered` after, so integrations can observe the
	 * final payload without mutating it. A single-entry graph is emitted
	 * on its own; multiple entries are wrapped in a schema.org `@graph`.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<int, array<string, mixed>>  $graph  The graph produced by {@see generateGraph()}.
	 * @param  Model|null                        $model  The model associated with this render, if any.
	 *
	 * @return string
	 */
	public function renderGraph( array $graph, ?Model $model = null ): string
	{
		if ( empty( $graph ) ) {
			return '';
		}

		doAction( 'ap.seo.schemaRendering', $graph, $model );

		if ( 1 === count( $graph ) ) {
			$payload = $this->ensureContext( $graph[0] );
		} else {
			$payload = [
				'@context' => 'https://schema.org',
				'@graph'   => array_map(
					fn ( array $entry ): array => $this->stripContext( $entry ),
					array_values( $graph ),
				),
			];
		}

		$output = $this->toJsonLd( $payload );

		doAction( 'ap.seo.schemaRendered', $graph, $model );

		return $output;
	}

	/**
	 * Convert schema data to JSON-LD script tag.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>  $schema  The schema data.
	 *
	 * @throws RuntimeException If JSON encoding fails.
	 *
	 * @return string
	 */
	public function toJsonLd( array $schema ): string
	{
		$json = json_encode(
			$schema,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP,
		);

		if ( false === $json ) {
			throw new RuntimeException(
				sprintf(
					'Failed to encode schema to JSON: %s (error code: %d)',
					json_last_error_msg(),
					json_last_error(),
				),
			);
		}

		return '<script type="application/ld+json">' . $json . '</script>';
	}

	/**
	 * Resolve the schema type for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model         $model    The model.
	 * @param  SeoMeta|null  $seoMeta  The SeoMeta instance.
	 *
	 * @return string
	 */
	protected function resolveSchemaType( Model $model, ?SeoMeta $seoMeta ): string
	{
		// Check SeoMeta first
		if ( null !== $seoMeta?->schema_type && '' !== $seoMeta->schema_type ) {
			return $seoMeta->schema_type;
		}

		// Check if model has getSchemaType method
		if ( method_exists( $model, 'getSchemaType' ) ) {
			return $model->getSchemaType();
		}

		// Infer from model class name
		return $this->inferSchemaType( $model );
	}

	/**
	 * Infer schema type from model class name.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model.
	 *
	 * @return string
	 */
	protected function inferSchemaType( Model $model ): string
	{
		$class = strtolower( class_basename( $model ) );

		$defaultTypes = config( 'seo.schema.default_types', [] );

		// Check config mapping
		if ( isset( $defaultTypes[ $class ] ) ) {
			return $defaultTypes[ $class ];
		}

		// Common mappings
		return match ( $class ) {
			'post', 'article'     => 'Article',
			'blog', 'blogpost'    => 'BlogPosting',
			'product'             => 'Product',
			'service'             => 'Service',
			'event'               => 'Event',
			'faq', 'faqpage'      => 'FAQPage',
			'review'              => 'Review',
			'organization', 'org' => 'Organization',
			'business'            => 'LocalBusiness',
			default               => 'WebPage',
		};
	}

	/**
	 * Extract data from model for schema generation.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model         $model    The model.
	 * @param  SeoMeta|null  $seoMeta  The SeoMeta instance.
	 *
	 * @return array<string, mixed>
	 */
	protected function extractModelData( Model $model, ?SeoMeta $seoMeta ): array
	{
		$data = [];

		// Basic properties
		$data['name']        = $seoMeta?->meta_title ?? $model->title ?? $model->name ?? '';
		$data['description'] = $seoMeta?->meta_description ?? $model->excerpt ?? $model->description ?? '';

		// URL
		if ( method_exists( $model, 'getUrl' ) ) {
			$data['url'] = $model->getUrl();
		} elseif ( isset( $model->slug ) ) {
			$data['url'] = url( $model->slug );
		}

		// Image
		$data['image'] = $seoMeta?->getEffectiveOgImage() ?? $model->featured_image ?? null;

		// Dates
		if ( isset( $model->created_at ) && $model->created_at instanceof DateTimeInterface ) {
			$data['dateCreated'] = $model->created_at->toIso8601String();
		}
		if ( isset( $model->published_at ) && $model->published_at instanceof DateTimeInterface ) {
			$data['datePublished'] = $model->published_at->toIso8601String();
		}
		if ( isset( $model->updated_at ) && $model->updated_at instanceof DateTimeInterface ) {
			$data['dateModified'] = $model->updated_at->toIso8601String();
		}

		// Author
		if ( isset( $model->author ) && null !== $model->author ) {
			$data['author'] = [
				'name' => $model->author->name ?? '',
				'url'  => method_exists( $model->author, 'getUrl' ) ? $model->author->getUrl() : null,
			];
		}

		// Custom schema markup from SeoMeta
		if ( null !== $seoMeta?->schema_markup && is_array( $seoMeta->schema_markup ) ) {
			$data = array_merge( $data, $seoMeta->schema_markup );
		}

		return $data;
	}

	/**
	 * Get organization data from config or CMS framework.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	protected function getOrganizationData(): array
	{
		// Check if CMS framework is available
		if ( class_exists( 'ArtisanPackUI\CmsFramework\Facades\Settings' ) ) {
			return $this->getOrganizationFromCms();
		}

		// Fall back to config
		return config( 'seo.schema.organization', [] );
	}

	/**
	 * Get organization data from CMS framework.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	protected function getOrganizationFromCms(): array
	{
		// Use the facade statically since class_exists was already verified
		$settingsClass = 'ArtisanPackUI\CmsFramework\Facades\Settings';

		return [
			'name'  => $settingsClass::get( 'site.name', config( 'app.name', '' ) ),
			'logo'  => $settingsClass::get( 'site.logo', null ),
			'url'   => $settingsClass::get( 'site.url', config( 'app.url', '' ) ),
			'email' => $settingsClass::get( 'site.email', null ),
			'phone' => $settingsClass::get( 'site.phone', null ),
		];
	}

	/**
	 * Resolve a SeoMeta instance from a model when one is attached.
	 *
	 * Mirrors the previous component-side lookup: prefers models that use
	 * the `HasSeo` trait, and falls back to a direct `seoMeta` property
	 * (useful in tests and custom implementations).
	 *
	 * @since 1.4.0
	 *
	 * @param  Model  $model  The model to inspect.
	 *
	 * @return SeoMeta|null
	 */
	protected function resolveModelSeoMeta( Model $model ): ?SeoMeta
	{
		if ( in_array( HasSeo::class, class_uses_recursive( $model ), true ) ) {
			/** @var SeoMeta|null $meta */
			$meta = $model->seoMeta;

			return $meta instanceof SeoMeta ? $meta : null;
		}

		if ( isset( $model->seoMeta ) && $model->seoMeta instanceof SeoMeta ) {
			return $model->seoMeta;
		}

		return null;
	}

	/**
	 * Ensure a schema has an `@context` key.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<string, mixed>  $schema  The schema data.
	 *
	 * @return array<string, mixed>
	 */
	protected function ensureContext( array $schema ): array
	{
		if ( ! isset( $schema['@context'] ) ) {
			$schema = [ '@context' => 'https://schema.org' ] + $schema;
		}

		return $schema;
	}

	/**
	 * Strip `@context` from a schema so it can nest inside `@graph`.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<string, mixed>  $schema  The schema data.
	 *
	 * @return array<string, mixed>
	 */
	protected function stripContext( array $schema ): array
	{
		unset( $schema['@context'] );

		return $schema;
	}
}
