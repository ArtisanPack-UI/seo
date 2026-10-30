<?php

/**
 * Make sitemap priority and change frequency nullable migration.
 *
 * Default values gave every URL the same `priority` and `changefreq`, which
 * tells crawlers nothing. Null now means "leave it out of the sitemap".
 * Existing rows holding the old defaults are cleared because they can't be
 * told apart from a deliberate choice.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.9.0
 */

declare( strict_types=1 );

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @since 1.9.0
	 *
	 * @return void
	 */
	public function up(): void
	{
		Schema::table( 'seo_meta', function ( Blueprint $table ) {
			$table->decimal( 'sitemap_priority', 2, 1 )->nullable()->default( null )->change();
			$table->string( 'sitemap_changefreq', 20 )->nullable()->default( null )->change();
		} );

		Schema::table( 'sitemap_entries', function ( Blueprint $table ) {
			$table->decimal( 'priority', 2, 1 )->nullable()->default( null )->change();
			$table->string( 'changefreq', 20 )->nullable()->default( null )->change();
		} );

		DB::table( 'seo_meta' )->where( 'sitemap_priority', 0.5 )->update( [ 'sitemap_priority' => null ] );
		DB::table( 'seo_meta' )->where( 'sitemap_changefreq', 'weekly' )->update( [ 'sitemap_changefreq' => null ] );
		DB::table( 'sitemap_entries' )->where( 'priority', 0.5 )->update( [ 'priority' => null ] );
		DB::table( 'sitemap_entries' )->where( 'changefreq', 'weekly' )->update( [ 'changefreq' => null ] );
	}

	/**
	 * Reverse the migrations.
	 *
	 * @since 1.9.0
	 *
	 * @return void
	 */
	public function down(): void
	{
		DB::table( 'seo_meta' )->whereNull( 'sitemap_priority' )->update( [ 'sitemap_priority' => 0.5 ] );
		DB::table( 'seo_meta' )->whereNull( 'sitemap_changefreq' )->update( [ 'sitemap_changefreq' => 'weekly' ] );
		DB::table( 'sitemap_entries' )->whereNull( 'priority' )->update( [ 'priority' => 0.5 ] );
		DB::table( 'sitemap_entries' )->whereNull( 'changefreq' )->update( [ 'changefreq' => 'weekly' ] );

		Schema::table( 'seo_meta', function ( Blueprint $table ) {
			$table->decimal( 'sitemap_priority', 2, 1 )->nullable( false )->default( 0.5 )->change();
			$table->string( 'sitemap_changefreq', 20 )->nullable( false )->default( 'weekly' )->change();
		} );

		Schema::table( 'sitemap_entries', function ( Blueprint $table ) {
			$table->decimal( 'priority', 2, 1 )->nullable( false )->default( 0.5 )->change();
			$table->string( 'changefreq', 20 )->nullable( false )->default( 'weekly' )->change();
		} );
	}
};
