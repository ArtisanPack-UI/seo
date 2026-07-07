<?php

/**
 * HreflangSuggestor Livewire component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Livewire\Ai;

use ArtisanPackUI\Ai\Contracts\FeatureRegistry;
use ArtisanPackUI\Ai\Exceptions\FeatureDisabledException;
use ArtisanPackUI\Ai\Exceptions\FeatureError;
use ArtisanPackUI\Ai\Exceptions\MissingCredentialsException;
use ArtisanPackUI\SEO\Ai\Agents\HreflangSuggestionAgent;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

/**
 * Trigger UI for the {@see HreflangSuggestionAgent}.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class HreflangSuggestor extends Component
{
	/**
	 * @var array<int, array{ url: string, lang: string, translations: array<int, array{ url: string, lang: string }> }>
	 */
	public array $pages = [];

	public bool $isLoading = false;

	public ?string $error = null;

	/**
	 * @var array<int, array{ page_url: string, issue_type: string, suggested_hreflang: array<int, array{ lang: string, url: string }> }>
	 */
	public array $issues = [];

	/**
	 * Mount the component with initial pages payload.
	 *
	 * @since 1.2.0
	 *
	 * @param  array<int, array<string, mixed>>  $pages  Pages payload.
	 */
	public function mount( array $pages = [] ): void
	{
		$this->pages = $pages;
	}

	/**
	 * React to the parent editor updating the pages payload.
	 *
	 * @since 1.2.0
	 *
	 * @param  array{ pages?: array<int, array<string, mixed>> }  $payload  New context.
	 *
	 * @return void
	 */
	#[On( 'seo-ai-context-updated' )]
	public function contextUpdated( array $payload ): void
	{
		if ( isset( $payload['pages'] ) && is_array( $payload['pages'] ) ) {
			$this->pages = $payload['pages'];
		}
	}

	/**
	 * Run the agent and populate `$issues` or `$error`.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function suggest(): void
	{
		$this->error     = null;
		$this->issues    = [];
		$this->isLoading = true;

		try {
			$output = HreflangSuggestionAgent::for( [ 'pages' => $this->pages ] )->run();

			$this->issues = is_array( $output['issues'] ?? null ) ? $output['issues'] : [];
		} catch ( FeatureDisabledException $exception ) {
			$this->error = __( 'This AI feature is disabled.' );
		} catch ( MissingCredentialsException $exception ) {
			$this->error = __( 'AI credentials are not configured.' );
		} catch ( FeatureError $exception ) {
			$this->error = $exception->getMessage();
		} catch ( Throwable $exception ) {
			$this->error = __( 'The AI agent could not complete this request.' );
		} finally {
			$this->isLoading = false;
		}
	}

	/**
	 * Emit a single fix back to the parent editor.
	 *
	 * @since 1.2.0
	 *
	 * @param  int  $index  Issue index.
	 *
	 * @return void
	 */
	public function applyFix( int $index ): void
	{
		if ( ! isset( $this->issues[ $index ] ) ) {
			return;
		}

		$this->dispatch(
			'seo-ai-hreflang-fix-selected',
			page_url: $this->issues[ $index ]['page_url'],
			suggested_hreflang: $this->issues[ $index ]['suggested_hreflang'],
		);
	}

	/**
	 * Determine whether this feature is enabled in the registry.
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public function getIsEnabledProperty(): bool
	{
		$registry = app( FeatureRegistry::class );
		$key      = 'seo.suggest_hreflang';

		if ( null === $registry->get( $key ) ) {
			return false;
		}

		return $registry->isToggleOn( $key );
	}

	/**
	 * Render the component view.
	 *
	 * @since 1.2.0
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'seo::livewire.ai.hreflang-suggestor' );
	}
}
