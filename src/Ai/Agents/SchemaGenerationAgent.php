<?php

/**
 * Schema generation agent.
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
 * Suggest a JSON-LD schema type and produce a starter structure.
 *
 * ## Input
 *
 * ```
 * [
 *   'content' => string,   // required
 *   'title'   => string,   // required
 *   'url'     => string,   // optional
 * ]
 * ```
 *
 * ## Output schema
 *
 * ```
 * {
 *   suggested_type:          enum(one of SUPPORTED_TYPES),
 *   confidence:              float(0..1),
 *   jsonld:                  object,
 *   missing_required_fields: string[]
 * }
 * ```
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class SchemaGenerationAgent extends ArtisanPackAgent
{

	/**
	 * Supported schema.org types, mirroring the existing schema builder.
	 *
	 * @since 1.2.0
	 *
	 * @var array<int, string>
	 */
	public const SUPPORTED_TYPES = [
		'Article',
		'BlogPosting',
		'NewsArticle',
		'Product',
		'Event',
		'Organization',
		'Person',
		'LocalBusiness',
		'Recipe',
		'Review',
		'FAQPage',
		'HowTo',
		'VideoObject',
		'WebPage',
	];

	/**
	 * {@inheritDoc}
	 */
	public string $featureKey = 'seo.generate_schema';

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
		$types = implode( ', ', self::SUPPORTED_TYPES );

		return <<<PROMPT
You classify web page content into a JSON-LD schema.org type and produce a starter object.

Allowed schema types (pick exactly one): {$types}.

Requirements:
- `suggested_type` MUST be one of the allowed types verbatim.
- `confidence` is a float between 0 and 1 representing how certain the choice is.
- `jsonld` MUST include `@context: "https://schema.org"` and `@type` matching `suggested_type`. Fill required schema.org properties for the chosen type using the provided content — extract values verbatim; do not invent facts, prices, ratings, dates, or authors.
- Populate `url` in the object when the caller supplies one, and `name`/`headline` from the title.
- List every REQUIRED schema.org property for the chosen type that you could NOT populate from the input in `missing_required_fields`. An empty array means the object is publish-ready.

Return a JSON object with keys: suggested_type (string), confidence (float), jsonld (object), missing_required_fields (array of strings).
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
			'required'             => [ 'suggested_type', 'confidence', 'jsonld', 'missing_required_fields' ],
			'properties'           => [
				'suggested_type'          => [ 'type' => 'string', 'enum' => self::SUPPORTED_TYPES ],
				'confidence'              => [ 'type' => 'number', 'minimum' => 0, 'maximum' => 1 ],
				'jsonld'                  => [ 'type' => 'object' ],
				'missing_required_fields' => [
					'type'  => 'array',
					'items' => [ 'type' => 'string' ],
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
	 * @return array{ content: string, title: string, url: string|null }
	 */
	protected function normalizeInput( mixed $input ): array
	{
		if ( ! is_array( $input ) ) {
			throw FeatureError::forFeature(
				$this->featureKey,
				'input must be an array with `content` and `title` keys.',
			);
		}

		$content = isset( $input['content'] ) && is_string( $input['content'] ) ? trim( $input['content'] ) : '';
		$title   = isset( $input['title'] ) && is_string( $input['title'] ) ? trim( $input['title'] ) : '';

		if ( '' === $content ) {
			throw FeatureError::forFeature( $this->featureKey, '`content` must be a non-empty string.' );
		}

		if ( '' === $title ) {
			throw FeatureError::forFeature( $this->featureKey, '`title` must be a non-empty string.' );
		}

		$url = isset( $input['url'] ) && is_string( $input['url'] ) ? trim( $input['url'] ) : '';

		return [
			'content' => $content,
			'title'   => $title,
			'url'     => '' === $url ? null : $url,
		];
	}

	/**
	 * Assemble the structured message body for the prompter.
	 *
	 * @since 1.2.0
	 *
	 * @param  array{ content: string, title: string, url: string|null }  $normalized  Normalized input.
	 *
	 * @return array<int, array<string, string>>
	 */
	protected function buildMessage( array $normalized ): array
	{
		$parts = [
			[ 'type' => 'text', 'text' => sprintf( 'Title: %s', $normalized['title'] ) ],
		];

		if ( null !== $normalized['url'] ) {
			$parts[] = [ 'type' => 'text', 'text' => sprintf( 'URL: %s', $normalized['url'] ) ];
		}

		$parts[] = [ 'type' => 'text', 'text' => "Page content:\n" . $normalized['content'] ];

		return $parts;
	}

	/**
	 * Enforce output invariants — type in enum, confidence clamped, jsonld shape.
	 *
	 * @since 1.2.0
	 *
	 * @param  array<string, mixed>  $output  Decoded model output.
	 *
	 * @return array{ suggested_type: string, confidence: float, jsonld: array<string, mixed>, missing_required_fields: array<int, string> }
	 */
	protected function validateOutput( array $output ): array
	{
		$type        = isset( $output['suggested_type'] ) ? (string) $output['suggested_type'] : '';
		$typeCoerced = false;

		if ( ! in_array( $type, self::SUPPORTED_TYPES, true ) ) {
			$type        = 'WebPage';
			$typeCoerced = true;
		}

		$confidence = isset( $output['confidence'] ) ? (float) $output['confidence'] : 0.0;
		$confidence = max( 0.0, min( 1.0, $confidence ) );

		$jsonld = is_array( $output['jsonld'] ?? null ) ? $output['jsonld'] : [];

		if ( ! isset( $jsonld['@context'] ) ) {
			$jsonld = [ '@context' => 'https://schema.org' ] + $jsonld;
		}

		// When we coerced `suggested_type` to `WebPage`, force `jsonld['@type']`
		// to match — otherwise callers get a payload where the top-level type
		// disagrees with the JSON-LD type and downstream markup would emit the
		// invalid type verbatim.
		if ( $typeCoerced || ! isset( $jsonld['@type'] ) ) {
			$jsonld['@type'] = $type;
		}

		$missing = [];

		if ( isset( $output['missing_required_fields'] ) && is_array( $output['missing_required_fields'] ) ) {
			foreach ( $output['missing_required_fields'] as $field ) {
				if ( is_string( $field ) && '' !== trim( $field ) ) {
					$missing[] = trim( $field );
				}
			}
		}

		return [
			'suggested_type'          => $type,
			'confidence'              => $confidence,
			'jsonld'                  => $jsonld,
			'missing_required_fields' => $missing,
		];
	}
}
