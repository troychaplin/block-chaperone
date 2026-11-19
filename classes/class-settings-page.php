<?php
/**
 * Admin settings page.
 *
 * @package Block_Policy
 */

namespace Block_Policy;

/**
 * Registers the settings screen. The page renders an empty root that the
 * `settings` script mounts a React (DataViews) app into.
 */
class Settings_Page {

	/**
	 * Menu / page slug.
	 */
	public const SLUG = 'block-policy';

	/**
	 * Capability required to view the page.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * Hook suffix returned by add_options_page(), used to scope asset loading.
	 *
	 * @var string
	 */
	private static string $hook_suffix = '';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	/**
	 * Add the page under Settings.
	 */
	public function register_page(): void {
		$hook_suffix = add_options_page(
			__( 'Block Policy', 'block-policy' ),
			__( 'Block Policy', 'block-policy' ),
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render_page' )
		);

		self::$hook_suffix = $hook_suffix ? $hook_suffix : '';
	}

	/**
	 * Hook suffix for the settings screen (empty until `admin_menu` has run).
	 *
	 * @return string
	 */
	public static function get_hook_suffix(): string {
		return self::$hook_suffix;
	}

	/**
	 * Output the mount point for the settings app.
	 *
	 * The React app draws its own page chrome edge to edge, so the usual `.wrap`
	 * container is omitted. The admin chrome overrides are inlined so they apply
	 * on first paint (no snap from default width to full bleed), and scoped to
	 * `body.js` so a browser without JavaScript keeps the normal admin layout.
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'block-policy' ) );
		}

		?>
		<style>
			#wpwrap { overflow-y: auto; }
			body.js #wpcontent { padding-inline-start: 0; }
			body.js #wpbody-content { padding-bottom: 0; }
			body.js #wpfooter { display: none; }

			@media (min-width: 782px) {
				#wpwrap { overflow-y: initial; }
			}
		</style>
		<?php
		// Anchor for admin notices, so they aren't injected inside the React root.
		echo '<hr class="wp-header-end">';
		echo '<div id="block-policy-settings-root"></div>';
		?>
		<noscript>
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<p><?php esc_html_e( 'Block Policy settings require JavaScript.', 'block-policy' ); ?></p>
			</div>
		</noscript>
		<?php
	}
}
