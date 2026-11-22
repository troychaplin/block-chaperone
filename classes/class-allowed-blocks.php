<?php
/**
 * Server-side enforcement of the block policy.
 *
 * @package BlockPolicy
 */

namespace BlockPolicy;

use WP_Block_Editor_Context;
use WP_Block_Type_Registry;

/**
 * Filters `allowed_block_types_all` to remove hidden blocks from the inserter
 * in the content editor, for the post type being edited.
 *
 * Returning an explicit list drops anything not in it, so the list starts from
 * every registered block (or from another plugin's list, if one already
 * narrowed it) and only denied blocks are removed. Child blocks of allowed
 * parents therefore stay available.
 */
class Allowed_Blocks {

	/**
	 * Runs late so it narrows lists set by themes or other plugins rather than
	 * being overwritten by them.
	 */
	private const PRIORITY = 100;

	/**
	 * Policy store.
	 *
	 * @var Policy_Store
	 */
	private Policy_Store $store;

	/**
	 * Constructor.
	 *
	 * @param Policy_Store $store Policy store.
	 */
	public function __construct( Policy_Store $store ) {
		$this->store = $store;
	}

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_filter( 'allowed_block_types_all', array( $this, 'filter_allowed_blocks' ), self::PRIORITY, 2 );
	}

	/**
	 * Filter the allowed blocks for the current editor context.
	 *
	 * @param bool|string[]           $allowed_blocks       True for all blocks, false for none, or a list of block names.
	 * @param WP_Block_Editor_Context $block_editor_context Current editor context.
	 *
	 * @return bool|string[]
	 */
	public function filter_allowed_blocks( $allowed_blocks, $block_editor_context ) {
		if ( false === $allowed_blocks ) {
			return false;
		}

		$denied = $this->denied_blocks( $block_editor_context );

		// Nothing to remove: leave the value untouched (usually `true`).
		if ( ! $denied ) {
			return $allowed_blocks;
		}

		$registry = WP_Block_Type_Registry::get_instance();
		$base     = is_array( $allowed_blocks ) ? $allowed_blocks : array_keys( $registry->get_all_registered() );
		$denied   = $this->with_orphaned_children( array_fill_keys( $denied, true ), $registry );

		return array_values(
			array_filter(
				$base,
				static fn( $block_name ) => is_string( $block_name ) && ! isset( $denied[ $block_name ] )
			)
		);
	}

	/**
	 * Blocks denied in this editor context.
	 *
	 * Restrictions only apply in the content editor (the post editor), for the
	 * post type being edited. The Site Editor, widgets editor and Customizer
	 * are left alone.
	 *
	 * @param WP_Block_Editor_Context|mixed $block_editor_context Current editor context.
	 *
	 * @return string[]
	 */
	private function denied_blocks( $block_editor_context ): array {
		if ( ! $block_editor_context instanceof WP_Block_Editor_Context || 'core/edit-post' !== $block_editor_context->name ) {
			return array();
		}

		$post = $block_editor_context->post;

		if ( ! $post || empty( $post->post_type ) ) {
			return array();
		}

		return $this->store->hidden_in( $post->post_type );
	}

	/**
	 * Also deny child blocks that can no longer be placed anywhere: blocks whose
	 * every `parent` (or, failing that, every `ancestor`) is denied. Repeats
	 * until stable so grandchildren follow too.
	 *
	 * This is tidiness rather than security: such blocks couldn't be inserted
	 * anyway, but leaving them out keeps the editor's list honest.
	 *
	 * @param array<string, true>    $denied   Denied block names as keys.
	 * @param WP_Block_Type_Registry $registry Block registry.
	 *
	 * @return array<string, true>
	 */
	private function with_orphaned_children( array $denied, WP_Block_Type_Registry $registry ): array {
		$all = $registry->get_all_registered();

		do {
			$added = false;

			foreach ( $all as $name => $block_type ) {
				if ( isset( $denied[ $name ] ) ) {
					continue;
				}

				$containers = ! empty( $block_type->parent ) ? $block_type->parent : (array) $block_type->ancestor;

				if ( ! $containers ) {
					continue;
				}

				$all_denied = true;
				foreach ( $containers as $container ) {
					if ( ! isset( $denied[ $container ] ) ) {
						$all_denied = false;
						break;
					}
				}

				if ( $all_denied ) {
					$denied[ $name ] = true;
					$added           = true;
				}
			}
		} while ( $added );

		return $denied;
	}
}
