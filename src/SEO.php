<?php

/**
 * Main SEO Class.
 *
 * The main class that provides access to all SEO functionality.
 * This class is accessed through the SEO facade.
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

namespace ArtisanPackUI\SEO;

use Illuminate\Database\Eloquent\Model;

class SEO
{
	/**
	 * Registered custom analyzers.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, class-string>
	 */
	protected array $analyzers = [];

	/**
	 * Registered custom schema types.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, class-string>
	 */
	protected array $schemaTypes = [];

	/**
	 * Registered sitemap providers.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, class-string>
	 */
	protected array $sitemapProviders = [];

	/**
	 * Create a new SEO instance.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		// Load sitemap providers from config
		$this->sitemapProviders = config( 'seo.sitemap.providers', [] );
	}

	/**
	 * Get meta tags for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|null  $model  The model to get meta tags for.
	 *
	 * @return array<string, mixed>
	 */
	public function getMetaTags( ?Model $model = null ): array
	{
		// Placeholder - will be implemented in core services task
		return [];
	}

	/**
	 * Register a custom analyzer.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $name   The analyzer name.
	 * @param  string  $class  The analyzer class name.
	 *
	 * @return self
	 */
	public function registerAnalyzer( string $name, string $class ): self
	{
		$this->analyzers[ $name ] = $class;
		return $this;
	}

	/**
	 * Get all registered analyzers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, class-string>
	 */
	public function getAnalyzers(): array
	{
		return $this->analyzers;
	}

	/**
	 * Register a custom schema type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $name   The schema type name.
	 * @param  string  $class  The schema type class name.
	 *
	 * @return self
	 */
	public function registerSchemaType( string $name, string $class ): self
	{
		$this->schemaTypes[ $name ] = $class;
		return $this;
	}

	/**
	 * Get all registered schema types.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, class-string>
	 */
	public function getSchemaTypes(): array
	{
		return $this->schemaTypes;
	}

	/**
	 * Register a sitemap provider.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $name   The provider name.
	 * @param  string  $class  The provider class name.
	 *
	 * @return self
	 */
	public function registerSitemapProvider( string $name, string $class ): self
	{
		$this->sitemapProviders[ $name ] = $class;
		return $this;
	}

	/**
	 * Get all registered sitemap providers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, class-string>
	 */
	public function getSitemapProviders(): array
	{
		return $this->sitemapProviders;
	}

	/**
	 * Get the site title with separator.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $title  The page title.
	 *
	 * @return string
	 */
	public function title( string $title ): string
	{
		$siteName  = config( 'seo.site.name', config( 'app.name' ) );
		$separator = config( 'seo.site.separator', ' | ' );

		return $title . $separator . $siteName;
	}

	/**
	 * Check if a feature is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $feature  The feature name.
	 *
	 * @return bool
	 */
	public function isEnabled( string $feature ): bool
	{
		return (bool) config( "seo.{$feature}.enabled", false );
	}
}
