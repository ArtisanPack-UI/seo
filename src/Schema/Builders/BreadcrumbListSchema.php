<?php

/**
 * BreadcrumbListSchema.
 *
 * Schema.org BreadcrumbList type builder.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Schema\Builders;

use Illuminate\Database\Eloquent\Model;

/**
 * BreadcrumbListSchema class.
 *
 * Generates Schema.org BreadcrumbList structured data for breadcrumb navigation.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class BreadcrumbListSchema extends AbstractSchema
{
	/**
	 * Get the Schema.org type name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getType(): string
	{
		return 'BreadcrumbList';
	}

	/**
	 * Get a human-readable description of this schema type.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return __( 'A breadcrumb trail showing the navigation path to a page' );
	}

	/**
	 * Get the field definitions for this schema type.
	 *
	 * @since 1.1.0
	 *
	 * @return array<int, array{name: string, type: string, label: string, required: bool, description: string}>
	 */
	public function getFieldDefinitions(): array
	{
		return [
			[ 'name' => 'items', 'type' => 'breadcrumb_list', 'label' => __( 'Breadcrumb Items' ), 'required' => true, 'description' => __( 'List of breadcrumb items with name and URL' ) ],
		];
	}

	/**
	 * Generate the schema data array.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|null  $model  Optional model to generate schema for.
	 *
	 * @return array<string, mixed>
	 */
	public function generate( ?Model $model = null ): array
	{
		$schema = $this->getBaseSchema();

		$items = $this->get( 'items', [] );

		if ( is_array( $items ) && ! empty( $items ) ) {
			$schema['itemListElement'] = $this->buildItems( $items );
		}

		return $this->filterEmpty( $schema );
	}

	/**
	 * Build ListItem schema array for breadcrumbs.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<int, array{name: string, url: string}>  $items  The breadcrumb items.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function buildItems( array $items ): array
	{
		$result   = [];
		$position = 1;

		foreach ( $items as $item ) {
			if ( empty( $item['name'] ) ) {
				continue;
			}

			$listItem = [
				'@type'    => 'ListItem',
				'position' => $position,
				'name'     => $item['name'],
			];

			if ( isset( $item['url'] ) && '' !== $item['url'] ) {
				$listItem['item'] = $item['url'];
			}

			$result[] = $listItem;
			++$position;
		}

		return $result;
	}
}
