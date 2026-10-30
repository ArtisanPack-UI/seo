<?php

/**
 * SoftwareSourceCode and SoftwareApplication Schema Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.9.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Models\SeoMeta;
use ArtisanPackUI\SEO\Schema\Builders\SoftwareApplicationSchema;
use ArtisanPackUI\SEO\Schema\Builders\SoftwareSourceCodeSchema;
use ArtisanPackUI\SEO\Schema\SchemaFactory;
use ArtisanPackUI\SEO\Services\SchemaService;
use Illuminate\Database\Eloquent\Model;

describe( 'SoftwareSourceCodeSchema', function (): void {

	it( 'returns correct type', function (): void {
		expect( ( new SoftwareSourceCodeSchema() )->getType() )->toBe( 'SoftwareSourceCode' );
	} );

	it( 'generates every supported property', function (): void {
		$schema = ( new SoftwareSourceCodeSchema( [
			'name'                => 'SEO',
			'description'         => 'SEO tools for Laravel.',
			'url'                 => 'https://example.com/packages/seo',
			'codeRepository'      => 'https://github.com/ArtisanPack-UI/seo',
			'programmingLanguage' => 'PHP',
			'runtimePlatform'     => 'PHP 8.2',
			'license'             => 'https://opensource.org/licenses/MIT',
			'version'             => '1.9.0',
		] ) )->generate();

		expect( $schema )->toBe( [
			'@context'            => 'https://schema.org',
			'@type'               => 'SoftwareSourceCode',
			'name'                => 'SEO',
			'description'         => 'SEO tools for Laravel.',
			'url'                 => 'https://example.com/packages/seo',
			'codeRepository'      => 'https://github.com/ArtisanPack-UI/seo',
			'programmingLanguage' => 'PHP',
			'runtimePlatform'     => 'PHP 8.2',
			'license'             => 'https://opensource.org/licenses/MIT',
			'version'             => '1.9.0',
		] );
	} );

	it( 'omits properties with no value', function (): void {
		$schema = ( new SoftwareSourceCodeSchema( [ 'name' => 'SEO', 'description' => '' ] ) )->generate();

		expect( $schema )->toHaveKeys( [ '@type', 'name' ] )
			->not->toHaveKey( 'description' )
			->not->toHaveKey( 'codeRepository' );
	} );

	it( 'defines a field for every property', function (): void {
		$fields = array_column( ( new SoftwareSourceCodeSchema() )->getFieldDefinitions(), 'name' );

		expect( $fields )->toBe( [ 'name', 'description', 'url', 'codeRepository', 'programmingLanguage', 'runtimePlatform', 'license', 'version' ] );
	} );

} );

describe( 'SoftwareApplicationSchema', function (): void {

	it( 'returns correct type', function (): void {
		expect( ( new SoftwareApplicationSchema() )->getType() )->toBe( 'SoftwareApplication' );
	} );

	it( 'generates every supported property', function (): void {
		$schema = ( new SoftwareApplicationSchema( [
			'name'                => 'Keystone',
			'description'         => 'A CMS.',
			'url'                 => 'https://example.com/keystone',
			'applicationCategory' => 'DeveloperApplication',
			'operatingSystem'     => 'Linux, macOS',
			'license'             => 'https://opensource.org/licenses/MIT',
			'version'             => '2.0.0',
		] ) )->generate();

		expect( $schema )->toBe( [
			'@context'            => 'https://schema.org',
			'@type'               => 'SoftwareApplication',
			'name'                => 'Keystone',
			'description'         => 'A CMS.',
			'url'                 => 'https://example.com/keystone',
			'applicationCategory' => 'DeveloperApplication',
			'operatingSystem'     => 'Linux, macOS',
			'license'             => 'https://opensource.org/licenses/MIT',
			'softwareVersion'     => '2.0.0',
		] );
	} );

	it( 'accepts softwareVersion directly', function (): void {
		$schema = ( new SoftwareApplicationSchema( [ 'name' => 'Keystone', 'softwareVersion' => '3.0.0' ] ) )->generate();

		expect( $schema['softwareVersion'] )->toBe( '3.0.0' );
	} );

	it( 'leaves out source code properties', function (): void {
		$schema = ( new SoftwareApplicationSchema( [
			'name'           => 'Keystone',
			'codeRepository' => 'https://github.com/example/keystone',
		] ) )->generate();

		expect( $schema )->not->toHaveKey( 'codeRepository' );
	} );

} );

describe( 'Software schema through the service', function (): void {

	it( 'maps saved schema markup onto the builder', function ( string $type, string $property ): void {
		$seoMeta = new SeoMeta( [
			'schema_type'   => $type,
			'schema_markup' => [ $property => 'mapped-value' ],
		] );

		$schema = ( new SchemaService( new SchemaFactory() ) )->generate( new class extends Model {
			protected $guarded = [];
		}, $seoMeta );

		expect( $schema['@type'] )->toBe( $type )
			->and( $schema[ $property ] )->toBe( 'mapped-value' );
	} )->with( [
		'source code' => [ 'SoftwareSourceCode', 'codeRepository' ],
		'application' => [ 'SoftwareApplication', 'applicationCategory' ],
	] );

} );
