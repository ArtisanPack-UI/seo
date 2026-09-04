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
		'name'        => env( 'SEO_SITE_NAME', env( 'APP_NAME', 'Laravel' ) ),
		'description' => env( 'SEO_SITE_DESCRIPTION', '' ),
		'separator'   => env( 'SEO_TITLE_SEPARATOR', ' | ' ),
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
		'robots'                 => env( 'SEO_DEFAULT_ROBOTS', 'index, follow' ),
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
			'name'   => env( 'APP_NAME', 'Laravel' ),
			'logo'   => null,
			'url'    => env( 'APP_URL', '' ),
			'email'  => null,
			'phone'  => null,
			'sameAs' => [],
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
		'enabled'           => env( 'SEO_SITEMAP_ENABLED', true ),
		'route_enabled'     => true,
		'route_path'        => 'sitemap.xml',
		'max_urls_per_file' => 10000,
		'default_frequency' => 'weekly',
		'default_priority'  => 0.5,
		'cache_enabled'     => true,
		'cache_ttl'         => 3600, // 1 hour in seconds
		'submit_enabled'    => env( 'SEO_SITEMAP_SUBMIT_ENABLED', false ),
		'submit_timeout'    => 10, // HTTP timeout for search engine pings
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
		'news' => [
			'types'        => [ 'article', 'post', 'news' ], // Content types for news sitemap
			'max_age_days' => 2, // Google News only indexes last 2 days
		],
		'search_engines' => [
			// The active default ping engine. Google's ping endpoint was
			// deprecated in 2023 — for Google, submit via Search Console or
			// list your sitemap in robots.txt. Override this array to add,
			// remove, or replace engines.
			'bing' => 'https://www.bing.com/ping?sitemap=%s',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| IndexNow Settings
	|--------------------------------------------------------------------------
	|
	| Configuration for IndexNow (https://www.indexnow.org/) — a protocol
	| for notifying participating search engines (Bing, Yandex, Seznam,
	| Naver, and others via the aggregator) when URLs are added, updated,
	| or deleted. The engine lives upstream; consumers wire a queued
	| dispatch from their publish observers.
	|
	| The `key` MUST be a hex string 8–128 characters long. Host it at
	| `https://{your-host}/{key}.txt` OR set `key_location` to a custom
	| URL that serves the same key.
	|
	| Bind a custom `IndexNowKeyProviderContract` implementation in the
	| container to manage the key dynamically (per-tenant, rotating, etc.).
	|
	*/

	'indexnow' => [
		'enabled'      => env( 'SEO_INDEXNOW_ENABLED', false ),
		'key'          => env( 'SEO_INDEXNOW_KEY' ),
		'key_location' => env( 'SEO_INDEXNOW_KEY_LOCATION' ),
		'endpoint'     => env( 'SEO_INDEXNOW_ENDPOINT', 'https://api.indexnow.org/IndexNow' ),
		'batch_size'   => 10000,
		'timeout'      => 10,
		'user_agent'   => 'ArtisanPackUI SEO IndexNow Submitter',
	],

	/*
	|--------------------------------------------------------------------------
	| llms.txt Settings
	|--------------------------------------------------------------------------
	|
	| Configuration for the llms.txt AI-discovery manifest (see
	| https://llmstxt.org/). The manifest is generated from the same
	| indexable SitemapEntry source as the XML sitemap, so any regeneration
	| triggered by the sitemap observer refreshes this output too. Route
	| wiring is left to consumers (e.g. Keystone).
	|
	*/

	'llms_txt' => [
		'enabled'       => env( 'SEO_LLMS_TXT_ENABLED', true ),

		// Header rendered as the top-level `# {title}` line. Falls back to seo.site.name.
		'title'         => null,

		// Blockquote summary rendered under the title. Falls back to seo.site.description.
		'summary'       => null,

		// Optional intro paragraph rendered above the entry sections.
		'intro'         => null,

		// Restrict which sitemap entry types are included (empty = all).
		'include_types' => [],

		// Exclude specific sitemap entry types.
		'exclude_types' => [],

		// Soft cap on total entries emitted; null = unlimited.
		'max_entries'   => null,
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
		'cache_ttl'     => 3600, // 1 hour in seconds

		/*
		|--------------------------------------------------------------------------
		| Global Disallow/Allow Rules
		|--------------------------------------------------------------------------
		|
		| These rules apply to all user agents (*). For bot-specific rules,
		| use the 'rules' array below.
		|
		*/

		'disallow' => [
			'/admin',
			'/api',
		],
		'allow' => [],

		/*
		|--------------------------------------------------------------------------
		| Bot-Specific Rules
		|--------------------------------------------------------------------------
		|
		| Define rules for specific user agents. Each key is the user-agent
		| string (e.g., 'Googlebot', 'Bingbot', 'GPTBot').
		|
		| Example:
		| 'rules' => [
		|     'GPTBot' => [
		|         'disallow' => ['/'],  // Block AI crawlers from entire site
		|     ],
		|     'Googlebot' => [
		|         'allow' => ['/api/public'],
		|         'disallow' => ['/api/internal'],
		|         'crawl_delay' => 1,
		|     ],
		| ],
		|
		*/

		'rules' => [
			// 'GPTBot' => [
			//     'disallow' => ['/'],
			// ],
			// 'CCBot' => [
			//     'disallow' => ['/'],
			// ],
		],

		/*
		|--------------------------------------------------------------------------
		| Sitemap Configuration
		|--------------------------------------------------------------------------
		|
		| The sitemap_url is auto-generated from the sitemap route if null.
		| Use 'sitemaps' array to include multiple sitemap URLs.
		|
		*/

		'sitemap_url' => null, // Auto-generated if null
		'sitemaps'    => [], // Additional sitemap URLs

		/*
		|--------------------------------------------------------------------------
		| Host Directive
		|--------------------------------------------------------------------------
		|
		| The Host directive (used by some crawlers like Yandex).
		|
		*/

		'host' => null,

		/*
		|--------------------------------------------------------------------------
		| AI Crawler Controls
		|--------------------------------------------------------------------------
		|
		| Explicit controls for AI crawlers. Defaults to allow (visibility is
		| the point). Set a group's 'blocked' flag to true to disallow every
		| user-agent in that group. The resolved rules are exposed via
		| AiCrawlerService so host middleware can enforce the same decisions.
		|
		*/

		'ai_crawlers' => [
			'default_allow' => true,

			'groups' => [
				'openai' => [
					'label'       => 'OpenAI',
					'user_agents' => [ 'GPTBot', 'ChatGPT-User', 'OAI-SearchBot' ],
					'blocked'     => false,
				],
				'anthropic' => [
					'label'       => 'Anthropic',
					'user_agents' => [ 'ClaudeBot', 'Claude-Web', 'anthropic-ai' ],
					'blocked'     => false,
				],
				'google-extended' => [
					'label'       => 'Google-Extended',
					'user_agents' => [ 'Google-Extended' ],
					'blocked'     => false,
				],
				'perplexity' => [
					'label'       => 'Perplexity',
					'user_agents' => [ 'PerplexityBot' ],
					'blocked'     => false,
				],
				'common-crawl' => [
					'label'       => 'Common Crawl',
					'user_agents' => [ 'CCBot' ],
					'blocked'     => false,
				],
				'bytedance' => [
					'label'       => 'ByteDance',
					'user_agents' => [ 'Bytespider' ],
					'blocked'     => false,
				],
				'meta' => [
					'label'       => 'Meta',
					'user_agents' => [ 'FacebookBot', 'Meta-ExternalAgent' ],
					'blocked'     => false,
				],
			],
		],
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
		'enabled'            => env( 'SEO_REDIRECTS_ENABLED', true ),
		'middleware_enabled' => true,
		'cache_enabled'      => true,
		'cache_ttl'          => 86400, // 24 hours in seconds
		'track_hits'         => true,
		'max_chain_depth'    => 5,

		/*
		|--------------------------------------------------------------------------
		| Authorization Settings
		|--------------------------------------------------------------------------
		|
		| Configure authorization for the RedirectManager Livewire component.
		| Set 'authorization_enabled' to true and define the gate/ability name.
		|
		| Example setup in AuthServiceProvider:
		| Gate::define('manage-redirects', fn ($user) => $user->isAdmin());
		|
		*/

		'authorization_enabled' => false,
		'authorization_ability' => 'manage-redirects',
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
		'enabled'          => env( 'SEO_ANALYSIS_ENABLED', true ),
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
			'ai_readiness'      => true,
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
	| Configuration for multi-language SEO support. Hreflang tags help search
	| engines serve the correct language or regional URL to users.
	|
	| 'enabled' - Set to true to enable hreflang functionality.
	| 'default_locale' - The primary locale, used for x-default if not explicitly set.
	| 'supported_locales' - Array of locale codes available for selection.
	|                       Leave empty to allow all common locales.
	| 'auto_add_x_default' - Automatically add x-default pointing to default_locale URL.
	|
	| Locale format examples:
	| - Language only: 'en', 'fr', 'de', 'es'
	| - Language-Region: 'en-US', 'en-GB', 'fr-FR', 'es-MX'
	| - x-default: Used for fallback/default language page
	|
	*/

	'hreflang' => [
		'enabled'            => false,
		'default_locale'     => 'en',
		'auto_add_x_default' => true,
		'supported_locales'  => [
			// Uncomment and customize the locales you need:
			// 'en',
			// 'en-US',
			// 'en-GB',
			// 'es',
			// 'es-ES',
			// 'es-MX',
			// 'fr',
			// 'fr-FR',
			// 'fr-CA',
			// 'de',
			// 'de-DE',
			// 'it',
			// 'it-IT',
			// 'pt',
			// 'pt-BR',
			// 'ja',
			// 'zh',
			// 'zh-CN',
			// 'zh-TW',
			// 'ko',
			// 'ar',
			// 'ru',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| OG Image Generator
	|--------------------------------------------------------------------------
	|
	| Settings for the branded OG social-share image generator. The generator
	| renders a 1200x630 image from a title, optional subtitle, and a
	| template describing the background, logo, and typography. Rendered
	| images are stored on the configured disk under a deterministic path
	| so identical inputs are only rendered once.
	|
	| Backend choice is discussed in docs/og-image-backend-decision.md.
	|
	*/

	'og_image' => [
		'enabled'  => env( 'SEO_OG_IMAGE_ENABLED', true ),
		'renderer' => ArtisanPackUI\SEO\Services\OgImage\GdOgImageRenderer::class,
		'disk'     => env( 'SEO_OG_IMAGE_DISK', 'public' ),
		'path'     => env( 'SEO_OG_IMAGE_PATH', 'og-images' ),
		'template' => [
			'width'                 => 1200,
			'height'                => 630,
			'background_color'      => '#0f172a',
			'text_color'            => '#ffffff',
			'subtitle_color'        => '#94a3b8',
			'background_image_path' => null,
			'logo_path'             => null,
			'logo_width'            => 160,
			'font_path'             => null,
			'title_font_size'       => 56,
			'subtitle_font_size'    => 28,
			'padding'               => 80,
		],
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
		'enabled' => env( 'SEO_CACHE_ENABLED', true ),
		'ttl'     => env( 'SEO_CACHE_TTL', 3600 ),
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
