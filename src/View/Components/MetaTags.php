<?php

/**
 * MetaTags Blade Component.
 *
 * Renders basic meta tags (title, description, canonical, robots).
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

use ArtisanPackUI\SEO\Services\MetaTagService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;
use RuntimeException;

/**
 * MetaTags component class.
 *
 * Outputs basic HTML meta tags including title, description,
 * canonical URL, and robots directives.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class MetaTags extends Component
{
	/**
	 * The page title.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $title;

	/**
	 * The meta description.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	public ?string $description;

	/**
	 * The canonical URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	public ?string $canonical;

	/**
	 * The robots directive.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $robots;

	/**
	 * Additional meta tags.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, string>
	 */
	public array $additionalMeta;

	/**
	 * Create a new component instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|null   $model        The model to get SEO data from.
	 * @param  string|null  $title        Override title.
	 * @param  string|null  $description  Override description.
	 * @param  string|null  $canonical    Override canonical URL.
	 * @param  string|null  $robots       Override robots directive.
	 */
	public function __construct(
		?Model $model = null,
		?string $title = null,
		?string $description = null,
		?string $canonical = null,
		?string $robots = null,
	) {
		$metaTagService = app( MetaTagService::class );

		if ( null !== $model ) {
			// Get SEO meta from model if it has the seoMeta relationship
			$seoMeta = method_exists( $model, 'seoMeta' ) ? $model->seoMeta : null;
			$dto     = $metaTagService->generate( $model, $seoMeta );

			$this->title          = $title ?? $dto->title;
			$this->description    = $description ?? $dto->description;
			$this->canonical      = $canonical ?? $dto->canonical;
			$this->robots         = $robots ?? $dto->robots;
			$this->additionalMeta = $dto->additionalMeta;
		} else {
			$this->title          = $title ?? $metaTagService->buildTitle( config( 'app.name', 'Laravel' ) );
			$this->description    = $description ?? config( 'seo.site.description' );
			$this->canonical      = $canonical ?? $this->getCurrentUrl();
			$this->robots         = $robots ?? config( 'seo.defaults.robots', 'index, follow' );
			$this->additionalMeta = [];
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
		return view( 'seo::components.meta-tags' );
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
