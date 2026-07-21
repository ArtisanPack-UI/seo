<?php

/**
 * VisualEditorIntegration Hook Tests.
 *
 * Verifies the SEO integration subscribes to the renamed
 * `ap.visualEditor.prePublishChecks` filter hook.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.3.0
 */

declare( strict_types=1 );

use ArtisanPackUI\Hooks\Facades\Filter;
use ArtisanPackUI\SEO\Services\AnalysisService;
use ArtisanPackUI\SEO\Services\VisualEditorIntegration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses( RefreshDatabase::class );

beforeEach( function (): void {
	$this->artisan( 'migrate', [ '--path' => realpath( __DIR__ . '/../../../database/migrations' ) ] );

	Filter::removeAll( 'ap.visualEditor.prePublishChecks' );
	Filter::removeAll( 'visual_editor.pre_publish_checks' );
} );

afterEach( function (): void {
	Filter::removeAll( 'ap.visualEditor.prePublishChecks' );
	Filter::removeAll( 'visual_editor.pre_publish_checks' );
} );

function makeVeHookPage(): Model
{
	return new class extends Model {
		protected $guarded = [];
	};
}

describe( 'VisualEditorIntegration hook subscriber', function (): void {

	it( 'subscribes to ap.visualEditor.prePublishChecks and appends SEO checks', function (): void {
		$integration = Mockery::mock( VisualEditorIntegration::class, [ new AnalysisService() ] )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( true );
		$integration->shouldReceive( 'getSeoChecks' )->andReturn( collect( [
			[ 'type' => 'warning', 'message' => 'Test check', 'suggestion' => '' ],
		] ) );

		$integration->registerPrePublishChecks();

		$result = applyFilters(
			'ap.visualEditor.prePublishChecks',
			collect( [ [ 'type' => 'info', 'message' => 'Existing', 'suggestion' => '' ] ] ),
			makeVeHookPage(),
		);

		expect( $result )->toBeInstanceOf( Collection::class )
			->and( $result->count() )->toBe( 2 )
			->and( $result->pluck( 'message' )->all() )->toContain( 'Existing', 'Test check' );
	} );

	it( 'does not subscribe to the deprecated visual_editor.pre_publish_checks hook name', function (): void {
		$integration = Mockery::mock( VisualEditorIntegration::class, [ new AnalysisService() ] )->makePartial();
		$integration->shouldReceive( 'isAvailable' )->andReturn( true );
		$integration->shouldNotReceive( 'getSeoChecks' );

		$integration->registerPrePublishChecks();

		$initial = collect( [ [ 'type' => 'info', 'message' => 'Existing', 'suggestion' => '' ] ] );
		$result  = applyFilters( 'visual_editor.pre_publish_checks', $initial, makeVeHookPage() );

		expect( $result )->toBe( $initial );
	} );

} );
