<?php
/**
 * Public plugin runtime.
 *
 * @package MediaGeni_Frontend_Theme_Switcher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates visitor-specific theme previews and frontend output.
 */
final class SGFTS_Plugin {

	/** Preference cookie name. */
	const COOKIE_NAME = 'sgfts_theme';

	/** Public query argument used to change the preference. */
	const QUERY_VAR = 'sgfts_theme';

	/** Settings option name. */
	const OPTION_NAME = 'sgfts_settings';

	/** @var SGFTS_Plugin|null */
	private static $instance = null;

	/** @var string */
	private $default_stylesheet = '';

	/** @var string */
	private $default_template = '';

	/** @var string */
	private $selected_stylesheet = '';

	/** @var string */
	private $selected_template = '';

	/** @var bool */
	private $has_switch_request = false;

	/** @var bool */
	private $automatic_switcher_rendered = false;

	/** @var bool */
	private $is_rendering_fallback = false;

	/**
	 * Returns the shared instance.
	 *
	 * @return SGFTS_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Adds safe defaults on first activation.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( false !== get_option( self::OPTION_NAME, false ) ) {
			return;
		}

		$allowed_themes = array_keys( self::get_usable_themes() );
		$menus          = wp_get_nav_menus();
		$shared_menu    = empty( $menus ) ? 0 : (int) $menus[0]->term_id;

		add_option(
			self::OPTION_NAME,
			array(
				'allowed_themes' => $allowed_themes,
				'shared_menu'     => $shared_menu,
				'auto_menu'      => 1,
				'delete_data'    => 0,
			),
			'',
			false
		);
	}

	/**
	 * Creates the runtime and registers hooks.
	 */
	private function __construct() {
		$this->default_stylesheet = (string) get_option( 'stylesheet' );
		$this->default_template   = (string) get_option( 'template' );

		if ( $this->is_frontend_request() ) {
			$this->prepare_theme_preview();
			$this->register_frontend_hooks();
		}

		if ( is_admin() ) {
			require_once SGFTS_PATH . 'includes/class-sgfts-admin.php';
			new SGFTS_Admin();
		}
	}

	/**
	 * Returns installed themes that WordPress reports as usable.
	 *
	 * @return WP_Theme[] Themes indexed by stylesheet slug.
	 */
	public static function get_usable_themes() {
		$themes = wp_get_themes( array( 'errors' => false ) );

		uasort(
			$themes,
			static function ( $first_theme, $second_theme ) {
				return strcasecmp( $first_theme->get( 'Name' ), $second_theme->get( 'Name' ) );
			}
		);

		return $themes;
	}

	/**
	 * Returns normalized plugin settings.
	 *
	 * @return array{allowed_themes:string[],shared_menu:int,auto_menu:int,delete_data:int}
	 */
	public static function get_settings() {
		$settings = get_option( self::OPTION_NAME, array() );
		$settings = is_array( $settings ) ? $settings : array();

		return array(
			'allowed_themes' => isset( $settings['allowed_themes'] ) && is_array( $settings['allowed_themes'] ) ? array_values( $settings['allowed_themes'] ) : array(),
			'shared_menu'     => isset( $settings['shared_menu'] ) ? absint( $settings['shared_menu'] ) : 0,
			'auto_menu'      => empty( $settings['auto_menu'] ) ? 0 : 1,
			'delete_data'    => empty( $settings['delete_data'] ) ? 0 : 1,
		);
	}

	/**
	 * Determines whether visitor theme selection may affect this request.
	 *
	 * @return bool
	 */
	private function is_frontend_request() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return false;
		}

		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$rest_prefix = '/' . rest_get_url_prefix() . '/';

		if ( false !== strpos( $request_uri, '/wp-login.php' ) || false !== strpos( $request_uri, $rest_prefix ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Reads and validates the requested or stored visitor preference.
	 *
	 * @return void
	 */
	private function prepare_theme_preview() {
		$requested_theme = null;

		if ( isset( $_GET[ self::QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This request changes only the visitor's own non-sensitive display cookie.
			$requested_theme          = sanitize_key( wp_unslash( $_GET[ self::QUERY_VAR ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->has_switch_request = true;
		}

		$selected_theme = '';

		if ( null !== $requested_theme ) {
			if ( 'default' !== $requested_theme && $this->is_theme_allowed( $requested_theme ) ) {
				$selected_theme = $requested_theme;
			}

			$this->write_preference_cookie( $selected_theme );
		} elseif ( isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			$cookie_theme = sanitize_key( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );

			if ( $this->is_theme_allowed( $cookie_theme ) ) {
				$selected_theme = $cookie_theme;
			} else {
				$this->write_preference_cookie( '' );
			}
		}

		if ( '' === $selected_theme || $this->default_stylesheet === $selected_theme ) {
			return;
		}

		$theme = wp_get_theme( $selected_theme );

		if ( ! $theme->exists() || $theme->errors() ) {
			$this->write_preference_cookie( '' );
			return;
		}

		$this->selected_stylesheet = $theme->get_stylesheet();
		$this->selected_template   = $theme->get_template();

		add_filter( 'stylesheet', array( $this, 'filter_stylesheet' ), 1 );
		add_filter( 'template', array( $this, 'filter_template' ), 1 );

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Standard page-cache compatibility constant.
		}

		add_action( 'send_headers', array( $this, 'send_no_cache_headers' ), 0 );
	}

	/**
	 * Registers public rendering hooks.
	 *
	 * @return void
	 */
	private function register_frontend_hooks() {
		add_action( 'template_redirect', array( $this, 'redirect_switch_request' ), 0 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'body_class', array( $this, 'filter_body_classes' ) );
		add_filter( 'theme_mod_nav_menu_locations', array( $this, 'filter_navigation_locations' ) );
		add_filter( 'wp_nav_menu_items', array( $this, 'append_to_classic_menu' ), 20, 2 );
		add_filter( 'render_block_core/navigation', array( $this, 'append_to_navigation_block' ), 20, 2 );
		add_action( 'wp_footer', array( $this, 'render_navigation_fallback' ), 99 );
		add_shortcode( 'frontend_theme_switcher', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Uses one approved classic menu in every header menu location.
	 *
	 * This changes only the current frontend response. Footer and social menu
	 * locations remain controlled by the previewed theme.
	 *
	 * @param int[] $locations Menu term IDs indexed by theme location.
	 * @return int[]
	 */
	public function filter_navigation_locations( $locations ) {
		$menu_id = $this->get_shared_menu_id();

		if ( ! $menu_id ) {
			return $locations;
		}

		$locations          = is_array( $locations ) ? $locations : array();
		$registered         = array_keys( get_registered_nav_menus() );
		$header_locations   = array_values( array_filter( $registered, array( $this, 'is_header_menu_location' ) ) );
		$primary_location   = $this->get_primary_menu_location( $header_locations );

		foreach ( $header_locations as $location ) {
			unset( $locations[ $location ] );
		}

		if ( $primary_location ) {
			$locations[ $primary_location ] = $menu_id;
		}

		return $locations;
	}

	/**
	 * Finds the most likely primary header location registered by a theme.
	 *
	 * @param string[] $locations Header-like theme locations.
	 * @return string
	 */
	private function get_primary_menu_location( $locations ) {
		$preferred = array( 'primary', 'primary-menu', 'menu-1', 'main', 'main-menu', 'header', 'top', 'top_nav' );

		foreach ( $preferred as $candidate ) {
			if ( in_array( $candidate, $locations, true ) ) {
				return $candidate;
			}
		}

		foreach ( $preferred as $candidate ) {
			foreach ( $locations as $location ) {
				if ( false !== strpos( strtolower( $location ), $candidate ) ) {
					return $location;
				}
			}
		}

		return empty( $locations ) ? '' : reset( $locations );
	}

	/**
	 * Returns the configured shared menu, or the first available menu.
	 *
	 * @return int Menu term ID, or zero when no classic menu exists.
	 */
	private function get_shared_menu_id() {
		$settings = self::get_settings();
		$menu_id  = absint( $settings['shared_menu'] );

		if ( $menu_id && wp_get_nav_menu_object( $menu_id ) ) {
			return $menu_id;
		}

		$menus = wp_get_nav_menus();

		return empty( $menus ) ? 0 : (int) $menus[0]->term_id;
	}

	/**
	 * Identifies header-like menu locations without assuming a theme API.
	 *
	 * @param string $location Registered menu location.
	 * @return bool
	 */
	private function is_header_menu_location( $location ) {
		$location = strtolower( (string) $location );

		return false === strpos( $location, 'footer' ) && false === strpos( $location, 'social' );
	}

	/**
	 * Returns whether a stylesheet is installed and approved by the administrator.
	 *
	 * @param string $stylesheet Theme stylesheet slug.
	 * @return bool
	 */
	private function is_theme_allowed( $stylesheet ) {
		if ( '' === $stylesheet ) {
			return false;
		}

		$themes = $this->get_public_themes();

		return isset( $themes[ $stylesheet ] );
	}

	/**
	 * Returns approved public themes and hides a parent when its child is approved.
	 *
	 * The globally active theme remains available as the visitor's reset option.
	 *
	 * @return WP_Theme[] Themes indexed by stylesheet slug.
	 */
	private function get_public_themes() {
		$themes         = self::get_usable_themes();
		$settings       = self::get_settings();
		$public_themes  = array_intersect_key( $themes, array_flip( $settings['allowed_themes'] ) );
		$hidden_parents = array();

		foreach ( $public_themes as $stylesheet => $theme ) {
			$template = $theme->get_template();

			if ( $template !== $stylesheet ) {
				$hidden_parents[ $template ] = true;
			}
		}

		foreach ( array_keys( $hidden_parents ) as $parent_stylesheet ) {
			if ( $parent_stylesheet !== $this->default_stylesheet ) {
				unset( $public_themes[ $parent_stylesheet ] );
			}
		}

		if ( isset( $themes[ $this->default_stylesheet ] ) ) {
			$public_themes = array( $this->default_stylesheet => $themes[ $this->default_stylesheet ] ) + $public_themes;
		}

		return $public_themes;
	}

	/**
	 * Stores or removes the visitor preference cookie.
	 *
	 * @param string $stylesheet Approved stylesheet slug, or an empty string to reset.
	 * @return void
	 */
	private function write_preference_cookie( $stylesheet ) {
		if ( headers_sent() ) {
			return;
		}

		$expires = '' === $stylesheet ? time() - HOUR_IN_SECONDS : time() + MONTH_IN_SECONDS;
		$options = array(
			'expires'  => $expires,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		);

		if ( COOKIE_DOMAIN ) {
			$options['domain'] = COOKIE_DOMAIN;
		}

		setcookie( self::COOKIE_NAME, $stylesheet, $options );

		if ( '' === $stylesheet ) {
			unset( $_COOKIE[ self::COOKIE_NAME ] );
		} else {
			$_COOKIE[ self::COOKIE_NAME ] = $stylesheet;
		}
	}

	/**
	 * Returns the selected parent theme directory.
	 *
	 * @param string $template Current template.
	 * @return string
	 */
	public function filter_template( $template ) {
		return $this->selected_template ? $this->selected_template : $template;
	}

	/**
	 * Returns the selected stylesheet directory.
	 *
	 * @param string $stylesheet Current stylesheet.
	 * @return string
	 */
	public function filter_stylesheet( $stylesheet ) {
		return $this->selected_stylesheet ? $this->selected_stylesheet : $stylesheet;
	}

	/**
	 * Prevents visitor-specific theme HTML from entering shared page caches.
	 *
	 * @return void
	 */
	public function send_no_cache_headers() {
		nocache_headers();
	}

	/**
	 * Redirects theme-choice URLs to their clean canonical URL.
	 *
	 * @return void
	 */
	public function redirect_switch_request() {
		if ( ! $this->has_switch_request ) {
			return;
		}

		$target_url = remove_query_arg( self::QUERY_VAR );
		wp_safe_redirect( $target_url, 302, 'MediaGeni Frontend Theme Switcher' );
		exit;
	}

	/**
	 * Loads the small scoped stylesheet through WordPress.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$stylesheet_path    = SGFTS_PATH . 'assets/css/frontend.css';
		$stylesheet_version = file_exists( $stylesheet_path ) ? (string) filemtime( $stylesheet_path ) : SGFTS_VERSION;
		$script_path        = SGFTS_PATH . 'assets/js/frontend.js';
		$script_version     = file_exists( $script_path ) ? (string) filemtime( $script_path ) : SGFTS_VERSION;

		wp_enqueue_style(
			'sgfts-frontend',
			SGFTS_URL . 'assets/css/frontend.css',
			array(),
			$stylesheet_version
		);

		wp_enqueue_script(
			'sgfts-frontend',
			SGFTS_URL . 'assets/js/frontend.js',
			array(),
			$script_version,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Adds diagnostic body classes for the visitor preview only.
	 *
	 * @param string[] $classes Existing body classes.
	 * @return string[]
	 */
	public function filter_body_classes( $classes ) {
		if ( $this->selected_stylesheet ) {
			$classes[] = 'sgfts-theme-preview';
			$classes[] = 'sgfts-theme-' . sanitize_html_class( $this->selected_stylesheet );
		}

		return $classes;
	}

	/**
	 * Adds the switcher as the last item in a classic navigation menu.
	 *
	 * @param string   $items Rendered menu items.
	 * @param stdClass $args  Menu arguments.
	 * @return string
	 */
	public function append_to_classic_menu( $items, $args ) {
		$location = isset( $args->theme_location ) ? $args->theme_location : '';
		$settings = self::get_settings();

		if ( $this->is_rendering_fallback || empty( $settings['auto_menu'] ) || ! $this->is_header_menu_location( $location ) ) {
			return $items;
		}

		$this->automatic_switcher_rendered = true;

		return $items . $this->get_switcher_markup( 'menu-item' );
	}

	/**
	 * Adds the switcher after the items in the first Navigation block.
	 *
	 * @param string $block_content Rendered Navigation block.
	 * @param array  $block         Parsed block data.
	 * @return string
	 */
	public function append_to_navigation_block( $block_content, $block ) {
		unset( $block );

		if ( ! $this->should_append_automatically() ) {
			return $block_content;
		}

		$closing_list_position = strripos( $block_content, '</ul>' );

		if ( false === $closing_list_position ) {
			return $block_content;
		}

		$this->automatic_switcher_rendered = true;
		$markup                            = $this->get_switcher_markup( 'wp-block-navigation-item' );

		return substr_replace( $block_content, $markup, $closing_list_position, 0 );
	}

	/**
	 * Returns whether the automatic first-navigation placement is available.
	 *
	 * @return bool
	 */
	private function should_append_automatically() {
		$settings = self::get_settings();

		return ! $this->automatic_switcher_rendered && ! empty( $settings['auto_menu'] );
	}

	/**
	 * Keeps the visitor in control when a theme renders no WordPress navigation.
	 *
	 * @return void
	 */
	public function render_navigation_fallback() {
		if ( ! $this->should_append_automatically() ) {
			return;
		}

		$this->automatic_switcher_rendered = true;
		$menu_id                           = $this->get_shared_menu_id();
		$menu_markup                       = '';

		if ( $menu_id ) {
			$this->is_rendering_fallback = true;
			$menu_markup = wp_nav_menu(
				array(
					'menu'        => $menu_id,
					'container'   => false,
					'echo'        => false,
					'fallback_cb' => false,
					'items_wrap'  => '<ul class="sgfts-navigation-fallback__menu">%3$s</ul>',
					'depth'       => 2,
				)
			);
			$this->is_rendering_fallback = false;
		}

		echo '<nav class="sgfts-navigation-fallback" aria-label="' . esc_attr__( 'Preview navigation', 'frontend-theme-switcher' ) . '">' . wp_kses_post( $menu_markup ) . $this->get_switcher_markup( '' ) . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dynamic values and assembled switcher markup are escaped.
	}

	/**
	 * Renders the shortcode fallback.
	 *
	 * @return string
	 */
	public function render_shortcode() {
		return $this->get_switcher_markup( '' );
	}

	/**
	 * Builds the accessible native details switcher.
	 *
	 * @param string $menu_class Optional class used by the surrounding navigation.
	 * @return string
	 */
	private function get_switcher_markup( $menu_class ) {
		$themes             = self::get_usable_themes();
		$allowed_themes     = $this->get_public_themes();
		$current_stylesheet = $this->selected_stylesheet ? $this->selected_stylesheet : $this->default_stylesheet;

		if ( empty( $allowed_themes ) ) {
			return '';
		}

		$current_theme = isset( $themes[ $current_stylesheet ] ) ? $themes[ $current_stylesheet ] : wp_get_theme( $current_stylesheet );
		$summary_label = sprintf(
			/* translators: %s: Current theme name. */
			__( 'Choose website theme. Current theme: %s', 'frontend-theme-switcher' ),
			$current_theme->get( 'Name' )
		);
		$wrapper_tag   = $menu_class ? 'li' : 'div';
		$item_classes  = $menu_class ? trim( $menu_class . ' sgfts-menu-item' ) : 'sgfts-switcher-wrap';
		$output        = '<' . $wrapper_tag . ' class="' . esc_attr( $item_classes ) . '">';
		$output       .= '<details class="sgfts-switcher">';
		$output       .= '<summary class="sgfts-switcher__summary" aria-label="' . esc_attr( $summary_label ) . '">';
		$output       .= '<span class="sgfts-switcher__icon" aria-hidden="true">◐</span>';
		$output       .= '<span class="sgfts-switcher__label">' . esc_html__( 'Theme', 'frontend-theme-switcher' ) . '</span>';
		$output       .= '<span class="sgfts-switcher__current">' . esc_html( $current_theme->get( 'Name' ) ) . '</span>';
		$output       .= '</summary>';
		$output       .= '<div class="sgfts-switcher__panel">';
		$output       .= '<p class="sgfts-switcher__heading">' . esc_html__( 'Preview this site', 'frontend-theme-switcher' ) . '</p>';
		$output       .= '<ul class="sgfts-switcher__options">';

		foreach ( $allowed_themes as $stylesheet => $theme ) {
			$is_current  = $stylesheet === $current_stylesheet;
			$query_value = $stylesheet === $this->default_stylesheet ? 'default' : $stylesheet;
			$theme_type  = $theme->is_block_theme() ? __( 'Block', 'frontend-theme-switcher' ) : __( 'Classic', 'frontend-theme-switcher' );
			$link_label  = sprintf(
				/* translators: 1: Theme name. 2: Theme type. */
				__( '%1$s, %2$s theme', 'frontend-theme-switcher' ),
				$theme->get( 'Name' ),
				$theme_type
			);

			$output .= '<li class="sgfts-switcher__option">';
			$output .= '<a class="sgfts-switcher__link" href="' . esc_url( add_query_arg( self::QUERY_VAR, $query_value ) ) . '"' . ( $is_current ? ' aria-current="true"' : '' ) . ' aria-label="' . esc_attr( $link_label ) . '">';
			$output .= '<span class="sgfts-switcher__name">' . esc_html( $theme->get( 'Name' ) ) . '</span>';
			$output .= '<span class="sgfts-switcher__type">' . esc_html( $theme_type ) . '</span>';
			$output .= '<span class="sgfts-switcher__check" aria-hidden="true">' . ( $is_current ? '✓' : '' ) . '</span>';
			$output .= '</a>';
			$output .= '</li>';
		}

		$output .= '</ul>';
		$output .= '</div>';
		$output .= '</details>';
		$output .= '</' . $wrapper_tag . '>';

		return $output;
	}
}
