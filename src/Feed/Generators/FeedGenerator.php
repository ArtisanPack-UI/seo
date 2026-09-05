<?php

/**
 * FeedGenerator.
 *
 * Generates valid RSS 2.0 and Atom 1.0 XML feeds from consumer-supplied entries.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Feed\Generators;

use ArtisanPackUI\SEO\Contracts\FeedProviderContract;
use ArtisanPackUI\SEO\DTOs\FeedEntryDTO;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use XMLWriter;
use function applyFilters;

/**
 * FeedGenerator class.
 *
 * Builds RSS 2.0 and Atom 1.0 documents from a supplied set of entries.
 * The consumer supplies the entry source (blog archive, per-type
 * collection, provider, or plain array); this class handles formatting,
 * date encoding, and character escaping.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
class FeedGenerator
{
	/**
	 * Atom namespace URI.
	 *
	 * @since 1.4.0
	 *
	 * @var string
	 */
	protected const ATOM_NS = 'http://www.w3.org/2005/Atom';

	/**
	 * Dublin Core namespace URI (used inside RSS 2.0 for name-only creators).
	 *
	 * @since 1.4.0
	 *
	 * @var string
	 */
	protected const RSS_DC_NS = 'http://purl.org/dc/elements/1.1/';

	/**
	 * Generate an RSS 2.0 feed.
	 *
	 * @since 1.4.0
	 *
	 * @param  string                                                                $title        Feed title.
	 * @param  string                                                                $link         Canonical HTML page URL for the feed.
	 * @param  string                                                                $description  Feed description.
	 * @param  iterable<int, array<string, mixed>|FeedEntryDTO>                      $entries      Entries to include.
	 * @param  array{feed_url?: string, language?: string, updated_at?: DateTimeInterface|string|null}  $options  Optional feed-level overrides.
	 *
	 * @return string The generated RSS 2.0 XML.
	 */
	public function generateRss(
		string $title,
		string $link,
		string $description,
		iterable $entries,
		array $options = [],
	): string {
		$normalized = $this->normalizeEntries( $entries );
		$normalized = $this->applyEntriesFilter( $normalized, 'rss' );

		$writer = new XMLWriter();
		$writer->openMemory();
		$writer->setIndent( true );
		$writer->setIndentString( "\t" );
		$writer->startDocument( '1.0', 'UTF-8' );

		$writer->startElement( 'rss' );
		$writer->writeAttribute( 'version', '2.0' );
		$writer->writeAttribute( 'xmlns:atom', self::ATOM_NS );
		$writer->writeAttribute( 'xmlns:dc', self::RSS_DC_NS );

		$writer->startElement( 'channel' );
		$writer->writeElement( 'title', $this->sanitizeXmlText( $title ) );
		$writer->writeElement( 'link', $this->isValidLinkScheme( $link ) ? $link : '' );
		$writer->writeElement( 'description', $this->sanitizeXmlText( $description ) );
		// RSS 2.0 <language> follows RFC 1766 (e.g. en-us). Laravel's app.locale
		// often uses the underscore form (en_US, pt_BR); normalize it so feed
		// validators accept the output.
		$language = (string) ( $options['language'] ?? config( 'app.locale', 'en' ) );
		$writer->writeElement( 'language', strtolower( str_replace( '_', '-', $language ) ) );

		$lastBuild = $this->resolveFeedTimestamp( $options['updated_at'] ?? null, $normalized );
		$writer->writeElement( 'lastBuildDate', $lastBuild->format( DateTimeInterface::RSS ) );

		if ( ! empty( $options['feed_url'] ) && $this->isValidLinkScheme( (string) $options['feed_url'] ) ) {
			$writer->startElement( 'atom:link' );
			$writer->writeAttribute( 'href', (string) $options['feed_url'] );
			$writer->writeAttribute( 'rel', 'self' );
			$writer->writeAttribute( 'type', 'application/rss+xml' );
			$writer->endElement();
		}

		foreach ( $normalized as $entry ) {
			$this->writeRssItem( $writer, $entry );
		}

		$writer->endElement(); // channel
		$writer->endElement(); // rss
		$writer->endDocument();

		return $writer->outputMemory();
	}

	/**
	 * Generate an Atom 1.0 feed.
	 *
	 * @since 1.4.0
	 *
	 * @param  string                                                                $title        Feed title.
	 * @param  string                                                                $link         Canonical HTML page URL for the feed.
	 * @param  string                                                                $description  Feed subtitle/description.
	 * @param  iterable<int, array<string, mixed>|FeedEntryDTO>                      $entries      Entries to include.
	 * @param  array{feed_url?: string, feed_id?: string, author?: string, author_email?: string, updated_at?: DateTimeInterface|string|null}  $options  Optional feed-level overrides.
	 *
	 * @return string The generated Atom 1.0 XML.
	 */
	public function generateAtom(
		string $title,
		string $link,
		string $description,
		iterable $entries,
		array $options = [],
	): string {
		$normalized = $this->normalizeEntries( $entries );
		$normalized = $this->applyEntriesFilter( $normalized, 'atom' );

		$feedUrl = (string) ( $options['feed_url'] ?? $link );

		$configuredFeedId = $options['feed_id'] ?? config( 'seo.feeds.feed_id' );
		if ( null === $configuredFeedId || '' === (string) $configuredFeedId ) {
			$feedId = $feedUrl;
			Log::notice( 'Atom feed-level <id> defaulted to feed URL; set seo.feeds.feed_id to a stable tag: IRI.' );
		} else {
			$feedId = (string) $configuredFeedId;
		}

		$writer = new XMLWriter();
		$writer->openMemory();
		$writer->setIndent( true );
		$writer->setIndentString( "\t" );
		$writer->startDocument( '1.0', 'UTF-8' );

		$writer->startElement( 'feed' );
		$writer->writeAttribute( 'xmlns', self::ATOM_NS );

		$writer->writeElement( 'title', $this->sanitizeXmlText( $title ) );
		$writer->writeElement( 'subtitle', $this->sanitizeXmlText( $description ) );
		$writer->writeElement( 'id', $feedId );

		if ( $this->isValidLinkScheme( $link ) ) {
			$writer->startElement( 'link' );
			$writer->writeAttribute( 'rel', 'alternate' );
			$writer->writeAttribute( 'type', 'text/html' );
			$writer->writeAttribute( 'href', $link );
			$writer->endElement();
		}

		if ( $this->isValidLinkScheme( $feedUrl ) ) {
			$writer->startElement( 'link' );
			$writer->writeAttribute( 'rel', 'self' );
			$writer->writeAttribute( 'type', 'application/atom+xml' );
			$writer->writeAttribute( 'href', $feedUrl );
			$writer->endElement();
		}

		$updated = $this->resolveFeedTimestamp( $options['updated_at'] ?? null, $normalized );
		$writer->writeElement( 'updated', $updated->format( DateTimeInterface::ATOM ) );

		// Atom 1.0 (RFC 4287 §4.2.1) requires a feed-level <author> unless every
		// entry provides its own. Always emit one for spec safety, defaulting to
		// the application name so anonymous entries never invalidate the feed.
		$feedAuthorName  = (string) ( $options['author'] ?? config( 'app.name', 'Site' ) );
		$feedAuthorEmail = isset( $options['author_email'] ) ? (string) $options['author_email'] : null;
		$writer->startElement( 'author' );
		$writer->writeElement( 'name', $this->sanitizeXmlText( $feedAuthorName ) );
		if ( null !== $feedAuthorEmail ) {
			$writer->writeElement( 'email', $this->sanitizeXmlText( $feedAuthorEmail ) );
		}
		$writer->endElement();

		foreach ( $normalized as $entry ) {
			$this->writeAtomEntry( $writer, $entry );
		}

		$writer->endElement(); // feed
		$writer->endDocument();

		return $writer->outputMemory();
	}

	/**
	 * Generate an RSS 2.0 feed from a FeedProviderContract implementation.
	 *
	 * @since 1.4.0
	 *
	 * @param  FeedProviderContract  $provider  The feed provider.
	 *
	 * @return string
	 */
	public function generateRssFromProvider( FeedProviderContract $provider ): string
	{
		return $this->generateRss(
			$provider->getTitle(),
			$provider->getLink(),
			$provider->getDescription(),
			$provider->getEntries(),
			[ 'feed_url' => $provider->getFeedUrl() ],
		);
	}

	/**
	 * Generate an Atom 1.0 feed from a FeedProviderContract implementation.
	 *
	 * @since 1.4.0
	 *
	 * @param  FeedProviderContract  $provider  The feed provider.
	 *
	 * @return string
	 */
	public function generateAtomFromProvider( FeedProviderContract $provider ): string
	{
		return $this->generateAtom(
			$provider->getTitle(),
			$provider->getLink(),
			$provider->getDescription(),
			$provider->getEntries(),
			[ 'feed_url' => $provider->getFeedUrl() ],
		);
	}

	/**
	 * Normalize a mixed iterable of entries into a Collection of FeedEntryDTOs.
	 *
	 * @since 1.4.0
	 *
	 * @param  iterable<int, array<string, mixed>|FeedEntryDTO>  $entries  Raw entries.
	 *
	 * @return Collection<int, FeedEntryDTO>
	 */
	protected function normalizeEntries( iterable $entries ): Collection
	{
		$normalized = [];

		foreach ( $entries as $entry ) {
			$dto = null;
			if ( $entry instanceof FeedEntryDTO ) {
				$dto = $entry;
			} elseif ( is_array( $entry ) ) {
				$dto = FeedEntryDTO::fromArray( $entry );
			}

			if ( null === $dto ) {
				continue;
			}

			if ( ! $this->isValidLinkScheme( $dto->link ) ) {
				Log::warning( 'Dropped feed entry with disallowed link scheme.', [
					'link'  => $dto->link,
					'title' => $dto->title,
				] );
				continue;
			}

			$normalized[] = $dto;
		}

		return collect( $normalized );
	}

	/**
	 * Return true when a URL uses an http/https scheme.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $url  The URL to validate.
	 *
	 * @return bool
	 */
	protected function isValidLinkScheme( string $url ): bool
	{
		if ( '' === $url ) {
			return false;
		}

		$scheme = parse_url( $url, PHP_URL_SCHEME );
		if ( ! is_string( $scheme ) ) {
			return false;
		}

		return in_array( strtolower( $scheme ), [ 'http', 'https' ], true );
	}

	/**
	 * Strip XML 1.0 forbidden control characters from consumer-supplied text.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $value  Raw text.
	 *
	 * @return string
	 */
	protected function sanitizeXmlText( string $value ): string
	{
		return preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value ) ?? '';
	}

	/**
	 * Apply the `ap.seo.feedEntries` filter to a collection of entries.
	 *
	 * @since 1.4.0
	 *
	 * @param  Collection<int, FeedEntryDTO>  $entries   The entries prior to filtering.
	 * @param  string                          $feedType  The feed type identifier (rss|atom).
	 *
	 * @return Collection<int, FeedEntryDTO>
	 */
	protected function applyEntriesFilter( Collection $entries, string $feedType ): Collection
	{
		$filtered = applyFilters( 'ap.seo.feedEntries', $entries->all(), $feedType );

		if ( ! is_array( $filtered ) ) {
			return $entries;
		}

		return collect( array_values( array_filter( $filtered, static fn ( $item ): bool => $item instanceof FeedEntryDTO ) ) );
	}

	/**
	 * Write a single <item> element for RSS 2.0.
	 *
	 * @since 1.4.0
	 *
	 * @param  XMLWriter     $writer  The XML writer instance.
	 * @param  FeedEntryDTO  $entry   The entry to render.
	 *
	 * @return void
	 */
	protected function writeRssItem( XMLWriter $writer, FeedEntryDTO $entry ): void
	{
		$writer->startElement( 'item' );

		$writer->writeElement( 'title', $this->sanitizeXmlText( $entry->title ) );
		$writer->writeElement( 'link', $entry->link );

		$writer->startElement( 'description' );
		$writer->writeCdata( $this->escapeCdata( $this->sanitizeSummary( $entry->summary ) ) );
		$writer->endElement();

		if ( null !== $entry->publishedAt ) {
			$writer->writeElement( 'pubDate', $entry->publishedAt->format( DateTimeInterface::RSS ) );
		}

		if ( null !== $entry->authorEmail ) {
			$authorValue = null !== $entry->author
				? sprintf( '%s (%s)', $entry->authorEmail, $entry->author )
				: $entry->authorEmail;
			$writer->writeElement( 'author', $this->sanitizeXmlText( $authorValue ) );
		} elseif ( null !== $entry->author ) {
			// RSS 2.0 requires <author> to be an email address; use Dublin Core
			// for name-only authorship so validators accept the feed.
			$writer->writeElement( 'dc:creator', $this->sanitizeXmlText( $entry->author ) );
		}

		foreach ( $entry->categories as $category ) {
			$writer->writeElement( 'category', $this->sanitizeXmlText( $category ) );
		}

		$guid = $entry->guid ?? $entry->link;
		$writer->startElement( 'guid' );
		$writer->writeAttribute( 'isPermaLink', ( $guid === $entry->link ) ? 'true' : 'false' );
		$writer->text( $guid );
		$writer->endElement();

		$writer->endElement(); // item
	}

	/**
	 * Write a single <entry> element for Atom 1.0.
	 *
	 * @since 1.4.0
	 *
	 * @param  XMLWriter     $writer  The XML writer instance.
	 * @param  FeedEntryDTO  $entry   The entry to render.
	 *
	 * @return void
	 */
	protected function writeAtomEntry( XMLWriter $writer, FeedEntryDTO $entry ): void
	{
		$writer->startElement( 'entry' );

		$writer->writeElement( 'title', $this->sanitizeXmlText( $entry->title ) );

		$writer->startElement( 'link' );
		$writer->writeAttribute( 'rel', 'alternate' );
		$writer->writeAttribute( 'type', 'text/html' );
		$writer->writeAttribute( 'href', $entry->link );
		$writer->endElement();

		$writer->writeElement( 'id', $entry->guid ?? $entry->link );

		$updated = $entry->updatedAt ?? $entry->publishedAt ?? new DateTimeImmutable();
		$writer->writeElement( 'updated', $updated->format( DateTimeInterface::ATOM ) );

		if ( null !== $entry->publishedAt ) {
			$writer->writeElement( 'published', $entry->publishedAt->format( DateTimeInterface::ATOM ) );
		}

		if ( null !== $entry->author ) {
			$writer->startElement( 'author' );
			$writer->writeElement( 'name', $this->sanitizeXmlText( $entry->author ) );
			if ( null !== $entry->authorEmail ) {
				$writer->writeElement( 'email', $this->sanitizeXmlText( $entry->authorEmail ) );
			}
			$writer->endElement();
		}

		foreach ( $entry->categories as $category ) {
			$writer->startElement( 'category' );
			$writer->writeAttribute( 'term', $this->sanitizeXmlText( $category ) );
			$writer->endElement();
		}

		$writer->startElement( 'summary' );
		$writer->writeAttribute( 'type', 'text' );
		$writer->text( $this->sanitizeSummary( $entry->summary ) );
		$writer->endElement();

		$writer->endElement(); // entry
	}

	/**
	 * Neutralize `]]>` sequences inside CDATA payloads.
	 *
	 * XMLWriter::writeCdata() does not sanitize the terminator; a hostile
	 * summary containing `]]>` would close the CDATA early and let the
	 * remainder be parsed as raw XML — a stored-XSS vector for feed
	 * readers that render Atom `type="html"` summaries. Splitting the
	 * terminator across adjacent CDATA sections preserves the literal
	 * characters while making the section unbreakable.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $value  The raw payload.
	 *
	 * @return string
	 */
	protected function escapeCdata( string $value ): string
	{
		return str_replace( ']]>', ']]]]><![CDATA[>', $this->sanitizeXmlText( $value ) );
	}

	/**
	 * Strip HTML from an entry summary to prevent stored XSS in feed readers.
	 *
	 * escapeCdata() protects the XML syntax layer but does not remove active
	 * HTML such as `<script>` tags, event handlers, or `javascript:` URLs.
	 * Rendering an untrusted summary as HTML in a subscriber's reader is a
	 * stored-XSS vector; treating it as plain text is safer and matches the
	 * expectations of most modern feed readers. Consumers who need rich
	 * HTML in summaries should sanitize upstream (e.g. via kses) before
	 * populating the FeedEntryDTO.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $summary  The raw summary payload.
	 *
	 * @return string
	 */
	protected function sanitizeSummary( string $summary ): string
	{
		$stripped = strip_tags( $summary );

		return html_entity_decode( $stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Resolve the feed-level timestamp (lastBuildDate / <updated>).
	 *
	 * Prefers an explicit override, then the newest entry timestamp, then now.
	 *
	 * @since 1.4.0
	 *
	 * @param  DateTimeInterface|string|null   $override  Consumer-supplied override.
	 * @param  Collection<int, FeedEntryDTO>   $entries   The normalized entries.
	 *
	 * @return DateTimeInterface
	 */
	protected function resolveFeedTimestamp( DateTimeInterface|string|null $override, Collection $entries ): DateTimeInterface
	{
		if ( $override instanceof DateTimeInterface ) {
			return $override;
		}

		if ( is_string( $override ) && '' !== $override ) {
			try {
				return new DateTimeImmutable( $override );
			} catch ( Exception $e ) {
				// Fall through to entry-derived value.
			}
		}

		$timestamps = $entries
			->map( static fn ( FeedEntryDTO $entry ): ?DateTimeInterface => $entry->updatedAt ?? $entry->publishedAt )
			->filter()
			->map( static fn ( DateTimeInterface $date ): int => $date->getTimestamp() );

		if ( $timestamps->isNotEmpty() ) {
			return ( new DateTimeImmutable() )->setTimestamp( $timestamps->max() );
		}

		return new DateTimeImmutable();
	}
}
