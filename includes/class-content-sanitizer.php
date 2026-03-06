<?php
/**
 * Content Sanitizer
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for sanitizing raw WordPress content for AI-facing output.
 *
 * Removes page builder markup, shortcodes, inline styles, scripts,
 * encoded characters and normalizes whitespace to produce clean
 * semantic text suitable for AI crawlers.
 */
class Geo_Ai_Woo_Content_Sanitizer {

	/**
	 * Single instance
	 *
	 * @var Geo_Ai_Woo_Content_Sanitizer
	 */
	private static $instance = null;

	/**
	 * Mojibake-to-UTF-8 mapping for double-encoded characters.
	 *
	 * Keys are hex-byte sequences (curly quotes read as Windows-1252 then
	 * re-encoded to UTF-8). Sorted by descending key length so that
	 * str_replace matches longer sequences first and avoids partial replacements.
	 *
	 * @var array
	 */
	private static $mojibake_map = array(
		"\xC3\xA2\xE2\x82\xAC\xE2\x84\xA2" => "\xE2\x80\x99", // U+2019 right single quote '
		"\xC3\xA2\xE2\x82\xAC\xE2\x80\x9D" => "\xE2\x80\x94", // U+2014 em dash —
		"\xC3\xA2\xE2\x82\xAC\xE2\x80\x9C" => "\xE2\x80\x93", // U+2013 en dash –
		"\xC3\xA2\xE2\x82\xAC\xC5\x93"     => "\xE2\x80\x9C", // U+201C left double quote "
		"\xC3\xA2\xE2\x82\xAC\xC2\x9D"     => "\xE2\x80\x9D", // U+201D right double quote "
		"\xC3\xA2\xE2\x82\xAC\xCB\x9C"     => "\xE2\x80\x98", // U+2018 left single quote '
		"\xC3\xA2\xE2\x82\xAC\xC2\xB3"     => "\xE2\x80\xB3", // U+2033 double prime ″
		"\xC3\xA2\xE2\x82\xAC\xC2\xB2"     => "\xE2\x80\xB2", // U+2032 single prime ′
		"\xC3\xA2\xE2\x82\xAC\xC2\xA6"     => "\xE2\x80\xA6", // U+2026 ellipsis …
	);

	/**
	 * Regex patterns for removing page builder markup.
	 *
	 * @var array
	 */
	private static $builder_patterns = array(
		'wpbakery'           => '/\[\/?(vc_|mk_)[^\]]*\]/s',
		'divi'               => '/\[\/?(et_pb_)[^\]]*\]/s',
		'beaver_builder'     => '/\[\/?(fl_builder_)[^\]]*\]/s',
		'elementor_comments' => '/<!--\s*\/?wp:[^>]*-->/s',
	);

	/**
	 * Get single instance
	 *
	 * @return Geo_Ai_Woo_Content_Sanitizer
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
	private function __construct() {}

	/**
	 * Sanitize raw content for AI-facing output.
	 *
	 * @param mixed $content Raw content from post_content, get_the_content(), etc.
	 * @return string Cleaned UTF-8 text.
	 */
	public static function sanitize( $content ) {
		// Validate input: null, non-string, empty string → return ''.
		if ( ! is_string( $content ) || '' === $content ) {
			return '';
		}

		$original = $content;

		/**
		 * Filter content before sanitization begins.
		 *
		 * @param string $content Raw content.
		 */
		$content = apply_filters( 'geo_ai_woo_pre_sanitize', $content );

		// Sanitization pipeline.
		$content = self::fix_mojibake( $content );
		$content = strip_shortcodes( $content );
		$content = self::remove_builder_shortcodes( $content );
		$content = self::remove_unregistered_shortcodes( $content );
		$content = self::remove_scripts_and_styles( $content );
		$content = self::remove_base64_data( $content );
		$content = wp_strip_all_tags( $content );
		$content = html_entity_decode( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$content = self::normalize_whitespace( $content );

		/**
		 * Filter the final sanitized content.
		 *
		 * @param string $content   Sanitized content.
		 * @param string $original  Original raw content.
		 */
		$content = apply_filters( 'geo_ai_woo_sanitized_content', $content, $original );

		return $content;
	}

	/**
	 * Fix mojibake sequences from double UTF-8 encoding.
	 *
	 * @param string $content Content to fix.
	 * @return string Content with corrected UTF-8 characters.
	 */
	private static function fix_mojibake( $content ) {
		return str_replace(
			array_keys( self::$mojibake_map ),
			array_values( self::$mojibake_map ),
			$content
		);
	}

	/**
	 * Remove page builder shortcodes (WP Bakery, Divi, Beaver Builder, Elementor comments).
	 *
	 * @param string $content Content to clean.
	 * @return string Content without builder markup.
	 */
	private static function remove_builder_shortcodes( $content ) {
		/**
		 * Filter regex patterns used to strip page builder markup.
		 *
		 * @param array $patterns Associative array of 'key' => '/regex/' patterns.
		 */
		$patterns = apply_filters( 'geo_ai_woo_sanitize_patterns', self::$builder_patterns );

		foreach ( $patterns as $pattern ) {
			$result = preg_replace( $pattern, '', $content );
			if ( null !== $result ) {
				$content = $result;
			}
		}

		return $content;
	}

	/**
	 * Remove unregistered shortcodes (paired and self-closing).
	 *
	 * Paired shortcodes: preserves inner content.
	 * Self-closing shortcodes: removed entirely.
	 *
	 * @param string $content Content to clean.
	 * @return string Content without unregistered shortcodes.
	 */
	private static function remove_unregistered_shortcodes( $content ) {
		// Paired: [tag attr="val"]inner content[/tag] → inner content.
		$content = preg_replace( '/\[(\w+)[^\]]*\](.*?)\[\/\1\]/s', '$2', $content );

		// Self-closing: [tag attr="val" /].
		$content = preg_replace( '/\[\w+[^\]]*\/\]/s', '', $content );

		return $content;
	}

	/**
	 * Remove script and style tags along with their contents.
	 *
	 * @param string $content Content to clean.
	 * @return string Content without script/style blocks.
	 */
	private static function remove_scripts_and_styles( $content ) {
		$content = preg_replace( '/<script[^>]*>.*?<\/script>/si', '', $content );
		$content = preg_replace( '/<style[^>]*>.*?<\/style>/si', '', $content );

		return $content;
	}

	/**
	 * Remove base64-encoded data strings (e.g. inline images).
	 *
	 * @param string $content Content to clean.
	 * @return string Content without base64 data.
	 */
	private static function remove_base64_data( $content ) {
		return preg_replace( '/data:[a-zA-Z0-9\/+\-]+;base64,[A-Za-z0-9+\/=]+/', '', $content );
	}

	/**
	 * Normalize whitespace: collapse multiple spaces/tabs/newlines to single space, trim.
	 *
	 * @param string $content Content to normalize.
	 * @return string Content with normalized whitespace.
	 */
	private static function normalize_whitespace( $content ) {
		return trim( preg_replace( '/\s+/', ' ', $content ) );
	}
}
