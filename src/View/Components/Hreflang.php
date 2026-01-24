<?php

/**
 * Hreflang Blade Component.
 *
 * Renders hreflang link tags for multi-language SEO support.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\View\Components;

use ArtisanPackUI\SEO\Services\HreflangService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

/**
 * Hreflang component class.
 *
 * Outputs hreflang link tags for alternate language/region versions of a page.
 * Automatically handles x-default based on configuration.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class Hreflang extends Component
{
	/**
	 * The hreflang tag data.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, array{hreflang: string, href: string}>
	 */
	public array $hreflangTags;

	/**
	 * Whether to include x-default.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	public bool $includeXDefault;

	/**
	 * Create a new component instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|null                       $model           The model to get hreflang data from.
	 * @param  array<string, string>|null       $urls            Manual hreflang URLs (locale => url pairs).
	 * @param  string|null                      $defaultUrl      URL for x-default.
	 * @param  bool                             $includeXDefault Whether to include x-default automatically.
	 */
	public function __construct(
		?Model $model = null,
		?array $urls = null,
		?string $defaultUrl = null,
		bool $includeXDefault = true,
	) {
		$this->includeXDefault = $includeXDefault;
		$hreflangService       = app( HreflangService::class );

		// Check if hreflang is enabled
		if ( ! $hreflangService->isEnabled() ) {
			$this->hreflangTags = [];

			return;
		}

		if ( null !== $model ) {
			// Get hreflang data from model
			$this->hreflangTags = $hreflangService->getHreflangTags( $model );
		} elseif ( null !== $urls ) {
			// Build from manual URLs
			$this->hreflangTags = $this->buildFromManualUrls( $urls, $defaultUrl, $hreflangService );
		} else {
			$this->hreflangTags = [];
		}
	}

	/**
	 * Get the view that represents the component.
	 *
	 * @since 1.0.0
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'seo::components.hreflang' );
	}

	/**
	 * Determine if the component should be rendered.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function shouldRender(): bool
	{
		return ! empty( $this->hreflangTags );
	}

	/**
	 * Build hreflang tags from manual URL data.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, string>  $urls            The locale => URL pairs.
	 * @param  string|null            $defaultUrl      The x-default URL.
	 * @param  HreflangService        $hreflangService The hreflang service.
	 *
	 * @return array<int, array{hreflang: string, href: string}>
	 */
	protected function buildFromManualUrls( array $urls, ?string $defaultUrl, HreflangService $hreflangService ): array
	{
		$tags          = [];
		$hasXDefault   = false;
		$defaultLocale = $hreflangService->getDefaultLocale();

		foreach ( $urls as $locale => $url ) {
			if ( ! $hreflangService->validateLocale( $locale ) ) {
				continue;
			}

			if ( 'x-default' === $locale ) {
				$hasXDefault = true;
			}

			$tags[] = [
				'hreflang' => $locale,
				'href'     => $url,
			];
		}

		// Add x-default if requested and not already present
		if ( $this->includeXDefault && ! $hasXDefault && config( 'seo.hreflang.auto_add_x_default', true ) ) {
			if ( null !== $defaultUrl ) {
				$tags[] = [
					'hreflang' => 'x-default',
					'href'     => $defaultUrl,
				];
			} elseif ( null !== $defaultLocale && isset( $urls[ $defaultLocale ] ) ) {
				$tags[] = [
					'hreflang' => 'x-default',
					'href'     => $urls[ $defaultLocale ],
				];
			}
		}

		return $tags;
	}
}
