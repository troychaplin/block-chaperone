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
	 * Enqueue the block editor script with the "unregistered" blocks for the
	 * post type being edited, in the content editor only (the post editor).
	 * The Site Editor, widgets editor and Customizer are left alone.
	 *
	 * The blocks aren't removed from the registry, since templates and the
	 * Template preview share it; the script shows existing instances in post
	 * content as a removable placeholder instead.
	 *
	 * Skipped entirely when there are no such blocks.
	 */
	public function enqueue_editor_assets(): void {
		$post_type = $this->screen_post_type();

		if ( null === $post_type ) {
			return;
		}

		$unregistered = $this->store->unregistered_in( $post_type );

		if ( ! $unregistered || ! $this->enqueue_entry( 'editor' ) ) {
			return;
		}

		wp_add_inline_script(
			'blockpolicy-editor',
			'window.blockPolicy = ' . wp_json_encode( array( 'unregistered' => $unregistered ) ) . ';',
			'before'
		);
	}

	/**
	 * Post type of the current editor screen, or null if it isn't the post editor.
	 *
	 * @return string|null
	 */
	private function screen_post_type(): ?string {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return ( $screen && 'post' === $screen->base && $screen->post_type ) ? $screen->post_type : null;
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
