<?php
/**
 * ArticleSchema.
 *
 * Schema.org Article type builder.
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
 * ArticleSchema class.
 *
 * Generates Schema.org Article structured data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class ArticleSchema extends AbstractSchema
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
		return 'Article';
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

		// Headline (required)
		$schema['headline'] = $this->get( 'name', $this->get( 'headline', '' ) );

		// URL
		$url = $this->get( 'url' );
		if ( null !== $url ) {
			$schema['mainEntityOfPage'] = [
				'@type' => 'WebPage',
				'@id'   => $url,
			];
		}

		// Description
		$description = $this->get( 'description' );
		if ( null !== $description && '' !== $description ) {
			$schema['description'] = $description;
		}

		// Image (recommended)
		$image = $this->get( 'image' );
		if ( null !== $image ) {
			$schema['image'] = $this->buildImageObject( $image );
		}

		// Author (required)
		$author = $this->get( 'author' );
		if ( null !== $author && is_array( $author ) ) {
			$schema['author'] = $this->buildPerson( $author );
		}

		// Publisher (required)
		$schema['publisher'] = $this->buildPublisher();

		// Dates (required)
		$datePublished = $this->get( 'datePublished' );
		if ( null !== $datePublished ) {
			$schema['datePublished'] = $datePublished;
		}

		$dateModified = $this->get( 'dateModified' );
		if ( null !== $dateModified ) {
			$schema['dateModified'] = $dateModified;
		}

		$dateCreated = $this->get( 'dateCreated' );
		if ( null !== $dateCreated ) {
			$schema['dateCreated'] = $dateCreated;
		}

		// Article body
		$articleBody = $this->get( 'articleBody' );
		if ( null !== $articleBody ) {
			$schema['articleBody'] = $articleBody;
		}

		// Word count
		$wordCount = $this->get( 'wordCount' );
		if ( null !== $wordCount ) {
			$schema['wordCount'] = $wordCount;
		}

		// Keywords
		$keywords = $this->get( 'keywords' );
		if ( null !== $keywords ) {
			$schema['keywords'] = is_array( $keywords ) ? implode( ', ', $keywords ) : $keywords;
		}

		// Article section (category)
		$articleSection = $this->get( 'articleSection' );
		if ( null !== $articleSection ) {
			$schema['articleSection'] = $articleSection;
		}

		// In language
		$inLanguage = $this->get( 'inLanguage' );
		if ( null !== $inLanguage ) {
			$schema['inLanguage'] = $inLanguage;
		}

		return $this->filterEmpty( $schema );
	}

	/**
	 * Build the publisher schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	protected function buildPublisher(): array
	{
		$publisher = $this->get( 'publisher' );

		if ( null !== $publisher && is_array( $publisher ) ) {
			$organization = $this->buildOrganization( $publisher );
			if ( null !== $organization && is_array( $organization ) ) {
				return $organization;
			}
		}

		// Default publisher from config
		$publisherSchema = [
			'@type' => 'Organization',
			'name'  => config( 'seo.schema.organization.name', config( 'app.name', '' ) ),
		];

		// Only add logo if config value is present
		$logo = config( 'seo.schema.organization.logo' );
		if ( null !== $logo ) {
			$publisherSchema['logo'] = $this->buildImageObject( $logo );
		}

		return $this->filterEmpty( $publisherSchema );
	}
}
