<?php

/**
 * SocialPreview Livewire Component.
 *
 * Displays social media sharing preview showing how content will appear on Facebook/Twitter.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 * @copyright  2026 Jacob Martella
 * @license    MIT
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Livewire\Partials;

use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * SocialPreview component for displaying social media sharing preview.
 *
 * This composable sub-component is used within SeoMetaEditor on the Social tab
 * to show users how their content will appear when shared on Facebook or Twitter.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SocialPreview extends Component
{
	/**
	 * Maximum title length for Facebook.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public const MAX_FACEBOOK_TITLE_LENGTH = 60;

	/**
	 * Maximum description length for Facebook.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public const MAX_FACEBOOK_DESCRIPTION_LENGTH = 155;

	/**
	 * Maximum title length for Twitter.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public const MAX_TWITTER_TITLE_LENGTH = 70;

	/**
	 * Maximum description length for Twitter.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public const MAX_TWITTER_DESCRIPTION_LENGTH = 200;

	/**
	 * The page title to display.
	 *
	 * @since 1.0.0
	 */
	public string $title = '';

	/**
	 * The meta description to display.
	 *
	 * @since 1.0.0
	 */
	public string $description = '';

	/**
	 * The OG image URL to display.
	 *
	 * @since 1.0.0
	 */
	public ?string $image = null;

	/**
	 * The page URL to display.
	 *
	 * @since 1.0.0
	 */
	public string $url = '';

	/**
	 * The currently selected platform (facebook or twitter).
	 *
	 * @since 1.0.0
	 */
	public string $platform = 'facebook';

	/**
	 * Set the active preview platform.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $platform  The platform to switch to (facebook or twitter).
	 *
	 * @return void
	 */
	public function setPlatform( string $platform ): void
	{
		if ( in_array( $platform, [ 'facebook', 'twitter' ], true ) ) {
			$this->platform = $platform;
		}
	}

	/**
	 * Get the truncated title for Facebook display.
	 *
	 * Title is truncated at 60 characters with ellipsis.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	#[Computed]
	public function facebookDisplayTitle(): string
	{
		$title = $this->title;

		if ( '' === $title ) {
			$title = __( 'Page Title' );
		}

		return Str::limit( $title, self::MAX_FACEBOOK_TITLE_LENGTH );
	}

	/**
	 * Get the truncated description for Facebook display.
	 *
	 * Description is truncated at 155 characters with ellipsis.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	#[Computed]
	public function facebookDisplayDescription(): string
	{
		$description = $this->description;

		if ( '' === $description ) {
			$description = __( 'Add a description to see it here...' );
		}

		return Str::limit( $description, self::MAX_FACEBOOK_DESCRIPTION_LENGTH );
	}

	/**
	 * Get the truncated title for Twitter display.
	 *
	 * Title is truncated at 70 characters with ellipsis.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	#[Computed]
	public function twitterDisplayTitle(): string
	{
		$title = $this->title;

		if ( '' === $title ) {
			$title = __( 'Page Title' );
		}

		return Str::limit( $title, self::MAX_TWITTER_TITLE_LENGTH );
	}

	/**
	 * Get the truncated description for Twitter display.
	 *
	 * Description is truncated at 200 characters with ellipsis.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	#[Computed]
	public function twitterDisplayDescription(): string
	{
		$description = $this->description;

		if ( '' === $description ) {
			$description = __( 'Add a description to see it here...' );
		}

		return Str::limit( $description, self::MAX_TWITTER_DESCRIPTION_LENGTH );
	}

	/**
	 * Get the display URL showing the domain.
	 *
	 * Returns the host portion of the URL or a placeholder if empty.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	#[Computed]
	public function displayDomain(): string
	{
		if ( '' === $this->url ) {
			$appUrl = config( 'app.url' );
			$url    = is_string( $appUrl ) && '' !== $appUrl ? $appUrl : 'https://example.com';
		} else {
			$url = $this->url;
		}

		$host = parse_url( $url, PHP_URL_HOST );

		return is_string( $host ) ? $host : 'example.com';
	}

	/**
	 * Get the full display URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	#[Computed]
	public function displayUrl(): string
	{
		if ( '' === $this->url ) {
			$appUrl = config( 'app.url' );

			return is_string( $appUrl ) && '' !== $appUrl ? $appUrl : 'https://example.com';
		}

		return $this->url;
	}

	/**
	 * Check if an image is set.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function hasImage(): bool
	{
		return null !== $this->image && '' !== $this->image;
	}

	/**
	 * Check if the title exceeds the Facebook maximum length.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function isFacebookTitleTruncated(): bool
	{
		return '' !== $this->title && mb_strlen( $this->title ) > self::MAX_FACEBOOK_TITLE_LENGTH;
	}

	/**
	 * Check if the description exceeds the Facebook maximum length.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function isFacebookDescriptionTruncated(): bool
	{
		return '' !== $this->description && mb_strlen( $this->description ) > self::MAX_FACEBOOK_DESCRIPTION_LENGTH;
	}

	/**
	 * Check if the title exceeds the Twitter maximum length.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function isTwitterTitleTruncated(): bool
	{
		return '' !== $this->title && mb_strlen( $this->title ) > self::MAX_TWITTER_TITLE_LENGTH;
	}

	/**
	 * Check if the description exceeds the Twitter maximum length.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function isTwitterDescriptionTruncated(): bool
	{
		return '' !== $this->description && mb_strlen( $this->description ) > self::MAX_TWITTER_DESCRIPTION_LENGTH;
	}

	/**
	 * Get the title character count.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function titleCharCount(): int
	{
		return mb_strlen( $this->title );
	}

	/**
	 * Get the description character count.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function descriptionCharCount(): int
	{
		return mb_strlen( $this->description );
	}

	/**
	 * Render the component.
	 *
	 * @since 1.0.0
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'seo::livewire.partials.social-preview' );
	}
}
