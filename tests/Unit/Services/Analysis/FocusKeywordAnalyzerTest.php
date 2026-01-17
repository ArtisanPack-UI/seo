<?php

/**
 * FocusKeywordAnalyzer Tests.
 *
 * Unit tests for the FocusKeywordAnalyzer.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\Analysis\FocusKeywordAnalyzer;
use Illuminate\Database\Eloquent\Model;

beforeEach( function (): void {
	$this->analyzer = new FocusKeywordAnalyzer();
} );

/**
 * Create a simple test model with configurable properties.
 */
function createFocusKeywordTestModel( array $attributes = [] ): Model
{
	return new class( $attributes ) extends Model {
		public string $title = 'Test Title';

		public string $slug = 'test-title';

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
 * Create a mock SeoMeta instance.
 */
function createMockSeoMeta( array $attributes = [] ): SeoMeta
{
	$seoMeta = new SeoMeta();
	foreach ( $attributes as $key => $value ) {
		$seoMeta->{$key} = $value;
	}

	return $seoMeta;
}

describe( 'FocusKeywordAnalyzer Interface', function (): void {

	it( 'returns correct name', function (): void {
		expect( $this->analyzer->getName() )->toBe( 'focus_keyword' );
	} );

	it( 'returns correct category', function (): void {
		expect( $this->analyzer->getCategory() )->toBe( 'keyword' );
	} );

	it( 'returns correct weight', function (): void {
		expect( $this->analyzer->getWeight() )->toBe( 50 );
	} );

} );

describe( 'FocusKeywordAnalyzer No Keyword', function (): void {

	it( 'returns suggestion when no focus keyword is set', function (): void {
		$model  = createFocusKeywordTestModel();
		$result = $this->analyzer->analyze( $model, 'Some content.', null, null );

		expect( $result['score'] )->toBe( 0 )
			->and( $result['suggestions'] )->toHaveCount( 1 )
			->and( $result['issues'] )->toHaveCount( 0 );
	} );

} );

describe( 'FocusKeywordAnalyzer Meta Title', function (): void {

	it( 'passes when keyword is in meta title', function (): void {
		$model   = createFocusKeywordTestModel();
		$seoMeta = createMockSeoMeta( [ 'meta_title' => 'Best SEO Tips for Beginners' ] );
		$result  = $this->analyzer->analyze( $model, '<h1>Content</h1>', 'seo', $seoMeta );

		expect( $result['passed'] )->toContain( 'Focus keyword appears in the meta title.' )
			->and( $result['details']['placements']['title'] )->toBeTrue();
	} );

	it( 'suggests adding keyword to meta title', function (): void {
		$model   = createFocusKeywordTestModel();
		$seoMeta = createMockSeoMeta( [ 'meta_title' => 'Tips for Beginners' ] );
		$result  = $this->analyzer->analyze( $model, '<h1>SEO Content</h1>', 'seo', $seoMeta );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'meta title' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue();
	} );

} );

describe( 'FocusKeywordAnalyzer Meta Description', function (): void {

	it( 'passes when keyword is in meta description', function (): void {
		$model   = createFocusKeywordTestModel();
		$seoMeta = createMockSeoMeta( [ 'meta_description' => 'Learn about SEO techniques.' ] );
		$result  = $this->analyzer->analyze( $model, '<h1>SEO Content</h1>', 'seo', $seoMeta );

		expect( $result['passed'] )->toContain( 'Focus keyword appears in the meta description.' )
			->and( $result['details']['placements']['description'] )->toBeTrue();
	} );

} );

describe( 'FocusKeywordAnalyzer URL/Slug', function (): void {

	it( 'passes when keyword is in slug', function (): void {
		$model  = createFocusKeywordTestModel( [ 'slug' => 'best-seo-tips' ] );
		$result = $this->analyzer->analyze( $model, '<h1>SEO Content</h1>', 'seo', null );

		expect( $result['passed'] )->toContain( 'Focus keyword appears in the URL.' )
			->and( $result['details']['placements']['url'] )->toBeTrue();
	} );

	it( 'suggests adding keyword to URL', function (): void {
		$model  = createFocusKeywordTestModel( [ 'slug' => 'best-tips' ] );
		$result = $this->analyzer->analyze( $model, '<h1>SEO Content</h1>', 'seo', null );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'URL slug' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue();
	} );

} );

describe( 'FocusKeywordAnalyzer H1 Heading', function (): void {

	it( 'passes when keyword is in H1', function (): void {
		$model  = createFocusKeywordTestModel();
		$result = $this->analyzer->analyze( $model, '<h1>Learn SEO Today</h1><p>Content here.</p>', 'seo', null );

		expect( $result['passed'] )->toContain( 'Focus keyword appears in H1 heading.' )
			->and( $result['details']['placements']['h1'] )->toBeTrue();
	} );

	it( 'warns when keyword is not in H1', function (): void {
		$model  = createFocusKeywordTestModel();
		$result = $this->analyzer->analyze( $model, '<h1>Learn Marketing Today</h1><p>SEO content here.</p>', 'seo', null );

		$hasWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'H1 heading' ) ) {
				$hasWarning = true;
				break;
			}
		}

		expect( $hasWarning )->toBeTrue()
			->and( $result['details']['placements']['h1'] )->toBeFalse();
	} );

} );

describe( 'FocusKeywordAnalyzer Subheadings', function (): void {

	it( 'passes when keyword is in subheadings', function (): void {
		$model   = createFocusKeywordTestModel();
		$content = '<h1>Main Title</h1><h2>SEO Tips</h2><p>Content.</p>';
		$result  = $this->analyzer->analyze( $model, $content, 'seo', null );

		expect( $result['passed'] )->toContain( 'Focus keyword appears in subheadings.' )
			->and( $result['details']['placements']['subheading'] )->toBeTrue();
	} );

	it( 'detects keyword in H3-H6', function (): void {
		$model   = createFocusKeywordTestModel();
		$content = '<h1>Main</h1><h3>Advanced SEO</h3><p>Content.</p>';
		$result  = $this->analyzer->analyze( $model, $content, 'seo', null );

		expect( $result['details']['placements']['subheading'] )->toBeTrue();
	} );

} );

describe( 'FocusKeywordAnalyzer Image Alt Text', function (): void {

	it( 'passes when keyword is in image alt text', function (): void {
		$model   = createFocusKeywordTestModel();
		$content = '<h1>SEO Guide</h1><img src="image.jpg" alt="SEO diagram">';
		$result  = $this->analyzer->analyze( $model, $content, 'seo', null );

		expect( $result['passed'] )->toContain( 'Focus keyword appears in image alt text.' )
			->and( $result['details']['placements']['alt_text'] )->toBeTrue();
	} );

	it( 'does not penalize pages with no images', function (): void {
		$model   = createFocusKeywordTestModel();
		$content = '<h1>SEO Guide</h1><p>Content without any images.</p>';
		$result  = $this->analyzer->analyze( $model, $content, 'seo', null );

		// When there are no images, the check should pass as not applicable
		expect( $result['passed'] )->toContain( 'No images on page - alt text check not applicable.' )
			->and( $result['details']['placements']['alt_text'] )->toBeTrue();
	} );

	it( 'suggests adding keyword to alt text when images exist without keyword', function (): void {
		$model   = createFocusKeywordTestModel();
		$content = '<h1>SEO Guide</h1><img src="image.jpg" alt="some random description">';
		$result  = $this->analyzer->analyze( $model, $content, 'seo', null );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'image alt text' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue()
			->and( $result['details']['placements']['alt_text'] )->toBeFalse();
	} );

} );

describe( 'FocusKeywordAnalyzer Score Calculation', function (): void {

	it( 'returns 100% when all checks pass', function (): void {
		$model   = createFocusKeywordTestModel( [ 'title' => 'SEO Guide', 'slug' => 'seo-guide' ] );
		$seoMeta = createMockSeoMeta( [
			'meta_title'       => 'Complete SEO Guide',
			'meta_description' => 'Learn SEO techniques.',
		] );
		$content = '<h1>SEO Guide</h1><h2>SEO Tips</h2><img src="image.jpg" alt="SEO">';
		$result  = $this->analyzer->analyze( $model, $content, 'seo', $seoMeta );

		expect( $result['score'] )->toBe( 100 )
			->and( $result['details']['checks_passed'] )->toBe( $result['details']['checks_total'] );
	} );

	it( 'returns proportional score based on checks passed', function (): void {
		$model  = createFocusKeywordTestModel( [ 'slug' => 'other-topic' ] );
		$result = $this->analyzer->analyze( $model, '<h1>No keyword here</h1>', 'seo', null );

		// Score should be less than 100 since not all checks pass
		expect( $result['score'] )->toBeLessThan( 100 )
			->and( $result['details']['checks_passed'] )->toBeLessThan( $result['details']['checks_total'] );
	} );

} );
