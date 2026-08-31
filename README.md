# Frontend Theme Switcher

Frontend Theme Switcher is a free, open-source WordPress plugin by MediaGeni. It lets visitors preview administrator-approved installed themes without changing the site's globally active theme.

It is developed and maintained by [Vidal de Wit](https://mediageni.com/), founder of MediaGeni and a web developer with 25 years of experience.

## Features

- Visitor-specific theme previews stored in a functional cookie.
- Administrator allowlist for installed themes.
- Public or administrators-only access.
- Primary-navigation, floating, or manual placement.
- Compact, list, and select layouts with `[frontend_theme_switcher]`.
- Configurable session, 1-day, 7-day, or 30-day preference duration.
- Support for classic, block, parent, and child themes.
- Preview-specific Customizer settings without changing the active theme.
- Shared navigation across classic theme previews.
- No external services, tracking, or third-party assets.

## Requirements

- WordPress 6.4 or later.
- PHP 7.4 or later.

## Installation

The installable plugin is the `plugin/frontend-theme-switcher` directory.

1. Copy that directory to `wp-content/plugins/frontend-theme-switcher`, or create a ZIP containing it and upload the ZIP under **Plugins > Add New > Upload Plugin**.
2. Activate **Frontend Theme Switcher**.
3. Open **Settings > Theme Switcher** and choose the themes visitors may preview.

## Development

The runtime has no build step and no third-party dependencies. PHP, CSS, JavaScript, translations, and the WordPress.org `readme.txt` are kept in [`plugin/frontend-theme-switcher`](plugin/frontend-theme-switcher).

The `documentation` and `licensing` directories contain project documentation and licensing records; they are not required in the installable WordPress plugin ZIP.

## Privacy

The plugin does not collect personal data, add tracking, contact external services, or load third-party assets. It stores only the selected theme's stylesheet identifier in the `sgfts_theme` functional cookie for the administrator-selected period, never longer than 30 days. Choosing the site's default theme removes the cookie.

## Security

Please report suspected vulnerabilities privately through [GitHub's security advisory form](https://github.com/mediageni/frontend-theme-switcher/security/advisories/new). Do not disclose an unpatched vulnerability in a public issue.

## License

Copyright 2026 MediaGeni. Licensed under the [GNU General Public License v2.0 or later](LICENSE).
