<?php

/**
 * MetaTags Component Tests.
 *
 * Feature tests for the MetaTags Blade component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\View\Components\MetaTags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	config( [
		'seo.site.name'        => 'Test Site',
		'seo.site.separator'   => ' | ',
		'seo.site.description' => 'Default site description',
		'seo.defaults.robots'  => 'index, follow',
		'app.name'             => 'Laravel',
	] );

	// Run the migration
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

/**
 * Create a test model with seoMeta relationship.
 */
function createMetaTestModel( array $attributes = [], ?SeoMeta $seoMeta = null ): Model
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

describe( 'MetaTags Component Rendering', function (): void {

	it( 'renders title tag', function (): void {
		$component = new MetaTags( title: 'Test Page Title' );
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<title>Test Page Title</title>' );
	} );

	it( 'renders description meta tag', function (): void {
		$component = new MetaTags(
			title: 'Test Title',
			description: 'This is a test description',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta name="description" content="This is a test description">' );
	} );

	it( 'renders canonical link tag', function (): void {
		$component = new MetaTags(
			title: 'Test Title',
			canonical: 'https://example.com/page',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<link rel="canonical" href="https://example.com/page">' );
	} );

	it( 'renders robots meta tag', function (): void {
		$component = new MetaTags(
			title: 'Test Title',
			robots: 'noindex, nofollow',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta name="robots" content="noindex, nofollow">' );
	} );

	it( 'uses default robots when not specified', function (): void {
		$component = new MetaTags( title: 'Test Title' );
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta name="robots" content="index, follow">' );
	} );

	it( 'omits description when null and no config default', function (): void {
		config( [ 'seo.site.description' => null ] );

		$component = new MetaTags(
			title: 'Test Title',
			description: null,
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->not->toContain( '<meta name="description"' );
	} );

	it( 'uses config description when description is null', function (): void {
		$component = new MetaTags(
			title: 'Test Title',
			description: null,
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta name="description" content="Default site description">' );
	} );

} );

describe( 'MetaTags Component with Model', function (): void {

	it( 'gets data from model', function (): void {
		$model = createMetaTestModel( [
			'title'       => 'Model Title',
			'description' => 'Model description',
			'slug'        => 'model-slug',
		] );

		$component = new MetaTags( model: $model );

		expect( $component->title )->toContain( 'Model Title' );
	} );

	it( 'gets data from model with seoMeta', function (): void {
		$seoMeta = new SeoMeta( [
			'meta_title'       => 'SEO Title',
			'meta_description' => 'SEO Description',
			'canonical_url'    => 'https://example.com/seo-page',
		] );

		$model = createMetaTestModel( [
			'title' => 'Model Title',
			'slug'  => 'model-slug',
		], $seoMeta );

		$component = new MetaTags( model: $model );

		expect( $component->title )->toContain( 'SEO Title' )
			->and( $component->description )->toBe( 'SEO Description' )
			->and( $component->canonical )->toBe( 'https://example.com/seo-page' );
	} );

	it( 'allows overriding model data', function (): void {
		$model = createMetaTestModel( [
			'title' => 'Model Title',
			'slug'  => 'model-slug',
		] );

		$component = new MetaTags(
			model: $model,
			title: 'Override Title',
			description: 'Override Description',
		);

		expect( $component->title )->toBe( 'Override Title' )
			->and( $component->description )->toBe( 'Override Description' );
	} );

} );

describe( 'MetaTags Component Additional Meta', function (): void {

	it( 'renders additional meta tags', function (): void {
		$seoMeta = new SeoMeta( [
			'meta_title'         => 'Test Title',
			'focus_keyword'      => 'keyword',
			'secondary_keywords' => [ 'secondary1', 'secondary2' ],
		] );

		$model = createMetaTestModel( [
			'title' => 'Title',
			'slug'  => 'slug',
		], $seoMeta );

		$component = new MetaTags( model: $model );
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();

		expect( $html )->toContain( 'keywords' );
	} );

} );
