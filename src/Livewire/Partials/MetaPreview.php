<?php

/**
 * MetaPreview Livewire Component.
 *
 * Displays a Google SERP preview showing how content will appear in search results.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
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
 * MetaPreview component for displaying Google SERP preview.
 *
 * This composable sub-component is used within SeoMetaEditor on the Basic SEO tab
 * to show users how their content will appear in Google search results.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class MetaPreview extends Component
{
	/**
	 * Maximum title length before truncation.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public const MAX_TITLE_LENGTH = 60;

	/**
	 * Maximum description length before truncation.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public const MAX_DESCRIPTION_LENGTH = 160;

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
	 * The page URL to display.
	 *
	 * @since 1.0.0
	 */
	public string $url = '';

	/**
	 * Get the truncated title for display.
	 *
	 * Title is truncated at 60 characters with ellipsis.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	#[Computed]
	public function displayTitle(): string
	{
		$title = $this->title;

		if ( '' === $title ) {
			$title = __( 'Page Title' );
		}

		return Str::limit( $title, self::MAX_TITLE_LENGTH );
	}

	/**
	 * Get the truncated description for display.
	 *
	 * Description is truncated at 160 characters with ellipsis.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	#[Computed]
	public function displayDescription(): string
	{
		$description = $this->description;

		if ( '' === $description ) {
			$description = __( 'Add a meta description to see it here...' );
		}

		return Str::limit( $description, self::MAX_DESCRIPTION_LENGTH );
	}

	/**
	 * Get the display URL.
	 *
	 * Returns the URL or a placeholder if empty.
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
	 * Check if the title exceeds the maximum length.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function isTitleTruncated(): bool
	{
		return '' !== $this->title && mb_strlen( $this->title ) > self::MAX_TITLE_LENGTH;
	}

	/**
	 * Check if the description exceeds the maximum length.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function isDescriptionTruncated(): bool
	{
		return '' !== $this->description && mb_strlen( $this->description ) > self::MAX_DESCRIPTION_LENGTH;
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
		return view( 'seo::livewire.partials.meta-preview' );
	}
}
