<?php

/**
 * llms.txt Route Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use Illuminate\Foundation\Testing\RefreshDatabase;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );
} );

describe( 'llms.txt route', function (): void {

	it( 'returns 200 with text/plain when enabled', function (): void {
		Illuminate\Support\Facades\Route::get(
			trim( (string) config( 'seo.llms_txt.route_path', 'llms.txt' ), '/' ),
			[ ArtisanPackUI\SEO\Http\Controllers\LlmsTxtController::class, 'index' ],
		);

		config( [ 'seo.llms_txt.enabled' => true ] );

		$response = $this->get( '/llms.txt' );

		$response->assertStatus( 200 )
			->assertHeader( 'Content-Type', 'text/plain; charset=UTF-8' );
	} );

	it( 'returns 404 when the manifest itself is disabled', function (): void {
		Illuminate\Support\Facades\Route::get(
			trim( (string) config( 'seo.llms_txt.route_path', 'llms.txt' ), '/' ),
			[ ArtisanPackUI\SEO\Http\Controllers\LlmsTxtController::class, 'index' ],
		);

		config( [ 'seo.llms_txt.enabled' => false ] );

		$response = $this->get( '/llms.txt' );

		$response->assertStatus( 404 );
	} );

} );
