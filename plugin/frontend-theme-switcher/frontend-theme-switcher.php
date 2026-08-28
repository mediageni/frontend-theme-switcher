<?php
/**
 * Plugin Name:       MediaGeni Frontend Theme Switcher
 * Plugin URI:        https://mediageni.com/
 * Description:       Let visitors preview approved installed themes without changing the site's active theme.
 * Version:           1.0.1
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            MediaGeni
 * Author URI:        https://mediageni.com/
 * Text Domain:       frontend-theme-switcher
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package MediaGeni_Frontend_Theme_Switcher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SGFTS_VERSION', '1.0.1' );
define( 'SGFTS_FILE', __FILE__ );
define( 'SGFTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'SGFTS_URL', plugin_dir_url( __FILE__ ) );

require_once SGFTS_PATH . 'includes/class-sgfts-plugin.php';

register_activation_hook( SGFTS_FILE, array( 'SGFTS_Plugin', 'activate' ) );

/**
 * Starts the plugin before WordPress initializes the active theme.
 *
 * @return void
 */
function sgfts_run_plugin() {
	SGFTS_Plugin::instance();
}
add_action( 'plugins_loaded', 'sgfts_run_plugin', 0 );
