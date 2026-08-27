<?php
/**
 * Administration settings.
 *
 * @package MediaGeni_Frontend_Theme_Switcher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the plugin settings page.
 */
final class SGFTS_Admin {

	/**
	 * Registers admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SGFTS_FILE ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Adds the page below Settings.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			esc_html__( 'Frontend Theme Switcher', 'frontend-theme-switcher' ),
			esc_html__( 'Theme Switcher', 'frontend-theme-switcher' ),
			'manage_options',
			'frontend-theme-switcher',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Registers one compact settings array through the Settings API.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'sgfts_settings_group',
			SGFTS_Plugin::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(
					'allowed_themes' => array(),
					'shared_menu'     => 0,
					'auto_menu'      => 1,
					'delete_data'    => 0,
				),
			)
		);
	}

	/**
	 * Validates all settings against installed themes.
	 *
	 * @param mixed $input Submitted settings.
	 * @return array{allowed_themes:string[],shared_menu:int,auto_menu:int,delete_data:int}
	 */
	public function sanitize_settings( $input ) {
		$input            = is_array( $input ) ? $input : array();
		$installed_themes = SGFTS_Plugin::get_usable_themes();
		$allowed_themes   = array();

		if ( isset( $input['allowed_themes'] ) && is_array( $input['allowed_themes'] ) ) {
			foreach ( $input['allowed_themes'] as $stylesheet ) {
				$stylesheet = sanitize_key( $stylesheet );

				if ( isset( $installed_themes[ $stylesheet ] ) ) {
					$allowed_themes[] = $stylesheet;
				}
			}
		}

		return array(
			'allowed_themes' => array_values( array_unique( $allowed_themes ) ),
			'shared_menu'     => isset( $input['shared_menu'] ) && wp_get_nav_menu_object( absint( $input['shared_menu'] ) ) ? absint( $input['shared_menu'] ) : 0,
			'auto_menu'      => empty( $input['auto_menu'] ) ? 0 : 1,
			'delete_data'    => empty( $input['delete_data'] ) ? 0 : 1,
		);
	}

	/**
	 * Adds a direct Settings link to the Plugins screen.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=frontend-theme-switcher' ) ) . '">' . esc_html__( 'Settings', 'frontend-theme-switcher' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Adds suggested privacy-policy wording through WordPress Core.
	 *
	 * @return void
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<p>';
		$content .= esc_html__( 'When a visitor chooses a preview theme, this site stores the selected theme identifier in a functional cookie for up to 30 days. The preference is used only to render the site for that visitor and is not transmitted to an external service.', 'frontend-theme-switcher' );
		$content .= '</p>';

		wp_add_privacy_policy_content(
			esc_html__( 'Frontend Theme Switcher', 'frontend-theme-switcher' ),
			wp_kses_post( wpautop( $content, false ) )
		);
	}

	/**
	 * Renders the settings form.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings          = SGFTS_Plugin::get_settings();
		$themes            = SGFTS_Plugin::get_usable_themes();
		$menus             = wp_get_nav_menus();
		$active_stylesheet = (string) get_option( 'stylesheet' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Frontend Theme Switcher', 'frontend-theme-switcher' ); ?></h1>
			<p><?php echo esc_html__( 'Let visitors preview approved installed themes without changing the active WordPress theme for anyone else.', 'frontend-theme-switcher' ); ?></p>

			<form action="options.php" method="post">
				<?php settings_fields( 'sgfts_settings_group' ); ?>

				<h2><?php echo esc_html__( 'Available themes', 'frontend-theme-switcher' ); ?></h2>
				<p><?php echo esc_html__( 'Only selected themes appear in the public switcher. Review every allowed theme before enabling it on a production website. When an allowed child theme is installed, its parent theme is hidden from the public list.', 'frontend-theme-switcher' ); ?></p>

				<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col" class="check-column"><span class="screen-reader-text"><?php echo esc_html__( 'Allowed', 'frontend-theme-switcher' ); ?></span></th>
							<th scope="col"><?php echo esc_html__( 'Theme', 'frontend-theme-switcher' ); ?></th>
							<th scope="col"><?php echo esc_html__( 'Type', 'frontend-theme-switcher' ); ?></th>
							<th scope="col"><?php echo esc_html__( 'Version', 'frontend-theme-switcher' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $themes as $stylesheet => $theme ) : ?>
							<tr>
								<th scope="row" class="check-column">
									<label>
										<input type="checkbox" name="<?php echo esc_attr( SGFTS_Plugin::OPTION_NAME ); ?>[allowed_themes][]" value="<?php echo esc_attr( $stylesheet ); ?>" <?php checked( in_array( $stylesheet, $settings['allowed_themes'], true ) ); ?>>
										<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: Theme name. */ __( 'Allow %s', 'frontend-theme-switcher' ), $theme->get( 'Name' ) ) ); ?></span>
									</label>
								</th>
								<td>
									<strong><?php echo esc_html( $theme->get( 'Name' ) ); ?></strong>
									<?php if ( $active_stylesheet === $stylesheet ) : ?>
										&mdash; <?php echo esc_html__( 'Site default', 'frontend-theme-switcher' ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $theme->is_block_theme() ? __( 'Block', 'frontend-theme-switcher' ) : __( 'Classic', 'frontend-theme-switcher' ) ); ?></td>
								<td><?php echo esc_html( $theme->get( 'Version' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<h2><?php echo esc_html__( 'Shared top navigation', 'frontend-theme-switcher' ); ?></h2>
				<?php if ( $menus ) : ?>
					<p>
						<label for="sgfts-shared-menu"><?php echo esc_html__( 'Navigation menu', 'frontend-theme-switcher' ); ?></label>
						<select id="sgfts-shared-menu" name="<?php echo esc_attr( SGFTS_Plugin::OPTION_NAME ); ?>[shared_menu]">
							<?php foreach ( $menus as $menu ) : ?>
								<option value="<?php echo esc_attr( $menu->term_id ); ?>" <?php selected( (int) $settings['shared_menu'], (int) $menu->term_id ); ?>><?php echo esc_html( $menu->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<p class="description"><?php echo esc_html__( 'Classic preview themes use this menu in their header locations. Themes without a usable header navigation receive the same links in the compact top fallback.', 'frontend-theme-switcher' ); ?></p>
				<?php else : ?>
					<p><?php echo esc_html__( 'Create a navigation menu under Appearance before enabling shared top navigation.', 'frontend-theme-switcher' ); ?></p>
				<?php endif; ?>

				<h2><?php echo esc_html__( 'Placement', 'frontend-theme-switcher' ); ?></h2>
				<p>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( SGFTS_Plugin::OPTION_NAME ); ?>[auto_menu]" value="1" <?php checked( $settings['auto_menu'] ); ?>>
						<?php echo esc_html__( 'Add the switcher after the items in the first frontend navigation.', 'frontend-theme-switcher' ); ?>
					</label>
				</p>
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Shortcode. */
							__( 'For manual placement, disable automatic placement and use the %s shortcode.', 'frontend-theme-switcher' ),
							'<code>[frontend_theme_switcher]</code>'
						)
					);
					?>
				</p>

				<h2><?php echo esc_html__( 'Data removal', 'frontend-theme-switcher' ); ?></h2>
				<p>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( SGFTS_Plugin::OPTION_NAME ); ?>[delete_data]" value="1" <?php checked( $settings['delete_data'] ); ?>>
						<?php echo esc_html__( 'Delete plugin settings when the plugin is uninstalled.', 'frontend-theme-switcher' ); ?>
					</label>
				</p>
				<p class="description"><?php echo esc_html__( 'Deactivation never deletes settings. Visitor preference cookies become inactive when the plugin is disabled.', 'frontend-theme-switcher' ); ?></p>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
