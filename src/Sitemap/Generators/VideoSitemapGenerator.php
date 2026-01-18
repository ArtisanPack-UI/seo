<?php
/**
 * VideoSitemapGenerator.
 *
 * Generates video sitemaps following Google's video sitemap protocol.
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

use ArtisanPackUI\SEO\Models\SitemapEntry;
use DateTimeInterface;
use Illuminate\Support\Collection;
use XMLWriter;

/**
 * VideoSitemapGenerator class.
 *
 * Generates video sitemaps with the Google video sitemap extension.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @see https://developers.google.com/search/docs/advanced/sitemaps/video-sitemaps
 * @since      1.0.0
 */
class VideoSitemapGenerator
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
	 * The XML namespace for video sitemaps.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected const VIDEO_NS = 'http://www.google.com/schemas/sitemap-video/1.1';

	/**
	 * Maximum URLs per sitemap file.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected int $maxUrls;

	/**
	 * Create a new VideoSitemapGenerator instance.
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
	 * Generate a video sitemap XML string.
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
	 * Get the total number of pages for the video sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getTotalPages(): int
	{
		$count = SitemapEntry::indexable()->withVideos()->count();

		return (int) ceil( $count / max( 1, $this->maxUrls ) );
	}

	/**
	 * Check if there are any videos to include in the sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function hasVideos(): bool
	{
		return SitemapEntry::indexable()->withVideos()->exists();
	}

	/**
	 * Get sitemap entries that have videos.
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
			->withVideos()
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
	 * @param  Collection<int, SitemapEntry>  $entries  The sitemap entries.
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
		$writer->writeAttribute( 'xmlns:video', self::VIDEO_NS );

		foreach ( $entries as $entry ) {
			$this->writeUrlElement( $writer, $entry );
		}

		$writer->endElement(); // urlset
		$writer->endDocument();

		return $writer->outputMemory();
	}

	/**
	 * Write a URL element with videos to the XML writer.
	 *
	 * @since 1.0.0
	 *
	 * @param  XMLWriter     $writer  The XML writer instance.
	 * @param  SitemapEntry  $entry   The sitemap entry.
	 *
	 * @return void
	 */
	protected function writeUrlElement( XMLWriter $writer, SitemapEntry $entry ): void
	{
		$videos = $entry->videos ?? [];

		if ( empty( $videos ) ) {
			return;
		}

		$writer->startElement( 'url' );

		// Location (required)
		$writer->writeElement( 'loc', $entry->url );

		// Write each video
		foreach ( $videos as $video ) {
			$this->writeVideoElement( $writer, $video );
		}

		$writer->endElement(); // url
	}

	/**
	 * Write a video element to the XML writer.
	 *
	 * @since 1.0.0
	 *
	 * @param  XMLWriter             $writer  The XML writer instance.
	 * @param  array<string, mixed>  $video   The video data.
	 *
	 * @return void
	 */
	protected function writeVideoElement( XMLWriter $writer, array $video ): void
	{
		// Required fields
		$thumbnailLoc = $video['thumbnail_loc'] ?? $video['thumbnail'] ?? null;
		$title        = $video['title'] ?? null;
		$description  = $video['description'] ?? null;

		if ( null === $thumbnailLoc || null === $title || null === $description ) {
			return;
		}

		$writer->startElement( 'video:video' );

		// Thumbnail URL (required)
		$writer->writeElement( 'video:thumbnail_loc', $thumbnailLoc );

		// Title (required)
		$writer->writeElement( 'video:title', $title );

		// Description (required)
		$writer->writeElement( 'video:description', $description );

		// Content location (recommended)
		$contentLoc = $video['content_loc'] ?? $video['url'] ?? null;
		if ( null !== $contentLoc ) {
			$writer->writeElement( 'video:content_loc', $contentLoc );
		}

		// Player location (alternative to content_loc)
		$playerLoc = $video['player_loc'] ?? null;
		if ( null !== $playerLoc ) {
			$writer->writeElement( 'video:player_loc', $playerLoc );
		}

		// Duration in seconds (recommended)
		$duration = $video['duration'] ?? null;
		if ( null !== $duration ) {
			$writer->writeElement( 'video:duration', (string) $duration );
		}

		// Expiration date
		$expirationDate = $video['expiration_date'] ?? null;
		if ( null !== $expirationDate ) {
			$writer->writeElement( 'video:expiration_date', $this->formatDate( $expirationDate ) );
		}

		// Rating (0.0 to 5.0)
		$rating = $video['rating'] ?? null;
		if ( null !== $rating ) {
			$writer->writeElement( 'video:rating', (string) $rating );
		}

		// View count
		$viewCount = $video['view_count'] ?? null;
		if ( null !== $viewCount ) {
			$writer->writeElement( 'video:view_count', (string) $viewCount );
		}

		// Publication date
		$publicationDate = $video['publication_date'] ?? null;
		if ( null !== $publicationDate ) {
			$writer->writeElement( 'video:publication_date', $this->formatDate( $publicationDate ) );
		}

		// Family friendly (yes/no)
		$familyFriendly = $video['family_friendly'] ?? null;
		if ( null !== $familyFriendly ) {
			$writer->writeElement( 'video:family_friendly', true === $familyFriendly ? 'yes' : 'no' );
		}

		// Tag(s)
		$tags = $video['tags'] ?? $video['tag'] ?? null;
		if ( null !== $tags ) {
			$tags = is_array( $tags ) ? $tags : [ $tags ];
			foreach ( $tags as $tag ) {
				$writer->writeElement( 'video:tag', $tag );
			}
		}

		// Category
		$category = $video['category'] ?? null;
		if ( null !== $category ) {
			$writer->writeElement( 'video:category', $category );
		}

		// Restriction
		$restriction = $video['restriction'] ?? null;
		if ( null !== $restriction && is_array( $restriction ) ) {
			$writer->startElement( 'video:restriction' );
			$writer->writeAttribute( 'relationship', $restriction['relationship'] ?? 'allow' );
			$writer->text( $restriction['countries'] ?? '' );
			$writer->endElement();
		}

		// Requires subscription (yes/no)
		$requiresSubscription = $video['requires_subscription'] ?? null;
		if ( null !== $requiresSubscription ) {
			$writer->writeElement(
				'video:requires_subscription',
				true === $requiresSubscription ? 'yes' : 'no',
			);
		}

		// Uploader
		$uploader = $video['uploader'] ?? null;
		if ( null !== $uploader ) {
			$writer->startElement( 'video:uploader' );
			if ( is_array( $uploader ) ) {
				if ( ! empty( $uploader['info'] ) ) {
					$writer->writeAttribute( 'info', $uploader['info'] );
				}
				$writer->text( $uploader['name'] ?? '' );
			} else {
				$writer->text( (string) $uploader );
			}
			$writer->endElement();
		}

		// Live (yes/no)
		$live = $video['live'] ?? null;
		if ( null !== $live ) {
			$writer->writeElement( 'video:live', true === $live ? 'yes' : 'no' );
		}

		$writer->endElement(); // video:video
	}

	/**
	 * Format a date for the sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed  $date  The date to format.
	 *
	 * @return string
	 */
	protected function formatDate( mixed $date ): string
	{
		if ( $date instanceof DateTimeInterface ) {
			return $date->format( 'c' );
		}

		return (string) $date;
	}
}
