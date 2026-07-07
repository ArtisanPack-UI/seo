<?php

declare( strict_types=1 );

use ArtisanPackUI\Ai\Exceptions\FeatureError;
use ArtisanPackUI\SEO\Ai\Agents\ContentAnalysisAgent;
use Tests\Feature\Ai\AiAgentTestSetup;

beforeEach( function (): void {
	$this->prompter = AiAgentTestSetup::bootstrap( $this->app );
} );

it( 'returns the shaped analysis when the prompter responds', function (): void {
	$dimension = static fn ( int $score ): array => [
		'score'           => $score,
		'recommendations' => [ 'Do the thing' ],
	];

	$this->prompter->queue( [
		'overall_score' => 82,
		'dimensions'    => [
			'keyword_usage'         => $dimension( 90 ),
			'readability'           => $dimension( 75 ),
			'structure'             => $dimension( 80 ),
			'semantic_completeness' => $dimension( 85 ),
		],
	] );

	$result = ContentAnalysisAgent::for( [
		'content'         => 'Sample article body.',
		'primary_keyword' => 'sample',
	] )->run();

	expect( $result['overall_score'] )->toBe( 82 );
	expect( array_keys( $result['dimensions'] ) )->toBe( [
		'keyword_usage',
		'readability',
		'structure',
		'semantic_completeness',
	] );
	expect( $result['dimensions']['keyword_usage']['score'] )->toBe( 90 );
} );

it( 'clamps out-of-range scores', function (): void {
	$this->prompter->queue( [
		'overall_score' => 150,
		'dimensions'    => [
			'keyword_usage'         => [ 'score' => -10, 'recommendations' => [ 'x' ] ],
			'readability'           => [ 'score' => 200, 'recommendations' => [ 'y' ] ],
			'structure'             => [ 'score' => 50, 'recommendations' => [ 'z' ] ],
			'semantic_completeness' => [ 'score' => 50, 'recommendations' => [ 'w' ] ],
		],
	] );

	$result = ContentAnalysisAgent::for( [
		'content'         => 'sample',
		'primary_keyword' => 'sample',
	] )->run();

	expect( $result['overall_score'] )->toBe( 100 );
	expect( $result['dimensions']['keyword_usage']['score'] )->toBe( 0 );
	expect( $result['dimensions']['readability']['score'] )->toBe( 100 );
} );

it( 'fills missing dimensions with empty defaults', function (): void {
	$this->prompter->queue( [
		'overall_score' => 40,
		'dimensions'    => [
			'keyword_usage' => [ 'score' => 40, 'recommendations' => [ 'a' ] ],
		],
	] );

	$result = ContentAnalysisAgent::for( [
		'content'         => 'sample',
		'primary_keyword' => 'sample',
	] )->run();

	expect( $result['dimensions']['readability']['score'] )->toBe( 0 );
	expect( $result['dimensions']['readability']['recommendations'] )->toBe( [] );
} );

it( 'raises FeatureError when primary_keyword is missing', function (): void {
	expect( fn () => ContentAnalysisAgent::for( [ 'content' => 'sample' ] )->run() )
		->toThrow( FeatureError::class );
} );

it( 'unwraps the buggy response where the full payload is wrapped inside dimensions as an object', function (): void {
	// Second-observed shape: `dimensions` is an OBJECT (not a string) that
	// contains the full intended top-level payload one level too deep.
	$innerPayload = [
		'overall_score' => 32,
		'dimensions'    => [
			'keyword_usage'         => [ 'score' => 45, 'recommendations' => [ 'Add keyword to opening sentence' ] ],
			'readability'           => [ 'score' => 55, 'recommendations' => [ 'Expand section 2' ] ],
			'structure'             => [ 'score' => 30, 'recommendations' => [ 'Add H3 headings' ] ],
			'semantic_completeness' => [ 'score' => 25, 'recommendations' => [ 'Cover static and dose retention' ] ],
		],
	];

	$this->prompter->queue( [
		'dimensions' => $innerPayload,
	] );

	$result = ContentAnalysisAgent::for( [
		'content'         => 'sample',
		'primary_keyword' => 'sample',
	] )->run();

	expect( $result['overall_score'] )->toBe( 32 );
	expect( $result['dimensions']['structure']['score'] )->toBe( 30 );
	expect( $result['dimensions']['structure']['recommendations'] )
		->toBe( [ 'Add H3 headings' ] );
} );

it( 'unwraps the buggy response where dimensions is a stringified dimensions-only map with score at top level', function (): void {
	// Third-observed shape: `overall_score` is reported honestly at the top
	// level, but `dimensions` is a stringified JSON of just the dimensions
	// map (no nested `overall_score` inside the string).
	$dimensionsOnly = [
		'keyword_usage'         => [ 'score' => 60, 'recommendations' => [ 'Use keyword in H1' ] ],
		'readability'           => [ 'score' => 40, 'recommendations' => [ 'Shorten paragraph 2' ] ],
		'structure'             => [ 'score' => 35, 'recommendations' => [ 'Add H3 headings' ] ],
		'semantic_completeness' => [ 'score' => 30, 'recommendations' => [ 'Cover static' ] ],
	];

	$this->prompter->queue( [
		'overall_score' => 41,
		'dimensions'    => json_encode( $dimensionsOnly ),
	] );

	$result = ContentAnalysisAgent::for( [
		'content'         => 'sample',
		'primary_keyword' => 'sample',
	] )->run();

	expect( $result['overall_score'] )->toBe( 41 );
	expect( $result['dimensions']['keyword_usage']['score'] )->toBe( 60 );
	expect( $result['dimensions']['structure']['recommendations'] )
		->toBe( [ 'Add H3 headings' ] );
} );

it( 'unwraps the buggy laravel/ai response where dimensions is a stringified full payload', function (): void {
	// This is the exact shape observed in the wild: laravel/ai's structured-
	// output bridge stuffs the entire response as a JSON string into the
	// `dimensions` field. The agent should recover, not score 0/100 across
	// the board.
	$innerPayload = [
		'overall_score' => 42,
		'dimensions'    => [
			'keyword_usage'         => [ 'score' => 55, 'recommendations' => [ 'Reuse the primary keyword in section 1' ] ],
			'readability'           => [ 'score' => 45, 'recommendations' => [ 'Shorten the 32-word sentence' ] ],
			'structure'             => [ 'score' => 30, 'recommendations' => [ 'Convert Section 1 to an H2' ] ],
			'semantic_completeness' => [ 'score' => 25, 'recommendations' => [ 'Cover static and dose retention' ] ],
		],
	];

	$this->prompter->queue( [
		'dimensions' => json_encode( $innerPayload ),
	] );

	$result = ContentAnalysisAgent::for( [
		'content'         => 'sample',
		'primary_keyword' => 'sample',
	] )->run();

	expect( $result['overall_score'] )->toBe( 42 );
	expect( $result['dimensions']['keyword_usage']['score'] )->toBe( 55 );
	expect( $result['dimensions']['keyword_usage']['recommendations'] )
		->toBe( [ 'Reuse the primary keyword in section 1' ] );
	expect( $result['dimensions']['structure']['recommendations'] )
		->toBe( [ 'Convert Section 1 to an H2' ] );
} );
