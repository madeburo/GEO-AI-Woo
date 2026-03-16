<?php
/**
 * SEO Headers — Meta Tags, HTTP Headers, JSON-LD
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for SEO headers and structured data
 */
class Geo_Ai_Woo_SEO_Headers {

    /**
     * Single instance
     *
     * @var Geo_Ai_Woo_SEO_Headers
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return Geo_Ai_Woo_SEO_Headers
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
        // Meta tags in <head>
        add_action( 'wp_head', array( $this, 'output_meta_tags' ), 1 );

        // JSON-LD structured data
        add_action( 'wp_head', array( $this, 'output_jsonld' ), 2 );

        // HTTP Link header
        add_action( 'template_redirect', array( $this, 'send_link_header' ), 1 );
    }

    /**
     * Output meta tags for AI discoverability
     */
    public function output_meta_tags() {
        $settings = get_option( 'geo_ai_woo_settings', array() );
        $enabled  = isset( $settings['seo_meta_enabled'] ) ? $settings['seo_meta_enabled'] : '1';

        if ( '1' !== $enabled ) {
            return;
        }

        $llms_url      = home_url( '/llms.txt' );
        $llms_full_url = home_url( '/llms-full.txt' );

        echo '<meta name="llms" content="' . esc_url( $llms_url ) . '" />' . "\n";
        echo '<meta name="llms-full" content="' . esc_url( $llms_full_url ) . '" />' . "\n";

        // Multilingual hreflang alternate links
        if ( class_exists( 'Geo_Ai_Woo_Multilingual' ) ) {
            $multilingual = Geo_Ai_Woo_Multilingual::instance();

            if ( $multilingual->is_active() ) {
                $languages = $multilingual->get_active_languages();

                foreach ( $languages as $lang ) {
                    $lang_file = $multilingual->get_llms_filename( $lang['code'], false );
                    $lang_url  = home_url( '/' . $lang_file );
                    echo '<link rel="alternate" hreflang="' . esc_attr( $lang['code'] ) . '" href="' . esc_url( $lang_url ) . '" type="text/plain" />' . "\n";
                }
            }
        }

        // Per-post AI description meta tag
        if ( is_singular() ) {
            $post_id        = get_queried_object_id();
            $ai_description = get_post_meta( $post_id, '_geo_ai_woo_description', true );
            if ( $ai_description ) {
                echo '<meta name="ai-description" content="' . esc_attr( wp_strip_all_tags( $ai_description ) ) . '" />' . "\n";
            }

            // AI keywords meta tag
            $ai_keywords = get_post_meta( $post_id, '_geo_ai_woo_keywords', true );
            if ( $ai_keywords ) {
                echo '<meta name="ai-keywords" content="' . esc_attr( wp_strip_all_tags( $ai_keywords ) ) . '" />' . "\n";
            }
        }
    }

    /**
     * Output JSON-LD structured data
     */
    public function output_jsonld() {
        $settings = get_option( 'geo_ai_woo_settings', array() );
        $enabled  = isset( $settings['seo_jsonld_enabled'] ) ? $settings['seo_jsonld_enabled'] : '1';

        if ( '1' !== $enabled ) {
            return;
        }

        // Skip if major SEO plugins handle schema themselves
        if ( $this->has_seo_plugin_schema() ) {
            return;
        }

        $llms_url = esc_url( home_url( '/llms.txt' ) );

        if ( is_front_page() || is_home() ) {
            // WebSite schema on front page
            $schema = array(
                '@context'        => 'https://schema.org',
                '@type'           => 'WebSite',
                'name'            => get_bloginfo( 'name' ),
                'url'             => esc_url( home_url( '/' ) ),
                'description'     => get_bloginfo( 'description' ),
                'potentialAction' => array(
                    '@type'  => 'ReadAction',
                    'target' => $llms_url,
                    'name'   => 'AI Content Index',
                ),
            );

            $this->print_jsonld( $schema );

        } elseif ( is_singular() ) {
            // Article/Product schema on single pages
            $post_id        = get_queried_object_id();
            $ai_description = get_post_meta( $post_id, '_geo_ai_woo_description', true );

            // Only add if we have AI-specific data
            if ( $ai_description ) {
                $post_type = get_post_type( $post_id );
                $schema    = array(
                    '@context'    => 'https://schema.org',
                    '@type'       => ( 'product' === $post_type && class_exists( 'WooCommerce' ) ) ? 'Product' : 'Article',
                    'name'        => get_the_title( $post_id ),
                    'url'         => esc_url( get_permalink( $post_id ) ),
                    'description' => wp_strip_all_tags( $ai_description ),
                );

                // Add keywords if available
                $ai_keywords = get_post_meta( $post_id, '_geo_ai_woo_keywords', true );
                if ( $ai_keywords ) {
                    $schema['keywords'] = $ai_keywords;
                }

                // Add date info
                $schema['datePublished'] = get_the_date( 'c', $post_id );
                $schema['dateModified']  = get_the_modified_date( 'c', $post_id );

                $this->print_jsonld( $schema );
            }
        }
    }

    /**
     * Send HTTP Link header pointing to llms.txt
     */
    public function send_link_header() {
        $settings = get_option( 'geo_ai_woo_settings', array() );
        $enabled  = isset( $settings['seo_link_header'] ) ? $settings['seo_link_header'] : '1';

        if ( '1' !== $enabled ) {
            return;
        }

        // Don't send on admin pages or AJAX requests
        if ( is_admin() || wp_doing_ajax() ) {
            return;
        }

        $llms_file = 'llms.txt';

        // Use language-specific file if multilingual is active
        if ( class_exists( 'Geo_Ai_Woo_Multilingual' ) ) {
            $multilingual = Geo_Ai_Woo_Multilingual::instance();
            if ( $multilingual->is_active() ) {
                $current_lang = $multilingual->get_current_language();
                if ( $current_lang ) {
                    $llms_file = $multilingual->get_llms_filename( $current_lang, false );
                }
            }
        }

        $llms_url = home_url( '/' . $llms_file );

        if ( ! headers_sent() ) {
            header( 'Link: <' . esc_url( $llms_url ) . '>; rel="ai-content-index"; type="text/plain"', false );
        }
    }

    /**
     * Check if a major SEO plugin is handling schema
     *
     * @return bool
     */
    private function has_seo_plugin_schema() {
        // Yoast SEO
        if ( defined( 'WPSEO_VERSION' ) ) {
            return true;
        }

        // Rank Math
        if ( class_exists( 'RankMath' ) ) {
            return true;
        }

        // All in One SEO
        if ( defined( 'AIOSEO_VERSION' ) ) {
            return true;
        }

        // SEOPress
        if ( defined( 'SEOPRESS_VERSION' ) ) {
            return true;
        }

        /**
         * Filter to indicate a custom SEO plugin handles schema
         *
         * @param bool $has_schema Whether an SEO plugin handles schema.
         */
        return apply_filters( 'geo_ai_woo_has_seo_plugin_schema', false );
    }

    /**
     * Print JSON-LD script tag
     *
     * @param array $schema Schema data.
     */
    private function print_jsonld( $schema ) {
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $schema );
        echo "\n" . '</script>' . "\n";
    }
}
