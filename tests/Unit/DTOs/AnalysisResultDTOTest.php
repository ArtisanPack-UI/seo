<?php

/**
 * AnalysisResultDTO Tests.
 *
 * Unit tests for the AnalysisResultDTO.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\AnalysisResultDTO;

describe( 'AnalysisResultDTO Instantiation', function (): void {

	it( 'can be instantiated with required parameters', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 75,
			readabilityScore: 80,
			keywordScore: 70,
			metaScore: 65,
			contentScore: 85,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: 'test keyword',
			wordCount: 500,
		);

		expect( $dto->overallScore )->toBe( 75 )
			->and( $dto->readabilityScore )->toBe( 80 )
			->and( $dto->keywordScore )->toBe( 70 )
			->and( $dto->metaScore )->toBe( 65 )
			->and( $dto->contentScore )->toBe( 85 )
			->and( $dto->focusKeyword )->toBe( 'test keyword' )
			->and( $dto->wordCount )->toBe( 500 )
			->and( $dto->analyzerResults )->toBe( [] );
	} );

	it( 'can be instantiated with analyzer results', function (): void {
		$analyzerResults = [
			'readability'      => [ 'score' => 80, 'issues' => [] ],
			'keyword_density'  => [ 'score' => 70, 'density' => 1.5 ],
		];

		$dto = new AnalysisResultDTO(
			overallScore: 75,
			readabilityScore: 80,
			keywordScore: 70,
			metaScore: 65,
			contentScore: 85,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 300,
			analyzerResults: $analyzerResults,
		);

		expect( $dto->analyzerResults )->toBe( $analyzerResults );
	} );

	it( 'accepts null focus keyword', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 50,
			readabilityScore: 50,
			keywordScore: 50,
			metaScore: 50,
			contentScore: 50,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 100,
		);

		expect( $dto->focusKeyword )->toBeNull();
	} );

	it( 'is readonly and immutable', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 75,
			readabilityScore: 80,
			keywordScore: 70,
			metaScore: 65,
			contentScore: 85,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: 'test',
			wordCount: 500,
		);

		$reflection = new ReflectionClass( $dto );

		expect( $reflection->isReadOnly() )->toBeTrue();
	} );

} );

describe( 'AnalysisResultDTO Grade Calculation', function (): void {

	it( 'returns good grade for score 80 or higher', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 80,
			readabilityScore: 80,
			keywordScore: 80,
			metaScore: 80,
			contentScore: 80,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 500,
		);

		expect( $dto->getGrade() )->toBe( 'good' );

		$dtoHigher = new AnalysisResultDTO(
			overallScore: 95,
			readabilityScore: 95,
			keywordScore: 95,
			metaScore: 95,
			contentScore: 95,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 500,
		);

		expect( $dtoHigher->getGrade() )->toBe( 'good' );
	} );

	it( 'returns ok grade for score 50-79', function (): void {
		$dto50 = new AnalysisResultDTO(
			overallScore: 50,
			readabilityScore: 50,
			keywordScore: 50,
			metaScore: 50,
			contentScore: 50,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 300,
		);

		expect( $dto50->getGrade() )->toBe( 'ok' );

		$dto79 = new AnalysisResultDTO(
			overallScore: 79,
			readabilityScore: 79,
			keywordScore: 79,
			metaScore: 79,
			contentScore: 79,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 300,
		);

		expect( $dto79->getGrade() )->toBe( 'ok' );
	} );

	it( 'returns poor grade for score below 50', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 49,
			readabilityScore: 49,
			keywordScore: 49,
			metaScore: 49,
			contentScore: 49,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 100,
		);

		expect( $dto->getGrade() )->toBe( 'poor' );

		$dtoZero = new AnalysisResultDTO(
			overallScore: 0,
			readabilityScore: 0,
			keywordScore: 0,
			metaScore: 0,
			contentScore: 0,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 0,
		);

		expect( $dtoZero->getGrade() )->toBe( 'poor' );
	} );

	it( 'returns correct grade color', function (): void {
		$goodDto = new AnalysisResultDTO(
			overallScore: 85,
			readabilityScore: 85,
			keywordScore: 85,
			metaScore: 85,
			contentScore: 85,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 500,
		);

		$okDto = new AnalysisResultDTO(
			overallScore: 65,
			readabilityScore: 65,
			keywordScore: 65,
			metaScore: 65,
			contentScore: 65,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 300,
		);

		$poorDto = new AnalysisResultDTO(
			overallScore: 30,
			readabilityScore: 30,
			keywordScore: 30,
			metaScore: 30,
			contentScore: 30,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 100,
		);

		expect( $goodDto->getGradeColor() )->toBe( 'green' )
			->and( $okDto->getGradeColor() )->toBe( 'yellow' )
			->and( $poorDto->getGradeColor() )->toBe( 'red' );
	} );

} );

describe( 'AnalysisResultDTO Counts', function (): void {

	it( 'counts issues correctly', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 60,
			readabilityScore: 60,
			keywordScore: 60,
			metaScore: 60,
			contentScore: 60,
			issues: [
				[ 'type' => 'error', 'message' => 'Error 1' ],
				[ 'type' => 'warning', 'message' => 'Warning 1' ],
				[ 'type' => 'warning', 'message' => 'Warning 2' ],
			],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 300,
		);

		expect( $dto->getIssueCount() )->toBe( 3 );
	} );

	it( 'counts suggestions correctly', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 60,
			readabilityScore: 60,
			keywordScore: 60,
			metaScore: 60,
			contentScore: 60,
			issues: [],
			suggestions: [
				[ 'type' => 'suggestion', 'message' => 'Suggestion 1' ],
				[ 'type' => 'suggestion', 'message' => 'Suggestion 2' ],
			],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 300,
		);

		expect( $dto->getSuggestionCount() )->toBe( 2 );
	} );

	it( 'counts passed checks correctly', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 80,
			readabilityScore: 80,
			keywordScore: 80,
			metaScore: 80,
			contentScore: 80,
			issues: [],
			suggestions: [],
			passedChecks: [
				'Good readability score',
				'Keyword density is optimal',
				'Meta title length is good',
				'Content length is sufficient',
			],
			focusKeyword: 'test',
			wordCount: 600,
		);

		expect( $dto->getPassedCount() )->toBe( 4 );
	} );

	it( 'calculates total feedback count', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 70,
			readabilityScore: 70,
			keywordScore: 70,
			metaScore: 70,
			contentScore: 70,
			issues: [
				[ 'type' => 'warning', 'message' => 'Warning 1' ],
			],
			suggestions: [
				[ 'type' => 'suggestion', 'message' => 'Suggestion 1' ],
				[ 'type' => 'suggestion', 'message' => 'Suggestion 2' ],
			],
			passedChecks: [
				'Passed 1',
				'Passed 2',
				'Passed 3',
			],
			focusKeyword: null,
			wordCount: 400,
		);

		expect( $dto->getTotalFeedbackCount() )->toBe( 6 );
	} );

	it( 'returns zero for empty counts', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 0,
			readabilityScore: 0,
			keywordScore: 0,
			metaScore: 0,
			contentScore: 0,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 0,
		);

		expect( $dto->getIssueCount() )->toBe( 0 )
			->and( $dto->getSuggestionCount() )->toBe( 0 )
			->and( $dto->getPassedCount() )->toBe( 0 )
			->and( $dto->getTotalFeedbackCount() )->toBe( 0 );
	} );

} );

describe( 'AnalysisResultDTO Issue Filtering', function (): void {

	it( 'filters issues by type', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 60,
			readabilityScore: 60,
			keywordScore: 60,
			metaScore: 60,
			contentScore: 60,
			issues: [
				[ 'type' => 'error', 'message' => 'Error 1' ],
				[ 'type' => 'warning', 'message' => 'Warning 1' ],
				[ 'type' => 'warning', 'message' => 'Warning 2' ],
				[ 'type' => 'error', 'message' => 'Error 2' ],
			],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 300,
		);

		$errors   = $dto->getErrors();
		$warnings = $dto->getWarnings();

		expect( count( $errors ) )->toBe( 2 )
			->and( count( $warnings ) )->toBe( 2 );
	} );

	it( 'returns empty array when no issues of type exist', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 80,
			readabilityScore: 80,
			keywordScore: 80,
			metaScore: 80,
			contentScore: 80,
			issues: [
				[ 'type' => 'warning', 'message' => 'Warning only' ],
			],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 500,
		);

		expect( $dto->getErrors() )->toBe( [] );
	} );

	it( 'checks if has issues', function (): void {
		$withIssues = new AnalysisResultDTO(
			overallScore: 60,
			readabilityScore: 60,
			keywordScore: 60,
			metaScore: 60,
			contentScore: 60,
			issues: [ [ 'type' => 'warning', 'message' => 'Test' ] ],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 300,
		);

		$withoutIssues = new AnalysisResultDTO(
			overallScore: 90,
			readabilityScore: 90,
			keywordScore: 90,
			metaScore: 90,
			contentScore: 90,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 600,
		);

		expect( $withIssues->hasIssues() )->toBeTrue()
			->and( $withoutIssues->hasIssues() )->toBeFalse();
	} );

	it( 'checks if has suggestions', function (): void {
		$withSuggestions = new AnalysisResultDTO(
			overallScore: 70,
			readabilityScore: 70,
			keywordScore: 70,
			metaScore: 70,
			contentScore: 70,
			issues: [],
			suggestions: [ [ 'type' => 'suggestion', 'message' => 'Test' ] ],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 400,
		);

		$withoutSuggestions = new AnalysisResultDTO(
			overallScore: 90,
			readabilityScore: 90,
			keywordScore: 90,
			metaScore: 90,
			contentScore: 90,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 600,
		);

		expect( $withSuggestions->hasSuggestions() )->toBeTrue()
			->and( $withoutSuggestions->hasSuggestions() )->toBeFalse();
	} );

} );

describe( 'AnalysisResultDTO Analyzer Results', function (): void {

	it( 'gets specific analyzer result', function (): void {
		$analyzerResults = [
			'readability'      => [ 'score' => 80, 'flesch' => 65.5 ],
			'keyword_density'  => [ 'score' => 70, 'density' => 1.5 ],
		];

		$dto = new AnalysisResultDTO(
			overallScore: 75,
			readabilityScore: 80,
			keywordScore: 70,
			metaScore: 75,
			contentScore: 75,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: 'test',
			wordCount: 500,
			analyzerResults: $analyzerResults,
		);

		expect( $dto->getAnalyzerResult( 'readability' ) )
			->toBe( [ 'score' => 80, 'flesch' => 65.5 ] );
	} );

	it( 'returns null for non-existent analyzer', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 75,
			readabilityScore: 75,
			keywordScore: 75,
			metaScore: 75,
			contentScore: 75,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 500,
			analyzerResults: [],
		);

		expect( $dto->getAnalyzerResult( 'nonexistent' ) )->toBeNull();
	} );

	it( 'gets category score', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 70,
			readabilityScore: 80,
			keywordScore: 65,
			metaScore: 75,
			contentScore: 60,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 400,
		);

		expect( $dto->getCategoryScore( 'readability' ) )->toBe( 80 )
			->and( $dto->getCategoryScore( 'keyword' ) )->toBe( 65 )
			->and( $dto->getCategoryScore( 'meta' ) )->toBe( 75 )
			->and( $dto->getCategoryScore( 'content' ) )->toBe( 60 )
			->and( $dto->getCategoryScore( 'invalid' ) )->toBe( 0 );
	} );

} );

describe( 'AnalysisResultDTO Serialization', function (): void {

	it( 'converts to array correctly', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 75,
			readabilityScore: 80,
			keywordScore: 70,
			metaScore: 65,
			contentScore: 85,
			issues: [ [ 'type' => 'warning', 'message' => 'Test issue' ] ],
			suggestions: [ [ 'type' => 'suggestion', 'message' => 'Test suggestion' ] ],
			passedChecks: [ 'Test passed' ],
			focusKeyword: 'test keyword',
			wordCount: 500,
		);

		$array = $dto->toArray();

		expect( $array['overall_score'] )->toBe( 75 )
			->and( $array['grade'] )->toBe( 'ok' )
			->and( $array['readability_score'] )->toBe( 80 )
			->and( $array['keyword_score'] )->toBe( 70 )
			->and( $array['meta_score'] )->toBe( 65 )
			->and( $array['content_score'] )->toBe( 85 )
			->and( $array['focus_keyword'] )->toBe( 'test keyword' )
			->and( $array['word_count'] )->toBe( 500 )
			->and( $array['issue_count'] )->toBe( 1 )
			->and( $array['suggestion_count'] )->toBe( 1 )
			->and( $array['passed_count'] )->toBe( 1 );
	} );

	it( 'can be created from array', function (): void {
		$data = [
			'overall_score'     => 75,
			'readability_score' => 80,
			'keyword_score'     => 70,
			'meta_score'        => 65,
			'content_score'     => 85,
			'issues'            => [],
			'suggestions'       => [],
			'passed_checks'     => [],
			'focus_keyword'     => 'test',
			'word_count'        => 500,
			'analyzer_results'  => [ 'readability' => [ 'score' => 80 ] ],
		];

		$dto = AnalysisResultDTO::fromArray( $data );

		expect( $dto->overallScore )->toBe( 75 )
			->and( $dto->readabilityScore )->toBe( 80 )
			->and( $dto->keywordScore )->toBe( 70 )
			->and( $dto->metaScore )->toBe( 65 )
			->and( $dto->contentScore )->toBe( 85 )
			->and( $dto->focusKeyword )->toBe( 'test' )
			->and( $dto->wordCount )->toBe( 500 )
			->and( $dto->analyzerResults )->toBe( [ 'readability' => [ 'score' => 80 ] ] );
	} );

	it( 'uses default values when creating from incomplete array', function (): void {
		$dto = AnalysisResultDTO::fromArray( [] );

		expect( $dto->overallScore )->toBe( 0 )
			->and( $dto->readabilityScore )->toBe( 0 )
			->and( $dto->keywordScore )->toBe( 0 )
			->and( $dto->metaScore )->toBe( 0 )
			->and( $dto->contentScore )->toBe( 0 )
			->and( $dto->issues )->toBe( [] )
			->and( $dto->suggestions )->toBe( [] )
			->and( $dto->passedChecks )->toBe( [] )
			->and( $dto->focusKeyword )->toBeNull()
			->and( $dto->wordCount )->toBe( 0 )
			->and( $dto->analyzerResults )->toBe( [] );
	} );

} );
