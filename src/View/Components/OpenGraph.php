<?php

/**
 * OpenGraph Blade Component.
 *
 * Renders Open Graph meta tags for social sharing.
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

use ArtisanPackUI\SEO\Services\SocialMetaService;
use ArtisanPackUI\SEO\Support\SeoMetaResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;
use RuntimeException;

/**
 * OpenGraph component class.
 *
 * Outputs Open Graph meta tags for social media sharing on
 * Facebook, LinkedIn, and other platforms.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class OpenGraph extends Component
{
	/**
	 * The OG title.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $ogTitle;

	/**
	 * The OG description.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	public ?string $ogDescription;

	/**
	 * The OG image URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	public ?string $ogImage;

	/**
	 * The OG URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	public ?string $ogUrl;

	/**
	 * The OG type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $ogType;

	/**
	 * The OG site name.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	public ?string $ogSiteName;

	/**
	 * The OG locale.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	public ?string $ogLocale;

	/**
	 * The OG image width in pixels.
	 *
	 * @since 1.9.0
	 *
	 * @var int|null
	 */
	public ?int $ogImageWidth;

	/**
	 * The OG image height in pixels.
	 *
	 * @since 1.9.0
	 *
	 * @var int|null
	 */
	public ?int $ogImageHeight;

	/**
	 * The OG image alt text.
	 *
	 * @since 1.9.0
	 *
	 * @var string|null
	 */
	public ?string $ogImageAlt;

	/**
	 * The article published time, for the `article` type.
	 *
	 * @since 1.9.0
	 *
	 * @var string|null
	 */
	public ?string $articlePublishedTime = null;

	/**
	 * The article modified time, for the `article` type.
	 *
	 * @since 1.9.0
	 *
	 * @var string|null
	 */
	public ?string $articleModifiedTime = null;

	/**
	 * Create a new component instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|null   $model        The model to get SEO data from.
	 * @param  string|null  $title        Override OG title.
	 * @param  string|null  $description  Override OG description.
	 * @param  string|null  $image        Override OG image.
	 * @param  string|null  $url          Override OG URL.
	 * @param  string|null  $type         Override OG type.
	 * @param  string|null  $siteName     Override OG site name.
	 * @param  string|null  $locale       Override OG locale.
	 */
	public function __construct(
		?Model $model = null,
		?string $title = null,
		?string $description = null,
		?string $image = null,
		?string $url = null,
		?string $type = null,
		?string $siteName = null,
		?string $locale = null,
	) {
		$socialMetaService = app( SocialMetaService::class );

		if ( null !== $model ) {
			// Get SEO meta from model if it has the seoMeta relationship
			$seoMeta = SeoMetaResolver::resolve( $model );
			$dto     = $socialMetaService->generateOpenGraph( $model, $seoMeta );

			$this->ogTitle       = $title ?? $dto->title;
			$this->ogDescription = $description ?? $dto->description;
			$this->ogImage       = $image ?? $dto->image;
			$this->ogUrl         = $url ?? $dto->url;
			$this->ogType        = $type ?? $dto->type;
			$this->ogSiteName    = $siteName ?? $dto->siteName;
			$this->ogLocale      = $locale ?? $dto->locale;

			if ( null === $image ) {
				$this->ogImageWidth  = $dto->imageWidth;
				$this->ogImageHeight = $dto->imageHeight;
				$this->ogImageAlt    = $dto->imageAlt;
			}

			if ( 'article' === $this->ogType ) {
				$times = $socialMetaService->resolveArticleTimes( $model );

				$this->articlePublishedTime = $times['published'] ?? null;
				$this->articleModifiedTime  = $times['modified'] ?? null;
			}
		} else {
			$this->ogTitle       = $title ?? config( 'app.name', 'Laravel' );
			$this->ogDescription = $description;
			$this->ogImage       = $image ?? config( 'seo.open_graph.default_image' );
			$this->ogUrl         = $url ?? $this->getCurrentUrl();
			$this->ogType        = $type ?? config( 'seo.open_graph.type', 'website' );
			$this->ogSiteName    = $siteName ?? config( 'seo.open_graph.site_name', config( 'app.name' ) );
			$this->ogLocale      = $locale ?? config( 'seo.open_graph.locale', 'en_US' );
		}

		if ( null === $model || null !== $image ) {
			$details = $socialMetaService->resolveImageDetails( $this->ogImage, null, $this->ogTitle );

			$this->ogImageWidth  = $details['width'];
			$this->ogImageHeight = $details['height'];
			$this->ogImageAlt    = $details['alt'];
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
		return view( 'seo::components.open-graph' );
	}

	/**
	 * Get the current URL safely.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	protected function getCurrentUrl(): ?string
	{
		try {
			return url()->current();
		} catch ( RuntimeException $e ) {
			return null;
		}
	}
}
