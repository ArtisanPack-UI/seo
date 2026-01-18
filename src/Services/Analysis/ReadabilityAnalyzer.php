<?php
/**
 * ReadabilityAnalyzer.
 *
 * Analyzes content readability using Flesch-Kincaid metrics.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 * @copyright  2026 Jacob Martella
 * @license    MIT
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Services\Analysis;

use ArtisanPackUI\SEO\Contracts\AnalyzerContract;
use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

/**
 * ReadabilityAnalyzer class.
 *
 * Calculates Flesch-Kincaid readability score and analyzes
 * sentence and paragraph lengths for optimal readability.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class ReadabilityAnalyzer implements AnalyzerContract
{
	/**
	 * Ideal sentence length in words.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const IDEAL_SENTENCE_LENGTH = 20;

	/**
	 * Maximum recommended sentence length in words.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const MAX_SENTENCE_LENGTH = 25;

	/**
	 * Ideal paragraph length in words.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const IDEAL_PARAGRAPH_LENGTH = 150;

	/**
	 * Maximum recommended paragraph length in words.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const MAX_PARAGRAPH_LENGTH = 200;

	/**
	 * Maximum percentage of long sentences allowed.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const MAX_LONG_SENTENCE_PERCENT = 25;

	/**
	 * Analyze content readability.
	 *
	 * @since 1.0.0
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
		$text        = strip_tags( $content );
		$issues      = [];
		$suggestions = [];
		$passed      = [];

		// Get text components
		$sentences     = $this->getSentences( $text );
		$words         = $this->getWords( $text );
		$syllables     = $this->countSyllables( $text );
		$sentenceCount = count( $sentences );
		$wordCount     = count( $words );

		// Handle empty content
		if ( 0 === $wordCount || 0 === $sentenceCount ) {
			return [
				'score'       => 0,
				'issues'      => [ [ 'type' => 'error', 'message' => __( 'No content to analyze for readability.' ) ] ],
				'suggestions' => [],
				'passed'      => [],
				'details'     => [],
			];
		}

		// Calculate Flesch Reading Ease score (0-100, higher is easier)
		$avgSentenceLength    = $wordCount / $sentenceCount;
		$avgSyllablesPerWord  = $syllables / $wordCount;
		$fleschScore          = 206.835 - ( 1.015 * $avgSentenceLength ) - ( 84.6 * $avgSyllablesPerWord );
		$fleschScore          = max( 0, min( 100, $fleschScore ) );

		// Calculate Flesch-Kincaid Grade Level
		$gradeLevel = ( 0.39 * $avgSentenceLength ) + ( 11.8 * $avgSyllablesPerWord ) - 15.59;

		// Analyze sentence length
		$longSentences       = array_filter( $sentences, fn ( string $s ): bool => str_word_count( $s ) > self::MAX_SENTENCE_LENGTH );
		$longSentencePercent = ( count( $longSentences ) / $sentenceCount ) * 100;

		if ( $longSentencePercent > self::MAX_LONG_SENTENCE_PERCENT ) {
			$issues[] = [
				'type'    => 'warning',
				'message' => sprintf(
					__( '%.0f%% of sentences are too long. Try to keep sentences under %d words.' ),
					$longSentencePercent,
					self::MAX_SENTENCE_LENGTH,
				),
			];
		} else {
			$passed[] = __( 'Sentence length is appropriate.' );
		}

		// Analyze paragraph length
		$paragraphs     = $this->getParagraphs( $content );
		$longParagraphs = array_filter( $paragraphs, fn ( string $p ): bool => str_word_count( strip_tags( $p ) ) > self::MAX_PARAGRAPH_LENGTH );

		if ( count( $longParagraphs ) > 0 ) {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => sprintf(
					__( '%d paragraph(s) are too long. Consider breaking them up for better readability.' ),
					count( $longParagraphs ),
				),
			];
		} else {
			$passed[] = __( 'Paragraph length is appropriate.' );
		}

		// Interpret Flesch score and add feedback
		if ( $fleschScore >= 60 ) {
			$passed[] = sprintf( __( 'Good readability score: %.1f (Easy to read).' ), $fleschScore );
		} elseif ( $fleschScore >= 30 ) {
			$suggestions[] = [
				'type'    => 'suggestion',
				'message' => sprintf(
					__( 'Readability score is %.1f (Fairly difficult). Consider simplifying your content.' ),
					$fleschScore,
				),
			];
		} else {
			$issues[] = [
				'type'    => 'warning',
				'message' => sprintf(
					__( 'Readability score is %.1f (Very difficult). Your content may be hard for many readers.' ),
					$fleschScore,
				),
			];
		}

		// Convert Flesch score to analysis score (0-100)
		$score = (int) round( $fleschScore );

		return [
			'score'       => $score,
			'issues'      => $issues,
			'suggestions' => $suggestions,
			'passed'      => $passed,
			'details'     => [
				'flesch_reading_ease'     => round( $fleschScore, 1 ),
				'flesch_kincaid_grade'    => round( $gradeLevel, 1 ),
				'avg_sentence_length'     => round( $avgSentenceLength, 1 ),
				'avg_syllables_per_word'  => round( $avgSyllablesPerWord, 2 ),
				'long_sentence_percent'   => round( $longSentencePercent, 1 ),
				'sentence_count'          => $sentenceCount,
				'word_count'              => $wordCount,
				'paragraph_count'         => count( $paragraphs ),
				'long_paragraph_count'    => count( $longParagraphs ),
			],
		];
	}

	/**
	 * Get the analyzer name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return 'readability';
	}

	/**
	 * Get the analyzer category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getCategory(): string
	{
		return 'readability';
	}

	/**
	 * Get the analyzer weight.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getWeight(): int
	{
		return 100;
	}

	/**
	 * Extract sentences from text.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $text  The text to parse.
	 *
	 * @return array<int, string>
	 */
	protected function getSentences( string $text ): array
	{
		$sentences = preg_split( '/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY );

		if ( false === $sentences ) {
			return [];
		}

		return array_values( array_filter( array_map( 'trim', $sentences ) ) );
	}

	/**
	 * Extract words from text.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $text  The text to parse.
	 *
	 * @return array<int, string>
	 */
	protected function getWords( string $text ): array
	{
		$words = str_word_count( $text, 1 );

		return is_array( $words ) ? $words : [];
	}

	/**
	 * Extract paragraphs from HTML content.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $content  The HTML content to parse.
	 *
	 * @return array<int, string>
	 */
	protected function getParagraphs( string $content ): array
	{
		// Split by paragraph tags or double newlines
		$paragraphs = preg_split( '/<\/p>|<br\s*\/?>\s*<br\s*\/?>|\n\n/', $content );

		if ( false === $paragraphs ) {
			return [];
		}

		return array_values( array_filter( array_map( 'trim', $paragraphs ) ) );
	}

	/**
	 * Count total syllables in text.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $text  The text to analyze.
	 *
	 * @return int
	 */
	protected function countSyllables( string $text ): int
	{
		$words = $this->getWords( strtolower( $text ) );
		$count = 0;

		foreach ( $words as $word ) {
			$count += $this->countWordSyllables( $word );
		}

		return $count;
	}

	/**
	 * Count syllables in a single word.
	 *
	 * Uses a simplified algorithm that counts vowel groups
	 * and adjusts for common patterns.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $word  The word to analyze.
	 *
	 * @return int
	 */
	protected function countWordSyllables( string $word ): int
	{
		$word = preg_replace( '/[^a-z]/', '', strtolower( $word ) );

		if ( null === $word || '' === $word ) {
			return 0;
		}

		if ( strlen( $word ) <= 3 ) {
			return 1;
		}

		// Count vowel groups
		$count = preg_match_all( '/[aeiouy]+/', $word );

		if ( false === $count ) {
			return 1;
		}

		// Subtract silent e at the end
		if ( preg_match( '/e$/', $word ) && ! preg_match( '/le$/', $word ) ) {
			$count--;
		}

		return max( 1, $count );
	}
}
