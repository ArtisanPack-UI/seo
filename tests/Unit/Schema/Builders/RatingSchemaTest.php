<?php

/**
 * Review and AggregateRating Schema Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Schema\Builders\AggregateRatingSchema;
use ArtisanPackUI\SEO\Schema\Builders\ReviewSchema;

describe( 'ReviewSchema', function (): void {

	it( 'returns correct type', function (): void {
		$builder = new ReviewSchema();

		expect( $builder->getType() )->toBe( 'Review' );
	} );

	it( 'generates basic review schema', function (): void {
		$builder = new ReviewSchema( [
			'name'       => 'Great Product Review',
			'reviewBody' => 'This product exceeded my expectations.',
		] );

		$schema = $builder->generate();

		expect( $schema['@type'] )->toBe( 'Review' )
			->and( $schema['name'] )->toBe( 'Great Product Review' )
			->and( $schema['reviewBody'] )->toBe( 'This product exceeded my expectations.' );
	} );

	it( 'includes author', function (): void {
		$builder = new ReviewSchema( [
			'name'   => 'Review',
			'author' => [
				'name' => 'John Doe',
				'url'  => 'https://example.com/users/john',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['author']['@type'] )->toBe( 'Person' )
			->and( $schema['author']['name'] )->toBe( 'John Doe' )
			->and( $schema['author']['url'] )->toBe( 'https://example.com/users/john' );
	} );

	it( 'includes review rating', function (): void {
		$builder = new ReviewSchema( [
			'name'         => 'Review',
			'reviewRating' => [
				'value' => 4,
				'best'  => 5,
			],
		] );

		$schema = $builder->generate();

		expect( $schema['reviewRating']['@type'] )->toBe( 'Rating' )
			->and( $schema['reviewRating']['ratingValue'] )->toBe( 4 )
			->and( $schema['reviewRating']['bestRating'] )->toBe( 5 );
	} );

	it( 'includes item reviewed', function (): void {
		$builder = new ReviewSchema( [
			'name'         => 'Review',
			'itemReviewed' => [
				'type' => 'Product',
				'name' => 'Super Widget',
				'url'  => 'https://example.com/products/widget',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['itemReviewed']['@type'] )->toBe( 'Product' )
			->and( $schema['itemReviewed']['name'] )->toBe( 'Super Widget' )
			->and( $schema['itemReviewed']['url'] )->toBe( 'https://example.com/products/widget' );
	} );

	it( 'includes date published', function (): void {
		$builder = new ReviewSchema( [
			'name'          => 'Review',
			'datePublished' => '2024-01-15T10:00:00+00:00',
		] );

		$schema = $builder->generate();

		expect( $schema['datePublished'] )->toBe( '2024-01-15T10:00:00+00:00' );
	} );

	it( 'defaults item reviewed type to Thing', function (): void {
		$builder = new ReviewSchema( [
			'name'         => 'Review',
			'itemReviewed' => [
				'name' => 'Something',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['itemReviewed']['@type'] )->toBe( 'Thing' );
	} );

} );

describe( 'AggregateRatingSchema', function (): void {

	it( 'returns correct type', function (): void {
		$builder = new AggregateRatingSchema();

		expect( $builder->getType() )->toBe( 'AggregateRating' );
	} );

	it( 'generates basic aggregate rating schema', function (): void {
		$builder = new AggregateRatingSchema( [
			'ratingValue' => 4.5,
			'ratingCount' => 125,
		] );

		$schema = $builder->generate();

		expect( $schema['@type'] )->toBe( 'AggregateRating' )
			->and( $schema['ratingValue'] )->toBe( 4.5 )
			->and( $schema['ratingCount'] )->toBe( 125 );
	} );

	it( 'includes best rating', function (): void {
		$builder = new AggregateRatingSchema( [
			'ratingValue' => 4.5,
			'bestRating'  => 5,
		] );

		$schema = $builder->generate();

		expect( $schema['bestRating'] )->toBe( 5 );
	} );

	it( 'includes worst rating', function (): void {
		$builder = new AggregateRatingSchema( [
			'ratingValue'  => 4.5,
			'worstRating'  => 1,
		] );

		$schema = $builder->generate();

		expect( $schema['worstRating'] )->toBe( 1 );
	} );

	it( 'includes review count', function (): void {
		$builder = new AggregateRatingSchema( [
			'ratingValue' => 4.5,
			'reviewCount' => 89,
		] );

		$schema = $builder->generate();

		expect( $schema['reviewCount'] )->toBe( 89 );
	} );

	it( 'includes item reviewed', function (): void {
		$builder = new AggregateRatingSchema( [
			'ratingValue'  => 4.5,
			'itemReviewed' => [
				'type' => 'LocalBusiness',
				'name' => 'Best Restaurant',
				'url'  => 'https://example.com/restaurant',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['itemReviewed']['@type'] )->toBe( 'LocalBusiness' )
			->and( $schema['itemReviewed']['name'] )->toBe( 'Best Restaurant' );
	} );

	it( 'handles all rating properties together', function (): void {
		$builder = new AggregateRatingSchema( [
			'ratingValue' => 4.2,
			'ratingCount' => 150,
			'reviewCount' => 89,
			'bestRating'  => 5,
			'worstRating' => 1,
		] );

		$schema = $builder->generate();

		expect( $schema['ratingValue'] )->toBe( 4.2 )
			->and( $schema['ratingCount'] )->toBe( 150 )
			->and( $schema['reviewCount'] )->toBe( 89 )
			->and( $schema['bestRating'] )->toBe( 5 )
			->and( $schema['worstRating'] )->toBe( 1 );
	} );

} );
