<?php

/**
 * SocialPreview Livewire Component Tests.
 *
 * Feature tests for the SocialPreview Livewire component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Livewire\Partials\SocialPreview;
use Illuminate\View\View;
use Livewire\Livewire;

/**
 * Test version of SocialPreview that uses a simplified view for testing.
 */
class TestSocialPreview extends SocialPreview
{
	/**
	 * Render the component with a simplified test view.
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'partials.test-social-preview' );
	}
}

beforeEach( function (): void {
	// Register the test view location
	$this->app['view']->addLocation( __DIR__ . '/../../../stubs/views/livewire' );
} );

describe( 'SocialPreview Component Mounting', function (): void {

	it( 'mounts with default empty values', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSet( 'title', '' )
			->assertSet( 'description', '' )
			->assertSet( 'image', null )
			->assertSet( 'url', '' )
			->assertSet( 'platform', 'facebook' );
	} );

	it( 'mounts with provided title', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'title' => 'My Page Title' ] )
			->assertSet( 'title', 'My Page Title' )
			->assertSeeHtml( 'data-test="title">My Page Title' );
	} );

	it( 'mounts with provided description', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'description' => 'This is a description.' ] )
			->assertSet( 'description', 'This is a description.' )
			->assertSeeHtml( 'data-test="description">This is a description.' );
	} );

	it( 'mounts with provided image', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'image' => 'https://example.com/image.jpg' ] )
			->assertSet( 'image', 'https://example.com/image.jpg' )
			->assertSeeHtml( 'data-test="image">https://example.com/image.jpg' );
	} );

	it( 'mounts with provided url', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'url' => 'https://example.com/page' ] )
			->assertSet( 'url', 'https://example.com/page' )
			->assertSeeHtml( 'data-test="url">https://example.com/page' );
	} );

	it( 'mounts with all values', function (): void {
		Livewire::test( TestSocialPreview::class, [
			'title'       => 'My Page',
			'description' => 'Page description',
			'image'       => 'https://example.com/og.jpg',
			'url'         => 'https://example.com/my-page',
		] )
			->assertSet( 'title', 'My Page' )
			->assertSet( 'description', 'Page description' )
			->assertSet( 'image', 'https://example.com/og.jpg' )
			->assertSet( 'url', 'https://example.com/my-page' );
	} );

} );

describe( 'SocialPreview Platform Toggle', function (): void {

	it( 'defaults to facebook platform', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="platform">facebook' );
	} );

	it( 'can switch to twitter platform', function (): void {
		Livewire::test( TestSocialPreview::class )
			->call( 'setPlatform', 'twitter' )
			->assertSeeHtml( 'data-test="platform">twitter' );
	} );

	it( 'can switch back to facebook from twitter', function (): void {
		Livewire::test( TestSocialPreview::class )
			->call( 'setPlatform', 'twitter' )
			->assertSeeHtml( 'data-test="platform">twitter' )
			->call( 'setPlatform', 'facebook' )
			->assertSeeHtml( 'data-test="platform">facebook' );
	} );

	it( 'ignores invalid platform values', function (): void {
		Livewire::test( TestSocialPreview::class )
			->call( 'setPlatform', 'invalid' )
			->assertSeeHtml( 'data-test="platform">facebook' );
	} );

	it( 'can mount with twitter platform', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'platform' => 'twitter' ] )
			->assertSeeHtml( 'data-test="platform">twitter' );
	} );

} );

describe( 'SocialPreview Facebook Display Title', function (): void {

	it( 'returns provided title when not empty', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'title' => 'My Title' ] )
			->assertSeeHtml( 'data-test="facebook-display-title">My Title' );
	} );

	it( 'returns placeholder when title is empty', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="facebook-display-title">Page Title' );
	} );

	it( 'truncates title at 60 characters', function (): void {
		$longTitle = str_repeat( 'a', 70 );

		Livewire::test( TestSocialPreview::class, [ 'title' => $longTitle ] )
			->assertSeeHtml( 'data-test="is-facebook-title-truncated">true' );
	} );

	it( 'does not truncate title under 60 characters', function (): void {
		$shortTitle = str_repeat( 'a', 50 );

		Livewire::test( TestSocialPreview::class, [ 'title' => $shortTitle ] )
			->assertSeeHtml( 'data-test="is-facebook-title-truncated">false' );
	} );

	it( 'handles exactly 60 character title', function (): void {
		$exactTitle = str_repeat( 'a', 60 );

		Livewire::test( TestSocialPreview::class, [ 'title' => $exactTitle ] )
			->assertSeeHtml( 'data-test="is-facebook-title-truncated">false' )
			->assertSeeHtml( 'data-test="title-char-count">60' );
	} );

} );

describe( 'SocialPreview Facebook Display Description', function (): void {

	it( 'returns provided description when not empty', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'description' => 'My description' ] )
			->assertSeeHtml( 'data-test="facebook-display-description">My description' );
	} );

	it( 'returns placeholder when description is empty', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="facebook-display-description">Add a description' );
	} );

	it( 'truncates description at 155 characters', function (): void {
		$longDescription = str_repeat( 'b', 170 );

		Livewire::test( TestSocialPreview::class, [ 'description' => $longDescription ] )
			->assertSeeHtml( 'data-test="is-facebook-description-truncated">true' );
	} );

	it( 'does not truncate description under 155 characters', function (): void {
		$shortDescription = str_repeat( 'b', 140 );

		Livewire::test( TestSocialPreview::class, [ 'description' => $shortDescription ] )
			->assertSeeHtml( 'data-test="is-facebook-description-truncated">false' );
	} );

	it( 'handles exactly 155 character description', function (): void {
		$exactDescription = str_repeat( 'b', 155 );

		Livewire::test( TestSocialPreview::class, [ 'description' => $exactDescription ] )
			->assertSeeHtml( 'data-test="is-facebook-description-truncated">false' )
			->assertSeeHtml( 'data-test="description-char-count">155' );
	} );

} );

describe( 'SocialPreview Twitter Display Title', function (): void {

	it( 'returns provided title when not empty', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'title' => 'My Title' ] )
			->assertSeeHtml( 'data-test="twitter-display-title">My Title' );
	} );

	it( 'returns placeholder when title is empty', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="twitter-display-title">Page Title' );
	} );

	it( 'truncates title at 70 characters', function (): void {
		$longTitle = str_repeat( 'a', 80 );

		Livewire::test( TestSocialPreview::class, [ 'title' => $longTitle ] )
			->assertSeeHtml( 'data-test="is-twitter-title-truncated">true' );
	} );

	it( 'does not truncate title under 70 characters', function (): void {
		$shortTitle = str_repeat( 'a', 60 );

		Livewire::test( TestSocialPreview::class, [ 'title' => $shortTitle ] )
			->assertSeeHtml( 'data-test="is-twitter-title-truncated">false' );
	} );

	it( 'handles exactly 70 character title', function (): void {
		$exactTitle = str_repeat( 'a', 70 );

		Livewire::test( TestSocialPreview::class, [ 'title' => $exactTitle ] )
			->assertSeeHtml( 'data-test="is-twitter-title-truncated">false' )
			->assertSeeHtml( 'data-test="title-char-count">70' );
	} );

} );

describe( 'SocialPreview Twitter Display Description', function (): void {

	it( 'returns provided description when not empty', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'description' => 'My description' ] )
			->assertSeeHtml( 'data-test="twitter-display-description">My description' );
	} );

	it( 'returns placeholder when description is empty', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="twitter-display-description">Add a description' );
	} );

	it( 'truncates description at 200 characters', function (): void {
		$longDescription = str_repeat( 'c', 220 );

		Livewire::test( TestSocialPreview::class, [ 'description' => $longDescription ] )
			->assertSeeHtml( 'data-test="is-twitter-description-truncated">true' );
	} );

	it( 'does not truncate description under 200 characters', function (): void {
		$shortDescription = str_repeat( 'c', 180 );

		Livewire::test( TestSocialPreview::class, [ 'description' => $shortDescription ] )
			->assertSeeHtml( 'data-test="is-twitter-description-truncated">false' );
	} );

	it( 'handles exactly 200 character description', function (): void {
		$exactDescription = str_repeat( 'c', 200 );

		Livewire::test( TestSocialPreview::class, [ 'description' => $exactDescription ] )
			->assertSeeHtml( 'data-test="is-twitter-description-truncated">false' )
			->assertSeeHtml( 'data-test="description-char-count">200' );
	} );

} );

describe( 'SocialPreview Display Domain', function (): void {

	it( 'extracts domain from url', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'url' => 'https://example.com/page/path' ] )
			->assertSeeHtml( 'data-test="display-domain">example.com' );
	} );

	it( 'returns app url domain when url is empty', function (): void {
		config( [ 'app.url' => 'https://mysite.com' ] );

		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="display-domain">mysite.com' );
	} );

	it( 'returns fallback domain when both url and app.url are empty', function (): void {
		config( [ 'app.url' => null ] );

		$component = Livewire::test( TestSocialPreview::class );

		expect( $component->instance()->displayDomain )->toBe( 'example.com' );
	} );

	it( 'handles complex url with port', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'url' => 'https://example.com:8080/page' ] )
			->assertSeeHtml( 'data-test="display-domain">example.com' );
	} );

	it( 'handles subdomain in url', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'url' => 'https://www.sub.example.com/page' ] )
			->assertSeeHtml( 'data-test="display-domain">www.sub.example.com' );
	} );

} );

describe( 'SocialPreview Display URL', function (): void {

	it( 'returns provided url when not empty', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'url' => 'https://example.com/page' ] )
			->assertSeeHtml( 'data-test="display-url">https://example.com/page' );
	} );

	it( 'returns app url when url is empty', function (): void {
		config( [ 'app.url' => 'https://mysite.com' ] );

		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="display-url">https://mysite.com' );
	} );

	it( 'returns fallback when both url and app.url are empty', function (): void {
		config( [ 'app.url' => null ] );

		$component = Livewire::test( TestSocialPreview::class );

		expect( $component->instance()->displayUrl )->toBe( 'https://example.com' );
	} );

} );

describe( 'SocialPreview Image Handling', function (): void {

	it( 'detects when image is set', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'image' => 'https://example.com/og.jpg' ] )
			->assertSeeHtml( 'data-test="has-image">true' );
	} );

	it( 'detects when image is not set', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="has-image">false' );
	} );

	it( 'detects when image is empty string', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'image' => '' ] )
			->assertSeeHtml( 'data-test="has-image">false' );
	} );

	it( 'handles various image url formats', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'image' => '/storage/images/og.png' ] )
			->assertSeeHtml( 'data-test="has-image">true' )
			->assertSeeHtml( 'data-test="image">/storage/images/og.png' );
	} );

} );

describe( 'SocialPreview Character Counts', function (): void {

	it( 'returns correct title character count', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'title' => 'Hello World' ] )
			->assertSeeHtml( 'data-test="title-char-count">11' );
	} );

	it( 'returns zero for empty title', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="title-char-count">0' );
	} );

	it( 'returns correct description character count', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'description' => 'A longer description text' ] )
			->assertSeeHtml( 'data-test="description-char-count">25' );
	} );

	it( 'returns zero for empty description', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="description-char-count">0' );
	} );

} );

describe( 'SocialPreview Truncation Indicators', function (): void {

	it( 'indicates facebook title is not truncated for empty title', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="is-facebook-title-truncated">false' );
	} );

	it( 'indicates facebook description is not truncated for empty description', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="is-facebook-description-truncated">false' );
	} );

	it( 'indicates twitter title is not truncated for empty title', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="is-twitter-title-truncated">false' );
	} );

	it( 'indicates twitter description is not truncated for empty description', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="is-twitter-description-truncated">false' );
	} );

	it( 'indicates facebook title is truncated for long title', function (): void {
		$longTitle = str_repeat( 'x', 65 );

		Livewire::test( TestSocialPreview::class, [ 'title' => $longTitle ] )
			->assertSeeHtml( 'data-test="is-facebook-title-truncated">true' );
	} );

	it( 'indicates twitter title is not truncated when under twitter limit but over facebook', function (): void {
		// 65 chars: over facebook 60, under twitter 70
		$title = str_repeat( 'x', 65 );

		Livewire::test( TestSocialPreview::class, [ 'title' => $title ] )
			->assertSeeHtml( 'data-test="is-facebook-title-truncated">true' )
			->assertSeeHtml( 'data-test="is-twitter-title-truncated">false' );
	} );

} );

describe( 'SocialPreview Constants', function (): void {

	it( 'has max facebook title length of 60', function (): void {
		expect( SocialPreview::MAX_FACEBOOK_TITLE_LENGTH )->toBe( 60 );
	} );

	it( 'has max facebook description length of 155', function (): void {
		expect( SocialPreview::MAX_FACEBOOK_DESCRIPTION_LENGTH )->toBe( 155 );
	} );

	it( 'has max twitter title length of 70', function (): void {
		expect( SocialPreview::MAX_TWITTER_TITLE_LENGTH )->toBe( 70 );
	} );

	it( 'has max twitter description length of 200', function (): void {
		expect( SocialPreview::MAX_TWITTER_DESCRIPTION_LENGTH )->toBe( 200 );
	} );

	it( 'displays constants in test view', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="max-facebook-title-length">60' )
			->assertSeeHtml( 'data-test="max-facebook-description-length">155' )
			->assertSeeHtml( 'data-test="max-twitter-title-length">70' )
			->assertSeeHtml( 'data-test="max-twitter-description-length">200' );
	} );

} );

describe( 'SocialPreview Live Updates', function (): void {

	it( 'updates display when title changes', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="title-char-count">0' )
			->set( 'title', 'New Title' )
			->assertSeeHtml( 'data-test="title">New Title' )
			->assertSeeHtml( 'data-test="title-char-count">9' );
	} );

	it( 'updates display when description changes', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="description-char-count">0' )
			->set( 'description', 'New Description' )
			->assertSeeHtml( 'data-test="description">New Description' )
			->assertSeeHtml( 'data-test="description-char-count">15' );
	} );

	it( 'updates display when image changes', function (): void {
		Livewire::test( TestSocialPreview::class )
			->assertSeeHtml( 'data-test="has-image">false' )
			->set( 'image', 'https://example.com/new-image.jpg' )
			->assertSeeHtml( 'data-test="has-image">true' )
			->assertSeeHtml( 'data-test="image">https://example.com/new-image.jpg' );
	} );

	it( 'updates display when url changes', function (): void {
		Livewire::test( TestSocialPreview::class )
			->set( 'url', 'https://newsite.com/page' )
			->assertSeeHtml( 'data-test="url">https://newsite.com/page' )
			->assertSeeHtml( 'data-test="display-domain">newsite.com' );
	} );

	it( 'updates truncation indicator when title crosses threshold', function (): void {
		$shortTitle = str_repeat( 'a', 50 );
		$longTitle  = str_repeat( 'a', 70 );

		Livewire::test( TestSocialPreview::class, [ 'title' => $shortTitle ] )
			->assertSeeHtml( 'data-test="is-facebook-title-truncated">false' )
			->set( 'title', $longTitle )
			->assertSeeHtml( 'data-test="is-facebook-title-truncated">true' );
	} );

} );

describe( 'SocialPreview Edge Cases', function (): void {

	it( 'handles whitespace-only title', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'title' => '   ' ] )
			->assertSeeHtml( 'data-test="title-char-count">3' );
	} );

	it( 'handles whitespace-only description', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'description' => '   ' ] )
			->assertSeeHtml( 'data-test="description-char-count">3' );
	} );

	it( 'handles special characters in title', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'title' => 'Test & Title <with> "special" chars' ] )
			->assertSeeHtml( 'data-test="title">Test &amp; Title &lt;with&gt; &quot;special&quot; chars' );
	} );

	it( 'handles very long url', function (): void {
		$longUrl = 'https://example.com/' . str_repeat( 'a', 200 );

		Livewire::test( TestSocialPreview::class, [ 'url' => $longUrl ] )
			->assertSet( 'url', $longUrl );
	} );

	it( 'handles url without protocol', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'url' => 'example.com/page' ] )
			->assertSeeHtml( 'data-test="url">example.com/page' );
	} );

	it( 'handles null image when updating to null', function (): void {
		Livewire::test( TestSocialPreview::class, [ 'image' => 'https://example.com/image.jpg' ] )
			->assertSeeHtml( 'data-test="has-image">true' )
			->set( 'image', null )
			->assertSeeHtml( 'data-test="has-image">false' );
	} );

} );

describe( 'SocialPreview Realistic Usage', function (): void {

	it( 'displays typical facebook share preview correctly', function (): void {
		Livewire::test( TestSocialPreview::class, [
			'title'       => 'Best Practices for SEO in 2024',
			'description' => 'Learn the most effective SEO strategies for improving your website ranking.',
			'image'       => 'https://example.com/og-image.jpg',
			'url'         => 'https://example.com/blog/seo-best-practices',
			'platform'    => 'facebook',
		] )
			->assertSeeHtml( 'data-test="facebook-display-title">Best Practices for SEO in 2024' )
			->assertSeeHtml( 'data-test="is-facebook-title-truncated">false' )
			->assertSeeHtml( 'data-test="is-facebook-description-truncated">false' )
			->assertSeeHtml( 'data-test="has-image">true' );
	} );

	it( 'displays typical twitter card preview correctly', function (): void {
		Livewire::test( TestSocialPreview::class, [
			'title'       => 'Best Practices for SEO in 2024',
			'description' => 'Learn the most effective SEO strategies for improving your website ranking.',
			'image'       => 'https://example.com/twitter-card.jpg',
			'url'         => 'https://example.com/blog/seo-best-practices',
			'platform'    => 'twitter',
		] )
			->assertSeeHtml( 'data-test="twitter-display-title">Best Practices for SEO in 2024' )
			->assertSeeHtml( 'data-test="is-twitter-title-truncated">false' )
			->assertSeeHtml( 'data-test="is-twitter-description-truncated">false' )
			->assertSeeHtml( 'data-test="has-image">true' );
	} );

	it( 'handles title at facebook limit but under twitter limit', function (): void {
		// 65 chars: over facebook 60, under twitter 70
		$title = str_repeat( 'a', 65 );

		Livewire::test( TestSocialPreview::class, [ 'title' => $title ] )
			->assertSeeHtml( 'data-test="is-facebook-title-truncated">true' )
			->assertSeeHtml( 'data-test="is-twitter-title-truncated">false' )
			->assertSeeHtml( 'data-test="title-char-count">65' );
	} );

	it( 'handles description at facebook limit but under twitter limit', function (): void {
		// 180 chars: over facebook 155, under twitter 200
		$description = str_repeat( 'x', 180 );

		Livewire::test( TestSocialPreview::class, [ 'description' => $description ] )
			->assertSeeHtml( 'data-test="is-facebook-description-truncated">true' )
			->assertSeeHtml( 'data-test="is-twitter-description-truncated">false' )
			->assertSeeHtml( 'data-test="description-char-count">180' );
	} );

} );
