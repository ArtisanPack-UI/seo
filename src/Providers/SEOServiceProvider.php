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

use ArtisanPackUI\SEO\Ai\Agents\ContentAnalysisAgent;
use ArtisanPackUI\SEO\Ai\Agents\HreflangSuggestionAgent;
use ArtisanPackUI\SEO\Ai\Agents\MetaDescriptionAgent;
use ArtisanPackUI\SEO\Ai\Agents\MetaTitleSuggestionAgent;
use ArtisanPackUI\SEO\Ai\Agents\SchemaGenerationAgent;
use ArtisanPackUI\SEO\Console\Commands\GenerateSitemapCommand;
use ArtisanPackUI\SEO\Console\Commands\InstallFrontend;
use ArtisanPackUI\SEO\Console\Commands\SubmitSitemapCommand;
use ArtisanPackUI\SEO\Contracts\IndexNowKeyProviderContract;
use ArtisanPackUI\SEO\Http\Middleware\HandleRedirects;
use ArtisanPackUI\SEO\IndexNow\ConfigIndexNowKeyProvider;
use ArtisanPackUI\SEO\Livewire\Ai\ContentAnalyzer;
use ArtisanPackUI\SEO\Livewire\Ai\HreflangSuggestor;
use ArtisanPackUI\SEO\Livewire\Ai\MetaDescriptionSuggestor;
use ArtisanPackUI\SEO\Livewire\Ai\MetaTitleSuggestor;
use ArtisanPackUI\SEO\Livewire\Ai\SchemaSuggestor;
use ArtisanPackUI\SEO\Livewire\HreflangEditor;
use ArtisanPackUI\SEO\Livewire\Partials\MetaPreview;
use ArtisanPackUI\SEO\Livewire\Partials\SocialPreview;
use ArtisanPackUI\SEO\Livewire\RedirectManager;
use ArtisanPackUI\SEO\Livewire\SeoAnalysisPanel;
use ArtisanPackUI\SEO\Livewire\SeoDashboard;
use ArtisanPackUI\SEO\Livewire\SeoMetaEditor;
use ArtisanPackUI\SEO\Schema\SchemaFactory;
use ArtisanPackUI\SEO\SEO;
use ArtisanPackUI\SEO\Services\AiCrawlerService;
use ArtisanPackUI\SEO\Services\Analysis\AiReadinessAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\ContentLengthAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\FocusKeywordAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\HeadingStructureAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\ImageAltAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\InternalLinkAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\KeywordDensityAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\MetaLengthAnalyzer;
use ArtisanPackUI\SEO\Services\Analysis\ReadabilityAnalyzer;
use ArtisanPackUI\SEO\Services\AnalysisService;
use ArtisanPackUI\SEO\Services\AnalyticsIntegration;
use ArtisanPackUI\SEO\Services\CacheService;
use ArtisanPackUI\SEO\Services\CmsFrameworkIntegration;
use ArtisanPackUI\SEO\Services\HreflangService;
use ArtisanPackUI\SEO\Services\MediaLibraryIntegration;
use ArtisanPackUI\SEO\Services\MetaTagService;
use ArtisanPackUI\SEO\Services\RedirectService;
use ArtisanPackUI\SEO\Services\RobotsService;
use ArtisanPackUI\SEO\Services\SchemaService;
use ArtisanPackUI\SEO\Services\SeoService;
use ArtisanPackUI\SEO\Services\SitemapService;
use ArtisanPackUI\SEO\Services\SocialMetaService;
use ArtisanPackUI\SEO\Services\VisualEditorIntegration;
use ArtisanPackUI\SEO\Support\PackageDetector;
use ArtisanPackUI\SEO\Support\SchemaCollector;
use ArtisanPackUI\SEO\View\Components\Hreflang;
use ArtisanPackUI\SEO\View\Components\Meta;
use ArtisanPackUI\SEO\View\Components\MetaTags;
use ArtisanPackUI\SEO\View\Components\OpenGraph;
use ArtisanPackUI\SEO\View\Components\Schema;
use ArtisanPackUI\SEO\View\Components\TwitterCard;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
		$this->registerLivewireComponents();
		$this->registerCommands();
		$this->registerMiddleware();
		$this->registerMediaLibraryImageSize();
		$this->registerVisualEditorPrePublishChecks();
		$this->registerAiGate();
	}

	/**
	 * Declare AI features owned by this package.
	 *
	 * Auto-discovered by artisanpack-ui/ai when the ai package is installed.
	 * Each entry maps a fully-qualified feature key to the agent class that
	 * fulfills it, along with a human-readable label and description for the
	 * admin UI.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function aiFeatures(): array
	{
		return [
			'seo.suggest_meta_title'       => [
				'agent'       => MetaTitleSuggestionAgent::class,
				'package'     => 'artisanpack-ui/seo',
				'label'       => __( 'Suggest meta title' ),
				'description' => __( 'Generate 3-5 SEO title variants (≤60 chars) optimized for CTR and keyword coverage.' ),
			],
			'seo.suggest_meta_description' => [
				'agent'       => MetaDescriptionAgent::class,
				'package'     => 'artisanpack-ui/seo',
				'label'       => __( 'Suggest meta description' ),
				'description' => __( 'Generate one 150-160 character meta description that naturally includes the primary keyword.' ),
			],
			'seo.analyze_content'          => [
				'agent'       => ContentAnalysisAgent::class,
				'package'     => 'artisanpack-ui/seo',
				'label'       => __( 'Analyze content' ),
				'description' => __( 'Score content quality across keyword usage, readability, structure, and semantic completeness.' ),
			],
			'seo.generate_schema'          => [
				'agent'       => SchemaGenerationAgent::class,
				'package'     => 'artisanpack-ui/seo',
				'label'       => __( 'Suggest schema' ),
				'description' => __( 'Pick a JSON-LD schema.org type from the supported list and produce a starter structure.' ),
			],
			'seo.suggest_hreflang'         => [
				'agent'       => HreflangSuggestionAgent::class,
				'package'     => 'artisanpack-ui/seo',
				'label'       => __( 'Suggest hreflang fixes' ),
				'description' => __( 'Cross-reference hreflang relationships and surface missing or inconsistent tags.' ),
			],
		];
	}

	/**
	 * Whether the optional `artisanpack-ui/ai` package is installed.
	 *
	 * The AI Feature Suite (agents, Livewire trigger components, and
	 * `/api/seo/ai/*` endpoints) all extend or resolve from types owned by
	 * `artisanpack-ui/ai`. When that package is absent, this returns false
	 * and callers skip the AI-specific registration.
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public static function aiPackageAvailable(): bool
	{
		return class_exists( \ArtisanPackUI\Ai\Agents\ArtisanPackAgent::class );
	}

	/**
	 * Register the default `seo.ai.use` authorization gate.
	 *
	 * The AI API endpoints (`/api/seo/ai/*`) gate on this ability so that
	 * paid quota isn't spent by every authenticated user. Ships with a
	 * permissive default (any authenticated user can use AI features) so
	 * upgrades are non-breaking; installers should override this gate in
	 * their own `AuthServiceProvider` to enforce a stricter policy —
	 * e.g. limit to editors/admins or apply per-tenant quotas.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	protected function registerAiGate(): void
	{
		$gate = \Illuminate\Support\Facades\Gate::getFacadeRoot();

		// Guard so an installer who registered their own `seo.ai.use`
		// before our boot() (via a plugin, package-first AuthServiceProvider,
		// etc.) is not silently overwritten by the permissive default.
		if ( method_exists( $gate, 'has' ) && $gate->has( 'seo.ai.use' ) ) {
			return;
		}

		\Illuminate\Support\Facades\Gate::define(
			'seo.ai.use',
			static function ( $user = null ): bool {
				return null !== $user;
			},
		);
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

		$this->app->singleton( SchemaCollector::class, function ( $app ) {
			return new SchemaCollector();
		} );

		$this->app->singleton( SchemaService::class, function ( $app ) {
			return new SchemaService(
				$app->make( SchemaFactory::class ),
			);
		} );

		$this->app->singleton( SitemapService::class, function ( $app ) {
			return new SitemapService();
		} );

		$this->app->bind( IndexNowKeyProviderContract::class, ConfigIndexNowKeyProvider::class );

		$this->app->singleton( RobotsService::class, function ( $app ) {
			return new RobotsService();
		} );

		$this->app->singleton( AiCrawlerService::class, function ( $app ) {
			return new AiCrawlerService();
		} );

		$this->app->singleton( RedirectService::class, function ( $app ) {
			return new RedirectService();
		} );

		$this->app->singleton( HreflangService::class, function ( $app ) {
			return new HreflangService(
				$app->make( CacheService::class ),
			);
		} );

		$this->app->singleton( MediaLibraryIntegration::class, function ( $app ) {
			return new MediaLibraryIntegration();
		} );

		$this->app->singleton( CmsFrameworkIntegration::class, function ( $app ) {
			return new CmsFrameworkIntegration();
		} );

		$this->app->singleton( AnalysisService::class, function ( $app ) {
			$service = new AnalysisService();

			$service->registerAnalyzer( 'ai_readiness', new AiReadinessAnalyzer() );
			$service->registerAnalyzer( 'content_length', new ContentLengthAnalyzer() );
			$service->registerAnalyzer( 'focus_keyword', new FocusKeywordAnalyzer() );
			$service->registerAnalyzer( 'heading_structure', new HeadingStructureAnalyzer() );
			$service->registerAnalyzer( 'image_alt', new ImageAltAnalyzer() );
			$service->registerAnalyzer( 'internal_links', new InternalLinkAnalyzer() );
			$service->registerAnalyzer( 'keyword_density', new KeywordDensityAnalyzer() );
			$service->registerAnalyzer( 'meta_length', new MetaLengthAnalyzer() );
			$service->registerAnalyzer( 'readability', new ReadabilityAnalyzer() );

			return $service;
		} );

		$this->app->singleton( AnalyticsIntegration::class, function ( $app ) {
			return new AnalyticsIntegration();
		} );

		$this->app->singleton( VisualEditorIntegration::class, function ( $app ) {
			return new VisualEditorIntegration(
				$app->make( AnalysisService::class ),
			);
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

			// Publish TypeScript type definitions
			$this->publishes(
				[
					__DIR__ . '/../../resources/js/types' => resource_path( 'js/types/seo' ),
				],
				'seo-types',
			);

			// Publish React components
			$this->publishes(
				[
					__DIR__ . '/../../resources/js/react' => resource_path( 'js/vendor/seo/react' ),
				],
				'seo-react',
			);

			// Publish Vue components
			$this->publishes(
				[
					__DIR__ . '/../../resources/js/vue' => resource_path( 'js/vendor/seo/vue' ),
				],
				'seo-vue',
			);

			// Publish all
			$this->publishes(
				[
					__DIR__ . '/../../config/seo.php'       => config_path( 'seo.php' ),
					__DIR__ . '/../../resources/views'      => resource_path( 'views/vendor/seo' ),
					__DIR__ . '/../../database/migrations'  => database_path( 'migrations' ),
					__DIR__ . '/../../resources/js/types'   => resource_path( 'js/types/seo' ),
					__DIR__ . '/../../resources/js/react'   => resource_path( 'js/vendor/seo/react' ),
					__DIR__ . '/../../resources/js/vue'     => resource_path( 'js/vendor/seo/vue' ),
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
		Blade::component( 'seo:hreflang', Hreflang::class );
	}

	/**
	 * Register Livewire components.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function registerLivewireComponents(): void
	{
		// Only register if Livewire is available
		if ( ! class_exists( Livewire::class ) ) {
			return;
		}

		Livewire::component( 'seo::seo-meta-editor', SeoMetaEditor::class );
		Livewire::component( 'seo::seo-analysis-panel', SeoAnalysisPanel::class );
		Livewire::component( 'seo::seo-dashboard', SeoDashboard::class );
		Livewire::component( 'seo::redirect-manager', RedirectManager::class );
		Livewire::component( 'seo::hreflang-editor', HreflangEditor::class );
		Livewire::component( 'seo::meta-preview', MetaPreview::class );
		Livewire::component( 'seo::social-preview', SocialPreview::class );

		// AI trigger components are only registered when artisanpack-ui/ai is installed,
		// since each component's mount path constructs an agent that extends the ai
		// package's ArtisanPackAgent base class.
		if ( ! self::aiPackageAvailable() ) {
			return;
		}

		Livewire::component( 'seo::ai-meta-title-suggestor', MetaTitleSuggestor::class );
		Livewire::component( 'seo::ai-meta-description-suggestor', MetaDescriptionSuggestor::class );
		Livewire::component( 'seo::ai-content-analyzer', ContentAnalyzer::class );
		Livewire::component( 'seo::ai-schema-suggestor', SchemaSuggestor::class );
		Livewire::component( 'seo::ai-hreflang-suggestor', HreflangSuggestor::class );
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
				InstallFrontend::class,
				SubmitSitemapCommand::class,
			] );
		}
	}

	/**
	 * Register the redirect handling middleware.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function registerMiddleware(): void
	{
		// Only register middleware if both redirects and middleware are enabled
		if ( ! config( 'seo.redirects.enabled', true ) ) {
			return;
		}

		if ( ! config( 'seo.redirects.middleware_enabled', true ) ) {
			return;
		}

		// Append middleware to the web group
		$kernel = $this->app->make( Kernel::class );

		if ( method_exists( $kernel, 'appendMiddlewareToGroup' ) ) {
			$kernel->appendMiddlewareToGroup( 'web', HandleRedirects::class );
		}
	}

	/**
	 * Register the social image size with the media library.
	 *
	 * This allows the media library to generate optimized images
	 * for social sharing (1200x630).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function registerMediaLibraryImageSize(): void
	{
		$integration = $this->app->make( MediaLibraryIntegration::class );
		$integration->registerSocialImageSize();
	}

	/**
	 * Register SEO pre-publish checks with the visual editor.
	 *
	 * This integrates with the optional visual editor package to provide
	 * SEO checks before content is published.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function registerVisualEditorPrePublishChecks(): void
	{
		if ( ! PackageDetector::hasVisualEditor() ) {
			return;
		}

		$integration = $this->app->make( VisualEditorIntegration::class );
		$integration->registerPrePublishChecks();
	}
}
