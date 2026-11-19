<?php
/**
 * Fired when the plugin is uninstalled. Removes the saved block policy.
 *
 * @package BlockPolicy
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Matches BlockPolicy\Policy_Store::OPTION. The autoloader isn't loaded here.
$blockpolicy_option = 'blockpolicy_settings';

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $blockpolicy_site_id ) {
		switch_to_blog( $blockpolicy_site_id );
		delete_option( $blockpolicy_option );
		restore_current_blog();
	}
} else {
	delete_option( $blockpolicy_option );
}
