<?php

/**
 * ContentAnalyzer Livewire component.
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
use ArtisanPackUI\SEO\Ai\Agents\ContentAnalysisAgent;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Trigger UI for the {@see ContentAnalysisAgent}.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class ContentAnalyzer extends Component
{
	use ChecksFeatureToggle;
	use InteractsWithAiFeature;

	public string $content = '';

	public string $primaryKeyword = '';

	/**
	 * @var array<int, string>
	 */
	public array $secondaryKeywords = [];

	public ?int $overallScore = null;

	/**
	 * @var array<string, array{ score: int, recommendations: array<int, string> }>
	 */
	public array $dimensions = [];

	protected string $featureKey = 'seo.analyze_content';

	/**
	 * Mount the component with initial context from the containing editor.
	 *
	 * @since 1.2.0
	 *
	 * @param  string             $content            Page content.
	 * @param  string             $primaryKeyword     Focus keyword.
	 * @param  array<int, string> $secondaryKeywords  Secondary keywords.
	 */
	public function mount( string $content = '', string $primaryKeyword = '', array $secondaryKeywords = [] ): void
	{
		$this->content           = $content;
		$this->primaryKeyword    = $primaryKeyword;
		$this->secondaryKeywords = $secondaryKeywords;
	}

	/**
	 * React to the parent editor updating its content payload.
	 *
	 * @since 1.2.0
	 *
	 * @param  array{ content?: string, primary_keyword?: string, secondary_keywords?: array<int, string> }  $payload  New context.
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

		if ( isset( $payload['secondary_keywords'] ) && is_array( $payload['secondary_keywords'] ) ) {
			$this->secondaryKeywords = array_values( array_filter(
				$payload['secondary_keywords'],
				static fn ( $value ): bool => is_string( $value ) && '' !== trim( $value ),
			) );
		}
	}

	/**
	 * Run the agent and populate scores or error.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function analyze(): void
	{
		$this->overallScore = null;
		$this->dimensions   = [];

		$this->runAiFeature( function (): void {
			$output = ContentAnalysisAgent::for( [
				'content'            => $this->content,
				'primary_keyword'    => $this->primaryKeyword,
				'secondary_keywords' => $this->secondaryKeywords,
			] )->run();

			$this->overallScore = (int) ( $output['overall_score'] ?? 0 );
			$this->dimensions   = is_array( $output['dimensions'] ?? null ) ? $output['dimensions'] : [];
		} );
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
		return view( 'seo::livewire.ai.content-analyzer' );
	}
}
