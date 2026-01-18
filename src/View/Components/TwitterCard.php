<?php
/**
 * TwitterCard Blade Component.
 *
 * Renders Twitter Card meta tags for social sharing.
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

namespace ArtisanPackUI\SEO\View\Components;

use ArtisanPackUI\SEO\Services\SocialMetaService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

/**
 * TwitterCard component class.
 *
 * Outputs Twitter Card meta tags for rich sharing on Twitter/X.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class TwitterCard extends Component
{
	/**
	 * The Twitter card type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $card;

	/**
	 * The Twitter title.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	public ?string $twitterTitle;

	/**
	 * The Twitter description.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	public ?string $twitterDescription;

	/**
	 * The Twitter image URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	public ?string $twitterImage;

	/**
	 * The Twitter site handle.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	public ?string $site;

	/**
	 * The Twitter creator handle.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	public ?string $creator;

	/**
	 * Create a new component instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|null   $model        The model to get SEO data from.
	 * @param  string|null  $card         Override card type.
	 * @param  string|null  $title        Override Twitter title.
	 * @param  string|null  $description  Override Twitter description.
	 * @param  string|null  $image        Override Twitter image.
	 * @param  string|null  $site         Override Twitter site handle.
	 * @param  string|null  $creator      Override Twitter creator handle.
	 */
	public function __construct(
		?Model $model = null,
		?string $card = null,
		?string $title = null,
		?string $description = null,
		?string $image = null,
		?string $site = null,
		?string $creator = null,
	) {
		$socialMetaService = app( SocialMetaService::class );

		if ( null !== $model ) {
			// Get SEO meta from model if it has the seoMeta relationship
			$seoMeta = method_exists( $model, 'seoMeta' ) ? $model->seoMeta : null;
			$dto     = $socialMetaService->generateTwitterCard( $model, $seoMeta );

			$this->card               = $card ?? $dto->card;
			$this->twitterTitle       = $title ?? $dto->title;
			$this->twitterDescription = $description ?? $dto->description;
			$this->twitterImage       = $image ?? $dto->image;
			$this->site               = $site ?? $dto->site;
			$this->creator            = $creator ?? $dto->creator;
		} else {
			$this->card               = $card ?? config( 'seo.twitter.card_type', 'summary_large_image' );
			$this->twitterTitle       = $title ?? config( 'app.name', 'Laravel' );
			$this->twitterDescription = $description;
			$this->twitterImage       = $image ?? config( 'seo.twitter.default_image' );
			$this->site               = $site ?? config( 'seo.twitter.site' );
			$this->creator            = $creator ?? config( 'seo.twitter.creator' );
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
		return view( 'seo::components.twitter-card' );
	}
}
