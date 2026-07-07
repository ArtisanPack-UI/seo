<?php

/**
 * AiAgentApiController.
 *
 * Single dispatch endpoint for the SEO package's AI agents. React/Vue
 * frontends POST here with a feature key + input payload; the controller
 * resolves the registered agent, runs it, and returns the structured output.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Http\Controllers\Api;

use ArtisanPackUI\Ai\Contracts\FeatureRegistry;
use ArtisanPackUI\Ai\Exceptions\FeatureDisabledException;
use ArtisanPackUI\Ai\Exceptions\FeatureError;
use ArtisanPackUI\Ai\Exceptions\MissingCredentialsException;
use ArtisanPackUI\SEO\Ai\Agents\ContentAnalysisAgent;
use ArtisanPackUI\SEO\Ai\Agents\HreflangSuggestionAgent;
use ArtisanPackUI\SEO\Ai\Agents\MetaDescriptionAgent;
use ArtisanPackUI\SEO\Ai\Agents\MetaTitleSuggestionAgent;
use ArtisanPackUI\SEO\Ai\Agents\SchemaGenerationAgent;
use ArtisanPackUI\SEO\Http\Requests\Api\Ai\AnalyzeContentAiRequest;
use ArtisanPackUI\SEO\Http\Requests\Api\Ai\GenerateSchemaAiRequest;
use ArtisanPackUI\SEO\Http\Requests\Api\Ai\SuggestHreflangAiRequest;
use ArtisanPackUI\SEO\Http\Requests\Api\Ai\SuggestMetaDescriptionAiRequest;
use ArtisanPackUI\SEO\Http\Requests\Api\Ai\SuggestMetaTitleAiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Throwable;

/**
 * Handles API requests for the SEO package's AI agents.
 *
 * The controller intentionally holds a static feature → agent map instead
 * of reflecting through the registry so a caller cannot execute an arbitrary
 * agent class by naming it in a request body.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class AiAgentApiController extends Controller
{
	/**
	 * Feature-key → agent-class map for this package.
	 *
	 * @since 1.2.0
	 *
	 * @var array<string, class-string>
	 */
	protected const AGENTS = [
		'seo.suggest_meta_title'       => MetaTitleSuggestionAgent::class,
		'seo.suggest_meta_description' => MetaDescriptionAgent::class,
		'seo.analyze_content'          => ContentAnalysisAgent::class,
		'seo.generate_schema'          => SchemaGenerationAgent::class,
		'seo.suggest_hreflang'         => HreflangSuggestionAgent::class,
	];

	/**
	 * Suggest 3-5 title variants.
	 *
	 * @since 1.2.0
	 */
	public function suggestMetaTitle( SuggestMetaTitleAiRequest $request ): JsonResponse
	{
		return $this->dispatchAgent( 'seo.suggest_meta_title', $request->validated() );
	}

	/**
	 * Suggest a 150-160 character meta description.
	 *
	 * @since 1.2.0
	 */
	public function suggestMetaDescription( SuggestMetaDescriptionAiRequest $request ): JsonResponse
	{
		return $this->dispatchAgent( 'seo.suggest_meta_description', $request->validated() );
	}

	/**
	 * Score content quality across four dimensions.
	 *
	 * @since 1.2.0
	 */
	public function analyzeContent( AnalyzeContentAiRequest $request ): JsonResponse
	{
		return $this->dispatchAgent( 'seo.analyze_content', $request->validated() );
	}

	/**
	 * Suggest a JSON-LD schema type and starter structure.
	 *
	 * @since 1.2.0
	 */
	public function generateSchema( GenerateSchemaAiRequest $request ): JsonResponse
	{
		return $this->dispatchAgent( 'seo.generate_schema', $request->validated() );
	}

	/**
	 * Surface missing or inconsistent hreflang relationships.
	 *
	 * @since 1.2.0
	 */
	public function suggestHreflang( SuggestHreflangAiRequest $request ): JsonResponse
	{
		return $this->dispatchAgent( 'seo.suggest_hreflang', $request->validated() );
	}

	/**
	 * Run the agent associated with `$featureKey` against `$input`.
	 *
	 * The registry-toggle short-circuit returns a 409 with the feature key so
	 * frontends can render a "This feature is disabled" state without needing
	 * to parse the exception message. Missing credentials return 412; validation
	 * failures inside the agent return 422; anything else is a 500.
	 *
	 * @since 1.2.0
	 *
	 * @param  string                $featureKey  Fully-qualified feature key.
	 * @param  array<string, mixed>  $input       Raw request payload.
	 *
	 * @return JsonResponse
	 */
	protected function dispatchAgent( string $featureKey, array $input ): JsonResponse
	{
		if ( ! isset( self::AGENTS[ $featureKey ] ) ) {
			return response()->json( [
				'message' => __( 'Unknown AI feature.' ),
			], 404 );
		}

		$registry = app( FeatureRegistry::class );

		if ( null !== $registry->get( $featureKey ) && ! $registry->isToggleOn( $featureKey ) ) {
			return response()->json( [
				'message'     => __( 'This AI feature is disabled.' ),
				'feature_key' => $featureKey,
			], 409 );
		}

		$agentClass = self::AGENTS[ $featureKey ];

		try {
			$output = $agentClass::for( $input )->run();
		} catch ( FeatureDisabledException $exception ) {
			return response()->json( [
				'message'     => $exception->getMessage(),
				'feature_key' => $featureKey,
			], 409 );
		} catch ( MissingCredentialsException $exception ) {
			return response()->json( [
				'message'     => $exception->getMessage(),
				'feature_key' => $featureKey,
			], 412 );
		} catch ( FeatureError $exception ) {
			return response()->json( [
				'message'     => $exception->getMessage(),
				'feature_key' => $featureKey,
			], 422 );
		} catch ( Throwable $exception ) {
			return response()->json( [
				'message'     => __( 'AI agent failed to run.' ),
				'feature_key' => $featureKey,
			], 500 );
		}

		return response()->json( [
			'data'        => $output,
			'feature_key' => $featureKey,
		] );
	}
}
