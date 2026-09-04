<?php

/**
 * AiReadinessAnalyzer.
 *
 * Analyzes content for citation-friendliness and AI answer engine readiness.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Services\Analysis;

use ArtisanPackUI\SEO\Contracts\AnalyzerContract;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

/**
 * AiReadinessAnalyzer class.
 *
 * Evaluates content on signals that improve the chance of being surfaced
 * or cited by AI answer engines (ChatGPT, Perplexity, Google AI Overviews):
 * - Question-style subheadings that mirror likely user queries
 * - A definition-style intro paragraph ("X is Y")
 * - Explicit FAQ presence
 * - A summary-friendly first ~60 words that can stand alone as an answer
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
class AiReadinessAnalyzer implements AnalyzerContract
{
	/**
	 * Maximum word count for the summary-friendly opening check.
	 *
	 * @since 1.4.0
	 *
	 * @var int
	 */
	protected const SUMMARY_WORD_TARGET = 60;

	/**
	 * Question-style opener words for heading detection.
	 *
	 * @since 1.4.0
	 *
	 * @var array<int, string>
	 */
	protected const QUESTION_OPENERS = [
		'what',
		'why',
		'how',
		'when',
		'where',
		'who',
		'which',
		'can',
		'do',
		'does',
		'is',
		'are',
		'should',
		'will',
	];

	/**
	 * Analyze the content for AI readiness signals.
	 *
	 * @since 1.4.0
	 *
	 * @param  Model        $model         The model being analyzed.
	 * @param  string       $content       The content to analyze.
	 * @param  string|null  $focusKeyword  The focus keyword.
	 * @param  SeoMeta|null $seoMeta       The SEO meta data.
	 *
	 * @return array{
	 *     score: int,
	 *     issues: array<int, array{type: string, message: string}>,
	 *     suggestions: array<int, array{type: string, message: string}>,
	 *     passed: array<int, string>,
	 *     details: array<string, mixed>
	 * }
	 */
	public function analyze( Model $model, string $content, ?string $focusKeyword, ?SeoMeta $seoMeta ): array
	{
		$issues       = [];
		$suggestions  = [];
		$passed       = [];
		$checksTotal  = 4;
		$checksPassed = 0;

		$subheadings         = $this->extractSubheadings( $content );
		$questionSubheadings = array_values( array_filter(
			$subheadings,
			fn ( string $heading ): bool => $this->isQuestionHeading( $heading ),
		) );
		$questionCount       = count( $questionSubheadings );

		if ( $questionCount > 0 ) {
			$passed[] = sprintf(
				/* translators: %d: number of question-style subheadings found. */
				__( 'Found %d question-style subheading(s) that mirror likely user queries.' ),
				$questionCount,
			);
			$checksPassed++;
		} else {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => __( 'Add at least one question-style subheading (e.g. "What is X?") so AI engines can match user queries to your content.' ),
			];
		}

		$firstParagraph = $this->extractFirstParagraph( $content );
		$isDefinition   = $this->isDefinitionParagraph( $firstParagraph );

		if ( $isDefinition ) {
			$passed[] = __( 'Intro paragraph opens with a definition-style statement.' );
			$checksPassed++;
		} else {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => __( 'Open with a short definition-style sentence (e.g. "X is a Y that …") so AI can quote it as a direct answer.' ),
			];
		}

		$hasFaq = $this->hasFaqSection( $content, $subheadings );

		if ( $hasFaq ) {
			$passed[] = __( 'Content includes an FAQ section that answer engines can extract.' );
			$checksPassed++;
		} else {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => __( 'Consider adding an FAQ section with concise question and answer pairs.' ),
			];
		}

		$summaryWordCount = $this->countWords( $this->firstWords( $firstParagraph, self::SUMMARY_WORD_TARGET ) );
		$paragraphWords   = $this->countWords( $firstParagraph );
		$isSummaryReady   = $paragraphWords > 0
			&& $paragraphWords <= self::SUMMARY_WORD_TARGET;

		if ( $isSummaryReady ) {
			$passed[] = sprintf(
				/* translators: %1$d: word count of the first paragraph. %2$d: target word count. */
				__( 'First paragraph is %1$d words — within the %2$d-word summary window AI engines prefer.' ),
				$paragraphWords,
				self::SUMMARY_WORD_TARGET,
			);
			$checksPassed++;
		} elseif ( 0 === $paragraphWords ) {
			$issues[] = [
				'type'    => 'warning',
				'message' => __( 'No opening paragraph detected. AI engines rely on a self-contained lead to summarize your page.' ),
			];
		} else {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => sprintf(
					/* translators: %d: target word count for the summary paragraph. */
					__( 'Tighten the first paragraph to roughly %d words so it can stand alone as an AI-generated answer.' ),
					self::SUMMARY_WORD_TARGET,
				),
			];
		}

		$score = (int) round( ( $checksPassed / $checksTotal ) * 100 );

		return [
			'score'       => $score,
			'issues'      => $issues,
			'suggestions' => $suggestions,
			'passed'      => $passed,
			'details'     => [
				'checks_total'          => $checksTotal,
				'checks_passed'         => $checksPassed,
				'question_headings'     => $questionSubheadings,
				'question_count'        => $questionCount,
				'has_definition_intro'  => $isDefinition,
				'has_faq'               => $hasFaq,
				'first_paragraph_words' => $paragraphWords,
				'summary_words'         => $summaryWordCount,
				'summary_target'        => self::SUMMARY_WORD_TARGET,
			],
		];
	}

	/**
	 * Get the analyzer name.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return 'ai_readiness';
	}

	/**
	 * Get the analyzer category.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function getCategory(): string
	{
		return 'content';
	}

	/**
	 * Get the analyzer weight.
	 *
	 * @since 1.4.0
	 *
	 * @return int
	 */
	public function getWeight(): int
	{
		return 20;
	}

	/**
	 * Extract the plain-text of every H2-H6 heading in the content.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $content  The HTML content.
	 *
	 * @return array<int, string>
	 */
	protected function extractSubheadings( string $content ): array
	{
		preg_match_all( '/<h[2-6][^>]*>(.*?)<\/h[2-6]>/si', $content, $matches );

		return array_values( array_filter( array_map(
			fn ( string $heading ): string => trim( strip_tags( $heading ) ),
			$matches[1] ?? [],
		), fn ( string $heading ): bool => '' !== $heading ) );
	}

	/**
	 * Determine whether a heading reads like a user question.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $heading  The heading text.
	 *
	 * @return bool
	 */
	protected function isQuestionHeading( string $heading ): bool
	{
		$normalized = trim( $heading );

		if ( '' === $normalized ) {
			return false;
		}

		if ( str_ends_with( $normalized, '?' ) ) {
			return true;
		}

		$firstWord = strtolower( strtok( $normalized, " \t\n" ) ?: '' );

		return in_array( $firstWord, self::QUESTION_OPENERS, true );
	}

	/**
	 * Extract plain text from the first paragraph of the content.
	 *
	 * Falls back to the first block of text before any heading when
	 * no <p> tag is present.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $content  The HTML content.
	 *
	 * @return string
	 */
	protected function extractFirstParagraph( string $content ): string
	{
		if ( preg_match( '/<p[^>]*>(.*?)<\/p>/si', $content, $paragraphMatch ) ) {
			return trim( strip_tags( $paragraphMatch[1] ) );
		}

		$stripped = trim( strip_tags( $content ) );

		if ( '' === $stripped ) {
			return '';
		}

		$firstBlock = preg_split( '/\R{2,}/', $stripped, 2 )[0] ?? '';

		return trim( $firstBlock );
	}

	/**
	 * Determine whether a paragraph opens with a definition-style statement.
	 *
	 * Looks for the "<subject> is|are|refers to|means" pattern near the start
	 * of the paragraph.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $paragraph  The paragraph text.
	 *
	 * @return bool
	 */
	protected function isDefinitionParagraph( string $paragraph ): bool
	{
		if ( '' === $paragraph ) {
			return false;
		}

		$firstSentence = preg_split( '/(?<=[.!?])\s+/', $paragraph, 2 )[0] ?? $paragraph;

		return 1 === preg_match(
			'/^\s*[A-Z][\w\s\-\'&]{0,80}\s+(is|are|refers to|means|stands for)\s+/i',
			$firstSentence,
		);
	}

	/**
	 * Detect an FAQ section either via a heading or JSON-LD FAQPage schema.
	 *
	 * @since 1.4.0
	 *
	 * @param  string             $content      The full HTML content.
	 * @param  array<int, string> $subheadings  The extracted subheadings.
	 *
	 * @return bool
	 */
	protected function hasFaqSection( string $content, array $subheadings ): bool
	{
		foreach ( $subheadings as $heading ) {
			if ( 1 === preg_match( '/\bfaq(s)?\b|frequently asked questions/i', $heading ) ) {
				return true;
			}
		}

		return 1 === preg_match( '/"@type"\s*:\s*"FAQPage"/i', $content );
	}

	/**
	 * Count words in a plain-text string.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $text  The plain text to count.
	 *
	 * @return int
	 */
	protected function countWords( string $text ): int
	{
		$trimmed = trim( $text );

		if ( '' === $trimmed ) {
			return 0;
		}

		return str_word_count( $trimmed );
	}

	/**
	 * Return the first N words of a plain-text string.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $text   The plain text.
	 * @param  int     $limit  Maximum number of words to return.
	 *
	 * @return string
	 */
	protected function firstWords( string $text, int $limit ): string
	{
		$trimmed = trim( $text );

		if ( '' === $trimmed || $limit <= 0 ) {
			return '';
		}

		$words = preg_split( '/\s+/', $trimmed ) ?: [];

		return implode( ' ', array_slice( $words, 0, $limit ) );
	}
}
