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

use ArtisanPackUI\SEO\SEO;
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
		// Blade components will be registered here once they are created
		// Example:
		// Blade::component( 'seo-meta-tags', \ArtisanPackUI\SEO\View\Components\MetaTags::class );
	}
}
