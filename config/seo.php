<?php

/**
 * SEO Package Configuration.
 *
 * This file contains all configurable options for the ArtisanPack UI SEO package.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */

return [

	/*
	|--------------------------------------------------------------------------
	| Site Meta Information
	|--------------------------------------------------------------------------
	|
	| Default meta information for your site. These values are used when
	| specific page meta data is not available.
	|
	*/

	'site' => [
		'name'        => env( 'APP_NAME', 'Laravel' ),
		'description' => env( 'SEO_SITE_DESCRIPTION', '' ),
		'separator'   => ' | ',
	],

	/*
	|--------------------------------------------------------------------------
	| Meta Tag Defaults
	|--------------------------------------------------------------------------
	|
	| Default values for meta tags when not specified by individual models.
	|
	*/

	'defaults' => [
		'robots'                 => 'index, follow',
		'title_max_length'       => 60,
		'description_max_length' => 160,
	],

	/*
	|--------------------------------------------------------------------------
	| Open Graph Settings
	|--------------------------------------------------------------------------
	|
	| Configuration for Open Graph meta tags (used by Facebook, LinkedIn, etc.).
	|
	*/

	'open_graph' => [
		'enabled'       => true,
		'type'          => 'website',
		'default_image' => null,
		'site_name'     => env( 'APP_NAME', 'Laravel' ),
	],

	/*
	|--------------------------------------------------------------------------
	| Twitter Card Settings
	|--------------------------------------------------------------------------
	|
	| Configuration for Twitter Card meta tags.
	|
	*/

	'twitter' => [
		'enabled'       => true,
		'card_type'     => 'summary_large_image',
		'site'          => null, // @username
		'creator'       => null, // @username
		'default_image' => null,
	],

	/*
	|--------------------------------------------------------------------------
	| Schema.org / JSON-LD Settings
	|--------------------------------------------------------------------------
	|
	| Configuration for structured data output.
	|
	*/

	'schema' => [
		'enabled'      => true,
		'organization' => [
			'name'  => env( 'APP_NAME', 'Laravel' ),
			'logo'  => null,
			'url'   => env( 'APP_URL', '' ),
			'email' => null,
			'phone' => null,
		],
		'default_types' => [
			'page'    => 'WebPage',
			'article' => 'Article',
			'product' => 'Product',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Sitemap Settings
	|--------------------------------------------------------------------------
	|
	| Configuration for XML sitemap generation.
	|
	*/

	'sitemap' => [
		'enabled'           => true,
		'route_enabled'     => true,
		'route_path'        => 'sitemap.xml',
		'max_urls_per_file' => 50000,
		'default_frequency' => 'weekly',
		'default_priority'  => 0.5,
		'cache_enabled'     => true,
		'cache_ttl'         => 3600, // 1 hour in seconds
		'providers'         => [
			// Register sitemap content providers here
			// 'posts' => \App\Sitemap\PostSitemapProvider::class,
		],
		'types' => [
			'standard' => true,
			'image'    => false,
			'video'    => false,
			'news'     => false,
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Robots.txt Settings
	|--------------------------------------------------------------------------
	|
	| Configuration for dynamic robots.txt generation.
	|
	*/

	'robots' => [
		'enabled'       => true,
		'route_enabled' => true,
		'route_path'    => 'robots.txt',
		'disallow'      => [
			'/admin',
			'/api',
		],
		'allow'       => [],
		'sitemap_url' => null, // Auto-generated if null
	],

	/*
	|--------------------------------------------------------------------------
	| Redirects Settings
	|--------------------------------------------------------------------------
	|
	| Configuration for URL redirect management.
	|
	*/

	'redirects' => [
		'enabled'            => true,
		'middleware_enabled' => true,
		'cache_enabled'      => true,
		'cache_ttl'          => 86400, // 24 hours in seconds
		'track_hits'         => true,
		'max_chain_depth'    => 5,
	],

	/*
	|--------------------------------------------------------------------------
	| SEO Analysis Settings
	|--------------------------------------------------------------------------
	|
	| Configuration for content SEO analysis features.
	|
	*/

	'analysis' => [
		'enabled'          => true,
		'queue_enabled'    => false,
		'queue_connection' => null,
		'queue_name'       => 'seo',
		'cache_enabled'    => true,
		'cache_ttl'        => 86400, // 24 hours in seconds
		'analyzers'        => [
			'readability'       => true,
			'keyword_density'   => true,
			'focus_keyword'     => true,
			'meta_length'       => true,
			'heading_structure' => true,
			'image_alt'         => true,
			'internal_links'    => true,
			'content_length'    => true,
		],
		'thresholds' => [
			'min_word_count'      => 300,
			'max_keyword_density' => 3.0,
			'min_internal_links'  => 2,
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Hreflang / Multi-language Settings
	|--------------------------------------------------------------------------
	|
	| Configuration for multi-language SEO support.
	|
	*/

	'hreflang' => [
		'enabled'           => false,
		'default_locale'    => 'en',
		'supported_locales' => [ 'en' ],
	],

	/*
	|--------------------------------------------------------------------------
	| Cache Settings
	|--------------------------------------------------------------------------
	|
	| Global cache settings for all SEO features.
	|
	*/

	'cache' => [
		'enabled' => true,
		'driver'  => null, // Uses default cache driver if null
		'prefix'  => 'seo',
	],

	/*
	|--------------------------------------------------------------------------
	| API Settings
	|--------------------------------------------------------------------------
	|
	| Configuration for SEO package API endpoints.
	|
	*/

	'api' => [
		'enabled'    => true,
		'prefix'     => 'api/seo',
		'middleware' => [ 'api', 'auth:sanctum' ],
		'rate_limit' => 60, // Requests per minute
	],

];
