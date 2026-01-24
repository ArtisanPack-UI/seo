<?php

declare( strict_types=1 );

namespace Tests;

use ArtisanPackUI\SEO\Providers\SEOServiceProvider;
use Illuminate\Support\Facades\Blade;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Base Test Case
 *
 * Provides base functionality for all package tests.
 *
 * @since   1.0.0
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Register stub components for testing
        $this->registerStubComponents();
    }

    /**
     * Register stub Blade components for testing.
     *
     * These are minimal replacements for the artisanpack-ui components
     * that allow Livewire tests to run without the full UI package.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function registerStubComponents(): void
    {
        $stubPath = __DIR__ . '/stubs/components';

        if ( ! is_dir( $stubPath ) ) {
            return;
        }

        $components = [
            'artisanpack-alert',
            'artisanpack-tabs',
            'artisanpack-tab',
            'artisanpack-card',
            'artisanpack-input',
            'artisanpack-textarea',
            'artisanpack-button',
            'artisanpack-icon',
            'artisanpack-select',
            'artisanpack-collapse',
            'artisanpack-toggle',
            'artisanpack-range',
        ];

        foreach ( $components as $component ) {
            $viewPath = $stubPath . '/' . $component . '.blade.php';
            if ( file_exists( $viewPath ) ) {
                Blade::component( $component, \Illuminate\View\AnonymousComponent::class );
                $this->app['view']->addNamespace( '__components', $stubPath );
            }
        }

        // Register the stub components directory as a views location
        $this->app['view']->addLocation( $stubPath );

        // Register anonymous components from stubs
        Blade::anonymousComponentPath( $stubPath );
    }

    /**
     * Gets package providers.
     *
     * @since 1.0.0
     *
     * @param  \Illuminate\Foundation\Application  $app  The application instance.
     *
     * @return array<int, class-string> Array of service provider class names.
     */
    protected function getPackageProviders( $app ): array
    {
        return [
            LivewireServiceProvider::class,
            SEOServiceProvider::class,
        ];
    }

    /**
     * Defines environment setup.
     *
     * @since 1.0.0
     *
     * @param  \Illuminate\Foundation\Application  $app  The application instance.
     */
    protected function defineEnvironment( $app ): void
    {
        // Setup app key for encryption
        $app['config']->set( 'app.key', 'base64:' . base64_encode( random_bytes( 32 ) ) );

        // Setup default database to use sqlite :memory:
        $app['config']->set( 'database.default', 'testbench' );
        $app['config']->set( 'database.connections.testbench', [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ] );
    }
}
