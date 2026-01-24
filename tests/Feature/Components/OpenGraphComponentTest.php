<?php

/**
 * OpenGraph Component Tests.
 *
 * Feature tests for the OpenGraph Blade component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\View\Components\OpenGraph;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	config( [
		'seo.open_graph.site_name'     => 'Test Site',
		'seo.open_graph.type'          => 'website',
		'seo.open_graph.default_image' => 'https://example.com/default-og.jpg',
		'seo.open_graph.locale'        => 'en_US',
		'app.name'                     => 'Laravel',
	] );

	// Run the migration
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

/**
 * Create a test model with seoMeta relationship.
 */
function createOgTestModel( array $attributes = [], ?SeoMeta $seoMeta = null ): Model
{
	return new class( $attributes, $seoMeta ) extends Model {
		public ?SeoMeta $seoMeta;

		protected $guarded = [];

		public function __construct( array $attributes = [], ?SeoMeta $seoMeta = null )
		{
			parent::__construct();
			$this->seoMeta = $seoMeta;
			foreach ( $attributes as $key => $value ) {
				$this->setAttribute( $key, $value );
			}
		}

		public function seoMeta(): ?SeoMeta
		{
			return $this->seoMeta;
		}
	};
}

describe( 'OpenGraph Component Rendering', function (): void {

	it( 'renders og:title meta tag', function (): void {
		$component = new OpenGraph( title: 'OG Test Title' );
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta property="og:title" content="OG Test Title">' );
	} );

	it( 'renders og:description meta tag', function (): void {
		$component = new OpenGraph(
			title: 'Title',
			description: 'OG Description',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta property="og:description" content="OG Description">' );
	} );

	it( 'renders og:image meta tag', function (): void {
		$component = new OpenGraph(
			title: 'Title',
			image: 'https://example.com/image.jpg',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta property="og:image" content="https://example.com/image.jpg">' );
	} );

	it( 'renders og:url meta tag', function (): void {
		$component = new OpenGraph(
			title: 'Title',
			url: 'https://example.com/page',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta property="og:url" content="https://example.com/page">' );
	} );

	it( 'renders og:type meta tag', function (): void {
		$component = new OpenGraph(
			title: 'Title',
			type: 'article',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta property="og:type" content="article">' );
	} );

	it( 'renders og:site_name meta tag', function (): void {
		$component = new OpenGraph(
			title: 'Title',
			siteName: 'My Site',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta property="og:site_name" content="My Site">' );
	} );

	it( 'renders og:locale meta tag', function (): void {
		$component = new OpenGraph(
			title: 'Title',
			locale: 'es_ES',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta property="og:locale" content="es_ES">' );
	} );

	it( 'uses default values from config', function (): void {
		$component = new OpenGraph();
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();

		expect( $html )->toContain( 'og:type' )
			->and( $html )->toContain( 'og:site_name' )
			->and( $html )->toContain( 'og:locale' );
	} );

	it( 'omits description when null', function (): void {
		$component = new OpenGraph(
			title: 'Title',
			description: null,
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->not->toContain( 'og:description' );
	} );

} );

describe( 'OpenGraph Component with Model', function (): void {

	it( 'gets data from model', function (): void {
		$model = createOgTestModel( [
			'title'       => 'Model Title',
			'description' => 'Model description',
			'slug'        => 'model-slug',
		] );

		$component = new OpenGraph( model: $model );

		expect( $component->ogTitle )->toBe( 'Model Title' );
	} );

	it( 'gets data from model with seoMeta', function (): void {
		$seoMeta = new SeoMeta( [
			'og_title'       => 'OG SEO Title',
			'og_description' => 'OG SEO Description',
			'og_image'       => 'https://example.com/seo-og.jpg',
			'og_type'        => 'article',
		] );

		$model = createOgTestModel( [
			'title' => 'Model Title',
			'slug'  => 'model-slug',
		], $seoMeta );

		$component = new OpenGraph( model: $model );

		expect( $component->ogTitle )->toBe( 'OG SEO Title' )
			->and( $component->ogDescription )->toBe( 'OG SEO Description' )
			->and( $component->ogImage )->toBe( 'https://example.com/seo-og.jpg' )
			->and( $component->ogType )->toBe( 'article' );
	} );

	it( 'allows overriding model data', function (): void {
		$model = createOgTestModel( [
			'title' => 'Model Title',
			'slug'  => 'model-slug',
		] );

		$component = new OpenGraph(
			model: $model,
			title: 'Override OG Title',
			description: 'Override OG Description',
		);

		expect( $component->ogTitle )->toBe( 'Override OG Title' )
			->and( $component->ogDescription )->toBe( 'Override OG Description' );
	} );

} );
