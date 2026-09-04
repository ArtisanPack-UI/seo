<?php

/**
 * LlmsTxtGenerator.
 *
 * Generates an llms.txt AI-discovery manifest from the same indexable
 * SitemapEntry source used by the XML sitemap.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Sitemap\Generators;

use ArtisanPackUI\SEO\Models\SitemapEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;
use function applyFilters;

/**
 * LlmsTxtGenerator class.
 *
 * Emits an [llms.txt](https://llmstxt.org/) manifest listing the site's
 * indexable pages with titles, one-line descriptions, and URLs, grouped
 * by sitemap entry type. Titles and descriptions are pulled from the
 * associated model's SEO metadata when available and fall back to a
 * URL-derived name.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */
class LlmsTxtGenerator
{
	/**
	 * Maximum number of entries to emit; null means unlimited.
	 *
	 * @since 1.4.0
	 *
	 * @var int|null
	 */
	protected ?int $maxEntries;

	/**
	 * Create a new LlmsTxtGenerator instance.
	 *
	 * @since 1.4.0
	 *
	 * @param  int|null  $maxEntries  Maximum number of entries to emit.
	 */
	public function __construct( ?int $maxEntries = null )
	{
		$configured       = config( 'seo.llms_txt.max_entries' );
		$this->maxEntries = $maxEntries ?? ( null === $configured ? null : (int) $configured );
	}

	/**
	 * Generate the llms.txt content.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function generate(): string
	{
		$entries = $this->getEntries();
		$entries = $this->applyEntriesFilter( $entries );

		return $this->buildMarkdown( $entries );
	}

	/**
	 * Apply the `ap.seo.llmsTxtEntries` filter to a collection of entries.
	 *
	 * Converts the collection to an array so filter callbacks can freely
	 * add, remove, or reorder entries, then wraps the result back into a
	 * collection for rendering.
	 *
	 * Filter payload: `(array $entries)`.
	 *
	 * @since 1.4.0
	 *
	 * @param  Collection  $entries  The entries prior to filtering.
	 *
	 * @return Collection
	 */
	protected function applyEntriesFilter( Collection $entries ): Collection
	{
		$filtered = applyFilters( 'ap.seo.llmsTxtEntries', $entries->all() );

		return collect( is_array( $filtered ) ? $filtered : $entries->all() );
	}

	/**
	 * Load indexable sitemap entries, applying type include/exclude rules.
	 *
	 * @since 1.4.0
	 *
	 * @return Collection<int, SitemapEntry>
	 */
	protected function getEntries(): Collection
	{
		$query = SitemapEntry::indexable()
			->orderBy( 'type' )
			->orderByDesc( 'priority' )
			->orderByDesc( 'last_modified' );

		$include = (array) config( 'seo.llms_txt.include_types', [] );
		$exclude = (array) config( 'seo.llms_txt.exclude_types', [] );

		if ( [] !== $include ) {
			$query->whereIn( 'type', $include );
		}

		if ( [] !== $exclude ) {
			$query->whereNotIn( 'type', $exclude );
		}

		if ( null !== $this->maxEntries && $this->maxEntries > 0 ) {
			$query->limit( $this->maxEntries );
		}

		return $query->get();
	}

	/**
	 * Build the llms.txt Markdown from a collection of entries.
	 *
	 * @since 1.4.0
	 *
	 * @param  Collection  $entries  The entries to render.
	 *
	 * @return string
	 */
	protected function buildMarkdown( Collection $entries ): string
	{
		$lines = [];

		$lines[] = '# ' . $this->getTitle();
		$lines[] = '';

		$summary = $this->getSummary();
		if ( '' !== $summary ) {
			$lines[] = '> ' . $summary;
			$lines[] = '';
		}

		$intro = $this->getIntro();
		if ( '' !== $intro ) {
			$lines[] = $intro;
			$lines[] = '';
		}

		$grouped = $entries->groupBy( function ( $entry ): string {
			return $this->getEntryValue( $entry, 'type' ) ?? 'pages';
		} );

		foreach ( $grouped as $type => $groupEntries ) {
			$lines[] = '## ' . $this->formatSectionHeading( (string) $type );
			$lines[] = '';

			foreach ( $groupEntries as $entry ) {
				$lines[] = $this->formatEntryLine( $entry );
			}

			$lines[] = '';
		}

		// Trim trailing blank lines and terminate with a single newline.
		$content = rtrim( implode( "\n", $lines ) );

		return $content . "\n";
	}

	/**
	 * Format a single entry as a Markdown list item.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<string, mixed>|SitemapEntry  $entry  The entry to format.
	 *
	 * @return string
	 */
	protected function formatEntryLine( SitemapEntry|array $entry ): string
	{
		$url         = (string) $this->getEntryValue( $entry, 'url' );
		$title       = $this->resolveTitle( $entry, $url );
		$description = $this->resolveDescription( $entry );

		$line = sprintf( '- [%s](%s)', $this->escapeMarkdown( $title ), $url );

		if ( null !== $description && '' !== $description ) {
			$line .= ': ' . $this->normalizeDescription( $description );
		}

		return $line;
	}

	/**
	 * Resolve a human-readable title for the entry.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<string, mixed>|SitemapEntry  $entry  The entry.
	 * @param  string                             $url    The entry URL.
	 *
	 * @return string
	 */
	protected function resolveTitle( SitemapEntry|array $entry, string $url ): string
	{
		$override = $this->getEntryValue( $entry, 'title' );
		if ( is_string( $override ) && '' !== $override ) {
			return $override;
		}

		$model = $this->resolveSitemapableModel( $entry );

		if ( null !== $model ) {
			$title = $this->readModelAccessor( $model, [ 'meta_title', 'seo_title', 'title', 'name' ] );

			if ( null !== $title && '' !== $title ) {
				return $title;
			}
		}

		return $this->deriveTitleFromUrl( $url );
	}

	/**
	 * Resolve a description for the entry.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<string, mixed>|SitemapEntry  $entry  The entry.
	 *
	 * @return string|null
	 */
	protected function resolveDescription( SitemapEntry|array $entry ): ?string
	{
		$override = $this->getEntryValue( $entry, 'description' );
		if ( is_string( $override ) && '' !== $override ) {
			return $override;
		}

		$model = $this->resolveSitemapableModel( $entry );

		if ( null !== $model ) {
			return $this->readModelAccessor( $model, [ 'meta_description', 'seo_description', 'excerpt', 'description' ] );
		}

		return null;
	}

	/**
	 * Load the sitemapable model for an entry, if any.
	 *
	 * @since 1.4.0
	 *
	 * @param  array<string, mixed>|SitemapEntry  $entry  The entry.
	 *
	 * @return Model|null
	 */
	protected function resolveSitemapableModel( SitemapEntry|array $entry ): ?Model
	{
		if ( ! $entry instanceof SitemapEntry ) {
			return null;
		}

		try {
			return $entry->sitemapable;
		} catch ( Throwable $e ) {
			return null;
		}
	}

	/**
	 * Read the first non-empty value from a list of model accessors.
	 *
	 * @since 1.4.0
	 *
	 * @param  Model              $model      The model to inspect.
	 * @param  array<int, string> $accessors  Accessor names to try in order.
	 *
	 * @return string|null
	 */
	protected function readModelAccessor( Model $model, array $accessors ): ?string
	{
		foreach ( $accessors as $accessor ) {
			try {
				$value = $model->{$accessor} ?? null;
			} catch ( Throwable $e ) {
				$value = null;
			}

			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * Get a value from an entry (either model or array).
	 *
	 * @since 1.4.0
	 *
	 * @param  array<string, mixed>|SitemapEntry  $entry  The entry.
	 * @param  string                             $key    The key to retrieve.
	 *
	 * @return mixed
	 */
	protected function getEntryValue( SitemapEntry|array $entry, string $key ): mixed
	{
		if ( $entry instanceof SitemapEntry ) {
			return $entry->{$key} ?? null;
		}

		return $entry[ $key ] ?? null;
	}

	/**
	 * Derive a fallback title from an entry URL.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $url  The URL to derive from.
	 *
	 * @return string
	 */
	protected function deriveTitleFromUrl( string $url ): string
	{
		$path = trim( (string) parse_url( $url, PHP_URL_PATH ), '/' );

		if ( '' === $path ) {
			return __( 'Home' );
		}

		$slug = basename( $path );

		return Str::title( str_replace( [ '-', '_' ], ' ', $slug ) );
	}

	/**
	 * Format a type key as a section heading.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $type  The entry type key.
	 *
	 * @return string
	 */
	protected function formatSectionHeading( string $type ): string
	{
		if ( '' === $type ) {
			return __( 'Pages' );
		}

		return Str::title( str_replace( [ '-', '_' ], ' ', Str::plural( $type ) ) );
	}

	/**
	 * Get the site title for the manifest header.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	protected function getTitle(): string
	{
		$title = config( 'seo.llms_txt.title' );

		if ( is_string( $title ) && '' !== $title ) {
			return $title;
		}

		return (string) config( 'seo.site.name', config( 'app.name', 'Laravel' ) );
	}

	/**
	 * Get the site summary for the manifest header.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	protected function getSummary(): string
	{
		$summary = config( 'seo.llms_txt.summary' );

		if ( is_string( $summary ) && '' !== $summary ) {
			return $summary;
		}

		return (string) config( 'seo.site.description', '' );
	}

	/**
	 * Get the extended intro text for the manifest.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	protected function getIntro(): string
	{
		$intro = config( 'seo.llms_txt.intro' );

		return is_string( $intro ) ? $intro : '';
	}

	/**
	 * Escape square brackets that would break Markdown link syntax.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $value  The value to escape.
	 *
	 * @return string
	 */
	protected function escapeMarkdown( string $value ): string
	{
		return strtr( $value, [ '[' => '\\[', ']' => '\\]' ] );
	}

	/**
	 * Collapse whitespace so a description renders on a single line.
	 *
	 * @since 1.4.0
	 *
	 * @param  string  $value  The value to normalize.
	 *
	 * @return string
	 */
	protected function normalizeDescription( string $value ): string
	{
		return trim( (string) preg_replace( '/\s+/', ' ', $value ) );
	}
}
