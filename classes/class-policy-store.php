<?php
/**
 * Persistence for the block policy.
 *
 * @package Block_Policy
 */

namespace Block_Policy;

/**
 * Reads, sanitizes and saves the `block_policy_settings` option.
 *
 * The option is a deny-list: only blocks that differ from the default
 * (allowed everywhere) are stored.
 *
 *     array(
 *         'blocks' => array(
 *             'core/quote' => array( 'hidden' => true ),
 *             'core/verse' => array( 'hidden' => true, 'unregistered' => true ),
 *             'core/cover' => array( 'postTypes' => array( 'post' ) ),
 *         ),
 *     )
 *
 * Rules for blocks or post types that aren't currently registered are kept,
 * so deactivating and reactivating a plugin doesn't lose its policy.
 */
class Policy_Store {

	/**
	 * Option name.
	 */
	public const OPTION = 'block_policy_settings';

	/**
	 * Valid block name: namespace/name, lowercase alphanumerics and dashes.
	 */
	private const BLOCK_NAME_PATTERN = '#^[a-z][a-z0-9-]*/[a-z][a-z0-9-]*$#';

	/**
	 * The saved policy, normalised.
	 *
	 * @return array{blocks: array<string, array{hidden?: bool, unregistered?: bool, postTypes?: string[]}>}
	 */
	public function get(): array {
		$saved = get_option( self::OPTION, array() );

		return $this->sanitize( is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Sanitize and save a full policy, replacing whatever was stored.
	 *
	 * @param array $policy Raw policy.
	 *
	 * @return array The sanitized policy that was saved.
	 */
	public function save( array $policy ): array {
		$sanitized = $this->sanitize( $policy );

		update_option( self::OPTION, $sanitized, true );

		return $sanitized;
	}

	/**
	 * Normalise a raw policy.
	 *
	 * - Drops invalid block names and protected blocks.
	 * - Casts flags to bool; `unregistered` implies `hidden`.
	 * - Dedupes post type slugs.
	 * - Drops rules that are all defaults, so only real overrides are stored.
	 *
	 * @param array $policy Raw policy.
	 *
	 * @return array{blocks: array<string, array{hidden?: bool, unregistered?: bool, postTypes?: string[]}>}
	 */
	public function sanitize( array $policy ): array {
		$blocks = array();
		$raw    = isset( $policy['blocks'] ) && is_array( $policy['blocks'] ) ? $policy['blocks'] : array();

		foreach ( $raw as $block_name => $rule ) {
			$block_name = (string) $block_name;

			if ( ! is_array( $rule ) || ! preg_match( self::BLOCK_NAME_PATTERN, $block_name ) || Block_Catalog::is_protected( $block_name ) ) {
				continue;
			}

			$clean = array();

			$unregistered = ! empty( $rule['unregistered'] );
			$hidden       = $unregistered || ! empty( $rule['hidden'] );

			if ( $hidden ) {
				$clean['hidden'] = true;
			}

			if ( $unregistered ) {
				$clean['unregistered'] = true;
			}

			if ( isset( $rule['postTypes'] ) && is_array( $rule['postTypes'] ) ) {
				$post_types = array_values(
					array_unique(
						array_filter( array_map( 'sanitize_key', array_filter( $rule['postTypes'], 'is_string' ) ) )
					)
				);

				if ( $post_types ) {
					$clean['postTypes'] = $post_types;
				}
			}

			if ( $clean ) {
				$blocks[ $block_name ] = $clean;
			}
		}

		ksort( $blocks );

		return array( 'blocks' => $blocks );
	}

	/**
	 * Blocks hidden from the inserter everywhere (includes unregistered blocks).
	 *
	 * @return string[]
	 */
	public function hidden_blocks(): array {
		return $this->blocks_where( static fn( $rule ) => ! empty( $rule['hidden'] ) );
	}

	/**
	 * Blocks unregistered from the editor.
	 *
	 * @return string[]
	 */
	public function unregistered_blocks(): array {
		return $this->blocks_where( static fn( $rule ) => ! empty( $rule['unregistered'] ) );
	}

	/**
	 * Blocks restricted (hidden from the inserter) in a given post type.
	 *
	 * @param string $post_type Post type slug.
	 *
	 * @return string[]
	 */
	public function restricted_for( string $post_type ): array {
		return $this->blocks_where(
			static fn( $rule ) => in_array( $post_type, $rule['postTypes'] ?? array(), true )
		);
	}

	/**
	 * Post type restrictions grouped by post type: post type => block names.
	 *
	 * @return array<string, string[]>
	 */
	public function restrictions_by_post_type(): array {
		$by_post_type = array();

		foreach ( $this->effective()['blocks'] as $block_name => $rule ) {
			foreach ( $rule['postTypes'] ?? array() as $post_type ) {
				$by_post_type[ $post_type ][] = $block_name;
			}
		}

		return $by_post_type;
	}

	/**
	 * The policy that is actually enforced: the saved policy, passed through
	 * the `block_policy_settings` filter so code can add to or override it.
	 *
	 * The settings page reads and writes get()/save() directly, so rules added
	 * by the filter are enforced but never shown in, or saved from, the UI.
	 *
	 * @return array{blocks: array<string, array{hidden?: bool, unregistered?: bool, postTypes?: string[]}>}
	 */
	public function effective(): array {
		$saved = $this->get();

		/**
		 * Filters the enforced block policy.
		 *
		 * The result is sanitized again, so the same rules apply as for saved
		 * settings: `unregistered` implies `hidden`, and protected blocks
		 * (core/paragraph) can't be restricted.
		 *
		 * @param array $policy {
		 *     The saved policy.
		 *
		 *     @type array $blocks Rules keyed by block name. Each rule may set
		 *                         `hidden` (bool), `unregistered` (bool) and
		 *                         `postTypes` (string[], hidden in those types).
		 * }
		 */
		$filtered = apply_filters( 'block_policy_settings', $saved );

		return is_array( $filtered ) ? $this->sanitize( $filtered ) : $saved;
	}

	/**
	 * Names of blocks whose enforced rule matches a predicate.
	 *
	 * @param callable $predicate Receives a rule array, returns bool.
	 *
	 * @return string[]
	 */
	private function blocks_where( callable $predicate ): array {
		return array_keys( array_filter( $this->effective()['blocks'], $predicate ) );
	}
}
