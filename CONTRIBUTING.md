# Contributing to GEO AI Woo

Thanks for your interest in contributing! GEO AI Woo is an open-source WordPress plugin and we welcome pull requests, bug reports, and feature suggestions.

## Getting Started

```bash
# Clone the repository
git clone https://github.com/madeburo/geo-ai-woo.git

# Symlink or copy into your local WordPress installation
ln -s /path/to/geo-ai-woo /path/to/wordpress/wp-content/plugins/geo-ai-woo

# Activate
wp plugin activate geo-ai-woo
```

There is no build step — all PHP, JS, and CSS files are used directly as-is.

## Requirements

- PHP 7.4+
- WordPress 6.2+
- WooCommerce 7.0+ (optional, for e-commerce features)

## Project Structure

```
geo-ai-woo/
├── geo-ai-woo.php          # Main plugin file — bootstrap, constants, singleton
├── uninstall.php            # Cleanup on plugin deletion
├── includes/                # PHP classes (one per file, Geo_Ai_Woo_ prefix)
├── assets/css/admin.css     # Admin styles
├── assets/js/admin.js       # Admin JavaScript (ES5, jQuery)
└── languages/               # .pot template + .po/.mo translations
```

## Coding Standards

This project follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/):

- Tabs for indentation, spaces inside parentheses
- `snake_case` for functions and variables
- All output escaped: `esc_html()`, `esc_attr()`, `esc_url()`, `esc_textarea()`
- All input sanitized: `sanitize_text_field()`, `sanitize_textarea_field()`, `absint()`
- Nonces verified before any data save
- Capability checks before privileged actions
- `defined( 'ABSPATH' ) || exit;` at the top of every PHP file

### Naming Conventions

- Classes: `Geo_Ai_Woo_` prefix (e.g., `Geo_Ai_Woo_Settings`)
- Hooks/filters: `geo_ai_woo_` prefix
- Options/transients: `geo_ai_woo_` prefix
- Post meta keys: `_geo_ai_woo_` prefix (leading underscore hides from custom fields UI)

## Submitting Changes

### Bug Reports

Open an [issue](https://github.com/madeburo/geo-ai-woo/issues) with:

- WordPress and PHP version
- WooCommerce version (if applicable)
- Steps to reproduce
- Expected vs. actual behavior

### Pull Requests

1. Fork the repository
2. Create a feature branch from `main`: `git checkout -b feature/your-feature`
3. Make your changes following the coding standards above
4. Test on a local WordPress installation
5. Commit with a clear message: `git commit -m "Add: brief description"`
6. Push and open a PR against `main`

### Commit Message Format

```
Add: new feature description
Fix: bug description
Update: what was changed
Remove: what was removed
```

## Translations

Translation files live in `languages/`. To add a new locale:

1. Copy `languages/geo-ai-woo.pot` to `languages/geo-ai-woo-{locale}.po`
2. Translate the strings using [Poedit](https://poedit.net/) or a similar tool
3. Compile the `.mo` file
4. Submit a PR with both `.po` and `.mo` files

Currently supported: en_US, ru_RU, kk, uz_UZ, zh_CN, id_ID, hi_IN, tr_TR, es_ES, pt_BR.

## Architecture Notes

- Every class uses the singleton pattern (`instance()` / `__construct()`)
- Admin-only classes load inside `is_admin()` checks
- WooCommerce class is lazy-loaded via `class_exists('WooCommerce')`
- WP-CLI class loads only when `defined('WP_CLI') && WP_CLI`
- No Composer, no npm, no autoloader — plain `require_once`

## License

By contributing, you agree that your contributions will be licensed under the [GPL v2](LICENSE).
