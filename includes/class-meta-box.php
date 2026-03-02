<?php
/**
 * AI Meta Box
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for AI meta box in post editor
 */
class Geo_Ai_Woo_Meta_Box {

    /**
     * Single instance
     *
     * @var Geo_Ai_Woo_Meta_Box
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return Geo_Ai_Woo_Meta_Box
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
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 2 );
    }

    /**
     * Add meta box to post types
     */
    public function add_meta_box() {
        $settings   = get_option( 'geo_ai_woo_settings', array() );
        $post_types = isset( $settings['post_types'] )
            ? $settings['post_types']
            : array( 'post', 'page' );

        foreach ( $post_types as $post_type ) {
            // Skip products — WooCommerce integration has its own product data panel
            if ( 'product' === $post_type && class_exists( 'WooCommerce' ) ) {
                continue;
            }

            add_meta_box(
                'geo_ai_woo_meta_box',
                __( 'GEO AI Woo', 'geo-ai-woo' ),
                array( $this, 'render_meta_box' ),
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render meta box content
     *
     * @param WP_Post $post Post object.
     */
    public function render_meta_box( $post ) {
        // Nonce field
        wp_nonce_field( 'geo_ai_woo_meta_box', 'geo_ai_woo_meta_box_nonce' );

        // Get saved values
        $description = get_post_meta( $post->ID, '_geo_ai_woo_description', true );
        $keywords    = get_post_meta( $post->ID, '_geo_ai_woo_keywords', true );
        $exclude     = get_post_meta( $post->ID, '_geo_ai_woo_exclude', true );

        ?>
        <div class="geo-ai-woo-meta-box">
            <p>
                <label for="geo_ai_woo_description">
                    <strong><?php esc_html_e( 'AI Description', 'geo-ai-woo' ); ?></strong>
                </label>
                <textarea
                    id="geo_ai_woo_description"
                    name="geo_ai_woo_description"
                    rows="3"
                    style="width: 100%;"
                    placeholder="<?php esc_attr_e( 'Brief summary for AI systems...', 'geo-ai-woo' ); ?>"
                ><?php echo esc_textarea( $description ); ?></textarea>
                <?php if ( class_exists( 'Geo_Ai_Woo_AI_Generator' ) && Geo_Ai_Woo_AI_Generator::instance()->is_configured() ) : ?>
                <button type="button" class="button button-small geo-ai-woo-generate-btn" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
                    <?php esc_html_e( 'Generate with AI', 'geo-ai-woo' ); ?>
                </button>
                <span class="geo-ai-woo-generate-status"></span>
                <?php endif; ?>
                <span class="description">
                    <?php esc_html_e( 'Concise description for LLMs (max 200 characters)', 'geo-ai-woo' ); ?>
                </span>
            </p>

            <p>
                <label for="geo_ai_woo_keywords">
                    <strong><?php esc_html_e( 'AI Keywords', 'geo-ai-woo' ); ?></strong>
                </label>
                <input 
                    type="text" 
                    id="geo_ai_woo_keywords" 
                    name="geo_ai_woo_keywords" 
                    value="<?php echo esc_attr( $keywords ); ?>" 
                    style="width: 100%;"
                    placeholder="<?php esc_attr_e( 'keyword1, keyword2, keyword3', 'geo-ai-woo' ); ?>"
                />
                <span class="description">
                    <?php esc_html_e( 'Comma-separated topics for context', 'geo-ai-woo' ); ?>
                </span>
            </p>

            <p>
                <label>
                    <input 
                        type="checkbox" 
                        name="geo_ai_woo_exclude" 
                        value="1" 
                        <?php checked( $exclude, '1' ); ?>
                    />
                    <?php esc_html_e( 'Exclude from AI indexing', 'geo-ai-woo' ); ?>
                </label>
                <br />
                <span class="description">
                    <?php esc_html_e( 'This content will not appear in llms.txt', 'geo-ai-woo' ); ?>
                </span>
            </p>
        </div>
        <?php
    }

    /**
     * Save meta box data
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function save_meta_box( $post_id, $post ) {
        // Check nonce
        if ( ! isset( $_POST['geo_ai_woo_meta_box_nonce'] ) ||
             ! wp_verify_nonce( wp_unslash( $_POST['geo_ai_woo_meta_box_nonce'] ), 'geo_ai_woo_meta_box' ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save description
        if ( isset( $_POST['geo_ai_woo_description'] ) ) {
            $description = sanitize_textarea_field( wp_unslash( $_POST['geo_ai_woo_description'] ) );
            // Limit to 200 characters
            $description = mb_substr( $description, 0, 200 );
            update_post_meta( $post_id, '_geo_ai_woo_description', $description );
        }

        // Save keywords
        if ( isset( $_POST['geo_ai_woo_keywords'] ) ) {
            $keywords = sanitize_text_field( wp_unslash( $_POST['geo_ai_woo_keywords'] ) );
            update_post_meta( $post_id, '_geo_ai_woo_keywords', $keywords );
        }

        // Save exclude flag
        $exclude = isset( $_POST['geo_ai_woo_exclude'] ) ? '1' : '0';
        update_post_meta( $post_id, '_geo_ai_woo_exclude', $exclude );
    }
}
