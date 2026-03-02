=== GEO AI Woo ===
Contributors: madeburo
Tags: ai, seo, woocommerce, llms.txt, chatgpt, claude, gemini, perplexity
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generative Engine Optimization for WordPress & WooCommerce. Optimize your site for AI search engines.

== Description ==

GEO AI Woo generates `/llms.txt` and `/llms-full.txt` files that help AI search engines understand your content. It supports ChatGPT, Claude, Gemini, Perplexity, YandexGPT, GigaChat, and more.

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
* Multilingual support (7 languages)

**Supported AI Crawlers:**

* GPTBot (OpenAI / ChatGPT)
* ClaudeBot (Anthropic / Claude)
* Google-Extended (Google / Gemini)
* PerplexityBot (Perplexity AI)
* YandexBot (Yandex / YandexGPT)
* SputnikBot (Sber / GigaChat)
* Bytespider (ByteDance / Douyin)
* Baiduspider (Baidu / ERNIE)

== Installation ==

1. Upload the `geo-ai-woo` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > GEO AI Woo to configure

The plugin works out of the box with sensible defaults.

== Frequently Asked Questions ==

= What is llms.txt? =

llms.txt is a proposed standard that provides AI systems with a structured overview of your site content, similar to how robots.txt works for search engine crawlers.

= Does this plugin require WooCommerce? =

No. WooCommerce integration is optional. The plugin works with standard WordPress posts and pages. WooCommerce features activate automatically when WooCommerce is installed.

= How often is llms.txt regenerated? =

By default, it regenerates daily. You can change this to immediate (on every post save), hourly, or weekly in Settings > GEO AI Woo > Cache Settings.

= Can I exclude specific content from llms.txt? =

Yes. Each post, page, and product has a "GEO AI Woo" meta box where you can check "Exclude from AI indexing". You can also use Quick Edit in list tables for bulk changes.

= Does this plugin work with my SEO plugin? =

Yes. The plugin detects major SEO plugins (Yoast, Rank Math, All in One SEO, SEOPress) and skips JSON-LD schema output to avoid conflicts. Meta tags and HTTP headers work alongside any SEO plugin.

= How are static files generated? =

The plugin writes `llms.txt` and `llms-full.txt` directly to your WordPress root directory for maximum performance. If the files cannot be written, it falls back to serving content via WordPress rewrite rules.

= Can I hide out-of-stock products from llms.txt? =

Yes. Go to Settings > GEO AI Woo and set the "Out-of-Stock Products" option to "Always hide" or let it follow your WooCommerce visibility settings.

== Screenshots ==

1. Settings page with bot rules configuration and SEO options
2. AI meta box in post editor
3. WooCommerce product data panel with variable product support
4. AI Status column in post list table with Quick Edit
5. Live preview of llms.txt content

== Changelog ==

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

= 0.2.0 =
Major update with static file generation, robots.txt integration, extended WooCommerce support (variable products, reviews, sale prices), SEO meta tags, JSON-LD, bulk edit, and live preview. Settings are automatically migrated.

= 0.1.0 =
Initial release.
