<?php
/**
 * AbstractSchema.
 *
 * Base abstract class for Schema.org type builders.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 * @copyright  2026 Jacob Martella
 * @license    MIT
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Schema\Builders;

use ArtisanPackUI\SEO\Contracts\SchemaTypeContract;

/**
 * AbstractSchema class.
 *
 * Provides common functionality for all Schema.org type builders.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
abstract class AbstractSchema implements SchemaTypeContract
{
	/**
	 * The schema data.
	 *
	 * @var array<string, mixed>
	 */
	protected array $data;

	/**
	 * Create a new schema builder instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>  $data  The schema data.
	 */
	public function __construct( array $data = [] )
	{
		$this->data = $data;
	}

	/**
	 * Get the base schema structure.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	protected function getBaseSchema(): array
	{
		return [
			'@context' => 'https://schema.org',
			'@type'    => $this->getType(),
		];
	}

	/**
	 * Filter out null and empty values from schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>  $schema  The schema array.
	 *
	 * @return array<string, mixed>
	 */
	protected function filterEmpty( array $schema ): array
	{
		return array_filter( $schema, static function ( $value ): bool {
			if ( null === $value ) {
				return false;
			}

			if ( '' === $value ) {
				return false;
			}

			if ( is_array( $value ) && empty( $value ) ) {
				return false;
			}

			return true;
		} );
	}

	/**
	 * Get a value from data with fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $key      The key to get.
	 * @param  mixed   $default  The default value.
	 *
	 * @return mixed
	 */
	protected function get( string $key, mixed $default = null ): mixed
	{
		return $this->data[ $key ] ?? $default;
	}

	/**
	 * Build an ImageObject schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null  $url  The image URL.
	 *
	 * @return array<string, string>|null
	 */
	protected function buildImageObject( ?string $url ): ?array
	{
		if ( null === $url || '' === $url ) {
			return null;
		}

		return [
			'@type' => 'ImageObject',
			'url'   => $url,
		];
	}

	/**
	 * Build a Person schema.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>|null  $person  The person data.
	 *
	 * @return array<string, mixed>|null
	 */
	protected function buildPerson( ?array $person ): ?array
	{
		if ( null === $person || empty( $person['name'] ) ) {
			return null;
		}

		$schema = [
			'@type' => 'Person',
			'name'  => $person['name'],
		];

		if ( isset( $person['url'] ) && null !== $person['url'] ) {
			$schema['url'] = $person['url'];
		}

		return $schema;
	}

	/**
	 * Build an Organization schema reference.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>|null  $org  The organization data.
	 *
	 * @return array<string, mixed>|null
	 */
	protected function buildOrganization( ?array $org ): ?array
	{
		if ( null === $org || empty( $org['name'] ) ) {
			return null;
		}

		$schema = [
			'@type' => 'Organization',
			'name'  => $org['name'],
		];

		if ( isset( $org['url'] ) && null !== $org['url'] ) {
			$schema['url'] = $org['url'];
		}

		if ( isset( $org['logo'] ) && null !== $org['logo'] ) {
			$schema['logo'] = $this->buildImageObject( $org['logo'] );
		}

		return $this->filterEmpty( $schema );
	}
}
