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
 * Suggest N meta description variants between 150 and 160 characters.
 *
 * ## Input
 *
 * ```
 * [
 *   'content'         => string,   // required
 *   'primary_keyword' => string,   // optional
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
 *     { meta_description: string(<=160), character_count: int, rationale: string }
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
class MetaDescriptionAgent extends ArtisanPackAgent
{
	/**
	 * Absolute upper limit on rendered description length.
	 */
	protected const MAX_LENGTH = 160;

	/**
	 * Preferred lower bound — descriptions under this get flagged in prompt
	 * but are not rejected; short-but-useful still beats padded filler.
	 */
	protected const MIN_LENGTH = 120;

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
You write SEO meta descriptions for a single web page. Return exactly the number
of variants requested — high-quality, distinct, and specific to the page.

Hard rules (every variant):
- 150 to 160 characters, inclusive of spaces and punctuation. Set
  `character_count` to the exact length. A variant longer than 160 will be
  discarded, so stay in the target window.
- 1 or 2 complete sentences. No trailing ellipsis. No emoji.
- Front-load the specific what and why — what the page is about, and why the
  reader should care — in the first sentence. Do NOT restate the H1 verbatim.
- Include `primary_keyword` (or a very close morphological variant) naturally
  when provided. Never keyword-stuff.
- End with a soft nudge or benefit that tells the reader what they get, e.g.
  "…with side-by-side pricing and setup times." Not a hard CTA.
- Forbidden openers (empty filler that wastes SERP real estate):
  "Discover…", "Learn…", "Everything you need…", "The ultimate guide…",
  "Welcome to…", "Looking for…", "In this article…". Rephrase instead.
- Active voice. Specific nouns. No hedging ("might", "may help").
- Vary the angle across variants — outcome, comparison, how, who-it's-for,
  differentiator — so the caller gets meaningfully different options.
- `rationale` is one sentence naming the angle.

Few-shot examples:

  Page: comparing burr and blade coffee grinders for espresso.
  Good: "Burr grinders produce a uniform grind that pulls balanced espresso;
         blade grinders don't. Here's when each earns its counter space and
         which one wins for daily shots."
  Bad:  "Discover the best coffee grinders in our ultimate guide. Learn
         everything you need to know about grinding coffee for espresso today."
         (empty filler, no specifics, no reason to click)

  Page: pricing page for a project-management SaaS.
  Good: "Every plan includes unlimited projects, guest seats, and native
         integrations — from a free tier through the $19/mo Business plan.
         Pick the tier that fits your team size."
  Bad:  "Welcome to our pricing page. We have three plans to choose from that
         fit any budget. Sign up today to get started with our platform."

Return a JSON object with key `variants` (array of objects with
`meta_description`, `character_count`, `rationale`).
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
						'required'             => [ 'meta_description', 'character_count', 'rationale' ],
						'properties'           => [
							'meta_description' => [ 'type' => 'string', 'maxLength' => self::MAX_LENGTH ],
							'character_count'  => [ 'type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_LENGTH ],
							'rationale'        => [ 'type' => 'string' ],
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
	 * @return array{ content: string, primary_keyword: string|null, h1: string|null, n: int }
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
		$h1      = isset( $input['h1'] ) && is_string( $input['h1'] ) ? trim( $input['h1'] ) : '';

		$n = isset( $input['n'] ) && is_numeric( $input['n'] ) ? (int) $input['n'] : self::DEFAULT_VARIANTS;
		$n = max( 1, min( self::MAX_VARIANTS, $n ) );

		return [
			'content'         => $content,
			'primary_keyword' => '' === $primary ? null : $primary,
			'h1'              => '' === $h1 ? null : $h1,
			'n'               => $n,
		];
	}

	/**
	 * Assemble the structured message body for the prompter.
	 *
	 * @since 1.2.0
	 *
	 * @param  array{ content: string, primary_keyword: string|null, h1: string|null, n: int }  $normalized  Normalized input.
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
	 * @param  array<string, mixed>                                                             $output      Decoded model output.
	 * @param  array{ content: string, primary_keyword: string|null, h1: string|null, n: int }  $normalized  Normalized input.
	 *
	 * @return array{ variants: array<int, array{ meta_description: string, character_count: int, rationale: string }> }
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

			$description = isset( $variant['meta_description'] ) ? trim( (string) $variant['meta_description'] ) : '';

			if ( '' === $description ) {
				continue;
			}

			// Reject overshoots — truncation would leave a trailing dangling
			// clause that reads worse than dropping the variant outright.
			if ( mb_strlen( $description ) > self::MAX_LENGTH ) {
				continue;
			}

			$key = mb_strtolower( $description );

			if ( null !== $h1Key && $key === $h1Key ) {
				continue;
			}

			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;

			$variants[] = [
				'meta_description' => $description,
				'character_count'  => mb_strlen( $description ),
				'rationale'        => isset( $variant['rationale'] ) ? trim( (string) $variant['rationale'] ) : '',
			];
		}

		if ( count( $variants ) > $normalized['n'] ) {
			$variants = array_slice( $variants, 0, $normalized['n'] );
		}

		return [ 'variants' => $variants ];
	}
}
