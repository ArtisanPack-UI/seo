<?php

/**
 * MetaTagService Hook Tests.
 *
 * Tests the `ap.seo.metaTags` filter hook fired during meta tag assembly.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.3.0
 */

declare( strict_types=1 );

use ArtisanPackUI\Hooks\Facades\Filter;
use ArtisanPackUI\SEO\DTOs\MetaTagsDTO;
use ArtisanPackUI\SEO\Services\MetaTagService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

beforeEach( function (): void {
	config( [
		'seo.site.name'                       => 'Test Site',
		'seo.site.separator'                  => ' | ',
		'seo.defaults.robots'                 => 'index, follow',
		'seo.defaults.description_max_length' => 160,
		'app.name'                            => 'Laravel',
	] );

	Filter::removeAll( 'ap.seo.metaTags' );
} );

afterEach( function (): void {
	Filter::removeAll( 'ap.seo.metaTags' );
} );

function makeHookTestModel( array $attributes = [] ): Model
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

describe( 'MetaTagService ap.seo.metaTags filter', function (): void {

	it( 'fires the ap.seo.metaTags filter with tags, subject, and request', function (): void {
		$service = new MetaTagService();
		$model   = makeHookTestModel( [ 'title' => 'Original Title' ] );
		$request = Request::create( '/example' );

		$captured = [];

		addFilter( 'ap.seo.metaTags', function ( array $tags, ?Model $subject, Request $req ) use ( &$captured ): array {
			$captured['tags']    = $tags;
			$captured['subject'] = $subject;
			$captured['request'] = $req;

			return $tags;
		} );

		$service->generate( $model, null, $request );

		expect( $captured )->toHaveKey( 'tags' )
			->and( $captured['tags'] )->toBeArray()
			->and( $captured['tags'] )->toHaveKeys( [ 'title', 'description', 'canonical', 'robots', 'additionalMeta' ] )
			->and( $captured['subject'] )->toBe( $model )
			->and( $captured['request'] )->toBe( $request );
	} );

	it( 'allows the filter to rewrite tags before the DTO is built', function (): void {
		$service = new MetaTagService();
		$model   = makeHookTestModel( [ 'title' => 'Original Title' ] );

		addFilter( 'ap.seo.metaTags', function ( array $tags ): array {
			$tags['title']       = 'Filtered Title';
			$tags['description'] = 'Filtered description';
			$tags['robots']      = 'noindex, nofollow';

			return $tags;
		} );

		$dto = $service->generate( $model );

		expect( $dto )->toBeInstanceOf( MetaTagsDTO::class )
			->and( $dto->title )->toBe( 'Filtered Title' )
			->and( $dto->description )->toBe( 'Filtered description' )
			->and( $dto->robots )->toBe( 'noindex, nofollow' );
	} );

	it( 'falls back to the unfiltered tags when a callback returns a non-array', function (): void {
		$service = new MetaTagService();
		$model   = makeHookTestModel( [ 'title' => 'Original Title' ] );

		addFilter( 'ap.seo.metaTags', function (): ?array {
			return null;
		} );

		$dto = $service->generate( $model );

		expect( $dto )->toBeInstanceOf( MetaTagsDTO::class )
			->and( $dto->title )->toContain( 'Original Title' )
			->and( $dto->robots )->toBe( 'index, follow' );
	} );

	it( 'resolves the current request when no request is provided', function (): void {
		$service = new MetaTagService();
		$model   = makeHookTestModel();

		$seen = null;

		addFilter( 'ap.seo.metaTags', function ( array $tags, ?Model $subject, Request $req ) use ( &$seen ): array {
			$seen = $req;

			return $tags;
		} );

		$service->generate( $model );

		expect( $seen )->toBeInstanceOf( Request::class );
	} );

} );
