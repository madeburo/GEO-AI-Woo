<?php
/**
 * AI Auto-Generation — Claude/OpenAI Integration
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for generating AI descriptions via Claude or OpenAI APIs
 */
class Geo_Ai_Woo_AI_Generator {

	/**
	 * Single instance
	 *
	 * @var Geo_Ai_Woo_AI_Generator
	 */
	private static $instance = null;

	/**
	 * Default prompt template
	 *
	 * @var string
	 */
	const DEFAULT_PROMPT = 'Write a concise AI-optimized description (max 200 characters) for the following {type}. Focus on key facts, purpose, and value. Do not use quotes or markdown.

Title: {title}
Content: {content}';

	/**
	 * Get single instance
	 *
	 * @return Geo_Ai_Woo_AI_Generator
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
		add_action( 'wp_ajax_geo_ai_woo_ai_generate', array( $this, 'ajax_generate' ) );
		add_action( 'wp_ajax_geo_ai_woo_ai_bulk_generate', array( $this, 'ajax_bulk_generate' ) );
		add_action( 'wp_ajax_geo_ai_woo_ai_bulk_progress', array( $this, 'ajax_bulk_progress' ) );
	}

	/**
	 * Check if AI generation is configured
	 *
	 * @return bool
	 */
	public function is_configured() {
		$settings = get_option( 'geo_ai_woo_settings', array() );
		$provider = isset( $settings['ai_provider'] ) ? $settings['ai_provider'] : 'none';
		$api_key  = $this->get_api_key();

		return 'none' !== $provider && ! empty( $api_key );
	}

	/**
	 * Get the active AI provider
	 *
	 * @return string Provider name: 'claude', 'openai', or 'none'.
	 */
	public function get_provider() {
		$settings = get_option( 'geo_ai_woo_settings', array() );
		return isset( $settings['ai_provider'] ) ? $settings['ai_provider'] : 'none';
	}

	/**
	 * Generate AI description for a post
	 *
	 * @param int $post_id Post ID.
	 * @return string|WP_Error Generated description or error.
	 */
	public function generate_description( $post_id ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'not_configured', __( 'AI generation is not configured.', 'geo-ai-woo' ) );
		}

		// Rate limit check
		if ( ! $this->check_rate_limit() ) {
			return new WP_Error( 'rate_limited', __( 'Rate limit exceeded. Please wait before generating more descriptions.', 'geo-ai-woo' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'invalid_post', __( 'Post not found.', 'geo-ai-woo' ) );
		}

		$prompt = $this->build_prompt( $post );

		$settings = get_option( 'geo_ai_woo_settings', array() );
		$provider = $this->get_provider();

		if ( 'claude' === $provider ) {
			$result = $this->call_claude_api( $prompt, $settings );
		} elseif ( 'openai' === $provider ) {
			$result = $this->call_openai_api( $prompt, $settings );
		} else {
			return new WP_Error( 'invalid_provider', __( 'Invalid AI provider.', 'geo-ai-woo' ) );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Sanitize and truncate
		$description = sanitize_text_field( $result );
		$description = mb_substr( $description, 0, 200 );

		// Increment rate limit counter
		$this->increment_rate_limit();

		return $description;
	}

	/**
	 * Build the prompt for AI generation
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	private function build_prompt( $post ) {
		$settings = get_option( 'geo_ai_woo_settings', array() );
		$template = isset( $settings['ai_prompt_template'] ) && ! empty( $settings['ai_prompt_template'] )
			? $settings['ai_prompt_template']
			: self::DEFAULT_PROMPT;

		// Determine content type
		$post_type_obj = get_post_type_object( $post->post_type );
		$type          = $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;

		// Build content excerpt — sanitize and limit to 500 words
		$content = Geo_Ai_Woo_Content_Sanitizer::sanitize( $post->post_content );
		$content = wp_trim_words( $content, 500, '' );

		// For WooCommerce products, add product-specific data
		if ( 'product' === $post->post_type && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $post->ID );
			if ( $product ) {
				$product_data = array();

				if ( $product->get_price() ) {
					$product_data[] = 'Price: ' . wp_strip_all_tags( wc_price( $product->get_price() ) );
				}

				$product_data[] = $product->is_in_stock() ? 'In Stock' : 'Out of Stock';

				$categories = wc_get_product_category_list( $post->ID, ', ' );
				if ( $categories ) {
					$product_data[] = 'Categories: ' . wp_strip_all_tags( $categories );
				}

				if ( ! empty( $product_data ) ) {
					$content .= "\n\nProduct data: " . implode( ' | ', $product_data );
				}
			}
		}

		// Replace placeholders
		$prompt = str_replace(
			array( '{title}', '{content}', '{type}' ),
			array( $post->post_title, $content, strtolower( $type ) ),
			$template
		);

		return $prompt;
	}

	/**
	 * Call Claude API
	 *
	 * @param string $prompt   The prompt to send.
	 * @param array  $settings Plugin settings.
	 * @return string|WP_Error Generated text or error.
	 */
	private function call_claude_api( $prompt, $settings ) {
		$api_key    = $this->get_api_key();
		$model      = isset( $settings['ai_model'] ) && ! empty( $settings['ai_model'] )
			? $settings['ai_model']
			: 'claude-sonnet-4-5-20250514';
		$max_tokens = isset( $settings['ai_max_tokens'] ) ? absint( $settings['ai_max_tokens'] ) : 150;

		$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type'      => 'application/json',
				'x-api-key'        => $api_key,
				'anthropic-version' => '2023-06-01',
			),
			'body'    => wp_json_encode( array(
				'model'      => $model,
				'max_tokens' => $max_tokens,
				'messages'   => array(
					array(
						'role'    => 'user',
						'content' => $prompt,
					),
				),
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Claude API error.', 'geo-ai-woo' );
			return new WP_Error( 'api_error', $message );
		}

		if ( isset( $body['content'][0]['text'] ) ) {
			return trim( $body['content'][0]['text'] );
		}

		return new WP_Error( 'api_error', __( 'Unexpected API response format.', 'geo-ai-woo' ) );
	}

	/**
	 * Call OpenAI API
	 *
	 * @param string $prompt   The prompt to send.
	 * @param array  $settings Plugin settings.
	 * @return string|WP_Error Generated text or error.
	 */
	private function call_openai_api( $prompt, $settings ) {
		$api_key    = $this->get_api_key();
		$model      = isset( $settings['ai_model'] ) && ! empty( $settings['ai_model'] )
			? $settings['ai_model']
			: 'gpt-4o-mini';
		$max_tokens = isset( $settings['ai_max_tokens'] ) ? absint( $settings['ai_max_tokens'] ) : 150;

		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			),
			'body'    => wp_json_encode( array(
				'model'      => $model,
				'max_tokens' => $max_tokens,
				'messages'   => array(
					array(
						'role'    => 'user',
						'content' => $prompt,
					),
				),
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'OpenAI API error.', 'geo-ai-woo' );
			return new WP_Error( 'api_error', $message );
		}

		if ( isset( $body['choices'][0]['message']['content'] ) ) {
			return trim( $body['choices'][0]['message']['content'] );
		}

		return new WP_Error( 'api_error', __( 'Unexpected API response format.', 'geo-ai-woo' ) );
	}

	/**
	 * Get decrypted API key
	 *
	 * @return string
	 */
	private function get_api_key() {
		$settings = get_option( 'geo_ai_woo_settings', array() );
		$key      = isset( $settings['ai_api_key'] ) ? $settings['ai_api_key'] : '';

		if ( empty( $key ) ) {
			return '';
		}

		// Check if key is base64 encoded (our encryption)
		$decoded = base64_decode( $key, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false !== $decoded && base64_encode( $decoded ) === $key ) { // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			return $decoded;
		}

		return $key;
	}

	/**
	 * Encrypt API key for storage
	 *
	 * @param string $key Plain text API key.
	 * @return string Encoded key.
	 */
	public static function encrypt_api_key( $key ) {
		if ( empty( $key ) ) {
			return '';
		}
		return base64_encode( $key ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Check rate limit (max 10 requests per minute)
	 *
	 * @return bool True if within rate limit.
	 */
	private function check_rate_limit() {
		$count = (int) get_transient( 'geo_ai_woo_ai_rate_limit' );
		return $count < 10;
	}

	/**
	 * Increment rate limit counter
	 */
	private function increment_rate_limit() {
		$count = (int) get_transient( 'geo_ai_woo_ai_rate_limit' );
		set_transient( 'geo_ai_woo_ai_rate_limit', $count + 1, 60 );
	}

	/**
	 * Get remaining rate limit
	 *
	 * @return int
	 */
	public function get_remaining_quota() {
		$count = (int) get_transient( 'geo_ai_woo_ai_rate_limit' );
		return max( 0, 10 - $count );
	}

	/**
	 * AJAX handler for single post AI generation
	 */
	public function ajax_generate() {
		check_ajax_referer( 'geo_ai_woo_ai_generate', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'geo-ai-woo' ) ) );
		}

		$result = $this->generate_description( $post_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Save the generated description
		update_post_meta( $post_id, '_geo_ai_woo_description', $result );

		wp_send_json_success( array(
			'description' => $result,
			'remaining'   => $this->get_remaining_quota(),
		) );
	}

	/**
	 * AJAX handler for bulk AI generation
	 */
	public function ajax_bulk_generate() {
		check_ajax_referer( 'geo_ai_woo_ai_bulk', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'geo-ai-woo' ) ) );
		}

		$settings   = get_option( 'geo_ai_woo_settings', array() );
		$post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post', 'page' );

		// Find posts without AI descriptions
		$posts = get_posts( array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key'     => '_geo_ai_woo_description',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_geo_ai_woo_description',
						'value'   => '',
						'compare' => '=',
					),
				),
				array(
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
			),
		) );

		if ( empty( $posts ) ) {
			wp_send_json_success( array(
				'total'     => 0,
				'message'   => __( 'All posts already have AI descriptions.', 'geo-ai-woo' ),
				'completed' => true,
			) );
		}

		$user_id     = get_current_user_id();
		$progress_key = 'geo_ai_woo_bulk_progress_' . $user_id;

		// Store progress info
		set_transient( $progress_key, array(
			'total'     => count( $posts ),
			'processed' => 0,
			'succeeded' => 0,
			'failed'    => 0,
			'post_ids'  => $posts,
			'status'    => 'running',
		), 600 );

		// Process first batch (up to 5 at a time to stay within rate limits)
		$batch   = array_slice( $posts, 0, 5 );
		$results = $this->process_batch( $batch );

		$progress = get_transient( $progress_key );
		$progress['processed'] = count( $batch );
		$progress['succeeded'] = $results['succeeded'];
		$progress['failed']    = $results['failed'];

		if ( $progress['processed'] >= $progress['total'] ) {
			$progress['status'] = 'complete';
		}

		set_transient( $progress_key, $progress, 600 );

		wp_send_json_success( array(
			'total'     => count( $posts ),
			'processed' => $progress['processed'],
			'succeeded' => $progress['succeeded'],
			'failed'    => $progress['failed'],
			'completed' => 'complete' === $progress['status'],
		) );
	}

	/**
	 * AJAX handler for bulk generation progress / continue
	 */
	public function ajax_bulk_progress() {
		check_ajax_referer( 'geo_ai_woo_ai_bulk', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'geo-ai-woo' ) ) );
		}

		$user_id      = get_current_user_id();
		$progress_key = 'geo_ai_woo_bulk_progress_' . $user_id;
		$progress     = get_transient( $progress_key );

		if ( ! $progress || 'complete' === $progress['status'] ) {
			wp_send_json_success( array(
				'total'     => $progress ? $progress['total'] : 0,
				'processed' => $progress ? $progress['processed'] : 0,
				'succeeded' => $progress ? $progress['succeeded'] : 0,
				'failed'    => $progress ? $progress['failed'] : 0,
				'completed' => true,
			) );
			return;
		}

		// Process next batch
		$remaining = array_slice( $progress['post_ids'], $progress['processed'], 5 );

		if ( empty( $remaining ) ) {
			$progress['status'] = 'complete';
			set_transient( $progress_key, $progress, 600 );
			wp_send_json_success( array(
				'total'     => $progress['total'],
				'processed' => $progress['processed'],
				'succeeded' => $progress['succeeded'],
				'failed'    => $progress['failed'],
				'completed' => true,
			) );
			return;
		}

		$results = $this->process_batch( $remaining );

		$progress['processed'] += count( $remaining );
		$progress['succeeded'] += $results['succeeded'];
		$progress['failed']    += $results['failed'];

		if ( $progress['processed'] >= $progress['total'] ) {
			$progress['status'] = 'complete';
		}

		set_transient( $progress_key, $progress, 600 );

		wp_send_json_success( array(
			'total'     => $progress['total'],
			'processed' => $progress['processed'],
			'succeeded' => $progress['succeeded'],
			'failed'    => $progress['failed'],
			'completed' => 'complete' === $progress['status'],
		) );
	}

	/**
	 * Process a batch of posts for AI generation
	 *
	 * @param array $post_ids Array of post IDs.
	 * @return array Results with 'succeeded' and 'failed' counts.
	 */
	private function process_batch( $post_ids ) {
		$succeeded = 0;
		$failed    = 0;

		foreach ( $post_ids as $post_id ) {
			$result = $this->generate_description( $post_id );

			if ( is_wp_error( $result ) ) {
				$failed++;
				continue;
			}

			update_post_meta( $post_id, '_geo_ai_woo_description', $result );
			$succeeded++;
		}

		return array(
			'succeeded' => $succeeded,
			'failed'    => $failed,
		);
	}
}
