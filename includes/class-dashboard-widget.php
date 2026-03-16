<?php
/**
 * Dashboard Widget — Content Stats & Bot Activity
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for WordPress dashboard widget
 */
class Geo_Ai_Woo_Dashboard_Widget {

	/**
	 * Single instance
	 *
	 * @var Geo_Ai_Woo_Dashboard_Widget
	 */
	private static $instance = null;

	/**
	 * Get single instance
	 *
	 * @return Geo_Ai_Woo_Dashboard_Widget
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
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
	}

	/**
	 * Register the dashboard widget
	 */
	public function register_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'geo_ai_woo_dashboard',
			__( 'GEO AI Search Optimization', 'geo-ai-for-woocommerce' ),
			array( $this, 'render_widget' )
		);
	}

	/**
	 * Render the dashboard widget content
	 */
	public function render_widget() {
		$stats = $this->get_stats();
		?>
		<div class="geo-ai-for-woocommerce-dashboard">
			<ul class="geo-ai-for-woocommerce-dashboard-stats">
				<li>
					<span class="label"><?php esc_html_e( 'Indexed', 'geo-ai-for-woocommerce' ); ?></span>
					<span class="value"><?php echo esc_html( $stats['indexed_count'] ); ?></span>
				</li>
				<li>
					<span class="label"><?php esc_html_e( 'Excluded', 'geo-ai-for-woocommerce' ); ?></span>
					<span class="value"><?php echo esc_html( $stats['excluded_count'] ); ?></span>
				</li>
				<li>
					<span class="label"><?php esc_html_e( 'Files', 'geo-ai-for-woocommerce' ); ?></span>
					<span class="value"><?php echo esc_html( $stats['file_count'] ); ?></span>
				</li>
			</ul>

			<table class="widefat geo-ai-for-woocommerce-dashboard-table">
				<tbody>
					<tr>
						<td><?php esc_html_e( 'llms.txt Status', 'geo-ai-for-woocommerce' ); ?></td>
						<td>
							<?php if ( $stats['file_exists'] ) : ?>
								<span class="geo-ai-for-woocommerce-status-badge active"><?php esc_html_e( 'Active', 'geo-ai-for-woocommerce' ); ?></span>
								<span class="description">(<?php echo esc_html( size_format( $stats['file_size'] ) ); ?>)</span>
							<?php else : ?>
								<span class="geo-ai-for-woocommerce-status-badge inactive"><?php esc_html_e( 'Not Generated', 'geo-ai-for-woocommerce' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Last Regenerated', 'geo-ai-for-woocommerce' ); ?></td>
						<td>
							<?php
							if ( $stats['last_regenerated'] ) {
								/* translators: %s: human-readable time difference */
								printf( esc_html__( '%s ago', 'geo-ai-for-woocommerce' ), esc_html( human_time_diff( $stats['last_regenerated'] ) ) );
							} else {
								esc_html_e( 'Never', 'geo-ai-for-woocommerce' );
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>

			<?php $this->render_bot_activity(); ?>

			<p class="geo-ai-for-woocommerce-dashboard-links">
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=geo-ai-for-woocommerce' ) ); ?>" class="button button-small">
					<?php esc_html_e( 'Settings', 'geo-ai-for-woocommerce' ); ?>
				</a>
				<a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" class="button button-small" target="_blank">
					<?php esc_html_e( 'View llms.txt', 'geo-ai-for-woocommerce' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Get dashboard statistics
	 *
	 * @return array
	 */
	private function get_stats() {
		$cached = get_transient( 'geo_ai_woo_dashboard_stats' );

		if ( false !== $cached ) {
			return $cached;
		}

		$settings   = get_option( 'geo_ai_woo_settings', array() );
		$post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post', 'page' );

		// Count indexed posts (not excluded)
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

		// File info
		$llms_file   = ABSPATH . 'llms.txt';
		$file_exists = file_exists( $llms_file );

		// Count total generated files
		$file_count = 0;
		if ( $file_exists ) {
			$file_count++;
		}
		if ( file_exists( ABSPATH . 'llms-full.txt' ) ) {
			$file_count++;
		}

		// Count multilingual files
		if ( class_exists( 'Geo_Ai_Woo_Multilingual' ) ) {
			$multilingual = Geo_Ai_Woo_Multilingual::instance();
			if ( $multilingual->is_active() ) {
				$all_files = $multilingual->get_all_llms_filenames();
				$file_count = 0;
				foreach ( $all_files as $filename ) {
					if ( file_exists( ABSPATH . $filename ) ) {
						$file_count++;
					}
				}
			}
		}

		$stats = array(
			'indexed_count'    => $indexed_query->found_posts,
			'excluded_count'   => $excluded_query->found_posts,
			'file_exists'      => $file_exists,
			'file_size'        => $file_exists ? filesize( $llms_file ) : 0,
			'file_count'       => $file_count,
			'last_regenerated' => get_option( 'geo_ai_woo_last_regenerated', 0 ),
		);

		set_transient( 'geo_ai_woo_dashboard_stats', $stats, HOUR_IN_SECONDS );

		return $stats;
	}

	/**
	 * Render bot activity section
	 */
	private function render_bot_activity() {
		if ( ! class_exists( 'Geo_Ai_Woo_Crawl_Tracker' ) ) {
			return;
		}

		$tracker  = Geo_Ai_Woo_Crawl_Tracker::instance();
		$activity = $tracker->get_recent_activity();

		if ( empty( $activity ) ) {
			echo '<p class="description">' . esc_html__( 'No AI bot visits recorded yet.', 'geo-ai-for-woocommerce' ) . '</p>';
			echo '<p class="description"><em>' . esc_html__( 'Bot tracking works for dynamic serving mode. For static files, check your server access logs.', 'geo-ai-for-woocommerce' ) . '</em></p>';
			return;
		}

		echo '<h4>' . esc_html__( 'Recent Bot Activity', 'geo-ai-for-woocommerce' ) . '</h4>';
		echo '<table class="widefat geo-ai-for-woocommerce-dashboard-table">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Bot', 'geo-ai-for-woocommerce' ) . '</th>';
		echo '<th>' . esc_html__( 'Visits (30d)', 'geo-ai-for-woocommerce' ) . '</th>';
		echo '<th>' . esc_html__( 'Last Visit', 'geo-ai-for-woocommerce' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $activity as $row ) {
			echo '<tr>';
			echo '<td><code>' . esc_html( $row->bot_name ) . '</code></td>';
			echo '<td>' . esc_html( $row->visit_count ) . '</td>';
			echo '<td>';
			/* translators: %s: human-readable time difference */
			printf( esc_html__( '%s ago', 'geo-ai-for-woocommerce' ), esc_html( human_time_diff( strtotime( $row->last_visit ) ) ) );
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}
}
