<?php

/**
 * TwitterCard Component Tests.
 *
 * Feature tests for the TwitterCard Blade component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\View\Components\TwitterCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	config( [
		'seo.twitter.card_type'     => 'summary_large_image',
		'seo.twitter.site'          => '@TestSite',
		'seo.twitter.creator'       => null,
		'seo.twitter.default_image' => 'https://example.com/default-twitter.jpg',
		'app.name'                  => 'Laravel',
	] );

	// Run the migration
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

/**
 * Create a test model with seoMeta relationship.
 */
function createTwitterTestModel( array $attributes = [], ?SeoMeta $seoMeta = null ): Model
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

describe( 'TwitterCard Component Rendering', function (): void {

	it( 'renders twitter:card meta tag', function (): void {
		$component = new TwitterCard( card: 'summary' );
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta name="twitter:card" content="summary">' );
	} );

	it( 'renders twitter:title meta tag', function (): void {
		$component = new TwitterCard( title: 'Twitter Test Title' );
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta name="twitter:title" content="Twitter Test Title">' );
	} );

	it( 'renders twitter:description meta tag', function (): void {
		$component = new TwitterCard(
			title: 'Title',
			description: 'Twitter Description',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta name="twitter:description" content="Twitter Description">' );
	} );

	it( 'renders twitter:image meta tag', function (): void {
		$component = new TwitterCard(
			title: 'Title',
			image: 'https://example.com/twitter-image.jpg',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta name="twitter:image" content="https://example.com/twitter-image.jpg">' );
	} );

	it( 'renders twitter:site meta tag', function (): void {
		$component = new TwitterCard(
			title: 'Title',
			site: '@MySite',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta name="twitter:site" content="@MySite">' );
	} );

	it( 'renders twitter:creator meta tag', function (): void {
		$component = new TwitterCard(
			title: 'Title',
			creator: '@AuthorHandle',
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->toContain( '<meta name="twitter:creator" content="@AuthorHandle">' );
	} );

	it( 'uses default card type from config', function (): void {
		$component = new TwitterCard();
		$view      = $component->render();
		$html      = $view->with( $component->data() )->render();

		expect( $html )->toContain( 'twitter:card' )
			->and( $html )->toContain( 'summary_large_image' );
	} );

	it( 'omits description when null', function (): void {
		$component = new TwitterCard(
			title: 'Title',
			description: null,
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->not->toContain( 'twitter:description' );
	} );

	it( 'omits creator when null', function (): void {
		$component = new TwitterCard(
			title: 'Title',
			creator: null,
		);
		$view = $component->render();
		$html = $view->with( $component->data() )->render();

		expect( $html )->not->toContain( 'twitter:creator' );
	} );

} );

describe( 'TwitterCard Component with Model', function (): void {

	it( 'gets data from model', function (): void {
		$model = createTwitterTestModel( [
			'title'       => 'Model Title',
			'description' => 'Model description',
			'slug'        => 'model-slug',
		] );

		$component = new TwitterCard( model: $model );

		expect( $component->twitterTitle )->toBe( 'Model Title' );
	} );

	it( 'gets data from model with seoMeta', function (): void {
		$seoMeta = new SeoMeta( [
			'twitter_card'        => 'summary',
			'twitter_title'       => 'Twitter SEO Title',
			'twitter_description' => 'Twitter SEO Description',
			'twitter_image'       => 'https://example.com/seo-twitter.jpg',
			'twitter_site'        => '@SeoSite',
			'twitter_creator'     => '@SeoCreator',
		] );

		$model = createTwitterTestModel( [
			'title' => 'Model Title',
			'slug'  => 'model-slug',
		], $seoMeta );

		$component = new TwitterCard( model: $model );

		expect( $component->card )->toBe( 'summary' )
			->and( $component->twitterTitle )->toBe( 'Twitter SEO Title' )
			->and( $component->twitterDescription )->toBe( 'Twitter SEO Description' )
			->and( $component->twitterImage )->toBe( 'https://example.com/seo-twitter.jpg' )
			->and( $component->site )->toBe( '@SeoSite' )
			->and( $component->creator )->toBe( '@SeoCreator' );
	} );

	it( 'allows overriding model data', function (): void {
		$model = createTwitterTestModel( [
			'title' => 'Model Title',
			'slug'  => 'model-slug',
		] );

		$component = new TwitterCard(
			model: $model,
			title: 'Override Twitter Title',
			description: 'Override Twitter Description',
		);

		expect( $component->twitterTitle )->toBe( 'Override Twitter Title' )
			->and( $component->twitterDescription )->toBe( 'Override Twitter Description' );
	} );

} );
