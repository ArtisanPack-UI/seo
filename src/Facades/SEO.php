<?php

/**
 * SEO Facade.
 *
 * Provides static access to the SEO service.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 * @copyright  2026 Jacob Martella
 * @license    MIT
 *
 * @since      1.0.0
 *
 * @method static array getMetaTags( \Illuminate\Database\Eloquent\Model|null $model = null )
 * @method static \ArtisanPackUI\SEO\SEO registerAnalyzer( string $name, string $class )
 * @method static array getAnalyzers()
 * @method static \ArtisanPackUI\SEO\SEO registerSchemaType( string $name, string $class )
 * @method static array getSchemaTypes()
 * @method static \ArtisanPackUI\SEO\SEO registerSitemapProvider( string $name, string $class )
 * @method static array getSitemapProviders()
 * @method static string title( string $title )
 * @method static bool isEnabled( string $feature )
 *
 * @see \ArtisanPackUI\SEO\SEO
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Facades;

use Illuminate\Support\Facades\Facade;

class SEO extends Facade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor(): string
	{
		return 'seo';
	}
}
