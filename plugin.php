<?php
/**
 * Plugin Name:       BlockPolicy – Restrict Blocks by Role & Post Type
 * Description:       Hide or unregister blocks, or restrict them by post type and user role.
 * Requires at least: 6.6
 * Requires PHP:      8.0
 * Version:           0.1.0
 * Author:            Troy Chaplin
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       blockpolicy
 *
 * @package BlockPolicy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Composer autoload.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( BlockPolicy\Plugin::class ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'BlockPolicy: Composer autoload not found. Run `composer install`.', 'blockpolicy' );
			echo '</p></div>';
		}
	);
	return;
}

( new BlockPolicy\Plugin( __DIR__ ) )->init();
