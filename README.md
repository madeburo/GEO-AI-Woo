# GEO AI Woo

![GEO AI Woo](Geo-AI-Woo.png)

**Generative Engine Optimization for WordPress & WooCommerce**

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-purple.svg)](https://woocommerce.com/)

An open-source WordPress plugin that optimizes your site for AI search engines like ChatGPT, Claude, Gemini, Perplexity, DeepSeek, Grok, YandexGPT, GigaChat, Apple Siri, Amazon Alexa, and more.

**Focus:** **WooCommerce-first** | **Zero-config setup**

---

## Features

### llms.txt Generator

Automatically generates `/llms.txt` and `/llms-full.txt` static files at your WordPress root for maximum performance. Falls back to rewrite rules if the filesystem is not writable.

**Supported AI Crawlers:**
| Bot | Provider |
|-----|----------|
| GPTBot | OpenAI / ChatGPT |
| OAI-SearchBot | OpenAI / Copilot Search |
| ClaudeBot | Anthropic / Claude |
| claude-web | Anthropic / Claude Web |
| Google-Extended | Google / Gemini |
| PerplexityBot | Perplexity AI |
| DeepSeekBot | DeepSeek |
| GrokBot | xAI / Grok |
| meta-externalagent | Meta / LLaMA |
| PanguBot | Alibaba / Qwen |
| YandexBot | Yandex / YandexGPT |
| SputnikBot | Sber / GigaChat |
| Bytespider | ByteDance / Douyin |
| Baiduspider | Baidu / ERNIE |
| Amazonbot | Amazon / Alexa |
| Applebot | Apple / Siri & Spotlight |

### AI Meta Box

Add AI-specific metadata to any post, page, or product:

- **AI Description** — Concise summary for LLMs (max 200 characters)
- **AI Keywords** — Topics and context hints
- **Exclude from AI** — Opt specific content out of llms.txt entirely
- **Generate with AI** — One-click description generation via Claude or OpenAI

### WooCommerce Integration

- Products automatically included in llms.txt with price, stock status, ratings, and attributes
- Variable product support with price ranges and available variations
- Sale price display (regular vs. sale)
- Enhanced product Schema.org markup for AI readability
- Dedicated GEO AI tab in the product data panel

### AI Auto-Generation

- Generate AI descriptions via **Claude (Anthropic)** or **OpenAI** APIs
- Customizable prompt template with `{title}`, `{content}`, `{type}` placeholders
- Bulk generation for all posts without descriptions (up to 50 at a time)
- Rate limiting and encrypted API key storage

### SEO & AI Visibility

- `<meta name="llms">` and `<meta name="ai-description">` tags in page `<head>`
- HTTP `Link` header pointing to llms.txt (`rel="ai-content-index"`)
- JSON-LD Schema.org structured data (WebSite + Article/Product)
- Automatic detection of Yoast, Rank Math, AIOSEO, SEOPress to avoid conflicts
- Per-bot `robots.txt` directives (Allow/Disallow) based on your crawler settings

### Multilingual Support

- Compatible with **WPML**, **Polylang**, and **TranslatePress**
- Generates separate llms.txt files per language
- Hreflang alternate links in SEO meta tags and HTTP headers

### REST API & WP-CLI

- REST API at `/wp-json/geo-ai-woo/v1/` for programmatic access
- WP-CLI commands: `regenerate`, `status`, `export`, `import`

### Content Sanitization

Centralized content cleaning pipeline for all AI-facing output. Automatically strips page builder markup (WP Bakery, Divi, Elementor, Beaver Builder), shortcodes, `<script>`/`<style>` tags, base64 data, and mojibake artifacts. Extensible via WordPress filters for custom page builders.

### Dashboard & Tracking

- Dashboard widget with indexed/excluded content counts
- AI bot crawl tracking with GDPR-compliant IP anonymization
- Bot activity summary for the last 30 days
- Bulk Edit and Quick Edit support for AI fields in list tables

---

## Installation

### From GitHub

1. Download the latest release from the repository.
2. Unzip and upload the folder to your `/wp-content/plugins/geo-ai-woo/` directory.
3. Activate the plugin in the WordPress admin panel.
4. Go to **Settings → GEO AI Woo** to configure.

---

## Configuration

### Basic Setup

After activation, the plugin works out of the box with sensible defaults:

- All public posts, pages, and products are included in llms.txt.
- All supported AI crawlers are allowed by default.

### Settings Page

Navigate to **Settings → GEO AI Woo** to configure:

- **Post Types**: Select which content types to include.
- **Bot Rules**: Allow or disallow specific AI crawlers.
- **Cache**: Set regeneration frequency.
- **WooCommerce**: Product-specific settings.

### AI Meta Box

Edit any post, page, or product to find the **GEO AI Woo** meta box to set specific AI contexts or exclude the content entirely.

---

## Localization

GEO AI Woo is fully translatable and includes:

| Language            | Locale | Status      |
| ---------------- | ------ | ----------- |
| English          | en_US  | ✅ Complete |
| Русский          | ru_RU  | ✅ Complete |
| Қазақша          | kk     | ✅ Complete |
| O'zbekcha        | uz_UZ  | ✅ Complete |
| 简体中文         | zh_CN  | ✅ Complete |
| Bahasa Indonesia | id_ID  | ✅ Complete |
| हिन्दी           | hi_IN  | ✅ Complete |
| Türkçe           | tr_TR  | ✅ Complete |
| Español          | es_ES  | ✅ Complete |
| Português (BR)   | pt_BR  | ✅ Complete |

---

## Requirements

- PHP 7.4 or higher
- WordPress 6.2 or higher
- WooCommerce 7.0+ (optional, for e-commerce features)

---

## File Structure

```text
geo-ai-woo/
├── geo-ai-woo.php              # Main plugin file — bootstrap, hooks, activation/deactivation
├── uninstall.php               # Cleanup on plugin deletion
├── includes/                   # All PHP classes (one per file)
│   ├── class-content-sanitizer.php  # Content sanitization pipeline for AI output
│   ├── class-llms-generator.php    # Core llms.txt generation and static file writing
│   ├── class-settings.php          # Admin settings page
│   ├── class-meta-box.php          # AI meta box for posts/pages
│   ├── class-woocommerce.php       # WooCommerce integration
│   ├── class-ai-generator.php      # Claude/OpenAI API integration
│   ├── class-seo-headers.php       # Meta tags, HTTP headers, JSON-LD
│   ├── class-multilingual.php      # WPML/Polylang/TranslatePress support
│   ├── class-crawl-tracker.php     # Bot visit logging and statistics
│   ├── class-rest-api.php          # REST API endpoints
│   ├── class-cli.php               # WP-CLI commands
│   ├── class-bulk-edit.php         # Bulk Edit / Quick Edit support
│   ├── class-dashboard-widget.php  # Dashboard widget
│   └── class-admin-notices.php     # Admin notices
├── assets/
│   ├── css/admin.css           # Admin styles
│   └── js/admin.js             # Admin JavaScript
├── languages/                  # Translation files (.pot, .po, .mo)
├── README.md                   # This file
├── CHANGELOG.md                # Version history
├── LICENSE                     # GPL v2
└── readme.txt                  # WordPress.org plugin directory format
```

---

## Contributing

Contributions are welcome! Please feel free to open issues or submit pull requests.

```bash
# Clone the repository
git clone https://github.com/madeburo/geo-ai-woo.git
```

---

## License

GEO AI Woo is open-source software licensed under the [GPL v2](LICENSE).

---

## Credits

Developed by [Made Büro](https://madeburo.com) — UI-first delivery studio.

- **Author:** Made Büro
- **Website:** [madeburo.com](https://madeburo.com)
- **GitHub:** [@madeburo](https://github.com/madeburo)
- **X:** [@imadeburo](https://x.com/imadeburo)
