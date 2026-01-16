<?php

/**
 * FAQPageSchema Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Schema\Builders\FAQPageSchema;

describe( 'FAQPageSchema', function (): void {

	it( 'returns correct type', function (): void {
		$builder = new FAQPageSchema();

		expect( $builder->getType() )->toBe( 'FAQPage' );
	} );

	it( 'generates basic FAQ schema', function (): void {
		$builder = new FAQPageSchema( [
			'name' => 'Frequently Asked Questions',
		] );

		$schema = $builder->generate();

		expect( $schema['@type'] )->toBe( 'FAQPage' )
			->and( $schema['name'] )->toBe( 'Frequently Asked Questions' );
	} );

	it( 'includes questions and answers', function (): void {
		$builder = new FAQPageSchema( [
			'name'      => 'FAQ',
			'questions' => [
				[
					'question' => 'What is your return policy?',
					'answer'   => 'You can return items within 30 days.',
				],
				[
					'question' => 'How do I contact support?',
					'answer'   => 'Email us at support@example.com.',
				],
			],
		] );

		$schema = $builder->generate();

		expect( $schema['mainEntity'] )->toHaveCount( 2 )
			->and( $schema['mainEntity'][0]['@type'] )->toBe( 'Question' )
			->and( $schema['mainEntity'][0]['name'] )->toBe( 'What is your return policy?' )
			->and( $schema['mainEntity'][0]['acceptedAnswer']['@type'] )->toBe( 'Answer' )
			->and( $schema['mainEntity'][0]['acceptedAnswer']['text'] )->toBe( 'You can return items within 30 days.' );
	} );

	it( 'handles single question', function (): void {
		$builder = new FAQPageSchema( [
			'name'      => 'FAQ',
			'questions' => [
				[
					'question' => 'Single question?',
					'answer'   => 'Single answer.',
				],
			],
		] );

		$schema = $builder->generate();

		expect( $schema['mainEntity'] )->toHaveCount( 1 )
			->and( $schema['mainEntity'][0]['name'] )->toBe( 'Single question?' );
	} );

	it( 'handles empty questions array', function (): void {
		$builder = new FAQPageSchema( [
			'name'      => 'FAQ',
			'questions' => [],
		] );

		$schema = $builder->generate();

		expect( $schema )->not->toHaveKey( 'mainEntity' );
	} );

	it( 'includes description', function (): void {
		$builder = new FAQPageSchema( [
			'name'        => 'FAQ',
			'description' => 'Common questions about our service.',
		] );

		$schema = $builder->generate();

		expect( $schema['description'] )->toBe( 'Common questions about our service.' );
	} );

	it( 'includes url', function (): void {
		$builder = new FAQPageSchema( [
			'name' => 'FAQ',
			'url'  => 'https://example.com/faq',
		] );

		$schema = $builder->generate();

		expect( $schema['url'] )->toBe( 'https://example.com/faq' );
	} );

	it( 'preserves question order', function (): void {
		$builder = new FAQPageSchema( [
			'name'      => 'FAQ',
			'questions' => [
				[ 'question' => 'First?', 'answer' => 'A1' ],
				[ 'question' => 'Second?', 'answer' => 'A2' ],
				[ 'question' => 'Third?', 'answer' => 'A3' ],
			],
		] );

		$schema = $builder->generate();

		expect( $schema['mainEntity'][0]['name'] )->toBe( 'First?' )
			->and( $schema['mainEntity'][1]['name'] )->toBe( 'Second?' )
			->and( $schema['mainEntity'][2]['name'] )->toBe( 'Third?' );
	} );

} );
