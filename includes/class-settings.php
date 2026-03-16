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
            __( 'GEO AI Search Optimization Settings', 'geo-ai-for-woocommerce' ),
            __( 'GEO AI Search Optimization', 'geo-ai-for-woocommerce' ),
            'manage_options',
            'geo-ai-for-woocommerce',
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
            __( 'General Settings', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_general_section' ),
            'geo-ai-for-woocommerce'
        );

        // Site description
        add_settings_field(
            'site_description',
            __( 'Site Description', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_site_description_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_general'
        );

        // Post types
        add_settings_field(
            'post_types',
            __( 'Content Types', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_post_types_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_general'
        );

        // Include taxonomies
        add_settings_field(
            'include_taxonomies',
            __( 'Include Taxonomies', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_include_taxonomies_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_general'
        );

        // Hide out-of-stock (only if WC active)
        if ( class_exists( 'WooCommerce' ) ) {
            add_settings_field(
                'hide_out_of_stock',
                __( 'Out-of-Stock Products', 'geo-ai-for-woocommerce' ),
                array( $this, 'render_hide_out_of_stock_field' ),
                'geo-ai-for-woocommerce',
                'geo_ai_woo_general'
            );
        }

        // Bot rules section
        add_settings_section(
            'geo_ai_woo_bots',
            __( 'AI Bot Rules', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_bots_section' ),
            'geo-ai-for-woocommerce'
        );

        // Bot rules field
        add_settings_field(
            'bot_rules',
            __( 'Crawler Permissions', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_bot_rules_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_bots'
        );

        // robots.txt integration
        add_settings_field(
            'robots_txt_enabled',
            __( 'robots.txt Integration', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_robots_txt_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_bots'
        );

        // SEO section
        add_settings_section(
            'geo_ai_woo_seo',
            __( 'SEO & AI Visibility', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_seo_section' ),
            'geo-ai-for-woocommerce'
        );

        // SEO meta tags
        add_settings_field(
            'seo_meta_enabled',
            __( 'Meta Tags', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_seo_meta_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_seo'
        );

        // HTTP Link header
        add_settings_field(
            'seo_link_header',
            __( 'HTTP Link Header', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_seo_link_header_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_seo'
        );

        // JSON-LD schema
        add_settings_field(
            'seo_jsonld_enabled',
            __( 'JSON-LD Schema', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_seo_jsonld_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_seo'
        );

        // Cache section
        add_settings_section(
            'geo_ai_woo_cache',
            __( 'Cache Settings', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_cache_section' ),
            'geo-ai-for-woocommerce'
        );

        // Cache duration
        add_settings_field(
            'cache_duration',
            __( 'Regeneration Frequency', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_cache_duration_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_cache'
        );

        // AI Generation section
        add_settings_section(
            'geo_ai_woo_ai',
            __( 'AI Description Generation', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_ai_section' ),
            'geo-ai-for-woocommerce'
        );

        add_settings_field(
            'ai_provider',
            __( 'AI Provider', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_ai_provider_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_ai'
        );

        add_settings_field(
            'ai_api_key',
            __( 'API Key', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_ai_api_key_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_ai'
        );

        add_settings_field(
            'ai_model',
            __( 'Model', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_ai_model_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_ai'
        );

        add_settings_field(
            'ai_max_tokens',
            __( 'Max Tokens', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_ai_max_tokens_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_ai'
        );

        add_settings_field(
            'ai_prompt_template',
            __( 'Prompt Template', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_ai_prompt_template_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_ai'
        );

        add_settings_field(
            'ai_bulk_generate',
            __( 'Bulk Generate', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_ai_bulk_generate_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_ai'
        );

        // Advanced section
        add_settings_section(
            'geo_ai_woo_advanced',
            __( 'Advanced Settings', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_advanced_section' ),
            'geo-ai-for-woocommerce'
        );

        add_settings_field(
            'multilingual_enabled',
            __( 'Multilingual Support', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_multilingual_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_advanced'
        );

        add_settings_field(
            'crawl_tracking_enabled',
            __( 'Crawl Tracking', 'geo-ai-for-woocommerce' ),
            array( $this, 'render_crawl_tracking_field' ),
            'geo-ai-for-woocommerce',
            'geo_ai_woo_advanced'
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

        // AI Generation settings
        if ( isset( $input['ai_provider'] ) ) {
            $sanitized['ai_provider'] = in_array( $input['ai_provider'], array( 'none', 'claude', 'openai' ), true )
                ? $input['ai_provider']
                : 'none';
        }

        // API key — encrypt for storage, preserve existing if masked value submitted
        if ( isset( $input['ai_api_key'] ) ) {
            $key = sanitize_text_field( $input['ai_api_key'] );
            if ( '' === $key || '****' === $key ) {
                // Preserve existing key
                $existing = get_option( $this->option_name, array() );
                $sanitized['ai_api_key'] = isset( $existing['ai_api_key'] ) ? $existing['ai_api_key'] : '';
            } else {
                $sanitized['ai_api_key'] = Geo_Ai_Woo_AI_Generator::encrypt_api_key( $key );
            }
        }

        if ( isset( $input['ai_model'] ) ) {
            $sanitized['ai_model'] = sanitize_text_field( $input['ai_model'] );
        }

        if ( isset( $input['ai_max_tokens'] ) ) {
            $sanitized['ai_max_tokens'] = min( 500, max( 50, absint( $input['ai_max_tokens'] ) ) );
        }

        if ( isset( $input['ai_prompt_template'] ) ) {
            $sanitized['ai_prompt_template'] = sanitize_textarea_field( $input['ai_prompt_template'] );
        }

        // Advanced settings
        $sanitized['multilingual_enabled']     = isset( $input['multilingual_enabled'] ) ? '1' : '0';
        $sanitized['crawl_tracking_enabled']   = isset( $input['crawl_tracking_enabled'] ) ? '1' : '0';

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

            <div class="geo-ai-for-woocommerce-status">
                <h3><?php esc_html_e( 'Your llms.txt Files', 'geo-ai-for-woocommerce' ); ?></h3>
                <p>
                    <strong><?php esc_html_e( 'Standard:', 'geo-ai-for-woocommerce' ); ?></strong>
                    <a href="<?php echo esc_url( $llms_url ); ?>" target="_blank">
                        <?php echo esc_html( $llms_url ); ?>
                    </a>
                    <?php if ( file_exists( ABSPATH . 'llms.txt' ) ) : ?>
                        <span class="geo-ai-for-woocommerce-file-status active">&#10003; <?php esc_html_e( 'Static file active', 'geo-ai-for-woocommerce' ); ?></span>
                    <?php else : ?>
                        <span class="geo-ai-for-woocommerce-file-status inactive"><?php esc_html_e( 'Dynamic (rewrite rules)', 'geo-ai-for-woocommerce' ); ?></span>
                    <?php endif; ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Full version:', 'geo-ai-for-woocommerce' ); ?></strong>
                    <a href="<?php echo esc_url( $llms_full_url ); ?>" target="_blank">
                        <?php echo esc_html( $llms_full_url ); ?>
                    </a>
                </p>
                <p>
                    <button type="button" class="button" id="geo-ai-for-woocommerce-regenerate">
                        <?php esc_html_e( 'Regenerate Now', 'geo-ai-for-woocommerce' ); ?>
                    </button>
                    <span id="geo-ai-for-woocommerce-regenerate-status"></span>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'geo_ai_woo_settings_group' );
                do_settings_sections( 'geo-ai-for-woocommerce' );
                submit_button();
                ?>
            </form>

            <div class="geo-ai-for-woocommerce-preview-section">
                <h2><?php esc_html_e( 'llms.txt Preview', 'geo-ai-for-woocommerce' ); ?></h2>
                <p>
                    <button type="button" class="button" id="geo-ai-for-woocommerce-load-preview">
                        <?php esc_html_e( 'Load Preview', 'geo-ai-for-woocommerce' ); ?>
                    </button>
                </p>
                <pre id="geo-ai-for-woocommerce-preview-content" class="geo-ai-for-woocommerce-preview-box"><?php esc_html_e( 'Click "Load Preview" to see your llms.txt content.', 'geo-ai-for-woocommerce' ); ?></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Render general section
     */
    public function render_general_section() {
        echo '<p>' . esc_html__( 'Configure how your content appears in llms.txt for AI search engines.', 'geo-ai-for-woocommerce' ) . '</p>';
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
            <?php esc_html_e( 'Brief description of your site for AI systems.', 'geo-ai-for-woocommerce' ); ?>
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
            <?php esc_html_e( 'Include categories, tags, and product taxonomies in llms.txt', 'geo-ai-for-woocommerce' ); ?>
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
                <?php esc_html_e( 'Use WooCommerce setting', 'geo-ai-for-woocommerce' ); ?>
            </option>
            <option value="yes" <?php selected( $value, 'yes' ); ?>>
                <?php esc_html_e( 'Always hide out-of-stock products', 'geo-ai-for-woocommerce' ); ?>
            </option>
            <option value="no" <?php selected( $value, 'no' ); ?>>
                <?php esc_html_e( 'Always show all products', 'geo-ai-for-woocommerce' ); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e( 'Control whether out-of-stock products appear in llms.txt.', 'geo-ai-for-woocommerce' ); ?>
        </p>
        <?php
    }

    /**
     * Render bots section
     */
    public function render_bots_section() {
        echo '<p>' . esc_html__( 'Control which AI crawlers can access your content.', 'geo-ai-for-woocommerce' ) . '</p>';
    }

    /**
     * Render bot rules field
     */
    public function render_bot_rules_field() {
        $settings  = get_option( $this->option_name, array() );
        $bot_rules = isset( $settings['bot_rules'] ) ? $settings['bot_rules'] : array();
        $ai_bots   = Geo_Ai_Woo_LLMS_Generator::instance()->get_ai_bots();
        ?>
        <table class="widefat geo-ai-for-woocommerce-bot-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Bot', 'geo-ai-for-woocommerce' ); ?></th>
                    <th><?php esc_html_e( 'Provider', 'geo-ai-for-woocommerce' ); ?></th>
                    <th><?php esc_html_e( 'Permission', 'geo-ai-for-woocommerce' ); ?></th>
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
                                <?php esc_html_e( 'Allow', 'geo-ai-for-woocommerce' ); ?>
                            </option>
                            <option value="disallow" <?php selected( $rule, 'disallow' ); ?>>
                                <?php esc_html_e( 'Disallow', 'geo-ai-for-woocommerce' ); ?>
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
            <?php esc_html_e( 'Add AI bot rules to robots.txt automatically', 'geo-ai-for-woocommerce' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( 'Adds User-agent/Allow/Disallow directives for each AI crawler based on the permissions above.', 'geo-ai-for-woocommerce' ); ?>
        </p>
        <?php
    }

    /**
     * Render SEO section description
     */
    public function render_seo_section() {
        echo '<p>' . esc_html__( 'Configure how your site communicates with AI systems through meta tags, HTTP headers, and structured data.', 'geo-ai-for-woocommerce' ) . '</p>';
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
            <?php esc_html_e( 'Add AI meta tags to page head', 'geo-ai-for-woocommerce' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( 'Outputs <meta name="llms"> and <meta name="ai-description"> tags.', 'geo-ai-for-woocommerce' ); ?>
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
            <?php esc_html_e( 'Send HTTP Link header pointing to llms.txt', 'geo-ai-for-woocommerce' ); ?>
        </label>
        <p class="description">
            <?php
            printf(
                /* translators: %s: example HTTP header */
                esc_html__( 'Adds %s to HTTP response headers.', 'geo-ai-for-woocommerce' ),
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
            <?php esc_html_e( 'Add JSON-LD structured data for AI', 'geo-ai-for-woocommerce' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( 'Adds Schema.org WebSite/Article markup with AI descriptions. Skipped if Yoast, Rank Math, or other SEO plugins are detected.', 'geo-ai-for-woocommerce' ); ?>
        </p>
        <?php
    }

    /**
     * Render cache section
     */
    public function render_cache_section() {
        echo '<p>' . esc_html__( 'Control how often llms.txt is regenerated.', 'geo-ai-for-woocommerce' ) . '</p>';
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
                <?php esc_html_e( 'On every post update', 'geo-ai-for-woocommerce' ); ?>
            </option>
            <option value="hourly" <?php selected( $duration, 'hourly' ); ?>>
                <?php esc_html_e( 'Hourly', 'geo-ai-for-woocommerce' ); ?>
            </option>
            <option value="daily" <?php selected( $duration, 'daily' ); ?>>
                <?php esc_html_e( 'Daily', 'geo-ai-for-woocommerce' ); ?>
            </option>
            <option value="weekly" <?php selected( $duration, 'weekly' ); ?>>
                <?php esc_html_e( 'Weekly', 'geo-ai-for-woocommerce' ); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Render AI section description
     */
    public function render_ai_section() {
        echo '<p>' . esc_html__( 'Use Claude or OpenAI to automatically generate AI descriptions for your posts and products.', 'geo-ai-for-woocommerce' ) . '</p>';
        echo '<div class="notice notice-info inline" style="margin:10px 0;padding:8px 12px;"><p>';
        echo wp_kses(
            __( 'When enabled, this feature sends your post titles and content excerpts to your chosen AI provider to generate descriptions. Review their privacy policies: <a href="https://www.anthropic.com/privacy" target="_blank" rel="noopener">Anthropic</a> | <a href="https://openai.com/privacy" target="_blank" rel="noopener">OpenAI</a>.', 'geo-ai-for-woocommerce' ),
            array(
                'a' => array(
                    'href'   => array(),
                    'target' => array(),
                    'rel'    => array(),
                ),
            )
        );
        echo '</p></div>';
    }

    /**
     * Render AI provider field
     */
    public function render_ai_provider_field() {
        $settings = get_option( $this->option_name, array() );
        $provider = isset( $settings['ai_provider'] ) ? $settings['ai_provider'] : 'none';
        ?>
        <select name="<?php echo esc_attr( $this->option_name ); ?>[ai_provider]">
            <option value="none" <?php selected( $provider, 'none' ); ?>>
                <?php esc_html_e( 'Disabled', 'geo-ai-for-woocommerce' ); ?>
            </option>
            <option value="claude" <?php selected( $provider, 'claude' ); ?>>
                <?php esc_html_e( 'Claude (Anthropic)', 'geo-ai-for-woocommerce' ); ?>
            </option>
            <option value="openai" <?php selected( $provider, 'openai' ); ?>>
                <?php esc_html_e( 'OpenAI', 'geo-ai-for-woocommerce' ); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Render AI API key field
     */
    public function render_ai_api_key_field() {
        $settings = get_option( $this->option_name, array() );
        $has_key  = ! empty( $settings['ai_api_key'] );
        ?>
        <input
            type="password"
            name="<?php echo esc_attr( $this->option_name ); ?>[ai_api_key]"
            value="<?php echo esc_attr( $has_key ? '****' : '' ); ?>"
            class="regular-text"
            autocomplete="off"
        />
        <p class="description">
            <?php if ( $has_key ) : ?>
                <?php esc_html_e( 'API key is saved. Enter a new key to replace it, or leave as-is.', 'geo-ai-for-woocommerce' ); ?>
            <?php else : ?>
                <?php esc_html_e( 'Enter your API key. It will be stored encrypted.', 'geo-ai-for-woocommerce' ); ?>
            <?php endif; ?>
        </p>
        <?php
    }

    /**
     * Render AI model field
     */
    public function render_ai_model_field() {
        $settings = get_option( $this->option_name, array() );
        $model    = isset( $settings['ai_model'] ) ? $settings['ai_model'] : '';
        $provider = isset( $settings['ai_provider'] ) ? $settings['ai_provider'] : 'none';
        $placeholder = 'claude' === $provider ? 'claude-sonnet-4-5-20250514' : 'gpt-5';
        ?>
        <input
            type="text"
            name="<?php echo esc_attr( $this->option_name ); ?>[ai_model]"
            value="<?php echo esc_attr( $model ); ?>"
            class="regular-text"
            placeholder="<?php echo esc_attr( $placeholder ); ?>"
        />
        <p class="description">
            <?php esc_html_e( 'Leave empty to use the default model for your provider.', 'geo-ai-for-woocommerce' ); ?>
        </p>
        <?php
    }

    /**
     * Render AI max tokens field
     */
    public function render_ai_max_tokens_field() {
        $settings   = get_option( $this->option_name, array() );
        $max_tokens = isset( $settings['ai_max_tokens'] ) ? $settings['ai_max_tokens'] : 150;
        ?>
        <input
            type="number"
            name="<?php echo esc_attr( $this->option_name ); ?>[ai_max_tokens]"
            value="<?php echo esc_attr( $max_tokens ); ?>"
            min="50"
            max="500"
            step="10"
            class="small-text"
        />
        <p class="description">
            <?php esc_html_e( 'Maximum tokens for generated descriptions (50-500).', 'geo-ai-for-woocommerce' ); ?>
        </p>
        <?php
    }

    /**
     * Render AI prompt template field
     */
    public function render_ai_prompt_template_field() {
        $settings = get_option( $this->option_name, array() );
        $template = isset( $settings['ai_prompt_template'] ) && ! empty( $settings['ai_prompt_template'] )
            ? $settings['ai_prompt_template']
            : Geo_Ai_Woo_AI_Generator::DEFAULT_PROMPT;
        ?>
        <textarea
            name="<?php echo esc_attr( $this->option_name ); ?>[ai_prompt_template]"
            rows="5"
            class="large-text"
        ><?php echo esc_textarea( $template ); ?></textarea>
        <p class="description">
            <?php
            printf(
                /* translators: %s: available placeholders */
                esc_html__( 'Available placeholders: %s', 'geo-ai-for-woocommerce' ),
                '<code>{title}</code>, <code>{content}</code>, <code>{type}</code>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render AI bulk generate field
     */
    public function render_ai_bulk_generate_field() {
        $is_configured = class_exists( 'Geo_Ai_Woo_AI_Generator' ) && Geo_Ai_Woo_AI_Generator::instance()->is_configured();
        ?>
        <button type="button" class="button" id="geo-ai-for-woocommerce-bulk-generate" <?php disabled( ! $is_configured ); ?>>
            <?php esc_html_e( 'Generate AI Descriptions for All Posts', 'geo-ai-for-woocommerce' ); ?>
        </button>
        <div id="geo-ai-for-woocommerce-bulk-progress" style="display: none; margin-top: 10px;">
            <div class="geo-ai-for-woocommerce-progress-bar">
                <div class="geo-ai-for-woocommerce-progress-fill"></div>
            </div>
            <span class="geo-ai-for-woocommerce-progress-text"></span>
        </div>
        <p class="description">
            <?php esc_html_e( 'Generate descriptions for all published posts that do not have one yet. Processes up to 50 posts.', 'geo-ai-for-woocommerce' ); ?>
        </p>
        <?php
    }

    /**
     * Render advanced section description
     */
    public function render_advanced_section() {
        echo '<p>' . esc_html__( 'Additional features for multilingual sites and bot tracking.', 'geo-ai-for-woocommerce' ) . '</p>';
    }

    /**
     * Render multilingual support field
     */
    public function render_multilingual_field() {
        $settings = get_option( $this->option_name, array() );
        $enabled  = isset( $settings['multilingual_enabled'] ) ? $settings['multilingual_enabled'] : '1';

        $has_plugin = defined( 'ICL_SITEPRESS_VERSION' )
            || function_exists( 'pll_languages_list' )
            || class_exists( 'TRP_Translate_Press' );
        ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr( $this->option_name ); ?>[multilingual_enabled]"
                value="1"
                <?php checked( $enabled, '1' ); ?>
                <?php disabled( ! $has_plugin ); ?>
            />
            <?php esc_html_e( 'Generate separate llms.txt files for each language', 'geo-ai-for-woocommerce' ); ?>
        </label>
        <?php if ( ! $has_plugin ) : ?>
            <p class="description">
                <?php esc_html_e( 'No multilingual plugin detected. Install WPML, Polylang, or TranslatePress to enable.', 'geo-ai-for-woocommerce' ); ?>
            </p>
        <?php else : ?>
            <p class="description">
                <?php
                if ( class_exists( 'Geo_Ai_Woo_Multilingual' ) ) {
                    $multilingual = Geo_Ai_Woo_Multilingual::instance();
                    printf(
                        /* translators: %s: multilingual provider name */
                        esc_html__( 'Detected: %s', 'geo-ai-for-woocommerce' ),
                        '<strong>' . esc_html( ucfirst( $multilingual->get_provider() ) ) . '</strong>'
                    );
                }
                ?>
            </p>
        <?php endif; ?>
        <?php
    }

    /**
     * Render crawl tracking field
     */
    public function render_crawl_tracking_field() {
        $settings = get_option( $this->option_name, array() );
        $enabled  = isset( $settings['crawl_tracking_enabled'] ) ? $settings['crawl_tracking_enabled'] : '1';
        ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr( $this->option_name ); ?>[crawl_tracking_enabled]"
                value="1"
                <?php checked( $enabled, '1' ); ?>
            />
            <?php esc_html_e( 'Track AI bot visits to llms.txt files', 'geo-ai-for-woocommerce' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( 'Logs bot visits for the dashboard widget. IP addresses are anonymized (GDPR-compliant).', 'geo-ai-for-woocommerce' ); ?>
        </p>
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
