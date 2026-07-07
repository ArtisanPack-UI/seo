<?php

/**
 * Hreflang suggestion agent.
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
 * Cross-reference hreflang relationships and surface gaps/inconsistencies.
 *
 * ## Input
 *
 * ```
 * [
 *   'pages' => [
 *     {
 *       'url'          => string,
 *       'lang'         => string,
 *       'translations' => [ { 'url' => string, 'lang' => string } ]
 *     }
 *   ]
 * ]
 * ```
 *
 * ## Output schema
 *
 * ```
 * {
 *   issues: [
 *     {
 *       page_url:            string,
 *       issue_type:          enum,
 *       suggested_hreflang:  [ { lang: string, url: string } ]
 *     }
 *   ]
 * }
 * ```
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class HreflangSuggestionAgent extends ArtisanPackAgent
{

	/**
	 * Recognised issue categories.
	 *
	 * @since 1.2.0
	 *
	 * @var array<int, string>
	 */
	public const ISSUE_TYPES = [
		'missing_reciprocal',
		'missing_translation',
		'missing_self',
		'missing_x_default',
		'inconsistent_lang',
	];

	/**
	 * {@inheritDoc}
	 */
	public string $featureKey = 'seo.suggest_hreflang';

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
		$types = implode( ', ', self::ISSUE_TYPES );

		return <<<PROMPT
You cross-reference hreflang relationships across a set of pages and surface where they are missing or inconsistent.

Issue types (use exactly one per issue): {$types}.

Definitions:
- missing_reciprocal: page A links to page B via hreflang, but B does NOT link back to A.
- missing_translation: a page has translations for some but not all languages that appear across the page set.
- missing_self: the page does not include a self-referential hreflang tag pointing to itself.
- missing_x_default: no `x-default` fallback is declared across the language cluster.
- inconsistent_lang: a translation url exists but under a different `lang` code than the target page reports.

Requirements:
- Return one issue per detected problem. Do NOT collapse multiple pages into one issue.
- `suggested_hreflang` contains the full recommended set for `page_url`, INCLUDING the self-referential entry.
- Use BCP 47 language tags exactly as they appeared in the input (do not lowercase or normalize).
- If a page has no issues, do not emit an entry for it.
- If the input contains no issues at all, return `issues: []`.

Return a JSON object with key `issues` (array of objects with page_url, issue_type, suggested_hreflang).
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
			'required'             => [ 'issues' ],
			'properties'           => [
				'issues' => [
					'type'  => 'array',
					'items' => [
						'type'                 => 'object',
						'additionalProperties' => false,
						'required'             => [ 'page_url', 'issue_type', 'suggested_hreflang' ],
						'properties'           => [
							'page_url'           => [ 'type' => 'string' ],
							'issue_type'         => [ 'type' => 'string', 'enum' => self::ISSUE_TYPES ],
							'suggested_hreflang' => [
								'type'  => 'array',
								'items' => [
									'type'                 => 'object',
									'additionalProperties' => false,
									'required'             => [ 'lang', 'url' ],
									'properties'           => [
										'lang' => [ 'type' => 'string' ],
										'url'  => [ 'type' => 'string' ],
									],
								],
							],
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

		if ( [] === $normalized['pages'] ) {
			return [
				'output'        => [ 'issues' => [] ],
				'input_tokens'  => 0,
				'output_tokens' => 0,
			];
		}

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
	 * @return array{ pages: array<int, array{ url: string, lang: string, translations: array<int, array{ url: string, lang: string }> }> }
	 */
	protected function normalizeInput( mixed $input ): array
	{
		if ( ! is_array( $input ) || ! isset( $input['pages'] ) || ! is_array( $input['pages'] ) ) {
			throw FeatureError::forFeature(
				$this->featureKey,
				'input must be an array with a `pages` key.',
			);
		}

		$pages = [];

		foreach ( $input['pages'] as $page ) {
			if ( ! is_array( $page ) ) {
				continue;
			}

			$url  = isset( $page['url'] ) && is_string( $page['url'] ) ? trim( $page['url'] ) : '';
			$lang = isset( $page['lang'] ) && is_string( $page['lang'] ) ? trim( $page['lang'] ) : '';

			if ( '' === $url || '' === $lang ) {
				continue;
			}

			$translations = [];

			if ( isset( $page['translations'] ) && is_array( $page['translations'] ) ) {
				foreach ( $page['translations'] as $translation ) {
					if ( ! is_array( $translation ) ) {
						continue;
					}

					$tUrl  = isset( $translation['url'] ) && is_string( $translation['url'] ) ? trim( $translation['url'] ) : '';
					$tLang = isset( $translation['lang'] ) && is_string( $translation['lang'] ) ? trim( $translation['lang'] ) : '';

					if ( '' === $tUrl || '' === $tLang ) {
						continue;
					}

					$translations[] = [ 'url' => $tUrl, 'lang' => $tLang ];
				}
			}

			$pages[] = [
				'url'          => $url,
				'lang'         => $lang,
				'translations' => $translations,
			];
		}

		return [ 'pages' => $pages ];
	}

	/**
	 * Assemble the structured message body for the prompter.
	 *
	 * @since 1.2.0
	 *
	 * @param  array{ pages: array<int, array<string, mixed>> }  $normalized  Normalized input.
	 *
	 * @return array<int, array<string, string>>
	 */
	protected function buildMessage( array $normalized ): array
	{
		return [
			[
				'type' => 'text',
				'text' => "Pages:\n" . json_encode(
					$normalized['pages'],
					JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
				),
			],
		];
	}

	/**
	 * Enforce output invariants — issue-type enum, hreflang shape.
	 *
	 * @since 1.2.0
	 *
	 * @param  array<string, mixed>  $output  Decoded model output.
	 *
	 * @return array{ issues: array<int, array{ page_url: string, issue_type: string, suggested_hreflang: array<int, array{ lang: string, url: string }> }> }
	 */
	protected function validateOutput( array $output ): array
	{
		$raw    = is_array( $output['issues'] ?? null ) ? $output['issues'] : [];
		$issues = [];

		foreach ( $raw as $issue ) {
			if ( ! is_array( $issue ) ) {
				continue;
			}

			$pageUrl   = isset( $issue['page_url'] ) ? trim( (string) $issue['page_url'] ) : '';
			$issueType = isset( $issue['issue_type'] ) ? (string) $issue['issue_type'] : '';

			if ( '' === $pageUrl || ! in_array( $issueType, self::ISSUE_TYPES, true ) ) {
				continue;
			}

			$suggested = [];

			if ( isset( $issue['suggested_hreflang'] ) && is_array( $issue['suggested_hreflang'] ) ) {
				foreach ( $issue['suggested_hreflang'] as $entry ) {
					if ( ! is_array( $entry ) ) {
						continue;
					}

					$lang = isset( $entry['lang'] ) ? trim( (string) $entry['lang'] ) : '';
					$url  = isset( $entry['url'] ) ? trim( (string) $entry['url'] ) : '';

					if ( '' === $lang || '' === $url ) {
						continue;
					}

					$suggested[] = [ 'lang' => $lang, 'url' => $url ];
				}
			}

			$issues[] = [
				'page_url'           => $pageUrl,
				'issue_type'         => $issueType,
				'suggested_hreflang' => $suggested,
			];
		}

		return [ 'issues' => $issues ];
	}
}
