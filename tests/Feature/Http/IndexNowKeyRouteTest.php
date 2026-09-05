<?php

/**
 * IndexNow Key Route Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\IndexNowKeyProviderContract;
use ArtisanPackUI\SEO\Http\Controllers\IndexNowKeyController;
use Illuminate\Support\Facades\Route;

function registerIndexNowKeyRoute(): void
{
	Route::get( '{key}.txt', [ IndexNowKeyController::class, 'show' ] )
		->where( 'key', '[A-Za-z0-9]{8,128}' );
}

function bindIndexNowKey( string $key ): void
{
	app()->bind( IndexNowKeyProviderContract::class, fn () => new class( $key ) implements IndexNowKeyProviderContract {
		public function __construct( private string $key )
		{
		}

		public function getKey(): string
		{
			return $this->key;
		}

		public function getKeyLocation( ?array $urlBatch = null ): ?string
		{
			return null;
		}
	} );
}

describe( 'IndexNow key route', function (): void {

	it( 'serves the configured key as text/plain', function (): void {
		registerIndexNowKeyRoute();
		bindIndexNowKey( 'a1b2c3d4e5f6a7b8' );

		$response = $this->get( '/a1b2c3d4e5f6a7b8.txt' );

		$response->assertStatus( 200 )
			->assertHeader( 'Content-Type', 'text/plain; charset=UTF-8' );

		expect( $response->getContent() )->toBe( 'a1b2c3d4e5f6a7b8' );
	} );

	it( 'returns 404 for a key mismatch', function (): void {
		registerIndexNowKeyRoute();
		bindIndexNowKey( 'a1b2c3d4e5f6a7b8' );

		$response = $this->get( '/wrongkey1234abcd.txt' );

		$response->assertStatus( 404 );
	} );

	it( 'returns 404 when no provider is bound', function (): void {
		registerIndexNowKeyRoute();

		$response = $this->get( '/a1b2c3d4e5f6a7b8.txt' );

		$response->assertStatus( 404 );
	} );

} );
