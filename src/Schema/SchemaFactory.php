<?php

/**
 * SchemaFactory.
 *
 * Factory for creating Schema.org type builders.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Schema;

use ArtisanPackUI\SEO\Contracts\SchemaTypeContract;
use ArtisanPackUI\SEO\Schema\Builders\AggregateRatingSchema;
use ArtisanPackUI\SEO\Schema\Builders\ArticleSchema;
use ArtisanPackUI\SEO\Schema\Builders\BlogPostingSchema;
use ArtisanPackUI\SEO\Schema\Builders\BreadcrumbListSchema;
use ArtisanPackUI\SEO\Schema\Builders\EventSchema;
use ArtisanPackUI\SEO\Schema\Builders\FAQPageSchema;
use ArtisanPackUI\SEO\Schema\Builders\LocalBusinessSchema;
use ArtisanPackUI\SEO\Schema\Builders\OrganizationSchema;
use ArtisanPackUI\SEO\Schema\Builders\ProductSchema;
use ArtisanPackUI\SEO\Schema\Builders\ReviewSchema;
use ArtisanPackUI\SEO\Schema\Builders\ServiceSchema;
use ArtisanPackUI\SEO\Schema\Builders\WebPageSchema;
use ArtisanPackUI\SEO\Schema\Builders\WebsiteSchema;
use InvalidArgumentException;

/**
 * SchemaFactory class.
 *
 * Creates appropriate Schema.org builder instances based on type.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SchemaFactory
{
	/**
	 * Map of schema types to their builder classes.
	 *
	 * @var array<string, class-string<SchemaTypeContract>>
	 */
	protected array $types = [
		'Organization'    => OrganizationSchema::class,
		'LocalBusiness'   => LocalBusinessSchema::class,
		'WebSite'         => WebsiteSchema::class,
		'WebPage'         => WebPageSchema::class,
		'Article'         => ArticleSchema::class,
		'BlogPosting'     => BlogPostingSchema::class,
		'Product'         => ProductSchema::class,
		'Service'         => ServiceSchema::class,
		'Event'           => EventSchema::class,
		'FAQPage'         => FAQPageSchema::class,
		'BreadcrumbList'  => BreadcrumbListSchema::class,
		'Review'          => ReviewSchema::class,
		'AggregateRating' => AggregateRatingSchema::class,
	];

	/**
	 * Create a schema builder for the given type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string               $type  The schema type.
	 * @param  array<string, mixed> $data  Optional data to pass to the builder.
	 *
	 * @throws InvalidArgumentException If the type is not supported.
	 *
	 * @return SchemaTypeContract
	 */
	public function make( string $type, array $data = [] ): SchemaTypeContract
	{
		if ( ! isset( $this->types[ $type ] ) ) {
			throw new InvalidArgumentException(
				__( 'Unknown schema type: :type', [ 'type' => $type ] ),
			);
		}

		$builderClass = $this->types[ $type ];

		return new $builderClass( $data );
	}

	/**
	 * Check if a schema type is supported.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $type  The schema type to check.
	 *
	 * @return bool
	 */
	public function supports( string $type ): bool
	{
		return isset( $this->types[ $type ] );
	}

	/**
	 * Get all supported schema types.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public function getSupportedTypes(): array
	{
		return array_keys( $this->types );
	}

	/**
	 * Register a custom schema type builder.
	 *
	 * @since 1.0.0
	 *
	 * @param  string                           $type          The schema type name.
	 * @param  class-string<SchemaTypeContract> $builderClass  The builder class.
	 *
	 * @return void
	 */
	public function register( string $type, string $builderClass ): void
	{
		$this->types[ $type ] = $builderClass;
	}
}
