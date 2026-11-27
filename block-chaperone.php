<?php
/**
 * Plugin Name:       Block Chaperone – Restrict Blocks by Role & Post Type
 * Description:       Hide or unregister blocks, or restrict them by post type and user role.
 * Requires at least: 6.6
 * Requires PHP:      8.0
 * Version:           1.0.0
 * Author:            Troy Chaplin
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       block-chaperone
 *
 * @package BlockChaperone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Composer autoload.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( BlockChaperone\Plugin::class ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Block Chaperone: Composer autoload not found. Run `composer install`.', 'block-chaperone' );
			echo '</p></div>';
		}
	);
	return;
}

( new BlockChaperone\Plugin( __DIR__ ) )->init();
