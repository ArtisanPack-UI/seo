<?php

/**
 * AiCrawlerService.
 *
 * Resolves AI-crawler robots controls (default-allow, per-bot-group block)
 * so both robots.txt generation and host UA middleware share one decision.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Services;

/**
 * AiCrawlerService class.
 *
 * Exposes the resolved AI-crawler policy defined in `seo.robots.ai_crawlers`.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
class AiCrawlerService
{
	/**
	 * Determine whether unblocked bots are allowed by default.
	 *
	 * @since 1.4.0
	 *
	 * @return bool
	 */
	public function defaultAllow(): bool
	{
		return (bool) config( 'seo.robots.ai_crawlers.default_allow', true );
	}

	/**
	 * Get all configured bot groups.
	 *
	 * @since 1.4.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function getGroups(): array
	{
		$groups = config( 'seo.robots.ai_crawlers.groups', [] );

		return is_array( $groups ) ? $groups : [];
	}

	/**
	 * Determine whether a named group is blocked.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $group  The group key (e.g. 'openai', 'anthropic').
	 *
	 * @return bool
	 */
	public function isGroupBlocked( string $group ): bool
	{
		$groups = $this->getGroups();

		if ( ! isset( $groups[ $group ] ) ) {
			return false;
		}

		$hasBlockedFlag = array_key_exists( 'blocked', $groups[ $group ] );
		$explicit       = $hasBlockedFlag ? (bool) $groups[ $group ]['blocked'] : null;

		if ( $this->defaultAllow() ) {
			return true === $explicit;
		}

		return false !== $explicit;
	}

	/**
	 * Get the user-agents for a named group.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $group  The group key.
	 *
	 * @return array<int, string>
	 */
	public function getUserAgentsForGroup( string $group ): array
	{
		$groups = $this->getGroups();

		if ( ! isset( $groups[ $group ] ) ) {
			return [];
		}

		$userAgents = $groups[ $group ]['user_agents'] ?? [];

		return array_values( array_filter( array_map( 'strval', (array) $userAgents ) ) );
	}

	/**
	 * Get every user-agent for groups whose 'blocked' flag is true.
	 *
	 * Host UA middleware can use this list to short-circuit blocked bots
	 * with the same decision the robots.txt output advertises.
	 *
	 * @since 1.4.0
	 *
	 * @return array<int, string>
	 */
	public function getBlockedUserAgents(): array
	{
		$blocked      = [];
		$defaultAllow = $this->defaultAllow();

		foreach ( $this->getGroups() as $key => $group ) {
			$hasBlockedFlag = array_key_exists( 'blocked', $group );
			$explicit       = $hasBlockedFlag ? (bool) $group['blocked'] : null;

			if ( $defaultAllow ) {
				// Opt-in mode: only groups explicitly flagged blocked=true are disallowed.
				$isBlocked = true === $explicit;
			} else {
				// Kill-switch mode: every group is blocked unless explicitly opted back in.
				$isBlocked = false !== $explicit;
			}

			if ( ! $isBlocked ) {
				continue;
			}

			foreach ( $this->getUserAgentsForGroup( (string) $key ) as $userAgent ) {
				if ( ! in_array( $userAgent, $blocked, true ) ) {
					$blocked[] = $userAgent;
				}
			}
		}

		return $blocked;
	}

	/**
	 * Get the resolved rule set for every configured group.
	 *
	 * Each entry has: label, user_agents, blocked, allow (bool). This is
	 * the same view host middleware and robots.txt consume, so they cannot
	 * disagree about whether a bot is allowed.
	 *
	 * @since 1.4.0
	 *
	 * @return array<string, array{
	 *     label: string,
	 *     user_agents: array<int, string>,
	 *     blocked: bool,
	 *     allow: bool,
	 * }>
	 */
	public function getResolvedRules(): array
	{
		$defaultAllow = $this->defaultAllow();
		$resolved     = [];

		foreach ( $this->getGroups() as $key => $group ) {
			$key            = (string) $key;
			$hasBlockedFlag = array_key_exists( 'blocked', $group );
			$explicit       = $hasBlockedFlag ? (bool) $group['blocked'] : null;

			$blocked = $defaultAllow ? true === $explicit : false !== $explicit;

			$resolved[ $key ] = [
				'label'       => (string) ( $group['label'] ?? $key ),
				'user_agents' => $this->getUserAgentsForGroup( $key ),
				'blocked'     => $blocked,
				'allow'       => ! $blocked,
			];
		}

		return $resolved;
	}

	/**
	 * Determine whether a raw request user-agent string matches a blocked group.
	 *
	 * Case-insensitive substring match against every user-agent token in
	 * blocked groups. Empty strings never match.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $userAgent  Raw User-Agent header value.
	 *
	 * @return bool
	 */
	public function isUserAgentBlocked( string $userAgent ): bool
	{
		if ( '' === $userAgent ) {
			return false;
		}

		$haystack = strtolower( $userAgent );

		foreach ( $this->getBlockedUserAgents() as $needle ) {
			if ( '' === $needle ) {
				continue;
			}

			if ( str_contains( $haystack, strtolower( $needle ) ) ) {
				return true;
			}
		}

		return false;
	}
}
