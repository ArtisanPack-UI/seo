<?php

/**
 * SEO service provider.
 *
 * Bootstraps the SEO package by registering services and bindings.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the SEO package.
 *
 * Bootstraps the SEO package by registering services and bindings.
 * Handles configuration merging, publishing, and other service registrations.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SEOServiceProvider extends ServiceProvider
{
    /**
     * Registers any application services.
     *
     * Binds the SEO class as a singleton in the container and
     * merges the package configuration into a temporary key.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register(): void
    {
        // Merge package config into temporary key for later processing
        $this->mergeConfigFrom(
            __DIR__ . '/../config/seo.php',
            'artisanpack-seo-temp'
        );

        // Register the SEO singleton
        $this->app->singleton( 'seo', function ( $app ) {
            return new SEO();
        } );
    }

    /**
     * Bootstraps any application services.
     *
     * Merges configuration, publishes config files, and handles
     * other package bootstrapping such as migrations, views, and routes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot(): void
    {
        $this->mergeConfiguration();
        $this->publishConfiguration();

        // Add additional package bootstrapping here:
        // - Migration loading: $this->loadMigrationsFrom(...)
        // - View loading: $this->loadViewsFrom(...)
        // - Route loading: $this->loadRoutesFrom(...)
    }

    /**
     * Merges the package configuration with user overrides.
     *
     * This method combines the package defaults with any user-defined
     * configuration in config/artisanpack.php under the 'seo' key.
     * User settings take precedence over package defaults.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function mergeConfiguration(): void
    {
        $packageDefaults = config( 'artisanpack-seo-temp', [] );
        $userConfig      = config( 'artisanpack.seo', [] );
        $mergedConfig    = array_replace_recursive( $packageDefaults, $userConfig );

        config( [ 'artisanpack.seo' => $mergedConfig ] );
    }

    /**
     * Publishes the package configuration file.
     *
     * Makes the config file available for publishing to the
     * config/artisanpack directory. Uses the 'artisanpack-package-config'
     * tag for integration with the scaffold command.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function publishConfiguration(): void
    {
        if ( $this->app->runningInConsole() ) {
            $this->publishes( [
                __DIR__ . '/../config/seo.php' => config_path( 'artisanpack/seo.php' ),
            ], 'artisanpack-package-config' );
        }
    }
}
