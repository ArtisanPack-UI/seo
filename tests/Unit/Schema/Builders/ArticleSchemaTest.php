<?php

/**
 * Article and BlogPosting Schema Tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Schema\Builders\ArticleSchema;
use ArtisanPackUI\SEO\Schema\Builders\BlogPostingSchema;

describe( 'ArticleSchema', function (): void {

	beforeEach( function (): void {
		config()->set( 'seo.schema.organization.name', 'Test Publisher' );
		config()->set( 'seo.schema.organization.logo', 'https://example.com/logo.png' );
		config()->set( 'app.name', 'Test App' );
	} );

	it( 'returns correct type', function (): void {
		$builder = new ArticleSchema();

		expect( $builder->getType() )->toBe( 'Article' );
	} );

	it( 'generates basic article schema', function (): void {
		$builder = new ArticleSchema( [
			'name'          => 'Test Article Title',
			'description'   => 'A brief description of the article.',
			'datePublished' => '2024-01-15T10:00:00+00:00',
		] );

		$schema = $builder->generate();

		expect( $schema['@type'] )->toBe( 'Article' )
			->and( $schema['headline'] )->toBe( 'Test Article Title' )
			->and( $schema['description'] )->toBe( 'A brief description of the article.' )
			->and( $schema['datePublished'] )->toBe( '2024-01-15T10:00:00+00:00' );
	} );

	it( 'includes mainEntityOfPage', function (): void {
		$builder = new ArticleSchema( [
			'name' => 'Test Article',
			'url'  => 'https://example.com/articles/test-article',
		] );

		$schema = $builder->generate();

		expect( $schema['mainEntityOfPage']['@type'] )->toBe( 'WebPage' )
			->and( $schema['mainEntityOfPage']['@id'] )->toBe( 'https://example.com/articles/test-article' );
	} );

	it( 'includes author', function (): void {
		$builder = new ArticleSchema( [
			'name'   => 'Test Article',
			'author' => [
				'name' => 'John Doe',
				'url'  => 'https://example.com/authors/john-doe',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['author']['@type'] )->toBe( 'Person' )
			->and( $schema['author']['name'] )->toBe( 'John Doe' )
			->and( $schema['author']['url'] )->toBe( 'https://example.com/authors/john-doe' );
	} );

	it( 'includes default publisher from config', function (): void {
		$builder = new ArticleSchema( [
			'name' => 'Test Article',
		] );

		$schema = $builder->generate();

		expect( $schema['publisher']['@type'] )->toBe( 'Organization' )
			->and( $schema['publisher']['name'] )->toBe( 'Test Publisher' );
	} );

	it( 'includes custom publisher', function (): void {
		$builder = new ArticleSchema( [
			'name'      => 'Test Article',
			'publisher' => [
				'name' => 'Custom Publisher',
				'url'  => 'https://publisher.com',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['publisher']['name'] )->toBe( 'Custom Publisher' );
	} );

	it( 'includes image', function (): void {
		$builder = new ArticleSchema( [
			'name'  => 'Test Article',
			'image' => 'https://example.com/article-image.jpg',
		] );

		$schema = $builder->generate();

		expect( $schema['image']['@type'] )->toBe( 'ImageObject' )
			->and( $schema['image']['url'] )->toBe( 'https://example.com/article-image.jpg' );
	} );

	it( 'includes article body', function (): void {
		$builder = new ArticleSchema( [
			'name'        => 'Test Article',
			'articleBody' => 'The full content of the article goes here.',
		] );

		$schema = $builder->generate();

		expect( $schema['articleBody'] )->toBe( 'The full content of the article goes here.' );
	} );

	it( 'includes word count', function (): void {
		$builder = new ArticleSchema( [
			'name'      => 'Test Article',
			'wordCount' => 1500,
		] );

		$schema = $builder->generate();

		expect( $schema['wordCount'] )->toBe( 1500 );
	} );

	it( 'handles keywords as array', function (): void {
		$builder = new ArticleSchema( [
			'name'     => 'Test Article',
			'keywords' => [ 'php', 'laravel', 'seo' ],
		] );

		$schema = $builder->generate();

		expect( $schema['keywords'] )->toBe( 'php, laravel, seo' );
	} );

	it( 'handles keywords as string', function (): void {
		$builder = new ArticleSchema( [
			'name'     => 'Test Article',
			'keywords' => 'php, laravel, seo',
		] );

		$schema = $builder->generate();

		expect( $schema['keywords'] )->toBe( 'php, laravel, seo' );
	} );

	it( 'includes article section', function (): void {
		$builder = new ArticleSchema( [
			'name'           => 'Test Article',
			'articleSection' => 'Technology',
		] );

		$schema = $builder->generate();

		expect( $schema['articleSection'] )->toBe( 'Technology' );
	} );

} );

describe( 'BlogPostingSchema', function (): void {

	it( 'returns correct type', function (): void {
		$builder = new BlogPostingSchema();

		expect( $builder->getType() )->toBe( 'BlogPosting' );
	} );

	it( 'generates blog posting with all article properties', function (): void {
		config()->set( 'seo.schema.organization.name', 'Test Publisher' );

		$builder = new BlogPostingSchema( [
			'name'          => 'Test Blog Post',
			'description'   => 'A test blog post.',
			'datePublished' => '2024-01-15T10:00:00+00:00',
			'author'        => [
				'name' => 'Jane Doe',
			],
		] );

		$schema = $builder->generate();

		expect( $schema['@type'] )->toBe( 'BlogPosting' )
			->and( $schema['headline'] )->toBe( 'Test Blog Post' )
			->and( $schema['author']['name'] )->toBe( 'Jane Doe' );
	} );

} );
