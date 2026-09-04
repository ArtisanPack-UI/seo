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

use ArtisanPackUI\Ai\Livewire\Concerns\ChecksFeatureToggle;
use ArtisanPackUI\Ai\Livewire\Concerns\InteractsWithAiFeature;
use ArtisanPackUI\SEO\Ai\Agents\HreflangSuggestionAgent;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

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
	use ChecksFeatureToggle;
	use InteractsWithAiFeature;

	/**
	 * @var array<int, array{ url: string, lang: string, translations: array<int, array{ url: string, lang: string }> }>
	 */
	public array $pages = [];

	/**
	 * @var array<int, array{ page_url: string, issue_type: string, suggested_hreflang: array<int, array{ lang: string, url: string }> }>
	 */
	public array $issues = [];

	protected string $featureKey = 'seo.suggest_hreflang';

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
		$this->issues = [];

		$this->runAiFeature( function (): void {
			$output = HreflangSuggestionAgent::for( [ 'pages' => $this->pages ] )->run();

			$this->issues = is_array( $output['issues'] ?? null ) ? $output['issues'] : [];
		} );
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
