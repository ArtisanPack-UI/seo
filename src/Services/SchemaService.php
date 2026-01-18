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
 * @copyright  2026 Jacob Martella
 * @license    MIT
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Schema\SchemaFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * SchemaService class.
 *
 * Coordinates Schema.org structured data generation using
 * the factory pattern to create appropriate schema builders.
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
	 * Generate multiple schemas and wrap in a graph.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<int, array<string, mixed>>  $schemas  The schemas to include.
	 *
	 * @return array<string, mixed>
	 */
	public function generateGraph( array $schemas ): array
	{
		return [
			'@context' => 'https://schema.org',
			'@graph'   => $schemas,
		];
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
}
