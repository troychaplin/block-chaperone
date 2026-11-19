<?php
/**
 * Plugin Name:       Block Policy
 * Description:       Control which blocks are available in the editor.
 * Requires at least: 6.6
 * Requires PHP:      8.0
 * Version:           0.1.0
 * Author:            Troy Chaplin
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       block-policy
 *
 * @package Block_Policy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Composer autoload.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( Block_Policy\Plugin::class ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Block Policy: Composer autoload not found. Run `composer install`.', 'block-policy' );
			echo '</p></div>';
		}
	);
	return;
}

( new Block_Policy\Plugin( __DIR__ ) )->init();
