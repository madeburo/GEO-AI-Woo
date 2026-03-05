<?php
/**
 * Uninstall GEO AI Woo
 *
 * Fired when the plugin is uninstalled.
 *
 * @package GeoAiWoo
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Run uninstall cleanup.
 */
function geo_ai_woo_uninstall() {
	global $wpdb;

	// Delete plugin options.
	delete_option( 'geo_ai_woo_settings' );
	delete_option( 'geo_ai_woo_last_regenerated' );

	// Delete transients.
	delete_transient( 'geo_ai_woo_llms' );
	delete_transient( 'geo_ai_woo_llms_full' );
	delete_transient( 'geo_ai_woo_activation_notice' );
	delete_transient( 'geo_ai_woo_dismiss_file_health' );
	delete_transient( 'geo_ai_woo_dismiss_permalink' );
	delete_transient( 'geo_ai_woo_dashboard_stats' );
	delete_transient( 'geo_ai_woo_ai_rate_limit' );

	// Delete user-specific transients.
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_geo_ai_woo_bulk_progress_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_geo_ai_woo_bulk_progress_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_geo_ai_woo_api_rate_limit_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_geo_ai_woo_api_rate_limit_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

	// Clear scheduled events.
	wp_clear_scheduled_hook( 'geo_ai_woo_regenerate_llms' );

	// Delete post meta for all posts.
	$meta_keys    = array(
		'_geo_ai_woo_description',
		'_geo_ai_woo_keywords',
		'_geo_ai_woo_exclude',
		'_geo_ai_woo_auto_description',
	);
	$placeholders = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$meta_keys
		)
	);

	// Drop crawl tracking table.
	$table_name = $wpdb->prefix . 'geo_ai_woo_crawl_log';
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange

	// Delete static files.
	$static_files = array(
		ABSPATH . 'llms.txt',
		ABSPATH . 'llms-full.txt',
	);
	foreach ( $static_files as $file ) {
		if ( file_exists( $file ) ) {
			wp_delete_file( $file );
		}
	}

	// Delete multilingual files (llms-*.txt, llms-full-*.txt).
	$language_codes = array( 'en', 'ru', 'kk', 'uz', 'zh', 'id', 'hi', 'de', 'fr', 'es', 'it', 'pt', 'ja', 'ko', 'ar', 'tr', 'pl', 'nl', 'sv', 'da', 'fi', 'no', 'cs', 'sk', 'uk', 'bg', 'ro', 'hr', 'sl', 'el', 'hu', 'th', 'vi', 'ms' );
	foreach ( $language_codes as $code ) {
		$files = array(
			ABSPATH . 'llms-' . $code . '.txt',
			ABSPATH . 'llms-full-' . $code . '.txt',
		);
		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}

	// Flush rewrite rules.
	flush_rewrite_rules();
}

geo_ai_woo_uninstall();
