<?php

/**
 * SubmitSitemapCommand.
 *
 * Artisan command to submit sitemaps to search engines.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Console\Commands;

use ArtisanPackUI\SEO\Sitemap\SitemapSubmitter;
use Exception;
use Illuminate\Console\Command;

/**
 * SubmitSitemapCommand class.
 *
 * Pings search engines to notify them of sitemap updates.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SubmitSitemapCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $signature = 'seo:submit-sitemap
		{--url= : Custom sitemap URL to submit}
		{--engine= : Submit to a specific search engine only}';

	/**
	 * The console command description.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $description = 'Submit sitemap to search engines';

	/**
	 * Execute the console command.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function handle(): int
	{
		$customUrl = $this->option( 'url' );
		$engine    = $this->option( 'engine' );

		$submitter = new SitemapSubmitter( $customUrl );

		$this->info( __( 'Submitting sitemap to search engines...' ) );
		$this->newLine();

		$this->line( __( 'Sitemap URL: :url', [ 'url' => $submitter->getSitemapUrl() ] ) );
		$this->newLine();

		try {
			if ( $engine ) {
				$result = $this->submitToSingleEngine( $submitter, $engine );

				return true === $result ? Command::SUCCESS : Command::FAILURE;
			}

			$results = $submitter->submit();

			$this->displayResults( $results );

			if ( $submitter->allSuccessful() ) {
				$this->newLine();
				$this->info( __( 'All submissions successful!' ) );

				return Command::SUCCESS;
			}

			if ( $submitter->anySuccessful() ) {
				$this->newLine();
				$this->warn( __( 'Some submissions failed. Check the results above.' ) );

				return Command::SUCCESS;
			}

			$this->newLine();
			$this->error( __( 'All submissions failed.' ) );

			return Command::FAILURE;
		} catch ( Exception $e ) {
			$this->error( __( 'Error submitting sitemap: :error', [ 'error' => $e->getMessage() ] ) );

			return Command::FAILURE;
		}
	}

	/**
	 * Submit to a single search engine.
	 *
	 * @since 1.0.0
	 *
	 * @param  SitemapSubmitter  $submitter  The submitter instance.
	 * @param  string            $engine     The search engine name.
	 *
	 * @return bool
	 */
	protected function submitToSingleEngine( SitemapSubmitter $submitter, string $engine ): bool
	{
		$result = $submitter->submitTo( $engine );

		if ( null === $result ) {
			$this->error( __( 'Unknown search engine: :engine', [ 'engine' => $engine ] ) );
			$this->newLine();
			$this->info( __( 'Available search engines:' ) );

			foreach ( array_keys( $submitter->getSearchEngines() ) as $name ) {
				$this->line( "  - {$name}" );
			}

			return false;
		}

		$this->displaySingleResult( $engine, $result );

		return true === $result['success'];
	}

	/**
	 * Display submission results.
	 *
	 * @since 1.0.0
	 *
	 * @param  \Illuminate\Support\Collection<string, array<string, mixed>>  $results  The submission results.
	 *
	 * @return void
	 */
	protected function displayResults( $results ): void
	{
		$rows = [];

		foreach ( $results as $engine => $result ) {
			$status = true === $result['success']
				? '<fg=green>✓</>'
				: '<fg=red>✗</>';

			$rows[] = [
				$engine,
				$status,
				$result['status_code'] ?? '-',
				$result['response_time'] . 'ms',
				$this->truncate( $result['message'], 50 ),
			];
		}

		$this->table(
			[ __( 'Engine' ), __( 'Status' ), __( 'Code' ), __( 'Time' ), __( 'Message' ) ],
			$rows,
		);
	}

	/**
	 * Display a single result.
	 *
	 * @since 1.0.0
	 *
	 * @param  string               $engine  The search engine name.
	 * @param  array<string, mixed> $result  The submission result.
	 *
	 * @return void
	 */
	protected function displaySingleResult( string $engine, array $result ): void
	{
		if ( true === $result['success'] ) {
			$this->info( __( '✓ :engine: Submission successful', [ 'engine' => ucfirst( $engine ) ] ) );
		} else {
			$this->error( __( '✗ :engine: Submission failed', [ 'engine' => ucfirst( $engine ) ] ) );
		}

		$this->newLine();
		$this->line( __( 'Status Code: :code', [ 'code' => $result['status_code'] ?? 'N/A' ] ) );
		$this->line( __( 'Response Time: :time ms', [ 'time' => $result['response_time'] ] ) );
		$this->line( __( 'Message: :message', [ 'message' => $result['message'] ] ) );

		if ( ! empty( $result['exception'] ) ) {
			$this->newLine();
			$this->error( __( 'Exception: :exception', [ 'exception' => $result['exception'] ] ) );
		}
	}

	/**
	 * Truncate a string to a maximum length.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $string  The string to truncate.
	 * @param  int     $length  Maximum length.
	 *
	 * @return string
	 */
	protected function truncate( string $string, int $length ): string
	{
		if ( strlen( $string ) <= $length ) {
			return $string;
		}

		return substr( $string, 0, $length - 3 ) . '...';
	}
}
