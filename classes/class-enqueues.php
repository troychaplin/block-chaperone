<?php
/**
 * Asset enqueue logic.
 *
 * @package BlockPolicy
 */

namespace BlockPolicy;

/**
 * Enqueues the editor script and the settings page app.
 *
 * Each webpack entry in `build/` produces `<entry>.js`, `<entry>.asset.php`
 * and (when it imports styles) `<entry>.css`.
 */
class Enqueues {

	/**
	 * Path resolver for the build directory.
	 *
	 * @var Plugin_Paths
	 */
	private Plugin_Paths $build_dir;

	/**
	 * Policy store.
	 *
	 * @var Policy_Store
	 */
	private Policy_Store $store;

	/**
	 * Constructor.
	 *
	 * @param string       $build_path Absolute path to the build directory.
	 * @param Policy_Store $store      Policy store.
	 */
	public function __construct( string $build_path, Policy_Store $store ) {
		$this->build_dir = new Plugin_Paths( $build_path );
		$this->store     = $store;
	}

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_assets' ) );
	}

	/**
	 * Enqueue the block editor script with the data it needs:
	 *
	 * - `unregistered`: blocks to unregister on this screen. In the post editor
	 *   that includes rules scoped to the post type being edited; elsewhere
	 *   (Site Editor, widgets) only unscoped rules.
	 * - `restricted`: post type => blocks to hide, Site Editor only. The Site
	 *   Editor gives `allowed_block_types_all` no post, so PHP can't apply post
	 *   type scopes there; the script applies them when the Site Editor
	 *   switches to editing a post or page. Scoped unregister rules are hidden
	 *   there instead, since it can't unregister per post type without a reload.
	 *
	 * Skipped entirely when there's nothing for the script to do.
	 */
	public function enqueue_editor_assets(): void {
		$site_editor = $this->is_site_editor();

		$data = array(
			'unregistered' => $this->store->unregistered_in( $site_editor ? null : $this->screen_post_type() ),
			'restricted'   => $site_editor ? $this->store->scoped_hidden_by_post_type() : array(),
		);

		if ( ! $data['unregistered'] && ! $data['restricted'] ) {
			return;
		}

		if ( ! $this->enqueue_entry( 'editor' ) ) {
			return;
		}

		if ( $data['restricted'] ) {
			// The Site Editor's own allowed list (hidden blocks already removed),
			// which the script narrows per post type and restores afterwards.
			$data['siteEditorAllowed'] = get_allowed_block_types( new \WP_Block_Editor_Context( array( 'name' => 'core/edit-site' ) ) );
		}

		// Cast so an empty map encodes as `{}`, not `[]`.
		$data['restricted'] = (object) $data['restricted'];

		wp_add_inline_script(
			'blockpolicy-editor',
			'window.blockPolicy = ' . wp_json_encode( $data ) . ';',
			'before'
		);
	}

	/**
	 * Post type of the current editor screen, or null if it isn't a post editor.
	 *
	 * @return string|null
	 */
	private function screen_post_type(): ?string {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return ( $screen && 'post' === $screen->base && $screen->post_type ) ? $screen->post_type : null;
	}

	/**
	 * Whether the current admin screen is the Site Editor.
	 *
	 * @return bool
	 */
	private function is_site_editor(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return $screen && 'site-editor' === $screen->id;
	}

	/**
	 * Enqueue the settings app on the plugin's settings screen only.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_settings_assets( string $hook_suffix ): void {
		if ( $hook_suffix !== Settings_Page::get_hook_suffix() ) {
			return;
		}

		$this->enqueue_entry( 'settings', array( 'wp-components' ) );
	}

	/**
	 * Enqueue a built webpack entry's script and (if present) stylesheet.
	 *
	 * @param string   $entry      Entry name, e.g. "editor".
	 * @param string[] $style_deps Style handle dependencies.
	 *
	 * @return bool Whether the script was enqueued (false if the build is missing).
	 */
	private function enqueue_entry( string $entry, array $style_deps = array() ): bool {
		$asset_meta = $this->build_dir->get_asset_meta( $entry . '.js' );

		if ( ! $asset_meta ) {
			return false;
		}

		$handle = 'blockpolicy-' . $entry;

		wp_enqueue_script(
			$handle,
			$this->build_dir->get_url( $entry . '.js' ),
			$asset_meta['dependencies'],
			$asset_meta['version'],
			true
		);

		wp_set_script_translations( $handle, 'blockpolicy' );

		if ( file_exists( $this->build_dir->get_path( $entry . '.css' ) ) ) {
			wp_enqueue_style(
				$handle,
				$this->build_dir->get_url( $entry . '.css' ),
				$style_deps,
				$asset_meta['version']
			);
		}

		return true;
	}
}
