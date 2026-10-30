<?php

/**
 * SoftwareSourceCodeSchema.
 *
 * Schema.org SoftwareSourceCode type builder.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.9.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Schema\Builders;

use Illuminate\Database\Eloquent\Model;

/**
 * SoftwareSourceCodeSchema class.
 *
 * Generates Schema.org SoftwareSourceCode structured data for code that's
 * published as source, such as a library or package.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.9.0
 */
class SoftwareSourceCodeSchema extends AbstractSchema
{
	/**
	 * Get the Schema.org type name.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function getType(): string
	{
		return 'SoftwareSourceCode';
	}

	/**
	 * Get a human-readable description of this schema type.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return __( 'Source code published for others to use, such as a library or package' );
	}

	/**
	 * Get the field definitions for this schema type.
	 *
	 * @since 1.9.0
	 *
	 * @return array<int, array{name: string, type: string, label: string, required: bool, description: string, options?: array<int, string>}>
	 */
	public function getFieldDefinitions(): array
	{
		return [
			[ 'name' => 'name', 'type' => 'text', 'label' => __( 'Name' ), 'required' => true, 'description' => __( 'The name of the software' ) ],
			[ 'name' => 'description', 'type' => 'textarea', 'label' => __( 'Description' ), 'required' => false, 'description' => __( 'A description of the software' ) ],
			[ 'name' => 'url', 'type' => 'url', 'label' => __( 'URL' ), 'required' => false, 'description' => __( 'URL of the software page' ) ],
			[ 'name' => 'codeRepository', 'type' => 'url', 'label' => __( 'Code Repository' ), 'required' => false, 'description' => __( 'URL of the source code repository' ) ],
			[ 'name' => 'programmingLanguage', 'type' => 'text', 'label' => __( 'Programming Language' ), 'required' => false, 'description' => __( 'The language the code is written in (e.g. "PHP")' ) ],
			[ 'name' => 'runtimePlatform', 'type' => 'text', 'label' => __( 'Runtime Platform' ), 'required' => false, 'description' => __( 'The runtime the code needs (e.g. "PHP 8.2")' ) ],
			[ 'name' => 'license', 'type' => 'url', 'label' => __( 'License' ), 'required' => false, 'description' => __( 'URL of the license' ) ],
			[ 'name' => 'version', 'type' => 'text', 'label' => __( 'Version' ), 'required' => false, 'description' => __( 'The current version' ) ],
		];
	}

	/**
	 * Generate the schema data array.
	 *
	 * @since 1.9.0
	 *
	 * @param  Model|null  $_model  Optional model to generate schema for (unused in this implementation).
	 *
	 * @return array<string, mixed>
	 */
	public function generate( ?Model $_model = null ): array
	{
		$schema = $this->getBaseSchema();

		$schema['name'] = $this->get( 'name', '' );

		foreach ( [ 'description', 'url', 'codeRepository', 'programmingLanguage', 'runtimePlatform', 'license', 'version' ] as $property ) {
			$schema[ $property ] = $this->get( $property );
		}

		return $this->filterEmpty( $schema );
	}
}
