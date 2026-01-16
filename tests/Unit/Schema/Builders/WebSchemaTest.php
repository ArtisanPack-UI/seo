<?php

/**
 * WebSite and WebPage Schema Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Schema\Builders\WebPageSchema;
use ArtisanPackUI\SEO\Schema\Builders\WebsiteSchema;

describe( 'WebsiteSchema', function (): void {

	it( 'returns correct type', function (): void {
		$builder = new WebsiteSchema();

		expect( $builder->getType() )->toBe( 'WebSite' );
	} );

	it( 'generates basic website schema', function (): void {
		$builder = new WebsiteSchema( [
			'name' => 'Test Website',
			'url'  => 'https://example.com',
		] );

		$schema = $builder->generate();

		expect( $schema['@context'] )->toBe( 'https://schema.org' )
			->and( $schema['@type'] )->toBe( 'WebSite' )
			->and( $schema['name'] )->toBe( 'Test Website' )
			->and( $schema['url'] )->toBe( 'https://example.com' );
	} );

	it( 'includes description', function (): void {
		$builder = new WebsiteSchema( [
			'name'        => 'Test Website',
			'description' => 'A test website for testing.',
		] );

		$schema = $builder->generate();

		expect( $schema['description'] )->toBe( 'A test website for testing.' );
	} );

	it( 'includes search action', function (): void {
		$builder = new WebsiteSchema( [
			'name'      => 'Test Website',
			'searchUrl' => 'https://example.com/search?q={search_term_string}',
		] );

		$schema = $builder->generate();

		expect( $schema['potentialAction']['@type'] )->toBe( 'SearchAction' )
			->and( $schema['potentialAction']['target']['@type'] )->toBe( 'EntryPoint' );
	} );

	it( 'includes publisher', function (): void {
		$builder = new WebsiteSchema( [
			'name'      => 'Test Website',
			'publisher' => [
				'name' => 'Test Organization',
				'url'  => 'https://example.com',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['publisher']['@type'] )->toBe( 'Organization' )
			->and( $schema['publisher']['name'] )->toBe( 'Test Organization' );
	} );

} );

describe( 'WebPageSchema', function (): void {

	it( 'returns correct type', function (): void {
		$builder = new WebPageSchema();

		expect( $builder->getType() )->toBe( 'WebPage' );
	} );

	it( 'generates basic webpage schema', function (): void {
		$builder = new WebPageSchema( [
			'name' => 'Test Page',
			'url'  => 'https://example.com/test-page',
		] );

		$schema = $builder->generate();

		expect( $schema['@type'] )->toBe( 'WebPage' )
			->and( $schema['name'] )->toBe( 'Test Page' )
			->and( $schema['url'] )->toBe( 'https://example.com/test-page' );
	} );

	it( 'includes dates', function (): void {
		$builder = new WebPageSchema( [
			'name'          => 'Test Page',
			'datePublished' => '2024-01-15T10:00:00+00:00',
			'dateModified'  => '2024-01-20T14:30:00+00:00',
		] );

		$schema = $builder->generate();

		expect( $schema['datePublished'] )->toBe( '2024-01-15T10:00:00+00:00' )
			->and( $schema['dateModified'] )->toBe( '2024-01-20T14:30:00+00:00' );
	} );

	it( 'includes primary image', function (): void {
		$builder = new WebPageSchema( [
			'name'  => 'Test Page',
			'image' => 'https://example.com/image.jpg',
		] );

		$schema = $builder->generate();

		expect( $schema['primaryImageOfPage']['@type'] )->toBe( 'ImageObject' )
			->and( $schema['primaryImageOfPage']['url'] )->toBe( 'https://example.com/image.jpg' );
	} );

	it( 'includes author', function (): void {
		$builder = new WebPageSchema( [
			'name'   => 'Test Page',
			'author' => [
				'name' => 'John Doe',
				'url'  => 'https://example.com/authors/john-doe',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['author']['@type'] )->toBe( 'Person' )
			->and( $schema['author']['name'] )->toBe( 'John Doe' );
	} );

	it( 'includes isPartOf reference', function (): void {
		$builder = new WebPageSchema( [
			'name'     => 'Test Page',
			'isPartOf' => 'https://example.com/#website',
		] );

		$schema = $builder->generate();

		expect( $schema['isPartOf']['@type'] )->toBe( 'WebSite' )
			->and( $schema['isPartOf']['@id'] )->toBe( 'https://example.com/#website' );
	} );

} );
