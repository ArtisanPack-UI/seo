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
	 * Get a human-readable description of this schema type.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return __( 'An article, such as a news article or piece of investigative report' );
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
			[ 'name' => 'headline', 'type' => 'text', 'label' => __( 'Headline' ), 'required' => true, 'description' => __( 'The headline of the article' ) ],
			[ 'name' => 'url', 'type' => 'url', 'label' => __( 'URL' ), 'required' => false, 'description' => __( 'The URL of the article page' ) ],
			[ 'name' => 'description', 'type' => 'textarea', 'label' => __( 'Description' ), 'required' => false, 'description' => __( 'A short description of the article' ) ],
			[ 'name' => 'image', 'type' => 'image', 'label' => __( 'Image' ), 'required' => false, 'description' => __( 'URL of the article image' ) ],
			[ 'name' => 'author', 'type' => 'person', 'label' => __( 'Author' ), 'required' => true, 'description' => __( 'The author of the article' ) ],
			[ 'name' => 'publisher', 'type' => 'organization', 'label' => __( 'Publisher' ), 'required' => false, 'description' => __( 'The publisher of the article (defaults to site organization from config)' ) ],
			[ 'name' => 'datePublished', 'type' => 'datetime', 'label' => __( 'Date Published' ), 'required' => true, 'description' => __( 'The date the article was published' ) ],
			[ 'name' => 'dateModified', 'type' => 'datetime', 'label' => __( 'Date Modified' ), 'required' => false, 'description' => __( 'The date the article was last modified' ) ],
			[ 'name' => 'dateCreated', 'type' => 'datetime', 'label' => __( 'Date Created' ), 'required' => false, 'description' => __( 'The date the article was created' ) ],
			[ 'name' => 'articleBody', 'type' => 'textarea', 'label' => __( 'Article Body' ), 'required' => false, 'description' => __( 'The full text of the article' ) ],
			[ 'name' => 'wordCount', 'type' => 'number', 'label' => __( 'Word Count' ), 'required' => false, 'description' => __( 'The number of words in the article' ) ],
			[ 'name' => 'keywords', 'type' => 'text', 'label' => __( 'Keywords' ), 'required' => false, 'description' => __( 'Keywords or tags for the article' ) ],
			[ 'name' => 'articleSection', 'type' => 'text', 'label' => __( 'Article Section' ), 'required' => false, 'description' => __( 'The section or category of the article' ) ],
			[ 'name' => 'inLanguage', 'type' => 'text', 'label' => __( 'Language' ), 'required' => false, 'description' => __( 'The language of the article (e.g. "en")' ) ],
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
