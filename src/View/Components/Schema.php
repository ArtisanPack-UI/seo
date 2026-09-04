<?php

/**
 * Schema Blade Component.
 *
 * Component for outputting JSON-LD structured data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\View\Components;

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\SchemaService;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

/**
 * Schema component class.
 *
 * Outputs Schema.org JSON-LD structured data for SEO.
 * Supports auto-generation from models, custom schemas,
 * and optional organization/website schema inclusion.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class Schema extends Component
{
	/**
	 * The collected schemas to output.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $collectedSchemas = [];

	/**
	 * Whether to use a graph wrapper for multiple schemas.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	public bool $useGraph;

	/**
	 * The schema service instance.
	 *
	 * @since 1.0.0
	 *
	 * @var SchemaService
	 */
	protected SchemaService $schemaService;

	/**
	 * Create a new component instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|null                       $model                The model to generate schema from.
	 * @param  array<int, array<string, mixed>> $schemas              Custom schema arrays to include.
	 * @param  bool                             $includeOrganization  Include organization schema.
	 * @param  bool                             $includeWebsite       Include website schema.
	 * @param  bool                             $useGraph             Use @graph wrapper for multiple schemas.
	 * @param  array<int, array<string, string>>|null $breadcrumbs    Breadcrumb items.
	 */
	public function __construct(
		?Model $model = null,
		array $schemas = [],
		bool $includeOrganization = false,
		bool $includeWebsite = false,
		bool $useGraph = true,
		?array $breadcrumbs = null,
	) {
		$this->schemaService = app( SchemaService::class );
		$this->useGraph      = $useGraph;

		// Add organization schema if requested.
		if ( $includeOrganization ) {
			$this->addOrganizationSchema();
		}

		// Add website schema if requested.
		if ( $includeWebsite ) {
			$this->addWebsiteSchema();
		}

		// Add breadcrumbs if provided.
		if ( null !== $breadcrumbs && ! empty( $breadcrumbs ) ) {
			$this->addBreadcrumbsSchema( $breadcrumbs );
		}

		// Generate schema from model if provided.
		if ( null !== $model ) {
			$this->addModelSchema( $model );
		}

		// Add custom schemas.
		foreach ( $schemas as $schema ) {
			if ( is_array( $schema ) && ! empty( $schema ) ) {
				$this->collectedSchemas[] = $this->ensureContext( $schema );
			}
		}
	}

	/**
	 * Get the view that represents the component.
	 *
	 * @since 1.0.0
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'seo::components.schema' );
	}

	/**
	 * Get the JSON-LD output.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getJsonLd(): string
	{
		if ( empty( $this->collectedSchemas ) ) {
			return '';
		}

		// Single schema without graph.
		if ( 1 === count( $this->collectedSchemas ) && ! $this->useGraph ) {
			return $this->schemaService->toJsonLd( $this->collectedSchemas[0] );
		}

		// Multiple schemas or forced graph.
		if ( $this->useGraph && count( $this->collectedSchemas ) > 1 ) {
			$graph = $this->schemaService->generateGraph(
				array_map( [ $this, 'stripContext' ], $this->collectedSchemas ),
			);

			return $this->schemaService->toJsonLd( $graph );
		}

		// Single schema or non-graph mode with multiple schemas.
		$output = '';
		foreach ( $this->collectedSchemas as $schema ) {
			$output .= $this->schemaService->toJsonLd( $schema );
		}

		return $output;
	}

	/**
	 * Add organization schema.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function addOrganizationSchema(): void
	{
		$schema = $this->schemaService->generateOrganizationSchema();
		if ( ! empty( $schema ) ) {
			$this->collectedSchemas[] = $schema;
		}
	}

	/**
	 * Add website schema.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function addWebsiteSchema(): void
	{
		$schema = $this->schemaService->generateWebsiteSchema();
		if ( ! empty( $schema ) ) {
			$this->collectedSchemas[] = $schema;
		}
	}

	/**
	 * Add breadcrumbs schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<int, array{name: string, url: string}>  $items  The breadcrumb items.
	 *
	 * @return void
	 */
	protected function addBreadcrumbsSchema( array $items ): void
	{
		$schema = $this->schemaService->generateBreadcrumbs( $items );
		if ( ! empty( $schema ) ) {
			$this->collectedSchemas[] = $schema;
		}
	}

	/**
	 * Add schema from a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to generate schema from.
	 *
	 * @return void
	 */
	protected function addModelSchema( Model $model ): void
	{
		$seoMeta = null;

		// Check if model uses HasSeo trait.
		if ( $this->modelHasSeoTrait( $model ) ) {
			/** @var HasSeo&Model $model */
			$seoMeta = $model->seoMeta;
		} elseif ( isset( $model->seoMeta ) && $model->seoMeta instanceof SeoMeta ) {
			// Also check for direct seoMeta property (for testing or custom implementations).
			$seoMeta = $model->seoMeta;
		}

		$schema = $this->schemaService->generate( $model, $seoMeta );
		if ( ! empty( $schema ) ) {
			$this->collectedSchemas[] = $schema;
		}
	}

	/**
	 * Check if model uses HasSeo trait.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to check.
	 *
	 * @return bool
	 */
	protected function modelHasSeoTrait( Model $model ): bool
	{
		return in_array( HasSeo::class, class_uses_recursive( $model ), true );
	}

	/**
	 * Ensure schema has @context.
	 *
	 * @since 1.0.0
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
	 * Strip @context from schema for graph inclusion.
	 *
	 * @since 1.0.0
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
