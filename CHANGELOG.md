# Changelog

All notable changes to GEO AI Woo will be documented in this file.

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
- `GET /wp-json/geo-ai-woo/v1/llms` — public llms.txt content
- `GET /wp-json/geo-ai-woo/v1/llms/full` — public llms-full.txt content
- `GET /wp-json/geo-ai-woo/v1/status` — admin-only file status and statistics
- `POST /wp-json/geo-ai-woo/v1/regenerate` — admin-only force regeneration (rate-limited)
- `GET /wp-json/geo-ai-woo/v1/settings` — admin-only current settings (API key masked)

### Added — WP-CLI Commands
- `wp geo-ai-woo regenerate` — regenerate all llms.txt files
- `wp geo-ai-woo status` — show file status, content counts, multilingual info
- `wp geo-ai-woo export [--file=path]` — export settings to JSON (excludes API keys)
- `wp geo-ai-woo import <file> [--regenerate]` — import settings with key validation

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
- Settings page under Settings > GEO AI Woo
- Plugin action link for quick access to settings
- Multilingual support with 7 languages (EN, RU, KK, UZ, ZH, ID, HI)
- Uninstall cleanup for options, transients, and post meta
