<?php
/**
 * REST API — Programmatic Access to llms.txt Data
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for REST API endpoints
 */
class Geo_Ai_Woo_REST_API {

	/**
	 * Single instance
	 *
	 * @var Geo_Ai_Woo_REST_API
	 */
	private static $instance = null;

	/**
	 * API namespace
	 *
	 * @var string
	 */
	const NAMESPACE = 'geo-ai-for-woocommerce/v1';

	/**
	 * Get single instance
	 *
	 * @return Geo_Ai_Woo_REST_API
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// GET /llms — public
		register_rest_route( self::NAMESPACE, '/llms', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_llms' ),
			'permission_callback' => '__return_true',
		) );

		// GET /llms/full — public
		register_rest_route( self::NAMESPACE, '/llms/full', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_llms_full' ),
			'permission_callback' => '__return_true',
		) );

		// GET /status — admin only
		register_rest_route( self::NAMESPACE, '/status', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_status' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// POST /regenerate — admin only
		register_rest_route( self::NAMESPACE, '/regenerate', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'regenerate' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// GET /settings — admin only
		register_rest_route( self::NAMESPACE, '/settings', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_settings' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );
	}

	/**
	 * Permission callback for admin endpoints
	 *
	 * @return bool|WP_Error
	 */
	public function check_admin_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this resource.', 'geo-ai-for-woocommerce' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * GET /llms — Return standard llms.txt content
	 *
	 * @return WP_REST_Response
	 */
	public function get_llms() {
		$content = Geo_Ai_Woo_LLMS_Generator::instance()->generate( false );

		return new WP_REST_Response( $content, 200, array(
			'Content-Type'  => 'text/plain; charset=utf-8',
			'Cache-Control' => 'public, max-age=86400',
		) );
	}

	/**
	 * GET /llms/full — Return full llms.txt content
	 *
	 * @return WP_REST_Response
	 */
	public function get_llms_full() {
		$content = Geo_Ai_Woo_LLMS_Generator::instance()->generate( true );

		return new WP_REST_Response( $content, 200, array(
			'Content-Type'  => 'text/plain; charset=utf-8',
			'Cache-Control' => 'public, max-age=86400',
		) );
	}

	/**
	 * GET /status — Return file status and statistics
	 *
	 * @return WP_REST_Response
	 */
	public function get_status() {
		$llms_file      = ABSPATH . 'llms.txt';
		$llms_full_file = ABSPATH . 'llms-full.txt';

		$settings   = get_option( 'geo_ai_woo_settings', array() );
		$post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post', 'page' );

		// Count indexed posts
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

		// Count excluded posts
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

		$data = array(
			'version'          => GEO_AI_WOO_VERSION,
			'llms_txt'         => array(
				'exists'    => file_exists( $llms_file ),
				'size'      => file_exists( $llms_file ) ? filesize( $llms_file ) : 0,
				'age'       => file_exists( $llms_file ) ? time() - filemtime( $llms_file ) : null,
				'url'       => home_url( '/llms.txt' ),
			),
			'llms_full_txt'    => array(
				'exists'    => file_exists( $llms_full_file ),
				'size'      => file_exists( $llms_full_file ) ? filesize( $llms_full_file ) : 0,
				'age'       => file_exists( $llms_full_file ) ? time() - filemtime( $llms_full_file ) : null,
				'url'       => home_url( '/llms-full.txt' ),
			),
			'last_regenerated' => (int) get_option( 'geo_ai_woo_last_regenerated', 0 ),
			'indexed_count'    => $indexed_query->found_posts,
			'excluded_count'   => $excluded_query->found_posts,
			'post_types'       => $post_types,
		);

		// Add multilingual info if active
		if ( class_exists( 'Geo_Ai_Woo_Multilingual' ) ) {
			$multilingual = Geo_Ai_Woo_Multilingual::instance();
			if ( $multilingual->is_active() ) {
				$data['multilingual'] = array(
					'provider'  => $multilingual->get_provider(),
					'languages' => $multilingual->get_active_languages(),
				);
			}
		}

		// Add crawl stats if tracker available
		if ( class_exists( 'Geo_Ai_Woo_Crawl_Tracker' ) ) {
			$tracker = Geo_Ai_Woo_Crawl_Tracker::instance();
			$data['crawl_stats'] = array(
				'total_visits_30d' => $tracker->get_total_visits( 30 ),
				'recent_activity'  => $tracker->get_recent_activity( 30 ),
			);
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * POST /regenerate — Force regeneration of llms.txt files
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function regenerate() {
		$user_id   = get_current_user_id();
		$rate_key  = 'geo_ai_woo_api_rate_limit_' . $user_id;

		// Rate limit: max 1 regeneration per 60 seconds
		if ( get_transient( $rate_key ) ) {
			return new WP_Error(
				'rate_limited',
				__( 'Please wait at least 60 seconds between regeneration requests.', 'geo-ai-for-woocommerce' ),
				array( 'status' => 429 )
			);
		}

		set_transient( $rate_key, '1', 60 );

		Geo_Ai_Woo_LLMS_Generator::instance()->regenerate_cache();

		return new WP_REST_Response( array(
			'success'   => true,
			'message'   => __( 'llms.txt files regenerated successfully.', 'geo-ai-for-woocommerce' ),
			'timestamp' => time(),
		), 200 );
	}

	/**
	 * GET /settings — Return current plugin settings
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		$settings = get_option( 'geo_ai_woo_settings', array() );

		// Remove sensitive data (API keys) from response
		$safe_settings = $settings;
		if ( isset( $safe_settings['ai_api_key'] ) ) {
			$safe_settings['ai_api_key'] = ! empty( $safe_settings['ai_api_key'] ) ? '****' : '';
		}

		return new WP_REST_Response( $safe_settings, 200 );
	}
}
