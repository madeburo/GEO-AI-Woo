<?php
/**
 * LLMS.txt Generator
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for generating llms.txt file
 */
class Geo_Ai_Woo_LLMS_Generator {

    /**
     * Single instance
     *
     * @var Geo_Ai_Woo_LLMS_Generator
     */
    private static $instance = null;

    /**
     * Supported AI bots
     *
     * @var array
     */
    private $ai_bots = array(
        'GPTBot'               => 'OpenAI / ChatGPT',
        'OAI-SearchBot'        => 'OpenAI / Copilot Search',
        'ClaudeBot'            => 'Anthropic / Claude',
        'Google-Extended'      => 'Google / Gemini',
        'PerplexityBot'        => 'Perplexity AI',
        'DeepSeekBot'          => 'DeepSeek',
        'GrokBot'              => 'xAI / Grok',
        'meta-externalagent'   => 'Meta / LLaMA',
        'PanguBot'             => 'Alibaba / Qwen',
        'YandexBot'            => 'Yandex / YandexGPT',
        'SputnikBot'           => 'Sber / GigaChat',
        'Bytespider'           => 'ByteDance / Douyin',
        'Baiduspider'          => 'Baidu / ERNIE',
        'claude-web'           => 'Anthropic / Claude Web',
        'Amazonbot'            => 'Amazon / Alexa',
        'Applebot'             => 'Apple / Siri & Spotlight',
    );

    /**
     * Get single instance
     *
     * @return Geo_Ai_Woo_LLMS_Generator
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
     * Initialize hooks
     */
    private function init_hooks() {
        // Register rewrite rules (fallback for servers without static file access)
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );

        // Handle llms.txt request (fallback)
        add_action( 'template_redirect', array( $this, 'serve_llms_txt' ) );

        // Regenerate on post save
        add_action( 'save_post', array( $this, 'maybe_regenerate' ), 20, 2 );

        // Scheduled regeneration
        add_action( 'geo_ai_woo_regenerate_llms', array( $this, 'regenerate_cache' ) );

        // Query vars
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

        // robots.txt integration
        add_filter( 'robots_txt', array( $this, 'add_robots_txt_rules' ), 10, 2 );
    }

    /**
     * Add rewrite rules for llms.txt (fallback)
     */
    public function add_rewrite_rules() {
        add_rewrite_rule( '^llms\.txt$', 'index.php?geo_ai_woo_llms=1', 'top' );
        add_rewrite_rule( '^llms-full\.txt$', 'index.php?geo_ai_woo_llms=full', 'top' );
    }

    /**
     * Add query vars
     *
     * @param array $vars Query vars.
     * @return array
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'geo_ai_woo_llms';
        return $vars;
    }

    /**
     * Serve llms.txt file (fallback when static files are not accessible)
     */
    public function serve_llms_txt() {
        $llms_type = get_query_var( 'geo_ai_woo_llms' );

        if ( ! $llms_type ) {
            return;
        }

        $is_full = ( 'full' === $llms_type );

        // Set headers
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'X-Robots-Tag: noindex' );
        header( 'Cache-Control: public, max-age=86400' );

        // Get cached content or generate new
        $cache_key = $is_full ? 'geo_ai_woo_llms_full' : 'geo_ai_woo_llms';
        $duration  = $this->get_cache_duration();

        $content = ( $duration > 0 ) ? get_transient( $cache_key ) : false;

        if ( false === $content ) {
            $content = $this->generate( $is_full );
            if ( $duration > 0 ) {
                set_transient( $cache_key, $content, $duration );
            }
        }

        // Log bot visit if crawl tracker is available
        if ( class_exists( 'Geo_Ai_Woo_Crawl_Tracker' ) ) {
            Geo_Ai_Woo_Crawl_Tracker::instance()->log_visit( $is_full ? 'full' : 'standard' );
        }

        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain text output, all content sanitized in generate().
        exit;
    }

    /**
     * Generate llms.txt content
     *
     * @param bool        $is_full   Whether to generate full version.
     * @param string|null $lang_code Language code for multilingual generation.
     * @return string
     */
    public function generate( $is_full = false, $lang_code = null ) {
        $settings = get_option( 'geo_ai_woo_settings', array() );
        $output   = array();

        // Header
        $site_name = get_bloginfo( 'name' );
        $output[]  = '# ' . $site_name;
        $output[]  = '';

        // Site description
        $description = ! empty( $settings['site_description'] )
            ? $settings['site_description']
            : get_bloginfo( 'description' );

        if ( $description ) {
            $output[] = '> ' . wp_strip_all_tags( $description );
            $output[] = '';
        }

        // Site URL
        $output[] = '- URL: ' . esc_url( home_url( '/' ) );
        $output[] = '- llms.txt: ' . esc_url( home_url( '/llms.txt' ) );
        $output[] = '- llms-full.txt: ' . esc_url( home_url( '/llms-full.txt' ) );

        // Language indicator for multilingual files
        if ( $lang_code ) {
            $multilingual = Geo_Ai_Woo_Multilingual::instance();
            $languages    = $multilingual->get_active_languages();
            $lang_name    = $lang_code;

            foreach ( $languages as $lang ) {
                if ( $lang['code'] === $lang_code ) {
                    $lang_name = $lang['name'];
                    break;
                }
            }

            $output[] = '- Language: ' . $lang_name;
        }

        $output[] = '';

        // Bot rules section
        $output[] = '## AI Crawler Rules';
        $output[] = '';

        $bot_rules = isset( $settings['bot_rules'] ) ? $settings['bot_rules'] : array();
        foreach ( $this->ai_bots as $bot => $provider ) {
            $rule     = isset( $bot_rules[ $bot ] ) ? $bot_rules[ $bot ] : 'allow';
            $status   = ( 'allow' === $rule ) ? 'Allowed' : 'Blocked';
            $output[] = "- {$bot} ({$provider}): {$status}";
        }
        $output[] = '';

        // Get post types to include
        $post_types = isset( $settings['post_types'] )
            ? $settings['post_types']
            : array( 'post', 'page' );

        // Section labels for known post types
        $section_labels = array(
            'page'    => __( 'Pages', 'geo-ai-woo' ),
            'post'    => __( 'Blog Posts', 'geo-ai-woo' ),
            'product' => __( 'Products', 'geo-ai-woo' ),
        );

        foreach ( $post_types as $post_type ) {
            // Skip product if WooCommerce is not active
            if ( 'product' === $post_type && ! class_exists( 'WooCommerce' ) ) {
                continue;
            }

            $items = $this->get_content( $post_type, $is_full );
            if ( ! empty( $items ) ) {
                // Use known label or post type object label
                if ( isset( $section_labels[ $post_type ] ) ) {
                    $label = $section_labels[ $post_type ];
                } else {
                    $pt_object = get_post_type_object( $post_type );
                    $label     = $pt_object ? $pt_object->label : ucfirst( $post_type );
                }

                $output[] = '## ' . $label;
                $output[] = '';
                $output   = array_merge( $output, $items );
                $output[] = '';
            }
        }

        // Taxonomy sections
        $include_taxonomies = isset( $settings['include_taxonomies'] ) ? $settings['include_taxonomies'] : '1';
        if ( '1' === $include_taxonomies ) {
            $taxonomy_sections = $this->get_taxonomy_sections( $post_types, $is_full );
            if ( ! empty( $taxonomy_sections ) ) {
                $output = array_merge( $output, $taxonomy_sections );
            }
        }

        // Footer
        $output[] = '---';
        $output[] = 'Generated by GEO AI Woo v' . GEO_AI_WOO_VERSION;
        $output[] = 'Last updated: ' . gmdate( 'Y-m-d H:i' ) . ' UTC';

        $result = implode( "\n", $output );

        // Decode HTML entities to actual UTF-8 characters for plain text output.
        // WordPress functions (get_the_title, wp_strip_all_tags, etc.) return HTML-encoded
        // strings, which must be decoded for .txt files (e.g., &#x20B8; → ₸, &#8212; → —).
        return html_entity_decode( $result, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    }

    /**
     * Get content for a post type
     *
     * @param string $post_type Post type.
     * @param bool   $is_full   Whether to include full content.
     * @return array
     */
    private function get_content( $post_type, $is_full = false ) {
        $output   = array();
        $settings = get_option( 'geo_ai_woo_settings', array() );

        $meta_query = array(
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
        );

        // Hide out-of-stock products
        if ( 'product' === $post_type && class_exists( 'WooCommerce' ) ) {
            $hide_oos = isset( $settings['hide_out_of_stock'] ) ? $settings['hide_out_of_stock'] : 'wc_default';

            $should_hide = false;
            if ( 'yes' === $hide_oos ) {
                $should_hide = true;
            } elseif ( 'wc_default' === $hide_oos ) {
                $should_hide = 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' );
            }

            if ( $should_hide ) {
                $meta_query = array(
                    'relation' => 'AND',
                    $meta_query,
                    array(
                        'key'     => '_stock_status',
                        'value'   => 'instock',
                        'compare' => '=',
                    ),
                );
            }
        }

        $args = array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $is_full ? apply_filters( 'geo_ai_woo_full_posts_limit', 500 ) : 50,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        );

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();

                $post_id = get_the_ID();
                $title   = wp_strip_all_tags( get_the_title() );
                $url     = esc_url( get_permalink() );

                // Get AI description — use filter for products, fallback to excerpt
                $ai_description = get_post_meta( $post_id, '_geo_ai_woo_description', true );

                if ( ! empty( $ai_description ) ) {
                    $description = wp_strip_all_tags( $ai_description );
                } elseif ( 'product' === $post_type && class_exists( 'WooCommerce' ) ) {
                    $description = wp_strip_all_tags( apply_filters( 'geo_ai_woo_product_description', '', $post_id ) );
                } else {
                    $description = wp_trim_words( Geo_Ai_Woo_Content_Sanitizer::sanitize( get_the_excerpt() ), 20, '...' );
                }

                // Build entry line
                $line = "- [{$title}]({$url})";
                if ( $description ) {
                    $line .= ": {$description}";
                }

                // Append keywords if set
                $keywords = get_post_meta( $post_id, '_geo_ai_woo_keywords', true );
                if ( ! empty( $keywords ) ) {
                    $line .= ' [' . wp_strip_all_tags( $keywords ) . ']';
                }

                $output[] = $line;

                // Include full content in llms-full.txt
                if ( $is_full ) {
                    $content  = Geo_Ai_Woo_Content_Sanitizer::sanitize( get_the_content() );
                    $content  = wp_trim_words( $content, 200, '...' );
                    $output[] = '  ' . $content;
                    $output[] = '';
                }
            }
            wp_reset_postdata();
        }

        return $output;
    }

    /**
     * Get taxonomy sections for llms.txt
     *
     * @param array $post_types Active post types.
     * @param bool  $is_full    Whether to include full content.
     * @return array
     */
    private function get_taxonomy_sections( $post_types, $is_full = false ) {
        $output = array();

        // Map of taxonomies to include
        $taxonomies = array(
            'category' => __( 'Categories', 'geo-ai-woo' ),
            'post_tag' => __( 'Tags', 'geo-ai-woo' ),
        );

        // Add WooCommerce taxonomies if active
        if ( in_array( 'product', $post_types, true ) && class_exists( 'WooCommerce' ) ) {
            $taxonomies['product_cat'] = __( 'Product Categories', 'geo-ai-woo' );
            $taxonomies['product_tag'] = __( 'Product Tags', 'geo-ai-woo' );
        }

        /**
         * Filter the taxonomies included in llms.txt
         *
         * @param array $taxonomies Taxonomy slug => label pairs.
         * @param array $post_types Active post types.
         */
        $taxonomies = apply_filters( 'geo_ai_woo_taxonomies', $taxonomies, $post_types );

        foreach ( $taxonomies as $taxonomy => $label ) {
            if ( ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }

            $terms = get_terms( array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
                'number'     => $is_full ? 200 : 50,
                'orderby'    => 'count',
                'order'      => 'DESC',
            ) );

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }

            $output[] = '## ' . $label;
            $output[] = '';

            foreach ( $terms as $term ) {
                $term_url  = esc_url( get_term_link( $term ) );
                $term_desc = $term->description ? wp_strip_all_tags( $term->description ) : '';
                $line      = "- [{$term->name}]({$term_url})";

                if ( $term_desc ) {
                    $line .= ': ' . wp_trim_words( $term_desc, 15, '...' );
                }

                $line .= " ({$term->count})";

                $output[] = $line;
            }

            $output[] = '';
        }

        return $output;
    }

    /**
     * Add AI bot rules to robots.txt
     *
     * @param string $output Existing robots.txt content.
     * @param bool   $public Blog visibility (public or not).
     * @return string
     */
    public function add_robots_txt_rules( $output, $public ) {
        if ( ! $public ) {
            return $output;
        }

        $settings = get_option( 'geo_ai_woo_settings', array() );
        $enabled  = isset( $settings['robots_txt_enabled'] ) ? $settings['robots_txt_enabled'] : '1';

        if ( '1' !== $enabled ) {
            return $output;
        }

        $bot_rules = isset( $settings['bot_rules'] ) ? $settings['bot_rules'] : array();
        $additions = "\n# GEO AI Woo — AI Crawler Rules\n";

        foreach ( $this->ai_bots as $bot => $provider ) {
            $rule = isset( $bot_rules[ $bot ] ) ? $bot_rules[ $bot ] : 'allow';

            $additions .= "\nUser-agent: {$bot}\n";

            if ( 'allow' === $rule ) {
                $additions .= "Allow: /llms.txt\n";
                $additions .= "Allow: /llms-full.txt\n";
                $additions .= "Allow: /\n";
            } else {
                $additions .= "Allow: /llms.txt\n";
                $additions .= "Disallow: /\n";
            }
        }

        return $output . $additions;
    }

    /**
     * Maybe regenerate cache on post save
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function maybe_regenerate( $post_id, $post ) {
        // Skip autosaves and revisions
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Skip if not a tracked post type
        $settings   = get_option( 'geo_ai_woo_settings', array() );
        $post_types = isset( $settings['post_types'] )
            ? $settings['post_types']
            : array( 'post', 'page' );

        if ( ! in_array( $post->post_type, $post_types, true ) ) {
            return;
        }

        // Clear cache and regenerate static files
        $this->clear_cache();
        $this->write_static_files();
    }

    /**
     * Regenerate cache and static files
     */
    public function regenerate_cache() {
        $this->clear_cache();

        // Pre-generate both versions and store them
        $duration     = $this->get_cache_duration();
        $content      = $this->generate( false );
        $content_full = $this->generate( true );

        if ( $duration > 0 ) {
            set_transient( 'geo_ai_woo_llms', $content, $duration );
            set_transient( 'geo_ai_woo_llms_full', $content_full, $duration );
        }

        // Write static files
        $this->write_static_files( $content, $content_full );

        // Store last regeneration timestamp
        update_option( 'geo_ai_woo_last_regenerated', time(), false );
    }

    /**
     * Write static llms.txt files to WordPress root directory
     *
     * @param string|null $content      Pre-generated standard content.
     * @param string|null $content_full Pre-generated full content.
     */
    public function write_static_files( $content = null, $content_full = null ) {
        if ( ! defined( 'ABSPATH' ) ) {
            return;
        }

        // Generate default language content if not provided
        if ( null === $content ) {
            $content = $this->generate( false );
        }
        if ( null === $content_full ) {
            $content_full = $this->generate( true );
        }

        // Default language files
        $files = array(
            ABSPATH . 'llms.txt'      => $content,
            ABSPATH . 'llms-full.txt' => $content_full,
        );

        // Generate multilingual files
        if ( class_exists( 'Geo_Ai_Woo_Multilingual' ) ) {
            $multilingual = Geo_Ai_Woo_Multilingual::instance();

            if ( $multilingual->is_active() ) {
                $languages = $multilingual->get_active_languages();

                foreach ( $languages as $lang ) {
                    // Skip default language — already generated above
                    if ( ! empty( $lang['default'] ) ) {
                        continue;
                    }

                    $multilingual->switch_language( $lang['code'] );

                    $lang_file      = $multilingual->get_llms_filename( $lang['code'], false );
                    $lang_file_full = $multilingual->get_llms_filename( $lang['code'], true );

                    $files[ ABSPATH . $lang_file ]      = $this->generate( false, $lang['code'] );
                    $files[ ABSPATH . $lang_file_full ]  = $this->generate( true, $lang['code'] );

                    $multilingual->restore_language();
                }
            }
        }

        // Write all files
        foreach ( $files as $file_path => $file_content ) {
            $this->write_file( $file_path, $file_content );
        }
    }

    /**
     * Write a single file using WP_Filesystem or direct fallback
     *
     * @param string $file_path    Full path to the file.
     * @param string $file_content File content to write.
     */
    private function write_file( $file_path, $file_content ) {
        // Use WordPress filesystem API if available
        if ( function_exists( 'WP_Filesystem' ) ) {
            global $wp_filesystem;
            if ( empty( $wp_filesystem ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }
            if ( $wp_filesystem ) {
                $wp_filesystem->put_contents( $file_path, $file_content, FS_CHMOD_FILE );
                return;
            }
        }

        // Direct fallback
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents( $file_path, $file_content );
    }

    /**
     * Get cache duration in seconds based on settings
     *
     * @return int
     */
    private function get_cache_duration() {
        $settings = get_option( 'geo_ai_woo_settings', array() );
        $duration = isset( $settings['cache_duration'] ) ? $settings['cache_duration'] : 'daily';

        $map = array(
            'immediate' => 0,
            'hourly'    => HOUR_IN_SECONDS,
            'daily'     => DAY_IN_SECONDS,
            'weekly'    => WEEK_IN_SECONDS,
        );

        return isset( $map[ $duration ] ) ? $map[ $duration ] : DAY_IN_SECONDS;
    }

    /**
     * Clear cache
     */
    public function clear_cache() {
        delete_transient( 'geo_ai_woo_llms' );
        delete_transient( 'geo_ai_woo_llms_full' );
    }

    /**
     * Get supported AI bots
     *
     * @return array
     */
    public function get_ai_bots() {
        return $this->ai_bots;
    }
}
