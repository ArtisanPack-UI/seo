<?php

/**
 * MetaPreview Livewire Component Tests.
 *
 * Feature tests for the MetaPreview Livewire component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Livewire\Partials\MetaPreview;
use Illuminate\View\View;
use Livewire\Livewire;

/**
 * Test version of MetaPreview that uses a simplified view for testing.
 */
class TestMetaPreview extends MetaPreview
{
	/**
	 * Render the component with a simplified test view.
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'partials.test-meta-preview' );
	}
}

beforeEach( function (): void {
	// Register the test view location
	$this->app['view']->addLocation( __DIR__ . '/../../../stubs/views/livewire' );
} );

describe( 'MetaPreview Component Mounting', function (): void {

	it( 'mounts with default empty values', function (): void {
		Livewire::test( TestMetaPreview::class )
			->assertSet( 'title', '' )
			->assertSet( 'description', '' )
			->assertSet( 'url', '' );
	} );

	it( 'mounts with provided title', function (): void {
		Livewire::test( TestMetaPreview::class, [ 'title' => 'My Page Title' ] )
			->assertSet( 'title', 'My Page Title' )
			->assertSeeHtml( 'data-test="title">My Page Title' );
	} );

	it( 'mounts with provided description', function (): void {
		Livewire::test( TestMetaPreview::class, [ 'description' => 'This is a description.' ] )
			->assertSet( 'description', 'This is a description.' )
			->assertSeeHtml( 'data-test="description">This is a description.' );
	} );

	it( 'mounts with provided url', function (): void {
		Livewire::test( TestMetaPreview::class, [ 'url' => 'https://example.com/page' ] )
			->assertSet( 'url', 'https://example.com/page' )
			->assertSeeHtml( 'data-test="url">https://example.com/page' );
	} );

	it( 'mounts with all values', function (): void {
		Livewire::test( TestMetaPreview::class, [
			'title'       => 'My Page',
			'description' => 'Page description',
			'url'         => 'https://example.com/my-page',
		] )
			->assertSet( 'title', 'My Page' )
			->assertSet( 'description', 'Page description' )
			->assertSet( 'url', 'https://example.com/my-page' );
	} );

} );

describe( 'MetaPreview Display Title', function (): void {

	it( 'returns provided title when not empty', function (): void {
		Livewire::test( TestMetaPreview::class, [ 'title' => 'My Title' ] )
			->assertSeeHtml( 'data-test="display-title">My Title' );
	} );

	it( 'returns placeholder when title is empty', function (): void {
		Livewire::test( TestMetaPreview::class )
			->assertSeeHtml( 'data-test="display-title">Page Title' );
	} );

	it( 'truncates title at 60 characters', function (): void {
		$longTitle = str_repeat( 'a', 70 );

		Livewire::test( TestMetaPreview::class, [ 'title' => $longTitle ] )
			->assertSeeHtml( 'data-test="is-title-truncated">true' );
	} );

	it( 'does not truncate title under 60 characters', function (): void {
		$shortTitle = str_repeat( 'a', 50 );

		Livewire::test( TestMetaPreview::class, [ 'title' => $shortTitle ] )
			->assertSeeHtml( 'data-test="is-title-truncated">false' );
	} );

	it( 'handles exactly 60 character title', function (): void {
		$exactTitle = str_repeat( 'a', 60 );

		Livewire::test( TestMetaPreview::class, [ 'title' => $exactTitle ] )
			->assertSeeHtml( 'data-test="is-title-truncated">false' )
			->assertSeeHtml( 'data-test="title-char-count">60' );
	} );

	it( 'adds ellipsis to truncated title', function (): void {
		$longTitle = str_repeat( 'a', 70 );

		$component = Livewire::test( TestMetaPreview::class, [ 'title' => $longTitle ] );

		// The display title should be 60 chars + "..." (63 total)
		$displayTitle = $component->instance()->displayTitle;
		expect( $displayTitle )->toEndWith( '...' );
		expect( strlen( $displayTitle ) )->toBe( 63 );
	} );

} );

describe( 'MetaPreview Display Description', function (): void {

	it( 'returns provided description when not empty', function (): void {
		Livewire::test( TestMetaPreview::class, [ 'description' => 'My description' ] )
			->assertSeeHtml( 'data-test="display-description">My description' );
	} );

	it( 'returns placeholder when description is empty', function (): void {
		Livewire::test( TestMetaPreview::class )
			->assertSeeHtml( 'data-test="display-description">Add a meta description' );
	} );

	it( 'truncates description at 160 characters', function (): void {
		$longDescription = str_repeat( 'b', 180 );

		Livewire::test( TestMetaPreview::class, [ 'description' => $longDescription ] )
			->assertSeeHtml( 'data-test="is-description-truncated">true' );
	} );

	it( 'does not truncate description under 160 characters', function (): void {
		$shortDescription = str_repeat( 'b', 140 );

		Livewire::test( TestMetaPreview::class, [ 'description' => $shortDescription ] )
			->assertSeeHtml( 'data-test="is-description-truncated">false' );
	} );

	it( 'handles exactly 160 character description', function (): void {
		$exactDescription = str_repeat( 'b', 160 );

		Livewire::test( TestMetaPreview::class, [ 'description' => $exactDescription ] )
			->assertSeeHtml( 'data-test="is-description-truncated">false' )
			->assertSeeHtml( 'data-test="description-char-count">160' );
	} );

	it( 'adds ellipsis to truncated description', function (): void {
		$longDescription = str_repeat( 'b', 180 );

		$component = Livewire::test( TestMetaPreview::class, [ 'description' => $longDescription ] );

		$displayDescription = $component->instance()->displayDescription;
		expect( $displayDescription )->toEndWith( '...' );
		expect( strlen( $displayDescription ) )->toBe( 163 );
	} );

} );

describe( 'MetaPreview Display URL', function (): void {

	it( 'returns provided url when not empty', function (): void {
		Livewire::test( TestMetaPreview::class, [ 'url' => 'https://example.com/page' ] )
			->assertSeeHtml( 'data-test="display-url">https://example.com/page' );
	} );

	it( 'returns app url when url is empty', function (): void {
		config( [ 'app.url' => 'https://mysite.com' ] );

		Livewire::test( TestMetaPreview::class )
			->assertSeeHtml( 'data-test="display-url">https://mysite.com' );
	} );

	it( 'returns fallback when both url and app.url are empty', function (): void {
		config( [ 'app.url' => null ] );

		$component = Livewire::test( TestMetaPreview::class );

		// Should fallback to something reasonable
		expect( $component->instance()->displayUrl )->not->toBe( '' );
	} );

} );

describe( 'MetaPreview Character Counts', function (): void {

	it( 'returns correct title character count', function (): void {
		Livewire::test( TestMetaPreview::class, [ 'title' => 'Hello World' ] )
			->assertSeeHtml( 'data-test="title-char-count">11' );
	} );

	it( 'returns zero for empty title', function (): void {
		Livewire::test( TestMetaPreview::class )
			->assertSeeHtml( 'data-test="title-char-count">0' );
	} );

	it( 'returns correct description character count', function (): void {
		Livewire::test( TestMetaPreview::class, [ 'description' => 'A longer description text' ] )
			->assertSeeHtml( 'data-test="description-char-count">25' );
	} );

	it( 'returns zero for empty description', function (): void {
		Livewire::test( TestMetaPreview::class )
			->assertSeeHtml( 'data-test="description-char-count">0' );
	} );

	it( 'handles unicode characters correctly', function (): void {
		// 5 unicode characters
		Livewire::test( TestMetaPreview::class, [ 'title' => 'Hello' ] )
			->assertSeeHtml( 'data-test="title-char-count">5' );
	} );

} );

describe( 'MetaPreview Truncation Indicators', function (): void {

	it( 'indicates title is not truncated for empty title', function (): void {
		Livewire::test( TestMetaPreview::class )
			->assertSeeHtml( 'data-test="is-title-truncated">false' );
	} );

	it( 'indicates description is not truncated for empty description', function (): void {
		Livewire::test( TestMetaPreview::class )
			->assertSeeHtml( 'data-test="is-description-truncated">false' );
	} );

	it( 'indicates title is truncated for long title', function (): void {
		$longTitle = str_repeat( 'x', 65 );

		Livewire::test( TestMetaPreview::class, [ 'title' => $longTitle ] )
			->assertSeeHtml( 'data-test="is-title-truncated">true' );
	} );

	it( 'indicates description is truncated for long description', function (): void {
		$longDescription = str_repeat( 'y', 165 );

		Livewire::test( TestMetaPreview::class, [ 'description' => $longDescription ] )
			->assertSeeHtml( 'data-test="is-description-truncated">true' );
	} );

} );

describe( 'MetaPreview Constants', function (): void {

	it( 'has max title length of 60', function (): void {
		expect( MetaPreview::MAX_TITLE_LENGTH )->toBe( 60 );
	} );

	it( 'has max description length of 160', function (): void {
		expect( MetaPreview::MAX_DESCRIPTION_LENGTH )->toBe( 160 );
	} );

	it( 'displays constants in test view', function (): void {
		Livewire::test( TestMetaPreview::class )
			->assertSeeHtml( 'data-test="max-title-length">60' )
			->assertSeeHtml( 'data-test="max-description-length">160' );
	} );

} );

describe( 'MetaPreview Live Updates', function (): void {

	it( 'updates display when title changes', function (): void {
		Livewire::test( TestMetaPreview::class )
			->assertSeeHtml( 'data-test="title-char-count">0' )
			->set( 'title', 'New Title' )
			->assertSeeHtml( 'data-test="title">New Title' )
			->assertSeeHtml( 'data-test="title-char-count">9' );
	} );

	it( 'updates display when description changes', function (): void {
		Livewire::test( TestMetaPreview::class )
			->assertSeeHtml( 'data-test="description-char-count">0' )
			->set( 'description', 'New Description' )
			->assertSeeHtml( 'data-test="description">New Description' )
			->assertSeeHtml( 'data-test="description-char-count">15' );
	} );

	it( 'updates display when url changes', function (): void {
		Livewire::test( TestMetaPreview::class )
			->set( 'url', 'https://newsite.com/page' )
			->assertSeeHtml( 'data-test="url">https://newsite.com/page' )
			->assertSeeHtml( 'data-test="display-url">https://newsite.com/page' );
	} );

	it( 'updates truncation indicator when title crosses threshold', function (): void {
		$shortTitle = str_repeat( 'a', 50 );
		$longTitle  = str_repeat( 'a', 70 );

		Livewire::test( TestMetaPreview::class, [ 'title' => $shortTitle ] )
			->assertSeeHtml( 'data-test="is-title-truncated">false' )
			->set( 'title', $longTitle )
			->assertSeeHtml( 'data-test="is-title-truncated">true' );
	} );

} );

describe( 'MetaPreview Edge Cases', function (): void {

	it( 'handles whitespace-only title', function (): void {
		Livewire::test( TestMetaPreview::class, [ 'title' => '   ' ] )
			->assertSeeHtml( 'data-test="title-char-count">3' );
	} );

	it( 'handles whitespace-only description', function (): void {
		Livewire::test( TestMetaPreview::class, [ 'description' => '   ' ] )
			->assertSeeHtml( 'data-test="description-char-count">3' );
	} );

	it( 'handles special characters in title', function (): void {
		Livewire::test( TestMetaPreview::class, [ 'title' => 'Test & Title <with> "special" chars' ] )
			->assertSeeHtml( 'data-test="title">Test &amp; Title &lt;with&gt; &quot;special&quot; chars' );
	} );

	it( 'handles very long url', function (): void {
		$longUrl = 'https://example.com/' . str_repeat( 'a', 200 );

		Livewire::test( TestMetaPreview::class, [ 'url' => $longUrl ] )
			->assertSet( 'url', $longUrl );
	} );

	it( 'handles url without protocol', function (): void {
		Livewire::test( TestMetaPreview::class, [ 'url' => 'example.com/page' ] )
			->assertSeeHtml( 'data-test="url">example.com/page' );
	} );

} );

describe( 'MetaPreview Realistic Usage', function (): void {

	it( 'displays typical SERP preview correctly', function (): void {
		Livewire::test( TestMetaPreview::class, [
			'title'       => 'Best Practices for SEO in 2024',
			'description' => 'Learn the most effective SEO strategies for improving your website ranking in search engines. Discover tips from industry experts.',
			'url'         => 'https://example.com/blog/seo-best-practices',
		] )
			->assertSeeHtml( 'data-test="display-title">Best Practices for SEO in 2024' )
			->assertSeeHtml( 'data-test="is-title-truncated">false' )
			->assertSeeHtml( 'data-test="is-description-truncated">false' );
	} );

	it( 'handles title at recommended length', function (): void {
		$title = 'This is an optimal title for SEO that is fifty-five'; // 51 chars

		Livewire::test( TestMetaPreview::class, [ 'title' => $title ] )
			->assertSeeHtml( 'data-test="is-title-truncated">false' )
			->assertSeeHtml( 'data-test="title-char-count">51' );
	} );

	it( 'handles description at recommended length', function (): void {
		$description = str_repeat( 'x', 155 ); // 155 chars - within recommended

		Livewire::test( TestMetaPreview::class, [ 'description' => $description ] )
			->assertSeeHtml( 'data-test="is-description-truncated">false' )
			->assertSeeHtml( 'data-test="description-char-count">155' );
	} );

} );
