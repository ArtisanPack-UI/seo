<?php

/**
 * TypeScript Types Publish Tests.
 *
 * Feature tests verifying that TypeScript type definitions
 * are publishable and contain the expected type files.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

describe( 'TypeScript Types Publishing', function (): void {

	it( 'registers seo-types as a publishable tag', function (): void {
		$publishableGroups = Illuminate\Support\ServiceProvider::$publishGroups;

		expect( $publishableGroups )->toHaveKey( 'seo-types' );
	} );

	it( 'publishes type files to the correct destination', function (): void {
		$publishableGroups = Illuminate\Support\ServiceProvider::$publishGroups;
		$typesPublishable  = $publishableGroups['seo-types'] ?? [];

		// Find the key that ends with resources/js/types
		$sourceKey = collect( array_keys( $typesPublishable ) )
			->first( fn ( string $key ) => str_ends_with( $key, 'resources/js/types' ) );

		expect( $sourceKey )->not->toBeNull();
		expect( $typesPublishable[ $sourceKey ] )->toBe( resource_path( 'js/types/seo' ) );
	} );

	it( 'includes types in the seo publish group', function (): void {
		$publishableGroups = Illuminate\Support\ServiceProvider::$publishGroups;
		$seoPublishable    = $publishableGroups['seo'] ?? [];

		$sourceKey = collect( array_keys( $seoPublishable ) )
			->first( fn ( string $key ) => str_ends_with( $key, 'resources/js/types' ) );

		expect( $sourceKey )->not->toBeNull();
	} );

	it( 'has all expected type definition files', function (): void {
		$typesDir      = __DIR__ . '/../../resources/js/types';
		$expectedFiles = [
			'index.d.ts',
			'meta-tags.d.ts',
			'open-graph.d.ts',
			'twitter-card.d.ts',
			'hreflang.d.ts',
			'seo-data.d.ts',
			'analysis.d.ts',
			'schema.d.ts',
			'redirect.d.ts',
			'components.d.ts',
		];

		foreach ( $expectedFiles as $file ) {
			expect( file_exists( $typesDir . '/' . $file ) )->toBeTrue();
		}
	} );

	it( 'contains no unexpected files in the types directory', function (): void {
		$typesDir      = __DIR__ . '/../../resources/js/types';
		$expectedFiles = [
			'index.d.ts',
			'meta-tags.d.ts',
			'open-graph.d.ts',
			'twitter-card.d.ts',
			'hreflang.d.ts',
			'seo-data.d.ts',
			'analysis.d.ts',
			'schema.d.ts',
			'redirect.d.ts',
			'components.d.ts',
		];

		$actualFiles = array_map(
			'basename',
			glob( $typesDir . '/*.d.ts' ),
		);

		sort( $actualFiles );
		sort( $expectedFiles );

		expect( $actualFiles )->toBe( $expectedFiles );
	} );

	it( 'type files contain valid TypeScript export declarations', function (): void {
		$typesDir = __DIR__ . '/../../resources/js/types';
		$files    = glob( $typesDir . '/*.d.ts' );

		foreach ( $files as $file ) {
			$content = file_get_contents( $file );

			expect( $content )->toContain( 'export' );
		}
	} );

	it( 'index.d.ts re-exports from all module files', function (): void {
		$indexContent = file_get_contents( __DIR__ . '/../../resources/js/types/index.d.ts' );
		$modules      = [
			'./meta-tags',
			'./open-graph',
			'./twitter-card',
			'./hreflang',
			'./seo-data',
			'./analysis',
			'./schema',
			'./redirect',
			'./components',
		];

		foreach ( $modules as $module ) {
			expect( $indexContent )->toContain( $module );
		}
	} );

	it( 'meta-tags types contain MetaTags interface', function (): void {
		$content = file_get_contents( __DIR__ . '/../../resources/js/types/meta-tags.d.ts' );

		expect( $content )->toContain( 'export interface MetaTags' )
			->and( $content )->toContain( 'export interface MetaTagsResponse' )
			->and( $content )->toContain( 'title: string' )
			->and( $content )->toContain( 'description: string | null' )
			->and( $content )->toContain( 'canonical: string' )
			->and( $content )->toContain( 'robots: string' );
	} );

	it( 'open-graph types contain OpenGraph interface and type union', function (): void {
		$content = file_get_contents( __DIR__ . '/../../resources/js/types/open-graph.d.ts' );

		expect( $content )->toContain( 'export type OpenGraphType' )
			->and( $content )->toContain( 'export interface OpenGraph' )
			->and( $content )->toContain( "'website'" )
			->and( $content )->toContain( "'article'" );
	} );

	it( 'twitter-card types contain TwitterCard interface and type union', function (): void {
		$content = file_get_contents( __DIR__ . '/../../resources/js/types/twitter-card.d.ts' );

		expect( $content )->toContain( 'export type TwitterCardType' )
			->and( $content )->toContain( 'export interface TwitterCard' )
			->and( $content )->toContain( "'summary'" )
			->and( $content )->toContain( "'summary_large_image'" );
	} );

	it( 'analysis types contain all analyzer names', function (): void {
		$content       = file_get_contents( __DIR__ . '/../../resources/js/types/analysis.d.ts' );
		$analyzerNames = [
			'content_length',
			'focus_keyword',
			'heading_structure',
			'image_alt',
			'internal_links',
			'keyword_density',
			'meta_length',
			'readability',
		];

		expect( $content )->toContain( 'export type AnalyzerName' );

		foreach ( $analyzerNames as $name ) {
			expect( $content )->toContain( "'{$name}'" );
		}
	} );

	it( 'schema types contain all 13 schema type names', function (): void {
		$content     = file_get_contents( __DIR__ . '/../../resources/js/types/schema.d.ts' );
		$schemaTypes = [
			'Organization',
			'LocalBusiness',
			'WebSite',
			'WebPage',
			'Article',
			'BlogPosting',
			'Product',
			'Service',
			'Event',
			'FAQPage',
			'BreadcrumbList',
			'Review',
			'AggregateRating',
		];

		expect( $content )->toContain( 'export type SchemaType' );

		foreach ( $schemaTypes as $type ) {
			expect( $content )->toContain( "'{$type}'" );
		}
	} );

	it( 'schema types contain per-type config interfaces', function (): void {
		$content          = file_get_contents( __DIR__ . '/../../resources/js/types/schema.d.ts' );
		$configInterfaces = [
			'ArticleSchemaConfig',
			'ProductSchemaConfig',
			'EventSchemaConfig',
			'FAQPageSchemaConfig',
			'BreadcrumbListSchemaConfig',
			'OrganizationSchemaConfig',
			'LocalBusinessSchemaConfig',
			'WebSiteSchemaConfig',
			'WebPageSchemaConfig',
			'ServiceSchemaConfig',
			'ReviewSchemaConfig',
			'AggregateRatingSchemaConfig',
		];

		foreach ( $configInterfaces as $interface ) {
			expect( $content )->toContain( "export interface {$interface}" );
		}
	} );

	it( 'redirect types contain status code and match type unions', function (): void {
		$content = file_get_contents( __DIR__ . '/../../resources/js/types/redirect.d.ts' );

		expect( $content )->toContain( 'export type RedirectStatusCode' )
			->and( $content )->toContain( '301' )
			->and( $content )->toContain( '302' )
			->and( $content )->toContain( '307' )
			->and( $content )->toContain( '308' )
			->and( $content )->toContain( 'export type RedirectMatchType' )
			->and( $content )->toContain( "'exact'" )
			->and( $content )->toContain( "'regex'" )
			->and( $content )->toContain( "'wildcard'" );
	} );

	it( 'component types contain SeoEditorProps and RedirectManagerProps', function (): void {
		$content = file_get_contents( __DIR__ . '/../../resources/js/types/components.d.ts' );

		expect( $content )->toContain( 'export interface SeoEditorProps' )
			->and( $content )->toContain( 'export interface RedirectManagerProps' )
			->and( $content )->toContain( 'modelType: string' )
			->and( $content )->toContain( 'modelId: number' );
	} );

	it( 'schema types contain SchemaFieldDefinition for dynamic forms', function (): void {
		$content = file_get_contents( __DIR__ . '/../../resources/js/types/schema.d.ts' );

		expect( $content )->toContain( 'export interface SchemaFieldDefinition' )
			->and( $content )->toContain( 'export type SchemaFieldType' )
			->and( $content )->toContain( 'name: string' )
			->and( $content )->toContain( 'required: boolean' )
			->and( $content )->toContain( 'description: string' );
	} );
} );
