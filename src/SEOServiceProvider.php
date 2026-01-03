<?php

namespace ArtisanPackUI\SEO;

use Illuminate\Support\ServiceProvider;

class SEOServiceProvider extends ServiceProvider
{

	public function register(): void
	{
		$this->app->singleton( 'sEO', function ( $app ) {
			return new SEO();
		} );
	}
}
