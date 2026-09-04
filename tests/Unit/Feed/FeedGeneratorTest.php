<?php

/**
 * FeedGenerator Tests.
 *
 * Unit tests for the RSS 2.0 and Atom 1.0 feed generator.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.4.0
 */

declare( strict_types=1 );

use ArtisanPackUI\SEO\Contracts\FeedProviderContract;
use ArtisanPackUI\SEO\DTOs\FeedEntryDTO;
use ArtisanPackUI\SEO\Feed\Generators\FeedGenerator;
use Illuminate\Support\Collection;

function makeFeedEntry( array $overrides = [] ): FeedEntryDTO
{
	return new FeedEntryDTO(
		title: $overrides['title'] ?? 'Hello & Welcome',
		link: $overrides['link'] ?? 'https://example.com/posts/hello',
		summary: $overrides['summary'] ?? '<p>First post.</p>',
		publishedAt: $overrides['publishedAt'] ?? new DateTimeImmutable( '2026-01-02T10:00:00+00:00' ),
		updatedAt: $overrides['updatedAt'] ?? null,
		author: $overrides['author'] ?? 'Jane Doe',
		authorEmail: $overrides['authorEmail'] ?? 'jane@example.com',
		guid: $overrides['guid'] ?? null,
		categories: $overrides['categories'] ?? [ 'news', 'updates' ],
	);
}

describe( 'FeedGenerator RSS 2.0', function (): void {

	it( 'generates a valid RSS 2.0 document with no entries', function (): void {
		$generator = new FeedGenerator();
		$xml       = $generator->generateRss( 'My Blog', 'https://example.com', 'Latest posts', [] );

		expect( $xml )->toContain( '<?xml version="1.0" encoding="UTF-8"?>' );

		$doc = new DOMDocument();
		expect( $doc->loadXML( $xml ) )->toBeTrue();

		$root = $doc->documentElement;
		expect( $root->tagName )->toBe( 'rss' )
			->and( $root->getAttribute( 'version' ) )->toBe( '2.0' );

		$channels = $doc->getElementsByTagName( 'channel' );
		expect( $channels->length )->toBe( 1 );

		$channel = $channels->item( 0 );
		expect( $channel->getElementsByTagName( 'title' )->item( 0 )->nodeValue )->toBe( 'My Blog' )
			->and( $channel->getElementsByTagName( 'link' )->item( 0 )->nodeValue )->toBe( 'https://example.com' )
			->and( $channel->getElementsByTagName( 'description' )->item( 0 )->nodeValue )->toBe( 'Latest posts' )
			->and( $channel->getElementsByTagName( 'item' )->length )->toBe( 0 );
	} );

	it( 'renders items with title, link, summary, pubDate, author, categories and guid', function (): void {
		$generator = new FeedGenerator();
		$xml       = $generator->generateRss(
			'My Blog',
			'https://example.com',
			'Latest posts',
			[ makeFeedEntry() ],
			[ 'feed_url' => 'https://example.com/feed.xml' ],
		);

		$doc = new DOMDocument();
		expect( $doc->loadXML( $xml ) )->toBeTrue();

		$item = $doc->getElementsByTagName( 'item' )->item( 0 );
		expect( $item )->not->toBeNull();

		expect( $item->getElementsByTagName( 'title' )->item( 0 )->nodeValue )->toBe( 'Hello & Welcome' )
			->and( $item->getElementsByTagName( 'link' )->item( 0 )->nodeValue )->toBe( 'https://example.com/posts/hello' )
			->and( $item->getElementsByTagName( 'description' )->item( 0 )->nodeValue )->toBe( '<p>First post.</p>' )
			->and( $item->getElementsByTagName( 'pubDate' )->item( 0 )->nodeValue )->toBe( 'Fri, 02 Jan 2026 10:00:00 +0000' )
			->and( $item->getElementsByTagName( 'author' )->item( 0 )->nodeValue )->toBe( 'jane@example.com (Jane Doe)' );

		$categories = $item->getElementsByTagName( 'category' );
		expect( $categories->length )->toBe( 2 )
			->and( $categories->item( 0 )->nodeValue )->toBe( 'news' )
			->and( $categories->item( 1 )->nodeValue )->toBe( 'updates' );

		$guid = $item->getElementsByTagName( 'guid' )->item( 0 );
		expect( $guid->nodeValue )->toBe( 'https://example.com/posts/hello' )
			->and( $guid->getAttribute( 'isPermaLink' ) )->toBe( 'true' );

		// atom:link self reference
		$atomLinks = $doc->getElementsByTagNameNS( 'http://www.w3.org/2005/Atom', 'link' );
		expect( $atomLinks->length )->toBe( 1 )
			->and( $atomLinks->item( 0 )->getAttribute( 'href' ) )->toBe( 'https://example.com/feed.xml' )
			->and( $atomLinks->item( 0 )->getAttribute( 'rel' ) )->toBe( 'self' );
	} );

	it( 'escapes special characters and encodes UTF-8 correctly', function (): void {
		$generator = new FeedGenerator();
		$entry     = new FeedEntryDTO(
			title: 'Café & <script>alert(1)</script>',
			link: 'https://example.com/café?x=1&y=2',
			summary: 'Résumé with ünicode — plus <b>markup</b>.',
		);

		$xml = $generator->generateRss( 'Ünicode Blog', 'https://example.com', 'Descripción', [ $entry ] );

		$doc = new DOMDocument();
		expect( $doc->loadXML( $xml ) )->toBeTrue();

		$item = $doc->getElementsByTagName( 'item' )->item( 0 );
		expect( $item->getElementsByTagName( 'title' )->item( 0 )->nodeValue )
			->toBe( 'Café & <script>alert(1)</script>' );
		expect( $item->getElementsByTagName( 'link' )->item( 0 )->nodeValue )
			->toBe( 'https://example.com/café?x=1&y=2' );

		// Raw XML must have escaped the entities.
		expect( $xml )->toContain( '&amp;' )
			->and( $xml )->not->toContain( '<script>alert(1)</script>' );
	} );

	it( 'uses dc:creator when only an author name (no email) is supplied', function (): void {
		$generator = new FeedGenerator();
		$entry     = new FeedEntryDTO(
			title: 'Name Only',
			link: 'https://example.com/name-only',
			summary: 'Body.',
			publishedAt: new DateTimeImmutable( '2026-01-02T10:00:00+00:00' ),
			author: 'Jane Doe',
		);

		$xml = $generator->generateRss( 'Blog', 'https://example.com', 'Desc', [ $entry ] );

		$doc = new DOMDocument();
		expect( $doc->loadXML( $xml ) )->toBeTrue();

		$item = $doc->getElementsByTagName( 'item' )->item( 0 );
		expect( $item->getElementsByTagName( 'author' )->length )->toBe( 0 );

		$creators = $item->getElementsByTagNameNS( 'http://purl.org/dc/elements/1.1/', 'creator' );
		expect( $creators->length )->toBe( 1 )
			->and( $creators->item( 0 )->nodeValue )->toBe( 'Jane Doe' );
	} );

	it( 'marks non-permalink guids correctly', function (): void {
		$generator = new FeedGenerator();
		$entry     = makeFeedEntry( [ 'guid' => 'urn:uuid:1234' ] );

		$xml = $generator->generateRss( 'Blog', 'https://example.com', 'Desc', [ $entry ] );

		$doc = new DOMDocument();
		$doc->loadXML( $xml );
		$guid = $doc->getElementsByTagName( 'guid' )->item( 0 );

		expect( $guid->nodeValue )->toBe( 'urn:uuid:1234' )
			->and( $guid->getAttribute( 'isPermaLink' ) )->toBe( 'false' );
	} );

	it( 'omits pubDate when publishedAt is null', function (): void {
		$generator = new FeedGenerator();
		$entry     = new FeedEntryDTO(
			title: 'No Date',
			link: 'https://example.com/no-date',
			summary: 'Nothing.',
		);

		$xml = $generator->generateRss( 'Blog', 'https://example.com', 'Desc', [ $entry ] );

		$doc = new DOMDocument();
		$doc->loadXML( $xml );
		$item = $doc->getElementsByTagName( 'item' )->item( 0 );

		expect( $item->getElementsByTagName( 'pubDate' )->length )->toBe( 0 );
	} );

	it( 'derives lastBuildDate from the newest entry', function (): void {
		$generator = new FeedGenerator();
		$xml       = $generator->generateRss(
			'Blog',
			'https://example.com',
			'Desc',
			[
				makeFeedEntry( [ 'publishedAt' => new DateTimeImmutable( '2026-01-01T00:00:00+00:00' ) ] ),
				makeFeedEntry( [ 'publishedAt' => new DateTimeImmutable( '2026-06-15T12:00:00+00:00' ) ] ),
			],
		);

		$doc = new DOMDocument();
		$doc->loadXML( $xml );
		$channel = $doc->getElementsByTagName( 'channel' )->item( 0 );

		// Compare the parsed instant rather than the formatted string so the
		// test is independent of the harness's configured app.timezone.
		$lastBuild = new DateTimeImmutable( $channel->getElementsByTagName( 'lastBuildDate' )->item( 0 )->nodeValue );
		expect( $lastBuild->getTimestamp() )
			->toBe( ( new DateTimeImmutable( '2026-06-15T12:00:00+00:00' ) )->getTimestamp() );
	} );

	it( 'accepts array entries and normalizes flexible keys', function (): void {
		$generator = new FeedGenerator();
		$xml       = $generator->generateRss(
			'Blog',
			'https://example.com',
			'Desc',
			[
				[
					'title'       => 'From Array',
					'url'         => 'https://example.com/from-array',
					'description' => 'Body.',
					'pubDate'     => '2026-03-01T09:00:00+00:00',
				],
			],
		);

		$doc = new DOMDocument();
		expect( $doc->loadXML( $xml ) )->toBeTrue();

		$item = $doc->getElementsByTagName( 'item' )->item( 0 );
		expect( $item->getElementsByTagName( 'title' )->item( 0 )->nodeValue )->toBe( 'From Array' )
			->and( $item->getElementsByTagName( 'link' )->item( 0 )->nodeValue )->toBe( 'https://example.com/from-array' )
			->and( $item->getElementsByTagName( 'pubDate' )->item( 0 )->nodeValue )->toBe( 'Sun, 01 Mar 2026 09:00:00 +0000' );
	} );

} );

describe( 'FeedGenerator Atom 1.0', function (): void {

	it( 'generates a valid Atom 1.0 document with feed metadata', function (): void {
		$generator = new FeedGenerator();
		$xml       = $generator->generateAtom(
			'My Blog',
			'https://example.com',
			'Latest posts',
			[ makeFeedEntry() ],
			[ 'feed_url' => 'https://example.com/atom.xml', 'feed_id' => 'urn:example:blog' ],
		);

		$doc = new DOMDocument();
		expect( $doc->loadXML( $xml ) )->toBeTrue();

		$root = $doc->documentElement;
		expect( $root->tagName )->toBe( 'feed' )
			->and( $root->namespaceURI )->toBe( 'http://www.w3.org/2005/Atom' );

		expect( $root->getElementsByTagName( 'title' )->item( 0 )->nodeValue )->toBe( 'My Blog' )
			->and( $root->getElementsByTagName( 'subtitle' )->item( 0 )->nodeValue )->toBe( 'Latest posts' )
			->and( $root->getElementsByTagName( 'id' )->item( 0 )->nodeValue )->toBe( 'urn:example:blog' );

		// Two links: alternate (html) + self (atom)
		$links = [];
		foreach ( $root->childNodes as $child ) {
			if ( $child instanceof DOMElement && 'link' === $child->tagName ) {
				$links[ $child->getAttribute( 'rel' ) ] = $child->getAttribute( 'href' );
			}
		}
		expect( $links )->toHaveKey( 'alternate' )
			->and( $links )->toHaveKey( 'self' )
			->and( $links['alternate'] )->toBe( 'https://example.com' )
			->and( $links['self'] )->toBe( 'https://example.com/atom.xml' );
	} );

	it( 'renders entries with all core Atom elements and RFC 3339 dates', function (): void {
		$generator = new FeedGenerator();
		$entry     = makeFeedEntry( [
			'updatedAt' => new DateTimeImmutable( '2026-01-03T11:30:00+00:00' ),
		] );

		$xml = $generator->generateAtom( 'My Blog', 'https://example.com', 'Desc', [ $entry ] );

		$doc = new DOMDocument();
		expect( $doc->loadXML( $xml ) )->toBeTrue();

		$atomNs  = 'http://www.w3.org/2005/Atom';
		$entryEl = $doc->getElementsByTagNameNS( $atomNs, 'entry' )->item( 0 );
		expect( $entryEl )->not->toBeNull();

		expect( $entryEl->getElementsByTagNameNS( $atomNs, 'title' )->item( 0 )->nodeValue )->toBe( 'Hello & Welcome' )
			->and( $entryEl->getElementsByTagNameNS( $atomNs, 'id' )->item( 0 )->nodeValue )->toBe( 'https://example.com/posts/hello' )
			->and( $entryEl->getElementsByTagNameNS( $atomNs, 'updated' )->item( 0 )->nodeValue )->toBe( '2026-01-03T11:30:00+00:00' )
			->and( $entryEl->getElementsByTagNameNS( $atomNs, 'published' )->item( 0 )->nodeValue )->toBe( '2026-01-02T10:00:00+00:00' );

		$linkEl = $entryEl->getElementsByTagNameNS( $atomNs, 'link' )->item( 0 );
		expect( $linkEl->getAttribute( 'href' ) )->toBe( 'https://example.com/posts/hello' )
			->and( $linkEl->getAttribute( 'rel' ) )->toBe( 'alternate' );

		$author = $entryEl->getElementsByTagNameNS( $atomNs, 'author' )->item( 0 );
		expect( $author->getElementsByTagNameNS( $atomNs, 'name' )->item( 0 )->nodeValue )->toBe( 'Jane Doe' )
			->and( $author->getElementsByTagNameNS( $atomNs, 'email' )->item( 0 )->nodeValue )->toBe( 'jane@example.com' );

		$categories = $entryEl->getElementsByTagNameNS( $atomNs, 'category' );
		expect( $categories->length )->toBe( 2 )
			->and( $categories->item( 0 )->getAttribute( 'term' ) )->toBe( 'news' )
			->and( $categories->item( 1 )->getAttribute( 'term' ) )->toBe( 'updates' );
	} );

	it( 'falls back to publishedAt when updatedAt is missing on an entry', function (): void {
		$generator = new FeedGenerator();
		$entry     = makeFeedEntry( [ 'updatedAt' => null ] );

		$xml = $generator->generateAtom( 'Blog', 'https://example.com', 'Desc', [ $entry ] );

		$doc = new DOMDocument();
		$doc->loadXML( $xml );
		$updated = $doc->getElementsByTagNameNS( 'http://www.w3.org/2005/Atom', 'updated' );

		// Feed-level updated + entry-level updated
		expect( $updated->length )->toBeGreaterThanOrEqual( 2 );
		expect( $updated->item( 1 )->nodeValue )->toBe( '2026-01-02T10:00:00+00:00' );
	} );

	it( 'preserves non-UTC timezone offsets in dates', function (): void {
		$generator = new FeedGenerator();
		$entry     = new FeedEntryDTO(
			title: 'TZ Test',
			link: 'https://example.com/tz',
			summary: 'Body.',
			publishedAt: new DateTimeImmutable( '2026-04-15T09:30:00-05:00' ),
		);

		$atom = $generator->generateAtom( 'Blog', 'https://example.com', 'Desc', [ $entry ] );
		$rss  = $generator->generateRss( 'Blog', 'https://example.com', 'Desc', [ $entry ] );

		expect( $atom )->toContain( '2026-04-15T09:30:00-05:00' );
		expect( $rss )->toContain( 'Wed, 15 Apr 2026 09:30:00 -0500' );
	} );

} );

describe( 'FeedGenerator security & spec compliance', function (): void {

	it( 'neutralizes CDATA terminators embedded in summaries', function (): void {
		$generator = new FeedGenerator();
		$payload   = 'safe]]><script>alert(1)</script><![CDATA[still safe';
		$entry     = new FeedEntryDTO(
			title: 'CDATA breakout',
			link: 'https://example.com/x',
			summary: $payload,
		);

		$rss = $generator->generateRss( 'Blog', 'https://example.com', 'Desc', [ $entry ] );
		$doc = new DOMDocument();
		expect( $doc->loadXML( $rss ) )->toBeTrue();
		// One item, no injected sibling <script>, payload preserved verbatim.
		$item = $doc->getElementsByTagName( 'item' );
		expect( $item->length )->toBe( 1 )
			->and( $doc->getElementsByTagName( 'script' )->length )->toBe( 0 );
		$itemDescs = $item->item( 0 )->getElementsByTagName( 'description' );
		expect( $itemDescs->length )->toBe( 1 )
			->and( $itemDescs->item( 0 )->nodeValue )->toBe( $payload );

		$atom = $generator->generateAtom( 'Blog', 'https://example.com', 'Desc', [ $entry ] );
		$doc  = new DOMDocument();
		expect( $doc->loadXML( $atom ) )->toBeTrue();
		expect( $doc->getElementsByTagNameNS( 'http://www.w3.org/2005/Atom', 'entry' )->length )->toBe( 1 )
			->and( $doc->getElementsByTagName( 'script' )->length )->toBe( 0 );
	} );

	it( 'always emits a feed-level author for Atom to satisfy RFC 4287', function (): void {
		config()->set( 'app.name', 'My App' );
		$generator = new FeedGenerator();
		$entry     = new FeedEntryDTO(
			title: 'Anonymous',
			link: 'https://example.com/a',
			summary: 'Body.',
			publishedAt: new DateTimeImmutable( '2026-01-02T10:00:00+00:00' ),
		);

		$xml = $generator->generateAtom( 'Blog', 'https://example.com', 'Desc', [ $entry ] );

		$doc = new DOMDocument();
		expect( $doc->loadXML( $xml ) )->toBeTrue();

		$atomNs           = 'http://www.w3.org/2005/Atom';
		$feedLevelAuthors = 0;
		foreach ( $doc->documentElement->childNodes as $child ) {
			if ( $child instanceof DOMElement && 'author' === $child->localName ) {
				$feedLevelAuthors++;
				expect( $child->getElementsByTagNameNS( $atomNs, 'name' )->item( 0 )->nodeValue )->toBe( 'My App' );
			}
		}
		expect( $feedLevelAuthors )->toBe( 1 );

		// Entry-level author omitted when entry has none.
		$entryEl      = $doc->getElementsByTagNameNS( $atomNs, 'entry' )->item( 0 );
		$entryAuthors = 0;
		foreach ( $entryEl->childNodes as $child ) {
			if ( $child instanceof DOMElement && 'author' === $child->localName ) {
				$entryAuthors++;
			}
		}
		expect( $entryAuthors )->toBe( 0 );
	} );

	it( 'honors feed-level author and email options for Atom', function (): void {
		$generator = new FeedGenerator();
		$xml       = $generator->generateAtom(
			'Blog',
			'https://example.com',
			'Desc',
			[ makeFeedEntry() ],
			[ 'author' => 'Editorial Team', 'author_email' => 'editors@example.com' ],
		);

		$doc = new DOMDocument();
		$doc->loadXML( $xml );

		$atomNs = 'http://www.w3.org/2005/Atom';
		foreach ( $doc->documentElement->childNodes as $child ) {
			if ( $child instanceof DOMElement && 'author' === $child->localName ) {
				expect( $child->getElementsByTagNameNS( $atomNs, 'name' )->item( 0 )->nodeValue )->toBe( 'Editorial Team' );
				expect( $child->getElementsByTagNameNS( $atomNs, 'email' )->item( 0 )->nodeValue )->toBe( 'editors@example.com' );
				return;
			}
		}
		$this->fail( 'Feed-level author element missing.' );
	} );

} );

describe( 'FeedGenerator filter hook', function (): void {

	it( 'passes entries through the ap.seo.feedEntries filter and preserves consumer edits', function (): void {
		addFilter( 'ap.seo.feedEntries', function ( array $entries, string $type ): array {
			expect( $type )->toBe( 'rss' );

			// Drop the second entry, keep the first.
			return [ $entries[0] ];
		} );

		try {
			$generator = new FeedGenerator();
			$xml       = $generator->generateRss(
				'Blog',
				'https://example.com',
				'Desc',
				[
					makeFeedEntry( [ 'link' => 'https://example.com/keep' ] ),
					makeFeedEntry( [ 'link' => 'https://example.com/drop' ] ),
				],
			);

			$doc = new DOMDocument();
			$doc->loadXML( $xml );
			expect( $doc->getElementsByTagName( 'item' )->length )->toBe( 1 );
			expect( $xml )->toContain( 'https://example.com/keep' )
				->and( $xml )->not->toContain( 'https://example.com/drop' );
		} finally {
			removeAllFilters( 'ap.seo.feedEntries' );
		}
	} );

} );

describe( 'FeedGenerator providers', function (): void {

	it( 'renders both RSS and Atom from a FeedProviderContract implementation', function (): void {
		$provider = new class implements FeedProviderContract {
			public function getEntries(): Collection
			{
				return collect( [ makeFeedEntry() ] );
			}

			public function getTitle(): string
			{
				return 'Provider Blog';
			}

			public function getDescription(): string
			{
				return 'Feed from a provider.';
			}

			public function getLink(): string
			{
				return 'https://example.com/blog';
			}

			public function getFeedUrl(): string
			{
				return 'https://example.com/blog/feed.xml';
			}
		};

		$generator = new FeedGenerator();

		$rss = $generator->generateRssFromProvider( $provider );
		$doc = new DOMDocument();
		expect( $doc->loadXML( $rss ) )->toBeTrue();
		expect( $doc->getElementsByTagName( 'title' )->item( 0 )->nodeValue )->toBe( 'Provider Blog' );

		$atom = $generator->generateAtomFromProvider( $provider );
		$doc  = new DOMDocument();
		expect( $doc->loadXML( $atom ) )->toBeTrue();
		expect( $doc->getElementsByTagName( 'title' )->item( 0 )->nodeValue )->toBe( 'Provider Blog' );
	} );

} );
