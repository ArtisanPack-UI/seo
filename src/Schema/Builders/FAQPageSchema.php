<?php

/**
 * FAQPageSchema.
 *
 * Schema.org FAQPage type builder.
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
 * FAQPageSchema class.
 *
 * Generates Schema.org FAQPage structured data for FAQ rich results.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class FAQPageSchema extends AbstractSchema
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
		return 'FAQPage';
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
		return __( 'A page containing a list of frequently asked questions and answers' );
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
			[ 'name' => 'name', 'type' => 'text', 'label' => __( 'Page Name' ), 'required' => false, 'description' => __( 'The name of the FAQ page' ) ],
			[ 'name' => 'description', 'type' => 'textarea', 'label' => __( 'Description' ), 'required' => false, 'description' => __( 'A description of the FAQ page' ) ],
			[ 'name' => 'url', 'type' => 'url', 'label' => __( 'URL' ), 'required' => false, 'description' => __( 'URL of the FAQ page' ) ],
			[ 'name' => 'questions', 'type' => 'faq_list', 'label' => __( 'Questions' ), 'required' => true, 'description' => __( 'List of question and answer pairs' ) ],
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

		// Name
		$name = $this->get( 'name' );
		if ( null !== $name && '' !== $name ) {
			$schema['name'] = $name;
		}

		// Description
		$description = $this->get( 'description' );
		if ( null !== $description && '' !== $description ) {
			$schema['description'] = $description;
		}

		// URL
		$url = $this->get( 'url' );
		if ( null !== $url ) {
			$schema['url'] = $url;
		}

		// Main entity (the FAQ items)
		$questions = $this->get( 'questions' );
		if ( null !== $questions && is_array( $questions ) ) {
			$schema['mainEntity'] = $this->buildQuestions( $questions );
		}

		return $this->filterEmpty( $schema );
	}

	/**
	 * Build Question/Answer schema array.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<int, array<string, string>>  $questions  The questions data.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function buildQuestions( array $questions ): array
	{
		$result = [];

		foreach ( $questions as $qa ) {
			if ( empty( $qa['question'] ) || empty( $qa['answer'] ) ) {
				continue;
			}

			$result[] = [
				'@type'          => 'Question',
				'name'           => $qa['question'],
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => $qa['answer'],
				],
			];
		}

		return $result;
	}
}
