<?php

/**
 * SeoPreviewResource.
 *
 * API Resource for serializing formatted SEO previews.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Http\Resources;

use ArtisanPackUI\SEO\DTOs\MetaTagsDTO;
use ArtisanPackUI\SEO\DTOs\OpenGraphDTO;
use ArtisanPackUI\SEO\DTOs\TwitterCardDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * SeoPreviewResource class.
 *
 * Provides formatted preview data for search engine results (Google snippet)
 * and social media cards (Open Graph and Twitter Card).
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class SeoPreviewResource extends JsonResource
{
	/**
	 * Maximum title length for Google snippet display.
	 *
	 * @var int
	 */
	protected const GOOGLE_TITLE_MAX = 60;

	/**
	 * Maximum description length for Google snippet display.
	 *
	 * @var int
	 */
	protected const GOOGLE_DESCRIPTION_MAX = 160;

	/**
	 * The meta tags DTO.
	 *
	 * @var MetaTagsDTO
	 */
	protected MetaTagsDTO $metaTags;

	/**
	 * The Open Graph DTO.
	 *
	 * @var OpenGraphDTO
	 */
	protected OpenGraphDTO $openGraph;

	/**
	 * The Twitter Card DTO.
	 *
	 * @var TwitterCardDTO
	 */
	protected TwitterCardDTO $twitterCard;

	/**
	 * The hreflang tags.
	 *
	 * @var array<int, array{hreflang: string, href: string}>
	 */
	protected array $hreflang;

	/**
	 * Create a new SeoPreviewResource instance.
	 *
	 * @since 1.1.0
	 *
	 * @param  MetaTagsDTO                                       $metaTags     The meta tags DTO.
	 * @param  OpenGraphDTO                                      $openGraph    The Open Graph DTO.
	 * @param  TwitterCardDTO                                    $twitterCard  The Twitter Card DTO.
	 * @param  array<int, array{hreflang: string, href: string}> $hreflang     The hreflang tags.
	 */
	public function __construct(
		MetaTagsDTO $metaTags,
		OpenGraphDTO $openGraph,
		TwitterCardDTO $twitterCard,
		array $hreflang = [],
	) {
		parent::__construct( null );

		$this->metaTags    = $metaTags;
		$this->openGraph   = $openGraph;
		$this->twitterCard = $twitterCard;
		$this->hreflang    = $hreflang;
	}

	/**
	 * Transform the resource into an array.
	 *
	 * @since 1.1.0
	 *
	 * @param  Request  $request  The incoming request.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray( Request $request ): array
	{
		return [
			'search'  => $this->buildSearchPreview(),
			'social'  => [
				'open_graph'   => $this->buildOpenGraphPreview(),
				'twitter_card' => $this->buildTwitterCardPreview(),
			],
			'meta'     => ( new MetaTagsResource( $this->metaTags ) )->toArray( $request ),
			'hreflang' => HreflangResource::collection( $this->hreflang )->toArray( $request ),
		];
	}

	/**
	 * Build the Google search snippet preview.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, mixed>
	 */
	protected function buildSearchPreview(): array
	{
		$title       = $this->metaTags->title;
		$description = $this->metaTags->description ?? '';

		return [
			'title'              => $title,
			'title_truncated'    => mb_strlen( $title ) > self::GOOGLE_TITLE_MAX
				? mb_substr( $title, 0, self::GOOGLE_TITLE_MAX - 3 ) . '...'
				: $title,
			'title_length'             => mb_strlen( $title ),
			'title_is_truncated'       => mb_strlen( $title ) > self::GOOGLE_TITLE_MAX,
			'description'              => $description,
			'description_truncated'    => mb_strlen( $description ) > self::GOOGLE_DESCRIPTION_MAX
				? mb_substr( $description, 0, self::GOOGLE_DESCRIPTION_MAX - 3 ) . '...'
				: $description,
			'description_length'       => mb_strlen( $description ),
			'description_is_truncated' => mb_strlen( $description ) > self::GOOGLE_DESCRIPTION_MAX,
			'url'                      => $this->metaTags->canonical,
		];
	}

	/**
	 * Build the Open Graph social card preview.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, mixed>
	 */
	protected function buildOpenGraphPreview(): array
	{
		return [
			'title'       => $this->openGraph->title,
			'description' => $this->openGraph->description,
			'image'       => $this->openGraph->image,
			'url'         => $this->openGraph->url,
			'site_name'   => $this->openGraph->siteName,
			'type'        => $this->openGraph->type,
		];
	}

	/**
	 * Build the Twitter Card preview.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, mixed>
	 */
	protected function buildTwitterCardPreview(): array
	{
		return [
			'card'        => $this->twitterCard->card,
			'title'       => $this->twitterCard->title,
			'description' => $this->twitterCard->description,
			'image'       => $this->twitterCard->image,
			'site'        => $this->twitterCard->site,
			'creator'     => $this->twitterCard->creator,
		];
	}
}
