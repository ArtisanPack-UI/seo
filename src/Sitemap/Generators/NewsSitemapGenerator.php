<?php

/**
 * NewsSitemapGenerator.
 *
 * Generates Google News sitemaps.
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
use Illuminate\Support\Facades\Log;
use Throwable;
use XMLWriter;

/**
 * NewsSitemapGenerator class.
 *
 * Generates Google News sitemaps with the news sitemap extension.
 * Only includes articles published within the last 2 days as per Google's requirements.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @see https://developers.google.com/search/docs/advanced/sitemaps/news-sitemap
 * @since      1.0.0
 */
class NewsSitemapGenerator
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
	 * The XML namespace for news sitemaps.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected const NEWS_NS = 'http://www.google.com/schemas/sitemap-news/0.9';

	/**
	 * Maximum URLs per sitemap file.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected int $maxUrls;

	/**
	 * Maximum age for news articles in days.
	 *
	 * Google News only indexes articles from the past 2 days.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected int $maxAgeDays;

	/**
	 * The publication name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $publicationName;

	/**
	 * The publication language.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $publicationLanguage;

	/**
	 * News content types to include.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	protected array $newsTypes;

	/**
	 * Create a new NewsSitemapGenerator instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  string|null        $publicationName      The publication name.
	 * @param  string|null        $publicationLanguage  The publication language.
	 * @param  array<int, string>|null  $newsTypes            Content types to include.
	 * @param  int|null           $maxUrls              Maximum URLs per sitemap file.
	 * @param  int|null           $maxAgeDays           Maximum age for news articles.
	 */
	public function __construct(
		?string $publicationName = null,
		?string $publicationLanguage = null,
		?array $newsTypes = null,
		?int $maxUrls = null,
		?int $maxAgeDays = null,
	) {
		$this->publicationName     = $publicationName ?? config( 'app.name', 'Laravel' );
		$this->publicationLanguage = $publicationLanguage ?? config( 'app.locale', 'en' );
		$this->newsTypes           = $newsTypes ?? config( 'seo.sitemap.news.types', [ 'article', 'post', 'news' ] );
		$this->maxUrls             = $maxUrls ?? (int) config( 'seo.sitemap.max_urls_per_file', 10000 );
		$this->maxAgeDays          = $maxAgeDays ?? (int) config( 'seo.sitemap.news.max_age_days', 2 );
	}

	/**
	 * Generate a news sitemap XML string.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $page  Page number for pagination.
	 *
	 * @return string The generated XML sitemap.
	 */
	public function generate( int $page = 1 ): string
	{
		$entries = $this->getEntries( $page );

		return $this->buildXml( $entries );
	}

	/**
	 * Generate a news sitemap from a custom provider.
	 *
	 * @since 1.0.0
	 *
	 * @param  SitemapProviderContract  $provider  The sitemap provider.
	 *
	 * @return string The generated XML sitemap.
	 */
	public function generateFromProvider( SitemapProviderContract $provider ): string
	{
		$urls    = $provider->getUrls();
		$cutoff  = now()->subDays( $this->maxAgeDays );
		$entries = $urls->filter( function ( $url ) use ( $cutoff ) {
			$date = $url['publication_date'] ?? $url['lastmod'] ?? null;

			if ( null === $date ) {
				return false;
			}

			if ( $date instanceof DateTimeInterface ) {
				return $date >= $cutoff;
			}

			$timestamp = strtotime( (string) $date );

			return false !== $timestamp && $timestamp >= $cutoff->timestamp;
		} );

		return $this->buildXml( $entries );
	}

	/**
	 * Get the total number of pages for the news sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getTotalPages(): int
	{
		$count = SitemapEntry::indexable()
			->whereIn( 'type', $this->newsTypes )
			->where( 'last_modified', '>=', now()->subDays( $this->maxAgeDays ) )
			->count();

		return (int) ceil( $count / max( 1, $this->maxUrls ) );
	}

	/**
	 * Check if there are any news articles to include in the sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function hasNews(): bool
	{
		return SitemapEntry::indexable()
			->whereIn( 'type', $this->newsTypes )
			->where( 'last_modified', '>=', now()->subDays( $this->maxAgeDays ) )
			->exists();
	}

	/**
	 * Set the news content types to include.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<int, string>  $types  Content types to include.
	 *
	 * @return self
	 */
	public function setNewsTypes( array $types ): self
	{
		$this->newsTypes = $types;

		return $this;
	}

	/**
	 * Set the publication name.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $name  The publication name.
	 *
	 * @return self
	 */
	public function setPublicationName( string $name ): self
	{
		$this->publicationName = $name;

		return $this;
	}

	/**
	 * Set the publication language.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $language  The publication language.
	 *
	 * @return self
	 */
	public function setPublicationLanguage( string $language ): self
	{
		$this->publicationLanguage = $language;

		return $this;
	}

	/**
	 * Get news sitemap entries.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $page  Page number for pagination.
	 *
	 * @return Collection<int, SitemapEntry>
	 */
	protected function getEntries( int $page ): Collection
	{
		return SitemapEntry::indexable()
			->whereIn( 'type', $this->newsTypes )
			->where( 'last_modified', '>=', now()->subDays( $this->maxAgeDays ) )
			->orderByLastModified()
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
		$writer->writeAttribute( 'xmlns:news', self::NEWS_NS );

		$skippedCount = 0;

		foreach ( $entries as $entry ) {
			// Google News requires a title - skip entries without one
			$title = $this->getEntryValue( $entry, 'title' );

			if ( null === $title || '' === $title ) {
				$skippedCount++;

				continue;
			}

			$this->writeUrlElement( $writer, $entry, $title );
		}

		if ( $skippedCount > 0 ) {
			Log::warning( __( 'Skipped :count news sitemap entries without titles (Google News requires title).', [ 'count' => $skippedCount ] ) );
		}

		$writer->endElement(); // urlset
		$writer->endDocument();

		return $writer->outputMemory();
	}

	/**
	 * Write a URL element with news data to the XML writer.
	 *
	 * @since 1.0.0
	 *
	 * @param  XMLWriter                         $writer  The XML writer instance.
	 * @param  array<string, mixed>|SitemapEntry $entry   The sitemap entry.
	 * @param  string                            $title   The pre-validated title.
	 *
	 * @return void
	 */
	protected function writeUrlElement( XMLWriter $writer, SitemapEntry|array $entry, string $title ): void
	{
		$writer->startElement( 'url' );

		// Location (required)
		$writer->writeElement( 'loc', $this->getEntryValue( $entry, 'url' ) );

		// News element
		$writer->startElement( 'news:news' );

		// Publication (required)
		$writer->startElement( 'news:publication' );
		$writer->writeElement( 'news:name', $this->getPublicationName( $entry ) );
		$writer->writeElement( 'news:language', $this->getPublicationLanguage( $entry ) );
		$writer->endElement(); // news:publication

		// Publication date (required)
		$pubDate = $this->getPublicationDate( $entry );
		if ( null !== $pubDate ) {
			$writer->writeElement( 'news:publication_date', $pubDate );
		}

		// Title (required - already validated in buildXml)
		$writer->writeElement( 'news:title', $title );

		// Keywords (optional, deprecated but still accepted)
		$keywords = $this->getEntryValue( $entry, 'keywords' );
		if ( null !== $keywords ) {
			$keywordsString = is_array( $keywords ) ? implode( ', ', $keywords ) : $keywords;
			$writer->writeElement( 'news:keywords', $keywordsString );
		}

		// Stock tickers (optional)
		$stockTickers = $this->getEntryValue( $entry, 'stock_tickers' );
		if ( null !== $stockTickers ) {
			$tickersString = is_array( $stockTickers ) ? implode( ', ', $stockTickers ) : $stockTickers;
			$writer->writeElement( 'news:stock_tickers', $tickersString );
		}

		$writer->endElement(); // news:news

		$writer->endElement(); // url
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
			// For news-specific data, try to get from related sitemapable model
			if ( 'title' === $key ) {
				return $this->getTitleFromSitemapable( $entry );
			}

			if ( 'keywords' === $key || 'stock_tickers' === $key ) {
				// These are optional and not typically stored on the sitemapable model
				return null;
			}

			return $entry->{$key};
		}

		// For URL key, fall back to 'loc' for compatibility
		if ( 'url' === $key ) {
			return $entry[ $key ] ?? $entry['loc'] ?? null;
		}

		return $entry[ $key ] ?? null;
	}

	/**
	 * Get the title from the related sitemapable model.
	 *
	 * Attempts to retrieve the title from the polymorphic sitemapable relationship.
	 * Supports models with a `title` property, `getTitle()` method, or `name` property.
	 *
	 * @since 1.0.0
	 *
	 * @param  SitemapEntry  $entry  The sitemap entry.
	 *
	 * @return string|null
	 */
	protected function getTitleFromSitemapable( SitemapEntry $entry ): ?string
	{
		try {
			$sitemapable = $entry->sitemapable;

			if ( null === $sitemapable ) {
				return null;
			}

			// Check for getTitle() method first (allows custom implementation)
			if ( method_exists( $sitemapable, 'getTitle' ) ) {
				return $sitemapable->getTitle();
			}

			// Check for title property
			if ( isset( $sitemapable->title ) && ! empty( $sitemapable->title ) ) {
				return $sitemapable->title;
			}

			// Fall back to name property (common for some models)
			if ( isset( $sitemapable->name ) && ! empty( $sitemapable->name ) ) {
				return $sitemapable->name;
			}
		} catch ( Throwable ) {
			// If the related model class doesn't exist or relationship fails, return null
		}

		return null;
	}

	/**
	 * Get the publication name for an entry.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>|SitemapEntry  $entry  The entry.
	 *
	 * @return string
	 */
	protected function getPublicationName( SitemapEntry|array $entry ): string
	{
		if ( is_array( $entry ) && ! empty( $entry['publication_name'] ) ) {
			return $entry['publication_name'];
		}

		return $this->publicationName;
	}

	/**
	 * Get the publication language for an entry.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>|SitemapEntry  $entry  The entry.
	 *
	 * @return string
	 */
	protected function getPublicationLanguage( SitemapEntry|array $entry ): string
	{
		if ( is_array( $entry ) && ! empty( $entry['language'] ) ) {
			return $entry['language'];
		}

		return $this->publicationLanguage;
	}

	/**
	 * Get the publication date for an entry.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed>|SitemapEntry  $entry  The entry.
	 *
	 * @return string|null
	 */
	protected function getPublicationDate( SitemapEntry|array $entry ): ?string
	{
		if ( $entry instanceof SitemapEntry ) {
			return $entry->getLastModifiedForSitemap();
		}

		$date = $entry['publication_date'] ?? $entry['lastmod'] ?? $entry['last_modified'] ?? null;

		if ( null === $date ) {
			return null;
		}

		if ( $date instanceof DateTimeInterface ) {
			return $date->format( 'c' );
		}

		return (string) $date;
	}
}
