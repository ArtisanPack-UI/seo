<?php

/**
 * SeoMeta Model Tests.
 *
 * Unit tests for the SeoMeta Eloquent model.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function () {
	// Run the migration
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

describe( 'SeoMeta Model', function () {

	it( 'can create a seo meta record', function () {
		$seoMeta = SeoMeta::create( [
			'seoable_type'     => 'App\Models\Page',
			'seoable_id'       => 1,
			'meta_title'       => 'Test Page Title',
			'meta_description' => 'This is a test description for SEO purposes.',
		] );

		expect( $seoMeta )->toBeInstanceOf( SeoMeta::class )
			->and( $seoMeta->meta_title )->toBe( 'Test Page Title' )
			->and( $seoMeta->meta_description )->toBe( 'This is a test description for SEO purposes.' );
	} );

	it( 'casts boolean fields correctly', function () {
		$seoMeta = SeoMeta::create( [
			'seoable_type'         => 'App\Models\Page',
			'seoable_id'           => 1,
			'no_index'             => true,
			'no_follow'            => true,
			'exclude_from_sitemap' => true,
		] );

		expect( $seoMeta->no_index )->toBeTrue()
			->and( $seoMeta->no_follow )->toBeTrue()
			->and( $seoMeta->exclude_from_sitemap )->toBeTrue();
	} );

	it( 'casts JSON fields correctly', function () {
		$schemaMarkup       = [ '@type' => 'Article', 'headline' => 'Test' ];
		$secondaryKeywords  = [ 'keyword1', 'keyword2', 'keyword3' ];
		$hreflang           = [ 'en' => 'https://example.com/en', 'es' => 'https://example.com/es' ];

		$seoMeta = SeoMeta::create( [
			'seoable_type'       => 'App\Models\Page',
			'seoable_id'         => 1,
			'schema_markup'      => $schemaMarkup,
			'secondary_keywords' => $secondaryKeywords,
			'hreflang'           => $hreflang,
		] );

		// Refresh from database
		$seoMeta->refresh();

		expect( $seoMeta->schema_markup )->toBe( $schemaMarkup )
			->and( $seoMeta->secondary_keywords )->toBe( $secondaryKeywords )
			->and( $seoMeta->hreflang )->toBe( $hreflang );
	} );

	it( 'has correct default values', function () {
		$seoMeta = SeoMeta::create( [
			'seoable_type' => 'App\Models\Page',
			'seoable_id'   => 1,
		] );

		// Refresh from database to get default values
		$seoMeta->refresh();

		expect( $seoMeta->no_index )->toBeFalse()
			->and( $seoMeta->no_follow )->toBeFalse()
			->and( $seoMeta->exclude_from_sitemap )->toBeFalse()
			->and( $seoMeta->og_type )->toBe( 'website' )
			->and( $seoMeta->twitter_card )->toBe( 'summary_large_image' )
			->and( (string) $seoMeta->sitemap_priority )->toBe( '0.5' )
			->and( $seoMeta->sitemap_changefreq )->toBe( 'weekly' );
	} );

} );

describe( 'SeoMeta Scopes', function () {

	beforeEach( function () {
		// Create test records
		SeoMeta::create( [
			'seoable_type' => 'App\Models\Page',
			'seoable_id'   => 1,
			'no_index'     => false,
			'focus_keyword' => 'test keyword',
			'exclude_from_sitemap' => false,
			'schema_type'  => 'Article',
		] );

		SeoMeta::create( [
			'seoable_type' => 'App\Models\Page',
			'seoable_id'   => 2,
			'no_index'     => true,
			'focus_keyword' => null,
			'exclude_from_sitemap' => true,
			'schema_type'  => 'WebPage',
		] );

		SeoMeta::create( [
			'seoable_type' => 'App\Models\Post',
			'seoable_id'   => 1,
			'no_index'     => false,
			'focus_keyword' => 'another keyword',
			'exclude_from_sitemap' => false,
			'schema_type'  => 'Article',
		] );
	} );

	it( 'filters indexable entries', function () {
		$indexable = SeoMeta::indexable()->get();

		expect( $indexable )->toHaveCount( 2 )
			->and( $indexable->pluck( 'no_index' )->unique()->toArray() )->toBe( [ false ] );
	} );

	it( 'filters entries with focus keyword', function () {
		$withKeyword = SeoMeta::withFocusKeyword()->get();

		expect( $withKeyword )->toHaveCount( 2 )
			->and( $withKeyword->pluck( 'focus_keyword' )->filter()->count() )->toBe( 2 );
	} );

	it( 'filters entries in sitemap', function () {
		$inSitemap = SeoMeta::inSitemap()->get();

		expect( $inSitemap )->toHaveCount( 2 )
			->and( $inSitemap->pluck( 'exclude_from_sitemap' )->unique()->toArray() )->toBe( [ false ] );
	} );

	it( 'filters by schema type', function () {
		$articles = SeoMeta::withSchemaType( 'Article' )->get();

		expect( $articles )->toHaveCount( 2 )
			->and( $articles->pluck( 'schema_type' )->unique()->toArray() )->toBe( [ 'Article' ] );
	} );

	it( 'filters by seoable type', function () {
		$pages = SeoMeta::forType( 'App\Models\Page' )->get();

		expect( $pages )->toHaveCount( 2 )
			->and( $pages->pluck( 'seoable_type' )->unique()->toArray() )->toBe( [ 'App\Models\Page' ] );
	} );

} );

describe( 'SeoMeta Helper Methods', function () {

	it( 'returns effective title from meta_title', function () {
		$seoMeta = new SeoMeta( [
			'meta_title' => 'Custom SEO Title',
		] );

		expect( $seoMeta->getEffectiveTitle() )->toBe( 'Custom SEO Title' );
	} );

	it( 'returns app name when meta_title is empty', function () {
		$seoMeta = new SeoMeta( [
			'meta_title' => null,
		] );

		config( [ 'app.name' => 'Test App' ] );

		expect( $seoMeta->getEffectiveTitle() )->toBe( 'Test App' );
	} );

	it( 'returns effective description from meta_description', function () {
		$seoMeta = new SeoMeta( [
			'meta_description' => 'Custom SEO description.',
		] );

		expect( $seoMeta->getEffectiveDescription() )->toBe( 'Custom SEO description.' );
	} );

	it( 'returns null when meta_description is empty', function () {
		$seoMeta = new SeoMeta( [
			'meta_description' => null,
		] );

		expect( $seoMeta->getEffectiveDescription() )->toBeNull();
	} );

	it( 'generates correct robots content for index follow', function () {
		$seoMeta = new SeoMeta( [
			'no_index'  => false,
			'no_follow' => false,
		] );

		expect( $seoMeta->getRobotsContent() )->toBe( 'index, follow' );
	} );

	it( 'generates correct robots content for noindex', function () {
		$seoMeta = new SeoMeta( [
			'no_index'  => true,
			'no_follow' => false,
		] );

		expect( $seoMeta->getRobotsContent() )->toBe( 'noindex' );
	} );

	it( 'generates correct robots content for noindex nofollow', function () {
		$seoMeta = new SeoMeta( [
			'no_index'  => true,
			'no_follow' => true,
		] );

		expect( $seoMeta->getRobotsContent() )->toBe( 'noindex, nofollow' );
	} );

	it( 'includes additional robots meta directives', function () {
		$seoMeta = new SeoMeta( [
			'no_index'    => true,
			'no_follow'   => false,
			'robots_meta' => 'noarchive',
		] );

		expect( $seoMeta->getRobotsContent() )->toBe( 'noindex, noarchive' );
	} );

	it( 'correctly identifies indexable status', function () {
		$indexable   = new SeoMeta( [ 'no_index' => false ] );
		$noIndexable = new SeoMeta( [ 'no_index' => true ] );

		expect( $indexable->isIndexable() )->toBeTrue()
			->and( $noIndexable->isIndexable() )->toBeFalse();
	} );

	it( 'correctly identifies followable status', function () {
		$followable   = new SeoMeta( [ 'no_follow' => false ] );
		$noFollowable = new SeoMeta( [ 'no_follow' => true ] );

		expect( $followable->isFollowable() )->toBeTrue()
			->and( $noFollowable->isFollowable() )->toBeFalse();
	} );

	it( 'correctly determines sitemap inclusion', function () {
		$includable = new SeoMeta( [
			'no_index'             => false,
			'exclude_from_sitemap' => false,
		] );

		$excludedByIndex = new SeoMeta( [
			'no_index'             => true,
			'exclude_from_sitemap' => false,
		] );

		$excludedManually = new SeoMeta( [
			'no_index'             => false,
			'exclude_from_sitemap' => true,
		] );

		expect( $includable->shouldIncludeInSitemap() )->toBeTrue()
			->and( $excludedByIndex->shouldIncludeInSitemap() )->toBeFalse()
			->and( $excludedManually->shouldIncludeInSitemap() )->toBeFalse();
	} );

	it( 'returns all keywords combined', function () {
		$seoMeta = new SeoMeta( [
			'focus_keyword'      => 'main keyword',
			'secondary_keywords' => [ 'secondary1', 'secondary2' ],
		] );

		$keywords = $seoMeta->getAllKeywords();

		expect( $keywords )->toBe( [ 'main keyword', 'secondary1', 'secondary2' ] );
	} );

	it( 'returns empty array when no keywords', function () {
		$seoMeta = new SeoMeta( [
			'focus_keyword'      => null,
			'secondary_keywords' => null,
		] );

		expect( $seoMeta->getAllKeywords() )->toBe( [] );
	} );

	it( 'detects Open Graph data presence', function () {
		$withOg    = new SeoMeta( [ 'og_title' => 'OG Title' ] );
		$withoutOg = new SeoMeta( [] );

		expect( $withOg->hasOpenGraphData() )->toBeTrue()
			->and( $withoutOg->hasOpenGraphData() )->toBeFalse();
	} );

	it( 'detects Twitter Card data presence', function () {
		$withTwitter    = new SeoMeta( [ 'twitter_title' => 'Twitter Title' ] );
		$withoutTwitter = new SeoMeta( [] );

		expect( $withTwitter->hasTwitterCardData() )->toBeTrue()
			->and( $withoutTwitter->hasTwitterCardData() )->toBeFalse();
	} );

	it( 'detects schema markup presence', function () {
		$withSchemaType   = new SeoMeta( [ 'schema_type' => 'Article' ] );
		$withSchemaMarkup = new SeoMeta( [ 'schema_markup' => [ '@type' => 'Article' ] ] );
		$withoutSchema    = new SeoMeta( [] );

		expect( $withSchemaType->hasSchemaMarkup() )->toBeTrue()
			->and( $withSchemaMarkup->hasSchemaMarkup() )->toBeTrue()
			->and( $withoutSchema->hasSchemaMarkup() )->toBeFalse();
	} );

} );
