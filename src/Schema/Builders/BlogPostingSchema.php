<?php
/**
 * BlogPostingSchema.
 *
 * Schema.org BlogPosting type builder.
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

/**
 * BlogPostingSchema class.
 *
 * Generates Schema.org BlogPosting structured data.
 * Extends Article schema with blog-specific properties.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class BlogPostingSchema extends ArticleSchema
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
		return 'BlogPosting';
	}
}
