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

use ArtisanPackUI\Ai\Contracts\FeatureRegistry;
use ArtisanPackUI\Ai\Exceptions\FeatureDisabledException;
use ArtisanPackUI\Ai\Exceptions\FeatureError;
use ArtisanPackUI\Ai\Exceptions\MissingCredentialsException;
use ArtisanPackUI\SEO\Ai\Agents\ContentAnalysisAgent;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

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
	public string $content = '';

	public string $primaryKeyword = '';

	/**
	 * @var array<int, string>
	 */
	public array $secondaryKeywords = [];

	public bool $isLoading = false;

	public ?string $error = null;

	public ?int $overallScore = null;

	/**
	 * @var array<string, array{ score: int, recommendations: array<int, string> }>
	 */
	public array $dimensions = [];

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
		$this->error        = null;
		$this->overallScore = null;
		$this->dimensions   = [];
		$this->isLoading    = true;

		try {
			$output = ContentAnalysisAgent::for( [
				'content'            => $this->content,
				'primary_keyword'    => $this->primaryKeyword,
				'secondary_keywords' => $this->secondaryKeywords,
			] )->run();

			$this->overallScore = (int) ( $output['overall_score'] ?? 0 );
			$this->dimensions   = is_array( $output['dimensions'] ?? null ) ? $output['dimensions'] : [];
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
	 * Determine whether this feature is enabled in the registry.
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public function getIsEnabledProperty(): bool
	{
		$registry = app( FeatureRegistry::class );
		$key      = 'seo.analyze_content';

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
		return view( 'seo::livewire.ai.content-analyzer' );
	}
}
