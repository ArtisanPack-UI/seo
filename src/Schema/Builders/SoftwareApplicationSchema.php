<?php

/**
 * SoftwareApplicationSchema.
 *
 * Schema.org SoftwareApplication type builder.
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
 * SoftwareApplicationSchema class.
 *
 * Generates Schema.org SoftwareApplication structured data for software
 * people install or use. `codeRepository`, `programmingLanguage` and
 * `runtimePlatform` aren't Schema.org properties of this type, so they
 * belong on {@see SoftwareSourceCodeSchema}.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.9.0
 */
class SoftwareApplicationSchema extends AbstractSchema
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
		return 'SoftwareApplication';
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
		return __( 'An application people install or use, such as a desktop, mobile, or web app' );
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
			[ 'name' => 'name', 'type' => 'text', 'label' => __( 'Name' ), 'required' => true, 'description' => __( 'The name of the application' ) ],
			[ 'name' => 'description', 'type' => 'textarea', 'label' => __( 'Description' ), 'required' => false, 'description' => __( 'A description of the application' ) ],
			[ 'name' => 'url', 'type' => 'url', 'label' => __( 'URL' ), 'required' => false, 'description' => __( 'URL of the application page' ) ],
			[ 'name' => 'applicationCategory', 'type' => 'text', 'label' => __( 'Application Category' ), 'required' => false, 'description' => __( 'The type of application (e.g. "DeveloperApplication")' ) ],
			[ 'name' => 'operatingSystem', 'type' => 'text', 'label' => __( 'Operating System' ), 'required' => false, 'description' => __( 'The operating systems it runs on (e.g. "Windows, macOS")' ) ],
			[ 'name' => 'license', 'type' => 'url', 'label' => __( 'License' ), 'required' => false, 'description' => __( 'URL of the license' ) ],
			[ 'name' => 'version', 'type' => 'text', 'label' => __( 'Version' ), 'required' => false, 'description' => __( 'The current version' ) ],
		];
	}

	/**
	 * Generate the schema data array.
	 *
	 * `version` is emitted as `softwareVersion`, the property Schema.org
	 * defines for this type.
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

		foreach ( [ 'description', 'url', 'applicationCategory', 'operatingSystem', 'license' ] as $property ) {
			$schema[ $property ] = $this->get( $property );
		}

		$schema['softwareVersion'] = $this->get( 'softwareVersion' ) ?: $this->get( 'version' );

		return $this->filterEmpty( $schema );
	}
}
