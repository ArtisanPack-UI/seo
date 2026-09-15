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
 * Suggest N SEO title variants for a page.
 *
 * ## Input
 *
 * ```
 * [
 *   'content'         => string,   // required
 *   'primary_keyword' => string,   // optional
 *   'brand'           => string,   // optional
 *   'h1'              => string,   // optional; used to reject verbatim restates
 *   'n'               => int,      // optional; default 5, clamped to [1, 10]
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
 * Existing single-variant callers keep working via `$variants[0]`.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class MetaTitleSuggestionAgent extends ArtisanPackAgent
{
	/**
	 * Absolute upper limit on rendered title length.
	 */
	protected const MAX_TITLE_LENGTH = 60;

	/**
	 * Absolute cap on variants returned in one call.
	 */
	protected const MAX_VARIANTS = 10;

	/**
	 * Default variant count when caller doesn't specify `n`.
	 */
	protected const DEFAULT_VARIANTS = 5;

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
You write SEO meta titles for a single web page. Return exactly the number of
variants requested — high-quality, click-worthy, and distinct.

Hard rules (every variant):
- 60 characters or fewer, including spaces and punctuation. Set `char_count` to
  the exact length. A variant that exceeds 60 will be discarded.
- Front-load the specific value, entity, or benefit — the most useful word
  belongs in the first 30 characters, where SERP snippets truncate.
- Do NOT restate the page's H1 verbatim. If an H1 is provided, treat it as a
  starting point to rephrase, not copy.
- Do NOT lead with the brand name. Append `" | {brand}"` at the end only if
  `brand` is provided AND the full string still fits under 60 chars. The one
  exception: pages that are explicitly ABOUT the brand (homepage, about page,
  brand-name product pages) may begin with the brand.
- Include `primary_keyword` (or a very close morphological variant) in every
  variant when it is provided, ideally within the first 30 characters.
- Prefer active voice and specific nouns. Avoid ALL-CAPS, avoid clickbait
  ("You Won't Believe…"), avoid empty openers ("Discover…", "Everything about…",
  "Ultimate guide to…").
- Vary the angle across variants — how-to, comparison, benefit, question,
  outcome — so the caller gets meaningfully different options, not re-orderings.
- `rationale` is a single sentence (<= 140 chars) naming the angle.

Few-shot examples (input → good variant):

  Input: an article comparing burr and blade grinders for espresso.
  Good:  "Burr vs. Blade Grinder: Which Wins for Espresso?"
  Bad:   "Discover Everything About Coffee Grinders Today"  (empty opener)
  Bad:   "Acme | The Complete Coffee Grinder Buying Guide"  (brand-first)

  Input: pricing page for a project-management SaaS.
  Good:  "Project Management Pricing — Plans From $9/mo"
  Bad:   "Pricing"  (too generic; wastes the first 30 chars)

Return a JSON object with key `variants` (array of objects with `title`,
`char_count`, `rationale`).
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
					'minItems' => 1,
					'maxItems' => self::MAX_VARIANTS,
					'items'    => [
						'type'                 => 'object',
						'additionalProperties' => false,
						'required'             => [ 'title', 'char_count', 'rationale' ],
						'properties'           => [
							'title'      => [ 'type' => 'string', 'maxLength' => self::MAX_TITLE_LENGTH ],
							'char_count' => [ 'type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_TITLE_LENGTH ],
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
			'output'        => $this->validateOutput( $result['output'] ?? [], $normalized ),
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
	 * @return array{ content: string, primary_keyword: string|null, brand: string|null, h1: string|null, n: int }
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
		$h1      = isset( $input['h1'] ) && is_string( $input['h1'] ) ? trim( $input['h1'] ) : '';

		$n = isset( $input['n'] ) && is_numeric( $input['n'] ) ? (int) $input['n'] : self::DEFAULT_VARIANTS;
		$n = max( 1, min( self::MAX_VARIANTS, $n ) );

		return [
			'content'         => $content,
			'primary_keyword' => '' === $primary ? null : $primary,
			'brand'           => '' === $brand ? null : $brand,
			'h1'              => '' === $h1 ? null : $h1,
			'n'               => $n,
		];
	}

	/**
	 * Assemble the structured message body for the prompter.
	 *
	 * @since 1.2.0
	 *
	 * @param  array{ content: string, primary_keyword: string|null, brand: string|null, h1: string|null, n: int }  $normalized  Normalized input.
	 *
	 * @return array<int, array<string, string>>
	 */
	protected function buildMessage( array $normalized ): array
	{
		$parts = [];

		$parts[] = [ 'type' => 'text', 'text' => sprintf( 'Return exactly %d variants.', $normalized['n'] ) ];

		if ( null !== $normalized['primary_keyword'] ) {
			$parts[] = [ 'type' => 'text', 'text' => sprintf( 'Primary keyword: %s', $normalized['primary_keyword'] ) ];
		}

		if ( null !== $normalized['brand'] ) {
			$parts[] = [ 'type' => 'text', 'text' => sprintf( 'Brand: %s', $normalized['brand'] ) ];
		}

		if ( null !== $normalized['h1'] ) {
			$parts[] = [ 'type' => 'text', 'text' => sprintf( 'Page H1 (do NOT restate verbatim): %s', $normalized['h1'] ) ];
		}

		$parts[] = [ 'type' => 'text', 'text' => "Page content:\n" . $normalized['content'] ];

		return $parts;
	}

	/**
	 * Enforce output invariants — length cap, empty/duplicate rejection, count cap.
	 *
	 * @since 1.2.0
	 *
	 * @param  array<string, mixed>                                                                                    $output      Decoded model output.
	 * @param  array{ content: string, primary_keyword: string|null, brand: string|null, h1: string|null, n: int }  $normalized  Normalized input.
	 *
	 * @return array{ variants: array<int, array{ title: string, char_count: int, rationale: string }> }
	 */
	protected function validateOutput( array $output, array $normalized ): array
	{
		$raw = $output['variants'] ?? [];

		if ( ! is_array( $raw ) ) {
			$raw = [];
		}

		$variants = [];
		$seen     = [];
		$h1Key    = null !== $normalized['h1'] ? mb_strtolower( $normalized['h1'] ) : null;

		foreach ( $raw as $variant ) {
			if ( ! is_array( $variant ) ) {
				continue;
			}

			$title = isset( $variant['title'] ) ? trim( (string) $variant['title'] ) : '';

			if ( '' === $title ) {
				continue;
			}

			// Reject rather than truncate — truncation destroys the front-load
			// discipline the prompt is optimizing for.
			if ( mb_strlen( $title ) > self::MAX_TITLE_LENGTH ) {
				continue;
			}

			$key = mb_strtolower( $title );

			if ( null !== $h1Key && $key === $h1Key ) {
				continue;
			}

			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;

			$variants[] = [
				'title'      => $title,
				'char_count' => mb_strlen( $title ),
				'rationale'  => isset( $variant['rationale'] ) ? trim( (string) $variant['rationale'] ) : '',
			];
		}

		if ( count( $variants ) > $normalized['n'] ) {
			$variants = array_slice( $variants, 0, $normalized['n'] );
		}

		return [ 'variants' => $variants ];
	}
}
