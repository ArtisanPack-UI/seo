<?php

/**
 * Shared setup helpers for SEO AI agent tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */

declare( strict_types=1 );

namespace Tests\Feature\Ai;

use ArtisanPackUI\Ai\Contracts\AgentPrompter;
use ArtisanPackUI\Ai\Contracts\CredentialResolver;
use ArtisanPackUI\Ai\Credentials\ChainedCredentialResolver;
use ArtisanPackUI\Ai\Credentials\Credentials;
use Tests\Support\FakeAgentPrompter;

/**
 * Registers a fake prompter and stub credentials for the 5 SEO features.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.2.0
 */
class AiAgentTestSetup
{
	/**
	 * Prepare the container so agents can run against a fake prompter.
	 *
	 * @since 1.2.0
	 *
	 * @param  \Illuminate\Foundation\Application  $app  Application instance.
	 *
	 * @return FakeAgentPrompter The bound fake prompter.
	 */
	public static function bootstrap( $app ): FakeAgentPrompter
	{
		/** @var ChainedCredentialResolver $resolver */
		$resolver = $app->make( CredentialResolver::class );
		$resolver->setOverride(
			new Credentials( provider: 'anthropic', apiKey: 'sk-test', defaultModel: 'claude-haiku-4-5' ),
		);
		$resolver->useStore( fn () => null );

		$prompter = new FakeAgentPrompter();
		$app->instance( AgentPrompter::class, $prompter );

		foreach (
			[
				'seo.suggest_meta_title',
				'seo.suggest_meta_description',
				'seo.analyze_content',
				'seo.generate_schema',
				'seo.suggest_hreflang',
			] as $key
		) {
			$app['config']->set( "artisanpack.ai.features.{$key}.enabled", true );
		}

		return $prompter;
	}
}
