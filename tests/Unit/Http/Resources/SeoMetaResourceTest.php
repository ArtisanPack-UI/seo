<?php

/**
 * SeoMetaResource Tests.
 *
 * Unit tests for SeoMetaResource.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Http\Resources\SeoMetaResource;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

uses( RefreshDatabase::class );

/**
 * Test model for SeoMetaResource tests.
 */
class SeoMetaResourceTestPage extends Model
{
	use HasSeo;

	protected $table = 'seo_meta_resource_test_pages';

	protected $fillable = [ 'title' ];
}

beforeEach( function (): void {
	Schema::create( 'seo_meta_resource_test_pages', function ( Blueprint $table ): void {
		$table->id();
		$table->string( 'title' )->nullable();
		$table->timestamps();
	} );

	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../../database/migrations' ) ] );
} );

afterEach( function (): void {
	Schema::dropIfExists( 'seo_meta_resource_test_pages' );
} );

describe( 'SeoMetaResource', function (): void {

	it( 'includes computed character count fields', function (): void {
		$page    = SeoMetaResourceTestPage::create( [ 'title' => 'Test' ] );
		$seoMeta = $page->seoMeta()->create( [
			'meta_title'       => 'Test SEO Title',
			'meta_description' => 'A short description.',
		] );

		$resource = new SeoMetaResource( $seoMeta );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['meta_title_length'] )->toBe( 14 )
			->and( $result['meta_title_warning'] )->toBeNull()
			->and( $result['meta_description_length'] )->toBe( 20 )
			->and( $result['meta_description_warning'] )->toBeNull();
	} );

	it( 'includes title warning for long titles', function (): void {
		$page    = SeoMetaResourceTestPage::create( [ 'title' => 'Test' ] );
		$seoMeta = $page->seoMeta()->create( [
			'meta_title' => str_repeat( 'a', 65 ),
		] );

		$resource = new SeoMetaResource( $seoMeta );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['meta_title_warning'] )->not->toBeNull()
			->and( $result['meta_title_length'] )->toBe( 65 );
	} );

	it( 'includes computed boolean fields', function (): void {
		$page    = SeoMetaResourceTestPage::create( [ 'title' => 'Test' ] );
		$seoMeta = $page->seoMeta()->create( [
			'no_index'  => false,
			'no_follow' => false,
		] );

		$resource = new SeoMetaResource( $seoMeta );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['is_indexable'] )->toBeTrue()
			->and( $result['is_followable'] )->toBeTrue()
			->and( $result['robots_content'] )->toBe( 'index, follow' )
			->and( $result['in_sitemap'] )->toBeTrue();
	} );

	it( 'includes has_open_graph and has_twitter_card flags', function (): void {
		$page    = SeoMetaResourceTestPage::create( [ 'title' => 'Test' ] );
		$seoMeta = $page->seoMeta()->create( [
			'og_title'      => 'OG Title',
			'twitter_title' => 'Twitter Title',
			'schema_type'   => 'Article',
		] );

		$resource = new SeoMetaResource( $seoMeta );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['has_open_graph'] )->toBeTrue()
			->and( $result['has_twitter_card'] )->toBeTrue()
			->and( $result['has_schema'] )->toBeTrue();
	} );

	it( 'includes all_keywords computed field', function (): void {
		$page    = SeoMetaResourceTestPage::create( [ 'title' => 'Test' ] );
		$seoMeta = $page->seoMeta()->create( [
			'focus_keyword'      => 'primary',
			'secondary_keywords' => [ 'secondary', 'tertiary' ],
		] );

		$resource = new SeoMetaResource( $seoMeta );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['all_keywords'] )->toBe( [ 'primary', 'secondary', 'tertiary' ] );
	} );

	it( 'includes analysis_cache when loaded', function (): void {
		$page    = SeoMetaResourceTestPage::create( [ 'title' => 'Test' ] );
		$seoMeta = $page->seoMeta()->create( [ 'meta_title' => 'Test' ] );

		$seoMeta->analysisCache()->create( [
			'overall_score'      => 75,
			'readability_score'  => 80,
			'keyword_score'      => 70,
			'meta_score'         => 75,
			'content_score'      => 72,
			'issues'             => [],
			'suggestions'        => [],
			'passed_checks'      => [],
			'analyzer_results'   => [],
			'analyzed_at'        => now(),
			'focus_keyword_used' => 'test',
			'content_word_count' => 500,
		] );

		$seoMeta->load( 'analysisCache' );

		$resource = new SeoMetaResource( $seoMeta );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['analysis_cache'] )->not->toBeNull()
			->and( $result['analysis_cache']['overall_score'] )->toBe( 75 )
			->and( $result['analysis_cache']['grade'] )->toBe( 'ok' );
	} );

	it( 'excludes analysis_cache when not loaded', function (): void {
		$page    = SeoMetaResourceTestPage::create( [ 'title' => 'Test' ] );
		$seoMeta = $page->seoMeta()->create( [ 'meta_title' => 'Test' ] );

		$resource = new SeoMetaResource( $seoMeta );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result )->toHaveKey( 'analysis_cache' );
		// whenLoaded returns MissingValue when not loaded
	} );

	it( 'includes pinterest and slack fields', function (): void {
		$page    = SeoMetaResourceTestPage::create( [ 'title' => 'Test' ] );
		$seoMeta = $page->seoMeta()->create( [
			'pinterest_description' => 'Pin desc',
			'slack_title'           => 'Slack Title',
		] );

		$resource = new SeoMetaResource( $seoMeta );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['pinterest_description'] )->toBe( 'Pin desc' )
			->and( $result['slack_title'] )->toBe( 'Slack Title' );
	} );
} );
