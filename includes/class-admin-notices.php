<?php
/**
 * Admin Notices
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for admin notices and health checks
 */
class Geo_Ai_Woo_Admin_Notices {

    /**
     * Single instance
     *
     * @var Geo_Ai_Woo_Admin_Notices
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return Geo_Ai_Woo_Admin_Notices
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
        add_action( 'admin_notices', array( $this, 'display_notices' ) );
        add_action( 'wp_ajax_geo_ai_woo_dismiss_notice', array( $this, 'dismiss_notice' ) );
    }

    /**
     * Display admin notices
     */
    public function display_notices() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $this->activation_notice();
        $this->duplicate_plugin_notice();
        $this->file_health_notice();
        $this->permalink_notice();
    }

    /**
     * Show activation success notice
     */
    private function activation_notice() {
        $notice = get_transient( 'geo_ai_woo_activation_notice' );

        if ( ! $notice ) {
            return;
        }

        // Delete transient so it only shows once
        delete_transient( 'geo_ai_woo_activation_notice' );

        $settings_url = admin_url( 'options-general.php?page=geo-ai-for-woocommerce' );
        ?>
        <div class="notice notice-success is-dismissible geo-ai-for-woocommerce-notice">
            <p>
                <strong><?php esc_html_e( 'GEO AI Search Optimization activated!', 'geo-ai-for-woocommerce' ); ?></strong>
                <?php
                printf(
                    wp_kses(
                        /* translators: %s: settings page URL */
                        __( 'Visit <a href="%s">Settings</a> to configure AI optimization for your site.', 'geo-ai-for-woocommerce' ),
                        array( 'a' => array( 'href' => array() ) )
                    ),
                    esc_url( $settings_url )
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Show notice if multiple copies of the plugin are detected
     */
    private function duplicate_plugin_notice() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        $matches     = array();

        foreach ( $all_plugins as $plugin_file => $plugin_data ) {
            if ( 'GEO AI Search Optimization' === $plugin_data['Name'] || 'geo-ai-for-woocommerce' === $plugin_data['TextDomain'] ) {
                $matches[] = $plugin_file;
            }
        }

        if ( count( $matches ) <= 1 ) {
            return;
        }

        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'GEO AI Search Optimization: Multiple copies detected!', 'geo-ai-for-woocommerce' ); ?></strong>
                <?php esc_html_e( 'Please delete all copies of the plugin and install only one. Having multiple copies causes fatal errors.', 'geo-ai-for-woocommerce' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Show notice if llms.txt files are missing or outdated
     */
    private function file_health_notice() {
        // Only show on plugin settings page or dashboard
        $screen = get_current_screen();
        if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'settings_page_geo-ai-for-woocommerce' ), true ) ) {
            return;
        }

        // Check if dismissed
        if ( get_transient( 'geo_ai_woo_dismiss_file_health' ) ) {
            return;
        }

        $llms_file = ABSPATH . 'llms.txt';

        // Check if static file exists
        if ( ! file_exists( $llms_file ) ) {
            $this->render_dismissible_notice(
                'file_health',
                sprintf(
                    /* translators: %s: file name */
                    __( 'GEO AI Search Optimization: The %s file has not been generated yet. Click "Regenerate Now" in Settings to create it.', 'geo-ai-for-woocommerce' ),
                    '<code>llms.txt</code>'
                ),
                'warning'
            );
            return;
        }

        // Check if file is older than 7 days
        $file_age = time() - filemtime( $llms_file );
        if ( $file_age > 7 * DAY_IN_SECONDS ) {
            $this->render_dismissible_notice(
                'file_health',
                sprintf(
                    /* translators: %d: number of days */
                    __( 'GEO AI Search Optimization: Your llms.txt file hasn\'t been updated in %d days. Consider regenerating it from Settings.', 'geo-ai-for-woocommerce' ),
                    (int) ( $file_age / DAY_IN_SECONDS )
                ),
                'info'
            );
        }
    }

    /**
     * Show notice if permalink structure is "plain"
     */
    private function permalink_notice() {
        // Only on settings page
        $screen = get_current_screen();
        if ( ! $screen || 'settings_page_geo-ai-for-woocommerce' !== $screen->id ) {
            return;
        }

        // Check if dismissed
        if ( get_transient( 'geo_ai_woo_dismiss_permalink' ) ) {
            return;
        }

        $permalink_structure = get_option( 'permalink_structure' );

        if ( empty( $permalink_structure ) ) {
            $this->render_dismissible_notice(
                'permalink',
                sprintf(
                    wp_kses(
                        /* translators: %s: Permalink settings URL */
                        __( 'GEO AI Search Optimization: Your permalink structure is set to "Plain". While the plugin uses static files, we recommend using pretty permalinks for best SEO results. <a href="%s">Change permalink settings</a>', 'geo-ai-for-woocommerce' ),
                        array( 'a' => array( 'href' => array() ) )
                    ),
                    esc_url( admin_url( 'options-permalink.php' ) )
                ),
                'warning'
            );
        }
    }

    /**
     * Render a dismissible notice
     *
     * @param string $notice_id Notice identifier.
     * @param string $message   Notice message (HTML allowed).
     * @param string $type      Notice type (success, warning, error, info).
     */
    private function render_dismissible_notice( $notice_id, $message, $type = 'info' ) {
        ?>
        <div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible geo-ai-for-woocommerce-notice" data-notice-id="<?php echo esc_attr( $notice_id ); ?>">
            <p><?php echo wp_kses_post( $message ); ?></p>
        </div>
        <?php
    }

    /**
     * AJAX handler to dismiss a notice
     */
    public function dismiss_notice() {
        check_ajax_referer( 'geo_ai_woo_regenerate', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        $notice_id = isset( $_POST['notice_id'] ) ? sanitize_key( wp_unslash( $_POST['notice_id'] ) ) : '';

        if ( $notice_id ) {
            set_transient( 'geo_ai_woo_dismiss_' . $notice_id, '1', 30 * DAY_IN_SECONDS );
        }

        wp_send_json_success();
    }
}
