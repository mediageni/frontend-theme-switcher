=== Frontend Theme Switcher - Visitor Theme Preview ===
Contributors: vidaldewit
Tags: theme switcher, theme preview, frontend, demo, showcase
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let visitors preview approved installed themes without changing the site's active theme.

== Description ==

Sometimes you want visitors to try a different look without changing the site for everyone. That is why I built Frontend Theme Switcher.

You choose which installed themes are available. Visitors can then switch between those approved themes from a simple selector on the frontend. Their choice applies only to their own browser; the active WordPress theme never changes.

**What it does**

* Visitor-specific theme previews.
* Administrator-approved themes only.
* Automatic placement after the first classic or block navigation, with a fixed fallback when a theme renders no navigation.
* One shared top navigation menu across classic theme previews.
* Shortcode fallback: `[frontend_theme_switcher]`.
* Support for parent themes, child themes, block themes, and classic themes.
* Each preview uses its own Customizer settings without changing the globally active theme.
* Automatic parent-theme hiding when an approved child theme is available.
* Clean URLs after a theme selection.
* No-cache headers for visitor-specific preview responses.
* Keyboard-accessible native details control.
* Tiny dependency-free interaction guard and no external services.

Development takes place in the public [GitHub repository](https://github.com/mediageni/frontend-theme-switcher), where the complete human-readable source is available.

**About the developer**

I am Vidal de Wit, founder of [MediaGeni](https://mediageni.com/) and a web developer with 25 years of experience. I built and maintain Frontend Theme Switcher as a free, focused plugin for the WordPress community.

== Installation ==

1. Upload the `frontend-theme-switcher` folder to `/wp-content/plugins/` or install its ZIP through Plugins > Add New.
2. Activate Frontend Theme Switcher.
3. Open Settings > Theme Switcher.
4. Select the installed themes visitors may preview.
5. Select the WordPress menu used as shared top navigation.
6. Keep automatic placement enabled, or place `[frontend_theme_switcher]` manually.

== Screenshots ==

1. Select approved installed themes, share navigation, and configure placement from one settings screen.
2. Visitors can preview another approved theme without changing the active theme for anyone else.

== Frequently Asked Questions ==

= Does this activate a theme for the whole website? =

No. The site's active theme remains unchanged. A functional cookie selects a theme for one visitor's frontend requests.

= Does it work with page caching? =

Alternative-theme responses send WordPress no-cache headers and define `DONOTCACHEPAGE`. If a reverse proxy or CDN ignores those signals, configure it to bypass full-page caching when the `sgfts_theme` cookie is present.

= Does it send visitor data anywhere? =

No. The cookie contains only an installed theme's stylesheet identifier and no data is transmitted to an external service.

= How long is the preference stored? =

Up to 30 days. Choosing the site's default theme removes the preference cookie.

= What happens when an allowed theme is removed? =

The stored preference is rejected and removed. WordPress continues with the site's active theme.

== Privacy ==

Frontend Theme Switcher does not collect personal data, add tracking, contact external services, or load third-party assets.

When a visitor chooses a preview theme, the plugin stores only that installed theme's stylesheet identifier in the `sgfts_theme` functional cookie. The cookie is used solely to remember the visitor's choice in that browser and expires after 30 days. Choosing the site's default theme removes it.

== Changelog ==

= 1.0.2 =

* Loaded each preview theme's own Customizer settings, including logo and header-display choices.

= 1.0.1 =

* Protected the switcher control from late-loading theme CSS resets.
* Prevented Salient Core icon styles from distorting Twenty Twenty-One submenu controls during previews.
* Kept the Twenty Twenty-One header compact at tablet-sized desktop navigation widths.

= 1.0.0 =

* Initial release.
