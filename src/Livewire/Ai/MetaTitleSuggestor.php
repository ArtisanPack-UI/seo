<?php

/**
 * MetaTitleSuggestor Livewire component.
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
use ArtisanPackUI\SEO\Ai\Agents\MetaTitleSuggestionAgent;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

/**
 * Trigger UI for the {@see MetaTitleSuggestionAgent}.
 *
 * Emits `seo-ai-title-selected` (payload: `[ 'title' => string ]`) when the
 * user picks a variant, so the containing editor can wire whichever field
 * update it wants.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class MetaTitleSuggestor extends Component
{
	public string $content = '';

	public string $primaryKeyword = '';

	public string $brand = '';

	public bool $isLoading = false;

	public ?string $error = null;

	/**
	 * @var array<int, array{ title: string, char_count: int, rationale: string }>
	 */
	public array $variants = [];

	/**
	 * Mount the component with initial context from the containing editor.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $content         Page content.
	 * @param  string  $primaryKeyword  Focus keyword (optional).
	 * @param  string  $brand           Brand name (optional).
	 */
	public function mount( string $content = '', string $primaryKeyword = '', string $brand = '' ): void
	{
		$this->content        = $content;
		$this->primaryKeyword = $primaryKeyword;
		$this->brand          = $brand;
	}

	/**
	 * React to the parent editor updating its content payload.
	 *
	 * @since 1.2.0
	 *
	 * @param  array{ content?: string, primary_keyword?: string, brand?: string }  $payload  New context.
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

		if ( isset( $payload['brand'] ) ) {
			$this->brand = (string) $payload['brand'];
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
		$this->error     = null;
		$this->variants  = [];
		$this->isLoading = true;

		try {
			$output = MetaTitleSuggestionAgent::for( [
				'content'         => $this->content,
				'primary_keyword' => $this->primaryKeyword,
				'brand'           => $this->brand,
			] )->run();

			$this->variants = $output['variants'] ?? [];
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

		$this->dispatch( 'seo-ai-title-selected', title: $this->variants[ $index ]['title'] );
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
		$key      = 'seo.suggest_meta_title';

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
		return view( 'seo::livewire.ai.meta-title-suggestor' );
	}
}
