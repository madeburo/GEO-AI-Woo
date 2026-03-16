<?php
/**
 * Bulk Edit — List Table Columns & Quick Edit
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for bulk edit and list table integration
 */
class Geo_Ai_Woo_Bulk_Edit {

    /**
     * Single instance
     *
     * @var Geo_Ai_Woo_Bulk_Edit
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return Geo_Ai_Woo_Bulk_Edit
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
        $this->init_hooks();
    }

    /**
     * Initialize hooks for all tracked post types
     */
    private function init_hooks() {
        $settings   = get_option( 'geo_ai_woo_settings', array() );
        $post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post', 'page' );

        foreach ( $post_types as $post_type ) {
            // Column headers
            add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_column' ) );

            // Column content
            add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_column' ), 10, 2 );
        }

        // Quick Edit fields
        add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_fields' ), 10, 2 );

        // Save Quick Edit
        add_action( 'save_post', array( $this, 'save_quick_edit' ), 10, 2 );

        // Inline JS to populate Quick Edit fields
        add_action( 'admin_footer-edit.php', array( $this, 'quick_edit_js' ) );
    }

    /**
     * Add AI Status column to list table
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function add_column( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;

            // Insert after title column
            if ( 'title' === $key ) {
                $new_columns['geo_ai_woo'] = __( 'AI Status', 'geo-ai-for-woocommerce' );
            }
        }

        return $new_columns;
    }

    /**
     * Render AI Status column content
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function render_column( $column, $post_id ) {
        if ( 'geo_ai_woo' !== $column ) {
            return;
        }

        $exclude     = get_post_meta( $post_id, '_geo_ai_woo_exclude', true );
        $description = get_post_meta( $post_id, '_geo_ai_woo_description', true );
        $keywords    = get_post_meta( $post_id, '_geo_ai_woo_keywords', true );

        // Status icon
        if ( '1' === $exclude ) {
            echo '<span class="geo-ai-for-woocommerce-status-icon excluded" title="' . esc_attr__( 'Excluded from AI indexing', 'geo-ai-for-woocommerce' ) . '">&#10005;</span> ';
            echo '<span class="geo-ai-for-woocommerce-column-label">' . esc_html__( 'Excluded', 'geo-ai-for-woocommerce' ) . '</span>';
        } else {
            echo '<span class="geo-ai-for-woocommerce-status-icon included" title="' . esc_attr__( 'Included in AI indexing', 'geo-ai-for-woocommerce' ) . '">&#10003;</span> ';

            if ( $description ) {
                echo '<span class="geo-ai-for-woocommerce-column-desc" title="' . esc_attr( $description ) . '">';
                echo esc_html( mb_strimwidth( $description, 0, 50, '...' ) );
                echo '</span>';
            } else {
                echo '<span class="geo-ai-for-woocommerce-column-label">' . esc_html__( 'Auto', 'geo-ai-for-woocommerce' ) . '</span>';
            }
        }

        // Hidden data for Quick Edit population
        echo '<div class="hidden geo-ai-for-woocommerce-inline-data"';
        echo ' data-description="' . esc_attr( $description ) . '"';
        echo ' data-keywords="' . esc_attr( $keywords ) . '"';
        echo ' data-exclude="' . esc_attr( $exclude ) . '"';
        echo '></div>';
    }

    /**
     * Add Quick Edit fields
     *
     * @param string $column_name Column name.
     * @param string $post_type   Post type.
     */
    public function quick_edit_fields( $column_name, $post_type ) {
        if ( 'geo_ai_woo' !== $column_name ) {
            return;
        }

        $settings   = get_option( 'geo_ai_woo_settings', array() );
        $post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post', 'page' );

        if ( ! in_array( $post_type, $post_types, true ) ) {
            return;
        }

        wp_nonce_field( 'geo_ai_woo_quick_edit', 'geo_ai_woo_quick_edit_nonce' );
        ?>
        <fieldset class="inline-edit-col-right geo-ai-for-woocommerce-quick-edit">
            <div class="inline-edit-col">
                <span class="title"><?php esc_html_e( 'GEO AI Search Optimization', 'geo-ai-for-woocommerce' ); ?></span>

                <label class="inline-edit-group">
                    <span class="title"><?php esc_html_e( 'AI Description', 'geo-ai-for-woocommerce' ); ?></span>
                    <span class="input-text-wrap">
                        <textarea name="geo_ai_woo_description" rows="2" class="widefat"></textarea>
                    </span>
                </label>

                <label class="inline-edit-group">
                    <span class="title"><?php esc_html_e( 'AI Keywords', 'geo-ai-for-woocommerce' ); ?></span>
                    <span class="input-text-wrap">
                        <input type="text" name="geo_ai_woo_keywords" class="widefat" />
                    </span>
                </label>

                <label class="inline-edit-group">
                    <input type="checkbox" name="geo_ai_woo_exclude" value="1" />
                    <span class="checkbox-title"><?php esc_html_e( 'Exclude from AI indexing', 'geo-ai-for-woocommerce' ); ?></span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Save Quick Edit data
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function save_quick_edit( $post_id, $post ) {
        // Check nonce — Quick Edit nonce
        if ( ! isset( $_POST['geo_ai_woo_quick_edit_nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['geo_ai_woo_quick_edit_nonce'] ) ), 'geo_ai_woo_quick_edit' ) ) {
            return;
        }

        // Skip autosaves
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check capabilities
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Only process tracked post types
        $settings   = get_option( 'geo_ai_woo_settings', array() );
        $post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post', 'page' );

        if ( ! in_array( $post->post_type, $post_types, true ) ) {
            return;
        }

        // Save description
        if ( isset( $_POST['geo_ai_woo_description'] ) ) {
            $description = sanitize_textarea_field( wp_unslash( $_POST['geo_ai_woo_description'] ) );
            update_post_meta( $post_id, '_geo_ai_woo_description', mb_substr( $description, 0, 200 ) );
        }

        // Save keywords
        if ( isset( $_POST['geo_ai_woo_keywords'] ) ) {
            update_post_meta( $post_id, '_geo_ai_woo_keywords', sanitize_text_field( wp_unslash( $_POST['geo_ai_woo_keywords'] ) ) );
        }

        // Save exclude flag
        $exclude = isset( $_POST['geo_ai_woo_exclude'] ) ? '1' : '0';
        update_post_meta( $post_id, '_geo_ai_woo_exclude', $exclude );
    }

    /**
     * Output JavaScript to populate Quick Edit fields from column data
     */
    public function quick_edit_js() {
        $screen     = get_current_screen();
        $settings   = get_option( 'geo_ai_woo_settings', array() );
        $post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post', 'page' );

        if ( ! $screen || ! in_array( $screen->post_type, $post_types, true ) ) {
            return;
        }
        ?>
        <script type="text/javascript">
        (function($) {
            var origInlineEdit = inlineEditPost.edit;

            inlineEditPost.edit = function(id) {
                origInlineEdit.apply(this, arguments);

                var postId = 0;
                if (typeof id === 'object') {
                    postId = parseInt(this.getId(id));
                }

                if (postId > 0) {
                    var $row = $('#post-' + postId);
                    var $data = $row.find('.geo-ai-for-woocommerce-inline-data');

                    if ($data.length) {
                        var $editRow = $('#edit-' + postId);

                        $editRow.find('textarea[name="geo_ai_woo_description"]').val($data.data('description') || '');
                        $editRow.find('input[name="geo_ai_woo_keywords"]').val($data.data('keywords') || '');
                        $editRow.find('input[name="geo_ai_woo_exclude"]').prop('checked', $data.data('exclude') === '1' || $data.data('exclude') === 1);
                    }
                }
            };
        })(jQuery);
        </script>
        <?php
    }
}
