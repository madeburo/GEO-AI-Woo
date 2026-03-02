# GEO AI Woo

**Generative Engine Optimization for WordPress & WooCommerce**

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-purple.svg)](https://woocommerce.com/)

An open-source WordPress plugin that optimizes your site for AI search engines like ChatGPT, Claude, Gemini, Perplexity, YandexGPT, GigaChat, and more.

🌏 **Focus:** **WooCommerce-first** | **Zero-config setup**

---

## Features

### llms.txt Generator

Automatically generates `/llms.txt` and `/llms-full.txt` files that help AI systems understand your content.

**Supported AI Crawlers:**
| Bot | Provider |
|-----|----------|
| GPTBot | OpenAI / ChatGPT |
| ClaudeBot | Anthropic / Claude |
| Google-Extended | Google / Gemini |
| PerplexityBot | Perplexity AI |
| YandexBot | Yandex / YandexGPT |
| SputnikBot | Sber / GigaChat |
| Bytespider | ByteDance / Douyin |
| Baiduspider | Baidu / ERNIE |

### AI Meta Box

Add AI-specific metadata to pages, posts, and products:

- **AI Description** — Concise summary for LLMs
- **AI Keywords** — Topics and context
- **Exclude from AI** — Opt-out specific content from being indexed

### WooCommerce Integration

- Products are automatically included in llms.txt
- Enhanced Product Schema for AI readability
- Category and attribute mapping

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

| Language         | Locale | Status      |
| ---------------- | ------ | ----------- |
| English          | en_US  | ✅ Complete |
| Русский          | ru_RU  | ✅ Complete |
| Қазақша          | kk     | ✅ Complete |
| O'zbekcha        | uz_UZ  | ✅ Complete |
| 简体中文         | zh_CN  | ✅ Complete |
| Bahasa Indonesia | id_ID  | ✅ Complete |
| हिन्दी           | hi_IN  | ✅ Complete |

---

## Requirements

- PHP 7.4 or higher
- WordPress 6.0 or higher
- WooCommerce 7.0+ (optional, for e-commerce features)

---

## File Structure

```text
geo-ai-woo/
├── geo-ai-woo.php              # Main plugin file
├── includes/                   # Core functionality classes
│   ├── class-llms-generator.php
│   ├── class-meta-box.php
│   ├── class-settings.php
│   └── class-woocommerce.php
├── languages/                  # Translation files
├── assets/                     # CSS and JS for admin
├── README.md                   # This documentation
├── CHANGELOG.md                # List of changes
├── LICENSE                     # GPL v2 License
├── readme.txt                  # WordPress.org plugin directory format
└── uninstall.php               # Cleanup script on plugin deletion
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
