<?php

/**
 * MetaLengthAnalyzer Tests.
 *
 * Unit tests for the MetaLengthAnalyzer.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\Analysis\MetaLengthAnalyzer;
use Illuminate\Database\Eloquent\Model;

beforeEach( function (): void {
	$this->analyzer = new MetaLengthAnalyzer();
} );

/**
 * Create a simple test model.
 */
function createMetaLengthTestModel( array $attributes = [] ): Model
{
	return new class( $attributes ) extends Model {
		public string $title = 'Default Title';

		public function __construct( array $attributes = [] )
		{
			parent::__construct();
			foreach ( $attributes as $key => $value ) {
				$this->{$key} = $value;
			}
		}
	};
}

/**
 * Create a mock SeoMeta with title and description.
 */
function createMetaMock( ?string $title = null, ?string $description = null ): SeoMeta
{
	$seoMeta = new SeoMeta();
	if ( null !== $title ) {
		$seoMeta->meta_title = $title;
	}
	if ( null !== $description ) {
		$seoMeta->meta_description = $description;
	}

	return $seoMeta;
}

describe( 'MetaLengthAnalyzer Interface', function (): void {

	it( 'returns correct name', function (): void {
		expect( $this->analyzer->getName() )->toBe( 'meta_length' );
	} );

	it( 'returns correct category', function (): void {
		expect( $this->analyzer->getCategory() )->toBe( 'meta' );
	} );

	it( 'returns correct weight', function (): void {
		expect( $this->analyzer->getWeight() )->toBe( 100 );
	} );

} );

describe( 'MetaLengthAnalyzer Meta Title', function (): void {

	it( 'errors when meta title is missing', function (): void {
		$model  = createMetaLengthTestModel( [ 'title' => '' ] );
		$result = $this->analyzer->analyze( $model, 'Content', null, null );

		$hasError = false;
		foreach ( $result['issues'] as $issue ) {
			if ( 'error' === $issue['type'] && str_contains( $issue['message'], 'title is missing' ) ) {
				$hasError = true;
				break;
			}
		}

		expect( $hasError )->toBeTrue()
			->and( $result['score'] )->toBeLessThan( 100 );
	} );

	it( 'warns when title is too short', function (): void {
		$model   = createMetaLengthTestModel();
		$seoMeta = createMetaMock( 'Short' ); // 5 chars, min is 30
		$result  = $this->analyzer->analyze( $model, 'Content', null, $seoMeta );

		$hasWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'too short' ) && str_contains( $issue['message'], 'title' ) ) {
				$hasWarning = true;
				break;
			}
		}

		expect( $hasWarning )->toBeTrue();
	} );

	it( 'warns when title is too long', function (): void {
		$model   = createMetaLengthTestModel();
		$seoMeta = createMetaMock( str_repeat( 'a', 70 ) ); // 70 chars, max is 60
		$result  = $this->analyzer->analyze( $model, 'Content', null, $seoMeta );

		$hasWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'too long' ) && str_contains( $issue['message'], 'title' ) ) {
				$hasWarning = true;
				break;
			}
		}

		expect( $hasWarning )->toBeTrue();
	} );

	it( 'passes when title length is optimal', function (): void {
		$model   = createMetaLengthTestModel();
		$seoMeta = createMetaMock( 'This is a Perfect Meta Title for SEO Purposes' ); // ~45 chars
		$result  = $this->analyzer->analyze( $model, 'Content', null, $seoMeta );

		$hasTitlePassed = false;
		foreach ( $result['passed'] as $passed ) {
			if ( str_contains( $passed, 'title length is good' ) ) {
				$hasTitlePassed = true;
				break;
			}
		}

		expect( $hasTitlePassed )->toBeTrue();
	} );

} );

describe( 'MetaLengthAnalyzer Meta Description', function (): void {

	it( 'warns when description is missing', function (): void {
		$model   = createMetaLengthTestModel();
		$seoMeta = createMetaMock( 'A Valid Title That Is Long Enough', '' );
		$result  = $this->analyzer->analyze( $model, 'Content', null, $seoMeta );

		$hasWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'description is missing' ) ) {
				$hasWarning = true;
				break;
			}
		}

		expect( $hasWarning )->toBeTrue();
	} );

	it( 'suggests when description is too short', function (): void {
		$model   = createMetaLengthTestModel();
		$seoMeta = createMetaMock( 'A Valid Title That Is Long Enough', 'Short description.' ); // ~18 chars
		$result  = $this->analyzer->analyze( $model, 'Content', null, $seoMeta );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'description is short' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue();
	} );

	it( 'warns when description is too long', function (): void {
		$model   = createMetaLengthTestModel();
		$seoMeta = createMetaMock( 'A Valid Title That Is Long Enough', str_repeat( 'a', 170 ) ); // 170 chars
		$result  = $this->analyzer->analyze( $model, 'Content', null, $seoMeta );

		$hasWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'description is too long' ) ) {
				$hasWarning = true;
				break;
			}
		}

		expect( $hasWarning )->toBeTrue();
	} );

	it( 'passes when description length is optimal', function (): void {
		$model   = createMetaLengthTestModel();
		$seoMeta = createMetaMock(
			'A Valid Title That Is Long Enough',
			'This is a great meta description that provides enough detail about the page content while staying within the recommended character limits for search engines.',
		); // ~155 chars
		$result = $this->analyzer->analyze( $model, 'Content', null, $seoMeta );

		$hasDescPassed = false;
		foreach ( $result['passed'] as $passed ) {
			if ( str_contains( $passed, 'description length is good' ) ) {
				$hasDescPassed = true;
				break;
			}
		}

		expect( $hasDescPassed )->toBeTrue();
	} );

} );

describe( 'MetaLengthAnalyzer Score Calculation', function (): void {

	it( 'returns 100 when both title and description are optimal', function (): void {
		$model   = createMetaLengthTestModel();
		$seoMeta = createMetaMock(
			'This is a Perfect Meta Title for SEO Purposes',
			'This is a great meta description that provides enough detail about the page content while staying within the recommended character limits for search engines.',
		);
		$result = $this->analyzer->analyze( $model, 'Content', null, $seoMeta );

		expect( $result['score'] )->toBe( 100 );
	} );

	it( 'reduces score for missing elements', function (): void {
		$model  = createMetaLengthTestModel( [ 'title' => '' ] );
		$result = $this->analyzer->analyze( $model, 'Content', null, null );

		expect( $result['score'] )->toBeLessThan( 100 );
	} );

} );

describe( 'MetaLengthAnalyzer Details', function (): void {

	it( 'returns detailed length information', function (): void {
		$model   = createMetaLengthTestModel();
		$seoMeta = createMetaMock( 'Test Title', 'Test description' );
		$result  = $this->analyzer->analyze( $model, 'Content', null, $seoMeta );

		expect( $result['details'] )->toHaveKey( 'title_length' )
			->and( $result['details'] )->toHaveKey( 'title_min' )
			->and( $result['details'] )->toHaveKey( 'title_max' )
			->and( $result['details'] )->toHaveKey( 'description_length' )
			->and( $result['details'] )->toHaveKey( 'description_min' )
			->and( $result['details'] )->toHaveKey( 'description_max' );
	} );

} );
