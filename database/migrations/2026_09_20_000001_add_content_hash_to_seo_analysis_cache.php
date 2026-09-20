<?php

/**
 * Add content_hash to seo_analysis_cache migration.
 *
 * Stores a fingerprint of the resolved-and-filtered analysis HTML so
 * cache lookups can invalidate when the content the analyzers saw
 * changes without a matching model field update — for example, a
 * template-provided H1 or `ap.seo.analysisContent` filter output.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.7.0
 */

declare( strict_types=1 );

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function up(): void
	{
		Schema::table( 'seo_analysis_cache', function ( Blueprint $table ) {
			$table->string( 'content_hash', 64 )
				->nullable()
				->after( 'content_word_count' );
		} );
	}

	/**
	 * Reverse the migrations.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function down(): void
	{
		Schema::table( 'seo_analysis_cache', function ( Blueprint $table ) {
			$table->dropColumn( 'content_hash' );
		} );
	}
};
