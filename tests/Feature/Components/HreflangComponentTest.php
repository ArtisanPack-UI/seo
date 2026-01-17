<?php

/**
 * Hreflang Component Tests.
 *
 * Feature tests for the Hreflang Blade component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\View\Components\Hreflang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	config( [
		'seo.hreflang.enabled'            => true,
		'seo.hreflang.default_locale'     => 'en',
		'seo.hreflang.auto_add_x_default' => true,
		'seo.hreflang.supported_locales'  => [ 'en', 'es', 'fr', 'de' ],
	] );

	// Run the migration
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

/**
 * Create a test model with seoMeta relationship.
 */
function createHreflangTestModel( array $attributes = [], ?SeoMeta $seoMeta = null ): Model
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

describe( 'Hreflang Component Rendering', function (): void {

	it( 'renders hreflang tags from manual urls', function (): void {
		$component = new Hreflang(
			urls: [
				'en' => 'https://example.com/en/page',
				'es' => 'https://example.com/es/page',
			],
		);

		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )
			->toContain( '<link rel="alternate" hreflang="en" href="https://example.com/en/page"' )
			->toContain( '<link rel="alternate" hreflang="es" href="https://example.com/es/page"' );
	} );

	it( 'adds x-default when default locale is present', function (): void {
		$component = new Hreflang(
			urls: [
				'en' => 'https://example.com/en/page',
				'es' => 'https://example.com/es/page',
			],
		);

		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<link rel="alternate" hreflang="x-default" href="https://example.com/en/page"' );
	} );

	it( 'uses explicit default URL for x-default', function (): void {
		$component = new Hreflang(
			urls: [
				'en' => 'https://example.com/en/page',
				'es' => 'https://example.com/es/page',
			],
			defaultUrl: 'https://example.com/page',
		);

		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<link rel="alternate" hreflang="x-default" href="https://example.com/page"' );
	} );

	it( 'does not add x-default when includeXDefault is false', function (): void {
		$component = new Hreflang(
			urls: [
				'en' => 'https://example.com/en/page',
				'es' => 'https://example.com/es/page',
			],
			includeXDefault: false,
		);

		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->not->toContain( 'x-default' );
	} );

	it( 'does not duplicate x-default if already in urls', function (): void {
		$component = new Hreflang(
			urls: [
				'en'        => 'https://example.com/en/page',
				'x-default' => 'https://example.com/default-page',
			],
		);

		$html  = $component->render()->with( $component->data() )->render();
		$count = substr_count( $html, 'x-default' );

		expect( $count )->toBe( 1 );
	} );

	it( 'renders empty when no urls provided', function (): void {
		$component = new Hreflang();

		expect( $component->shouldRender() )->toBeFalse();
	} );

	it( 'filters out invalid locales', function (): void {
		$component = new Hreflang(
			urls: [
				'en'      => 'https://example.com/en/page',
				'invalid' => 'https://example.com/invalid/page',
			],
		);

		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )
			->toContain( 'hreflang="en"' )
			->not->toContain( 'hreflang="invalid"' );
	} );

} );

describe( 'Hreflang Component with Model', function (): void {

	it( 'gets hreflang data from model seoMeta', function (): void {
		$seoMeta = new SeoMeta( [
			'hreflang' => [
				'en' => 'https://example.com/en/article',
				'fr' => 'https://example.com/fr/article',
			],
		] );

		$model = createHreflangTestModel( [
			'id'    => 1,
			'title' => 'Test Article',
		], $seoMeta );

		$component = new Hreflang( model: $model );

		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )
			->toContain( '<link rel="alternate" hreflang="en" href="https://example.com/en/article"' )
			->toContain( '<link rel="alternate" hreflang="fr" href="https://example.com/fr/article"' );
	} );

	it( 'renders empty when model has no hreflang data', function (): void {
		$seoMeta = new SeoMeta();

		$model = createHreflangTestModel( [
			'id'    => 1,
			'title' => 'Test Article',
		], $seoMeta );

		$component = new Hreflang( model: $model );

		expect( $component->shouldRender() )->toBeFalse();
	} );

	it( 'renders empty when model has no seoMeta', function (): void {
		$model = createHreflangTestModel( [
			'id'    => 1,
			'title' => 'Test Article',
		] );

		$component = new Hreflang( model: $model );

		expect( $component->shouldRender() )->toBeFalse();
	} );

} );

describe( 'Hreflang Component Disabled State', function (): void {

	it( 'renders empty when hreflang is disabled', function (): void {
		config( [ 'seo.hreflang.enabled' => false ] );

		$component = new Hreflang(
			urls: [
				'en' => 'https://example.com/en/page',
				'es' => 'https://example.com/es/page',
			],
		);

		expect( $component->shouldRender() )->toBeFalse()
			->and( $component->hreflangTags )->toBeEmpty();
	} );

} );

describe( 'Hreflang Component Configuration', function (): void {

	it( 'respects auto_add_x_default config', function (): void {
		config( [ 'seo.hreflang.auto_add_x_default' => false ] );

		$component = new Hreflang(
			urls: [
				'en' => 'https://example.com/en/page',
				'es' => 'https://example.com/es/page',
			],
		);

		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->not->toContain( 'x-default' );
	} );

	it( 'uses configured default locale for x-default', function (): void {
		config( [ 'seo.hreflang.default_locale' => 'fr' ] );

		$component = new Hreflang(
			urls: [
				'en' => 'https://example.com/en/page',
				'fr' => 'https://example.com/fr/page',
			],
		);

		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<link rel="alternate" hreflang="x-default" href="https://example.com/fr/page"' );
	} );

} );

describe( 'Hreflang Component Tag Format', function (): void {

	it( 'renders proper link tag format', function (): void {
		$component = new Hreflang(
			urls: [
				'en-US' => 'https://example.com/en-us/page',
			],
		);

		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<link rel="alternate" hreflang="en-US" href="https://example.com/en-us/page" />' );
	} );

	it( 'renders all hreflang tags on separate lines', function (): void {
		$component = new Hreflang(
			urls: [
				'en' => 'https://example.com/en/page',
				'es' => 'https://example.com/es/page',
				'fr' => 'https://example.com/fr/page',
			],
			includeXDefault: false,
		);

		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		$lineCount = substr_count( $html, '<link rel="alternate"' );
		expect( $lineCount )->toBe( 3 );
	} );

} );
