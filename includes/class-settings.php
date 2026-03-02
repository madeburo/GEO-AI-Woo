<?php
/**
 * Settings Page
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for plugin settings page
 */
class Geo_Ai_Woo_Settings {

    /**
     * Single instance
     *
     * @var Geo_Ai_Woo_Settings
     */
    private static $instance = null;

    /**
     * Option name
     *
     * @var string
     */
    private $option_name = 'geo_ai_woo_settings';

    /**
     * Get single instance
     *
     * @return Geo_Ai_Woo_Settings
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
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Add settings page to menu
     */
    public function add_menu_page() {
        add_options_page(
            __( 'GEO AI Woo Settings', 'geo-ai-woo' ),
            __( 'GEO AI Woo', 'geo-ai-woo' ),
            'manage_options',
            'geo-ai-woo',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'geo_ai_woo_settings_group',
            $this->option_name,
            array( $this, 'sanitize_settings' )
        );

        // General section
        add_settings_section(
            'geo_ai_woo_general',
            __( 'General Settings', 'geo-ai-woo' ),
            array( $this, 'render_general_section' ),
            'geo-ai-woo'
        );

        // Site description
        add_settings_field(
            'site_description',
            __( 'Site Description', 'geo-ai-woo' ),
            array( $this, 'render_site_description_field' ),
            'geo-ai-woo',
            'geo_ai_woo_general'
        );

        // Post types
        add_settings_field(
            'post_types',
            __( 'Content Types', 'geo-ai-woo' ),
            array( $this, 'render_post_types_field' ),
            'geo-ai-woo',
            'geo_ai_woo_general'
        );

        // Include taxonomies
        add_settings_field(
            'include_taxonomies',
            __( 'Include Taxonomies', 'geo-ai-woo' ),
            array( $this, 'render_include_taxonomies_field' ),
            'geo-ai-woo',
            'geo_ai_woo_general'
        );

        // Hide out-of-stock (only if WC active)
        if ( class_exists( 'WooCommerce' ) ) {
            add_settings_field(
                'hide_out_of_stock',
                __( 'Out-of-Stock Products', 'geo-ai-woo' ),
                array( $this, 'render_hide_out_of_stock_field' ),
                'geo-ai-woo',
                'geo_ai_woo_general'
            );
        }

        // Bot rules section
        add_settings_section(
            'geo_ai_woo_bots',
            __( 'AI Bot Rules', 'geo-ai-woo' ),
            array( $this, 'render_bots_section' ),
            'geo-ai-woo'
        );

        // Bot rules field
        add_settings_field(
            'bot_rules',
            __( 'Crawler Permissions', 'geo-ai-woo' ),
            array( $this, 'render_bot_rules_field' ),
            'geo-ai-woo',
            'geo_ai_woo_bots'
        );

        // robots.txt integration
        add_settings_field(
            'robots_txt_enabled',
            __( 'robots.txt Integration', 'geo-ai-woo' ),
            array( $this, 'render_robots_txt_field' ),
            'geo-ai-woo',
            'geo_ai_woo_bots'
        );

        // SEO section
        add_settings_section(
            'geo_ai_woo_seo',
            __( 'SEO & AI Visibility', 'geo-ai-woo' ),
            array( $this, 'render_seo_section' ),
            'geo-ai-woo'
        );

        // SEO meta tags
        add_settings_field(
            'seo_meta_enabled',
            __( 'Meta Tags', 'geo-ai-woo' ),
            array( $this, 'render_seo_meta_field' ),
            'geo-ai-woo',
            'geo_ai_woo_seo'
        );

        // HTTP Link header
        add_settings_field(
            'seo_link_header',
            __( 'HTTP Link Header', 'geo-ai-woo' ),
            array( $this, 'render_seo_link_header_field' ),
            'geo-ai-woo',
            'geo_ai_woo_seo'
        );

        // JSON-LD schema
        add_settings_field(
            'seo_jsonld_enabled',
            __( 'JSON-LD Schema', 'geo-ai-woo' ),
            array( $this, 'render_seo_jsonld_field' ),
            'geo-ai-woo',
            'geo_ai_woo_seo'
        );

        // Cache section
        add_settings_section(
            'geo_ai_woo_cache',
            __( 'Cache Settings', 'geo-ai-woo' ),
            array( $this, 'render_cache_section' ),
            'geo-ai-woo'
        );

        // Cache duration
        add_settings_field(
            'cache_duration',
            __( 'Regeneration Frequency', 'geo-ai-woo' ),
            array( $this, 'render_cache_duration_field' ),
            'geo-ai-woo',
            'geo_ai_woo_cache'
        );
    }

    /**
     * Sanitize settings
     *
     * @param array $input Input values.
     * @return array Sanitized values.
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        // Site description
        if ( isset( $input['site_description'] ) ) {
            $sanitized['site_description'] = sanitize_textarea_field( $input['site_description'] );
        }

        // Post types
        if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
            $sanitized['post_types'] = array_map( 'sanitize_key', $input['post_types'] );
        } else {
            $sanitized['post_types'] = array( 'post', 'page' );
        }

        // Bot rules — only accept known bots, preserve original name casing
        if ( isset( $input['bot_rules'] ) && is_array( $input['bot_rules'] ) ) {
            $sanitized['bot_rules'] = array();
            $valid_bots = array_keys( Geo_Ai_Woo_LLMS_Generator::instance()->get_ai_bots() );
            foreach ( $input['bot_rules'] as $bot => $rule ) {
                if ( ! in_array( $bot, $valid_bots, true ) ) {
                    continue;
                }
                $sanitized['bot_rules'][ $bot ] =
                    in_array( $rule, array( 'allow', 'disallow' ), true ) ? $rule : 'allow';
            }
        }

        // Cache duration
        if ( isset( $input['cache_duration'] ) ) {
            $sanitized['cache_duration'] = sanitize_key( $input['cache_duration'] );
        }

        // Include taxonomies
        $sanitized['include_taxonomies'] = isset( $input['include_taxonomies'] ) ? '1' : '0';

        // Hide out-of-stock
        if ( isset( $input['hide_out_of_stock'] ) ) {
            $valid_values = array( 'wc_default', 'yes', 'no' );
            $sanitized['hide_out_of_stock'] = in_array( $input['hide_out_of_stock'], $valid_values, true )
                ? $input['hide_out_of_stock']
                : 'wc_default';
        }

        // SEO settings (checkboxes)
        $sanitized['seo_meta_enabled']   = isset( $input['seo_meta_enabled'] ) ? '1' : '0';
        $sanitized['seo_link_header']    = isset( $input['seo_link_header'] ) ? '1' : '0';
        $sanitized['seo_jsonld_enabled'] = isset( $input['seo_jsonld_enabled'] ) ? '1' : '0';
        $sanitized['robots_txt_enabled'] = isset( $input['robots_txt_enabled'] ) ? '1' : '0';

        // Clear cache and regenerate static files when settings are saved
        Geo_Ai_Woo_LLMS_Generator::instance()->regenerate_cache();

        return $sanitized;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $llms_url      = home_url( '/llms.txt' );
        $llms_full_url = home_url( '/llms-full.txt' );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <div class="geo-ai-woo-status">
                <h3><?php esc_html_e( 'Your llms.txt Files', 'geo-ai-woo' ); ?></h3>
                <p>
                    <strong><?php esc_html_e( 'Standard:', 'geo-ai-woo' ); ?></strong>
                    <a href="<?php echo esc_url( $llms_url ); ?>" target="_blank">
                        <?php echo esc_html( $llms_url ); ?>
                    </a>
                    <?php if ( file_exists( ABSPATH . 'llms.txt' ) ) : ?>
                        <span class="geo-ai-woo-file-status active">&#10003; <?php esc_html_e( 'Static file active', 'geo-ai-woo' ); ?></span>
                    <?php else : ?>
                        <span class="geo-ai-woo-file-status inactive"><?php esc_html_e( 'Dynamic (rewrite rules)', 'geo-ai-woo' ); ?></span>
                    <?php endif; ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Full version:', 'geo-ai-woo' ); ?></strong>
                    <a href="<?php echo esc_url( $llms_full_url ); ?>" target="_blank">
                        <?php echo esc_html( $llms_full_url ); ?>
                    </a>
                </p>
                <p>
                    <button type="button" class="button" id="geo-ai-woo-regenerate">
                        <?php esc_html_e( 'Regenerate Now', 'geo-ai-woo' ); ?>
                    </button>
                    <span id="geo-ai-woo-regenerate-status"></span>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'geo_ai_woo_settings_group' );
                do_settings_sections( 'geo-ai-woo' );
                submit_button();
                ?>
            </form>

            <div class="geo-ai-woo-preview-section">
                <h2><?php esc_html_e( 'llms.txt Preview', 'geo-ai-woo' ); ?></h2>
                <p>
                    <button type="button" class="button" id="geo-ai-woo-load-preview">
                        <?php esc_html_e( 'Load Preview', 'geo-ai-woo' ); ?>
                    </button>
                </p>
                <pre id="geo-ai-woo-preview-content" class="geo-ai-woo-preview-box"><?php esc_html_e( 'Click "Load Preview" to see your llms.txt content.', 'geo-ai-woo' ); ?></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Render general section
     */
    public function render_general_section() {
        echo '<p>' . esc_html__( 'Configure how your content appears in llms.txt for AI search engines.', 'geo-ai-woo' ) . '</p>';
    }

    /**
     * Render site description field
     */
    public function render_site_description_field() {
        $settings    = get_option( $this->option_name, array() );
        $description = isset( $settings['site_description'] )
            ? $settings['site_description']
            : get_bloginfo( 'description' );
        ?>
        <textarea
            name="<?php echo esc_attr( $this->option_name ); ?>[site_description]"
            rows="3"
            class="large-text"
        ><?php echo esc_textarea( $description ); ?></textarea>
        <p class="description">
            <?php esc_html_e( 'Brief description of your site for AI systems.', 'geo-ai-woo' ); ?>
        </p>
        <?php
    }

    /**
     * Render post types field
     */
    public function render_post_types_field() {
        $settings   = get_option( $this->option_name, array() );
        $selected   = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post', 'page' );
        $post_types = get_post_types( array( 'public' => true ), 'objects' );

        foreach ( $post_types as $post_type ) {
            // Skip attachments
            if ( 'attachment' === $post_type->name ) {
                continue;
            }
            ?>
            <label style="display: block; margin-bottom: 5px;">
                <input
                    type="checkbox"
                    name="<?php echo esc_attr( $this->option_name ); ?>[post_types][]"
                    value="<?php echo esc_attr( $post_type->name ); ?>"
                    <?php checked( in_array( $post_type->name, $selected, true ) ); ?>
                />
                <?php echo esc_html( $post_type->label ); ?>
            </label>
            <?php
        }
    }

    /**
     * Render include taxonomies field
     */
    public function render_include_taxonomies_field() {
        $settings = get_option( $this->option_name, array() );
        $enabled  = isset( $settings['include_taxonomies'] ) ? $settings['include_taxonomies'] : '1';
        ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr( $this->option_name ); ?>[include_taxonomies]"
                value="1"
                <?php checked( $enabled, '1' ); ?>
            />
            <?php esc_html_e( 'Include categories, tags, and product taxonomies in llms.txt', 'geo-ai-woo' ); ?>
        </label>
        <?php
    }

    /**
     * Render hide out-of-stock products field
     */
    public function render_hide_out_of_stock_field() {
        $settings = get_option( $this->option_name, array() );
        $value    = isset( $settings['hide_out_of_stock'] ) ? $settings['hide_out_of_stock'] : 'wc_default';
        ?>
        <select name="<?php echo esc_attr( $this->option_name ); ?>[hide_out_of_stock]">
            <option value="wc_default" <?php selected( $value, 'wc_default' ); ?>>
                <?php esc_html_e( 'Use WooCommerce setting', 'geo-ai-woo' ); ?>
            </option>
            <option value="yes" <?php selected( $value, 'yes' ); ?>>
                <?php esc_html_e( 'Always hide out-of-stock products', 'geo-ai-woo' ); ?>
            </option>
            <option value="no" <?php selected( $value, 'no' ); ?>>
                <?php esc_html_e( 'Always show all products', 'geo-ai-woo' ); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e( 'Control whether out-of-stock products appear in llms.txt.', 'geo-ai-woo' ); ?>
        </p>
        <?php
    }

    /**
     * Render bots section
     */
    public function render_bots_section() {
        echo '<p>' . esc_html__( 'Control which AI crawlers can access your content.', 'geo-ai-woo' ) . '</p>';
    }

    /**
     * Render bot rules field
     */
    public function render_bot_rules_field() {
        $settings  = get_option( $this->option_name, array() );
        $bot_rules = isset( $settings['bot_rules'] ) ? $settings['bot_rules'] : array();
        $ai_bots   = Geo_Ai_Woo_LLMS_Generator::instance()->get_ai_bots();
        ?>
        <table class="widefat geo-ai-woo-bot-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Bot', 'geo-ai-woo' ); ?></th>
                    <th><?php esc_html_e( 'Provider', 'geo-ai-woo' ); ?></th>
                    <th><?php esc_html_e( 'Permission', 'geo-ai-woo' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $ai_bots as $bot => $provider ) :
                    $rule = isset( $bot_rules[ $bot ] ) ? $bot_rules[ $bot ] : 'allow';
                ?>
                <tr>
                    <td><code><?php echo esc_html( $bot ); ?></code></td>
                    <td><?php echo esc_html( $provider ); ?></td>
                    <td>
                        <select name="<?php echo esc_attr( $this->option_name ); ?>[bot_rules][<?php echo esc_attr( $bot ); ?>]">
                            <option value="allow" <?php selected( $rule, 'allow' ); ?>>
                                <?php esc_html_e( 'Allow', 'geo-ai-woo' ); ?>
                            </option>
                            <option value="disallow" <?php selected( $rule, 'disallow' ); ?>>
                                <?php esc_html_e( 'Disallow', 'geo-ai-woo' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render robots.txt integration field
     */
    public function render_robots_txt_field() {
        $settings = get_option( $this->option_name, array() );
        $enabled  = isset( $settings['robots_txt_enabled'] ) ? $settings['robots_txt_enabled'] : '1';
        ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr( $this->option_name ); ?>[robots_txt_enabled]"
                value="1"
                <?php checked( $enabled, '1' ); ?>
            />
            <?php esc_html_e( 'Add AI bot rules to robots.txt automatically', 'geo-ai-woo' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( 'Adds User-agent/Allow/Disallow directives for each AI crawler based on the permissions above.', 'geo-ai-woo' ); ?>
        </p>
        <?php
    }

    /**
     * Render SEO section description
     */
    public function render_seo_section() {
        echo '<p>' . esc_html__( 'Configure how your site communicates with AI systems through meta tags, HTTP headers, and structured data.', 'geo-ai-woo' ) . '</p>';
    }

    /**
     * Render SEO meta tags field
     */
    public function render_seo_meta_field() {
        $settings = get_option( $this->option_name, array() );
        $enabled  = isset( $settings['seo_meta_enabled'] ) ? $settings['seo_meta_enabled'] : '1';
        ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr( $this->option_name ); ?>[seo_meta_enabled]"
                value="1"
                <?php checked( $enabled, '1' ); ?>
            />
            <?php esc_html_e( 'Add AI meta tags to page head', 'geo-ai-woo' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( 'Outputs <meta name="llms"> and <meta name="ai-description"> tags.', 'geo-ai-woo' ); ?>
        </p>
        <?php
    }

    /**
     * Render HTTP Link header field
     */
    public function render_seo_link_header_field() {
        $settings = get_option( $this->option_name, array() );
        $enabled  = isset( $settings['seo_link_header'] ) ? $settings['seo_link_header'] : '1';
        ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr( $this->option_name ); ?>[seo_link_header]"
                value="1"
                <?php checked( $enabled, '1' ); ?>
            />
            <?php esc_html_e( 'Send HTTP Link header pointing to llms.txt', 'geo-ai-woo' ); ?>
        </label>
        <p class="description">
            <?php
            /* translators: %s: example HTTP header */
            printf(
                esc_html__( 'Adds %s to HTTP response headers.', 'geo-ai-woo' ),
                '<code>Link: &lt;.../llms.txt&gt;; rel="ai-content-index"</code>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render JSON-LD schema field
     */
    public function render_seo_jsonld_field() {
        $settings = get_option( $this->option_name, array() );
        $enabled  = isset( $settings['seo_jsonld_enabled'] ) ? $settings['seo_jsonld_enabled'] : '1';
        ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr( $this->option_name ); ?>[seo_jsonld_enabled]"
                value="1"
                <?php checked( $enabled, '1' ); ?>
            />
            <?php esc_html_e( 'Add JSON-LD structured data for AI', 'geo-ai-woo' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( 'Adds Schema.org WebSite/Article markup with AI descriptions. Skipped if Yoast, Rank Math, or other SEO plugins are detected.', 'geo-ai-woo' ); ?>
        </p>
        <?php
    }

    /**
     * Render cache section
     */
    public function render_cache_section() {
        echo '<p>' . esc_html__( 'Control how often llms.txt is regenerated.', 'geo-ai-woo' ) . '</p>';
    }

    /**
     * Render cache duration field
     */
    public function render_cache_duration_field() {
        $settings = get_option( $this->option_name, array() );
        $duration = isset( $settings['cache_duration'] ) ? $settings['cache_duration'] : 'daily';
        ?>
        <select name="<?php echo esc_attr( $this->option_name ); ?>[cache_duration]">
            <option value="immediate" <?php selected( $duration, 'immediate' ); ?>>
                <?php esc_html_e( 'On every post update', 'geo-ai-woo' ); ?>
            </option>
            <option value="hourly" <?php selected( $duration, 'hourly' ); ?>>
                <?php esc_html_e( 'Hourly', 'geo-ai-woo' ); ?>
            </option>
            <option value="daily" <?php selected( $duration, 'daily' ); ?>>
                <?php esc_html_e( 'Daily', 'geo-ai-woo' ); ?>
            </option>
            <option value="weekly" <?php selected( $duration, 'weekly' ); ?>>
                <?php esc_html_e( 'Weekly', 'geo-ai-woo' ); ?>
            </option>
        </select>
        <?php
    }
}

// AJAX handler for regeneration
add_action( 'wp_ajax_geo_ai_woo_regenerate', function() {
    check_ajax_referer( 'geo_ai_woo_regenerate', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error();
    }

    Geo_Ai_Woo_LLMS_Generator::instance()->regenerate_cache();
    wp_send_json_success();
} );

// AJAX handler for live preview
add_action( 'wp_ajax_geo_ai_woo_preview', function() {
    check_ajax_referer( 'geo_ai_woo_preview', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error();
    }

    $content = Geo_Ai_Woo_LLMS_Generator::instance()->generate( false );
    wp_send_json_success( array( 'content' => $content ) );
} );
