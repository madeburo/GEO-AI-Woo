<?php
/**
 * Plugin Name: GEO AI Woo
 * Plugin URI: https://github.com/madeburo/geo-ai-woo
 * Description: Generative Engine Optimization for WordPress & WooCommerce. Optimize your site for AI search engines like ChatGPT, Claude, Gemini, Perplexity, YandexGPT, GigaChat, and more.
 * Version: 0.5.3
 * Author: Made Büro
 * Author URI: https://madeburo.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: geo-ai-woo
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.6
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants
define( 'GEO_AI_WOO_VERSION', '0.5.3' );
define( 'GEO_AI_WOO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEO_AI_WOO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GEO_AI_WOO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
final class Geo_Ai_Woo {

    /**
     * Single instance of the class
     *
     * @var Geo_Ai_Woo
     */
    private static $instance = null;

    /**
     * Get single instance of the class
     *
     * @return Geo_Ai_Woo
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
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-llms-generator.php';
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-meta-box.php';
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-settings.php';
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-seo-headers.php';
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-admin-notices.php';
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-multilingual.php';
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-crawl-tracker.php';
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-rest-api.php';
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-ai-generator.php';

        if ( is_admin() ) {
            require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-bulk-edit.php';
            require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-dashboard-widget.php';
        }

        // WP-CLI commands
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-cli.php';
            WP_CLI::add_command( 'geo-ai-woo', 'Geo_Ai_Woo_CLI' );
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Text domain is loaded automatically by WordPress for plugins hosted on WordPress.org.

        // Initialize components
        add_action( 'init', array( $this, 'init' ), 0 );

        // Admin assets
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );

        // Plugin action links
        add_filter( 'plugin_action_links_' . GEO_AI_WOO_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );

        // Activation/Deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // WooCommerce HPOS compatibility
        add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
    }

    /**
     * Declare WooCommerce HPOS compatibility
     */
    public function declare_hpos_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_storage',
                __FILE__,
                true
            );
        }
    }

    /**
     * Load plugin text domain
     */

    /**
     * Initialize plugin components
     */
    public function init() {
        // Initialize LLMS generator
        Geo_Ai_Woo_LLMS_Generator::instance();

        // Initialize Meta Box
        Geo_Ai_Woo_Meta_Box::instance();

        // Initialize Settings
        Geo_Ai_Woo_Settings::instance();

        // Initialize SEO Headers
        Geo_Ai_Woo_SEO_Headers::instance();

        // Initialize Multilingual
        Geo_Ai_Woo_Multilingual::instance();

        // Initialize Crawl Tracker
        Geo_Ai_Woo_Crawl_Tracker::instance();

        // Initialize REST API
        Geo_Ai_Woo_REST_API::instance();

        // Initialize AI Generator
        Geo_Ai_Woo_AI_Generator::instance();

        // Initialize Admin components
        if ( is_admin() ) {
            Geo_Ai_Woo_Admin_Notices::instance();
            Geo_Ai_Woo_Bulk_Edit::instance();
            Geo_Ai_Woo_Dashboard_Widget::instance();
        }

        // Lazy-load WooCommerce integration (WC is loaded by plugins_loaded)
        if ( class_exists( 'WooCommerce' ) ) {
            require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-woocommerce.php';
            Geo_Ai_Woo_WooCommerce::instance();
        }
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook.
     */
    public function admin_assets( $hook ) {
        $screen = get_current_screen();

        // Load on settings page, post/product edit screens, and list tables
        $is_settings  = ( 'settings_page_geo-ai-woo' === $hook );
        $is_editor    = ( $screen && 'post' === $screen->base );
        $is_list_page = ( $screen && 'edit' === $screen->base );

        if ( ! $is_settings && ! $is_editor && ! $is_list_page ) {
            return;
        }

        wp_enqueue_style(
            'geo-ai-woo-admin',
            GEO_AI_WOO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            GEO_AI_WOO_VERSION
        );

        wp_enqueue_script(
            'geo-ai-woo-admin',
            GEO_AI_WOO_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            GEO_AI_WOO_VERSION,
            true
        );

        wp_localize_script( 'geo-ai-woo-admin', 'geo_ai_woo_admin', array(
            'nonce'            => wp_create_nonce( 'geo_ai_woo_regenerate' ),
            'preview_nonce'    => wp_create_nonce( 'geo_ai_woo_preview' ),
            'ai_nonce'         => wp_create_nonce( 'geo_ai_woo_ai_generate' ),
            'ai_bulk_nonce'    => wp_create_nonce( 'geo_ai_woo_ai_bulk' ),
            'regenerating'     => __( 'Regenerating...', 'geo-ai-woo' ),
            'done'             => __( 'Done!', 'geo-ai-woo' ),
            'error'            => __( 'Error', 'geo-ai-woo' ),
            'loading'          => __( 'Loading preview...', 'geo-ai-woo' ),
            'ai_generating'    => __( 'Generating...', 'geo-ai-woo' ),
            'ai_generated'     => __( 'Generated!', 'geo-ai-woo' ),
            'ai_bulk_running'  => __( 'Processing...', 'geo-ai-woo' ),
            'ai_bulk_complete' => __( 'Complete!', 'geo-ai-woo' ),
        ) );
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing links.
     * @return array Modified links.
     */
    public function plugin_action_links( $links ) {
        $plugin_links = array(
            '<a href="' . admin_url( 'options-general.php?page=geo-ai-woo' ) . '">' . __( 'Settings', 'geo-ai-woo' ) . '</a>',
        );
        return array_merge( $plugin_links, $links );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $defaults = array(
            'post_types'             => array( 'post', 'page', 'product' ),
            'bot_rules'              => array(
                'GPTBot'             => 'allow',
                'OAI-SearchBot'      => 'allow',
                'ClaudeBot'          => 'allow',
                'Google-Extended'    => 'allow',
                'PerplexityBot'      => 'allow',
                'DeepSeekBot'        => 'allow',
                'GrokBot'            => 'allow',
                'meta-externalagent' => 'allow',
                'PanguBot'           => 'allow',
                'YandexBot'          => 'allow',
                'SputnikBot'         => 'allow',
                'Bytespider'         => 'allow',
                'Baiduspider'        => 'allow',
            ),
            'cache_duration'         => 'daily',
            'site_description'       => get_bloginfo( 'description' ),
            'include_taxonomies'     => '1',
            'hide_out_of_stock'      => 'wc_default',
            'seo_meta_enabled'       => '1',
            'seo_link_header'        => '1',
            'seo_jsonld_enabled'     => '1',
            'robots_txt_enabled'     => '1',
            // v0.3.0 defaults
            'multilingual_enabled'   => '1',
            'crawl_tracking_enabled' => '1',
            'ai_provider'            => 'none',
            'ai_api_key'             => '',
            'ai_model'               => '',
            'ai_max_tokens'          => 150,
            'ai_prompt_template'     => '',
        );

        if ( ! get_option( 'geo_ai_woo_settings' ) ) {
            add_option( 'geo_ai_woo_settings', $defaults );
        } else {
            // Merge new defaults into existing settings (migration)
            $existing = get_option( 'geo_ai_woo_settings', array() );
            $merged   = array_merge( $defaults, $existing );
            update_option( 'geo_ai_woo_settings', $merged );
        }

        // Create crawl tracking database table
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-crawl-tracker.php';
        Geo_Ai_Woo_Crawl_Tracker::create_table();

        // Generate static files
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-llms-generator.php';
        Geo_Ai_Woo_LLMS_Generator::instance()->write_static_files();

        // Keep rewrite rules as fallback
        add_rewrite_rule( '^llms\.txt$', 'index.php?geo_ai_woo_llms=1', 'top' );
        add_rewrite_rule( '^llms-full\.txt$', 'index.php?geo_ai_woo_llms=full', 'top' );
        flush_rewrite_rules();

        // Schedule cache regeneration
        if ( ! wp_next_scheduled( 'geo_ai_woo_regenerate_llms' ) ) {
            wp_schedule_event( time(), 'daily', 'geo_ai_woo_regenerate_llms' );
        }

        // Set activation notice flag
        set_transient( 'geo_ai_woo_activation_notice', '1', 60 );
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook( 'geo_ai_woo_regenerate_llms' );

        // Delete static files
        $files = array( ABSPATH . 'llms.txt', ABSPATH . 'llms-full.txt' );
        foreach ( $files as $file ) {
            if ( file_exists( $file ) ) {
                wp_delete_file( $file );
            }
        }

        // Delete multilingual files
        if ( class_exists( 'Geo_Ai_Woo_Multilingual' ) ) {
            Geo_Ai_Woo_Multilingual::instance()->delete_all_files();
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 *
 * @return Geo_Ai_Woo
 */
function geo_ai_woo() {
    return Geo_Ai_Woo::instance();
}

// Start the plugin
geo_ai_woo();
