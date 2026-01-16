<?php

/**
 * WebPageSchema.
 *
 * Schema.org WebPage type builder.
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
 * WebPageSchema class.
 *
 * Generates Schema.org WebPage structured data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class WebPageSchema extends AbstractSchema
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
		return 'WebPage';
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

		$schema['name'] = $this->get( 'name', '' );

		$url = $this->get( 'url' );
		if ( null !== $url ) {
			$schema['url'] = $url;
		}

		$description = $this->get( 'description' );
		if ( null !== $description && '' !== $description ) {
			$schema['description'] = $description;
		}

		// Dates
		$datePublished = $this->get( 'datePublished' );
		if ( null !== $datePublished ) {
			$schema['datePublished'] = $datePublished;
		}

		$dateModified = $this->get( 'dateModified' );
		if ( null !== $dateModified ) {
			$schema['dateModified'] = $dateModified;
		}

		// Image
		$image = $this->get( 'image' );
		if ( null !== $image ) {
			$schema['primaryImageOfPage'] = $this->buildImageObject( $image );
		}

		// Author
		$author = $this->get( 'author' );
		if ( null !== $author && is_array( $author ) ) {
			$schema['author'] = $this->buildPerson( $author );
		}

		// Publisher
		$publisher = $this->get( 'publisher' );
		if ( null !== $publisher && is_array( $publisher ) ) {
			$schema['publisher'] = $this->buildOrganization( $publisher );
		}

		// Breadcrumb
		$breadcrumb = $this->get( 'breadcrumb' );
		if ( null !== $breadcrumb && is_array( $breadcrumb ) ) {
			$schema['breadcrumb'] = $breadcrumb;
		}

		// Is part of (parent website)
		$isPartOf = $this->get( 'isPartOf' );
		if ( null !== $isPartOf ) {
			$schema['isPartOf'] = [
				'@type' => 'WebSite',
				'@id'   => $isPartOf,
			];
		}

		// In language
		$inLanguage = $this->get( 'inLanguage' );
		if ( null !== $inLanguage ) {
			$schema['inLanguage'] = $inLanguage;
		}

		return $this->filterEmpty( $schema );
	}
}
