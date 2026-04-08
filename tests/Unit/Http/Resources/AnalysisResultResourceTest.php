<?php

/**
 * AnalysisResultResource Tests.
 *
 * Unit tests for AnalysisResultResource.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\AnalysisResultDTO;
use ArtisanPackUI\SEO\Http\Resources\AnalysisResultResource;
use Illuminate\Http\Request;

describe( 'AnalysisResultResource', function (): void {

	it( 'serializes AnalysisResultDTO with scores grouped', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 85,
			readabilityScore: 90,
			keywordScore: 80,
			metaScore: 85,
			contentScore: 82,
			issues: [ [ 'type' => 'warning', 'message' => 'Title could be longer' ] ],
			suggestions: [ [ 'type' => 'info', 'message' => 'Add more internal links' ] ],
			passedChecks: [ 'Meta description present' ],
			focusKeyword: 'test keyword',
			wordCount: 500,
			analyzerResults: [ 'readability' => [ 'score' => 90 ] ],
		);

		$resource = new AnalysisResultResource( $dto );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['overall_score'] )->toBe( 85 )
			->and( $result['grade'] )->toBe( 'good' )
			->and( $result['grade_label'] )->not->toBeEmpty()
			->and( $result['grade_color'] )->toBe( 'green' )
			->and( $result['scores'] )->toBe( [
				'readability' => 90,
				'keyword'     => 80,
				'meta'        => 85,
				'content'     => 82,
			] )
			->and( $result['focus_keyword'] )->toBe( 'test keyword' )
			->and( $result['word_count'] )->toBe( 500 )
			->and( $result['issue_count'] )->toBe( 1 )
			->and( $result['suggestion_count'] )->toBe( 1 )
			->and( $result['passed_count'] )->toBe( 1 )
			->and( $result['analyzer_results'] )->toHaveKey( 'readability' );
	} );

	it( 'returns correct grade for poor score', function (): void {
		$dto = new AnalysisResultDTO(
			overallScore: 30,
			readabilityScore: 20,
			keywordScore: 30,
			metaScore: 40,
			contentScore: 25,
			issues: [],
			suggestions: [],
			passedChecks: [],
			focusKeyword: null,
			wordCount: 100,
		);

		$resource = new AnalysisResultResource( $dto );
		$result   = $resource->toArray( Request::create( '/' ) );

		expect( $result['grade'] )->toBe( 'poor' )
			->and( $result['grade_color'] )->toBe( 'red' );
	} );
} );
