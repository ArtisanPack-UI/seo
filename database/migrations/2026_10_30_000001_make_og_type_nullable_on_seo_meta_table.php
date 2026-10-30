<?php

/**
 * Make og_type nullable on seo_meta migration.
 *
 * A `website` default made every row look like a deliberate choice, so
 * Open Graph type inference never ran for models with SEO meta. Null now
 * means "infer it". Existing `website` rows are cleared because they can't
 * be told apart from the default, and inference returns the configured
 * default type for anything that isn't an article, product, event or profile.
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
			$table->string( 'og_type', 50 )->nullable()->default( null )->change();
		} );

		DB::table( 'seo_meta' )->where( 'og_type', 'website' )->update( [ 'og_type' => null ] );
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
		DB::table( 'seo_meta' )->whereNull( 'og_type' )->update( [ 'og_type' => 'website' ] );

		Schema::table( 'seo_meta', function ( Blueprint $table ) {
			$table->string( 'og_type', 50 )->nullable( false )->default( 'website' )->change();
		} );
	}
};
