<?php

/**
 * SchemaCollector.
 *
 * Request-scoped collector for schema.org entries contributed at render time.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Support;

/**
 * SchemaCollector class.
 *
 * Holds schema entries pushed during a single request so the layout's
 * `<x-seo:schema />` component can emit them alongside model-derived schema.
 * Bound as a singleton in the service provider; a fresh container per request
 * gives each request its own collector instance.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
class SchemaCollector
{
	/**
	 * Collected schema entries for the current request.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected array $entries = [];

	/**
	 * Push a schema entry into the collector.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<string, mixed>  $schema  The schema.org entry to collect.
	 *
	 * @return void
	 */
	public function add( array $schema ): void
	{
		$this->entries[] = $schema;
	}

	/**
	 * Get all collected schema entries without clearing them.
	 *
	 * @since 1.4.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function all(): array
	{
		return $this->entries;
	}

	/**
	 * Return all collected schema entries and clear the collector.
	 *
	 * @since 1.4.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function flush(): array
	{
		$entries       = $this->entries;
		$this->entries = [];

		return $entries;
	}
}
