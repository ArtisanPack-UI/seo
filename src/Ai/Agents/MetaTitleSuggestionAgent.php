<?php

/**
 * Meta title suggestion agent.
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
 * Suggest 3-5 SEO title variants for a page.
 *
 * ## Input
 *
 * ```
 * [
 *   'content'         => string,   // required
 *   'primary_keyword' => string,   // optional
 *   'brand'           => string,   // optional
 * ]
 * ```
 *
 * ## Output schema
 *
 * ```
 * {
 *   variants: [
 *     { title: string(<=60), char_count: int, rationale: string }
 *   ]
 * }
 * ```
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class MetaTitleSuggestionAgent extends ArtisanPackAgent
{
	/**
	 * {@inheritDoc}
	 */
	public string $featureKey = 'seo.suggest_meta_title';

	/**
	 * {@inheritDoc}
	 */
	public string $package = 'artisanpack-ui/seo';

	/**
	 * {@inheritDoc}
	 */
	public string $defaultModel = 'claude-haiku-4-5';

	/**
	 * {@inheritDoc}
	 */
	public function instructions(): string
	{
		return <<<'PROMPT'
You generate 3 to 5 SEO title variants for a single web page.

Requirements:
- Every `title` MUST be 60 characters or fewer, including spaces and punctuation. Set `char_count` to the exact length.
- Optimize for click-through: front-load the strongest term, prefer specific over generic, avoid clickbait, avoid ALL-CAPS.
- When `primary_keyword` is provided, include it verbatim (or a very close morphological variant) in every variant, ideally near the start.
- When `brand` is provided, append it separated by " | " ONLY when it fits under the 60-character limit; otherwise omit it.
- Each `rationale` is a single sentence (<= 140 chars) explaining what angle the variant leans on.
- Return between 3 and 5 variants. Do not return duplicates or trivial re-orderings.

Return a JSON object with key `variants` (array of objects with `title`, `char_count`, `rationale`).
PROMPT;
	}

	/**
	 * {@inheritDoc}
	 */
	public function outputSchema(): array
	{
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'variants' ],
			'properties'           => [
				'variants' => [
					'type'     => 'array',
					'minItems' => 3,
					'maxItems' => 5,
					'items'    => [
						'type'                 => 'object',
						'additionalProperties' => false,
						'required'             => [ 'title', 'char_count', 'rationale' ],
						'properties'           => [
							'title'      => [ 'type' => 'string', 'maxLength' => 60 ],
							'char_count' => [ 'type' => 'integer', 'minimum' => 1, 'maximum' => 60 ],
							'rationale'  => [ 'type' => 'string' ],
						],
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
			'output'        => $this->validateOutput( $result['output'] ?? [] ),
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
	 * @return array{ content: string, primary_keyword: string|null, brand: string|null }
	 */
	protected function normalizeInput( mixed $input ): array
	{
		if ( ! is_array( $input ) ) {
			throw FeatureError::forFeature(
				$this->featureKey,
				'input must be an array with a `content` key.',
			);
		}

		$content = isset( $input['content'] ) && is_string( $input['content'] ) ? trim( $input['content'] ) : '';

		if ( '' === $content ) {
			throw FeatureError::forFeature( $this->featureKey, '`content` must be a non-empty string.' );
		}

		$primary = isset( $input['primary_keyword'] ) && is_string( $input['primary_keyword'] )
			? trim( $input['primary_keyword'] )
			: '';
		$brand   = isset( $input['brand'] ) && is_string( $input['brand'] ) ? trim( $input['brand'] ) : '';

		return [
			'content'         => $content,
			'primary_keyword' => '' === $primary ? null : $primary,
			'brand'           => '' === $brand ? null : $brand,
		];
	}

	/**
	 * Assemble the structured message body for the prompter.
	 *
	 * @since 1.2.0
	 *
	 * @param  array{ content: string, primary_keyword: string|null, brand: string|null }  $normalized  Normalized input.
	 *
	 * @return array<int, array<string, string>>
	 */
	protected function buildMessage( array $normalized ): array
	{
		$parts = [];

		if ( null !== $normalized['primary_keyword'] ) {
			$parts[] = [ 'type' => 'text', 'text' => sprintf( 'Primary keyword: %s', $normalized['primary_keyword'] ) ];
		}

		if ( null !== $normalized['brand'] ) {
			$parts[] = [ 'type' => 'text', 'text' => sprintf( 'Brand: %s', $normalized['brand'] ) ];
		}

		$parts[] = [ 'type' => 'text', 'text' => "Page content:\n" . $normalized['content'] ];

		return $parts;
	}

	/**
	 * Enforce output invariants — variant count, title length, integer counts.
	 *
	 * @since 1.2.0
	 *
	 * @param  array<string, mixed>  $output  Decoded model output.
	 *
	 * @return array{ variants: array<int, array{ title: string, char_count: int, rationale: string }> }
	 */
	protected function validateOutput( array $output ): array
	{
		$raw = $output['variants'] ?? [];

		if ( ! is_array( $raw ) ) {
			$raw = [];
		}

		$variants = [];

		foreach ( $raw as $variant ) {
			if ( ! is_array( $variant ) ) {
				continue;
			}

			$title = isset( $variant['title'] ) ? trim( (string) $variant['title'] ) : '';

			if ( '' === $title ) {
				continue;
			}

			if ( mb_strlen( $title ) > 60 ) {
				$title = mb_substr( $title, 0, 60 );
			}

			$variants[] = [
				'title'      => $title,
				'char_count' => mb_strlen( $title ),
				'rationale'  => isset( $variant['rationale'] ) ? trim( (string) $variant['rationale'] ) : '',
			];
		}

		if ( count( $variants ) > 5 ) {
			$variants = array_slice( $variants, 0, 5 );
		}

		return [ 'variants' => $variants ];
	}
}
