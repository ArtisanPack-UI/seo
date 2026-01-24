<?php

/**
 * SitemapGenerator.
 *
 * Generates standard XML sitemaps.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Sitemap\Generators;

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use ArtisanPackUI\SEO\Models\SitemapEntry;
use DateTimeInterface;
use Illuminate\Support\Collection;
use XMLWriter;

/**
 * SitemapGenerator class.
 *
 * Generates standard XML sitemaps following the sitemap protocol.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class SitemapGenerator
{
	/**
	 * The XML namespace for sitemaps.
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
	 * Create a new SitemapGenerator instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  int|null  $maxUrls  Maximum URLs per sitemap file.
	 */
	public function __construct( ?int $maxUrls = null )
	{
		$this->maxUrls = $maxUrls ?? (int) config( 'seo.sitemap.max_urls_per_file', 10000 );
	}

	/**
	 * Generate a sitemap XML string for the given type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null  $type   The sitemap type to generate.
	 * @param  int          $page   Page number for pagination.
	 *
	 * @return string The generated XML sitemap.
	 */
	public function generate( ?string $type = null, int $page = 1 ): string
	{
		$entries = $this->getEntries( $type, $page );

		return $this->buildXml( $entries );
	}

	/**
	 * Generate a sitemap from a custom provider.
	 *
	 * @since 1.0.0
	 *
	 * @param  SitemapProviderContract  $provider  The sitemap provider.
	 *
	 * @return string The generated XML sitemap.
	 */
	public function generateFromProvider( SitemapProviderContract $provider ): string
	{
		$urls = $provider->getUrls();

		$entries = $urls->map( function ( $url ) use ( $provider ) {
			return $this->normalizeProviderUrl( $url, $provider );
		} )->filter();

		return $this->buildXml( $entries );
	}

	/**
	 * Get the total number of pages for a sitemap type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null  $type  The sitemap type.
	 *
	 * @return int
	 */
	public function getTotalPages( ?string $type = null ): int
	{
		$query = SitemapEntry::indexable();

		if ( null !== $type ) {
			$query->byType( $type );
		}

		$count = $query->count();

		return (int) ceil( $count / max( 1, $this->maxUrls ) );
	}

	/**
	 * Get the maximum URLs per sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getMaxUrls(): int
	{
		return $this->maxUrls;
	}

	/**
	 * Get sitemap entries for the given type and page.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null  $type  The sitemap type.
	 * @param  int          $page  Page number for pagination.
	 *
	 * @return Collection<int, SitemapEntry>
	 */
	protected function getEntries( ?string $type, int $page ): Collection
	{
		$query = SitemapEntry::indexable()
			->orderByLastModified();

		if ( null !== $type ) {
			$query->byType( $type );
		}

		return $query
			->skip( ( $page - 1 ) * $this->maxUrls )
			->take( $this->maxUrls )
			->get();
	}

	/**
	 * Build XML from sitemap entries.
	 *
	 * @since 1.0.0
	 *
	 * @param  Collection  $entries  The sitemap entries.
	 *
	 * @return string The generated XML.
	 */
	protected function buildXml( Collection $entries ): string
	{
		$writer = new XMLWriter();
		$writer->openMemory();
		$writer->setIndent( true );
		$writer->setIndentString( "\t" );

		$writer->startDocument( '1.0', 'UTF-8' );
		$writer->startElement( 'urlset' );
		$writer->writeAttribute( 'xmlns', self::SITEMAP_NS );

		foreach ( $entries as $entry ) {
			$this->writeUrlElement( $writer, $entry );
		}

		$writer->endElement(); // urlset
		$writer->endDocument();

		return $writer->outputMemory();
	}

	/**
	 * Write a URL element to the XML writer.
	 *
	 * @since 1.0.0
	 *
	 * @param  XMLWriter                 $writer  The XML writer instance.
	 * @param  array<string, mixed>|SitemapEntry  $entry   The sitemap entry or normalized array.
	 *
	 * @return void
	 */
	protected function writeUrlElement( XMLWriter $writer, SitemapEntry|array $entry ): void
	{
		$writer->startElement( 'url' );

		// Location (required)
		$writer->writeElement( 'loc', $this->getEntryValue( $entry, 'url' ) );

		// Last modified (optional)
		$lastmod = $this->getLastmod( $entry );
		if ( null !== $lastmod ) {
			$writer->writeElement( 'lastmod', $lastmod );
		}

		// Change frequency (optional)
		$changefreq = $this->getEntryValue( $entry, 'changefreq' );
		if ( null !== $changefreq ) {
			$writer->writeElement( 'changefreq', $changefreq );
		}

		// Priority (optional)
		$priority = $this->getEntryValue( $entry, 'priority' );
		if ( null !== $priority ) {
			$writer->writeElement( 'priority', (string) $priority );
		}

		$writer->endElement(); // url
	}

	/**
	 * Get the lastmod value from an entry.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>|SitemapEntry  $entry  The sitemap entry.
	 *
	 * @return string|null
	 */
	protected function getLastmod( SitemapEntry|array $entry ): ?string
	{
		if ( $entry instanceof SitemapEntry ) {
			return $entry->getLastModifiedForSitemap();
		}

		$lastmod = $entry['lastmod'] ?? $entry['last_modified'] ?? null;

		if ( null === $lastmod ) {
			return null;
		}

		if ( $lastmod instanceof DateTimeInterface ) {
			return $lastmod->format( 'c' );
		}

		return (string) $lastmod;
	}

	/**
	 * Get a value from an entry (either model or array).
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>|SitemapEntry  $entry  The entry.
	 * @param  string                              $key    The key to retrieve.
	 *
	 * @return mixed
	 */
	protected function getEntryValue( SitemapEntry|array $entry, string $key ): mixed
	{
		if ( $entry instanceof SitemapEntry ) {
			return $entry->{$key};
		}

		return $entry[ $key ] ?? null;
	}

	/**
	 * Normalize a URL from a provider to a standard format.
	 *
	 * Returns null if no valid URL is found, allowing callers to filter out invalid entries.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>      $url       The URL data from provider.
	 * @param  SitemapProviderContract   $provider  The provider instance.
	 *
	 * @return array<string, mixed>|null Normalized URL data or null if URL is empty.
	 */
	protected function normalizeProviderUrl( array $url, SitemapProviderContract $provider ): ?array
	{
		$resolvedUrl = $url['loc'] ?? $url['url'] ?? '';

		if ( '' === $resolvedUrl || null === $resolvedUrl ) {
			return null;
		}

		return [
			'url'        => $resolvedUrl,
			'lastmod'    => $url['lastmod'] ?? $url['last_modified'] ?? null,
			'changefreq' => $url['changefreq'] ?? $provider->getChangeFrequency(),
			'priority'   => $url['priority'] ?? $provider->getPriority(),
		];
	}
}
