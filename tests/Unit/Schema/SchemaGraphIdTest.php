<?php

/**
 * SchemaGraphIdTest.
 *
 * Verifies that every graph node emits a stable `@id` and that
 * cross-references between nodes (Article.publisher -> Organization) use
 * the same identifier so Google can link them.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Schema\Builders\ArticleSchema;
use ArtisanPackUI\SEO\Schema\Builders\LocalBusinessSchema;
use ArtisanPackUI\SEO\Schema\Builders\OrganizationSchema;
use ArtisanPackUI\SEO\Schema\Builders\WebPageSchema;
use ArtisanPackUI\SEO\Schema\Builders\WebsiteSchema;

beforeEach( function (): void {
	config( [
		'app.url'                          => 'https://example.com',
		'seo.schema.organization.name'     => 'Acme',
	] );
} );

describe( 'schema graph @id emission', function (): void {

	it( 'emits a stable @id on Organization', function (): void {
		$schema = ( new OrganizationSchema() )->generate();
		expect( $schema['@id'] ?? null )->toBe( 'http://localhost/#organization' );
	} );

	it( 'emits a stable @id on LocalBusiness distinct from Organization', function (): void {
		$schema = ( new LocalBusinessSchema() )->generate();
		expect( $schema['@id'] ?? null )->toBe( 'http://localhost/#localbusiness' );
	} );

	it( 'emits a stable @id on WebSite', function (): void {
		$schema = ( new WebsiteSchema( [ 'name' => 'Acme', 'url' => 'https://example.com' ] ) )->generate();
		expect( $schema['@id'] ?? null )->toBe( 'http://localhost/#website' );
	} );

	it( 'emits a URL-derived @id on WebPage', function (): void {
		$schema = ( new WebPageSchema( [ 'name' => 'Page', 'url' => 'https://example.com/about' ] ) )->generate();
		expect( $schema['@id'] ?? null )->toBe( 'https://example.com/about#webpage' );
	} );

	it( 'emits a URL-derived @id on Article', function (): void {
		$schema = ( new ArticleSchema( [ 'headline' => 'Hello', 'url' => 'https://example.com/blog/hello' ] ) )->generate();
		expect( $schema['@id'] ?? null )->toBe( 'https://example.com/blog/hello#article' );
	} );

	it( 'Article.publisher references the Organization @id when no explicit publisher is supplied', function (): void {
		$schema  = ( new ArticleSchema( [ 'headline' => 'Hello', 'url' => 'https://example.com/blog/hello' ] ) )->generate();
		$orgId   = ( new OrganizationSchema() )->generate()['@id'] ?? null;

		expect( $schema['publisher']['@id'] ?? null )->toBe( $orgId );
	} );

} );
