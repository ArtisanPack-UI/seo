<?php

/**
 * SchemaService Pipeline Tests.
 *
 * Verifies the `generateGraph()` -> `ap.seo.schemaGraph` filter ->
 * `renderGraph()` pipeline: the filter can add or remove entries,
 * apSeoAddSchema() contributions land in the rendered output, and
 * the two render actions fire once each with the expected payload.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\Hooks\Facades\Action;
use ArtisanPackUI\Hooks\Facades\Filter;
use ArtisanPackUI\SEO\Services\SchemaService;
use ArtisanPackUI\SEO\Support\SchemaCollector;
use ArtisanPackUI\SEO\View\Components\Schema;

beforeEach( function (): void {
	config( [
		'seo.schema.organization' => [
			'name' => 'Baseline Org',
			'url'  => 'https://example.com',
		],
	] );

	Filter::removeAll( 'ap.seo.schemaGraph' );
	Action::removeAll( 'ap.seo.schemaRendering' );
	Action::removeAll( 'ap.seo.schemaRendered' );

	app( SchemaCollector::class )->flush();
} );

/**
 * Render the Schema component with the given props.
 *
 * @param  array<string, mixed>  $props
 */
function renderSchemaComponent( array $props = [] ): string
{
	$component = new Schema( ...$props );

	return $component->render()->with( $component->data() )->render();
}

/**
 * Extract and decode the JSON-LD payload from a rendered component.
 */
function decodeJsonLd( string $html ): ?array
{
	if ( 1 !== preg_match( '/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches ) ) {
		return null;
	}

	return json_decode( $matches[1], true );
}

describe( 'ap.seo.schemaGraph filter', function (): void {

	it( 'adds an entry that appears in the rendered output', function (): void {
		Filter::add( 'ap.seo.schemaGraph', function ( array $graph ): array {
			$graph[] = [ '@type' => 'FAQPage', 'name' => 'Injected via filter' ];

			return $graph;
		} );

		$html = renderSchemaComponent( [
			'schemas' => [ [ '@type' => 'Article', 'headline' => 'Base entry' ] ],
		] );

		$json = decodeJsonLd( $html );

		expect( $json )->not->toBeNull();
		expect( $json['@graph'] )->toHaveCount( 2 );

		$types = array_column( $json['@graph'], '@type' );
		expect( $types )->toContain( 'Article', 'FAQPage' );
	} );

	it( 'removes an existing entry via array_filter on @type', function (): void {
		Filter::add( 'ap.seo.schemaGraph', function ( array $graph ): array {
			return array_filter(
				$graph,
				static fn ( array $entry ): bool => ( $entry['@type'] ?? null ) !== 'Organization',
			);
		} );

		$html = renderSchemaComponent( [
			'schemas'             => [ [ '@type' => 'Article', 'headline' => 'Keeper' ] ],
			'includeOrganization' => true,
		] );

		$json = decodeJsonLd( $html );

		expect( $json )->not->toBeNull();
		// Only one entry remained → renderGraph emits it without a @graph wrapper.
		expect( $json['@type'] ?? null )->toBe( 'Article' );
		expect( $json )->not->toHaveKey( '@graph' );
	} );

	it( 'receives the model as the second filter argument', function (): void {
		$captured = null;

		Filter::add( 'ap.seo.schemaGraph', function ( array $graph, $model ) use ( &$captured ): array {
			$captured = $model;

			return $graph;
		} );

		renderSchemaComponent( [
			'schemas' => [ [ '@type' => 'Article' ] ],
		] );

		expect( $captured )->toBeNull();
	} );
} );

describe( 'apSeoAddSchema pipeline integration', function (): void {

	it( 'includes SchemaCollector entries in the rendered output', function (): void {
		apSeoAddSchema( [ '@type' => 'BreadcrumbList', 'name' => 'From collector' ] );

		$html = renderSchemaComponent( [
			'schemas' => [ [ '@type' => 'Article', 'headline' => 'Base entry' ] ],
		] );

		$json = decodeJsonLd( $html );

		expect( $json )->not->toBeNull();

		$types = array_column( $json['@graph'], '@type' );
		expect( $types )->toContain( 'Article', 'BreadcrumbList' );
	} );

	it( 'drains the collector so a second render does not re-emit entries', function (): void {
		apSeoAddSchema( [ '@type' => 'Product', 'name' => 'One-shot' ] );

		renderSchemaComponent();

		expect( app( SchemaCollector::class )->all() )->toBe( [] );
	} );
} );

describe( 'render action lifecycle', function (): void {

	it( 'fires ap.seo.schemaRendering and ap.seo.schemaRendered exactly once with (graph, model)', function (): void {
		$renderingCalls = [];
		$renderedCalls  = [];

		Action::add( 'ap.seo.schemaRendering', function ( array $graph, $model ) use ( &$renderingCalls ): void {
			$renderingCalls[] = [ 'graph' => $graph, 'model' => $model ];
		} );

		Action::add( 'ap.seo.schemaRendered', function ( array $graph, $model ) use ( &$renderedCalls ): void {
			$renderedCalls[] = [ 'graph' => $graph, 'model' => $model ];
		} );

		renderSchemaComponent( [
			'schemas' => [ [ '@type' => 'Article', 'headline' => 'Payload check' ] ],
		] );

		expect( $renderingCalls )->toHaveCount( 1 );
		expect( $renderedCalls )->toHaveCount( 1 );

		expect( $renderingCalls[0]['graph'] )->toHaveCount( 1 );
		expect( $renderingCalls[0]['graph'][0]['@type'] )->toBe( 'Article' );
		expect( $renderingCalls[0]['model'] )->toBeNull();

		expect( $renderedCalls[0]['graph'] )->toEqual( $renderingCalls[0]['graph'] );
		expect( $renderedCalls[0]['model'] )->toBeNull();
	} );

	it( 'does not fire the render actions when the graph is empty', function (): void {
		$fired = 0;

		Action::add( 'ap.seo.schemaRendering', function () use ( &$fired ): void {
			$fired++;
		} );
		Action::add( 'ap.seo.schemaRendered', function () use ( &$fired ): void {
			$fired++;
		} );

		$service = app( SchemaService::class );
		$output  = $service->renderGraph( [], null );

		expect( $output )->toBe( '' );
		expect( $fired )->toBe( 0 );
	} );
} );

describe( 'public component API compatibility', function (): void {

	it( 'still supports the pre-1.4 :schemas + :includeOrganization props', function (): void {
		$html = renderSchemaComponent( [
			'schemas'             => [
				[ '@type' => 'Article', 'headline' => 'Post title' ],
			],
			'includeOrganization' => true,
		] );

		$json = decodeJsonLd( $html );

		expect( $json )->not->toBeNull();
		expect( $json['@graph'] )->toHaveCount( 2 );

		$types = array_column( $json['@graph'], '@type' );
		expect( $types )->toContain( 'Article', 'Organization' );
	} );
} );
