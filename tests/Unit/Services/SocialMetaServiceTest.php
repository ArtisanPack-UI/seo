<?php

/**
 * SocialMetaService Tests.
 *
 * Unit tests for the SocialMetaService.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\OpenGraphDTO;
use ArtisanPackUI\SEO\DTOs\TwitterCardDTO;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\SocialMetaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	config( [
		'seo.open_graph.site_name'     => 'Test Site',
		'seo.open_graph.type'          => 'website',
		'seo.open_graph.default_image' => 'https://example.com/default-og.jpg',
		'seo.twitter.card_type'        => 'summary_large_image',
		'seo.twitter.site'             => '@TestSite',
		'seo.twitter.creator'          => null,
		'seo.twitter.default_image'    => 'https://example.com/default-twitter.jpg',
		'app.name'                     => 'Laravel',
	] );

	// Run the migration
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

/**
 * Create a simple test model class.
 */
function createSocialTestModel( array $attributes = [] ): Model
{
	return new class( $attributes ) extends Model {
		protected $guarded = [];

		public function __construct( array $attributes = [] )
		{
			parent::__construct();
			foreach ( $attributes as $key => $value ) {
				$this->setAttribute( $key, $value );
			}
		}
	};
}

describe( 'SocialMetaService Open Graph Generation', function (): void {

	it( 'generates Open Graph DTO from model without seo meta', function (): void {
		$service = new SocialMetaService();

		$model = createSocialTestModel( [
			'title' => 'Model Title',
			'slug'  => 'model-slug',
		] );

		$dto = $service->generateOpenGraph( $model, null );

		expect( $dto )->toBeInstanceOf( OpenGraphDTO::class )
			->and( $dto->title )->toBe( 'Model Title' )
			->and( $dto->type )->toBe( 'website' )
			->and( $dto->siteName )->toBe( 'Test Site' );
	} );

	it( 'generates Open Graph DTO from model with seo meta', function (): void {
		$service = new SocialMetaService();

		$model = createSocialTestModel( [
			'slug' => 'slug',
		] );

		$seoMeta = new SeoMeta( [
			'og_title'       => 'OG Custom Title',
			'og_description' => 'OG Custom Description',
			'og_image'       => 'https://example.com/og-image.jpg',
			'og_type'        => 'article',
			'og_site_name'   => 'Custom Site Name',
			'og_locale'      => 'es_ES',
			'canonical_url'  => 'https://example.com/canonical',
		] );

		$dto = $service->generateOpenGraph( $model, $seoMeta );

		expect( $dto )->toBeInstanceOf( OpenGraphDTO::class )
			->and( $dto->title )->toBe( 'OG Custom Title' )
			->and( $dto->description )->toBe( 'OG Custom Description' )
			->and( $dto->image )->toBe( 'https://example.com/og-image.jpg' )
			->and( $dto->type )->toBe( 'article' )
			->and( $dto->siteName )->toBe( 'Custom Site Name' )
			->and( $dto->locale )->toBe( 'es_ES' )
			->and( $dto->url )->toBe( 'https://example.com/canonical' );
	} );

	it( 'falls back to meta_title for og_title', function (): void {
		$service = new SocialMetaService();

		$model = createSocialTestModel( [
			'slug' => 'slug',
		] );

		$seoMeta = new SeoMeta( [
			'meta_title' => 'Meta Title',
			'og_title'   => null,
		] );

		$dto = $service->generateOpenGraph( $model, $seoMeta );

		expect( $dto->title )->toBe( 'Meta Title' );
	} );

	it( 'falls back to model title for og_title', function (): void {
		$service = new SocialMetaService();

		$model = createSocialTestModel( [
			'title' => 'Model Title',
			'slug'  => 'slug',
		] );

		$seoMeta = new SeoMeta( [
			'og_title'   => null,
			'meta_title' => null,
		] );

		$dto = $service->generateOpenGraph( $model, $seoMeta );

		expect( $dto->title )->toBe( 'Model Title' );
	} );

	it( 'truncates og description to 200 characters', function (): void {
		$service = new SocialMetaService();

		$longDescription = str_repeat( 'This is a very long description. ', 20 );

		$model = createSocialTestModel( [
			'slug' => 'slug',
		] );

		$seoMeta = new SeoMeta( [
			'og_description' => $longDescription,
		] );

		$dto = $service->generateOpenGraph( $model, $seoMeta );

		expect( strlen( $dto->description ) )->toBeLessThanOrEqual( 203 ); // 200 + "..."
	} );

	it( 'uses default og image from config when none specified', function (): void {
		config( [ 'seo.open_graph.default_image' => 'https://example.com/default-og.jpg' ] );
		$service = new SocialMetaService();

		$model = createSocialTestModel( [
			'title' => 'Title',
			'slug'  => 'slug',
		] );

		$dto = $service->generateOpenGraph( $model, null );

		expect( $dto->image )->toBe( 'https://example.com/default-og.jpg' );
	} );

	it( 'uses model featured_image when available', function (): void {
		$service = new SocialMetaService();

		$model = createSocialTestModel( [
			'title'          => 'Title',
			'slug'           => 'slug',
			'featured_image' => 'https://example.com/featured.jpg',
		] );

		$seoMeta = new SeoMeta( [
			'og_image' => null,
		] );

		$dto = $service->generateOpenGraph( $model, $seoMeta );

		expect( $dto->image )->toBe( 'https://example.com/featured.jpg' );
	} );

} );

describe( 'SocialMetaService OG Type Inference', function (): void {

	it( 'uses default website type for generic models', function (): void {
		config( [ 'seo.open_graph.type' => 'website' ] );
		$service = new SocialMetaService();

		$model = createSocialTestModel( [
			'title' => 'Page Title',
			'slug'  => 'page-slug',
		] );

		$dto = $service->generateOpenGraph( $model, null );

		expect( $dto->type )->toBe( 'website' );
	} );

} );

describe( 'SocialMetaService Twitter Card Generation', function (): void {

	it( 'generates Twitter Card DTO from model without seo meta', function (): void {
		$service = new SocialMetaService();

		$model = createSocialTestModel( [
			'title' => 'Model Title',
			'slug'  => 'model-slug',
		] );

		$dto = $service->generateTwitterCard( $model, null );

		expect( $dto )->toBeInstanceOf( TwitterCardDTO::class )
			->and( $dto->title )->toBe( 'Model Title' )
			->and( $dto->card )->toBe( 'summary_large_image' )
			->and( $dto->site )->toBe( '@TestSite' );
	} );

	it( 'generates Twitter Card DTO from model with seo meta', function (): void {
		$service = new SocialMetaService();

		$model = createSocialTestModel( [
			'slug' => 'slug',
		] );

		$seoMeta = new SeoMeta( [
			'twitter_card'        => 'summary',
			'twitter_title'       => 'Twitter Custom Title',
			'twitter_description' => 'Twitter Custom Description',
			'twitter_image'       => 'https://example.com/twitter-image.jpg',
			'twitter_site'        => '@CustomSite',
			'twitter_creator'     => '@CustomCreator',
		] );

		$dto = $service->generateTwitterCard( $model, $seoMeta );

		expect( $dto )->toBeInstanceOf( TwitterCardDTO::class )
			->and( $dto->card )->toBe( 'summary' )
			->and( $dto->title )->toBe( 'Twitter Custom Title' )
			->and( $dto->description )->toBe( 'Twitter Custom Description' )
			->and( $dto->image )->toBe( 'https://example.com/twitter-image.jpg' )
			->and( $dto->site )->toBe( '@CustomSite' )
			->and( $dto->creator )->toBe( '@CustomCreator' );
	} );

	it( 'falls back to og title for twitter title', function (): void {
		$service = new SocialMetaService();

		$model = createSocialTestModel( [
			'title' => 'Model Title',
			'slug'  => 'slug',
		] );

		$seoMeta = new SeoMeta( [
			'og_title'      => 'OG Title',
			'twitter_title' => null,
		] );

		$dto = $service->generateTwitterCard( $model, $seoMeta );

		expect( $dto->title )->toBe( 'OG Title' );
	} );

	it( 'falls back to og image for twitter image', function (): void {
		$service = new SocialMetaService();

		$model = createSocialTestModel( [
			'title' => 'Title',
			'slug'  => 'slug',
		] );

		$seoMeta = new SeoMeta( [
			'og_image'      => 'https://example.com/og-image.jpg',
			'twitter_image' => null,
		] );

		$dto = $service->generateTwitterCard( $model, $seoMeta );

		expect( $dto->image )->toBe( 'https://example.com/og-image.jpg' );
	} );

	it( 'uses config twitter site when not in seo meta', function (): void {
		config( [ 'seo.twitter.site' => '@ConfiguredSite' ] );
		$service = new SocialMetaService();

		$model = createSocialTestModel( [
			'title' => 'Title',
			'slug'  => 'slug',
		] );

		$seoMeta = new SeoMeta( [
			'twitter_site' => null,
		] );

		$dto = $service->generateTwitterCard( $model, $seoMeta );

		expect( $dto->site )->toBe( '@ConfiguredSite' );
	} );

	it( 'uses config twitter creator when not in seo meta', function (): void {
		config( [ 'seo.twitter.creator' => '@ConfiguredCreator' ] );
		$service = new SocialMetaService();

		$model = createSocialTestModel( [
			'title' => 'Title',
			'slug'  => 'slug',
		] );

		$seoMeta = new SeoMeta( [
			'twitter_creator' => null,
		] );

		$dto = $service->generateTwitterCard( $model, $seoMeta );

		expect( $dto->creator )->toBe( '@ConfiguredCreator' );
	} );

} );
