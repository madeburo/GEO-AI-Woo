=== GEO AI Search Optimization ===
Contributors: madeburo
Tags: ai seo, llms.txt, chatgpt, wordpress, ai search
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI Search Optimization for WordPress & WooCommerce – optimize your site for ChatGPT, Claude, Gemini, Perplexity, Grok, DeepSeek and more.

== Description ==

GEO AI Search Optimization generates `/llms.txt` and `/llms-full.txt` files that help AI search engines understand your content. It supports ChatGPT, Claude, Gemini, Perplexity, YandexGPT, GigaChat, and more.

**Features:**

* Static llms.txt file generation for maximum performance
* AI meta box for posts, pages, and products
* Per-bot crawler permissions (allow/disallow)
* Automatic robots.txt integration with AI bot rules
* WooCommerce integration with variable products, reviews, and sale prices
* Enhanced product schema for AI readability
* SEO meta tags, HTTP Link headers, and JSON-LD structured data
* Categories and taxonomies in llms.txt
* Bulk edit support with AI Status column and Quick Edit
* Live preview of llms.txt on settings page
* Admin notices and file health checks
* Configurable cache and regeneration
* WooCommerce HPOS compatibility
* Multilingual support (WPML, Polylang, TranslatePress)
* REST API for programmatic access
* WP-CLI commands for terminal management
* AI auto-generation of descriptions (Claude / OpenAI)
* Dashboard widget with statistics and bot tracking
* Crawl tracker with GDPR-compliant IP anonymization
* Content sanitization pipeline — cleans page builder markup, shortcodes, scripts, base64, and mojibake from AI output

**Supported AI Crawlers (16):**

* GPTBot (OpenAI / ChatGPT)
* OAI-SearchBot (OpenAI / Copilot Search)
* ClaudeBot (Anthropic / Claude)
* claude-web (Anthropic / Claude Web)
* Google-Extended (Google / Gemini)
* PerplexityBot (Perplexity AI)
* DeepSeekBot (DeepSeek)
* GrokBot (xAI / Grok)
* meta-externalagent (Meta / LLaMA)
* PanguBot (Alibaba / Qwen)
* YandexBot (Yandex / YandexGPT)
* SputnikBot (Sber / GigaChat)
* Bytespider (ByteDance / Douyin)
* Baiduspider (Baidu / ERNIE)
* Amazonbot (Amazon / Alexa)
* Applebot (Apple / Siri & Spotlight)

== Use Cases ==

* Make your WooCommerce store visible to AI search
* Optimize your WordPress site for ChatGPT discovery
* Generate llms.txt automatically
* Improve AI understanding of your content

== Installation ==

1. Upload the `geo-ai-for-woocommerce` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > GEO AI Search Optimization to configure

The plugin works out of the box with sensible defaults.

== Third-Party Services ==

This plugin optionally connects to external AI services for generating content descriptions. These connections are **disabled by default** and only activate when you explicitly configure an AI provider in Settings > GEO AI Search Optimization > AI Description Generation.

= Anthropic (Claude) =

When you select Claude as your AI provider and click "Generate with AI", the plugin sends your post title and content excerpt to the Anthropic API to generate an AI-optimized description.

* API endpoint: `https://api.anthropic.com/v1/messages`
* [Anthropic Terms of Service](https://www.anthropic.com/terms)
* [Anthropic Privacy Policy](https://www.anthropic.com/privacy)

= OpenAI =

When you select OpenAI as your AI provider and click "Generate with AI", the plugin sends your post title and content excerpt to the OpenAI API to generate an AI-optimized description.

* API endpoint: `https://api.openai.com/v1/chat/completions`
* [OpenAI Terms of Use](https://openai.com/terms)
* [OpenAI Privacy Policy](https://openai.com/privacy)

No data is sent to any external service unless you explicitly enable and configure the AI Description Generation feature. Your API key is stored encrypted in the WordPress database and is never exposed in the admin interface.

== Frequently Asked Questions ==

= What is llms.txt? =

llms.txt is a proposed standard that provides AI systems with a structured overview of your site content, similar to how robots.txt works for search engine crawlers.

= Does this plugin require WooCommerce? =

No. WooCommerce integration is optional. The plugin works with standard WordPress posts and pages. WooCommerce features activate automatically when WooCommerce is installed.

= How often is llms.txt regenerated? =

By default, it regenerates daily. You can change this to immediate (on every post save), hourly, or weekly in Settings > GEO AI Search Optimization > Cache Settings.

= Can I exclude specific content from llms.txt? =

Yes. Each post, page, and product has a "GEO AI Search Optimization" meta box where you can check "Exclude from AI indexing". You can also use Quick Edit in list tables for bulk changes.

= Does this plugin work with my SEO plugin? =

Yes. The plugin detects major SEO plugins (Yoast, Rank Math, All in One SEO, SEOPress) and skips JSON-LD schema output to avoid conflicts. Meta tags and HTTP headers work alongside any SEO plugin.

= How are static files generated? =

The plugin writes `llms.txt` and `llms-full.txt` directly to your WordPress root directory for maximum performance. If the files cannot be written, it falls back to serving content via WordPress rewrite rules.

= Can I hide out-of-stock products from llms.txt? =

Yes. Go to Settings > GEO AI Search Optimization and set the "Out-of-Stock Products" option to "Always hide" or let it follow your WooCommerce visibility settings.

= Can I auto-generate AI descriptions for my content? =

Yes. Go to Settings > GEO AI Search Optimization > AI Description Generation, choose Claude (Anthropic) or OpenAI as your provider, and enter your API key. A "Generate with AI" button will appear in the meta box on each post/page/product. You can also bulk-generate descriptions for all content at once from the settings page.

= Is there a REST API or CLI access? =

Yes. The plugin exposes a REST API at `/wp-json/geo-ai-for-woocommerce/v1/` with endpoints for reading llms.txt content, checking file status, and triggering regeneration. WP-CLI commands are also available: `wp geo-ai-for-woocommerce regenerate`, `status`, `export`, and `import`.

== Screenshots ==

1. Settings page with bot rules configuration and SEO options
2. AI meta box in post editor
3. WooCommerce product data panel with variable product support
4. AI Status column in post list table with Quick Edit
5. Live preview of llms.txt content

== Changelog ==

= 0.7.0 =
**Plugin Rename**
* Display name changed from "GEO AI for WooCommerce" to "GEO AI Search Optimization"
* Updated all user-facing surfaces: settings page, meta box, dashboard widget, admin notices, CLI, Quick Edit, llms.txt footer, robots.txt
* Slug, text domain, and internal keys unchanged for backward compatibility

**Fixed — UTF-8 / Cyrillic Encoding**
* Fixed mojibake from double UTF-8 encoding (detects and repairs latin1→UTF-8→UTF-8 corruption)
* Static file writer now validates UTF-8 integrity before writing
* Excerpt, full content, and taxonomy descriptions now properly decoded for plain text output

= 0.6.0 =
**Plugin Rename — WordPress Plugin Review**
* Plugin renamed from "GEO AI Woo" to "GEO AI Search Optimization"
* Slug changed from geo-ai-woo to geo-ai-for-woocommerce everywhere
* Text domain updated to geo-ai-for-woocommerce
* Main plugin file renamed to geo-ai-for-woocommerce.php
* REST API namespace, WP-CLI command, asset handles, CSS classes updated
* Language files renamed to geo-ai-for-woocommerce-*.po/mo
* Added Requires Plugins: woocommerce header
* CLI export now writes to uploads directory

**Fixed — Security**
* Removed unsafe flags from wp_json_encode() in JSON-LD output
* Fixed unescaped API key field output in settings page

**Fixed — UTF-8 Encoding**
* Fixed Cyrillic and multibyte character corruption in static llms.txt files (mojibake)
* Replaced WP_Filesystem file writing with direct file_put_contents to preserve UTF-8 encoding
* Added per-field HTML entity decoding (decode_text) for titles, descriptions, keywords, and taxonomy names

**Improved — Duplicate Plugin Protection**
* Added version constant guard to prevent fatal errors when multiple copies are installed
* Added admin notice warning when duplicate plugin copies are detected

**Changed**
* Default OpenAI model placeholder updated to GPT-5
* Added German (de_DE) and French (fr_FR) translations

= 0.5.5 =
**Localization**
* Added German (de_DE) translation
* Added French (fr_FR) translation

= 0.5.4.1 =
**Machine Readability**
* Crawler rules in llms.txt now use plain ASCII `Allowed` / `Blocked` instead of UTF-8 symbols for maximum parser compatibility

= 0.5.4 =
**Content Sanitization**
* New centralized content sanitization pipeline for all AI-facing output
* Removes page builder markup: WP Bakery, Divi, Beaver Builder, Elementor/Gutenberg comments
* Removes registered and unregistered WordPress shortcodes (paired and self-closing)
* Strips `<script>` and `<style>` tags with their contents
* Removes inline base64-encoded data (embedded images, fonts)
* Fixes mojibake artifacts from double UTF-8 encoding (curly quotes, em/en dashes, ellipsis)
* Decodes HTML entities to proper UTF-8 characters
* Normalizes whitespace
* Extensible via filters: `geo_ai_woo_pre_sanitize`, `geo_ai_woo_sanitized_content`, `geo_ai_woo_sanitize_patterns`
* Integrated into llms.txt generator, AI prompt builder, and WooCommerce product descriptions

= 0.5.3 =
**WordPress Plugin Check Compliance**
* Replaced interpolated table names in SQL queries with `%i` identifier placeholder (`$wpdb->prepare()`)
* Fixed `class-crawl-tracker.php`: `drop_table`, `get_recent_activity`, `get_total_visits`, `cleanup_old_records`
* Fixed `uninstall.php`: `DROP TABLE` query now uses `%i` placeholder
* Minimum WordPress version raised from 6.0 to 6.2

= 0.5.2 =
**New AI Crawlers**
* Added claude-web (Anthropic / Claude Web)
* Added Amazonbot (Amazon / Alexa)
* Added Applebot (Apple / Siri & Spotlight)
* Supported AI crawlers expanded from 13 to 16

= 0.5.1 =
**WordPress Plugin Check Compliance**
* Fixed unescaped URL output in SEO meta tags
* Fixed translators comment placement for i18n functions
* Fixed unsanitized nonce input before wp_verify_nonce()
* Fixed unprefixed global variables in uninstall.php
* Added PHPCS annotations for custom table queries and third-party plugin hooks

= 0.5.0 =
**New AI Crawlers**
* Added OAI-SearchBot (OpenAI / Copilot Search)
* Added DeepSeekBot (DeepSeek)
* Added GrokBot (xAI / Grok)
* Added meta-externalagent (Meta / LLaMA)
* Added PanguBot (Alibaba / Qwen)
* Supported AI crawlers expanded from 8 to 13

= 0.4.1 =
**Localization**
* Added Turkish (tr_TR) translation
* Added Spanish (es_ES) translation
* Added Brazilian Portuguese (pt_BR) translation

= 0.4.0 =
**Bug Fix**
* Fixed Cyrillic and special character encoding in llms.txt (HTML entities now properly decoded to UTF-8)

**WordPress.org Compliance**
* Added "Third-Party Services" disclosure section with Anthropic and OpenAI privacy policies
* Added API data disclosure notice in AI Description Generation settings
* Reduced readme tags to 5 (guideline 12)
* Removed `load_plugin_textdomain()` — translations handled automatically by WordPress.org
* Updated "Tested up to" to WordPress 6.9

= 0.3.0 =
**Multilingual Support**
* WPML, Polylang, and TranslatePress integration
* Per-language llms.txt and llms-full.txt file generation
* Hreflang alternate links in SEO meta tags
* Language-aware HTTP Link header

**Dashboard Widget & Statistics**
* Dashboard widget with content overview (indexed/excluded counts)
* AI bot crawl tracking with visit logging
* GDPR-compliant IP anonymization via hashing
* Bot activity summary (last 30 days)
* Auto-cleanup of tracking records older than 90 days

**REST API**
* GET /wp-json/geo-ai-for-woocommerce/v1/llms — public llms.txt content
* GET /wp-json/geo-ai-for-woocommerce/v1/llms/full — public full content
* GET /wp-json/geo-ai-for-woocommerce/v1/status — admin file status and statistics
* POST /wp-json/geo-ai-for-woocommerce/v1/regenerate — admin force regeneration
* GET /wp-json/geo-ai-for-woocommerce/v1/settings — admin current settings
* Rate limiting on regeneration endpoint

**WP-CLI Commands**
* `wp geo-ai-for-woocommerce regenerate` — regenerate llms.txt files
* `wp geo-ai-for-woocommerce status` — show file status, content counts, multilingual info
* `wp geo-ai-for-woocommerce export` — export settings to JSON file
* `wp geo-ai-for-woocommerce import` — import settings from JSON file

**AI Auto-Generation**
* Claude (Anthropic) and OpenAI API integration
* "Generate with AI" button in meta box and WooCommerce product panel
* Customizable prompt template with {title}, {content}, {type} placeholders
* Bulk generation for all posts without descriptions (up to 50 posts)
* Rate limiting (10 requests per minute)
* Encrypted API key storage
* Progress bar for bulk generation

**Settings**
* New "AI Description Generation" settings section
* New "Advanced Settings" section (multilingual, crawl tracking)
* API provider, key, model, max tokens, and prompt template configuration

= 0.2.0 =
**Architecture & Performance**
* Static llms.txt file generation (no more rewrite rules dependency)
* WooCommerce HPOS (High-Performance Order Storage) compatibility
* Lazy WooCommerce integration loading for better performance
* Settings migration from v0.1 to v0.2

**llms.txt Enhancements**
* robots.txt integration with per-bot Allow/Disallow directives
* Categories, tags, and product taxonomies included in llms.txt
* Configurable taxonomy inclusion setting
* Site URL and file links in llms.txt header

**WooCommerce Extended Integration**
* Variable products support with price ranges
* Product reviews and ratings in descriptions
* Sale price display (regular vs. sale price)
* Available variation attributes (sizes, colors, etc.)
* Hide out-of-stock products option (with WooCommerce setting integration)
* Enhanced product schema with aggregate ratings

**Admin & UX**
* Live preview of llms.txt on settings page
* AI Status column in post/page/product list tables
* Quick Edit support for AI Description, Keywords, and Exclude flag
* Admin notices: activation, file health, permalink structure warnings
* Dismissible notices with AJAX

**SEO & AI Visibility**
* `<meta name="llms">` and `<meta name="ai-description">` tags in page head
* HTTP Link header pointing to llms.txt (`rel="ai-content-index"`)
* JSON-LD Schema.org structured data (WebSite + Article/Product)
* Automatic SEO plugin detection (Yoast, Rank Math, AIOSEO, SEOPress)
* Per-post AI keywords meta tag

= 0.1.0 =
* Initial release
* llms.txt and llms-full.txt generation
* AI meta box for posts, pages, and products
* Bot rules configuration (8 AI crawlers)
* WooCommerce basic integration
* Cache management with configurable frequency
* Multilingual support (7 languages)

== Upgrade Notice ==

= 0.7.0 =
Plugin renamed to "GEO AI Search Optimization". Improved Cyrillic/UTF-8 encoding in llms.txt. Regenerate your files after updating.

= 0.6.0 =
Major rename: plugin is now "GEO AI Search Optimization" with slug geo-ai-for-woocommerce. Fixes JSON-LD escaping, file write security, and UTF-8 encoding. Deactivate the old version before activating this one.

= 0.5.5 =
Added German (de_DE) and French (fr_FR) translations.

= 0.5.4.1 =
Crawler rules in llms.txt now use plain ASCII instead of UTF-8 symbols for better machine readability. Regenerate your llms.txt after updating.

= 0.5.4 =
New content sanitization pipeline cleans page builder markup (WP Bakery, Divi, Elementor, Beaver Builder), shortcodes, scripts, base64, and mojibake from all AI-facing output. Regenerate your llms.txt after updating.

= 0.5.3 =
Fixes all remaining WordPress Plugin Check warnings: SQL queries now use %i identifier placeholders instead of interpolated table names. Minimum WP version raised to 6.2.

= 0.5.2 =
Adds support for 3 new AI crawlers: claude-web (Anthropic), Amazonbot (Amazon/Alexa), and Applebot (Apple/Siri). Total supported crawlers: 16. Regenerate your llms.txt after updating.

= 0.5.1 =
Fixes all errors and warnings from WordPress Plugin Check: output escaping, nonce sanitization, translators comments, global variable prefixing, and PHPCS annotations.

= 0.5.0 =
Adds support for 5 new AI crawlers: DeepSeek, Grok (xAI), Meta/LLaMA, Copilot Search, and Alibaba/Qwen. Total supported crawlers: 13. Regenerate your llms.txt after updating.

= 0.4.1 =
Added Turkish, Spanish, and Brazilian Portuguese translations.

= 0.4.0 =
Fixes Cyrillic/special character encoding in llms.txt. Adds WordPress.org plugin guidelines compliance (third-party service disclosures). Regenerate your llms.txt after updating.

= 0.3.0 =
New features: multilingual support (WPML/Polylang/TranslatePress), REST API, WP-CLI commands, AI auto-generation (Claude/OpenAI), dashboard widget with bot tracking. Settings are automatically migrated.

= 0.2.0 =
Major update with static file generation, robots.txt integration, extended WooCommerce support (variable products, reviews, sale prices), SEO meta tags, JSON-LD, bulk edit, and live preview. Settings are automatically migrated.

= 0.1.0 =
Initial release.
