<?php
/**
 * Fired when the plugin is uninstalled. Removes the saved block policy.
 *
 * @package Block_Policy
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Matches Block_Policy\Policy_Store::OPTION. The autoloader isn't loaded here.
$block_policy_option = 'block_policy_settings';

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $block_policy_site_id ) {
		switch_to_blog( $block_policy_site_id );
		delete_option( $block_policy_option );
		restore_current_blog();
	}
} else {
	delete_option( $block_policy_option );
}
