<?php

/**
 * OgImageService.
 *
 * Generates branded OG social-share images (MightyShare-style) with
 * deterministic caching so identical inputs always resolve to the
 * same file path.
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

use ArtisanPackUI\SEO\Contracts\OgImageRendererContract;
use ArtisanPackUI\SEO\DTOs\OgImageTemplate;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * OgImageService class.
 *
 * Coordinates the renderer + storage layer. The service:
 *
 *  - Resolves the effective template by merging the config-defined
 *    site-wide default with per-page overrides.
 *  - Computes a deterministic output path from the title + subtitle +
 *    template signature so identical inputs never re-render.
 *  - Delegates the actual pixel work to an OgImageRendererContract
 *    implementation (GD by default; see docs/og-image-backend-decision.md).
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
class OgImageService
{
	/**
	 * Create a new OgImageService instance.
	 *
	 * @since 1.4.0
	 *
	 * @param  OgImageRendererContract  $renderer  The pluggable image backend.
	 */
	public function __construct(
		protected OgImageRendererContract $renderer,
	) {
	}

	/**
	 * Generate an OG image and return its public URL.
	 *
	 * If the deterministic path already exists on the configured disk the
	 * cached image is reused, unless $forceRegenerate is true.
	 *
	 * SECURITY: the $overrides array can carry local file paths for
	 * `logo_path`, `background_image_path`, and `font_path`. The service
	 * treats those as trusted developer input (the renderer will open and
	 * read them). Never populate this array directly from untrusted user
	 * input — resolve any user-visible template choices to a whitelist of
	 * server-controlled paths first.
	 *
	 * @since 1.4.0
	 *
	 * @param  string                 $title           The primary title.
	 * @param  string|null            $subtitle        Optional subtitle.
	 * @param  array<string, mixed>   $overrides       Per-page template overrides.
	 * @param  bool                   $forceRegenerate Force regeneration even if cached.
	 *
	 * @return string The public URL to the rendered image.
	 */
	public function generate(
		string $title,
		?string $subtitle = null,
		array $overrides = [],
		bool $forceRegenerate = false,
	): string {
		$template = $this->resolveTemplate( $overrides );
		$path     = $this->path( $title, $subtitle, $template );
		$disk     = $this->disk();

		if ( $forceRegenerate || ! $disk->exists( $path ) ) {
			$this->renderUnderLock( $path, $template, $title, $subtitle, $disk, $forceRegenerate );
		}

		return $disk->url( $path );
	}

	/**
	 * Return the deterministic relative path for the given inputs.
	 *
	 * @since 1.4.0
	 *
	 * @param  string               $title    The primary title.
	 * @param  string|null          $subtitle Optional subtitle.
	 * @param  OgImageTemplate|null $template Optional resolved template.
	 *
	 * @return string A path relative to the configured disk root.
	 */
	public function path( string $title, ?string $subtitle = null, ?OgImageTemplate $template = null ): string
	{
		$template = $template ?? $this->resolveTemplate();
		$hash     = $this->hashFor( $title, $subtitle, $template );
		$dir      = trim( (string) config( 'seo.og_image.path', 'og-images' ), '/' );

		return ( '' === $dir ? '' : $dir . '/' ) . $hash . '.png';
	}

	/**
	 * Delete a previously generated image so it will be re-rendered
	 * on the next call to {@see generate()}.
	 *
	 * Use this from an observer when the source content (title,
	 * subtitle, or template) changes.
	 *
	 * @since 1.4.0
	 *
	 * @param  string               $title    The primary title used to generate.
	 * @param  string|null          $subtitle Optional subtitle used to generate.
	 * @param  OgImageTemplate|null $template Optional resolved template.
	 *
	 * @return bool True if a file was deleted, false if nothing was cached.
	 */
	public function forget( string $title, ?string $subtitle = null, ?OgImageTemplate $template = null ): bool
	{
		$path = $this->path( $title, $subtitle, $template );
		$disk = $this->disk();

		if ( ! $disk->exists( $path ) ) {
			return false;
		}

		return $disk->delete( $path );
	}

	/**
	 * Resolve the effective template by merging the site-wide default
	 * with per-page overrides.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<string, mixed> $overrides Per-page overrides.
	 *
	 * @return OgImageTemplate
	 */
	public function resolveTemplate( array $overrides = [] ): OgImageTemplate
	{
		$config = (array) config( 'seo.og_image.template', [] );

		return OgImageTemplate::fromConfig( $config, $overrides );
	}

	/**
	 * Render the image under a cache lock so concurrent misses don't each
	 * spend ~200ms of GD CPU on identical work. Falls back to a plain
	 * render when the underlying cache store doesn't support locks.
	 *
	 * @since 1.4.0
	 *
	 * @param  string           $path            Relative storage path.
	 * @param  OgImageTemplate  $template        Resolved template.
	 * @param  string           $title           Title text.
	 * @param  string|null      $subtitle        Optional subtitle.
	 * @param  Filesystem       $disk            Storage disk.
	 * @param  bool             $forceRegenerate Whether force flag was set.
	 *
	 * @return void
	 */
	protected function renderUnderLock(
		string $path,
		OgImageTemplate $template,
		string $title,
		?string $subtitle,
		Filesystem $disk,
		bool $forceRegenerate,
	): void {
		try {
			$lock = Cache::lock( 'og-image:' . $path, 30 );
			$lock->block( 5, function () use ( $disk, $path, $template, $title, $subtitle, $forceRegenerate ): void {
				// Re-check after acquiring the lock: another worker may have
				// finished the render while we were queued.
				if ( ! $forceRegenerate && $disk->exists( $path ) ) {
					return;
				}

				$disk->put( $path, $this->renderer->render( $template, $title, $subtitle ) );
			} );
		} catch ( Throwable $e ) {
			$disk->put( $path, $this->renderer->render( $template, $title, $subtitle ) );
		}
	}

	/**
	 * Compute the deterministic hash used as the filename.
	 *
	 * @since 1.4.0
	 *
	 * @param  string          $title    The title.
	 * @param  string|null     $subtitle The subtitle.
	 * @param  OgImageTemplate $template The resolved template.
	 *
	 * @return string A 40-character hex digest.
	 */
	protected function hashFor( string $title, ?string $subtitle, OgImageTemplate $template ): string
	{
		return substr( hash( 'sha256', $title . '|' . ( $subtitle ?? '' ) . '|' . $template->signature() ), 0, 40 );
	}

	/**
	 * Resolve the configured storage disk.
	 *
	 * @since 1.4.0
	 *
	 * @return Filesystem
	 */
	protected function disk(): Filesystem
	{
		return Storage::disk( (string) config( 'seo.og_image.disk', 'public' ) );
	}
}
