<?php

/**
 * SeoAnalyzableContent.
 *
 * Optional per-model contract that lets a model contribute the exact
 * HTML the SEO analyzers should reason over. Useful when the model's
 * content field is only part of what a visitor sees — for example, a
 * theme template supplies the page H1 outside the content body.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.7.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Contracts;

/**
 * SeoAnalyzableContent interface.
 *
 * Models implementing this contract short-circuit
 * `AnalysisService::extractContent()` and provide the full HTML that
 * analyzers (heading structure, image alt, internal links, etc.)
 * should inspect. Return the rendered markup a visitor would see —
 * including template-provided headings — not just the content field.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.7.0
 */
interface SeoAnalyzableContent
{
	/**
	 * Return the HTML the SEO analyzers should score.
	 *
	 * @since 1.7.0
	 *
	 * @return string
	 */
	public function getSeoAnalysisHtml(): string;
}
