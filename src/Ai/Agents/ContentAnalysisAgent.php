<?php

/**
 * Content analysis agent.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Ai\Agents;

use ArtisanPackUI\Ai\Agents\ArtisanPackAgent;
use ArtisanPackUI\Ai\Contracts\AgentPrompter;
use ArtisanPackUI\Ai\Credentials\Credentials;
use ArtisanPackUI\Ai\Exceptions\FeatureError;

/**
 * Structured content quality score with per-dimension recommendations.
 *
 * ## Input
 *
 * ```
 * [
 *   'content'            => string,      // required
 *   'primary_keyword'    => string,      // required
 *   'secondary_keywords' => string[],    // optional
 * ]
 * ```
 *
 * ## Output schema
 *
 * ```
 * {
 *   overall_score: int(0..100),
 *   dimensions: {
 *     keyword_usage:         { score:int, recommendations:string[] },
 *     readability:           { score:int, recommendations:string[] },
 *     structure:             { score:int, recommendations:string[] },
 *     semantic_completeness: { score:int, recommendations:string[] }
 *   }
 * }
 * ```
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class ContentAnalysisAgent extends ArtisanPackAgent
{

	/**
	 * Dimensions scored on every run.
	 *
	 * @since 1.2.0
	 *
	 * @var array<int, string>
	 */
	protected const DIMENSIONS = [
		'keyword_usage',
		'readability',
		'structure',
		'semantic_completeness',
	];

	/**
	 * {@inheritDoc}
	 */
	public string $featureKey = 'seo.analyze_content';

	/**
	 * {@inheritDoc}
	 */
	public string $package = 'artisanpack-ui/seo';

	/**
	 * {@inheritDoc}
	 */
	public string $defaultModel = 'claude-sonnet-4-5';

	/**
	 * {@inheritDoc}
	 */
	public bool $stream = true;

	/**
	 * {@inheritDoc}
	 */
	public function instructions(): string
	{
		return <<<'PROMPT'
You analyze web page content for SEO quality across four dimensions and return concrete, actionable recommendations.

Dimensions:
- keyword_usage: density, placement, natural inclusion of primary and secondary keywords.
- readability: sentence length, paragraph length, transition words, active voice, jargon.
- structure: heading hierarchy, subheading distribution, list/bullet usage, scannability.
- semantic_completeness: topical coverage, related entities, questions a reader would still have.

Requirements:
- Score each dimension from 0 to 100. `overall_score` is a rounded weighted average (keyword_usage 30%, readability 25%, structure 20%, semantic_completeness 25%).
- Every dimension MUST have between 1 and 5 recommendations. Recommendations MUST be concrete, imperative sentences that reference the actual content (e.g. "shorten the 32-word sentence in paragraph 2", "add an H3 under 'Getting Started'"). Never emit generic advice like "improve readability", "add more content", or "N/A".
- If the content is short, thin, or otherwise limits your judgment for a dimension, score it low (0-40) and use the recommendations to describe exactly what is missing (e.g. "no H2/H3 headings present — add at least two section headings"). Do NOT skip a dimension or return placeholder text.
- Do NOT invent facts about the content — quote or reference what is actually there.

Return a JSON object with keys: overall_score (integer 0-100) and dimensions (object with keys keyword_usage, readability, structure, semantic_completeness — each an object with score:int and recommendations:string[]).
PROMPT;
	}

	/**
	 * {@inheritDoc}
	 */
	public function outputSchema(): array
	{
		$dimensionSchema = [
			'type'       => 'object',
			'required'   => [ 'score', 'recommendations' ],
			'properties' => [
				'score'           => [ 'type' => 'integer', 'minimum' => 0, 'maximum' => 100 ],
				'recommendations' => [
					'type'  => 'array',
					'items' => [ 'type' => 'string' ],
				],
			],
		];

		return [
			'type'       => 'object',
			'required'   => [ 'overall_score', 'dimensions' ],
			'properties' => [
				'overall_score' => [ 'type' => 'integer', 'minimum' => 0, 'maximum' => 100 ],
				'dimensions'    => [
					'type'       => 'object',
					'required'   => self::DIMENSIONS,
					'properties' => [
						'keyword_usage'         => $dimensionSchema,
						'readability'           => $dimensionSchema,
						'structure'             => $dimensionSchema,
						'semantic_completeness' => $dimensionSchema,
					],
				],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function execute( Credentials $credentials, string $model, string $instructions ): array
	{
		$normalized = $this->normalizeInput( $this->input() );

		$prompter = app( AgentPrompter::class );

		$result = $prompter->prompt(
			credentials: $credentials,
			model: $model,
			instructions: $instructions,
			message: $this->buildMessage( $normalized ),
			outputSchema: $this->outputSchema(),
		);

		return [
			'output'        => $this->validateOutput( $this->unwrapNestedPayload( $result['output'] ?? [] ) ),
			'input_tokens'  => (int) ( $result['input_tokens'] ?? 0 ),
			'output_tokens' => (int) ( $result['output_tokens'] ?? 0 ),
		];
	}

	/**
	 * Validate and shape-check the raw agent input.
	 *
	 * @since 1.2.0
	 *
	 * @param  mixed  $input  Raw agent input.
	 *
	 * @return array{ content: string, primary_keyword: string, secondary_keywords: array<int, string> }
	 */
	protected function normalizeInput( mixed $input ): array
	{
		if ( ! is_array( $input ) ) {
			throw FeatureError::forFeature(
				$this->featureKey,
				'input must be an array with `content` and `primary_keyword` keys.',
			);
		}

		$content = isset( $input['content'] ) && is_string( $input['content'] ) ? trim( $input['content'] ) : '';

		if ( '' === $content ) {
			throw FeatureError::forFeature( $this->featureKey, '`content` must be a non-empty string.' );
		}

		$primary = isset( $input['primary_keyword'] ) && is_string( $input['primary_keyword'] )
			? trim( $input['primary_keyword'] )
			: '';

		if ( '' === $primary ) {
			throw FeatureError::forFeature( $this->featureKey, '`primary_keyword` must be a non-empty string.' );
		}

		$secondary = [];

		if ( isset( $input['secondary_keywords'] ) && is_array( $input['secondary_keywords'] ) ) {
			foreach ( $input['secondary_keywords'] as $keyword ) {
				if ( is_string( $keyword ) && '' !== trim( $keyword ) ) {
					$secondary[] = trim( $keyword );
				}
			}
		}

		return [
			'content'            => $content,
			'primary_keyword'    => $primary,
			'secondary_keywords' => $secondary,
		];
	}

	/**
	 * Assemble the structured message body for the prompter.
	 *
	 * @since 1.2.0
	 *
	 * @param  array{ content: string, primary_keyword: string, secondary_keywords: array<int, string> }  $normalized  Normalized input.
	 *
	 * @return array<int, array<string, string>>
	 */
	protected function buildMessage( array $normalized ): array
	{
		$parts = [
			[ 'type' => 'text', 'text' => sprintf( 'Primary keyword: %s', $normalized['primary_keyword'] ) ],
		];

		if ( [] !== $normalized['secondary_keywords'] ) {
			$parts[] = [
				'type' => 'text',
				'text' => 'Secondary keywords: ' . implode( ', ', $normalized['secondary_keywords'] ),
			];
		}

		$parts[] = [ 'type' => 'text', 'text' => "Page content:\n" . $normalized['content'] ];

		return $parts;
	}

	/**
	 * Unwrap a stringified nested payload the model sometimes returns.
	 *
	 * The `laravel/ai` `StructuredAnonymousAgent` mangles the tool spec for
	 * schemas with a nested `object` field (like our `dimensions`). When it
	 * does, Anthropic returns the entire intended JSON as a *string* stuffed
	 * into one of the top-level fields — for us, that's `dimensions`. Rather
	 * than let the (real, useful) response fall on the floor and score 0/100
	 * across the board, detect that shape and JSON-decode it back into the
	 * expected structure. Correctly-shaped payloads pass through unchanged.
	 *
	 * @since 1.2.0
	 *
	 * @param  array<string, mixed>  $output  Raw prompter output.
	 *
	 * @return array<string, mixed>
	 */
	protected function unwrapNestedPayload( array $output ): array
	{
		// Recurse up to a few levels deep looking for the correctly-shaped
		// payload. Both known bugs (stringified JSON stuffed into a field,
		// or the full response wrapped one level too deep inside a field)
		// resolve the same way: find the first array with our expected
		// top-level keys and use it.
		return $this->findPayload( $output, 0 ) ?? $output;
	}

	/**
	 * Recursively hunt for an array shaped like the agent's true output.
	 *
	 * A correctly-shaped payload has `overall_score` alongside a `dimensions`
	 * array. When the tool-use bridge misroutes the response, that payload
	 * may end up one to two levels deeper — as either a nested object or a
	 * stringified JSON blob — inside a wrong-typed field. This walks a
	 * bounded depth of the tree and returns the first match.
	 *
	 * @since 1.2.0
	 *
	 * @param  mixed  $value  Node to inspect.
	 * @param  int    $depth  Current recursion depth.
	 *
	 * @return array<string, mixed>|null Correctly-shaped payload, or null when nothing matches.
	 */
	protected function findPayload( mixed $value, int $depth ): ?array
	{
		if ( $depth > 3 ) {
			return null;
		}

		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );

			return is_array( $decoded ) ? $this->findPayload( $decoded, $depth + 1 ) : null;
		}

		if ( ! is_array( $value ) ) {
			return null;
		}

		// Third observed shape: `overall_score` is reported honestly at the
		// top level, but `dimensions` is a JSON-encoded string of just the
		// dimensions object (no nested `overall_score`). Decode the string
		// and, if it looks like a real dimensions map, recombine with the
		// top-level score.
		if (
			isset( $value['overall_score'] )
			&& isset( $value['dimensions'] )
			&& is_string( $value['dimensions'] )
		) {
			$decodedDimensions = json_decode( $value['dimensions'], true );

			if ( $this->looksLikeDimensionsMap( $decodedDimensions ) ) {
				return [
					'overall_score' => $value['overall_score'],
					'dimensions'    => $decodedDimensions,
				];
			}
		}

		if (
			isset( $value['overall_score'] )
			&& isset( $value['dimensions'] )
			&& is_array( $value['dimensions'] )
			&& ! isset( $value['dimensions']['overall_score'] )
		) {
			return $value;
		}

		foreach ( $value as $child ) {
			$match = $this->findPayload( $child, $depth + 1 );

			if ( null !== $match ) {
				return $match;
			}
		}

		return null;
	}

	/**
	 * Determine whether a decoded value resembles a dimensions map (has at
	 * least one of the four dimension keys with a score/recommendations pair).
	 *
	 * @since 1.2.0
	 *
	 * @param  mixed  $value  Decoded candidate.
	 *
	 * @return bool
	 */
	protected function looksLikeDimensionsMap( mixed $value ): bool
	{
		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( self::DIMENSIONS as $key ) {
			if ( isset( $value[ $key ] ) && is_array( $value[ $key ] ) && isset( $value[ $key ]['score'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enforce output invariants — dimension presence, score bounds.
	 *
	 * @since 1.2.0
	 *
	 * @param  array<string, mixed>  $output  Decoded model output.
	 *
	 * @return array{ overall_score: int, dimensions: array<string, array{ score: int, recommendations: array<int, string> }> }
	 */
	protected function validateOutput( array $output ): array
	{
		$rawDimensions = is_array( $output['dimensions'] ?? null ) ? $output['dimensions'] : [];
		$dimensions    = [];

		foreach ( self::DIMENSIONS as $key ) {
			$entry = is_array( $rawDimensions[ $key ] ?? null ) ? $rawDimensions[ $key ] : [];

			$dimensions[ $key ] = [
				'score'           => $this->clampScore( $entry['score'] ?? 0 ),
				'recommendations' => $this->stringList( $entry['recommendations'] ?? [] ),
			];
		}

		return [
			'overall_score' => $this->clampScore( $output['overall_score'] ?? 0 ),
			'dimensions'    => $dimensions,
		];
	}

	/**
	 * Coerce a value into an integer bounded to [0, 100].
	 *
	 * @since 1.2.0
	 *
	 * @param  mixed  $value  Raw score.
	 *
	 * @return int
	 */
	protected function clampScore( mixed $value ): int
	{
		$score = (int) $value;

		return max( 0, min( 100, $score ) );
	}

	/**
	 * Filter a raw list into a clean array of non-empty strings.
	 *
	 * @since 1.2.0
	 *
	 * @param  mixed  $raw  Raw list from the model.
	 *
	 * @return array<int, string>
	 */
	protected function stringList( mixed $raw ): array
	{
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$out = [];

		foreach ( $raw as $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}

			$trimmed = trim( $value );

			if ( '' === $trimmed ) {
				continue;
			}

			// Reject hedge/placeholder strings the model sometimes emits
			// when it can't or won't judge a dimension. The prompt already
			// forbids these, but LLMs occasionally slip them through; the
			// caller is better served by an empty list than by unactionable
			// noise.
			if ( in_array( strtolower( $trimmed ), [ 'n/a', 'na', 'none', 'nothing', 'no recommendations' ], true ) ) {
				continue;
			}

			$out[] = $trimmed;
		}

		return $out;
	}
}
