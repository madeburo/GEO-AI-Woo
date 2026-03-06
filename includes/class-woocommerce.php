<?php
/**
 * WooCommerce Integration
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for WooCommerce integration
 */
class Geo_Ai_Woo_WooCommerce {

    /**
     * Single instance
     *
     * @var Geo_Ai_Woo_WooCommerce
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return Geo_Ai_Woo_WooCommerce
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
        // Add AI description to product data tabs
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_data_tab' ) );
        add_action( 'woocommerce_product_data_panels', array( $this, 'add_product_data_panel' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ) );

        // Enhanced product schema
        add_filter( 'woocommerce_structured_data_product', array( $this, 'enhance_product_schema' ), 10, 2 );

        // Add product attributes to llms.txt
        add_filter( 'geo_ai_woo_product_description', array( $this, 'build_product_description' ), 10, 2 );
    }

    /**
     * Add product data tab
     *
     * @param array $tabs Existing tabs.
     * @return array
     */
    public function add_product_data_tab( $tabs ) {
        $tabs['geo_ai_woo'] = array(
            'label'    => __( 'GEO AI', 'geo-ai-woo' ),
            'target'   => 'geo_ai_woo_product_data',
            'class'    => array(),
            'priority' => 80,
        );
        return $tabs;
    }

    /**
     * Add product data panel
     */
    public function add_product_data_panel() {
        global $post;

        $description = get_post_meta( $post->ID, '_geo_ai_woo_description', true );
        $keywords    = get_post_meta( $post->ID, '_geo_ai_woo_keywords', true );
        $exclude     = get_post_meta( $post->ID, '_geo_ai_woo_exclude', true );
        $auto_desc   = get_post_meta( $post->ID, '_geo_ai_woo_auto_description', true );
        ?>
        <div id="geo_ai_woo_product_data" class="panel woocommerce_options_panel">
            <?php wp_nonce_field( 'geo_ai_woo_product_data', 'geo_ai_woo_product_data_nonce' ); ?>
            <div class="options_group">
                <h4 style="padding-left: 12px;">
                    <?php esc_html_e( 'AI Optimization Settings', 'geo-ai-woo' ); ?>
                </h4>

                <?php
                // Auto-generate description checkbox
                woocommerce_wp_checkbox( array(
                    'id'          => '_geo_ai_woo_auto_description',
                    'label'       => __( 'Auto-generate AI Description', 'geo-ai-woo' ),
                    'description' => __( 'Generate description from product data', 'geo-ai-woo' ),
                    'value'       => '' !== $auto_desc ? $auto_desc : 'yes',
                ) );

                // AI Description
                woocommerce_wp_textarea_input( array(
                    'id'          => '_geo_ai_woo_description',
                    'label'       => __( 'AI Description', 'geo-ai-woo' ),
                    'placeholder' => __( 'Custom description for AI systems...', 'geo-ai-woo' ),
                    'description' => __( 'Leave empty to auto-generate from product data.', 'geo-ai-woo' ),
                    'value'       => $description,
                ) );

                if ( class_exists( 'Geo_Ai_Woo_AI_Generator' ) && Geo_Ai_Woo_AI_Generator::instance()->is_configured() ) :
                ?>
                <p class="form-field" style="padding-left: 12px;">
                    <button type="button" class="button button-small geo-ai-woo-generate-btn" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
                        <?php esc_html_e( 'Generate with AI', 'geo-ai-woo' ); ?>
                    </button>
                    <span class="geo-ai-woo-generate-status"></span>
                </p>
                <?php
                endif;

                // AI Keywords
                woocommerce_wp_text_input( array(
                    'id'          => '_geo_ai_woo_keywords',
                    'label'       => __( 'AI Keywords', 'geo-ai-woo' ),
                    'placeholder' => __( 'keyword1, keyword2, keyword3', 'geo-ai-woo' ),
                    'description' => __( 'Comma-separated keywords for AI context.', 'geo-ai-woo' ),
                    'value'       => $keywords,
                ) );

                // Exclude checkbox — stored as '1'/'0', WC checkbox expects 'yes'/''
                woocommerce_wp_checkbox( array(
                    'id'          => '_geo_ai_woo_exclude',
                    'label'       => __( 'Exclude from AI', 'geo-ai-woo' ),
                    'description' => __( 'Do not include this product in llms.txt', 'geo-ai-woo' ),
                    'value'       => '1' === $exclude ? 'yes' : '',
                ) );
                ?>
            </div>

            <div class="options_group">
                <h4 style="padding-left: 12px;">
                    <?php esc_html_e( 'Preview', 'geo-ai-woo' ); ?>
                </h4>
                <p style="padding: 0 12px;">
                    <em><?php esc_html_e( 'This is how your product will appear in llms.txt:', 'geo-ai-woo' ); ?></em>
                </p>
                <pre class="geo-ai-woo-preview"><?php echo esc_html( $this->get_product_preview( $post->ID ) ); ?></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Save product data
     *
     * @param int $post_id Product ID.
     */
    public function save_product_data( $post_id ) {
        // Verify nonce
        if ( ! isset( $_POST['geo_ai_woo_product_data_nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['geo_ai_woo_product_data_nonce'] ) ), 'geo_ai_woo_product_data' ) ) {
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

        // Description
        if ( isset( $_POST['_geo_ai_woo_description'] ) ) {
            $description = sanitize_textarea_field( wp_unslash( $_POST['_geo_ai_woo_description'] ) );
            update_post_meta( $post_id, '_geo_ai_woo_description', mb_substr( $description, 0, 200 ) );
        }

        // Keywords
        if ( isset( $_POST['_geo_ai_woo_keywords'] ) ) {
            update_post_meta( $post_id, '_geo_ai_woo_keywords', sanitize_text_field( wp_unslash( $_POST['_geo_ai_woo_keywords'] ) ) );
        }

        // WooCommerce checkboxes use 'yes' when checked, absent when unchecked
        // Store as '1'/'0' for consistency with the meta_query in LLMS generator
        $exclude = isset( $_POST['_geo_ai_woo_exclude'] ) ? '1' : '0';
        update_post_meta( $post_id, '_geo_ai_woo_exclude', $exclude );

        $auto_desc = isset( $_POST['_geo_ai_woo_auto_description'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_geo_ai_woo_auto_description', $auto_desc );
    }

    /**
     * Enhance WooCommerce product schema for AI
     *
     * @param array      $markup  Existing schema markup.
     * @param WC_Product $product Product object.
     * @return array
     */
    public function enhance_product_schema( $markup, $product ) {
        $product_id = $product->get_id();

        // Add AI description as alternate description
        $ai_description = get_post_meta( $product_id, '_geo_ai_woo_description', true );
        if ( $ai_description ) {
            $markup['description'] = $ai_description;
        }

        // Add keywords as additional property
        $keywords = get_post_meta( $product_id, '_geo_ai_woo_keywords', true );
        if ( $keywords ) {
            $markup['keywords'] = $keywords;
        }

        // Add product attributes for better AI understanding
        $attributes = $product->get_attributes();
        if ( ! empty( $attributes ) ) {
            $markup['additionalProperty'] = array();
            foreach ( $attributes as $attribute ) {
                if ( $attribute->is_taxonomy() ) {
                    $terms = wp_get_post_terms( $product_id, $attribute->get_name(), array( 'fields' => 'names' ) );
                    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                        $markup['additionalProperty'][] = array(
                            '@type' => 'PropertyValue',
                            'name'  => wc_attribute_label( $attribute->get_name() ),
                            'value' => implode( ', ', $terms ),
                        );
                    }
                }
            }
        }

        // Add aggregate rating data
        $review_count = $product->get_review_count();
        if ( $review_count > 0 ) {
            $markup['aggregateRating'] = array(
                '@type'       => 'AggregateRating',
                'ratingValue' => $product->get_average_rating(),
                'reviewCount' => $review_count,
            );
        }

        return $markup;
    }

    /**
     * Build product description for llms.txt
     *
     * @param string $description Current description.
     * @param int    $product_id  Product ID.
     * @return string
     */
    public function build_product_description( $description, $product_id ) {
        // Check if custom description exists
        $custom = get_post_meta( $product_id, '_geo_ai_woo_description', true );
        if ( ! empty( $custom ) ) {
            return $custom;
        }

        // Check if auto-generate is enabled
        $auto = get_post_meta( $product_id, '_geo_ai_woo_auto_description', true );
        if ( 'no' === $auto ) {
            return $description;
        }

        // Auto-generate from product data
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return $description;
        }

        $parts = array();

        // Price — handle variable products with price ranges
        if ( $product->is_type( 'variable' ) ) {
            $min_price = $product->get_variation_price( 'min' );
            $max_price = $product->get_variation_price( 'max' );
            if ( $min_price && $max_price ) {
                if ( $min_price === $max_price ) {
                    $parts[] = sprintf(
                        /* translators: %s: product price */
                        __( 'Price: %s', 'geo-ai-woo' ),
                        wp_strip_all_tags( wc_price( $min_price ) )
                    );
                } else {
                    $parts[] = sprintf(
                        /* translators: %1$s: min price, %2$s: max price */
                        __( 'Price: %1$s – %2$s', 'geo-ai-woo' ),
                        wp_strip_all_tags( wc_price( $min_price ) ),
                        wp_strip_all_tags( wc_price( $max_price ) )
                    );
                }
            }
        } elseif ( $product->is_on_sale() && $product->get_regular_price() ) {
            // Sale price display
            $parts[] = sprintf(
                /* translators: %1$s: sale price, %2$s: regular price */
                __( 'Price: %1$s (was %2$s)', 'geo-ai-woo' ),
                wp_strip_all_tags( wc_price( $product->get_sale_price() ) ),
                wp_strip_all_tags( wc_price( $product->get_regular_price() ) )
            );
        } elseif ( $product->get_price() ) {
            $parts[] = sprintf(
                /* translators: %s: product price */
                __( 'Price: %s', 'geo-ai-woo' ),
                wp_strip_all_tags( wc_price( $product->get_price() ) )
            );
        }

        // Stock status
        if ( $product->is_in_stock() ) {
            $parts[] = __( 'In Stock', 'geo-ai-woo' );
        } else {
            $parts[] = __( 'Out of Stock', 'geo-ai-woo' );
        }

        // Reviews and rating
        $review_count = $product->get_review_count();
        if ( $review_count > 0 ) {
            $avg_rating = $product->get_average_rating();
            $parts[] = sprintf(
                /* translators: %1$s: average rating, %2$d: review count */
                __( 'Rating: %1$s/5 (%2$d reviews)', 'geo-ai-woo' ),
                number_format( (float) $avg_rating, 1 ),
                $review_count
            );
        }

        // Variable product attributes (available variations)
        if ( $product->is_type( 'variable' ) ) {
            $variation_attributes = $product->get_variation_attributes();
            if ( ! empty( $variation_attributes ) ) {
                foreach ( $variation_attributes as $attr_name => $attr_values ) {
                    $attr_label = wc_attribute_label( $attr_name, $product );
                    $values     = array_filter( $attr_values );
                    if ( ! empty( $values ) ) {
                        // Decode taxonomy term slugs to names
                        $decoded_values = array();
                        foreach ( $values as $value ) {
                            $term = get_term_by( 'slug', $value, $attr_name );
                            $decoded_values[] = $term ? $term->name : $value;
                        }
                        $parts[] = sprintf(
                            /* translators: %1$s: attribute label, %2$s: attribute values */
                            __( '%1$s: %2$s', 'geo-ai-woo' ),
                            $attr_label,
                            implode( ', ', $decoded_values )
                        );
                    }
                }
            }
        }

        // Categories
        $categories = wc_get_product_category_list( $product_id, ', ' );
        if ( $categories ) {
            $parts[] = wp_strip_all_tags( $categories );
        }

        // Short description fallback
        $short_desc = $product->get_short_description();
        if ( $short_desc ) {
            $parts[] = wp_trim_words( Geo_Ai_Woo_Content_Sanitizer::sanitize( $short_desc ), 15, '...' );
        }

        return implode( ' | ', $parts );
    }

    /**
     * Get product preview for admin
     *
     * @param int $product_id Product ID.
     * @return string
     */
    private function get_product_preview( $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return __( 'Product not found', 'geo-ai-woo' );
        }

        $title = $product->get_name();
        $url   = get_permalink( $product_id );
        $desc  = apply_filters( 'geo_ai_woo_product_description', '', $product_id );

        if ( empty( $desc ) ) {
            $desc = $this->build_product_description( '', $product_id );
        }

        return sprintf( '- [%s](%s): %s', $title, $url, $desc );
    }
}
