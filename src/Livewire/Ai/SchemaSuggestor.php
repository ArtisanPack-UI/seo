<?php

/**
 * SchemaSuggestor Livewire component.
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
use ArtisanPackUI\SEO\Ai\Agents\SchemaGenerationAgent;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Trigger UI for the {@see SchemaGenerationAgent}.
 *
 * Emits `seo-ai-schema-selected` (payload: `[ 'type' => string, 'jsonld' => array ]`)
 * when the user accepts the suggestion.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class SchemaSuggestor extends Component
{
	use ChecksFeatureToggle;
	use InteractsWithAiFeature;

	public string $content = '';

	public string $title = '';

	public string $url = '';

	public ?string $suggestedType = null;

	public float $confidence = 0.0;

	/**
	 * @var array<string, mixed>
	 */
	public array $jsonld = [];

	/**
	 * @var array<int, string>
	 */
	public array $missingRequiredFields = [];

	protected string $featureKey = 'seo.generate_schema';

	/**
	 * Mount the component with initial context from the containing editor.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $content  Page content.
	 * @param  string  $title    Page title.
	 * @param  string  $url      Page URL (optional).
	 */
	public function mount( string $content = '', string $title = '', string $url = '' ): void
	{
		$this->content = $content;
		$this->title   = $title;
		$this->url     = $url;
	}

	/**
	 * React to the parent editor updating its content payload.
	 *
	 * @since 1.2.0
	 *
	 * @param  array{ content?: string, title?: string, url?: string }  $payload  New context.
	 *
	 * @return void
	 */
	#[On( 'seo-ai-context-updated' )]
	public function contextUpdated( array $payload ): void
	{
		if ( isset( $payload['content'] ) ) {
			$this->content = (string) $payload['content'];
		}

		if ( isset( $payload['title'] ) ) {
			$this->title = (string) $payload['title'];
		}

		if ( isset( $payload['url'] ) ) {
			$this->url = (string) $payload['url'];
		}
	}

	/**
	 * Run the agent and populate suggestion or error.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function suggest(): void
	{
		$this->suggestedType         = null;
		$this->confidence            = 0.0;
		$this->jsonld                = [];
		$this->missingRequiredFields = [];

		$this->runAiFeature( function (): void {
			$output = SchemaGenerationAgent::for( [
				'content' => $this->content,
				'title'   => $this->title,
				'url'     => $this->url,
			] )->run();

			$this->suggestedType         = (string) ( $output['suggested_type'] ?? '' );
			$this->confidence            = (float) ( $output['confidence'] ?? 0 );
			$this->jsonld                = is_array( $output['jsonld'] ?? null ) ? $output['jsonld'] : [];
			$this->missingRequiredFields = is_array( $output['missing_required_fields'] ?? null )
				? array_values( array_filter(
					$output['missing_required_fields'],
					static fn ( $value ): bool => is_string( $value ) && '' !== trim( $value ),
				) )
				: [];
		} );
	}

	/**
	 * Emit the suggestion back to the parent editor.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function accept(): void
	{
		if ( null === $this->suggestedType ) {
			return;
		}

		$this->dispatch( 'seo-ai-schema-selected', type: $this->suggestedType, jsonld: $this->jsonld );
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
		return view( 'seo::livewire.ai.schema-suggestor' );
	}
}
