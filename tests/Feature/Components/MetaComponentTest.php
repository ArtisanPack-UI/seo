<?php

/**
 * Meta Component Tests.
 *
 * Feature tests for the all-in-one Meta Blade component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\View\Components\Meta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	config( [
		'seo.site.name'                => 'Test Site',
		'seo.site.separator'           => ' | ',
		'seo.site.description'         => 'Default site description',
		'seo.defaults.robots'          => 'index, follow',
		'seo.open_graph.site_name'     => 'Test Site',
		'seo.open_graph.type'          => 'website',
		'seo.open_graph.default_image' => 'https://example.com/default-og.jpg',
		'seo.open_graph.locale'        => 'en_US',
		'seo.twitter.card_type'        => 'summary_large_image',
		'seo.twitter.site'             => '@TestSite',
		'seo.twitter.creator'          => null,
		'seo.twitter.default_image'    => 'https://example.com/default-twitter.jpg',
		'seo.hreflang.default_locale'  => 'en',
		'app.name'                     => 'Laravel',
	] );

	// Run the migration
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

/**
 * Create a test model with seoMeta relationship.
 */
function createAllInOneTestModel( array $attributes = [], ?SeoMeta $seoMeta = null, ?int $key = null ): Model
{
	return new class( $attributes, $seoMeta, $key ) extends Model {
		public ?SeoMeta $seoMeta;

		protected $guarded = [];

		protected ?int $modelKey;

		public function __construct( array $attributes = [], ?SeoMeta $seoMeta = null, ?int $key = null )
		{
			parent::__construct();
			$this->seoMeta  = $seoMeta;
			$this->modelKey = $key;
			foreach ( $attributes as $attrKey => $value ) {
				$this->setAttribute( $attrKey, $value );
			}
		}

		public function getKey(): mixed
		{
			return $this->modelKey ?? 1;
		}

		public function seoMeta(): ?SeoMeta
		{
			return $this->seoMeta;
		}
	};
}

describe( 'Meta Component Rendering', function (): void {

	it( 'renders all meta tags', function (): void {
		$component = new Meta(
			title: 'Page Title',
			description: 'Page Description',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		// Basic meta
		expect( $html )->toContain( '<title>' )
			->and( $html )->toContain( 'Page Title' )
			->and( $html )->toContain( 'Page Description' )
			->and( $html )->toContain( '<meta name="robots"' );

		// Open Graph
		expect( $html )->toContain( 'og:title' )
			->and( $html )->toContain( 'og:type' );

		// Twitter Card
		expect( $html )->toContain( 'twitter:card' )
			->and( $html )->toContain( 'twitter:title' );
	} );

	it( 'can exclude Open Graph tags', function (): void {
		$component = new Meta(
			title: 'Title',
			includeOpenGraph: false,
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<title>' )
			->and( $html )->not->toContain( 'og:title' );
	} );

	it( 'can exclude Twitter Card tags', function (): void {
		$component = new Meta(
			title: 'Title',
			includeTwitterCard: false,
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<title>' )
			->and( $html )->not->toContain( 'twitter:card' );
	} );

	it( 'can exclude hreflang tags', function (): void {
		$seoMeta = new SeoMeta( [
			'hreflang' => [
				'en' => 'https://example.com/en',
				'es' => 'https://example.com/es',
			],
		] );

		$model = createAllInOneTestModel( [
			'title' => 'Title',
			'slug'  => 'slug',
		], $seoMeta );

		$component = new Meta(
			model: $model,
			includeHreflang: false,
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->not->toContain( 'hreflang' );
	} );

} );

describe( 'Meta Component with Model', function (): void {

	it( 'gets all data from model', function (): void {
		$model = createAllInOneTestModel( [
			'title'       => 'Model Title',
			'description' => 'Model description',
			'slug'        => 'model-slug',
		] );

		$component = new Meta( model: $model );

		expect( $component->meta['title'] )->toContain( 'Model Title' )
			->and( $component->openGraph['title'] )->toBe( 'Model Title' )
			->and( $component->twitterCard['title'] )->toBe( 'Model Title' );
	} );

	it( 'gets data from model with seoMeta', function (): void {
		$seoMeta = new SeoMeta( [
			'meta_title'       => 'SEO Title',
			'meta_description' => 'SEO Description',
			'og_title'         => 'OG Title',
			'og_description'   => 'OG Description',
			'twitter_title'    => 'Twitter Title',
		] );

		$model = createAllInOneTestModel( [
			'title' => 'Model Title',
			'slug'  => 'model-slug',
		], $seoMeta );

		$component = new Meta( model: $model );

		expect( $component->meta['title'] )->toContain( 'SEO Title' )
			->and( $component->meta['description'] )->toBe( 'SEO Description' )
			->and( $component->openGraph['title'] )->toBe( 'OG Title' )
			->and( $component->openGraph['description'] )->toBe( 'OG Description' )
			->and( $component->twitterCard['title'] )->toBe( 'Twitter Title' );
	} );

	it( 'allows overriding model data', function (): void {
		$model = createAllInOneTestModel( [
			'title' => 'Model Title',
			'slug'  => 'model-slug',
		] );

		$component = new Meta(
			model: $model,
			title: 'Override Title',
			description: 'Override Description',
			image: 'https://example.com/override.jpg',
		);

		expect( $component->meta['title'] )->toContain( 'Override Title' )
			->and( $component->meta['description'] )->toBe( 'Override Description' )
			->and( $component->openGraph['title'] )->toBe( 'Override Title' )
			->and( $component->openGraph['image'] )->toBe( 'https://example.com/override.jpg' )
			->and( $component->twitterCard['title'] )->toBe( 'Override Title' )
			->and( $component->twitterCard['image'] )->toBe( 'https://example.com/override.jpg' );
	} );

} );

describe( 'Meta Component Hreflang', function (): void {

	it( 'renders hreflang tags from model', function (): void {
		$seoMeta = new SeoMeta( [
			'hreflang' => [
				'en' => 'https://example.com/en',
				'es' => 'https://example.com/es',
			],
		] );

		$model = createAllInOneTestModel( [
			'title' => 'Title',
			'slug'  => 'slug',
		], $seoMeta );

		$component = new Meta( model: $model );
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();

		expect( $html )->toContain( 'rel="alternate" hreflang="en"' )
			->and( $html )->toContain( 'rel="alternate" hreflang="es"' )
			->and( $html )->toContain( 'href="https://example.com/en"' )
			->and( $html )->toContain( 'href="https://example.com/es"' );
	} );

	it( 'includes x-default hreflang', function (): void {
		config( [ 'seo.hreflang.default_locale' => 'en' ] );

		$seoMeta = new SeoMeta( [
			'hreflang' => [
				'en' => 'https://example.com/en',
				'es' => 'https://example.com/es',
			],
		] );

		$model = createAllInOneTestModel( [
			'title' => 'Title',
			'slug'  => 'slug',
		], $seoMeta );

		$component = new Meta( model: $model );
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();

		expect( $html )->toContain( 'hreflang="x-default"' );
	} );

} );

describe( 'Meta Component Default Values', function (): void {

	it( 'uses config defaults when no model or overrides', function (): void {
		$component = new Meta();

		expect( $component->meta['robots'] )->toBe( 'index, follow' )
			->and( $component->openGraph['type'] )->toBe( 'website' )
			->and( $component->twitterCard['card'] )->toBe( 'summary_large_image' );
	} );

	it( 'uses default OG image from config', function (): void {
		config( [ 'seo.open_graph.default_image' => 'https://example.com/default.jpg' ] );

		$component = new Meta();

		expect( $component->openGraph['image'] )->toBe( 'https://example.com/default.jpg' );
	} );

} );
