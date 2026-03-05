<?php
/**
 * Crawl Tracker — AI Bot Visit Logging
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for tracking AI bot visits to llms.txt files
 */
class Geo_Ai_Woo_Crawl_Tracker {

	/**
	 * Single instance
	 *
	 * @var Geo_Ai_Woo_Crawl_Tracker
	 */
	private static $instance = null;

	/**
	 * Database table name (without prefix)
	 *
	 * @var string
	 */
	const TABLE_NAME = 'geo_ai_woo_crawl_log';

	/**
	 * Get single instance
	 *
	 * @return Geo_Ai_Woo_Crawl_Tracker
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Auto-cleanup old records daily
		add_action( 'geo_ai_woo_regenerate_llms', array( $this, 'cleanup_old_records' ) );
	}

	/**
	 * Get the full table name with prefix
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Create the tracking database table
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			bot_name varchar(50) NOT NULL,
			file_type varchar(20) NOT NULL DEFAULT 'standard',
			ip_hash varchar(64) NOT NULL,
			user_agent text NOT NULL,
			accessed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY bot_name (bot_name),
			KEY accessed_at (accessed_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop the tracking table
	 */
	public static function drop_table() {
		global $wpdb;
		$table_name = self::get_table_name();
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
	}

	/**
	 * Log a bot visit
	 *
	 * @param string $file_type File type: 'standard' or 'full'.
	 */
	public function log_visit( $file_type = 'standard' ) {
		$settings = get_option( 'geo_ai_woo_settings', array() );
		$enabled  = isset( $settings['crawl_tracking_enabled'] ) ? $settings['crawl_tracking_enabled'] : '1';

		if ( '1' !== $enabled ) {
			return;
		}

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$bot_name   = $this->detect_bot( $user_agent );

		if ( ! $bot_name ) {
			return;
		}

		// Anonymize IP for GDPR compliance
		$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ip_hash = wp_hash( $ip );

		global $wpdb;
		$table_name = self::get_table_name();

		// Silently skip if table doesn't exist yet
		$table_exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
				DB_NAME,
				$table_name
			)
		);

		if ( ! $table_exists ) {
			return;
		}

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table_name,
			array(
				'bot_name'    => $bot_name,
				'file_type'   => in_array( $file_type, array( 'standard', 'full' ), true ) ? $file_type : 'standard',
				'ip_hash'     => $ip_hash,
				'user_agent'  => mb_substr( $user_agent, 0, 500 ),
				'accessed_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Detect AI bot from user agent string
	 *
	 * @param string $user_agent User agent string.
	 * @return string|null Bot name or null if not recognized.
	 */
	private function detect_bot( $user_agent ) {
		if ( empty( $user_agent ) ) {
			return null;
		}

		$bots = Geo_Ai_Woo_LLMS_Generator::instance()->get_ai_bots();

		foreach ( array_keys( $bots ) as $bot_name ) {
			if ( false !== stripos( $user_agent, $bot_name ) ) {
				return $bot_name;
			}
		}

		/**
		 * Filter to detect custom bots not in the default list
		 *
		 * @param string|null $bot_name   Detected bot name.
		 * @param string      $user_agent User agent string.
		 */
		return apply_filters( 'geo_ai_woo_detect_bot', null, $user_agent );
	}

	/**
	 * Get recent bot activity summary (last 30 days)
	 *
	 * @param int $days Number of days to look back.
	 * @return array Array of objects with bot_name, visit_count, last_visit.
	 */
	public function get_recent_activity( $days = 30 ) {
		global $wpdb;
		$table_name = self::get_table_name();

		// Check if table exists
		$table_exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
				DB_NAME,
				$table_name
			)
		);

		if ( ! $table_exists ) {
			return array();
		}

		$since = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT bot_name, COUNT(*) as visit_count, MAX(accessed_at) as last_visit
				FROM %i
				WHERE accessed_at > %s
				GROUP BY bot_name
				ORDER BY visit_count DESC
				LIMIT 10',
				$table_name,
				$since
			)
		);
	}

	/**
	 * Get total visit count
	 *
	 * @param int $days Number of days to look back.
	 * @return int
	 */
	public function get_total_visits( $days = 30 ) {
		global $wpdb;
		$table_name = self::get_table_name();

		// Check if table exists
		$table_exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
				DB_NAME,
				$table_name
			)
		);

		if ( ! $table_exists ) {
			return 0;
		}

		$since = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE accessed_at > %s',
				$table_name,
				$since
			)
		);

		return (int) $count;
	}

	/**
	 * Clean up records older than 90 days
	 */
	public function cleanup_old_records() {
		global $wpdb;
		$table_name = self::get_table_name();

		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( 90 * DAY_IN_SECONDS ) );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'DELETE FROM %i WHERE accessed_at < %s',
				$table_name,
				$cutoff
			)
		);
	}
}
