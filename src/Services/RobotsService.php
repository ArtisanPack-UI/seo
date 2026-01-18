<?php
/**
 * RobotsService.
 *
 * Service for generating dynamic robots.txt content.
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

namespace ArtisanPackUI\SEO\Services;

/**
 * RobotsService class.
 *
 * Generates robots.txt content with support for bot-specific rules,
 * global rules, allow/disallow directives, crawl delay, and sitemap URLs.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class RobotsService
{
	/**
	 * Custom rules organized by user-agent.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $rules = [];

	/**
	 * Sitemap URLs to include.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	protected array $sitemapUrls = [];

	/**
	 * Additional host directive.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	protected ?string $host = null;

	/**
	 * Create a new RobotsService instance.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->loadConfigRules();
	}

	/**
	 * Generate the robots.txt content.
	 *
	 * @since 1.0.0
	 *
	 * @return string The robots.txt content.
	 */
	public function generate(): string
	{
		$content = '';

		// Ensure at least a default user-agent block exists
		if ( empty( $this->rules ) ) {
			$this->ensureUserAgentExists( '*' );
		}

		// Generate rules for each user-agent
		foreach ( $this->rules as $userAgent => $rules ) {
			$content .= $this->generateUserAgentBlock( $userAgent, $rules );
			$content .= "\n";
		}

		// Add sitemap URLs
		$content .= $this->generateSitemapDirectives();

		// Add host directive if set
		if ( null !== $this->host ) {
			$content .= "Host: {$this->host}\n";
		}

		return trim( $content ) . "\n";
	}

	/**
	 * Add a disallow rule for a user-agent.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path       The path to disallow.
	 * @param  string  $userAgent  The user-agent (default: *).
	 *
	 * @return self
	 */
	public function disallow( string $path, string $userAgent = '*' ): self
	{
		$this->ensureUserAgentExists( $userAgent );
		$this->rules[ $userAgent ]['disallow'][] = $path;

		return $this;
	}

	/**
	 * Add an allow rule for a user-agent.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $path       The path to allow.
	 * @param  string  $userAgent  The user-agent (default: *).
	 *
	 * @return self
	 */
	public function allow( string $path, string $userAgent = '*' ): self
	{
		$this->ensureUserAgentExists( $userAgent );
		$this->rules[ $userAgent ]['allow'][] = $path;

		return $this;
	}

	/**
	 * Set crawl delay for a user-agent.
	 *
	 * @since 1.0.0
	 *
	 * @param  int     $seconds    The crawl delay in seconds.
	 * @param  string  $userAgent  The user-agent (default: *).
	 *
	 * @return self
	 */
	public function crawlDelay( int $seconds, string $userAgent = '*' ): self
	{
		$this->ensureUserAgentExists( $userAgent );
		$this->rules[ $userAgent ]['crawl_delay'] = $seconds;

		return $this;
	}

	/**
	 * Add a sitemap URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $url  The sitemap URL.
	 *
	 * @return self
	 */
	public function addSitemap( string $url ): self
	{
		if ( ! in_array( $url, $this->sitemapUrls, true ) ) {
			$this->sitemapUrls[] = $url;
		}

		return $this;
	}

	/**
	 * Set the host directive.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null  $host  The host URL.
	 *
	 * @return self
	 */
	public function setHost( ?string $host ): self
	{
		$this->host = $host;

		return $this;
	}

	/**
	 * Get the host directive.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function getHost(): ?string
	{
		return $this->host;
	}

	/**
	 * Get all sitemap URLs.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public function getSitemapUrls(): array
	{
		return $this->sitemapUrls;
	}

	/**
	 * Get rules for a specific user-agent.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $userAgent  The user-agent.
	 *
	 * @return array<string, mixed>
	 */
	public function getRulesForUserAgent( string $userAgent ): array
	{
		return $this->rules[ $userAgent ] ?? [];
	}

	/**
	 * Get all user agents with rules.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public function getUserAgents(): array
	{
		return array_keys( $this->rules );
	}

	/**
	 * Clear all rules.
	 *
	 * @since 1.0.0
	 *
	 * @return self
	 */
	public function clearRules(): self
	{
		$this->rules       = [];
		$this->sitemapUrls = [];
		$this->host        = null;

		return $this;
	}

	/**
	 * Remove rules for a specific user-agent.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $userAgent  The user-agent.
	 *
	 * @return self
	 */
	public function removeUserAgent( string $userAgent ): self
	{
		unset( $this->rules[ $userAgent ] );

		return $this;
	}

	/**
	 * Check if robots.txt generation is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return (bool) config( 'seo.robots.enabled', true );
	}

	/**
	 * Check if the robots.txt route is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isRouteEnabled(): bool
	{
		return (bool) config( 'seo.robots.route_enabled', true );
	}

	/**
	 * Load rules from configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function loadConfigRules(): void
	{
		// Load bot-specific rules first
		$botRules = config( 'seo.robots.rules', [] );

		foreach ( $botRules as $userAgent => $rules ) {
			$this->ensureUserAgentExists( $userAgent );

			// Load disallow rules
			if ( ! empty( $rules['disallow'] ) ) {
				foreach ( (array) $rules['disallow'] as $path ) {
					$this->disallow( $path, $userAgent );
				}
			}

			// Load allow rules
			if ( ! empty( $rules['allow'] ) ) {
				foreach ( (array) $rules['allow'] as $path ) {
					$this->allow( $path, $userAgent );
				}
			}

			// Load crawl delay
			if ( isset( $rules['crawl_delay'] ) ) {
				$this->crawlDelay( (int) $rules['crawl_delay'], $userAgent );
			}
		}

		// Load global disallow rules (fallback to legacy config)
		$globalDisallow = config( 'seo.robots.disallow', [] );
		foreach ( $globalDisallow as $path ) {
			$this->disallow( $path );
		}

		// Load global allow rules (fallback to legacy config)
		$globalAllow = config( 'seo.robots.allow', [] );
		foreach ( $globalAllow as $path ) {
			$this->allow( $path );
		}

		// Load sitemap URL
		$this->loadSitemapUrl();

		// Load host
		$host = config( 'seo.robots.host' );
		if ( null !== $host ) {
			$this->setHost( $host );
		}
	}

	/**
	 * Load sitemap URL from configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function loadSitemapUrl(): void
	{
		$sitemapUrl = config( 'seo.robots.sitemap_url' );

		if ( null === $sitemapUrl && true === config( 'seo.sitemap.route_enabled', true ) ) {
			$sitemapUrl = url( config( 'seo.sitemap.route_path', 'sitemap.xml' ) );
		}

		if ( null !== $sitemapUrl ) {
			$this->addSitemap( $sitemapUrl );
		}

		// Load additional sitemap URLs
		$additionalSitemaps = config( 'seo.robots.sitemaps', [] );
		foreach ( $additionalSitemaps as $url ) {
			$this->addSitemap( $url );
		}
	}

	/**
	 * Ensure a user-agent exists in the rules array.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $userAgent  The user-agent.
	 *
	 * @return void
	 */
	protected function ensureUserAgentExists( string $userAgent ): void
	{
		if ( ! isset( $this->rules[ $userAgent ] ) ) {
			$this->rules[ $userAgent ] = [
				'disallow'    => [],
				'allow'       => [],
				'crawl_delay' => null,
			];
		}
	}

	/**
	 * Generate a user-agent block.
	 *
	 * @since 1.0.0
	 *
	 * @param  string               $userAgent  The user-agent.
	 * @param  array<string, mixed> $rules      The rules for this user-agent.
	 *
	 * @return string
	 */
	protected function generateUserAgentBlock( string $userAgent, array $rules ): string
	{
		$content = "User-agent: {$userAgent}\n";

		// Add allow rules first (order matters for some crawlers)
		if ( ! empty( $rules['allow'] ) ) {
			$uniqueAllow = array_unique( $rules['allow'] );
			foreach ( $uniqueAllow as $path ) {
				$content .= "Allow: {$path}\n";
			}
		}

		// Add disallow rules
		if ( ! empty( $rules['disallow'] ) ) {
			$uniqueDisallow = array_unique( $rules['disallow'] );
			foreach ( $uniqueDisallow as $path ) {
				$content .= "Disallow: {$path}\n";
			}
		}

		// Add crawl delay
		if ( null !== $rules['crawl_delay'] && $rules['crawl_delay'] > 0 ) {
			$content .= "Crawl-delay: {$rules['crawl_delay']}\n";
		}

		return $content;
	}

	/**
	 * Generate sitemap directives.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function generateSitemapDirectives(): string
	{
		$content = '';

		if ( ! empty( $this->sitemapUrls ) ) {
			$content .= "\n";

			foreach ( $this->sitemapUrls as $url ) {
				$content .= "Sitemap: {$url}\n";
			}
		}

		return $content;
	}
}
