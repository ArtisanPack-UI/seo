<?php

namespace ArtisanPackUI\SEO\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ArtisanPackUI\SEO\A11y
 */
class SEO extends Facade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'sEO';
	}
}
