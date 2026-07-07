<?php

/**
 * Meta description agent.
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
 * Generate one meta description between 150 and 160 characters.
 *
 * ## Input
 *
 * ```
 * [
 *   'content'         => string,   // required
 *   'primary_keyword' => string,   // optional
 * ]
 * ```
 *
 * ## Output schema
 *
 * ```
 * {
 *   meta_description: string(<=160),
 *   character_count:  int,
 *   rationale:        string
 * }
 * ```
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class MetaDescriptionAgent extends ArtisanPackAgent
{
	/**
	 * {@inheritDoc}
	 */
	public string $featureKey = 'seo.suggest_meta_description';

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
You generate a single SEO meta description for a web page.

Requirements:
- The `meta_description` MUST be between 150 and 160 characters inclusive, including spaces and punctuation. Set `character_count` to the exact length.
- Naturally include `primary_keyword` (or a very close morphological variant) when provided — do NOT keyword-stuff.
- Summarize the value the page delivers to the reader. Use active voice.
- End with an implicit call to action ("Learn how...", "Discover...", "See...") when it fits naturally.
- `rationale` is one sentence explaining the angle you took.

Return a JSON object with keys: meta_description (string), character_count (int), rationale (string).
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
			'required'             => [ 'meta_description', 'character_count', 'rationale' ],
			'properties'           => [
				'meta_description' => [ 'type' => 'string', 'maxLength' => 160 ],
				'character_count'  => [ 'type' => 'integer', 'minimum' => 1, 'maximum' => 160 ],
				'rationale'        => [ 'type' => 'string' ],
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
	 * @return array{ content: string, primary_keyword: string|null }
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

		return [
			'content'         => $content,
			'primary_keyword' => '' === $primary ? null : $primary,
		];
	}

	/**
	 * Assemble the structured message body for the prompter.
	 *
	 * @since 1.2.0
	 *
	 * @param  array{ content: string, primary_keyword: string|null }  $normalized  Normalized input.
	 *
	 * @return array<int, array<string, string>>
	 */
	protected function buildMessage( array $normalized ): array
	{
		$parts = [];

		if ( null !== $normalized['primary_keyword'] ) {
			$parts[] = [ 'type' => 'text', 'text' => sprintf( 'Primary keyword: %s', $normalized['primary_keyword'] ) ];
		}

		$parts[] = [ 'type' => 'text', 'text' => "Page content:\n" . $normalized['content'] ];

		return $parts;
	}

	/**
	 * Enforce output invariants — clamp length, coerce counts.
	 *
	 * @since 1.2.0
	 *
	 * @param  array<string, mixed>  $output  Decoded model output.
	 *
	 * @return array{ meta_description: string, character_count: int, rationale: string }
	 */
	protected function validateOutput( array $output ): array
	{
		$description = isset( $output['meta_description'] ) ? trim( (string) $output['meta_description'] ) : '';

		if ( mb_strlen( $description ) > 160 ) {
			$description = mb_substr( $description, 0, 160 );
		}

		return [
			'meta_description' => $description,
			'character_count'  => mb_strlen( $description ),
			'rationale'        => isset( $output['rationale'] ) ? trim( (string) $output['rationale'] ) : '',
		];
	}
}
