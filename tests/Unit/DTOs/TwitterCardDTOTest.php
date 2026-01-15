<?php

/**
 * TwitterCardDTO Tests.
 *
 * Unit tests for the TwitterCardDTO.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\DTOs\TwitterCardDTO;

describe( 'TwitterCardDTO', function (): void {

	it( 'can be instantiated with default values', function (): void {
		$dto = new TwitterCardDTO();

		expect( $dto->card )->toBe( 'summary_large_image' )
			->and( $dto->title )->toBe( '' )
			->and( $dto->description )->toBeNull()
			->and( $dto->image )->toBeNull()
			->and( $dto->site )->toBeNull()
			->and( $dto->creator )->toBeNull();
	} );

	it( 'can be instantiated with all parameters', function (): void {
		$dto = new TwitterCardDTO(
			card: 'summary',
			title: 'Twitter Title',
			description: 'Twitter Description',
			image: 'https://example.com/twitter.jpg',
			site: '@MySite',
			creator: '@JohnDoe',
		);

		expect( $dto->card )->toBe( 'summary' )
			->and( $dto->title )->toBe( 'Twitter Title' )
			->and( $dto->description )->toBe( 'Twitter Description' )
			->and( $dto->image )->toBe( 'https://example.com/twitter.jpg' )
			->and( $dto->site )->toBe( '@MySite' )
			->and( $dto->creator )->toBe( '@JohnDoe' );
	} );

	it( 'supports different card types', function (): void {
		$summary     = new TwitterCardDTO( card: 'summary' );
		$large       = new TwitterCardDTO( card: 'summary_large_image' );
		$app         = new TwitterCardDTO( card: 'app' );
		$player      = new TwitterCardDTO( card: 'player' );

		expect( $summary->card )->toBe( 'summary' )
			->and( $large->card )->toBe( 'summary_large_image' )
			->and( $app->card )->toBe( 'app' )
			->and( $player->card )->toBe( 'player' );
	} );

	it( 'converts to array with twitter prefixed keys', function (): void {
		$dto = new TwitterCardDTO(
			card: 'summary_large_image',
			title: 'Card Title',
			description: 'Card Description',
			image: 'https://example.com/card.jpg',
			site: '@TestSite',
			creator: '@TestCreator',
		);

		$array = $dto->toArray();

		expect( $array )->toBe( [
			'twitter:card'        => 'summary_large_image',
			'twitter:title'       => 'Card Title',
			'twitter:description' => 'Card Description',
			'twitter:image'       => 'https://example.com/card.jpg',
			'twitter:site'        => '@TestSite',
			'twitter:creator'     => '@TestCreator',
		] );
	} );

	it( 'converts to filtered array excluding null and empty values', function (): void {
		$dto = new TwitterCardDTO(
			card: 'summary',
			title: 'Title Only',
			description: null,
			image: null,
			site: null,
			creator: null,
		);

		$filtered = $dto->toArrayFiltered();

		expect( $filtered )->toHaveKeys( [ 'twitter:card', 'twitter:title' ] )
			->and( $filtered )->not->toHaveKeys( [
				'twitter:description',
				'twitter:image',
				'twitter:site',
				'twitter:creator',
			] );
	} );

	it( 'can be created from array with twitter prefixed keys', function (): void {
		$data = [
			'twitter:card'        => 'summary',
			'twitter:title'       => 'From Twitter Array',
			'twitter:description' => 'Twitter Desc',
			'twitter:image'       => 'https://example.com/tw.jpg',
			'twitter:site'        => '@ArraySite',
			'twitter:creator'     => '@ArrayCreator',
		];

		$dto = TwitterCardDTO::fromArray( $data );

		expect( $dto->card )->toBe( 'summary' )
			->and( $dto->title )->toBe( 'From Twitter Array' )
			->and( $dto->description )->toBe( 'Twitter Desc' )
			->and( $dto->image )->toBe( 'https://example.com/tw.jpg' )
			->and( $dto->site )->toBe( '@ArraySite' )
			->and( $dto->creator )->toBe( '@ArrayCreator' );
	} );

	it( 'can be created from array with unprefixed keys', function (): void {
		$data = [
			'card'        => 'summary',
			'title'       => 'Simple Title',
			'description' => 'Simple Desc',
			'image'       => 'https://example.com/simple.jpg',
			'site'        => '@SimpleSite',
			'creator'     => '@SimpleCreator',
		];

		$dto = TwitterCardDTO::fromArray( $data );

		expect( $dto->card )->toBe( 'summary' )
			->and( $dto->title )->toBe( 'Simple Title' )
			->and( $dto->site )->toBe( '@SimpleSite' );
	} );

	it( 'uses defaults when creating from empty array', function (): void {
		$dto = TwitterCardDTO::fromArray( [] );

		expect( $dto->card )->toBe( 'summary_large_image' )
			->and( $dto->title )->toBe( '' )
			->and( $dto->description )->toBeNull()
			->and( $dto->image )->toBeNull()
			->and( $dto->site )->toBeNull()
			->and( $dto->creator )->toBeNull();
	} );

	it( 'is readonly and immutable', function (): void {
		$dto = new TwitterCardDTO(
			card: 'summary',
			title: 'Test',
		);

		$reflection = new ReflectionClass( $dto );

		expect( $reflection->isReadOnly() )->toBeTrue();
	} );

} );
