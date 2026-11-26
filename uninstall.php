<?php
/**
 * Fired when the plugin is uninstalled. Removes the saved block policy.
 *
 * @package BlockChaperone
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Matches BlockChaperone\Policy_Store::OPTION. The autoloader isn't loaded here.
$block_chaperone_option = 'block_chaperone_settings';

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $block_chaperone_site_id ) {
		switch_to_blog( $block_chaperone_site_id );
		delete_option( $block_chaperone_option );
		restore_current_blog();
	}
} else {
	delete_option( $block_chaperone_option );
}
