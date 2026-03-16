# Changelog

All notable changes to GEO AI Search Optimization will be documented in this file.

## [0.7.0] - 2026-03-16

### Changed — Plugin Rename
- Plugin display name changed from "GEO AI for WooCommerce" to "GEO AI Search Optimization" across all user-facing surfaces
- Updated Plugin Name header, settings page title, menu label, meta box title, dashboard widget title, admin notices, CLI status output, Quick Edit section title
- Slug, text domain, CSS classes, option keys, meta keys, REST namespace, and WP-CLI command remain unchanged for backward compatibility

### Fixed — UTF-8 / Cyrillic Encoding
- `decode_text()` now detects and repairs mojibake from double UTF-8 encoding (latin1 DB connection → UTF-8 → UTF-8) via `mb_convert_encoding` from Windows-1252
- `write_file()` validates UTF-8 integrity via `mb_check_encoding` before writing static files
- Excerpt descriptions (`wp_trim_words` output) now passed through `decode_text()` to prevent HTML entities in plain text
- Full content in llms-full.txt now passed through `decode_text()` after `wp_trim_words`
- Taxonomy term descriptions now passed through `decode_text()` after `wp_trim_words`

## [0.6.0] - 2026-03-16

### Changed — Plugin Rename (WordPress Plugin Review)
- Plugin renamed from "GEO AI Woo" to "GEO AI Search Optimization"
- Slug changed from `geo-ai-woo` to `geo-ai-for-woocommerce`
- Text domain changed from `geo-ai-woo` to `geo-ai-for-woocommerce`
- Main plugin file renamed from `geo-ai-woo.php` to `geo-ai-for-woocommerce.php`
- REST API namespace changed from `geo-ai-woo/v1` to `geo-ai-for-woocommerce/v1`
- WP-CLI command changed from `wp geo-ai-woo` to `wp geo-ai-for-woocommerce`
- All asset handles, CSS classes, HTML IDs, and admin page slugs updated
- Language files renamed from `geo-ai-woo-*.po/mo` to `geo-ai-for-woocommerce-*.po/mo`
- Internal prefixes (`geo_ai_woo_`, `_geo_ai_woo_`, `Geo_Ai_Woo_`) intentionally preserved for backward compatibility with existing installations

### Added — WordPress Plugin Review Compliance
- Added `Requires Plugins: woocommerce` header to main plugin file
- CLI export now writes to `wp_upload_dir()/geo-ai-for-woocommerce/` instead of arbitrary paths

### Fixed — Security
- Removed `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT` flags from `wp_json_encode()` in JSON-LD output (`class-seo-headers.php`)
- Fixed unescaped API key field output in settings page (`class-settings.php`)

### Fixed — UTF-8 Encoding
- Fixed Cyrillic and multibyte character corruption (mojibake) in static llms.txt and llms-full.txt files
- Replaced `WP_Filesystem::put_contents()` with direct `file_put_contents()` — FTP transport was corrupting multibyte characters during file write
- Added `decode_text()` helper — decodes HTML entities to UTF-8 per-field (titles, descriptions, keywords, taxonomy names) instead of relying on a single pass at the end

### Improved — Duplicate Plugin Protection
- Added `defined('GEO_AI_WOO_VERSION')` guard in main plugin file — prevents fatal error when two copies of the plugin are installed
- Added admin notice that detects and warns about duplicate plugin copies

### Changed
- Default OpenAI model placeholder updated from `gpt-4o-mini` to `gpt-5`

### Added — Localization
- German (de_DE) translation
- French (fr_FR) translation

## [0.5.5] - 2026-03-07

### Docs
- Updated documentation with new domain geoai.run
- Added ecosystem overview

## [0.5.4.1] - 2026-03-07

### Fixed — Machine Readability
- Removed UTF-8 symbols (✓/✗) from crawler rules in llms.txt output — now uses plain ASCII `Allowed` / `Blocked` for maximum parser compatibility

### Changed
- Version bump 0.5.4 → 0.5.4.1

## [0.5.4] - 2026-03-06

### Added — Content Sanitization
- New `Geo_Ai_Woo_Content_Sanitizer` class — centralized content cleaning pipeline for all AI-facing output
- Removes page builder markup: WP Bakery (`vc_*`, `mk_*`), Divi (`et_pb_*`), Beaver Builder (`fl_builder_*`), Elementor/Gutenberg HTML comments
- Removes registered and unregistered WordPress shortcodes (paired and self-closing)
- Strips `<script>` and `<style>` tags with their contents
- Removes inline base64-encoded data (embedded images, fonts, etc.)
- Fixes mojibake artifacts from double UTF-8 encoding (curly quotes, em/en dashes, ellipsis, primes)
- Decodes HTML entities to proper UTF-8 characters
- Normalizes whitespace (collapses multiple spaces/tabs/newlines, trims)
- Filter `geo_ai_woo_pre_sanitize` — modify content before sanitization
- Filter `geo_ai_woo_sanitized_content` — modify final sanitized output (receives cleaned + original content)
- Filter `geo_ai_woo_sanitize_patterns` — add custom regex patterns for third-party page builders

### Changed — Integration
- `class-llms-generator.php`: `get_content()` now uses `Content_Sanitizer::sanitize()` for full content and excerpts
- `class-ai-generator.php`: `build_prompt()` now uses `Content_Sanitizer::sanitize()` instead of separate `strip_shortcodes()` + `wp_strip_all_tags()`
- `class-woocommerce.php`: `build_product_description()` now uses `Content_Sanitizer::sanitize()` for short descriptions
- Version bump 0.5.3 → 0.5.4

### Added — New Files
- `includes/class-content-sanitizer.php` — Content sanitization pipeline

## [0.5.3] - 2026-03-05

### Fixed — WordPress Plugin Check Compliance
- Replaced interpolated `$table_name` in SQL queries with `%i` identifier placeholder via `$wpdb->prepare()` in `class-crawl-tracker.php` (`drop_table`, `get_recent_activity`, `get_total_visits`, `cleanup_old_records`)
- Replaced interpolated `$table_name` in `DROP TABLE` query with `%i` placeholder in `uninstall.php`
- Fixed phpcs:ignore placement for postmeta `DELETE` query in `uninstall.php`

### Changed
- Minimum WordPress version raised from 6.0 to 6.2 (required for `%i` identifier placeholder support in `$wpdb->prepare()`)
- Version bump 0.5.2 → 0.5.3

## [0.5.2] - 2026-03-04

### Added — New AI Crawlers
- claude-web (Anthropic / Claude Web)
- Amazonbot (Amazon / Alexa)
- Applebot (Apple / Siri & Spotlight)

### Changed
- Version bump 0.5.1 → 0.5.2
- Supported AI crawlers expanded from 13 to 16

## [0.5.1] - 2026-03-04

### Fixed — WordPress Plugin Check Compliance
- Fixed unescaped URL output in SEO meta tags (`class-seo-headers.php`) — `esc_url()` now applied at echo, not at assignment
- Fixed `translators:` comment placement in `class-admin-notices.php` and `class-settings.php` — moved directly above `__()` / `esc_html__()` calls
- Fixed unsanitized nonce input in `class-meta-box.php`, `class-bulk-edit.php`, and `class-woocommerce.php` — added `sanitize_text_field()` before `wp_verify_nonce()`
- Fixed unprefixed global variables in `uninstall.php` — wrapped cleanup logic in `geo_ai_woo_uninstall()` function
- Added PHPCS ignore annotations for legitimate direct DB queries in `class-crawl-tracker.php` (custom table operations)
- Added PHPCS ignore annotations for third-party WPML/TranslatePress hooks and globals in `class-multilingual.php`
- Expanded PHPCS ignore annotations in `uninstall.php` for `DirectQuery`, `NoCaching`, `SchemaChange`, and `UnfinishedPrepare`

### Changed
- Version bump 0.5.0 → 0.5.1

## [0.5.0] - 2026-03-03

### Added — New AI Crawlers
- OAI-SearchBot (OpenAI / Copilot Search)
- DeepSeekBot (DeepSeek)
- GrokBot (xAI / Grok)
- meta-externalagent (Meta / LLaMA)
- PanguBot (Alibaba / Qwen)

### Changed
- Version bump 0.4.1 → 0.5.0
- Supported AI crawlers expanded from 8 to 13
- Default bot_rules now include all 13 crawlers (set to "allow" by default)
- Existing installations receive new bot rules via settings migration on update

## [0.4.1] - 2026-03-03

### Added — Localization
- Turkish (tr_TR) translation
- Spanish (es_ES) translation
- Brazilian Portuguese (pt_BR) translation

## [0.4.0] - 2026-03-02

### Fixed — Encoding
- Fixed Cyrillic and special character encoding in llms.txt output
- HTML entities (`&#x20B8;`, `&#8212;`, `&#187;`, etc.) are now properly decoded to UTF-8 characters
- Tenge symbol (₸), em dashes (—), guillemets (») and other non-ASCII characters display correctly

### Added — WordPress.org Compliance
- "Third-Party Services" disclosure section in readme.txt (Anthropic and OpenAI API usage, ToS and Privacy Policy links)
- API data disclosure notice in AI Description Generation settings section
- Links to Anthropic and OpenAI privacy policies in admin UI

### Removed
- `load_plugin_textdomain()` call — translations are loaded automatically by WordPress.org

### Changed
- Version bump 0.3.0 → 0.4.0
- Reduced readme.txt tags from 9 to 5 (WordPress.org guideline 12 compliance)
- Updated "Tested up to" to WordPress 6.9

## [0.3.0] - 2026-03-02

### Added — Multilingual Support
- WPML, Polylang, and TranslatePress abstraction layer
- Per-language `llms-{lang}.txt` and `llms-full-{lang}.txt` static file generation
- Hreflang alternate `<link>` tags in SEO meta output
- Language-aware HTTP Link header
- Configurable multilingual toggle in Advanced Settings
- Filter `geo_ai_woo_multilingual_provider` for custom provider override

### Added — Dashboard Widget & Statistics
- WordPress Dashboard widget with content overview (indexed/excluded counts, file status)
- AI bot crawl tracker with database-backed visit logging
- Bot activity summary for last 30 days in dashboard widget
- GDPR-compliant IP anonymization (hashed, not stored raw)
- Auto-cleanup of tracking records older than 90 days
- Quick links to Settings and View llms.txt in dashboard widget
- Configurable crawl tracking toggle in Advanced Settings

### Added — REST API
- `GET /wp-json/geo-ai-for-woocommerce/v1/llms` — public llms.txt content
- `GET /wp-json/geo-ai-for-woocommerce/v1/llms/full` — public llms-full.txt content
- `GET /wp-json/geo-ai-for-woocommerce/v1/status` — admin-only file status and statistics
- `POST /wp-json/geo-ai-for-woocommerce/v1/regenerate` — admin-only force regeneration (rate-limited)
- `GET /wp-json/geo-ai-for-woocommerce/v1/settings` — admin-only current settings (API key masked)

### Added — WP-CLI Commands
- `wp geo-ai-for-woocommerce regenerate` — regenerate all llms.txt files
- `wp geo-ai-for-woocommerce status` — show file status, content counts, multilingual info
- `wp geo-ai-for-woocommerce export [--file=path]` — export settings to JSON (excludes API keys)
- `wp geo-ai-for-woocommerce import <file> [--regenerate]` — import settings with key validation

### Added — AI Auto-Generation
- Claude (Anthropic) and OpenAI API integration for AI description generation
- "Generate with AI" button in post meta box and WooCommerce product panel
- Customizable prompt template with `{title}`, `{content}`, `{type}` placeholders
- Bulk generation for posts without descriptions (up to 50, batched)
- Progress bar UI for bulk generation
- Rate limiting (10 requests/minute)
- Encrypted API key storage (base64)
- Settings section: provider, API key, model, max tokens, prompt template

### Added — New Files
- `includes/class-multilingual.php` — WPML/Polylang/TranslatePress abstraction
- `includes/class-dashboard-widget.php` — Dashboard stats widget
- `includes/class-crawl-tracker.php` — AI bot visit tracking
- `includes/class-rest-api.php` — REST API endpoints
- `includes/class-cli.php` — WP-CLI commands
- `includes/class-ai-generator.php` — Claude/OpenAI AI generation

### Changed
- Version bump 0.2.0 → 0.3.0
- Settings migration adds v0.3 defaults (multilingual, crawl tracking, AI generation)
- `generate()` method now accepts optional `$lang_code` parameter
- `write_static_files()` generates per-language files when multilingual is active
- `regenerate_cache()` stores `geo_ai_woo_last_regenerated` timestamp
- `serve_llms_txt()` fallback now logs bot visits via crawl tracker
- Admin JS extended with AI generate button and bulk generate handlers
- Uninstall cleanup extended for crawl table, multilingual files, and new transients

## [0.2.0] - 2026-03-02

### Added — Architecture & Performance
- Static llms.txt and llms-full.txt file generation to WordPress root for maximum performance
- WooCommerce HPOS (High-Performance Order Storage) compatibility declaration
- Lazy WooCommerce integration loading (deferred to `init` hook)
- Automatic settings migration from v0.1 to v0.2

### Added — llms.txt Enhancements
- robots.txt integration with per-bot User-agent/Allow/Disallow directives
- Categories, tags, and WooCommerce product taxonomies in llms.txt
- Configurable taxonomy inclusion setting
- Site URL and file links in llms.txt header section
- Filter `geo_ai_woo_taxonomies` for custom taxonomy control

### Added — WooCommerce Extended Integration
- Variable products support with price ranges (min – max)
- Product reviews and average ratings in auto-generated descriptions
- Sale price display: "Price: $35 (was $50)"
- Available variation attributes (e.g., "Color: Red, Blue, Green")
- Hide out-of-stock products option (follows WC setting or override)
- Enhanced product schema with aggregateRating data

### Added — Admin & UX
- Live preview of llms.txt content on settings page via AJAX
- "AI Status" column in posts/pages/products list tables
- Quick Edit support for AI Description, AI Keywords, and Exclude flag
- Admin notice on plugin activation with settings page link
- File health notice when llms.txt is missing or older than 7 days
- Permalink structure warning when set to "Plain"
- Dismissible notices with AJAX persistence (30-day memory)

### Added — SEO & AI Visibility
- `<meta name="llms">` and `<meta name="llms-full">` tags in page `<head>`
- Per-post `<meta name="ai-description">` and `<meta name="ai-keywords">` tags
- HTTP Link header: `Link: <.../llms.txt>; rel="ai-content-index"`
- JSON-LD Schema.org structured data (WebSite on front page, Article/Product on singles)
- Automatic detection of SEO plugins (Yoast, Rank Math, AIOSEO, SEOPress) to avoid schema conflicts
- 6 new configurable settings for SEO features

### Added — New Files
- `includes/class-seo-headers.php` — Meta tags, HTTP headers, JSON-LD
- `includes/class-bulk-edit.php` — List table columns, Quick Edit integration
- `includes/class-admin-notices.php` — Activation, health, and permalink notices

## [0.1.0] - 2026-03-02

### Added
- llms.txt and llms-full.txt automatic generation
- AI meta box for posts, pages, and custom post types
- Bot rules configuration for 8 AI crawlers (GPTBot, ClaudeBot, Google-Extended, PerplexityBot, YandexBot, SputnikBot, Bytespider, Baiduspider)
- WooCommerce product data panel with AI optimization
- Auto-generate product descriptions from product data
- Enhanced product schema for AI readability
- Configurable cache with 4 frequency options
- Settings page under Settings > GEO AI Search Optimization
- Plugin action link for quick access to settings
- Multilingual support with 7 languages (EN, RU, KK, UZ, ZH, ID, HI)
- Uninstall cleanup for options, transients, and post meta
