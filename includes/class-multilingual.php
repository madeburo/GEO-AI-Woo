<?php
/**
 * Multilingual Support — WPML, Polylang, TranslatePress
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstraction layer for multilingual plugin integration
 */
class Geo_Ai_Woo_Multilingual {

	/**
	 * Single instance
	 *
	 * @var Geo_Ai_Woo_Multilingual
	 */
	private static $instance = null;

	/**
	 * Active multilingual provider
	 *
	 * @var string|null wpml, polylang, trp, or null
	 */
	private $provider = null;

	/**
	 * Original language before switching
	 *
	 * @var string|null
	 */
	private $original_language = null;

	/**
	 * Get single instance
	 *
	 * @return Geo_Ai_Woo_Multilingual
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — detect active multilingual plugin
	 */
	private function __construct() {
		$this->detect_provider();
	}

	/**
	 * Detect which multilingual plugin is active
	 */
	private function detect_provider() {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$this->provider = 'wpml';
		} elseif ( function_exists( 'pll_languages_list' ) ) {
			$this->provider = 'polylang';
		} elseif ( class_exists( 'TRP_Translate_Press' ) ) {
			$this->provider = 'trp';
		}

		/**
		 * Filter the detected multilingual provider
		 *
		 * @param string|null $provider Detected provider slug.
		 */
		$this->provider = apply_filters( 'geo_ai_woo_multilingual_provider', $this->provider );
	}

	/**
	 * Check if multilingual support is active and enabled
	 *
	 * @return bool
	 */
	public function is_active() {
		if ( null === $this->provider ) {
			return false;
		}

		$settings = get_option( 'geo_ai_woo_settings', array() );
		$enabled  = isset( $settings['multilingual_enabled'] ) ? $settings['multilingual_enabled'] : '1';

		return '1' === $enabled;
	}

	/**
	 * Get the active provider slug
	 *
	 * @return string|null
	 */
	public function get_provider() {
		return $this->provider;
	}

	/**
	 * Get all active languages
	 *
	 * @return array Array of ['code' => 'en', 'name' => 'English', 'default' => true]
	 */
	public function get_active_languages() {
		if ( null === $this->provider ) {
			return array();
		}

		switch ( $this->provider ) {
			case 'wpml':
				return $this->get_wpml_languages();
			case 'polylang':
				return $this->get_polylang_languages();
			case 'trp':
				return $this->get_trp_languages();
			default:
				return array();
		}
	}

	/**
	 * Get WPML languages
	 *
	 * @return array
	 */
	private function get_wpml_languages() {
		$languages      = array();
		$wpml_languages = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );

		if ( ! is_array( $wpml_languages ) ) {
			return $languages;
		}

		$default_lang = apply_filters( 'wpml_default_language', '' );

		foreach ( $wpml_languages as $lang ) {
			$languages[] = array(
				'code'    => $lang['language_code'],
				'name'    => $lang['translated_name'],
				'default' => ( $lang['language_code'] === $default_lang ),
			);
		}

		return $languages;
	}

	/**
	 * Get Polylang languages
	 *
	 * @return array
	 */
	private function get_polylang_languages() {
		$languages = array();

		if ( ! function_exists( 'pll_languages_list' ) || ! function_exists( 'pll_default_language' ) ) {
			return $languages;
		}

		$default_lang = pll_default_language();
		$pll_langs    = pll_languages_list( array( 'fields' => '' ) );

		if ( ! is_array( $pll_langs ) ) {
			return $languages;
		}

		foreach ( $pll_langs as $lang ) {
			$languages[] = array(
				'code'    => $lang->slug,
				'name'    => $lang->name,
				'default' => ( $lang->slug === $default_lang ),
			);
		}

		return $languages;
	}

	/**
	 * Get TranslatePress languages
	 *
	 * @return array
	 */
	private function get_trp_languages() {
		$languages = array();
		$settings  = get_option( 'trp_settings', array() );

		if ( empty( $settings['publish-languages'] ) || empty( $settings['default-language'] ) ) {
			return $languages;
		}

		$trp_languages  = $settings['publish-languages'];
		$default_lang   = $settings['default-language'];
		$language_names  = $this->get_trp_language_names();

		foreach ( $trp_languages as $lang_code ) {
			$languages[] = array(
				'code'    => $lang_code,
				'name'    => isset( $language_names[ $lang_code ] ) ? $language_names[ $lang_code ] : $lang_code,
				'default' => ( $lang_code === $default_lang ),
			);
		}

		return $languages;
	}

	/**
	 * Get TranslatePress language names helper
	 *
	 * @return array
	 */
	private function get_trp_language_names() {
		if ( ! class_exists( 'TRP_Languages' ) ) {
			return array();
		}

		$trp_languages = new TRP_Languages();
		$all_languages = $trp_languages->get_wp_languages();
		$names         = array();

		foreach ( $all_languages as $lang ) {
			if ( isset( $lang['language'] ) && isset( $lang['english_name'] ) ) {
				$names[ $lang['language'] ] = $lang['english_name'];
			}
		}

		return $names;
	}

	/**
	 * Switch to a specific language context
	 *
	 * @param string $lang_code Language code.
	 */
	public function switch_language( $lang_code ) {
		if ( null === $this->provider ) {
			return;
		}

		$this->original_language = $this->get_current_language();

		switch ( $this->provider ) {
			case 'wpml':
				do_action( 'wpml_switch_language', $lang_code );
				break;

			case 'polylang':
				if ( function_exists( 'PLL' ) && is_object( PLL() ) ) {
					PLL()->curlang = PLL()->model->get_language( $lang_code );
				}
				break;

			case 'trp':
				// TranslatePress uses URL-based switching; set global for content queries
				global $TRP_LANGUAGE;
				$TRP_LANGUAGE = $lang_code;
				break;
		}
	}

	/**
	 * Restore to original language context
	 */
	public function restore_language() {
		if ( null === $this->provider || null === $this->original_language ) {
			return;
		}

		switch ( $this->provider ) {
			case 'wpml':
				do_action( 'wpml_switch_language', $this->original_language );
				break;

			case 'polylang':
				if ( function_exists( 'PLL' ) && is_object( PLL() ) ) {
					PLL()->curlang = PLL()->model->get_language( $this->original_language );
				}
				break;

			case 'trp':
				global $TRP_LANGUAGE;
				$TRP_LANGUAGE = $this->original_language;
				break;
		}

		$this->original_language = null;
	}

	/**
	 * Get the current language code
	 *
	 * @return string
	 */
	public function get_current_language() {
		switch ( $this->provider ) {
			case 'wpml':
				return apply_filters( 'wpml_current_language', '' );

			case 'polylang':
				if ( function_exists( 'pll_current_language' ) ) {
					return pll_current_language();
				}
				return '';

			case 'trp':
				global $TRP_LANGUAGE;
				if ( ! empty( $TRP_LANGUAGE ) ) {
					return $TRP_LANGUAGE;
				}
				$settings = get_option( 'trp_settings', array() );
				return isset( $settings['default-language'] ) ? $settings['default-language'] : '';

			default:
				return '';
		}
	}

	/**
	 * Get the default language code
	 *
	 * @return string
	 */
	public function get_default_language() {
		$languages = $this->get_active_languages();

		foreach ( $languages as $lang ) {
			if ( ! empty( $lang['default'] ) ) {
				return $lang['code'];
			}
		}

		return '';
	}

	/**
	 * Get the language of a specific post
	 *
	 * @param int $post_id Post ID.
	 * @return string Language code or empty string.
	 */
	public function get_post_language( $post_id ) {
		switch ( $this->provider ) {
			case 'wpml':
				$lang_info = apply_filters( 'wpml_post_language_details', null, $post_id );
				return is_array( $lang_info ) && isset( $lang_info['language_code'] )
					? $lang_info['language_code']
					: '';

			case 'polylang':
				if ( function_exists( 'pll_get_post_language' ) ) {
					return (string) pll_get_post_language( $post_id );
				}
				return '';

			case 'trp':
				// TranslatePress stores translations differently — all content shares the same post ID
				$settings = get_option( 'trp_settings', array() );
				return isset( $settings['default-language'] ) ? $settings['default-language'] : '';

			default:
				return '';
		}
	}

	/**
	 * Get the llms.txt filename for a language
	 *
	 * @param string $lang_code Language code.
	 * @param bool   $is_full   Whether this is the full version.
	 * @return string Filename (e.g., 'llms-ru.txt' or 'llms.txt' for default)
	 */
	public function get_llms_filename( $lang_code, $is_full = false ) {
		$default_lang = $this->get_default_language();
		$prefix       = $is_full ? 'llms-full' : 'llms';

		if ( $lang_code === $default_lang || empty( $lang_code ) ) {
			return $prefix . '.txt';
		}

		return $prefix . '-' . sanitize_file_name( $lang_code ) . '.txt';
	}

	/**
	 * Get all llms.txt filenames across languages
	 *
	 * @return array Array of filenames ['llms.txt', 'llms-ru.txt', ...]
	 */
	public function get_all_llms_filenames() {
		$filenames = array( 'llms.txt', 'llms-full.txt' );

		if ( ! $this->is_active() ) {
			return $filenames;
		}

		$languages = $this->get_active_languages();

		foreach ( $languages as $lang ) {
			if ( ! empty( $lang['default'] ) ) {
				continue;
			}

			$filenames[] = $this->get_llms_filename( $lang['code'], false );
			$filenames[] = $this->get_llms_filename( $lang['code'], true );
		}

		return $filenames;
	}

	/**
	 * Delete all multilingual llms.txt files
	 */
	public function delete_all_files() {
		$filenames = $this->get_all_llms_filenames();

		foreach ( $filenames as $filename ) {
			$file_path = ABSPATH . $filename;
			if ( file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
			}
		}
	}
}
