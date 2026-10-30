<?php

/**
 * Social Image and Article Tag Tests.
 *
 * Verifies `article:*` tags are Open Graph properties on articles only, and
 * that image dimensions and alt text are emitted when known.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.9.0
 */

declare( strict_types=1 );

namespace Tests\Feature\Components\SocialImageAndArticleTags;

use ArtisanPackUI\SEO\Http\Resources\OpenGraphResource;
use ArtisanPackUI\SEO\Http\Resources\TwitterCardResource;
use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Services\MediaLibraryIntegration;
use ArtisanPackUI\SEO\Services\OgImageService;
use ArtisanPackUI\SEO\Services\SocialMetaService;
use ArtisanPackUI\SEO\View\Components\Meta;
use ArtisanPackUI\SEO\View\Components\MetaTags;
use ArtisanPackUI\SEO\View\Components\OpenGraph;
use ArtisanPackUI\SEO\View\Components\TwitterCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

uses( RefreshDatabase::class );

/**
 * Model that inference treats as an article.
 */
class Post extends Model
{
	public $timestamps = false;

	protected $guarded = [];

	protected $casts = [ 'published_at' => 'datetime', 'updated_at' => 'datetime' ];
}

/**
 * Model that inference treats as a website.
 */
class Page extends Model
{
	public $timestamps = false;

	protected $guarded = [];

	protected $casts = [ 'published_at' => 'datetime', 'updated_at' => 'datetime' ];
}

/**
 * Render a component to HTML.
 */
function renderComponent( object $component ): string
{
	return $component->render()->with( $component->data() )->render();
}

/**
 * Build a stand-in media library integration.
 *
 * @param  array{width: int|null, height: int|null, alt: string|null}  $details  The media details.
 */
function fakeMedia( string $url, array $details ): void
{
	app()->instance( MediaLibraryIntegration::class, new class( $url, $details ) extends MediaLibraryIntegration {
		public function __construct( private string $url, private array $details )
		{
		}

		public function getSocialImageUrl( ?int $mediaId ): ?string
		{
			return $this->url;
		}

		public function getSocialImageDetails( ?int $mediaId, ?string $url = null ): array
		{
			return $this->details;
		}
	} );
}

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	config( [
		'seo.open_graph.default_image' => null,
		'seo.twitter.default_image'    => null,
	] );

	$this->dates = [
		'published_at' => Carbon::parse( '2026-10-01 12:00:00', 'UTC' ),
		'updated_at'   => Carbon::parse( '2026-10-02 12:00:00', 'UTC' ),
	];
} );

describe( 'article tags', function (): void {

	it( 'renders article times as properties on articles', function (): void {
		$html = renderComponent( new OpenGraph( model: new Post( $this->dates ) ) );

		expect( $html )->toContain( '<meta property="article:published_time" content="2026-10-01T12:00:00+00:00">' )
			->and( $html )->toContain( '<meta property="article:modified_time" content="2026-10-02T12:00:00+00:00">' )
			->and( $html )->not->toContain( 'name="article:' );
	} );

	it( 'leaves article times off pages that are not articles', function (): void {
		$model = new Page( $this->dates );

		expect( renderComponent( new OpenGraph( model: $model ) ) )->not->toContain( 'article:' )
			->and( renderComponent( new Meta( model: $model ) ) )->not->toContain( 'article:' );
	} );

	it( 'follows a saved og type', function (): void {
		$service = new SocialMetaService();

		expect( $service->generateOpenGraph( new Page( $this->dates ), new SeoMeta( [ 'og_type' => 'article' ] ) )->publishedTime )->not->toBeNull()
			->and( $service->generateOpenGraph( new Post( $this->dates ), new SeoMeta( [ 'og_type' => 'website' ] ) )->publishedTime )->toBeNull();
	} );

	it( 'follows a type override on the component', function (): void {
		expect( renderComponent( new OpenGraph( model: new Page( $this->dates ), type: 'article' ) ) )->toContain( 'article:published_time' )
			->and( renderComponent( new OpenGraph( model: new Post( $this->dates ), type: 'website' ) ) )->not->toContain( 'article:' );
	} );

	it( 'renders article times through the all-in-one component', function (): void {
		$html = renderComponent( new Meta( model: new Post( $this->dates ) ) );

		expect( $html )->toContain( '<meta property="article:modified_time"' )
			->and( $html )->not->toContain( 'name="article:' );
	} );

	it( 'leaves article times out of the basic meta tags', function (): void {
		expect( renderComponent( new MetaTags( model: new Post( $this->dates ) ) ) )->not->toContain( 'article:' );
	} );

	it( 'skips article times that are not dates', function (): void {
		$dto = ( new SocialMetaService() )->generateOpenGraph( new Post() );

		expect( $dto->publishedTime )->toBeNull()
			->and( $dto->modifiedTime )->toBeNull();
	} );

} );

describe( 'social image details', function (): void {

	it( 'emits no details for an image from an unknown source', function (): void {
		$html = renderComponent( new OpenGraph( model: new Page( [ 'featured_image' => 'https://example.com/photo.jpg' ] ) ) );

		expect( $html )->toContain( 'og:image"' )
			->and( $html )->not->toContain( 'og:image:' );
	} );

	it( 'emits generated card dimensions, with the title as alt text', function (): void {
		Storage::fake( 'public' );
		config( [ 'seo.og_image.disk' => 'public', 'seo.og_image.path' => 'og-images' ] );

		$url   = Storage::disk( 'public' )->url( 'og-images/abc123-1200x630.png' );
		$model = new Page( [ 'title' => 'Hello World', 'featured_image' => $url ] );
		$html  = renderComponent( new Meta( model: $model ) );

		expect( $html )->toContain( '<meta property="og:image:width" content="1200">' )
			->and( $html )->toContain( '<meta property="og:image:height" content="630">' )
			->and( $html )->toContain( '<meta property="og:image:alt" content="Hello World">' )
			->and( $html )->toContain( '<meta name="twitter:image:alt" content="Hello World">' );
	} );

	it( 'reports the size a card was rendered at', function (): void {
		Storage::fake( 'public' );
		config( [ 'seo.og_image.disk' => 'public', 'seo.og_image.path' => 'og-images' ] );

		$service = app( OgImageService::class );
		$square  = $service->path( 'Title', null, $service->resolveTemplate( [ 'width' => 1080, 'height' => 1080 ] ) );

		expect( $service->dimensionsForUrl( Storage::disk( 'public' )->url( $square ) ) )->toBe( [ 'width' => 1080, 'height' => 1080 ] );

		config( [ 'seo.og_image.template.width' => 800 ] );

		expect( $service->dimensionsForUrl( Storage::disk( 'public' )->url( $square ) ) )->toBe( [ 'width' => 1080, 'height' => 1080 ] )
			->and( $service->dimensionsForUrl( Storage::disk( 'public' )->url( 'og-images/oldhash.png' ) ) )->toBeNull();
	} );

	it( 'does not treat other images on the disk as generated cards', function (): void {
		Storage::fake( 'public' );
		config( [ 'seo.og_image.disk' => 'public', 'seo.og_image.path' => 'og-images' ] );

		$service = app( OgImageService::class );

		expect( $service->dimensionsForUrl( Storage::disk( 'public' )->url( 'photos/a.png' ) ) )->toBeNull();

		config( [ 'seo.og_image.path' => '' ] );

		expect( $service->dimensionsForUrl( Storage::disk( 'public' )->url( 'photos/a.png' ) ) )->toBeNull();

		config( [ 'seo.og_image.path' => 'og-images', 'seo.og_image.enabled' => false ] );

		expect( $service->dimensionsForUrl( Storage::disk( 'public' )->url( 'og-images/a.png' ) ) )->toBeNull();
	} );

	it( 'emits media library details', function (): void {
		fakeMedia( 'https://example.com/media-social.jpg', [ 'width' => 1200, 'height' => 630, 'alt' => 'A mountain' ] );

		$seoMeta = new SeoMeta( [ 'og_image_id' => 5 ] );
		$service = new SocialMetaService();
		$dto     = $service->generateOpenGraph( new Page(), $seoMeta );
		$card    = $service->generateTwitterCard( new Page(), $seoMeta );

		expect( $dto->image )->toBe( 'https://example.com/media-social.jpg' )
			->and( $dto->imageWidth )->toBe( 1200 )
			->and( $dto->imageHeight )->toBe( 630 )
			->and( $dto->imageAlt )->toBe( 'A mountain' )
			->and( $card->imageAlt )->toBe( 'A mountain' );
	} );

	it( 'reports the right size for social, original and other media URLs', function ( string $url, ?int $width, ?int $height ): void {
		$integration = new class extends MediaLibraryIntegration {
			public function getMedia( int $mediaId ): ?object
			{
				return new class {
					public int $width = 800;

					public int $height = 800;

					public string $alt_text = 'A beach';

					public function url(): string
					{
						return 'https://example.com/summer-social.jpg';
					}
				};
			}
		};

		$details = $integration->getSocialImageDetails( 1, $url );

		expect( $details )->toBe( [ 'width' => $width, 'height' => $height, 'alt' => 'A beach' ] );
	} )->with( [
		'generated social size'             => [ 'https://example.com/summer-social-social.jpg', 1200, 630 ],
		'original named like a social file' => [ 'https://example.com/summer-social.jpg', 800, 800 ],
		'some other size'                   => [ 'https://example.com/summer-large.jpg', null, null ],
		'social only in a parent directory' => [ 'https://example.com/my-social.images/summer-large.jpg', null, null ],
		'social only in the query string'   => [ 'https://example.com/summer-large.jpg?from=a-social.jpg', null, null ],
	] );

	it( 'ignores media details when a different image won', function (): void {
		fakeMedia( 'https://example.com/media-social.jpg', [ 'width' => 1200, 'height' => 630, 'alt' => 'A mountain' ] );

		$details = ( new SocialMetaService() )->resolveImageDetails( 'https://example.com/other.jpg', 5 );

		expect( $details )->toBe( [ 'width' => null, 'height' => null, 'alt' => null ] );
	} );

	it( 'uses a twitter image alt from its own media item', function (): void {
		fakeMedia( 'https://example.com/media-social.jpg', [ 'width' => 800, 'height' => 400, 'alt' => 'A lake' ] );

		$card = ( new SocialMetaService() )->generateTwitterCard( new Page(), new SeoMeta( [ 'twitter_image_id' => 9 ] ) );

		expect( $card->imageAlt )->toBe( 'A lake' );
	} );

	it( 'emits configured default image details', function (): void {
		config( [
			'seo.open_graph.default_image'        => 'https://example.com/default.png',
			'seo.open_graph.default_image_width'  => 1200,
			'seo.open_graph.default_image_height' => 630,
			'seo.open_graph.default_image_alt'    => 'Example logo',
			'seo.twitter.default_image'           => 'https://example.com/default.png',
		] );

		$html = renderComponent( new Meta() );

		expect( $html )->toContain( '<meta property="og:image:width" content="1200">' )
			->and( $html )->toContain( '<meta property="og:image:alt" content="Example logo">' )
			->and( $html )->toContain( '<meta name="twitter:image:alt" content="Example logo">' )
			->and( renderComponent( new OpenGraph() ) )->toContain( 'og:image:height' )
			->and( renderComponent( new TwitterCard() ) )->toContain( 'twitter:image:alt' );
	} );

	it( 'does not reuse details when the image is overridden', function (): void {
		fakeMedia( 'https://example.com/media-social.jpg', [ 'width' => 1200, 'height' => 630, 'alt' => 'A mountain' ] );

		$model = new Page();
		$image = 'https://example.com/override.jpg';

		$component = new Meta( model: $model, image: $image );

		expect( $component->openGraph['imageWidth'] )->toBeNull()
			->and( $component->twitterCard['imageAlt'] )->toBeNull()
			->and( renderComponent( new OpenGraph( model: $model, image: $image ) ) )->not->toContain( 'og:image:' )
			->and( renderComponent( new TwitterCard( model: $model, image: $image ) ) )->not->toContain( 'twitter:image:alt' );
	} );

	it( 'exposes the details through the API resources', function (): void {
		$service = new SocialMetaService();
		$request = new Request();

		config( [
			'seo.open_graph.default_image'     => 'https://example.com/default.png',
			'seo.open_graph.default_image_alt' => 'Example logo',
		] );

		$graph = ( new OpenGraphResource( $service->generateOpenGraph( new Post( $this->dates ) ) ) )->toArray( $request );
		$card  = ( new TwitterCardResource( $service->generateTwitterCard( new Page() ) ) )->toArray( $request );

		expect( $graph['image_alt'] )->toBe( 'Example logo' )
			->and( $graph['published_time'] )->toBe( '2026-10-01T12:00:00+00:00' )
			->and( $card['image_alt'] )->toBe( 'Example logo' );
	} );

} );
