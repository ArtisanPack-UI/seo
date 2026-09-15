<?php

/**
 * MetaDescriptionSuggestor Livewire component.
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
use ArtisanPackUI\SEO\Ai\Agents\MetaDescriptionAgent;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Trigger UI for the {@see MetaDescriptionAgent}.
 *
 * Emits `seo-ai-description-selected` (payload: `[ 'description' => string ]`)
 * when the user picks a variant, so the containing editor can wire whichever
 * field update it wants.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class MetaDescriptionSuggestor extends Component
{
	use ChecksFeatureToggle;
	use InteractsWithAiFeature;

	public string $content = '';

	public string $primaryKeyword = '';

	/**
	 * @var array<int, array{ meta_description: string, character_count: int, rationale: string }>
	 */
	public array $variants = [];

	protected string $featureKey = 'seo.suggest_meta_description';

	/**
	 * Mount the component with initial context from the containing editor.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $content         Page content.
	 * @param  string  $primaryKeyword  Focus keyword (optional).
	 */
	public function mount( string $content = '', string $primaryKeyword = '' ): void
	{
		$this->content        = $content;
		$this->primaryKeyword = $primaryKeyword;
	}

	/**
	 * React to the parent editor updating its content payload.
	 *
	 * @since 1.2.0
	 *
	 * @param  array{ content?: string, primary_keyword?: string }  $payload  New context.
	 *
	 * @return void
	 */
	#[On( 'seo-ai-context-updated' )]
	public function contextUpdated( array $payload ): void
	{
		if ( isset( $payload['content'] ) ) {
			$this->content = (string) $payload['content'];
		}

		if ( isset( $payload['primary_keyword'] ) ) {
			$this->primaryKeyword = (string) $payload['primary_keyword'];
		}
	}

	/**
	 * Run the agent and populate `$variants` or `$error`.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function suggest(): void
	{
		$this->variants = [];

		$this->runAiFeature( function (): void {
			$output = MetaDescriptionAgent::for( [
				'content'         => $this->content,
				'primary_keyword' => $this->primaryKeyword,
			] )->run();

			$this->variants = $output['variants'] ?? [];
		} );
	}

	/**
	 * Emit the chosen variant back to the parent editor.
	 *
	 * @since 1.2.0
	 *
	 * @param  int  $index  Variant index.
	 *
	 * @return void
	 */
	public function select( int $index ): void
	{
		if ( ! isset( $this->variants[ $index ] ) ) {
			return;
		}

		$this->dispatch( 'seo-ai-description-selected', description: $this->variants[ $index ]['meta_description'] );
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
		return view( 'seo::livewire.ai.meta-description-suggestor' );
	}
}
