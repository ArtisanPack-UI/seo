<?php
/**
 * SitemapIndexGenerator.
 *
 * Generates sitemap index XML for large sites.
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

namespace ArtisanPackUI\SEO\Sitemap\Generators;

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use Illuminate\Support\Collection;
use XMLWriter;

/**
 * SitemapIndexGenerator class.
 *
 * Generates sitemap index XML files for sites with multiple sitemaps.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SitemapIndexGenerator
{
	/**
	 * The XML namespace for sitemap index.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected const SITEMAP_NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';

	/**
	 * Maximum URLs per sitemap file.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected int $maxUrls;

	/**
	 * The base URL for sitemap files.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $baseUrl;

	/**
	 * Registered sitemap providers.
	 *
	 * @since 1.0.0
	 *
	 * @var Collection<string, SitemapProviderContract>
	 */
	protected Collection $providers;

	/**
	 * Create a new SitemapIndexGenerator instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null  $baseUrl  The base URL for sitemap files.
	 * @param  int|null     $maxUrls  Maximum URLs per sitemap file.
	 */
	public function __construct( ?string $baseUrl = null, ?int $maxUrls = null )
	{
		$this->baseUrl   = $baseUrl ?? config( 'app.url', '' );
		$this->maxUrls   = $maxUrls ?? (int) config( 'seo.sitemap.max_urls_per_file', 10000 );
		$this->providers = collect();
	}

	/**
	 * Set registered sitemap providers.
	 *
	 * @since 1.0.0
	 *
	 * @param  Collection<string, SitemapProviderContract>  $providers  The providers collection.
	 *
	 * @return self
	 */
	public function setProviders( Collection $providers ): self
	{
		$this->providers = $providers;

		return $this;
	}

	/**
	 * Generate the sitemap index XML.
	 *
	 * @since 1.0.0
	 *
	 * @return string The generated XML sitemap index.
	 */
	public function generate(): string
	{
		$sitemaps = $this->getSitemapList();

		return $this->buildXml( $sitemaps );
	}

	/**
	 * Check if a sitemap index is needed.
	 *
	 * Returns true if there are multiple sitemap types or pages.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function needsIndex(): bool
	{
		$generator  = new SitemapGenerator( $this->maxUrls );
		$totalPages = $generator->getTotalPages();

		// Need index if multiple pages
		if ( $totalPages > 1 ) {
			return true;
		}

		// Need index if multiple types
		$types = SitemapEntry::getAvailableTypes();
		if ( count( $types ) > 1 ) {
			return true;
		}

		// Need index if specialized sitemaps are enabled
		if ( true === config( 'seo.sitemap.types.image', false )
			|| true === config( 'seo.sitemap.types.video', false )
			|| true === config( 'seo.sitemap.types.news', false )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Get the list of sitemaps to include in the index.
	 *
	 * @since 1.0.0
	 *
	 * @return Collection<int, array<string, mixed>>
	 */
	protected function getSitemapList(): Collection
	{
		$sitemaps  = collect();
		$types     = SitemapEntry::getAvailableTypes();
		$generator = new SitemapGenerator( $this->maxUrls );

		// Add main sitemap if there are entries without specific type filtering
		$totalPages = $generator->getTotalPages();
		if ( $totalPages > 1 ) {
			// Multiple pages for all entries
			for ( $page = 1; $page <= $totalPages; $page++ ) {
				$sitemaps->push( $this->buildSitemapEntry( null, $page ) );
			}
		} elseif ( $totalPages > 0 ) {
			// Single sitemap for all entries
			$sitemaps->push( $this->buildSitemapEntry() );
		}

		// Add type-specific sitemaps
		foreach ( $types as $type ) {
			$typePages = $generator->getTotalPages( $type );

			for ( $page = 1; $page <= $typePages; $page++ ) {
				$sitemaps->push( $this->buildSitemapEntry( $type, $page > 1 ? $page : null ) );
			}
		}

		// Add provider-specific sitemaps
		foreach ( $this->providers->keys() as $providerType ) {
			// Skip if type is already in database types (already handled above)
			if ( in_array( $providerType, $types, true ) ) {
				continue;
			}

			$sitemaps->push( [
				'loc'     => $this->getSitemapUrl( $providerType ),
				'lastmod' => now()->format( 'c' ),
			] );
		}

		// Add image sitemap if enabled
		if ( true === config( 'seo.sitemap.types.image', false ) ) {
			$imageCount = SitemapEntry::withImages()->count();
			if ( $imageCount > 0 ) {
				$sitemaps->push( [
					'loc'     => $this->getSitemapUrl( 'images' ),
					'lastmod' => SitemapEntry::withImages()
						->orderByLastModified()
						->first()
						?->getLastModifiedForSitemap(),
				] );
			}
		}

		// Add video sitemap if enabled
		if ( true === config( 'seo.sitemap.types.video', false ) ) {
			$videoCount = SitemapEntry::withVideos()->count();
			if ( $videoCount > 0 ) {
				$sitemaps->push( [
					'loc'     => $this->getSitemapUrl( 'videos' ),
					'lastmod' => SitemapEntry::withVideos()
						->orderByLastModified()
						->first()
						?->getLastModifiedForSitemap(),
				] );
			}
		}

		// Add news sitemap if enabled
		if ( true === config( 'seo.sitemap.types.news', false ) ) {
			$sitemaps->push( [
				'loc'     => $this->getSitemapUrl( 'news' ),
				'lastmod' => now()->format( 'c' ),
			] );
		}

		return $sitemaps->unique( 'loc' );
	}

	/**
	 * Build a sitemap entry for the index.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null  $type  The sitemap type.
	 * @param  int|null     $page  The page number.
	 *
	 * @return array<string, mixed>
	 */
	protected function buildSitemapEntry( ?string $type = null, ?int $page = null ): array
	{
		$query = SitemapEntry::indexable()->orderByLastModified();

		if ( null !== $type ) {
			$query->byType( $type );
		}

		$lastEntry = $query->first();

		return [
			'loc'     => $this->getSitemapUrl( $type, $page ),
			'lastmod' => $lastEntry?->getLastModifiedForSitemap(),
		];
	}

	/**
	 * Get the URL for a sitemap file.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null  $type  The sitemap type.
	 * @param  int|null     $page  The page number.
	 *
	 * @return string
	 */
	protected function getSitemapUrl( ?string $type = null, ?int $page = null ): string
	{
		$path = config( 'seo.sitemap.route_path', 'sitemap.xml' );
		$url  = rtrim( $this->baseUrl, '/' ) . '/' . ltrim( $path, '/' );

		if ( null !== $type ) {
			$url = str_replace( '.xml', "-{$type}.xml", $url );
		}

		if ( null !== $page && $page > 1 ) {
			$url = str_replace( '.xml', "-{$page}.xml", $url );
		}

		return $url;
	}

	/**
	 * Build the sitemap index XML.
	 *
	 * @since 1.0.0
	 *
	 * @param  Collection<int, array<string, mixed>>  $sitemaps  The sitemap entries.
	 *
	 * @return string The generated XML.
	 */
	protected function buildXml( Collection $sitemaps ): string
	{
		$writer = new XMLWriter();
		$writer->openMemory();
		$writer->setIndent( true );
		$writer->setIndentString( "\t" );

		$writer->startDocument( '1.0', 'UTF-8' );
		$writer->startElement( 'sitemapindex' );
		$writer->writeAttribute( 'xmlns', self::SITEMAP_NS );

		foreach ( $sitemaps as $sitemap ) {
			$this->writeSitemapElement( $writer, $sitemap );
		}

		$writer->endElement(); // sitemapindex
		$writer->endDocument();

		return $writer->outputMemory();
	}

	/**
	 * Write a sitemap element to the XML writer.
	 *
	 * @since 1.0.0
	 *
	 * @param  XMLWriter              $writer   The XML writer instance.
	 * @param  array<string, mixed>   $sitemap  The sitemap data.
	 *
	 * @return void
	 */
	protected function writeSitemapElement( XMLWriter $writer, array $sitemap ): void
	{
		$writer->startElement( 'sitemap' );

		$writer->writeElement( 'loc', $sitemap['loc'] );

		if ( ! empty( $sitemap['lastmod'] ) ) {
			$writer->writeElement( 'lastmod', $sitemap['lastmod'] );
		}

		$writer->endElement(); // sitemap
	}
}
