<?php
/**
 * GenerateSitemapCommand.
 *
 * Artisan command to generate sitemaps.
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

namespace ArtisanPackUI\SEO\Console\Commands;

use ArtisanPackUI\SEO\Services\SitemapService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * GenerateSitemapCommand class.
 *
 * Generates sitemap XML files and optionally saves them to disk.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class GenerateSitemapCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $signature = 'seo:generate-sitemap
		{--type= : Generate sitemap for a specific type}
		{--all : Generate all sitemaps including specialized ones}
		{--output= : Directory to save sitemap files}
		{--no-cache : Bypass cache when generating}
		{--clear-cache : Clear sitemap cache after generation}';

	/**
	 * The console command description.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $description = 'Generate XML sitemaps';

	/**
	 * Execute the console command.
	 *
	 * @since 1.0.0
	 *
	 * @param  SitemapService  $sitemapService  The sitemap service.
	 *
	 * @return int
	 */
	public function handle( SitemapService $sitemapService ): int
	{
		$type       = $this->option( 'type' );
		$all        = $this->option( 'all' );
		$output     = $this->option( 'output' );
		$noCache    = $this->option( 'no-cache' );
		$clearCache = $this->option( 'clear-cache' );

		// Disable caching if requested
		if ( $noCache ) {
			$sitemapService->setCacheEnabled( false );
		}

		$this->info( __( 'Generating sitemaps...' ) );

		try {
			if ( $output ) {
				$this->generateToFiles( $sitemapService, $output, $type, $all );
			} else {
				$this->generateToOutput( $sitemapService, $type, $all );
			}

			if ( $clearCache ) {
				$this->info( __( 'Clearing sitemap cache...' ) );
				$sitemapService->clearCache();
				$this->info( __( 'Cache cleared.' ) );
			}

			$this->newLine();
			$this->info( __( 'Sitemap generation complete!' ) );

			return Command::SUCCESS;
		} catch ( Exception $e ) {
			$this->error( __( 'Error generating sitemap: :error', [ 'error' => $e->getMessage() ] ) );

			return Command::FAILURE;
		}
	}

	/**
	 * Generate sitemaps to files.
	 *
	 * @since 1.0.0
	 *
	 * @param  SitemapService  $service  The sitemap service.
	 * @param  string          $output   Output directory.
	 * @param  string|null     $type     Specific type to generate.
	 * @param  bool            $all      Generate all sitemaps.
	 *
	 * @return void
	 */
	protected function generateToFiles( SitemapService $service, string $output, ?string $type, bool $all ): void
	{
		$outputPath    = rtrim( $output, '/' );
		$hasIndex      = false;

		if ( ! File::isDirectory( $outputPath ) ) {
			File::makeDirectory( $outputPath, 0755, true );
		}

		// Generate sitemap index if needed (when no specific type is requested)
		if ( null === $type && $service->needsIndex() ) {
			$this->info( __( 'Generating sitemap index...' ) );
			$indexXml = $service->generateIndex();
			File::put( "{$outputPath}/sitemap.xml", $indexXml );
			$this->line( __( '  Created: :file', [ 'file' => 'sitemap.xml' ] ) );
			$hasIndex = true;
		}

		// Generate standard sitemaps
		if ( null !== $type ) {
			$this->generateTypeSitemap( $service, $outputPath, $type );
		} else {
			$types = $service->getTypes();

			if ( empty( $types ) ) {
				// No specific types, generate main sitemap
				$this->generateMainSitemap( $service, $outputPath, $hasIndex );
			} else {
				foreach ( $types as $sitemapType ) {
					$this->generateTypeSitemap( $service, $outputPath, $sitemapType );
				}
			}
		}

		// Generate specialized sitemaps if --all flag
		if ( $all ) {
			$this->generateSpecializedSitemaps( $service, $outputPath );
		}
	}

	/**
	 * Generate the main sitemap (when no types are defined).
	 *
	 * @since 1.0.0
	 *
	 * @param  SitemapService  $service     The sitemap service.
	 * @param  string          $outputPath  Output directory.
	 * @param  bool            $hasIndex    Whether a sitemap index was created.
	 *
	 * @return void
	 */
	protected function generateMainSitemap( SitemapService $service, string $outputPath, bool $hasIndex ): void
	{
		$this->info( __( 'Generating main sitemap...' ) );
		$totalPages = $service->getTotalPages();

		for ( $page = 1; $page <= max( 1, $totalPages ); $page++ ) {
			$xml = $service->generate( null, $page );

			// When an index exists, use numbered filenames for all pages (sitemap-1.xml, sitemap-2.xml, etc.)
			// to avoid overwriting the index file (sitemap.xml)
			if ( $hasIndex ) {
				$filename = "sitemap-{$page}.xml";
			} else {
				$filename = $page > 1 ? "sitemap-{$page}.xml" : 'sitemap.xml';
			}

			File::put( "{$outputPath}/{$filename}", $xml );
			$this->line( __( '  Created: :file', [ 'file' => $filename ] ) );
		}
	}

	/**
	 * Generate a sitemap for a specific type.
	 *
	 * @since 1.0.0
	 *
	 * @param  SitemapService  $service     The sitemap service.
	 * @param  string          $outputPath  Output directory.
	 * @param  string          $type        The sitemap type.
	 *
	 * @return void
	 */
	protected function generateTypeSitemap( SitemapService $service, string $outputPath, string $type ): void
	{
		$this->info( __( 'Generating :type sitemap...', [ 'type' => $type ] ) );
		$totalPages = $service->getTotalPages( $type );

		for ( $page = 1; $page <= max( 1, $totalPages ); $page++ ) {
			$xml      = $service->generate( $type, $page );
			$filename = $page > 1 ? "sitemap-{$type}-{$page}.xml" : "sitemap-{$type}.xml";
			File::put( "{$outputPath}/{$filename}", $xml );
			$this->line( __( '  Created: :file', [ 'file' => $filename ] ) );
		}
	}

	/**
	 * Generate specialized sitemaps.
	 *
	 * @since 1.0.0
	 *
	 * @param  SitemapService  $service     The sitemap service.
	 * @param  string          $outputPath  Output directory.
	 *
	 * @return void
	 */
	protected function generateSpecializedSitemaps( SitemapService $service, string $outputPath ): void
	{
		// Image sitemap
		if ( $service->isImageSitemapEnabled() ) {
			$this->info( __( 'Generating image sitemap...' ) );
			$xml = $service->generateImages();
			File::put( "{$outputPath}/sitemap-images.xml", $xml );
			$this->line( __( '  Created: :file', [ 'file' => 'sitemap-images.xml' ] ) );
		}

		// Video sitemap
		if ( $service->isVideoSitemapEnabled() ) {
			$this->info( __( 'Generating video sitemap...' ) );
			$xml = $service->generateVideos();
			File::put( "{$outputPath}/sitemap-videos.xml", $xml );
			$this->line( __( '  Created: :file', [ 'file' => 'sitemap-videos.xml' ] ) );
		}

		// News sitemap
		if ( $service->isNewsSitemapEnabled() ) {
			$this->info( __( 'Generating news sitemap...' ) );
			$xml = $service->generateNews();
			File::put( "{$outputPath}/sitemap-news.xml", $xml );
			$this->line( __( '  Created: :file', [ 'file' => 'sitemap-news.xml' ] ) );
		}
	}

	/**
	 * Generate sitemaps to console output.
	 *
	 * @since 1.0.0
	 *
	 * @param  SitemapService  $service  The sitemap service.
	 * @param  string|null     $type     Specific type to generate.
	 * @param  bool            $all      Generate all sitemaps.
	 *
	 * @return void
	 */
	protected function generateToOutput( SitemapService $service, ?string $type, bool $all ): void
	{
		// Show statistics instead of XML when no output directory specified
		$types = $service->getTypes();

		$this->newLine();
		$this->info( __( 'Sitemap Statistics:' ) );
		$this->newLine();

		$this->table(
			[ __( 'Type' ), __( 'Pages' ), __( 'Enabled' ) ],
			$this->getStatisticsRows( $service, $types ),
		);

		$this->newLine();
		$this->line( __( 'Max URLs per sitemap: :max', [ 'max' => $service->getMaxUrls() ] ) );
		$this->line( __( 'Caching enabled: :status', [
			'status' => $service->isCacheEnabled() ? __( 'Yes' ) : __( 'No' ),
		] ) );
		$this->line( __( 'Needs sitemap index: :status', [
			'status' => $service->needsIndex() ? __( 'Yes' ) : __( 'No' ),
		] ) );

		$this->newLine();
		$this->info( __( 'Use --output=<path> to generate files to disk.' ) );
	}

	/**
	 * Get statistics rows for the table.
	 *
	 * @since 1.0.0
	 *
	 * @param  SitemapService       $service  The sitemap service.
	 * @param  array<int, string>   $types    Available types.
	 *
	 * @return array<int, array<int, string>>
	 */
	protected function getStatisticsRows( SitemapService $service, array $types ): array
	{
		$rows = [];

		// Standard sitemaps
		if ( empty( $types ) ) {
			$rows[] = [
				__( 'Main' ),
				(string) $service->getTotalPages(),
				__( 'Yes' ),
			];
		} else {
			foreach ( $types as $type ) {
				$rows[] = [
					$type,
					(string) $service->getTotalPages( $type ),
					__( 'Yes' ),
				];
			}
		}

		// Specialized sitemaps
		$rows[] = [
			__( 'Images' ),
			'-',
			$service->isImageSitemapEnabled() ? __( 'Yes' ) : __( 'No' ),
		];

		$rows[] = [
			__( 'Videos' ),
			'-',
			$service->isVideoSitemapEnabled() ? __( 'Yes' ) : __( 'No' ),
		];

		$rows[] = [
			__( 'News' ),
			'-',
			$service->isNewsSitemapEnabled() ? __( 'Yes' ) : __( 'No' ),
		];

		return $rows;
	}
}
