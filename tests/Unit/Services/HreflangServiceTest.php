<?php

/**
 * HreflangService Tests.
 *
 * Unit tests for the HreflangService.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\CacheService;
use ArtisanPackUI\SEO\Services\HreflangService;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

/**
 * Test model for HreflangService tests.
 */
class HreflangServiceTestModel extends Model
{
	use HasSeo;

	protected $table = 'hreflang_service_test_models';

	protected $fillable = [ 'title' ];
}

beforeEach( function (): void {
	// Run migrations
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	// Create test table
	$this->app['db']->connection()->getSchemaBuilder()->create( 'hreflang_service_test_models', function ( $table ): void {
		$table->id();
		$table->string( 'title' );
		$table->timestamps();
	} );

	config( [
		'seo.hreflang.enabled'            => true,
		'seo.hreflang.default_locale'     => 'en',
		'seo.hreflang.auto_add_x_default' => true,
		'seo.hreflang.supported_locales'  => [ 'en', 'es', 'fr', 'de' ],
		'seo.cache.enabled'               => false,
	] );
} );

describe( 'HreflangService Locale Validation', function (): void {

	it( 'validates language-only codes', function (): void {
		$service = new HreflangService( new CacheService() );

		expect( $service->validateLocale( 'en' ) )->toBeTrue()
			->and( $service->validateLocale( 'fr' ) )->toBeTrue()
			->and( $service->validateLocale( 'de' ) )->toBeTrue()
			->and( $service->validateLocale( 'zh' ) )->toBeTrue();
	} );

	it( 'validates three-letter language codes', function (): void {
		$service = new HreflangService( new CacheService() );

		expect( $service->validateLocale( 'zho' ) )->toBeTrue();
	} );

	it( 'validates language-region codes', function (): void {
		$service = new HreflangService( new CacheService() );

		expect( $service->validateLocale( 'en-US' ) )->toBeTrue()
			->and( $service->validateLocale( 'fr-FR' ) )->toBeTrue()
			->and( $service->validateLocale( 'zh-CN' ) )->toBeTrue()
			->and( $service->validateLocale( 'es-MX' ) )->toBeTrue();
	} );

	it( 'validates x-default', function (): void {
		$service = new HreflangService( new CacheService() );

		expect( $service->validateLocale( 'x-default' ) )->toBeTrue();
	} );

	it( 'rejects invalid locale formats', function (): void {
		$service = new HreflangService( new CacheService() );

		expect( $service->validateLocale( '' ) )->toBeFalse()
			->and( $service->validateLocale( 'e' ) )->toBeFalse()
			->and( $service->validateLocale( 'english' ) )->toBeFalse()
			->and( $service->validateLocale( 'en_US' ) )->toBeFalse()
			->and( $service->validateLocale( 'EN-US' ) )->toBeFalse()
			->and( $service->validateLocale( 'en-us' ) )->toBeFalse()
			->and( $service->validateLocale( 'en-USA' ) )->toBeFalse();
	} );

} );

describe( 'HreflangService Configuration', function (): void {

	it( 'checks if hreflang is enabled', function (): void {
		$service = new HreflangService( new CacheService() );

		config( [ 'seo.hreflang.enabled' => true ] );
		expect( $service->isEnabled() )->toBeTrue();

		config( [ 'seo.hreflang.enabled' => false ] );
		expect( $service->isEnabled() )->toBeFalse();
	} );

	it( 'returns default locale from config', function (): void {
		$service = new HreflangService( new CacheService() );

		config( [ 'seo.hreflang.default_locale' => 'fr' ] );
		expect( $service->getDefaultLocale() )->toBe( 'fr' );
	} );

	it( 'returns available locales from config', function (): void {
		config( [ 'seo.hreflang.supported_locales' => [ 'en', 'es' ] ] );
		$service = new HreflangService( new CacheService() );

		$locales = $service->getAvailableLocales();

		expect( $locales )->toHaveCount( 2 )
			->and( $locales[0]['value'] )->toBe( 'en' )
			->and( $locales[1]['value'] )->toBe( 'es' );
	} );

	it( 'returns all common locales when config is empty', function (): void {
		config( [ 'seo.hreflang.supported_locales' => [] ] );
		$service = new HreflangService( new CacheService() );

		$locales = $service->getAvailableLocales();

		expect( count( $locales ) )->toBeGreaterThan( 10 );
	} );

} );

describe( 'HreflangService Locale Labels', function (): void {

	it( 'returns label for known locale', function (): void {
		$service = new HreflangService( new CacheService() );

		expect( $service->getLocaleLabel( 'en' ) )->toBe( 'English' )
			->and( $service->getLocaleLabel( 'en-US' ) )->toBe( 'English (United States)' )
			->and( $service->getLocaleLabel( 'fr' ) )->toBe( 'French' )
			->and( $service->getLocaleLabel( 'zh-CN' ) )->toBe( 'Chinese (Simplified)' );
	} );

	it( 'returns locale code for unknown locale', function (): void {
		$service = new HreflangService( new CacheService() );

		expect( $service->getLocaleLabel( 'xy' ) )->toBe( 'xy' )
			->and( $service->getLocaleLabel( 'xx-YY' ) )->toBe( 'xx-YY' );
	} );

} );

describe( 'HreflangService Building Tags', function (): void {

	it( 'builds hreflang tags from data array', function (): void {
		$service = new HreflangService( new CacheService() );

		$data = [
			'en'    => 'https://example.com/en/page',
			'es'    => 'https://example.com/es/page',
			'fr-FR' => 'https://example.com/fr/page',
		];

		$tags = $service->buildHreflangTags( $data );

		expect( $tags )->toHaveCount( 4 ) // 3 locales + x-default
			->and( $tags[0] )->toMatchArray( [
				'hreflang' => 'en',
				'href'     => 'https://example.com/en/page',
			] )
			->and( $tags[1] )->toMatchArray( [
				'hreflang' => 'es',
				'href'     => 'https://example.com/es/page',
			] )
			->and( $tags[2] )->toMatchArray( [
				'hreflang' => 'fr-FR',
				'href'     => 'https://example.com/fr/page',
			] )
			->and( $tags[3] )->toMatchArray( [
				'hreflang' => 'x-default',
				'href'     => 'https://example.com/en/page',
			] );
	} );

	it( 'does not duplicate x-default if already present', function (): void {
		$service = new HreflangService( new CacheService() );

		$data = [
			'en'        => 'https://example.com/en/page',
			'x-default' => 'https://example.com/page',
		];

		$tags = $service->buildHreflangTags( $data );

		$xDefaultCount = count( array_filter( $tags, fn ( $t ) => 'x-default' === $t['hreflang'] ) );
		expect( $xDefaultCount )->toBe( 1 );
	} );

	it( 'does not add x-default when default locale is missing', function (): void {
		config( [ 'seo.hreflang.default_locale' => 'de' ] );
		$service = new HreflangService( new CacheService() );

		$data = [
			'en' => 'https://example.com/en/page',
			'es' => 'https://example.com/es/page',
		];

		$tags = $service->buildHreflangTags( $data );

		$xDefaultCount = count( array_filter( $tags, fn ( $t ) => 'x-default' === $t['hreflang'] ) );
		expect( $xDefaultCount )->toBe( 0 );
	} );

} );

describe( 'HreflangService Set Alternate URL', function (): void {

	it( 'sets alternate URL for a locale', function (): void {
		$service = new HreflangService( new CacheService() );
		$seoMeta = SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => 1,
		] );

		$service->setAlternateUrl( $seoMeta, 'en', 'https://example.com/en/page' );
		$seoMeta->refresh();

		expect( $seoMeta->hreflang )->toBeArray()
			->and( $seoMeta->hreflang['en'] )->toBe( 'https://example.com/en/page' );
	} );

	it( 'adds multiple alternate URLs', function (): void {
		$service = new HreflangService( new CacheService() );
		$seoMeta = SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => 1,
		] );

		$service->setAlternateUrl( $seoMeta, 'en', 'https://example.com/en/page' );
		$service->setAlternateUrl( $seoMeta, 'es', 'https://example.com/es/page' );
		$seoMeta->refresh();

		expect( $seoMeta->hreflang )->toHaveCount( 2 )
			->and( $seoMeta->hreflang['en'] )->toBe( 'https://example.com/en/page' )
			->and( $seoMeta->hreflang['es'] )->toBe( 'https://example.com/es/page' );
	} );

	it( 'updates existing alternate URL', function (): void {
		$service = new HreflangService( new CacheService() );
		$seoMeta = SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => 1,
			'hreflang'     => [ 'en' => 'https://old.com/en' ],
		] );

		$service->setAlternateUrl( $seoMeta, 'en', 'https://new.com/en' );
		$seoMeta->refresh();

		expect( $seoMeta->hreflang['en'] )->toBe( 'https://new.com/en' );
	} );

	it( 'throws exception for invalid locale', function (): void {
		$service = new HreflangService( new CacheService() );
		$seoMeta = SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => 1,
		] );

		$service->setAlternateUrl( $seoMeta, 'invalid', 'https://example.com' );
	} )->throws( InvalidArgumentException::class );

	it( 'throws exception for invalid URL', function (): void {
		$service = new HreflangService( new CacheService() );
		$seoMeta = SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => 1,
		] );

		$service->setAlternateUrl( $seoMeta, 'en', 'not-a-url' );
	} )->throws( InvalidArgumentException::class );

} );

describe( 'HreflangService Remove Alternate URL', function (): void {

	it( 'removes alternate URL for a locale', function (): void {
		$service = new HreflangService( new CacheService() );
		$seoMeta = SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => 1,
			'hreflang'     => [
				'en' => 'https://example.com/en',
				'es' => 'https://example.com/es',
			],
		] );

		$service->removeAlternateUrl( $seoMeta, 'en' );
		$seoMeta->refresh();

		expect( $seoMeta->hreflang )->toHaveCount( 1 )
			->and( $seoMeta->hreflang )->not->toHaveKey( 'en' )
			->and( $seoMeta->hreflang['es'] )->toBe( 'https://example.com/es' );
	} );

	it( 'handles removing non-existent locale', function (): void {
		$service = new HreflangService( new CacheService() );
		$seoMeta = SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => 1,
			'hreflang'     => [ 'en' => 'https://example.com/en' ],
		] );

		$service->removeAlternateUrl( $seoMeta, 'fr' );
		$seoMeta->refresh();

		expect( $seoMeta->hreflang )->toHaveCount( 1 )
			->and( $seoMeta->hreflang['en'] )->toBe( 'https://example.com/en' );
	} );

} );

describe( 'HreflangService Set Multiple URLs', function (): void {

	it( 'sets multiple alternate URLs at once', function (): void {
		$service = new HreflangService( new CacheService() );
		$seoMeta = SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => 1,
		] );

		$service->setAlternateUrls( $seoMeta, [
			'en' => 'https://example.com/en',
			'es' => 'https://example.com/es',
			'fr' => 'https://example.com/fr',
		] );
		$seoMeta->refresh();

		expect( $seoMeta->hreflang )->toHaveCount( 3 )
			->and( $seoMeta->hreflang['en'] )->toBe( 'https://example.com/en' )
			->and( $seoMeta->hreflang['es'] )->toBe( 'https://example.com/es' )
			->and( $seoMeta->hreflang['fr'] )->toBe( 'https://example.com/fr' );
	} );

	it( 'merges with existing URLs by default', function (): void {
		$service = new HreflangService( new CacheService() );
		$seoMeta = SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => 1,
			'hreflang'     => [ 'de' => 'https://example.com/de' ],
		] );

		$service->setAlternateUrls( $seoMeta, [
			'en' => 'https://example.com/en',
		] );
		$seoMeta->refresh();

		expect( $seoMeta->hreflang )->toHaveCount( 2 )
			->and( $seoMeta->hreflang['de'] )->toBe( 'https://example.com/de' )
			->and( $seoMeta->hreflang['en'] )->toBe( 'https://example.com/en' );
	} );

	it( 'replaces all URLs when replace is true', function (): void {
		$service = new HreflangService( new CacheService() );
		$seoMeta = SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => 1,
			'hreflang'     => [ 'de' => 'https://example.com/de' ],
		] );

		$service->setAlternateUrls( $seoMeta, [
			'en' => 'https://example.com/en',
		], true );
		$seoMeta->refresh();

		expect( $seoMeta->hreflang )->toHaveCount( 1 )
			->and( $seoMeta->hreflang )->not->toHaveKey( 'de' )
			->and( $seoMeta->hreflang['en'] )->toBe( 'https://example.com/en' );
	} );

	it( 'throws exception for invalid locale in batch', function (): void {
		$service = new HreflangService( new CacheService() );
		$seoMeta = SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => 1,
		] );

		$service->setAlternateUrls( $seoMeta, [
			'en'      => 'https://example.com/en',
			'invalid' => 'https://example.com/invalid',
		] );
	} )->throws( InvalidArgumentException::class );

	it( 'throws exception for invalid URL in batch', function (): void {
		$service = new HreflangService( new CacheService() );
		$seoMeta = SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => 1,
		] );

		$service->setAlternateUrls( $seoMeta, [
			'en' => 'https://example.com/en',
			'es' => 'not-a-valid-url',
		] );
	} )->throws( InvalidArgumentException::class );

} );

describe( 'HreflangService Clear Alternate URLs', function (): void {

	it( 'clears all alternate URLs', function (): void {
		$service = new HreflangService( new CacheService() );
		$seoMeta = SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => 1,
			'hreflang'     => [
				'en' => 'https://example.com/en',
				'es' => 'https://example.com/es',
			],
		] );

		$service->clearAlternateUrls( $seoMeta );
		$seoMeta->refresh();

		expect( $seoMeta->hreflang )->toBeNull();
	} );

} );

describe( 'HreflangService Model Integration', function (): void {

	it( 'checks if model has hreflang data', function (): void {
		$service = new HreflangService( new CacheService() );

		// Model with hreflang data
		$modelWithHreflang = HreflangServiceTestModel::create( [ 'title' => 'Test Post' ] );
		SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => $modelWithHreflang->id,
			'hreflang'     => [ 'en' => 'https://example.com/en' ],
		] );

		// Model without hreflang data
		$modelWithoutHreflang = HreflangServiceTestModel::create( [ 'title' => 'Test Post 2' ] );
		SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => $modelWithoutHreflang->id,
		] );

		// Refresh models to load relationships
		$modelWithHreflang->refresh();
		$modelWithoutHreflang->refresh();

		expect( $service->hasHreflangData( $modelWithHreflang ) )->toBeTrue()
			->and( $service->hasHreflangData( $modelWithoutHreflang ) )->toBeFalse();
	} );

	it( 'returns hreflang count for model', function (): void {
		$service = new HreflangService( new CacheService() );

		$model = HreflangServiceTestModel::create( [ 'title' => 'Test Post' ] );
		SeoMeta::create( [
			'seoable_type' => HreflangServiceTestModel::class,
			'seoable_id'   => $model->id,
			'hreflang'     => [
				'en' => 'https://example.com/en',
				'es' => 'https://example.com/es',
				'fr' => 'https://example.com/fr',
			],
		] );

		// Refresh model to load relationship
		$model->refresh();

		expect( $service->getHreflangCount( $model ) )->toBe( 3 );
	} );

	it( 'returns zero count when no hreflang data', function (): void {
		$service = new HreflangService( new CacheService() );

		$model = HreflangServiceTestModel::create( [ 'title' => 'Test Post' ] );

		expect( $service->getHreflangCount( $model ) )->toBe( 0 );
	} );

} );
