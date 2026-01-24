<?php

/**
 * MetaTagService Tests.
 *
 * Unit tests for the MetaTagService.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\MetaTagsDTO;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\MetaTagService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	config( [
		'seo.site.name'                       => 'Test Site',
		'seo.site.separator'                  => ' | ',
		'seo.site.description'                => 'Default site description',
		'seo.defaults.robots'                 => 'index, follow',
		'seo.defaults.description_max_length' => 160,
		'app.name'                            => 'Laravel',
	] );

	// Run the migration
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

/**
 * Create a simple test model class.
 */
function createTestModel( array $attributes = [] ): Model
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

describe( 'MetaTagService Configuration', function (): void {

	it( 'returns configured title suffix', function (): void {
		config( [ 'seo.site.name' => 'My Awesome Site' ] );
		$service = new MetaTagService();

		expect( $service->getTitleSuffix() )->toBe( 'My Awesome Site' );
	} );

	it( 'falls back to app name for title suffix', function (): void {
		config( [ 'seo.site.name' => null, 'app.name' => 'Fallback App' ] );
		$service = new MetaTagService();

		expect( $service->getTitleSuffix() )->toBe( 'Fallback App' );
	} );

	it( 'returns configured title separator', function (): void {
		config( [ 'seo.site.separator' => ' - ' ] );
		$service = new MetaTagService();

		expect( $service->getTitleSeparator() )->toBe( ' - ' );
	} );

	it( 'returns default title separator', function (): void {
		config( [ 'seo.site.separator' => null ] );
		$service = new MetaTagService();

		expect( $service->getTitleSeparator() )->toBe( ' | ' );
	} );

} );

describe( 'MetaTagService Title Building', function (): void {

	it( 'builds full title with suffix', function (): void {
		config( [
			'seo.site.name'      => 'My Site',
			'seo.site.separator' => ' | ',
		] );
		$service = new MetaTagService();

		$title = $service->buildTitle( 'Page Title' );

		expect( $title )->toBe( 'Page Title | My Site' );
	} );

	it( 'builds title without suffix when specified', function (): void {
		config( [ 'seo.site.name' => 'My Site' ] );
		$service = new MetaTagService();

		$title = $service->buildTitle( 'Page Title', false );

		expect( $title )->toBe( 'Page Title' );
	} );

	it( 'does not duplicate suffix if already present', function (): void {
		config( [
			'seo.site.name'      => 'My Site',
			'seo.site.separator' => ' | ',
		] );
		$service = new MetaTagService();

		$title = $service->buildTitle( 'Page Title | My Site' );

		expect( $title )->toBe( 'Page Title | My Site' );
	} );

	it( 'does not add suffix when suffix is empty', function (): void {
		config( [ 'seo.site.name' => '' ] );
		$service = new MetaTagService();

		$title = $service->buildTitle( 'Page Title' );

		expect( $title )->toBe( 'Page Title' );
	} );

} );

describe( 'MetaTagService Robots Directive', function (): void {

	it( 'returns default robots when seo meta is null', function (): void {
		config( [ 'seo.defaults.robots' => 'index, follow' ] );
		$service = new MetaTagService();

		$robots = $service->buildRobotsDirective( null );

		expect( $robots )->toBe( 'index, follow' );
	} );

	it( 'returns robots content from seo meta', function (): void {
		$service = new MetaTagService();

		$seoMeta = new SeoMeta( [
			'no_index'  => true,
			'no_follow' => false,
		] );

		$robots = $service->buildRobotsDirective( $seoMeta );

		expect( $robots )->toBe( 'noindex' );
	} );

	it( 'returns noindex nofollow when both are set', function (): void {
		$service = new MetaTagService();

		$seoMeta = new SeoMeta( [
			'no_index'  => true,
			'no_follow' => true,
		] );

		$robots = $service->buildRobotsDirective( $seoMeta );

		expect( $robots )->toBe( 'noindex, nofollow' );
	} );

} );

describe( 'MetaTagService Generate Method', function (): void {

	it( 'generates meta tags DTO from model without seo meta', function (): void {
		config( [ 'app.name' => 'Fallback App' ] );
		$service = new MetaTagService();

		$model = createTestModel( [
			'title' => 'Model Title',
			'slug'  => 'model-slug',
		] );

		$dto = $service->generate( $model, null );

		expect( $dto )->toBeInstanceOf( MetaTagsDTO::class )
			->and( $dto->title )->toContain( 'Model Title' )
			->and( $dto->robots )->toBe( 'index, follow' );
	} );

	it( 'generates meta tags DTO from model with seo meta', function (): void {
		$service = new MetaTagService();

		$model = createTestModel( [] );

		$seoMeta = new SeoMeta( [
			'meta_title'       => 'SEO Title',
			'meta_description' => 'SEO Description for testing purposes.',
			'canonical_url'    => 'https://example.com/custom-canonical',
			'no_index'         => false,
			'no_follow'        => false,
		] );

		$dto = $service->generate( $model, $seoMeta );

		expect( $dto )->toBeInstanceOf( MetaTagsDTO::class )
			->and( $dto->title )->toContain( 'SEO Title' )
			->and( $dto->description )->toBe( 'SEO Description for testing purposes.' )
			->and( $dto->canonical )->toBe( 'https://example.com/custom-canonical' )
			->and( $dto->robots )->toBe( 'index, follow' );
	} );

	it( 'truncates description to configured max length', function (): void {
		config( [ 'seo.defaults.description_max_length' => 50 ] );
		$service = new MetaTagService();

		$model = createTestModel( [
			'title'       => 'Title',
			'description' => 'This is a very long description that should be truncated because it exceeds the maximum length limit that is configured.',
		] );

		$dto = $service->generate( $model, null );

		expect( strlen( $dto->description ) )->toBeLessThanOrEqual( 53 ); // 50 + "..."
	} );

	it( 'strips HTML tags from description', function (): void {
		$service = new MetaTagService();

		$seoMeta = new SeoMeta( [
			'meta_description' => '<p>Description with <strong>HTML</strong> tags.</p>',
		] );

		$model = createTestModel( [] );

		$dto = $service->generate( $model, $seoMeta );

		expect( $dto->description )->toBe( 'Description with HTML tags.' );
	} );

	it( 'includes focus keyword in additional meta', function (): void {
		$service = new MetaTagService();

		$seoMeta = new SeoMeta( [
			'meta_title'         => 'Title',
			'focus_keyword'      => 'main keyword',
			'secondary_keywords' => [ 'secondary1', 'secondary2' ],
		] );

		$model = createTestModel( [] );

		$dto = $service->generate( $model, $seoMeta );

		expect( $dto->additionalMeta )->toHaveKey( 'keywords' )
			->and( $dto->additionalMeta['keywords'] )->toContain( 'main keyword' )
			->and( $dto->additionalMeta['keywords'] )->toContain( 'secondary1' )
			->and( $dto->additionalMeta['keywords'] )->toContain( 'secondary2' );
	} );

} );

describe( 'MetaTagService Title Resolution', function (): void {

	it( 'uses seo meta title as first priority', function (): void {
		$service = new MetaTagService();

		$model = createTestModel( [
			'meta_title' => 'Model Meta Title',
			'title'      => 'Model Title',
		] );

		$seoMeta = new SeoMeta( [
			'meta_title' => 'SEO Meta Title',
		] );

		$dto = $service->generate( $model, $seoMeta );

		expect( $dto->title )->toContain( 'SEO Meta Title' );
	} );

	it( 'falls back to model meta_title', function (): void {
		$service = new MetaTagService();

		$model = createTestModel( [
			'meta_title' => 'Model Meta Title',
			'title'      => 'Model Title',
		] );

		$seoMeta = new SeoMeta( [
			'meta_title' => null,
		] );

		$dto = $service->generate( $model, $seoMeta );

		expect( $dto->title )->toContain( 'Model Meta Title' );
	} );

	it( 'falls back to model title', function (): void {
		$service = new MetaTagService();

		$model = createTestModel( [
			'title' => 'Model Title',
		] );

		$seoMeta = new SeoMeta( [
			'meta_title' => null,
		] );

		$dto = $service->generate( $model, $seoMeta );

		expect( $dto->title )->toContain( 'Model Title' );
	} );

	it( 'falls back to model name', function (): void {
		$service = new MetaTagService();

		$model = createTestModel( [
			'name' => 'Model Name',
		] );

		$dto = $service->generate( $model, null );

		expect( $dto->title )->toContain( 'Model Name' );
	} );

	it( 'falls back to app name as last resort', function (): void {
		config( [ 'app.name' => 'Final Fallback App' ] );
		$service = new MetaTagService();

		$model = createTestModel( [] );

		$dto = $service->generate( $model, null );

		expect( $dto->title )->toContain( 'Final Fallback App' );
	} );

} );
