<?php

/**
 * Schema Blade Component.
 *
 * Thin wrapper that delegates JSON-LD assembly and rendering to
 * {@see SchemaService}. All merging, filtering, and encoding lives
 * in the service; the component only forwards constructor props.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\View\Components;

use ArtisanPackUI\SEO\Services\SchemaService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

/**
 * Schema component class.
 *
 * Outputs Schema.org JSON-LD structured data for SEO by delegating
 * to {@see SchemaService::generateGraph()} and
 * {@see SchemaService::renderGraph()}.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class Schema extends Component
{
	/**
	 * Create a new component instance.
	 *
	 * The `$useGraph` prop is accepted for backward compatibility with
	 * the pre-1.4 component API but is no longer consulted: the service
	 * always emits a single script when the graph has one entry and a
	 * `@graph` wrapper when it has more.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|null                                         $model                The model to generate schema from.
	 * @param  array<int, array<string, mixed>>                   $schemas              Custom schema arrays to include.
	 * @param  bool                                               $includeOrganization  Include organization schema.
	 * @param  bool                                               $includeWebsite       Include website schema.
	 * @param  bool                                               $useGraph             Deprecated, no-op — kept for BC.
	 * @param  array<int, array{name: string, url: string}>|null  $breadcrumbs          Breadcrumb items.
	 */
	public function __construct(
		public ?Model $model = null,
		public array $schemas = [],
		public bool $includeOrganization = false,
		public bool $includeWebsite = false,
		public bool $useGraph = true,
		public ?array $breadcrumbs = null,
	) {
	}

	/**
	 * Get the view that represents the component.
	 *
	 * @since 1.0.0
	 *
	 * @return View
	 */
	public function render(): View
	{
		return view( 'seo::components.schema' );
	}

	/**
	 * Get the JSON-LD output for this render.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getJsonLd(): string
	{
		$service = app( SchemaService::class );

		$graph = $service->generateGraph(
			$this->schemas,
			$this->model,
			$this->includeOrganization,
			$this->includeWebsite,
			$this->breadcrumbs,
		);

		return $service->renderGraph( $graph, $this->model );
	}
}
