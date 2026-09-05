<?php

/**
 * AiCrawlerService Tests.
 *
 * Unit tests for AI-crawler robots controls.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Services\AiCrawlerService;
use ArtisanPackUI\SEO\Services\RobotsService;

beforeEach( function (): void {
	config( [ 'app.url' => 'https://example.com' ] );
	config( [ 'seo.robots.enabled' => true ] );
	config( [ 'seo.robots.route_enabled' => true ] );
	config( [ 'seo.robots.disallow' => [] ] );
	config( [ 'seo.robots.allow' => [] ] );
	config( [ 'seo.robots.rules' => [] ] );
	config( [ 'seo.robots.sitemap_url' => null ] );
	config( [ 'seo.robots.sitemaps' => [] ] );
	config( [ 'seo.robots.host' => null ] );
	config( [ 'seo.sitemap.route_enabled' => false ] );

	config( [ 'seo.robots.ai_crawlers' => [
		'default_allow' => true,
		'groups'        => [
			'openai' => [
				'label'       => 'OpenAI',
				'user_agents' => [ 'GPTBot', 'ChatGPT-User' ],
				'blocked'     => false,
			],
			'anthropic' => [
				'label'       => 'Anthropic',
				'user_agents' => [ 'ClaudeBot' ],
				'blocked'     => false,
			],
			'common-crawl' => [
				'label'       => 'Common Crawl',
				'user_agents' => [ 'CCBot' ],
				'blocked'     => false,
			],
		],
	] ] );
} );

describe( 'AiCrawlerService', function (): void {

	it( 'defaults to allow', function (): void {
		$service = new AiCrawlerService();

		expect( $service->defaultAllow() )->toBeTrue();
		expect( $service->getBlockedUserAgents() )->toBe( [] );
	} );

	it( 'emits no AI-crawler disallow blocks by default', function (): void {
		$content = ( new RobotsService() )->generate();

		expect( $content )->not->toContain( 'GPTBot' )
			->and( $content )->not->toContain( 'ClaudeBot' )
			->and( $content )->not->toContain( 'CCBot' );
	} );

	it( 'blocks every user-agent in a blocked group', function (): void {
		config( [ 'seo.robots.ai_crawlers.groups.openai.blocked' => true ] );

		$service = new AiCrawlerService();

		expect( $service->isGroupBlocked( 'openai' ) )->toBeTrue()
			->and( $service->isGroupBlocked( 'anthropic' ) )->toBeFalse()
			->and( $service->getBlockedUserAgents() )->toBe( [ 'GPTBot', 'ChatGPT-User' ] );
	} );

	it( 'emits a disallow: / block for each user-agent in a blocked group', function (): void {
		config( [ 'seo.robots.ai_crawlers.groups.openai.blocked' => true ] );

		$content = ( new RobotsService() )->generate();

		expect( $content )->toContain( "User-agent: GPTBot\nDisallow: /" )
			->and( $content )->toContain( "User-agent: ChatGPT-User\nDisallow: /" )
			->and( $content )->not->toContain( 'User-agent: ClaudeBot' );
	} );

	it( 'independently blocks multiple groups', function (): void {
		config( [ 'seo.robots.ai_crawlers.groups.openai.blocked' => true ] );
		config( [ 'seo.robots.ai_crawlers.groups.anthropic.blocked' => true ] );

		$service = new AiCrawlerService();
		$content = ( new RobotsService() )->generate();

		expect( $service->getBlockedUserAgents() )->toBe( [ 'GPTBot', 'ChatGPT-User', 'ClaudeBot' ] );
		expect( $content )->toContain( 'User-agent: GPTBot' )
			->and( $content )->toContain( 'User-agent: ClaudeBot' )
			->and( $content )->not->toContain( 'User-agent: CCBot' );
	} );

	it( 'exposes resolved rules matching the robots.txt output', function (): void {
		config( [ 'seo.robots.ai_crawlers.groups.anthropic.blocked' => true ] );

		$rules = ( new AiCrawlerService() )->getResolvedRules();

		expect( $rules )->toHaveKey( 'openai' )
			->and( $rules['openai']['blocked'] )->toBeFalse()
			->and( $rules['openai']['allow'] )->toBeTrue()
			->and( $rules['openai']['user_agents'] )->toBe( [ 'GPTBot', 'ChatGPT-User' ] )
			->and( $rules['anthropic']['blocked'] )->toBeTrue()
			->and( $rules['anthropic']['allow'] )->toBeFalse();
	} );

	it( 'matches a raw user-agent string against blocked groups', function (): void {
		config( [ 'seo.robots.ai_crawlers.groups.openai.blocked' => true ] );

		$service = new AiCrawlerService();

		expect( $service->isUserAgentBlocked( 'Mozilla/5.0 (compatible; GPTBot/1.0)' ) )->toBeTrue()
			->and( $service->isUserAgentBlocked( 'ClaudeBot/1.0' ) )->toBeFalse()
			->and( $service->isUserAgentBlocked( '' ) )->toBeFalse();
	} );

	it( 'keeps a group allowed when default_allow is false but blocked is explicitly false', function (): void {
		// The shipped defaults set blocked => false on every group, which acts as
		// an explicit opt-in. Flipping default_allow to false must not override
		// those explicit opt-ins.
		config( [ 'seo.robots.ai_crawlers.default_allow' => false ] );

		$rules = ( new AiCrawlerService() )->getResolvedRules();

		expect( $rules['openai']['allow'] )->toBeTrue()
			->and( $rules['openai']['blocked'] )->toBeFalse();
	} );

	it( 'blocks a group when default_allow is false and blocked flag is absent', function (): void {
		config( [
			'seo.robots.ai_crawlers.default_allow' => false,
			'seo.robots.ai_crawlers.groups'        => [
				'openai' => [
					'label'       => 'OpenAI',
					'user_agents' => [ 'GPTBot', 'ChatGPT-User' ],
				],
				'anthropic' => [
					'label'       => 'Anthropic',
					'user_agents' => [ 'ClaudeBot' ],
					'blocked'     => false,
				],
			],
		] );

		$service = new AiCrawlerService();
		$rules   = $service->getResolvedRules();

		expect( $rules['openai']['blocked'] )->toBeTrue()
			->and( $rules['openai']['allow'] )->toBeFalse()
			->and( $rules['anthropic']['blocked'] )->toBeFalse()
			->and( $rules['anthropic']['allow'] )->toBeTrue();

		$blocked = $service->getBlockedUserAgents();
		expect( $blocked )->toContain( 'GPTBot' )
			->and( $blocked )->toContain( 'ChatGPT-User' )
			->and( $blocked )->not->toContain( 'ClaudeBot' );

		expect( $service->isGroupBlocked( 'openai' ) )->toBeTrue()
			->and( $service->isGroupBlocked( 'anthropic' ) )->toBeFalse();
	} );

	it( 'emits Disallow blocks for every group when default_allow=false and no explicit opt-ins', function (): void {
		config( [
			'seo.robots.ai_crawlers.default_allow' => false,
			'seo.robots.ai_crawlers.groups'        => [
				'openai' => [
					'label'       => 'OpenAI',
					'user_agents' => [ 'GPTBot' ],
				],
				'anthropic' => [
					'label'       => 'Anthropic',
					'user_agents' => [ 'ClaudeBot' ],
				],
			],
		] );

		$service = new AiCrawlerService();

		expect( $service->getBlockedUserAgents() )->toBe( [ 'GPTBot', 'ClaudeBot' ] );
	} );

	it( 'reports isGroupBlocked false for unknown groups', function (): void {
		$service = new AiCrawlerService();

		expect( $service->isGroupBlocked( 'no-such-group' ) )->toBeFalse()
			->and( $service->getUserAgentsForGroup( 'no-such-group' ) )->toBe( [] );
	} );

} );
