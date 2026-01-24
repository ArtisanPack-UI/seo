<?php

/**
 * SeoMetaEditor Livewire Component Tests.
 *
 * Feature tests for the SeoMetaEditor Livewire component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Livewire\SeoMetaEditor;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use Livewire\Livewire;

uses( RefreshDatabase::class );

/**
 * Test model for SEO meta attachment.
 */
class TestPost extends Model
{
	protected $table = 'test_posts';

	protected $fillable = [ 'title', 'excerpt', 'content' ];
}

/**
 * Test version of SeoMetaEditor that uses a simplified view for testing.
 */
class TestSeoMetaEditor extends SeoMetaEditor
{
	/**
	 * Render the component with a simplified test view.
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'test-seo-meta-editor' );
	}
}

beforeEach( function (): void {
	// Run migrations
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	// Create test_posts table
	$this->app['db']->connection()->getSchemaBuilder()->create( 'test_posts', function ( $table ): void {
		$table->id();
		$table->string( 'title' );
		$table->text( 'excerpt' )->nullable();
		$table->text( 'content' )->nullable();
		$table->timestamps();
	} );

	// Register the test view location
	$this->app['view']->addLocation( __DIR__ . '/../../stubs/views/livewire' );
} );

describe( 'SeoMetaEditor Component Mounting', function (): void {

	it( 'mounts with a model', function (): void {
		$post = TestPost::create( [
			'title'   => 'Test Post',
			'excerpt' => 'This is a test excerpt',
		] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->assertSet( 'metaTitle', 'Test Post' )
			->assertSet( 'metaDescription', 'This is a test excerpt' );
	} );

	it( 'loads existing seo meta data', function (): void {
		$post = TestPost::create( [
			'title'   => 'Test Post',
			'excerpt' => 'Test excerpt',
		] );

		SeoMeta::create( [
			'seoable_type'     => TestPost::class,
			'seoable_id'       => $post->id,
			'meta_title'       => 'Custom Meta Title',
			'meta_description' => 'Custom meta description',
			'focus_keyword'    => 'test keyword',
		] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->assertSet( 'metaTitle', 'Custom Meta Title' )
			->assertSet( 'metaDescription', 'Custom meta description' )
			->assertSet( 'focusKeyword', 'test keyword' );
	} );

	it( 'sets default active tab to basic', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->assertSet( 'activeTab', 'basic' );
	} );

	it( 'accepts custom initial tab', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post, 'activeTab' => 'social' ] )
			->assertSet( 'activeTab', 'social' );
	} );

} );

describe( 'SeoMetaEditor Character Counts', function (): void {

	it( 'calculates title character count', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		$component = Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', 'This is a test title' );

		expect( $component->get( 'titleCharCount' ) )->toBe( 20 );
	} );

	it( 'calculates description character count', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		$component = Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaDescription', 'This is a test description for the page.' );

		expect( $component->get( 'descriptionCharCount' ) )->toBe( 40 );
	} );

	it( 'calculates og title character count', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		$component = Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'ogTitle', 'Open Graph Title' );

		expect( $component->get( 'ogTitleCharCount' ) )->toBe( 16 );
	} );

	it( 'calculates twitter title character count', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		$component = Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'twitterTitle', 'Twitter Title' );

		expect( $component->get( 'twitterTitleCharCount' ) )->toBe( 13 );
	} );

} );

describe( 'SeoMetaEditor Preview Data', function (): void {

	it( 'returns meta title for preview when set', function (): void {
		$post = TestPost::create( [ 'title' => 'Post Title' ] );

		$component = Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', 'Custom Meta Title' );

		expect( $component->get( 'previewTitle' ) )->toBe( 'Custom Meta Title' );
	} );

	it( 'falls back to model title for preview', function (): void {
		$post = TestPost::create( [ 'title' => 'Post Title' ] );

		$component = Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', '' );

		expect( $component->get( 'previewTitle' ) )->toBe( 'Post Title' );
	} );

	it( 'returns meta description for preview when set', function (): void {
		$post = TestPost::create( [ 'title' => 'Post', 'excerpt' => 'Post excerpt' ] );

		$component = Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaDescription', 'Custom description' );

		expect( $component->get( 'previewDescription' ) )->toBe( 'Custom description' );
	} );

} );

describe( 'SeoMetaEditor Saving', function (): void {

	it( 'creates new seo meta record when saving', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', 'New Meta Title' )
			->set( 'metaDescription', 'New meta description' )
			->set( 'focusKeyword', 'test keyword' )
			->call( 'save' );

		$seoMeta = SeoMeta::where( 'seoable_type', TestPost::class )
			->where( 'seoable_id', $post->id )
			->first();

		expect( $seoMeta )->not->toBeNull();
		expect( $seoMeta->meta_title )->toBe( 'New Meta Title' );
		expect( $seoMeta->meta_description )->toBe( 'New meta description' );
		expect( $seoMeta->focus_keyword )->toBe( 'test keyword' );
	} );

	it( 'updates existing seo meta record when saving', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		$seoMeta = SeoMeta::create( [
			'seoable_type'     => TestPost::class,
			'seoable_id'       => $post->id,
			'meta_title'       => 'Old Title',
			'meta_description' => 'Old description',
		] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', 'Updated Title' )
			->set( 'metaDescription', 'Updated description' )
			->call( 'save' );

		$seoMeta->refresh();

		expect( $seoMeta->meta_title )->toBe( 'Updated Title' );
		expect( $seoMeta->meta_description )->toBe( 'Updated description' );
	} );

	it( 'parses secondary keywords from comma-separated string', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'secondaryKeywords', 'keyword1, keyword2, keyword3' )
			->call( 'save' );

		$seoMeta = SeoMeta::where( 'seoable_type', TestPost::class )
			->where( 'seoable_id', $post->id )
			->first();

		expect( $seoMeta->secondary_keywords )->toBe( [ 'keyword1', 'keyword2', 'keyword3' ] );
	} );

	it( 'saves robots settings', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'noIndex', true )
			->set( 'noFollow', true )
			->call( 'save' );

		$seoMeta = SeoMeta::where( 'seoable_type', TestPost::class )
			->where( 'seoable_id', $post->id )
			->first();

		expect( $seoMeta->no_index )->toBeTrue();
		expect( $seoMeta->no_follow )->toBeTrue();
	} );

	it( 'saves social media fields', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'ogTitle', 'OG Title' )
			->set( 'ogDescription', 'OG Description' )
			->set( 'ogType', 'article' )
			->set( 'twitterCard', 'summary_large_image' )
			->set( 'twitterTitle', 'Twitter Title' )
			->call( 'save' );

		$seoMeta = SeoMeta::where( 'seoable_type', TestPost::class )
			->where( 'seoable_id', $post->id )
			->first();

		expect( $seoMeta->og_title )->toBe( 'OG Title' );
		expect( $seoMeta->og_description )->toBe( 'OG Description' );
		expect( $seoMeta->og_type )->toBe( 'article' );
		expect( $seoMeta->twitter_card )->toBe( 'summary_large_image' );
		expect( $seoMeta->twitter_title )->toBe( 'Twitter Title' );
	} );

	it( 'saves sitemap settings', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'sitemapPriority', 0.8 )
			->set( 'sitemapChangefreq', 'daily' )
			->set( 'excludeFromSitemap', true )
			->call( 'save' );

		$seoMeta = SeoMeta::where( 'seoable_type', TestPost::class )
			->where( 'seoable_id', $post->id )
			->first();

		expect( (float) $seoMeta->sitemap_priority )->toBe( 0.8 );
		expect( $seoMeta->sitemap_changefreq )->toBe( 'daily' );
		expect( $seoMeta->exclude_from_sitemap )->toBeTrue();
	} );

	it( 'dispatches event after saving', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', 'Test Title' )
			->call( 'save' )
			->assertDispatched( 'seo-meta-saved' );
	} );

} );

describe( 'SeoMetaEditor Validation', function (): void {

	it( 'validates meta title max length', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', str_repeat( 'a', 65 ) )
			->call( 'save' )
			->assertHasErrors( [ 'metaTitle' => 'max' ] );
	} );

	it( 'validates meta description max length', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaDescription', str_repeat( 'a', 165 ) )
			->call( 'save' )
			->assertHasErrors( [ 'metaDescription' => 'max' ] );
	} );

	it( 'validates canonical url format', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'canonicalUrl', 'not-a-valid-url' )
			->call( 'save' )
			->assertHasErrors( [ 'canonicalUrl' => 'url' ] );
	} );

	it( 'validates twitter card type', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'twitterCard', 'invalid_type' )
			->call( 'save' )
			->assertHasErrors( [ 'twitterCard' => 'in' ] );
	} );

	it( 'validates sitemap priority range', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'sitemapPriority', 1.5 )
			->call( 'save' )
			->assertHasErrors( [ 'sitemapPriority' => 'max' ] );
	} );

	it( 'validates sitemap changefreq', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'sitemapChangefreq', 'invalid' )
			->call( 'save' )
			->assertHasErrors( [ 'sitemapChangefreq' => 'in' ] );
	} );

} );

describe( 'SeoMetaEditor Tab Navigation', function (): void {

	it( 'can change active tab', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->assertSet( 'activeTab', 'basic' )
			->call( 'setActiveTab', 'social' )
			->assertSet( 'activeTab', 'social' )
			->call( 'setActiveTab', 'schema' )
			->assertSet( 'activeTab', 'schema' )
			->call( 'setActiveTab', 'advanced' )
			->assertSet( 'activeTab', 'advanced' );
	} );

} );

describe( 'SeoMetaEditor SEO Analysis', function (): void {

	it( 'runs seo analysis', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', 'Great SEO Title for Testing' )
			->set( 'metaDescription', 'This is a well-crafted meta description that provides valuable information to search engines and users about the content of this page.' )
			->set( 'focusKeyword', 'SEO' )
			->call( 'runAnalysis' )
			->assertDispatched( 'seo-analysis-complete' );
	} );

	it( 'warns about empty meta title', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		$component = Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', '' )
			->call( 'runAnalysis' );

		$result = $component->get( 'analysisResult' );
		expect( $result['title']['status'] )->toBe( 'warning' );
	} );

	it( 'warns about short meta title', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		$component = Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', 'Short' )
			->call( 'runAnalysis' );

		$result = $component->get( 'analysisResult' );
		expect( $result['title']['status'] )->toBe( 'warning' );
	} );

	it( 'warns about long meta title', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		$component = Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', str_repeat( 'a', 65 ) )
			->call( 'runAnalysis' );

		$result = $component->get( 'analysisResult' );
		expect( $result['title']['status'] )->toBe( 'warning' );
	} );

	it( 'succeeds for optimal meta title length', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		$component = Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', 'This is an optimal length title for SEO purposes' )
			->call( 'runAnalysis' );

		$result = $component->get( 'analysisResult' );
		expect( $result['title']['status'] )->toBe( 'success' );
	} );

	it( 'checks for focus keyword in title', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		$component = Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', 'Title with SEO keyword included' )
			->set( 'focusKeyword', 'SEO' )
			->call( 'runAnalysis' );

		$result = $component->get( 'analysisResult' );
		expect( $result['keyword_in_title']['status'] )->toBe( 'success' );
	} );

	it( 'warns when focus keyword not in title', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		$component = Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', 'Title without the keyword' )
			->set( 'focusKeyword', 'SEO' )
			->call( 'runAnalysis' );

		$result = $component->get( 'analysisResult' );
		expect( $result['keyword_in_title']['status'] )->toBe( 'warning' );
	} );

} );

describe( 'SeoMetaEditor Copy Functions', function (): void {

	it( 'copies meta title to og title', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaTitle', 'My Meta Title' )
			->call( 'copyTitleToOg' )
			->assertSet( 'ogTitle', 'My Meta Title' );
	} );

	it( 'copies meta description to og description', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'metaDescription', 'My Meta Description' )
			->call( 'copyDescriptionToOg' )
			->assertSet( 'ogDescription', 'My Meta Description' );
	} );

	it( 'copies og data to twitter', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'ogTitle', 'OG Title' )
			->set( 'ogDescription', 'OG Description' )
			->set( 'ogImage', 'https://example.com/image.jpg' )
			->call( 'copyOgToTwitter' )
			->assertSet( 'twitterTitle', 'OG Title' )
			->assertSet( 'twitterDescription', 'OG Description' )
			->assertSet( 'twitterImage', 'https://example.com/image.jpg' );
	} );

} );

describe( 'SeoMetaEditor Image Handling', function (): void {

	it( 'clears og image', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'ogImage', 'https://example.com/image.jpg' )
			->set( 'ogImageId', 123 )
			->call( 'clearImage', 'og' )
			->assertSet( 'ogImage', '' )
			->assertSet( 'ogImageId', null );
	} );

	it( 'clears twitter image', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'twitterImage', 'https://example.com/image.jpg' )
			->set( 'twitterImageId', 456 )
			->call( 'clearImage', 'twitter' )
			->assertSet( 'twitterImage', '' )
			->assertSet( 'twitterImageId', null );
	} );

	it( 'handles media selection for og image', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->dispatch( 'media-selected', media: [
				[ 'id' => 123, 'url' => 'https://example.com/selected.jpg' ],
			], context: 'og-image' )
			->assertSet( 'ogImage', 'https://example.com/selected.jpg' )
			->assertSet( 'ogImageId', 123 );
	} );

	it( 'handles media selection for twitter image', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->dispatch( 'media-selected', media: [
				[ 'id' => 456, 'url' => 'https://example.com/twitter.jpg' ],
			], context: 'twitter-image' )
			->assertSet( 'twitterImage', 'https://example.com/twitter.jpg' )
			->assertSet( 'twitterImageId', 456 );
	} );

} );

describe( 'SeoMetaEditor Schema Handling', function (): void {

	it( 'saves schema type', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'schemaType', 'Article' )
			->call( 'save' );

		$seoMeta = SeoMeta::where( 'seoable_type', TestPost::class )
			->where( 'seoable_id', $post->id )
			->first();

		expect( $seoMeta->schema_type )->toBe( 'Article' );
	} );

	it( 'saves valid schema markup json', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		$schemaJson = json_encode( [
			'@context' => 'https://schema.org',
			'@type'    => 'Article',
			'headline' => 'Test Article',
		], JSON_PRETTY_PRINT );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'schemaMarkup', $schemaJson )
			->call( 'save' );

		$seoMeta = SeoMeta::where( 'seoable_type', TestPost::class )
			->where( 'seoable_id', $post->id )
			->first();

		expect( $seoMeta->schema_markup )->toBeArray();
		expect( $seoMeta->schema_markup['@type'] )->toBe( 'Article' );
	} );

	it( 'ignores invalid schema markup json', function (): void {
		$post = TestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestSeoMetaEditor::class, [ 'model' => $post ] )
			->set( 'schemaMarkup', 'not valid json {' )
			->call( 'save' );

		$seoMeta = SeoMeta::where( 'seoable_type', TestPost::class )
			->where( 'seoable_id', $post->id )
			->first();

		expect( $seoMeta->schema_markup )->toBeNull();
	} );

} );
