<?php
/**
 * WebsiteSchema.
 *
 * Schema.org WebSite type builder.
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

use Illuminate\Database\Eloquent\Model;

/**
 * WebsiteSchema class.
 *
 * Generates Schema.org WebSite structured data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class WebsiteSchema extends AbstractSchema
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
		return 'WebSite';
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

		$schema['name'] = $this->get( 'name', config( 'seo.site.name', config( 'app.name', '' ) ) );
		$schema['url']  = $this->get( 'url', config( 'app.url', '' ) );

		$description = $this->get( 'description', config( 'seo.site.description' ) );
		if ( null !== $description && '' !== $description ) {
			$schema['description'] = $description;
		}

		// Publisher/Organization
		$publisher = $this->get( 'publisher' );
		if ( null !== $publisher && is_array( $publisher ) ) {
			$schema['publisher'] = $this->buildOrganization( $publisher );
		}

		// Search action for sitelinks search box
		$searchUrl = $this->get( 'searchUrl' );
		if ( null !== $searchUrl ) {
			$schema['potentialAction'] = $this->buildSearchAction( $searchUrl );
		}

		// Alternate name
		$alternateName = $this->get( 'alternateName' );
		if ( null !== $alternateName ) {
			$schema['alternateName'] = $alternateName;
		}

		// In language
		$inLanguage = $this->get( 'inLanguage' );
		if ( null !== $inLanguage ) {
			$schema['inLanguage'] = $inLanguage;
		}

		return $this->filterEmpty( $schema );
	}

	/**
	 * Build a SearchAction schema for sitelinks search box.
	 *
	 * Uses PropertyValueSpecification for query-input per schema.org spec.
	 *
	 * @since 1.0.0
	 *
	 * @param  string       $searchUrl  The search URL template.
	 * @param  string|null  $paramName  The query parameter name (default: 'search_term_string').
	 *
	 * @return array<string, mixed>
	 */
	protected function buildSearchAction( string $searchUrl, ?string $paramName = null ): array
	{
		$valueName = $paramName ?? $this->get( 'searchParamName', 'search_term_string' );

		return [
			'@type'       => 'SearchAction',
			'target'      => [
				'@type'       => 'EntryPoint',
				'urlTemplate' => $searchUrl,
			],
			'query-input' => [
				'@type'         => 'PropertyValueSpecification',
				'valueRequired' => true,
				'valueName'     => $valueName,
			],
		];
	}
}
