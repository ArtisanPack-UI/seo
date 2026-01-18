<?php

/**
 * SeoMetaEditor Livewire Component.
 *
 * Main Livewire component for editing SEO metadata in admin interfaces.
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

namespace ArtisanPackUI\SEO\Livewire;

use ArtisanPackUI\SEO\Models\SeoMeta;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * SeoMetaEditor component for managing SEO metadata.
 *
 * Provides a tabbed interface for editing all SEO-related fields
 * including basic meta tags, social sharing, schema markup, and advanced settings.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SeoMetaEditor extends Component
{

	/**
	 * Available Open Graph types.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, array{value: string, label: string}>
	 */
	public const OG_TYPES = [
		[ 'value' => 'website', 'label' => 'Website' ],
		[ 'value' => 'article', 'label' => 'Article' ],
		[ 'value' => 'book', 'label' => 'Book' ],
		[ 'value' => 'profile', 'label' => 'Profile' ],
		[ 'value' => 'music.song', 'label' => 'Music Song' ],
		[ 'value' => 'music.album', 'label' => 'Music Album' ],
		[ 'value' => 'video.movie', 'label' => 'Video Movie' ],
		[ 'value' => 'video.episode', 'label' => 'Video Episode' ],
		[ 'value' => 'product', 'label' => 'Product' ],
	];

	/**
	 * Available Twitter Card types.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, array{value: string, label: string}>
	 */
	public const TWITTER_CARD_TYPES = [
		[ 'value' => 'summary', 'label' => 'Summary' ],
		[ 'value' => 'summary_large_image', 'label' => 'Summary Large Image' ],
		[ 'value' => 'app', 'label' => 'App' ],
		[ 'value' => 'player', 'label' => 'Player' ],
	];

	/**
	 * Available sitemap change frequencies.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, array{value: string, label: string}>
	 */
	public const CHANGEFREQ_OPTIONS = [
		[ 'value' => 'always', 'label' => 'Always' ],
		[ 'value' => 'hourly', 'label' => 'Hourly' ],
		[ 'value' => 'daily', 'label' => 'Daily' ],
		[ 'value' => 'weekly', 'label' => 'Weekly' ],
		[ 'value' => 'monthly', 'label' => 'Monthly' ],
		[ 'value' => 'yearly', 'label' => 'Yearly' ],
		[ 'value' => 'never', 'label' => 'Never' ],
	];

	/**
	 * Available schema types.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, array{value: string, label: string}>
	 */
	public const SCHEMA_TYPES = [
		[ 'value' => '', 'label' => 'None' ],
		[ 'value' => 'Article', 'label' => 'Article' ],
		[ 'value' => 'BlogPosting', 'label' => 'Blog Posting' ],
		[ 'value' => 'Product', 'label' => 'Product' ],
		[ 'value' => 'Service', 'label' => 'Service' ],
		[ 'value' => 'LocalBusiness', 'label' => 'Local Business' ],
		[ 'value' => 'Organization', 'label' => 'Organization' ],
		[ 'value' => 'Event', 'label' => 'Event' ],
		[ 'value' => 'FAQPage', 'label' => 'FAQ Page' ],
		[ 'value' => 'WebPage', 'label' => 'Web Page' ],
	];

	/**
	 * The model to edit SEO meta for.
	 *
	 * @since 1.0.0
	 */
	public Model $model;

	/**
	 * The associated SeoMeta record.
	 *
	 * @since 1.0.0
	 */
	public ?SeoMeta $seoMeta = null;

	/**
	 * The currently active tab.
	 *
	 * @since 1.0.0
	 */
	public string $activeTab = 'basic';

	// Basic SEO fields
	public string $metaTitle = '';

	public string $metaDescription = '';

	public string $canonicalUrl = '';

	public string $focusKeyword = '';

	public string $secondaryKeywords = '';

	// Robots settings
	public bool $noIndex = false;

	public bool $noFollow = false;

	public string $robotsMeta = '';

	// Open Graph fields
	public string $ogTitle = '';

	public string $ogDescription = '';

	public string $ogImage = '';

	public ?int $ogImageId = null;

	public string $ogType = 'website';

	public string $ogLocale = '';

	public string $ogSiteName = '';

	// Twitter Card fields
	public string $twitterCard = 'summary_large_image';

	public string $twitterTitle = '';

	public string $twitterDescription = '';

	public string $twitterImage = '';

	public ?int $twitterImageId = null;

	public string $twitterSite = '';

	public string $twitterCreator = '';

	// Pinterest fields
	public string $pinterestDescription = '';

	public string $pinterestImage = '';

	public ?int $pinterestImageId = null;

	// Slack fields
	public string $slackTitle = '';

	public string $slackDescription = '';

	public string $slackImage = '';

	public ?int $slackImageId = null;

	// Schema fields
	public string $schemaType = '';

	public string $schemaMarkup = '';

	// Sitemap fields
	public float $sitemapPriority = 0.5;

	public string $sitemapChangefreq = 'weekly';

	public bool $excludeFromSitemap = false;

	// Hreflang
	public string $hreflang = '';

	// Analysis results
	public array $analysisResult = [];

	/**
	 * Whether the form is currently saving.
	 *
	 * @since 1.0.0
	 */
	public bool $isSaving = false;

	/**
	 * Validation rules.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, array<int, string>>
	 */
	protected array $rules = [
		'metaTitle'            => [ 'nullable', 'string', 'max:60' ],
		'metaDescription'      => [ 'nullable', 'string', 'max:160' ],
		'canonicalUrl'         => [ 'nullable', 'url', 'max:2000' ],
		'focusKeyword'         => [ 'nullable', 'string', 'max:100' ],
		'secondaryKeywords'    => [ 'nullable', 'string', 'max:500' ],
		'noIndex'              => [ 'boolean' ],
		'noFollow'             => [ 'boolean' ],
		'robotsMeta'           => [ 'nullable', 'string', 'max:255' ],
		'ogTitle'              => [ 'nullable', 'string', 'max:95' ],
		'ogDescription'        => [ 'nullable', 'string', 'max:200' ],
		'ogImage'              => [ 'nullable', 'string', 'max:2000' ],
		'ogImageId'            => [ 'nullable', 'integer' ],
		'ogType'               => [ 'nullable', 'string', 'max:50' ],
		'ogLocale'             => [ 'nullable', 'string', 'max:10' ],
		'ogSiteName'           => [ 'nullable', 'string', 'max:100' ],
		'twitterCard'          => [ 'nullable', 'string', 'in:summary,summary_large_image,app,player' ],
		'twitterTitle'         => [ 'nullable', 'string', 'max:70' ],
		'twitterDescription'   => [ 'nullable', 'string', 'max:200' ],
		'twitterImage'         => [ 'nullable', 'string', 'max:2000' ],
		'twitterImageId'       => [ 'nullable', 'integer' ],
		'twitterSite'          => [ 'nullable', 'string', 'max:50' ],
		'twitterCreator'       => [ 'nullable', 'string', 'max:50' ],
		'pinterestDescription' => [ 'nullable', 'string', 'max:500' ],
		'pinterestImage'       => [ 'nullable', 'string', 'max:2000' ],
		'pinterestImageId'     => [ 'nullable', 'integer' ],
		'slackTitle'           => [ 'nullable', 'string', 'max:255' ],
		'slackDescription'     => [ 'nullable', 'string', 'max:500' ],
		'slackImage'           => [ 'nullable', 'string', 'max:2000' ],
		'slackImageId'         => [ 'nullable', 'integer' ],
		'schemaType'           => [ 'nullable', 'string', 'max:100' ],
		'schemaMarkup'         => [ 'nullable', 'string' ],
		'sitemapPriority'      => [ 'nullable', 'numeric', 'min:0', 'max:1' ],
		'sitemapChangefreq'    => [ 'nullable', 'string', 'in:always,hourly,daily,weekly,monthly,yearly,never' ],
		'excludeFromSitemap'   => [ 'boolean' ],
		'hreflang'             => [ 'nullable', 'string' ],
	];

	/**
	 * Mount the component.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model       $model      The model to edit SEO meta for.
	 * @param  string|null $activeTab  The initial active tab.
	 */
	public function mount( Model $model, ?string $activeTab = null ): void
	{
		$this->model = $model;

		if ( null !== $activeTab ) {
			$this->activeTab = $activeTab;
		}

		$this->loadSeoMeta();
	}

	/**
	 * Get the character count for meta title.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function titleCharCount(): int
	{
		return mb_strlen( $this->metaTitle );
	}

	/**
	 * Get the character count for meta description.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function descriptionCharCount(): int
	{
		return mb_strlen( $this->metaDescription );
	}

	/**
	 * Get the character count for OG title.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function ogTitleCharCount(): int
	{
		return mb_strlen( $this->ogTitle );
	}

	/**
	 * Get the character count for OG description.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function ogDescriptionCharCount(): int
	{
		return mb_strlen( $this->ogDescription );
	}

	/**
	 * Get the character count for Twitter title.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function twitterTitleCharCount(): int
	{
		return mb_strlen( $this->twitterTitle );
	}

	/**
	 * Get the character count for Twitter description.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function twitterDescriptionCharCount(): int
	{
		return mb_strlen( $this->twitterDescription );
	}

	/**
	 * Get the effective title for preview.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	#[Computed]
	public function previewTitle(): string
	{
		if ( '' !== $this->metaTitle ) {
			return $this->metaTitle;
		}

		if ( isset( $this->model->title ) ) {
			return (string) $this->model->title;
		}

		return config( 'app.name', '' );
	}

	/**
	 * Get the effective description for preview.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	#[Computed]
	public function previewDescription(): string
	{
		if ( '' !== $this->metaDescription ) {
			return $this->metaDescription;
		}

		if ( isset( $this->model->excerpt ) ) {
			return (string) $this->model->excerpt;
		}

		return '';
	}

	/**
	 * Get the effective URL for preview.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	#[Computed]
	public function previewUrl(): string
	{
		if ( '' !== $this->canonicalUrl ) {
			return $this->canonicalUrl;
		}

		if ( method_exists( $this->model, 'getUrl' ) ) {
			return $this->model->getUrl();
		}

		return url()->current();
	}

	/**
	 * Save the SEO metadata.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function save(): void
	{
		$this->isSaving = true;

		$this->validate();

		try {
			$data = $this->prepareDataForSave();

			if ( null !== $this->seoMeta ) {
				$this->seoMeta->update( $data );
			} else {
				$data['seoable_type'] = get_class( $this->model );
				$data['seoable_id']   = $this->model->getKey();

				$this->seoMeta = SeoMeta::create( $data );
			}

			$this->dispatch( 'seo-meta-saved', id: $this->seoMeta->id );

			session()->flash( 'success', __( 'SEO settings saved successfully.' ) );
		} catch ( Exception $e ) {
			Log::error( 'Failed to save SEO settings', [
				'model_type' => get_class( $this->model ),
				'model_id'   => $this->model->getKey(),
				'exception'  => $e->getMessage(),
				'trace'      => $e->getTraceAsString(),
			] );

			session()->flash( 'error', __( 'Failed to save SEO settings. Please try again or contact support.' ) );
		} finally {
			$this->isSaving = false;
		}
	}

	/**
	 * Run SEO analysis on the content.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function runAnalysis(): void
	{
		$this->analysisResult = [];

		// Basic checks
		$checks = [];

		// Title length check
		$titleLength = mb_strlen( $this->metaTitle );
		if ( 0 === $titleLength ) {
			$checks['title'] = [
				'status'  => 'warning',
				'message' => __( 'Meta title is empty. Add a title for better SEO.' ),
			];
		} elseif ( $titleLength < 30 ) {
			$checks['title'] = [
				'status'  => 'warning',
				'message' => __( 'Meta title is too short. Aim for 50-60 characters.' ),
			];
		} elseif ( $titleLength > 60 ) {
			$checks['title'] = [
				'status'  => 'warning',
				'message' => __( 'Meta title is too long. It may be truncated in search results.' ),
			];
		} else {
			$checks['title'] = [
				'status'  => 'success',
				'message' => __( 'Meta title length is optimal.' ),
			];
		}

		// Description length check
		$descLength = mb_strlen( $this->metaDescription );
		if ( 0 === $descLength ) {
			$checks['description'] = [
				'status'  => 'warning',
				'message' => __( 'Meta description is empty. Add a description for better click-through rates.' ),
			];
		} elseif ( $descLength < 70 ) {
			$checks['description'] = [
				'status'  => 'warning',
				'message' => __( 'Meta description is too short. Aim for 150-160 characters.' ),
			];
		} elseif ( $descLength > 160 ) {
			$checks['description'] = [
				'status'  => 'warning',
				'message' => __( 'Meta description is too long. It may be truncated in search results.' ),
			];
		} else {
			$checks['description'] = [
				'status'  => 'success',
				'message' => __( 'Meta description length is optimal.' ),
			];
		}

		// Focus keyword check
		if ( '' === $this->focusKeyword ) {
			$checks['focus_keyword'] = [
				'status'  => 'warning',
				'message' => __( 'No focus keyword set. Add a keyword to optimize your content.' ),
			];
		} else {
			// Check if keyword is in title
			$keywordInTitle = false !== mb_stripos( $this->metaTitle, $this->focusKeyword );

			if ( $keywordInTitle ) {
				$checks['keyword_in_title'] = [
					'status'  => 'success',
					'message' => __( 'Focus keyword appears in the meta title.' ),
				];
			} else {
				$checks['keyword_in_title'] = [
					'status'  => 'warning',
					'message' => __( 'Focus keyword does not appear in the meta title.' ),
				];
			}

			// Check if keyword is in description
			$keywordInDesc = false !== mb_stripos( $this->metaDescription, $this->focusKeyword );

			if ( $keywordInDesc ) {
				$checks['keyword_in_description'] = [
					'status'  => 'success',
					'message' => __( 'Focus keyword appears in the meta description.' ),
				];
			} else {
				$checks['keyword_in_description'] = [
					'status'  => 'warning',
					'message' => __( 'Focus keyword does not appear in the meta description.' ),
				];
			}
		}

		// Social media checks
		if ( '' === $this->ogImage && null === $this->ogImageId ) {
			$checks['og_image'] = [
				'status'  => 'info',
				'message' => __( 'No Open Graph image set. Social shares may not display an image.' ),
			];
		} else {
			$checks['og_image'] = [
				'status'  => 'success',
				'message' => __( 'Open Graph image is set.' ),
			];
		}

		$this->analysisResult = $checks;

		$this->dispatch( 'seo-analysis-complete', results: $this->analysisResult );
	}

	/**
	 * Set the active tab.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $tab  The tab to activate.
	 *
	 * @return void
	 */
	public function setActiveTab( string $tab ): void
	{
		$this->activeTab = $tab;
	}

	/**
	 * Handle media selection from media library.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<int, array<string, mixed>>  $media    The selected media.
	 * @param  string                            $context  The context (og, twitter, etc.).
	 *
	 * @return void
	 */
	#[On( 'media-selected' )]
	public function handleMediaSelected( array $media, string $context ): void
	{
		if ( empty( $media ) ) {
			return;
		}

		$selectedMedia = $media[0];
		$url           = $selectedMedia['url'] ?? '';
		$id            = $selectedMedia['id'] ?? null;

		switch ( $context ) {
			case 'og-image':
				$this->ogImage   = $url;
				$this->ogImageId = $id;
				break;

			case 'twitter-image':
				$this->twitterImage   = $url;
				$this->twitterImageId = $id;
				break;

			case 'pinterest-image':
				$this->pinterestImage   = $url;
				$this->pinterestImageId = $id;
				break;

			case 'slack-image':
				$this->slackImage   = $url;
				$this->slackImageId = $id;
				break;
		}
	}

	/**
	 * Clear an image field.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $field  The field to clear (og, twitter, pinterest, slack).
	 *
	 * @return void
	 */
	public function clearImage( string $field ): void
	{
		switch ( $field ) {
			case 'og':
				$this->ogImage   = '';
				$this->ogImageId = null;
				break;

			case 'twitter':
				$this->twitterImage   = '';
				$this->twitterImageId = null;
				break;

			case 'pinterest':
				$this->pinterestImage   = '';
				$this->pinterestImageId = null;
				break;

			case 'slack':
				$this->slackImage   = '';
				$this->slackImageId = null;
				break;
		}
	}

	/**
	 * Get image URL from media library ID.
	 *
	 * Resolves a media library ID to its public URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  int|null  $mediaId  The media library ID.
	 *
	 * @return string|null The public URL or null if not found.
	 */
	public function getImageUrlFromId( ?int $mediaId ): ?string
	{
		if ( null === $mediaId ) {
			return null;
		}

		// Check if the media library package is available
		if ( ! class_exists( 'ArtisanPackUI\MediaLibrary\Models\Media' ) ) {
			return null;
		}

		try {
			$mediaClass = 'ArtisanPackUI\MediaLibrary\Models\Media';
			$media      = $mediaClass::find( $mediaId );

			if ( null !== $media && method_exists( $media, 'getUrl' ) ) {
				return $media->getUrl();
			}

			// Fallback to url attribute if available
			if ( null !== $media && isset( $media->url ) ) {
				return $media->url;
			}
		} catch ( Exception $e ) {
			// Media not found or error, return null
		}

		return null;
	}

	/**
	 * Get the resolved OG image URL.
	 *
	 * Returns the OG image URL, resolving from media library ID if needed.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	#[Computed]
	public function resolvedOgImage(): ?string
	{
		if ( '' !== $this->ogImage ) {
			return $this->ogImage;
		}

		return $this->getImageUrlFromId( $this->ogImageId );
	}

	/**
	 * Get the resolved Twitter image URL.
	 *
	 * Returns the Twitter image URL, resolving from media library ID if needed.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	#[Computed]
	public function resolvedTwitterImage(): ?string
	{
		if ( '' !== $this->twitterImage ) {
			return $this->twitterImage;
		}

		return $this->getImageUrlFromId( $this->twitterImageId );
	}

	/**
	 * Get the resolved Pinterest image URL.
	 *
	 * Returns the Pinterest image URL, resolving from media library ID if needed.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	#[Computed]
	public function resolvedPinterestImage(): ?string
	{
		if ( '' !== $this->pinterestImage ) {
			return $this->pinterestImage;
		}

		return $this->getImageUrlFromId( $this->pinterestImageId );
	}

	/**
	 * Get the resolved Slack image URL.
	 *
	 * Returns the Slack image URL, resolving from media library ID if needed.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	#[Computed]
	public function resolvedSlackImage(): ?string
	{
		if ( '' !== $this->slackImage ) {
			return $this->slackImage;
		}

		return $this->getImageUrlFromId( $this->slackImageId );
	}

	/**
	 * Copy meta title to OG title.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function copyTitleToOg(): void
	{
		$this->ogTitle = $this->metaTitle;
	}

	/**
	 * Copy meta description to OG description.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function copyDescriptionToOg(): void
	{
		$this->ogDescription = $this->metaDescription;
	}

	/**
	 * Copy OG data to Twitter Card.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function copyOgToTwitter(): void
	{
		$this->twitterTitle       = $this->ogTitle;
		$this->twitterDescription = $this->ogDescription;
		$this->twitterImage       = $this->ogImage;
		$this->twitterImageId     = $this->ogImageId;
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
		return view( 'seo::livewire.seo-meta-editor', [
			'ogTypes'           => self::OG_TYPES,
			'twitterCardTypes'  => self::TWITTER_CARD_TYPES,
			'changefreqOptions' => self::CHANGEFREQ_OPTIONS,
			'schemaTypes'       => self::SCHEMA_TYPES,
		] );
	}

	/**
	 * Load the SEO meta record for the model.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function loadSeoMeta(): void
	{
		// Get or create the SeoMeta record
		$this->seoMeta = SeoMeta::where( 'seoable_type', get_class( $this->model ) )
			->where( 'seoable_id', $this->model->getKey() )
			->first();

		if ( null !== $this->seoMeta ) {
			$this->populateFromSeoMeta();
		} else {
			$this->populateDefaults();
		}
	}

	/**
	 * Populate form fields from the SeoMeta record.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function populateFromSeoMeta(): void
	{
		$this->metaTitle            = $this->seoMeta->meta_title ?? '';
		$this->metaDescription      = $this->seoMeta->meta_description ?? '';
		$this->canonicalUrl         = $this->seoMeta->canonical_url ?? '';
		$this->focusKeyword         = $this->seoMeta->focus_keyword ?? '';
		$this->secondaryKeywords    = is_array( $this->seoMeta->secondary_keywords )
			? implode( ', ', $this->seoMeta->secondary_keywords )
			: '';
		$this->noIndex              = (bool) $this->seoMeta->no_index;
		$this->noFollow             = (bool) $this->seoMeta->no_follow;
		$this->robotsMeta           = $this->seoMeta->robots_meta ?? '';
		$this->ogTitle              = $this->seoMeta->og_title ?? '';
		$this->ogDescription        = $this->seoMeta->og_description ?? '';
		$this->ogImage              = $this->seoMeta->og_image ?? '';
		$this->ogImageId            = $this->seoMeta->og_image_id;
		$this->ogType               = $this->seoMeta->og_type ?? 'website';
		$this->ogLocale             = $this->seoMeta->og_locale ?? '';
		$this->ogSiteName           = $this->seoMeta->og_site_name ?? '';
		$this->twitterCard          = $this->seoMeta->twitter_card ?? 'summary_large_image';
		$this->twitterTitle         = $this->seoMeta->twitter_title ?? '';
		$this->twitterDescription   = $this->seoMeta->twitter_description ?? '';
		$this->twitterImage         = $this->seoMeta->twitter_image ?? '';
		$this->twitterImageId       = $this->seoMeta->twitter_image_id;
		$this->twitterSite          = $this->seoMeta->twitter_site ?? '';
		$this->twitterCreator       = $this->seoMeta->twitter_creator ?? '';
		$this->pinterestDescription = $this->seoMeta->pinterest_description ?? '';
		$this->pinterestImage       = $this->seoMeta->pinterest_image ?? '';
		$this->pinterestImageId     = $this->seoMeta->pinterest_image_id;
		$this->slackTitle           = $this->seoMeta->slack_title ?? '';
		$this->slackDescription     = $this->seoMeta->slack_description ?? '';
		$this->slackImage           = $this->seoMeta->slack_image ?? '';
		$this->slackImageId         = $this->seoMeta->slack_image_id;
		$this->schemaType           = $this->seoMeta->schema_type ?? '';
		$this->schemaMarkup         = $this->safeJsonEncode( $this->seoMeta->schema_markup );
		$this->sitemapPriority      = (float) ( $this->seoMeta->sitemap_priority ?? 0.5 );
		$this->sitemapChangefreq    = $this->seoMeta->sitemap_changefreq ?? 'weekly';
		$this->excludeFromSitemap   = (bool) $this->seoMeta->exclude_from_sitemap;
		$this->hreflang             = $this->safeJsonEncode( $this->seoMeta->hreflang );
	}

	/**
	 * Populate default values from config or model.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function populateDefaults(): void
	{
		// Try to get title from model
		if ( isset( $this->model->title ) ) {
			$this->metaTitle = (string) $this->model->title;
		} elseif ( isset( $this->model->name ) ) {
			$this->metaTitle = (string) $this->model->name;
		}

		// Try to get description from model
		if ( isset( $this->model->excerpt ) ) {
			$this->metaDescription = (string) $this->model->excerpt;
		} elseif ( isset( $this->model->description ) ) {
			$this->metaDescription = mb_substr( strip_tags( (string) $this->model->description ), 0, 160 );
		}

		// Set defaults from config
		$this->ogSiteName        = config( 'seo.defaults.og_site_name', config( 'app.name', '' ) );
		$this->ogType            = config( 'seo.defaults.og_type', 'website' );
		$this->twitterCard       = config( 'seo.defaults.twitter_card', 'summary_large_image' );
		$this->twitterSite       = config( 'seo.defaults.twitter_site', '' );
		$this->sitemapPriority   = (float) config( 'seo.defaults.sitemap_priority', 0.5 );
		$this->sitemapChangefreq = config( 'seo.defaults.sitemap_changefreq', 'weekly' );
	}

	/**
	 * Prepare data for saving.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	protected function prepareDataForSave(): array
	{
		// Parse secondary keywords
		$secondaryKeywords = array_filter(
			array_map( 'trim', explode( ',', $this->secondaryKeywords ) ),
		);

		// Parse schema markup JSON
		$schemaMarkup = null;
		if ( '' !== $this->schemaMarkup ) {
			$decoded = json_decode( $this->schemaMarkup, true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				$schemaMarkup = $decoded;
			}
		}

		// Parse hreflang JSON
		$hreflang = null;
		if ( '' !== $this->hreflang ) {
			$decoded = json_decode( $this->hreflang, true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				$hreflang = $decoded;
			}
		}

		return [
			'meta_title'            => $this->metaTitle ?: null,
			'meta_description'      => $this->metaDescription ?: null,
			'canonical_url'         => $this->canonicalUrl ?: null,
			'focus_keyword'         => $this->focusKeyword ?: null,
			'secondary_keywords'    => ! empty( $secondaryKeywords ) ? $secondaryKeywords : null,
			'no_index'              => $this->noIndex,
			'no_follow'             => $this->noFollow,
			'robots_meta'           => $this->robotsMeta ?: null,
			'og_title'              => $this->ogTitle ?: null,
			'og_description'        => $this->ogDescription ?: null,
			'og_image'              => $this->ogImage ?: null,
			'og_image_id'           => $this->ogImageId,
			'og_type'               => $this->ogType ?: 'website',
			'og_locale'             => $this->ogLocale ?: null,
			'og_site_name'          => $this->ogSiteName ?: null,
			'twitter_card'          => $this->twitterCard ?: 'summary_large_image',
			'twitter_title'         => $this->twitterTitle ?: null,
			'twitter_description'   => $this->twitterDescription ?: null,
			'twitter_image'         => $this->twitterImage ?: null,
			'twitter_image_id'      => $this->twitterImageId,
			'twitter_site'          => $this->twitterSite ?: null,
			'twitter_creator'       => $this->twitterCreator ?: null,
			'pinterest_description' => $this->pinterestDescription ?: null,
			'pinterest_image'       => $this->pinterestImage ?: null,
			'pinterest_image_id'    => $this->pinterestImageId,
			'slack_title'           => $this->slackTitle ?: null,
			'slack_description'     => $this->slackDescription ?: null,
			'slack_image'           => $this->slackImage ?: null,
			'slack_image_id'        => $this->slackImageId,
			'schema_type'           => $this->schemaType ?: null,
			'schema_markup'         => $schemaMarkup,
			'sitemap_priority'      => $this->sitemapPriority,
			'sitemap_changefreq'    => $this->sitemapChangefreq,
			'exclude_from_sitemap'  => $this->excludeFromSitemap,
			'hreflang'              => $hreflang,
		];
	}

	/**
	 * Safely encode a value to JSON.
	 *
	 * Returns an empty string if the value is not an array or if encoding fails.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed  $value  The value to encode.
	 *
	 * @return string The JSON string or empty string on failure.
	 */
	protected function safeJsonEncode( mixed $value ): string
	{
		if ( ! is_array( $value ) ) {
			return '';
		}

		$encoded = json_encode( $value, JSON_PRETTY_PRINT );

		if ( false === $encoded || JSON_ERROR_NONE !== json_last_error() ) {
			return '';
		}

		return $encoded;
	}
}
