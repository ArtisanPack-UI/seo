<?php

/**
 * SEO Service Provider.
 *
 * Registers all SEO package services, configuration, views, routes, and migrations.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Providers;

use ArtisanPackUI\SEO\Console\Commands\GenerateSitemapCommand;
use ArtisanPackUI\SEO\Console\Commands\SubmitSitemapCommand;
use ArtisanPackUI\SEO\Schema\SchemaFactory;
use ArtisanPackUI\SEO\SEO;
use ArtisanPackUI\SEO\Services\CacheService;
use ArtisanPackUI\SEO\Services\MetaTagService;
use ArtisanPackUI\SEO\Services\RobotsService;
use ArtisanPackUI\SEO\Services\SchemaService;
use ArtisanPackUI\SEO\Services\SeoService;
use ArtisanPackUI\SEO\Services\SitemapService;
use ArtisanPackUI\SEO\Services\SocialMetaService;
use ArtisanPackUI\SEO\View\Components\Meta;
use ArtisanPackUI\SEO\View\Components\MetaTags;
use ArtisanPackUI\SEO\View\Components\OpenGraph;
use ArtisanPackUI\SEO\View\Components\Schema;
use ArtisanPackUI\SEO\View\Components\TwitterCard;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class SEOServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register(): void
	{
		$this->mergeConfigFrom(
			__DIR__ . '/../../config/seo.php',
			'seo',
		);

		$this->registerServices();

		$this->app->singleton( 'seo', function ( $app ) {
			return new SEO();
		} );
	}

	/**
	 * Bootstrap any application services.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function boot(): void
	{
		$this->registerPublishables();
		$this->registerViews();
		$this->registerRoutes();
		$this->registerMigrations();
		$this->registerBladeComponents();
		$this->registerCommands();
	}

	/**
	 * Register SEO services.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function registerServices(): void
	{
		$this->app->singleton( CacheService::class, function ( $app ) {
			return new CacheService();
		} );

		$this->app->singleton( MetaTagService::class, function ( $app ) {
			return new MetaTagService();
		} );

		$this->app->singleton( SocialMetaService::class, function ( $app ) {
			return new SocialMetaService();
		} );

		$this->app->singleton( SeoService::class, function ( $app ) {
			return new SeoService(
				$app->make( MetaTagService::class ),
				$app->make( SocialMetaService::class ),
				$app->make( CacheService::class ),
			);
		} );

		$this->app->singleton( SchemaFactory::class, function ( $app ) {
			return new SchemaFactory();
		} );

		$this->app->singleton( SchemaService::class, function ( $app ) {
			return new SchemaService(
				$app->make( SchemaFactory::class ),
			);
		} );

		$this->app->singleton( SitemapService::class, function ( $app ) {
			return new SitemapService();
		} );

		$this->app->singleton( RobotsService::class, function ( $app ) {
			return new RobotsService();
		} );
	}

	/**
	 * Register publishable resources.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function registerPublishables(): void
	{
		if ( $this->app->runningInConsole() ) {
			// Publish configuration
			$this->publishes(
				[
					__DIR__ . '/../../config/seo.php' => config_path( 'seo.php' ),
				],
				'seo-config',
			);

			// Publish views
			$this->publishes(
				[
					__DIR__ . '/../../resources/views' => resource_path( 'views/vendor/seo' ),
				],
				'seo-views',
			);

			// Publish migrations
			$this->publishes(
				[
					__DIR__ . '/../../database/migrations' => database_path( 'migrations' ),
				],
				'seo-migrations',
			);

			// Publish all
			$this->publishes(
				[
					__DIR__ . '/../../config/seo.php'       => config_path( 'seo.php' ),
					__DIR__ . '/../../resources/views'      => resource_path( 'views/vendor/seo' ),
					__DIR__ . '/../../database/migrations'  => database_path( 'migrations' ),
				],
				'seo',
			);
		}
	}

	/**
	 * Register package views.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function registerViews(): void
	{
		$this->loadViewsFrom(
			__DIR__ . '/../../resources/views',
			'seo',
		);
	}

	/**
	 * Register package routes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function registerRoutes(): void
	{
		// Register sitemap and robots.txt routes if enabled
		if ( true === config( 'seo.sitemap.route_enabled', true ) || true === config( 'seo.robots.route_enabled', true ) ) {
			$this->loadRoutesFrom( __DIR__ . '/../../routes/web.php' );
		}

		// Register API routes if enabled
		if ( true === config( 'seo.api.enabled', true ) ) {
			$this->loadRoutesFrom( __DIR__ . '/../../routes/api.php' );
		}
	}

	/**
	 * Register package migrations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function registerMigrations(): void
	{
		if ( $this->app->runningInConsole() ) {
			$this->loadMigrationsFrom( __DIR__ . '/../../database/migrations' );
		}
	}

	/**
	 * Register Blade components.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function registerBladeComponents(): void
	{
		Blade::component( 'seo:meta', Meta::class );
		Blade::component( 'seo:meta-tags', MetaTags::class );
		Blade::component( 'seo:open-graph', OpenGraph::class );
		Blade::component( 'seo:twitter-card', TwitterCard::class );
		Blade::component( 'seo:schema', Schema::class );
	}

	/**
	 * Register Artisan commands.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function registerCommands(): void
	{
		if ( $this->app->runningInConsole() ) {
			$this->commands( [
				GenerateSitemapCommand::class,
				SubmitSitemapCommand::class,
			] );
		}
	}
}
