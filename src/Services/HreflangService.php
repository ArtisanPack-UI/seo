<?php

/**
 * HreflangService.
 *
 * Service for managing hreflang tags for multi-language SEO support.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Services;

use ArtisanPackUI\SEO\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * HreflangService class.
 *
 * Provides functionality for managing alternate language URLs (hreflang tags)
 * for internationalized SEO. Supports language codes (en, fr), region codes
 * (en-US, fr-FR), and the x-default value.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
 */
class HreflangService
{
	/**
	 * Regular expression pattern for validating locale codes (BCP-47 compliant).
	 *
	 * Matches:
	 * - Language only: en, fr, de, zh
	 * - Language-Script: zh-Hans, zh-Hant
	 * - Language-Region: en-US, fr-FR, zh-CN
	 * - Language-Script-Region: zh-Hans-CN, zh-Hant-TW
	 * - Language-Numeric Region: es-419 (Latin America)
	 * - x-default: x-default
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public const LOCALE_PATTERN = '/^(x-default|[a-z]{2,3}(-[A-Za-z]{4})?(-([A-Z]{2}|[0-9]{3}))?)$/';

	/**
	 * Common locale codes with their labels.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, string>
	 */
	public const COMMON_LOCALES = [
		'en'    => 'English',
		'en-US' => 'English (United States)',
		'en-GB' => 'English (United Kingdom)',
		'en-AU' => 'English (Australia)',
		'en-CA' => 'English (Canada)',
		'es'    => 'Spanish',
		'es-ES' => 'Spanish (Spain)',
		'es-MX' => 'Spanish (Mexico)',
		'es-AR' => 'Spanish (Argentina)',
		'fr'    => 'French',
		'fr-FR' => 'French (France)',
		'fr-CA' => 'French (Canada)',
		'de'    => 'German',
		'de-DE' => 'German (Germany)',
		'de-AT' => 'German (Austria)',
		'de-CH' => 'German (Switzerland)',
		'it'    => 'Italian',
		'it-IT' => 'Italian (Italy)',
		'pt'    => 'Portuguese',
		'pt-BR' => 'Portuguese (Brazil)',
		'pt-PT' => 'Portuguese (Portugal)',
		'nl'    => 'Dutch',
		'nl-NL' => 'Dutch (Netherlands)',
		'nl-BE' => 'Dutch (Belgium)',
		'pl'    => 'Polish',
		'pl-PL' => 'Polish (Poland)',
		'ru'    => 'Russian',
		'ru-RU' => 'Russian (Russia)',
		'ja'    => 'Japanese',
		'ja-JP' => 'Japanese (Japan)',
		'ko'    => 'Korean',
		'ko-KR' => 'Korean (Korea)',
		'zh'    => 'Chinese',
		'zh-CN' => 'Chinese (Simplified)',
		'zh-TW' => 'Chinese (Traditional)',
		'ar'    => 'Arabic',
		'ar-SA' => 'Arabic (Saudi Arabia)',
		'hi'    => 'Hindi',
		'hi-IN' => 'Hindi (India)',
		'tr'    => 'Turkish',
		'tr-TR' => 'Turkish (Turkey)',
		'sv'    => 'Swedish',
		'sv-SE' => 'Swedish (Sweden)',
		'da'    => 'Danish',
		'da-DK' => 'Danish (Denmark)',
		'fi'    => 'Finnish',
		'fi-FI' => 'Finnish (Finland)',
		'no'    => 'Norwegian',
		'no-NO' => 'Norwegian (Norway)',
		'cs'    => 'Czech',
		'cs-CZ' => 'Czech (Czech Republic)',
		'el'    => 'Greek',
		'el-GR' => 'Greek (Greece)',
		'he'    => 'Hebrew',
		'he-IL' => 'Hebrew (Israel)',
		'th'    => 'Thai',
		'th-TH' => 'Thai (Thailand)',
		'vi'    => 'Vietnamese',
		'vi-VN' => 'Vietnamese (Vietnam)',
		'id'    => 'Indonesian',
		'id-ID' => 'Indonesian (Indonesia)',
		'ms'    => 'Malay',
		'ms-MY' => 'Malay (Malaysia)',
		'uk'    => 'Ukrainian',
		'uk-UA' => 'Ukrainian (Ukraine)',
	];

	/**
	 * Create a new HreflangService instance.
	 *
	 * @since 1.0.0
	 *
	 * @param  CacheService  $cacheService  The cache service.
	 */
	public function __construct(
		protected CacheService $cacheService,
	) {
	}

	/**
	 * Get hreflang tags for a model.
	 *
	 * Returns an array of hreflang tag data ready for rendering.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to get hreflang tags for.
	 *
	 * @return array<int, array{hreflang: string, href: string}>
	 */
	public function getHreflangTags( Model $model ): array
	{
		$seoMeta = $this->getSeoMeta( $model );

		if ( null === $seoMeta || empty( $seoMeta->hreflang ) ) {
			return [];
		}

		return $this->buildHreflangTags( $seoMeta->hreflang );
	}

	/**
	 * Build hreflang tag array from stored data.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, string>  $hreflangData  The stored hreflang data.
	 *
	 * @return array<int, array{hreflang: string, href: string}>
	 */
	public function buildHreflangTags( array $hreflangData ): array
	{
		$tags         = [];
		$hasXDefault  = false;
		$defaultUrl   = null;
		$defaultLang  = config( 'seo.hreflang.default_locale' );

		foreach ( $hreflangData as $locale => $url ) {
			if ( 'x-default' === $locale ) {
				$hasXDefault = true;
			}

			if ( $locale === $defaultLang ) {
				$defaultUrl = $url;
			}

			$tags[] = [
				'hreflang' => $locale,
				'href'     => $url,
			];
		}

		// Add x-default if not already present and we have a default locale URL
		if ( ! $hasXDefault && null !== $defaultUrl ) {
			$tags[] = [
				'hreflang' => 'x-default',
				'href'     => $defaultUrl,
			];
		}

		return $tags;
	}

	/**
	 * Set an alternate URL for a locale.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta  $seoMeta  The SEO meta record.
	 * @param  string   $locale   The locale code (e.g., 'en-US', 'fr').
	 * @param  string   $url      The URL for this locale.
	 *
	 * @throws InvalidArgumentException If the locale format is invalid.
	 *
	 * @return void
	 */
	public function setAlternateUrl( SeoMeta $seoMeta, string $locale, string $url ): void
	{
		if ( ! $this->validateLocale( $locale ) ) {
			throw new InvalidArgumentException(
				sprintf( __( 'Invalid locale format: %s. Expected format like "en", "en-US", or "x-default".' ), $locale ),
			);
		}

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			throw new InvalidArgumentException(
				sprintf( __( 'Invalid URL format: %s.' ), $url ),
			);
		}

		$hreflang            = $seoMeta->hreflang ?? [];
		$hreflang[ $locale ] = $url;

		$seoMeta->hreflang = $hreflang;
		$seoMeta->save();

		// Clear cache if model is available
		$this->clearCacheForSeoMeta( $seoMeta );
	}

	/**
	 * Remove an alternate URL for a locale.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta  $seoMeta  The SEO meta record.
	 * @param  string   $locale   The locale code to remove.
	 *
	 * @return void
	 */
	public function removeAlternateUrl( SeoMeta $seoMeta, string $locale ): void
	{
		$hreflang = $seoMeta->hreflang ?? [];

		if ( isset( $hreflang[ $locale ] ) ) {
			unset( $hreflang[ $locale ] );
			$seoMeta->hreflang = $hreflang;
			$seoMeta->save();

			$this->clearCacheForSeoMeta( $seoMeta );
		}
	}

	/**
	 * Set multiple alternate URLs at once.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta               $seoMeta       The SEO meta record.
	 * @param  array<string, string> $hreflangData  Array of locale => URL pairs.
	 * @param  bool                  $replace       Whether to replace existing data or merge.
	 *
	 * @throws InvalidArgumentException If any locale or URL format is invalid.
	 *
	 * @return void
	 */
	public function setAlternateUrls( SeoMeta $seoMeta, array $hreflangData, bool $replace = false ): void
	{
		// Validate all entries first
		foreach ( $hreflangData as $locale => $url ) {
			if ( ! $this->validateLocale( $locale ) ) {
				throw new InvalidArgumentException(
					sprintf( __( 'Invalid locale format: %s.' ), $locale ),
				);
			}

			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				throw new InvalidArgumentException(
					sprintf( __( 'Invalid URL format for locale %s: %s.' ), $locale, $url ),
				);
			}
		}

		if ( $replace ) {
			$seoMeta->hreflang = $hreflangData;
		} else {
			$existing          = $seoMeta->hreflang ?? [];
			$seoMeta->hreflang = array_merge( $existing, $hreflangData );
		}

		$seoMeta->save();

		$this->clearCacheForSeoMeta( $seoMeta );
	}

	/**
	 * Clear all alternate URLs for a SEO meta record.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta  $seoMeta  The SEO meta record.
	 *
	 * @return void
	 */
	public function clearAlternateUrls( SeoMeta $seoMeta ): void
	{
		$seoMeta->hreflang = null;
		$seoMeta->save();

		$this->clearCacheForSeoMeta( $seoMeta );
	}

	/**
	 * Validate a locale code.
	 *
	 * Validates that a locale code follows the correct format:
	 * - Language only (2-3 chars): en, fr, de, zho
	 * - Language-Region: en-US, fr-FR, zh-CN
	 * - x-default
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $locale  The locale code to validate.
	 *
	 * @return bool True if valid, false otherwise.
	 */
	public function validateLocale( string $locale ): bool
	{
		return 1 === preg_match( self::LOCALE_PATTERN, $locale );
	}

	/**
	 * Get the available locales for selection.
	 *
	 * Returns the configured supported locales, or falls back to
	 * the common locales list.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public function getAvailableLocales(): array
	{
		$configuredLocales = config( 'seo.hreflang.supported_locales', [] );

		if ( empty( $configuredLocales ) ) {
			// Return all common locales
			$locales = [];
			foreach ( self::COMMON_LOCALES as $code => $label ) {
				$locales[] = [
					'value' => $code,
					'label' => $label,
				];
			}

			return $locales;
		}

		// Return only configured locales
		$locales = [];
		foreach ( $configuredLocales as $code ) {
			$label     = self::COMMON_LOCALES[ $code ] ?? $code;
			$locales[] = [
				'value' => $code,
				'label' => $label,
			];
		}

		return $locales;
	}

	/**
	 * Get the locale label for a code.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $locale  The locale code.
	 *
	 * @return string The human-readable label.
	 */
	public function getLocaleLabel( string $locale ): string
	{
		return self::COMMON_LOCALES[ $locale ] ?? $locale;
	}

	/**
	 * Check if hreflang is enabled in configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return (bool) config( 'seo.hreflang.enabled', false );
	}

	/**
	 * Get the default locale from configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function getDefaultLocale(): ?string
	{
		return config( 'seo.hreflang.default_locale' );
	}

	/**
	 * Check if a model has hreflang data.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to check.
	 *
	 * @return bool
	 */
	public function hasHreflangData( Model $model ): bool
	{
		$seoMeta = $this->getSeoMeta( $model );

		return null !== $seoMeta && ! empty( $seoMeta->hreflang );
	}

	/**
	 * Get hreflang count for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model to check.
	 *
	 * @return int
	 */
	public function getHreflangCount( Model $model ): int
	{
		$seoMeta = $this->getSeoMeta( $model );

		if ( null === $seoMeta || empty( $seoMeta->hreflang ) ) {
			return 0;
		}

		return count( $seoMeta->hreflang );
	}

	/**
	 * Get the SeoMeta for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model  $model  The model.
	 *
	 * @return SeoMeta|null
	 */
	protected function getSeoMeta( Model $model ): ?SeoMeta
	{
		if ( method_exists( $model, 'seoMeta' ) ) {
			return $model->seoMeta;
		}

		return null;
	}

	/**
	 * Clear cache for a SeoMeta record.
	 *
	 * @since 1.0.0
	 *
	 * @param  SeoMeta  $seoMeta  The SEO meta record.
	 *
	 * @return void
	 */
	protected function clearCacheForSeoMeta( SeoMeta $seoMeta ): void
	{
		// Check if seoable_type class exists before trying to load it
		if ( ! class_exists( $seoMeta->seoable_type ) ) {
			return;
		}

		$model = $seoMeta->seoable;

		if ( null !== $model ) {
			$this->cacheService->clearAllForModel( $model );
		}
	}
}
