<?php

/**
 * Meta Blade Component.
 *
 * All-in-one component for complete SEO meta tag output.
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

use ArtisanPackUI\SEO\Services\SeoService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;
use RuntimeException;

/**
 * Meta component class.
 *
 * All-in-one SEO component that outputs basic meta tags,
 * Open Graph tags, Twitter Card tags, and hreflang links.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class Meta extends Component
{
	/**
	 * Meta tags data.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, mixed>
	 */
	public array $meta;

	/**
	 * Open Graph data.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, mixed>
	 */
	public array $openGraph;

	/**
	 * Twitter Card data.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, mixed>
	 */
	public array $twitterCard;

	/**
	 * Hreflang links.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, array{hreflang: string, href: string}>
	 */
	public array $hreflang;

	/**
	 * Whether to include Open Graph tags.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	public bool $includeOpenGraph;

	/**
	 * Whether to include Twitter Card tags.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	public bool $includeTwitterCard;

	/**
	 * Whether to include hreflang links.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	public bool $includeHreflang;

	/**
	 * Create a new component instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|null   $model               The model to get SEO data from.
	 * @param  string|null  $title               Override title.
	 * @param  string|null  $description         Override description.
	 * @param  string|null  $image               Override OG/Twitter image.
	 * @param  string|null  $canonical           Override canonical URL.
	 * @param  bool         $includeOpenGraph    Include Open Graph tags.
	 * @param  bool         $includeTwitterCard  Include Twitter Card tags.
	 * @param  bool         $includeHreflang     Include hreflang links.
	 */
	public function __construct(
		?Model $model = null,
		?string $title = null,
		?string $description = null,
		?string $image = null,
		?string $canonical = null,
		bool $includeOpenGraph = true,
		bool $includeTwitterCard = true,
		bool $includeHreflang = true,
	) {
		$this->includeOpenGraph    = $includeOpenGraph;
		$this->includeTwitterCard  = $includeTwitterCard;
		$this->includeHreflang     = $includeHreflang;

		$seoService = app( SeoService::class );

		if ( null !== $model ) {
			$this->buildFromModel( $seoService, $model, $title, $description, $image, $canonical );
		} else {
			$this->buildFromDefaults( $seoService, $title, $description, $image, $canonical );
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
		return view( 'seo::components.meta' );
	}

	/**
	 * Build SEO data from a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoService   $seoService   The SEO service.
	 * @param  Model        $model        The model.
	 * @param  string|null  $title        Override title.
	 * @param  string|null  $description  Override description.
	 * @param  string|null  $image        Override image.
	 * @param  string|null  $canonical    Override canonical URL.
	 *
	 * @return void
	 */
	protected function buildFromModel(
		SeoService $seoService,
		Model $model,
		?string $title,
		?string $description,
		?string $image,
		?string $canonical,
	): void {
		$metaDto        = $seoService->getMetaTags( $model );
		$openGraphDto   = $seoService->getOpenGraph( $model );
		$twitterCardDto = $seoService->getTwitterCard( $model );

		$this->meta = [
			'title'          => $title ?? $metaDto->title,
			'description'    => $description ?? $metaDto->description,
			'canonical'      => $canonical ?? $metaDto->canonical,
			'robots'         => $metaDto->robots,
			'additionalMeta' => $metaDto->additionalMeta,
		];

		$this->openGraph = [
			'title'       => $title ?? $openGraphDto->title,
			'description' => $description ?? $openGraphDto->description,
			'image'       => $image ?? $openGraphDto->image,
			'url'         => $canonical ?? $openGraphDto->url,
			'type'        => $openGraphDto->type,
			'siteName'    => $openGraphDto->siteName,
			'locale'      => $openGraphDto->locale,
		];

		$this->twitterCard = [
			'card'        => $twitterCardDto->card,
			'title'       => $title ?? $twitterCardDto->title,
			'description' => $description ?? $twitterCardDto->description,
			'image'       => $image ?? $twitterCardDto->image,
			'site'        => $twitterCardDto->site,
			'creator'     => $twitterCardDto->creator,
		];

		$this->hreflang = $seoService->getHreflang( $model );
	}

	/**
	 * Build SEO data from defaults.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoService   $seoService   The SEO service.
	 * @param  string|null  $title        Override title.
	 * @param  string|null  $description  Override description.
	 * @param  string|null  $image        Override image.
	 * @param  string|null  $canonical    Override canonical URL.
	 *
	 * @return void
	 */
	protected function buildFromDefaults(
		SeoService $seoService,
		?string $title,
		?string $description,
		?string $image,
		?string $canonical,
	): void {
		$currentUrl    = $this->getCurrentUrl();
		$resolvedTitle = $title
			? $seoService->buildTitle( $title )
			: $seoService->buildTitle( config( 'app.name', 'Laravel' ) );

		$this->meta = [
			'title'          => $resolvedTitle,
			'description'    => $description ?? config( 'seo.site.description' ),
			'canonical'      => $canonical ?? $currentUrl,
			'robots'         => config( 'seo.defaults.robots', 'index, follow' ),
			'additionalMeta' => [],
		];

		$siteName     = config( 'seo.open_graph.site_name' ) ?? config( 'app.name', 'Laravel' );
		$defaultImage = $image ?? config( 'seo.open_graph.default_image' );

		$this->openGraph = [
			'title'       => $title ?? config( 'app.name', 'Laravel' ),
			'description' => $description,
			'image'       => $defaultImage,
			'url'         => $canonical ?? $currentUrl,
			'type'        => config( 'seo.open_graph.type', 'website' ),
			'siteName'    => $siteName,
			'locale'      => config( 'seo.open_graph.locale', 'en_US' ),
		];

		$this->twitterCard = [
			'card'        => config( 'seo.twitter.card_type', 'summary_large_image' ),
			'title'       => $title ?? config( 'app.name', 'Laravel' ),
			'description' => $description,
			'image'       => $image ?? config( 'seo.twitter.default_image' ),
			'site'        => config( 'seo.twitter.site' ),
			'creator'     => config( 'seo.twitter.creator' ),
		];

		$this->hreflang = [];
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
