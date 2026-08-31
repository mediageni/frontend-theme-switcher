<?php
/**
 * Optional settings cleanup.
 *
 * @package MediaGeni_Frontend_Theme_Switcher
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Deletes settings for the current site only when the administrator opted in.
 *
 * @return void
 */
function sgfts_maybe_delete_site_settings() {
	$settings = get_option( 'sgfts_settings', array() );

	if ( is_array( $settings ) && ! empty( $settings['delete_data'] ) ) {
		delete_option( 'sgfts_settings' );
	}
}

if ( is_multisite() ) {
	$sgfts_offset = 0;

	do {
		$sgfts_site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 100,
				'offset' => $sgfts_offset,
			)
		);

		foreach ( $sgfts_site_ids as $sgfts_site_id ) {
			switch_to_blog( $sgfts_site_id );
			sgfts_maybe_delete_site_settings();
			restore_current_blog();
		}

		$sgfts_offset += count( $sgfts_site_ids );
	} while ( 100 === count( $sgfts_site_ids ) );
} else {
	sgfts_maybe_delete_site_settings();
}
