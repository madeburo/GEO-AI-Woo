<?php
/**
 * WP-CLI Commands
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * GEO AI Search Optimization CLI commands for managing llms.txt files.
 *
 * ## EXAMPLES
 *
 *     # Regenerate all llms.txt files
 *     $ wp geo-ai-for-woocommerce regenerate
 *     Success: llms.txt files regenerated.
 *
 *     # Show current status
 *     $ wp geo-ai-for-woocommerce status
 *
 *     # Export settings to file
 *     $ wp geo-ai-for-woocommerce export --file=settings.json
 *
 *     # Import settings from file
 *     $ wp geo-ai-for-woocommerce import settings.json
 */
class Geo_Ai_Woo_CLI extends WP_CLI_Command {

	/**
	 * Regenerate llms.txt files.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Force regeneration even if cache is fresh.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp geo-ai-for-woocommerce regenerate
	 *     Success: llms.txt files regenerated.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function regenerate( $args, $assoc_args ) {
		WP_CLI::log( 'Regenerating llms.txt files...' );

		$generator = Geo_Ai_Woo_LLMS_Generator::instance();
		$generator->regenerate_cache();

		// Report generated files
		$files = array( 'llms.txt', 'llms-full.txt' );

		// Add multilingual files
		if ( class_exists( 'Geo_Ai_Woo_Multilingual' ) ) {
			$multilingual = Geo_Ai_Woo_Multilingual::instance();
			if ( $multilingual->is_active() ) {
				$files = $multilingual->get_all_llms_filenames();
			}
		}

		$items = array();
		foreach ( $files as $filename ) {
			$file_path = ABSPATH . $filename;
			if ( file_exists( $file_path ) ) {
				$items[] = array(
					'File'  => $filename,
					'Size'  => size_format( filesize( $file_path ) ),
					'Path'  => $file_path,
				);
			}
		}

		if ( ! empty( $items ) ) {
			WP_CLI\Utils\format_items( 'table', $items, array( 'File', 'Size', 'Path' ) );
		}

		WP_CLI::success( sprintf( '%d file(s) regenerated.', count( $items ) ) );
	}

	/**
	 * Show current plugin status.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp geo-ai-for-woocommerce status
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function status( $args, $assoc_args ) {
		$settings   = get_option( 'geo_ai_woo_settings', array() );
		$post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post', 'page' );

		// Plugin info
		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%BGEO AI Search Optimization v' . GEO_AI_WOO_VERSION . '%n' ) );
		WP_CLI::log( '' );

		// File status
		$file_items = array();
		$files      = array( 'llms.txt', 'llms-full.txt' );

		if ( class_exists( 'Geo_Ai_Woo_Multilingual' ) ) {
			$multilingual = Geo_Ai_Woo_Multilingual::instance();
			if ( $multilingual->is_active() ) {
				$files = $multilingual->get_all_llms_filenames();
			}
		}

		foreach ( $files as $filename ) {
			$file_path = ABSPATH . $filename;
			if ( file_exists( $file_path ) ) {
				$file_items[] = array(
					'File'    => $filename,
					'Status'  => 'Active',
					'Size'    => size_format( filesize( $file_path ) ),
					'Age'     => human_time_diff( filemtime( $file_path ) ),
				);
			} else {
				$file_items[] = array(
					'File'    => $filename,
					'Status'  => 'Missing',
					'Size'    => '-',
					'Age'     => '-',
				);
			}
		}

		WP_CLI::log( WP_CLI::colorize( '%YFiles:%n' ) );
		WP_CLI\Utils\format_items( 'table', $file_items, array( 'File', 'Status', 'Size', 'Age' ) );

		// Content stats
		$indexed_query = new WP_Query( array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => '_geo_ai_woo_exclude',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_geo_ai_woo_exclude',
					'value'   => '1',
					'compare' => '!=',
				),
			),
		) );

		$excluded_query = new WP_Query( array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_geo_ai_woo_exclude',
					'value'   => '1',
					'compare' => '=',
				),
			),
		) );

		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%YContent:%n' ) );
		WP_CLI::log( sprintf( '  Indexed posts:  %d', $indexed_query->found_posts ) );
		WP_CLI::log( sprintf( '  Excluded posts: %d', $excluded_query->found_posts ) );
		WP_CLI::log( sprintf( '  Post types:     %s', implode( ', ', $post_types ) ) );
		WP_CLI::log( sprintf( '  Cache duration: %s', isset( $settings['cache_duration'] ) ? $settings['cache_duration'] : 'daily' ) );

		// Last regenerated
		$last_regen = get_option( 'geo_ai_woo_last_regenerated', 0 );
		if ( $last_regen ) {
			WP_CLI::log( sprintf( '  Last regenerated: %s ago', human_time_diff( $last_regen ) ) );
		}

		// Multilingual
		if ( class_exists( 'Geo_Ai_Woo_Multilingual' ) ) {
			$multilingual = Geo_Ai_Woo_Multilingual::instance();
			if ( $multilingual->is_active() ) {
				$languages = $multilingual->get_active_languages();
				$lang_list = array_map( function( $l ) {
					return $l['code'] . ( ! empty( $l['default'] ) ? ' (default)' : '' );
				}, $languages );
				WP_CLI::log( '' );
				WP_CLI::log( WP_CLI::colorize( '%YMultilingual:%n' ) );
				WP_CLI::log( sprintf( '  Provider:  %s', $multilingual->get_provider() ) );
				WP_CLI::log( sprintf( '  Languages: %s', implode( ', ', $lang_list ) ) );
			}
		}

		WP_CLI::log( '' );
	}

	/**
	 * Export settings to a JSON file.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<path>]
	 * : Path to export file. Default: geo-ai-for-woocommerce-settings.json
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp geo-ai-for-woocommerce export
	 *     Success: Settings exported to geo-ai-for-woocommerce-settings.json
	 *
	 *     $ wp geo-ai-for-woocommerce export --file=/tmp/my-settings.json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function export( $args, $assoc_args ) {
		$file = isset( $assoc_args['file'] ) ? $assoc_args['file'] : 'geo-ai-for-woocommerce-settings.json';

		$settings = get_option( 'geo_ai_woo_settings', array() );

		// Remove sensitive data
		$export_settings = $settings;
		unset( $export_settings['ai_api_key'] );

		$export_data = array(
			'plugin_version' => GEO_AI_WOO_VERSION,
			'exported_at'    => gmdate( 'Y-m-d H:i:s' ) . ' UTC',
			'site_url'       => home_url(),
			'settings'       => $export_settings,
		);

		$json = wp_json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		// Restrict file writes to the WordPress uploads directory.
		$upload_dir = wp_upload_dir();
		$geo_dir    = trailingslashit( $upload_dir['basedir'] ) . 'geo-ai-for-woocommerce/';
		if ( ! file_exists( $geo_dir ) ) {
			wp_mkdir_p( $geo_dir );
		}
		$safe_file = $geo_dir . sanitize_file_name( basename( $file ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $safe_file, $json ) ) {
			WP_CLI::error( sprintf( 'Could not write to file: %s', $safe_file ) );
		}

		WP_CLI::success( sprintf( 'Settings exported to %s', $safe_file ) );
	}

	/**
	 * Import settings from a JSON file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to the settings JSON file.
	 *
	 * [--regenerate]
	 * : Regenerate llms.txt files after import.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp geo-ai-for-woocommerce import settings.json
	 *     Success: Settings imported from settings.json
	 *
	 *     $ wp geo-ai-for-woocommerce import settings.json --regenerate
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function import( $args, $assoc_args ) {
		$file = $args[0];

		if ( ! file_exists( $file ) ) {
			WP_CLI::error( sprintf( 'File not found: %s', $file ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$json = file_get_contents( $file );
		$data = json_decode( $json, true );

		if ( null === $data || ! isset( $data['settings'] ) ) {
			WP_CLI::error( 'Invalid settings file format.' );
		}

		// Validate keys — only allow known settings keys
		$valid_keys = array(
			'post_types', 'bot_rules', 'cache_duration', 'site_description',
			'include_taxonomies', 'hide_out_of_stock', 'seo_meta_enabled',
			'seo_link_header', 'seo_jsonld_enabled', 'robots_txt_enabled',
			'multilingual_enabled', 'crawl_tracking_enabled',
			'ai_provider', 'ai_model', 'ai_max_tokens', 'ai_prompt_template',
		);

		$import_settings = array();
		foreach ( $data['settings'] as $key => $value ) {
			if ( in_array( $key, $valid_keys, true ) ) {
				$import_settings[ $key ] = $value;
			}
		}

		// Merge with existing (preserve API key and other non-exported settings)
		$existing = get_option( 'geo_ai_woo_settings', array() );
		$merged   = array_merge( $existing, $import_settings );

		update_option( 'geo_ai_woo_settings', $merged );

		WP_CLI::success( sprintf( 'Settings imported from %s (%d keys).', $file, count( $import_settings ) ) );

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'regenerate', false ) ) {
			WP_CLI::log( 'Regenerating llms.txt files...' );
			Geo_Ai_Woo_LLMS_Generator::instance()->regenerate_cache();
			WP_CLI::success( 'Files regenerated.' );
		}
	}
}
