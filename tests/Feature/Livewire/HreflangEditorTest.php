<?php

/**
 * HreflangEditor Livewire Component Tests.
 *
 * Feature tests for the HreflangEditor Livewire component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Livewire\HreflangEditor;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use Livewire\Livewire;

uses( RefreshDatabase::class );

/**
 * Test model for hreflang attachment.
 */
class HreflangTestPost extends Model
{
	use HasSeo;

	protected $table = 'hreflang_test_posts';

	protected $fillable = [ 'title', 'content' ];
}

/**
 * Test version of HreflangEditor that uses a simplified view for testing.
 */
class TestHreflangEditor extends HreflangEditor
{
	/**
	 * Render the component with a simplified test view.
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'test-hreflang-editor' );
	}
}

beforeEach( function (): void {
	// Run migrations
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	// Create test_posts table
	$this->app['db']->connection()->getSchemaBuilder()->create( 'hreflang_test_posts', function ( $table ): void {
		$table->id();
		$table->string( 'title' );
		$table->text( 'content' )->nullable();
		$table->timestamps();
	} );

	// Register the test view location
	$this->app['view']->addLocation( __DIR__ . '/../../stubs/views/livewire' );

	// Configure hreflang
	config( [
		'seo.hreflang.enabled'            => true,
		'seo.hreflang.default_locale'     => 'en',
		'seo.hreflang.auto_add_x_default' => true,
		'seo.hreflang.supported_locales'  => [ 'en', 'es', 'fr', 'de' ],
	] );
} );

describe( 'HreflangEditor Component Mounting', function (): void {

	it( 'mounts with a model', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->assertSet( 'hreflangEntries', [] )
			->assertSee( '0 entries' );
	} );

	it( 'loads existing hreflang data from seo meta', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		SeoMeta::create( [
			'seoable_type' => HreflangTestPost::class,
			'seoable_id'   => $post->id,
			'hreflang'     => [
				'en' => 'https://example.com/en/page',
				'es' => 'https://example.com/es/page',
			],
		] );

		Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->assertSet( 'hreflangEntries', [
				[ 'locale' => 'en', 'url' => 'https://example.com/en/page' ],
				[ 'locale' => 'es', 'url' => 'https://example.com/es/page' ],
			] )
			->assertSee( '2 entries' );
	} );

} );

describe( 'HreflangEditor Adding Entries', function (): void {

	it( 'adds empty hreflang entry', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->call( 'addHreflang' )
			->assertSet( 'hreflangEntries', [
				[ 'locale' => '', 'url' => '' ],
			] )
			->assertSee( '1 entries' );
	} );

	it( 'adds multiple hreflang entries', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->call( 'addHreflang' )
			->call( 'addHreflang' )
			->call( 'addHreflang' )
			->assertCount( 'hreflangEntries', 3 );
	} );

	it( 'duplicates entry with base URL', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		SeoMeta::create( [
			'seoable_type' => HreflangTestPost::class,
			'seoable_id'   => $post->id,
			'hreflang'     => [
				'en' => 'https://example.com/en/page',
			],
		] );

		$component = Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->call( 'duplicateEntry' );

		$entries = $component->get( 'hreflangEntries' );

		expect( $entries )->toHaveCount( 2 )
			->and( $entries[1]['url'] )->toBe( 'https://example.com/en/page' )
			->and( $entries[1]['locale'] )->toBe( '' );
	} );

} );

describe( 'HreflangEditor Removing Entries', function (): void {

	it( 'removes hreflang entry by index', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		SeoMeta::create( [
			'seoable_type' => HreflangTestPost::class,
			'seoable_id'   => $post->id,
			'hreflang'     => [
				'en' => 'https://example.com/en/page',
				'es' => 'https://example.com/es/page',
				'fr' => 'https://example.com/fr/page',
			],
		] );

		Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->call( 'removeHreflang', 1 )
			->assertCount( 'hreflangEntries', 2 )
			->assertSet( 'hreflangEntries.0.locale', 'en' )
			->assertSet( 'hreflangEntries.1.locale', 'fr' );
	} );

	it( 'handles removing non-existent index', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->call( 'addHreflang' )
			->call( 'removeHreflang', 999 )
			->assertCount( 'hreflangEntries', 1 );
	} );

} );

describe( 'HreflangEditor Saving Data', function (): void {

	it( 'saves hreflang data to seo meta', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->call( 'addHreflang' )
			->set( 'hreflangEntries.0.locale', 'en' )
			->set( 'hreflangEntries.0.url', 'https://example.com/en/page' )
			->call( 'addHreflang' )
			->set( 'hreflangEntries.1.locale', 'es' )
			->set( 'hreflangEntries.1.url', 'https://example.com/es/page' )
			->call( 'save' )
			->assertDispatched( 'hreflang-saved' );

		$seoMeta = SeoMeta::where( 'seoable_type', HreflangTestPost::class )
			->where( 'seoable_id', $post->id )
			->first();

		expect( $seoMeta->hreflang )
			->toHaveKey( 'en' )
			->toHaveKey( 'es' )
			->and( $seoMeta->hreflang['en'] )->toBe( 'https://example.com/en/page' )
			->and( $seoMeta->hreflang['es'] )->toBe( 'https://example.com/es/page' );
	} );

	it( 'updates existing hreflang data', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		SeoMeta::create( [
			'seoable_type' => HreflangTestPost::class,
			'seoable_id'   => $post->id,
			'hreflang'     => [
				'en' => 'https://old.com/en/page',
			],
		] );

		Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->set( 'hreflangEntries.0.url', 'https://new.com/en/page' )
			->call( 'save' );

		$seoMeta = SeoMeta::where( 'seoable_type', HreflangTestPost::class )
			->where( 'seoable_id', $post->id )
			->first();

		expect( $seoMeta->hreflang['en'] )->toBe( 'https://new.com/en/page' );
	} );

	it( 'validates locale format', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		// Use a locale that passes max:10 but fails the regex pattern
		// The regex is: /^(x-default|[a-z]{2,3}(-[A-Z]{2})?)$/
		// "abcde" has more than 3 lowercase letters so it fails
		Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->call( 'addHreflang' )
			->set( 'hreflangEntries.0.locale', 'abcde' )
			->set( 'hreflangEntries.0.url', 'https://example.com/page' )
			->call( 'save' )
			->assertHasErrors( 'hreflangEntries.0.locale' );
	} );

	it( 'validates URL format', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->call( 'addHreflang' )
			->set( 'hreflangEntries.0.locale', 'en' )
			->set( 'hreflangEntries.0.url', 'not-a-url' )
			->call( 'save' )
			->assertHasErrors( 'hreflangEntries.0.url' );
	} );

	it( 'skips empty entries when saving', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->call( 'addHreflang' )
			->set( 'hreflangEntries.0.locale', 'en' )
			->set( 'hreflangEntries.0.url', 'https://example.com/en/page' )
			->call( 'addHreflang' )
			// Second entry left empty
			->call( 'save' );

		$seoMeta = SeoMeta::where( 'seoable_type', HreflangTestPost::class )
			->where( 'seoable_id', $post->id )
			->first();

		expect( $seoMeta->hreflang )->toHaveCount( 1 );
	} );

} );

describe( 'HreflangEditor Clearing Data', function (): void {

	it( 'clears all hreflang entries', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		SeoMeta::create( [
			'seoable_type' => HreflangTestPost::class,
			'seoable_id'   => $post->id,
			'hreflang'     => [
				'en' => 'https://example.com/en/page',
				'es' => 'https://example.com/es/page',
			],
		] );

		Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->call( 'clearAll' )
			->assertSet( 'hreflangEntries', [] )
			->assertDispatched( 'hreflang-cleared' );

		$seoMeta = SeoMeta::where( 'seoable_type', HreflangTestPost::class )
			->where( 'seoable_id', $post->id )
			->first();

		expect( $seoMeta->hreflang )->toBeNull();
	} );

} );

describe( 'HreflangEditor Computed Properties', function (): void {

	it( 'returns available locales', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		$component = Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] );

		$locales = $component->get( 'availableLocales' );

		// Should include x-default plus configured locales
		expect( $locales )->toBeArray();

		$values = array_column( $locales, 'value' );
		expect( $values )->toContain( 'x-default' )
			->toContain( 'en' )
			->toContain( 'es' )
			->toContain( 'fr' )
			->toContain( 'de' );
	} );

	it( 'returns default locale from config', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		$component = Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] );

		expect( $component->get( 'defaultLocale' ) )->toBe( 'en' );
	} );

	it( 'returns entry count', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		SeoMeta::create( [
			'seoable_type' => HreflangTestPost::class,
			'seoable_id'   => $post->id,
			'hreflang'     => [
				'en' => 'https://example.com/en/page',
				'es' => 'https://example.com/es/page',
				'fr' => 'https://example.com/fr/page',
			],
		] );

		$component = Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] );

		expect( $component->get( 'entryCount' ) )->toBe( 3 );
	} );

	it( 'returns enabled status from config', function (): void {
		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		config( [ 'seo.hreflang.enabled' => true ] );
		$component = Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] );
		expect( $component->get( 'isEnabled' ) )->toBeTrue();

		config( [ 'seo.hreflang.enabled' => false ] );
		$component = Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] );
		expect( $component->get( 'isEnabled' ) )->toBeFalse();
	} );

} );

describe( 'HreflangEditor Disabled State', function (): void {

	it( 'shows disabled notice when hreflang is disabled', function (): void {
		config( [ 'seo.hreflang.enabled' => false ] );

		$post = HreflangTestPost::create( [ 'title' => 'Test Post' ] );

		Livewire::test( TestHreflangEditor::class, [ 'model' => $post ] )
			->assertSee( 'Hreflang is disabled' );
	} );

} );
