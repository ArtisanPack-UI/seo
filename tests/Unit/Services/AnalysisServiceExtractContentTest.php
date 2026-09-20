<?php

/**
 * AnalysisService extractContent extension seam tests.
 *
 * Covers the SeoAnalyzableContent contract and the
 * `ap.seo.analysisContent` filter that hosts use to surface
 * template-provided markup (e.g. a template-rendered H1) to
 * the SEO analyzers.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.7.0
 */

declare( strict_types=1 );

use ArtisanPackUI\Hooks\Facades\Filter;
use ArtisanPackUI\SEO\Contracts\SeoAnalyzableContent;
use ArtisanPackUI\SEO\Services\AnalysisService;
use Illuminate\Database\Eloquent\Model;

beforeEach( function (): void {
	Filter::removeAll( 'ap.seo.analysisContent' );
	$this->service = new class() extends AnalysisService {
		public function callExtractContent( Model $model ): string
		{
			return $this->extractContent( $model );
		}
	};
} );

afterEach( function (): void {
	Filter::removeAll( 'ap.seo.analysisContent' );
} );

function makeExtractContentModel( array $attributes = [] ): Model
{
	return new class( $attributes ) extends Model {
		protected $guarded = [];

		public function __construct( array $attributes = [] )
		{
			parent::__construct();
			foreach ( $attributes as $key => $value ) {
				$this->setAttribute( $key, $value );
			}
		}
	};
}

describe( 'AnalysisService extractContent()', function (): void {

	it( 'returns the content field when present', function (): void {
		$model = makeExtractContentModel( [ 'content' => '<p>Body copy</p>' ] );

		expect( $this->service->callExtractContent( $model ) )->toBe( '<p>Body copy</p>' );
	} );

	it( 'prefers a SeoAnalyzableContent implementation over the content field', function (): void {
		$model = new class extends Model implements SeoAnalyzableContent {
			protected $guarded = [];

			public function getSeoAnalysisHtml(): string
			{
				return '<h1>Template H1</h1><p>Body</p>';
			}
		};
		$model->setAttribute( 'content', '<p>Body</p>' );

		expect( $this->service->callExtractContent( $model ) )
			->toBe( '<h1>Template H1</h1><p>Body</p>' );
	} );

	it( 'fires the ap.seo.analysisContent filter with content and model', function (): void {
		$captured = [];

		Filter::add( 'ap.seo.analysisContent', function ( string $content, Model $model ) use ( &$captured ) {
			$captured = [ 'content' => $content, 'model' => $model ];
			return $content . '<h1>Template H1</h1>';
		} );

		$model  = makeExtractContentModel( [ 'content' => '<p>Body</p>' ] );
		$result = $this->service->callExtractContent( $model );

		expect( $captured['content'] )->toBe( '<p>Body</p>' )
			->and( $captured['model'] )->toBe( $model )
			->and( $result )->toBe( '<p>Body</p><h1>Template H1</h1>' );
	} );

	it( 'runs the filter after the SeoAnalyzableContent contract', function (): void {
		Filter::add( 'ap.seo.analysisContent', function ( string $content ): string {
			return $content . '<footer>Filter</footer>';
		} );

		$model = new class extends Model implements SeoAnalyzableContent {
			protected $guarded = [];

			public function getSeoAnalysisHtml(): string
			{
				return '<h1>Contract</h1>';
			}
		};

		expect( $this->service->callExtractContent( $model ) )
			->toBe( '<h1>Contract</h1><footer>Filter</footer>' );
	} );

	it( 'falls back to the original content when the filter returns a non-string', function (): void {
		Filter::add( 'ap.seo.analysisContent', fn () => null );

		$model = makeExtractContentModel( [ 'content' => '<p>Body</p>' ] );

		expect( $this->service->callExtractContent( $model ) )->toBe( '<p>Body</p>' );
	} );

	it( 'returns an empty string when no content sources are available', function (): void {
		$model = makeExtractContentModel();

		expect( $this->service->callExtractContent( $model ) )->toBe( '' );
	} );
} );
