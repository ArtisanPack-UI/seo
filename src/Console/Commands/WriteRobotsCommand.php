<?php

/**
 * WriteRobotsCommand.
 *
 * Artisan command to sync the static robots.txt file.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.9.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Console\Commands;

use ArtisanPackUI\SEO\Services\RobotsService;
use Exception;
use Illuminate\Console\Command;

/**
 * WriteRobotsCommand class.
 *
 * Writes public/robots.txt when `seo.robots.write_static` is on and removes
 * it when off. Also runs as part of `php artisan optimize`.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.9.0
 */
class WriteRobotsCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @since 1.9.0
	 *
	 * @var string
	 */
	protected $signature = 'seo:write-robots
		{--force : Overwrite a robots.txt this package did not write}';

	/**
	 * The console command description.
	 *
	 * @since 1.9.0
	 *
	 * @var string
	 */
	protected $description = 'Sync public/robots.txt with the seo.robots settings';

	/**
	 * Execute the console command.
	 *
	 * @since 1.9.0
	 *
	 * @param  RobotsService  $robotsService  The robots service.
	 *
	 * @return int
	 */
	public function handle( RobotsService $robotsService ): int
	{
		try {
			$status = $robotsService->syncStaticFile( (bool) $this->option( 'force' ) );
		} catch ( Exception $e ) {
			$this->error( __( 'Error writing robots.txt: :error', [ 'error' => $e->getMessage() ] ) );

			return Command::FAILURE;
		}

		if ( 'unmanaged' === $status ) {
			$this->error( __( 'public/robots.txt was not written by this package, so it was left alone. Delete it or use --force to replace it.' ) );

			return Command::FAILURE;
		}

		match ( $status ) {
			'written'   => $this->info( __( 'public/robots.txt was written.' ) ),
			'unchanged' => $this->info( __( 'public/robots.txt is already up to date.' ) ),
			'removed'   => $this->info( __( 'public/robots.txt was removed.' ) ),
			default     => $this->info( __( 'Static robots.txt is turned off. Nothing to do.' ) ),
		};

		return Command::SUCCESS;
	}
}
