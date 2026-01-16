<?php

/**
 * ImageAltAnalyzer Tests.
 *
 * Unit tests for the ImageAltAnalyzer.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\Analysis\ImageAltAnalyzer;
use Illuminate\Database\Eloquent\Model;

beforeEach( function (): void {
	$this->analyzer = new ImageAltAnalyzer();
} );

/**
 * Create a simple test model.
 */
function createImageAltTestModel(): Model
{
	return new class extends Model {
		public string $title = 'Test Title';
	};
}

describe( 'ImageAltAnalyzer Interface', function (): void {

	it( 'returns correct name', function (): void {
		expect( $this->analyzer->getName() )->toBe( 'image_alt' );
	} );

	it( 'returns correct category', function (): void {
		expect( $this->analyzer->getCategory() )->toBe( 'content' );
	} );

	it( 'returns correct weight', function (): void {
		expect( $this->analyzer->getWeight() )->toBe( 25 );
	} );

} );

describe( 'ImageAltAnalyzer No Images', function (): void {

	it( 'returns perfect score when no images exist', function (): void {
		$model   = createImageAltTestModel();
		$content = '<p>Content without any images.</p>';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['score'] )->toBe( 100 )
			->and( $result['details']['image_count'] )->toBe( 0 )
			->and( $result['passed'] )->toContain( 'No images to analyze.' );
	} );

} );

describe( 'ImageAltAnalyzer Missing Alt', function (): void {

	it( 'errors when images have no alt attribute', function (): void {
		$model   = createImageAltTestModel();
		$content = '<img src="image.jpg"><img src="another.jpg">';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasError = false;
		foreach ( $result['issues'] as $issue ) {
			if ( 'error' === $issue['type'] && str_contains( $issue['message'], 'missing alt' ) ) {
				$hasError = true;
				break;
			}
		}

		expect( $hasError )->toBeTrue()
			->and( $result['details']['images_without_alt'] )->toBe( 2 );
	} );

} );

describe( 'ImageAltAnalyzer Empty Alt', function (): void {

	it( 'suggests adding content to empty alt attributes', function (): void {
		$model   = createImageAltTestModel();
		$content = '<img src="image.jpg" alt="">';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'empty alt text' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue()
			->and( $result['details']['images_with_empty_alt'] )->toBe( 1 );
	} );

} );

describe( 'ImageAltAnalyzer Good Alt Text', function (): void {

	it( 'passes when all images have proper alt text', function (): void {
		$model   = createImageAltTestModel();
		$content = '<img src="image.jpg" alt="A descriptive alt text for this image">';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['score'] )->toBeGreaterThanOrEqual( 90 )
			->and( $result['details']['good_alt_count'] )->toBe( 1 );
	} );

	it( 'counts images with good alt text', function (): void {
		$model   = createImageAltTestModel();
		$content = '<img src="a.jpg" alt="Good description one"><img src="b.jpg" alt="Good description two">';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['details']['images_with_alt'] )->toBe( 2 )
			->and( $result['details']['good_alt_count'] )->toBe( 2 );
	} );

} );

describe( 'ImageAltAnalyzer Alt Text Quality', function (): void {

	it( 'suggests improving very short alt text', function (): void {
		$model   = createImageAltTestModel();
		$content = '<img src="image.jpg" alt="img">';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'very short alt text' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue()
			->and( $result['details']['too_short_alt'] )->toBe( 1 );
	} );

	it( 'suggests shortening very long alt text', function (): void {
		$model    = createImageAltTestModel();
		$longAlt  = str_repeat( 'word ', 30 ); // ~150 chars
		$content  = '<img src="image.jpg" alt="' . $longAlt . '">';
		$result   = $this->analyzer->analyze( $model, $content, null, null );

		$hasSuggestion = false;
		foreach ( $result['suggestions'] as $suggestion ) {
			if ( str_contains( $suggestion['message'], 'very long alt text' ) ) {
				$hasSuggestion = true;
				break;
			}
		}

		expect( $hasSuggestion )->toBeTrue()
			->and( $result['details']['too_long_alt'] )->toBe( 1 );
	} );

	it( 'warns about filename-like alt text', function (): void {
		$model   = createImageAltTestModel();
		$content = '<img src="photo.jpg" alt="IMG_20240101.jpg">';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		$hasWarning = false;
		foreach ( $result['issues'] as $issue ) {
			if ( str_contains( $issue['message'], 'filename' ) ) {
				$hasWarning = true;
				break;
			}
		}

		expect( $hasWarning )->toBeTrue()
			->and( $result['details']['filename_alts'] )->toBe( 1 );
	} );

} );

describe( 'ImageAltAnalyzer Mixed Content', function (): void {

	it( 'handles mixed alt text quality', function (): void {
		$model   = createImageAltTestModel();
		$content = '
            <img src="good.jpg" alt="A well-written description of the image">
            <img src="missing.jpg">
            <img src="empty.jpg" alt="">
            <img src="short.jpg" alt="hi">
        ';
		$result = $this->analyzer->analyze( $model, $content, null, null );

		// images_with_alt counts non-empty alt text (good.jpg + short.jpg = 2)
		// short.jpg has alt="hi" which is not empty, just too short
		expect( $result['details']['image_count'] )->toBe( 4 )
			->and( $result['details']['images_with_alt'] )->toBe( 2 )
			->and( $result['details']['images_without_alt'] )->toBe( 1 )
			->and( $result['details']['images_with_empty_alt'] )->toBe( 1 )
			->and( $result['details']['too_short_alt'] )->toBe( 1 );
	} );

} );

describe( 'ImageAltAnalyzer Score', function (): void {

	it( 'returns high score for proper alt text', function (): void {
		$model   = createImageAltTestModel();
		$content = '
            <img src="a.jpg" alt="Description for image A">
            <img src="b.jpg" alt="Description for image B">
        ';
		$result = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['score'] )->toBeGreaterThanOrEqual( 90 );
	} );

	it( 'reduces score significantly for missing alt', function (): void {
		$model   = createImageAltTestModel();
		$content = '<img src="a.jpg"><img src="b.jpg"><img src="c.jpg">';
		$result  = $this->analyzer->analyze( $model, $content, null, null );

		expect( $result['score'] )->toBeLessThanOrEqual( 70 );
	} );

} );
