<?php

/**
 * SEO Facade.
 *
 * Provides static access to the SEO class.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * SEO Facade.
 *
 * @see \ArtisanPackUI\SEO\SEO
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
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
