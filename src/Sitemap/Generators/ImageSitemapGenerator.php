<?php

/**
 * ImageSitemapGenerator.
 *
 * Generates image sitemaps following Google's image sitemap protocol.
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

use ArtisanPackUI\SEO\Models\SitemapEntry;
use Illuminate\Support\Collection;
use XMLWriter;

/**
 * ImageSitemapGenerator class.
 *
 * Generates image sitemaps with the Google image sitemap extension.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @see https://developers.google.com/search/docs/advanced/sitemaps/image-sitemaps
 * @since      1.0.0
 */
class ImageSitemapGenerator
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
	 * The XML namespace for image sitemaps.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected const IMAGE_NS = 'http://www.google.com/schemas/sitemap-image/1.1';

	/**
	 * Maximum URLs per sitemap file.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected int $maxUrls;

	/**
	 * Create a new ImageSitemapGenerator instance.
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
	 * Generate an image sitemap XML string.
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
	 * Get the total number of pages for the image sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getTotalPages(): int
	{
		$count = SitemapEntry::indexable()->withImages()->count();

		return (int) ceil( $count / max( 1, $this->maxUrls ) );
	}

	/**
	 * Check if there are any images to include in the sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function hasImages(): bool
	{
		return SitemapEntry::indexable()->withImages()->exists();
	}

	/**
	 * Get sitemap entries that have images.
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
			->withImages()
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
		$writer->writeAttribute( 'xmlns:image', self::IMAGE_NS );

		foreach ( $entries as $entry ) {
			$this->writeUrlElement( $writer, $entry );
		}

		$writer->endElement(); // urlset
		$writer->endDocument();

		return $writer->outputMemory();
	}

	/**
	 * Write a URL element with images to the XML writer.
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
		$images = $entry->images ?? [];

		if ( empty( $images ) ) {
			return;
		}

		$writer->startElement( 'url' );

		// Location (required)
		$writer->writeElement( 'loc', $entry->url );

		// Write each image
		foreach ( $images as $image ) {
			$this->writeImageElement( $writer, $image );
		}

		$writer->endElement(); // url
	}

	/**
	 * Write an image element to the XML writer.
	 *
	 * @since 1.0.0
	 *
	 * @param  XMLWriter             $writer  The XML writer instance.
	 * @param  array<string, mixed>  $image   The image data.
	 *
	 * @return void
	 */
	protected function writeImageElement( XMLWriter $writer, array $image ): void
	{
		$loc = $image['loc'] ?? $image['url'] ?? null;

		if ( null === $loc || '' === $loc ) {
			return;
		}

		$writer->startElement( 'image:image' );

		// Image location (required)
		$writer->writeElement( 'image:loc', $loc );

		// Image caption (optional)
		if ( ! empty( $image['caption'] ) ) {
			$writer->writeElement( 'image:caption', $image['caption'] );
		}

		// Image geo location (optional) - check both snake_case and camelCase keys
		$geoLocation = $image['geo_location'] ?? $image['geoLocation'] ?? null;
		if ( ! empty( $geoLocation ) ) {
			$writer->writeElement( 'image:geo_location', $geoLocation );
		}

		// Image title (optional)
		if ( ! empty( $image['title'] ) ) {
			$writer->writeElement( 'image:title', $image['title'] );
		}

		// Image license (optional)
		if ( ! empty( $image['license'] ) ) {
			$writer->writeElement( 'image:license', $image['license'] );
		}

		$writer->endElement(); // image:image
	}
}
