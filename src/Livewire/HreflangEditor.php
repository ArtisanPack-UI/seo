<?php

/**
 * HreflangEditor Livewire Component.
 *
 * Livewire component for managing hreflang alternate language URLs in admin interfaces.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Livewire;

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\HreflangService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * HreflangEditor component for managing alternate language URLs.
 *
 * Provides an interface for adding, editing, and removing hreflang
 * entries for a model's SEO metadata.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class HreflangEditor extends Component
{
	/**
	 * The model to edit hreflang for.
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
	 * The hreflang entries.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, array{locale: string, url: string}>
	 */
	public array $hreflangEntries = [];

	/**
	 * Whether the component is currently saving.
	 *
	 * @since 1.0.0
	 */
	public bool $isSaving = false;

	/**
	 * Mount the component.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to edit hreflang for.
	 */
	public function mount( Model $model ): void
	{
		$this->model = $model;

		$this->loadSeoMeta();
	}

	/**
	 * Get the available locales for selection.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	#[Computed]
	public function availableLocales(): array
	{
		$hreflangService = app( HreflangService::class );

		$locales = $hreflangService->getAvailableLocales();

		// Add x-default option
		array_unshift( $locales, [
			'value' => 'x-default',
			'label' => __( 'x-default (Fallback)' ),
		] );

		return $locales;
	}

	/**
	 * Get the default locale.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	#[Computed]
	public function defaultLocale(): ?string
	{
		return app( HreflangService::class )->getDefaultLocale();
	}

	/**
	 * Check if hreflang is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	#[Computed]
	public function isEnabled(): bool
	{
		return app( HreflangService::class )->isEnabled();
	}

	/**
	 * Get the count of hreflang entries.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	#[Computed]
	public function entryCount(): int
	{
		return count( $this->hreflangEntries );
	}

	/**
	 * Add a new hreflang entry.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function addHreflang(): void
	{
		$this->hreflangEntries[] = [
			'locale' => '',
			'url'    => '',
		];
	}

	/**
	 * Remove a hreflang entry.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $index  The index to remove.
	 *
	 * @return void
	 */
	public function removeHreflang( int $index ): void
	{
		if ( isset( $this->hreflangEntries[ $index ] ) ) {
			unset( $this->hreflangEntries[ $index ] );
			$this->hreflangEntries = array_values( $this->hreflangEntries );
		}
	}

	/**
	 * Save the hreflang data.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function save(): void
	{
		$this->validate();

		$this->isSaving = true;

		try {
			$hreflangService = app( HreflangService::class );

			// Validate non-empty entries
			foreach ( $this->hreflangEntries as $index => $entry ) {
				$hasLocale = '' !== $entry['locale'];
				$hasUrl    = '' !== $entry['url'];

				// Skip completely empty entries
				if ( ! $hasLocale && ! $hasUrl ) {
					continue;
				}

				// Validate partial entries (one field filled, other empty)
				if ( $hasLocale && ! $hasUrl ) {
					$this->addError( "hreflangEntries.{$index}.url", __( 'Please enter a URL.' ) );
					$this->isSaving = false;

					return;
				}
				if ( $hasUrl && ! $hasLocale ) {
					$this->addError( "hreflangEntries.{$index}.locale", __( 'Please select a language.' ) );
					$this->isSaving = false;

					return;
				}

				// Validate locale format
				if ( ! $hreflangService->validateLocale( $entry['locale'] ) ) {
					$this->addError( "hreflangEntries.{$index}.locale", __( 'Invalid locale format: :locale', [ 'locale' => $entry['locale'] ] ) );
					$this->isSaving = false;

					return;
				}
			}

			// Convert entries to locale => url format
			$hreflangData = [];
			$seenLocales  = [];
			foreach ( $this->hreflangEntries as $index => $entry ) {
				if ( '' !== $entry['locale'] && '' !== $entry['url'] ) {
					if ( isset( $seenLocales[ $entry['locale'] ] ) ) {
						$this->addError( "hreflangEntries.{$index}.locale", __( 'Duplicate locale: :locale', [ 'locale' => $entry['locale'] ] ) );
						$this->isSaving = false;
						return;
					}
					$seenLocales[ $entry['locale'] ]  = true;
					$hreflangData[ $entry['locale'] ] = $entry['url'];
				}
			}

			// Get or create SeoMeta
			$this->ensureSeoMetaExists();

			// Save hreflang data
			$hreflangService->setAlternateUrls( $this->seoMeta, $hreflangData, true );

			$this->dispatch( 'hreflang-saved', count: count( $hreflangData ) );

			session()->flash( 'success', __( 'Alternate language URLs saved successfully.' ) );
		} catch ( Exception $e ) {
			Log::error( 'Failed to save hreflang data', [
				'model_type' => $this->model->getMorphClass(),
				'model_id'   => $this->model->getKey(),
				'exception'  => $e->getMessage(),
				'trace'      => $e->getTraceAsString(),
			] );

			session()->flash( 'error', __( 'Failed to save alternate language URLs. Please try again.' ) );
		} finally {
			$this->isSaving = false;
		}
	}

	/**
	 * Clear all hreflang entries.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clearAll(): void
	{
		$this->hreflangEntries = [];

		if ( null !== $this->seoMeta ) {
			app( HreflangService::class )->clearAlternateUrls( $this->seoMeta );

			$this->dispatch( 'hreflang-cleared' );

			session()->flash( 'success', __( 'All alternate language URLs cleared.' ) );
		}
	}

	/**
	 * Duplicate the current URL for a new locale.
	 *
	 * Takes the first entry's URL as a base for a new entry.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function duplicateEntry(): void
	{
		$baseUrl = '';

		if ( ! empty( $this->hreflangEntries ) ) {
			$baseUrl = $this->hreflangEntries[0]['url'] ?? '';
		}

		$this->hreflangEntries[] = [
			'locale' => '',
			'url'    => $baseUrl,
		];
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
		return view( 'seo::livewire.hreflang-editor' );
	}

	/**
	 * Validation rules.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<int, string>>
	 */
	protected function rules(): array
	{
		return [
			'hreflangEntries'          => [ 'array' ],
			'hreflangEntries.*.locale' => [ 'nullable', 'string', 'max:10' ],
			'hreflangEntries.*.url'    => [ 'nullable', 'url', 'max:2000' ],
		];
	}

	/**
	 * Custom validation messages.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	protected function messages(): array
	{
		return [
			'hreflangEntries.*.locale.required' => __( 'Please select a language.' ),
			'hreflangEntries.*.url.required'    => __( 'Please enter a URL.' ),
			'hreflangEntries.*.url.url'         => __( 'Please enter a valid URL.' ),
		];
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
		$this->seoMeta = SeoMeta::where( 'seoable_type', $this->model->getMorphClass() )
			->where( 'seoable_id', $this->model->getKey() )
			->first();

		if ( null !== $this->seoMeta && is_array( $this->seoMeta->hreflang ) ) {
			// Convert stored format to entries format
			$this->hreflangEntries = [];
			foreach ( $this->seoMeta->hreflang as $locale => $url ) {
				$this->hreflangEntries[] = [
					'locale' => $locale,
					'url'    => $url,
				];
			}
		}
	}

	/**
	 * Ensure the SeoMeta record exists.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function ensureSeoMetaExists(): void
	{
		if ( null === $this->seoMeta ) {
			$this->seoMeta = SeoMeta::create( [
				'seoable_type' => $this->model->getMorphClass(),
				'seoable_id'   => $this->model->getKey(),
			] );
		}
	}
}
