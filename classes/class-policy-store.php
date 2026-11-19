<?php
/**
 * Persistence for the block policy.
 *
 * @package BlockPolicy
 */

namespace BlockPolicy;

/**
 * Reads, sanitizes and saves the `blockpolicy_settings` option.
 *
 * The option is a deny-list: only blocks that differ from the default
 * (allowed everywhere) are stored.
 *
 *     array(
 *         'blocks' => array(
 *             'core/quote' => array( 'hidden' => true ),
 *             'core/verse' => array( 'hidden' => true, 'unregistered' => true ),
 *             'core/cover' => array( 'hidden' => true, 'postTypes' => array( 'post' ) ),
 *             'core/html'  => array( 'hidden' => true, 'roles' => array( 'editor', 'author' ) ),
 *         ),
 *     )
 *
 * A rule's restrictions are `hidden` (not in the inserter) and `unregistered`
 * (removed from the editor; implies hidden). `postTypes` and `roles` scope
 * them: where and to whom they apply. Missing or empty means all post types /
 * everyone; otherwise only those post types / users with at least one of the
 * listed roles are restricted.
 *
 * Rules for blocks, post types or roles that aren't currently registered are
 * kept, so deactivating and reactivating a plugin doesn't lose its policy.
 */
class Policy_Store {

	/**
	 * Option name.
	 */
	public const OPTION = 'blockpolicy_settings';

	/**
	 * Valid block name: namespace/name, lowercase alphanumerics and dashes.
	 */
	private const BLOCK_NAME_PATTERN = '#^[a-z][a-z0-9-]*/[a-z][a-z0-9-]*$#';

	/**
	 * The saved policy, normalised.
	 *
	 * @return array{blocks: array<string, array{hidden?: bool, unregistered?: bool, postTypes?: string[], roles?: string[]}>}
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
	 * - Dedupes post type and role slugs.
	 * - Drops rules with no restriction: `postTypes` and `roles` only scope
	 *   `hidden` / `unregistered`, so on their own they mean nothing.
	 * - Legacy shape: a rule with `postTypes` but no restriction used to mean
	 *   "hidden in these post types", so it's read as `hidden` + `postTypes`.
	 *
	 * @param array $policy Raw policy.
	 *
	 * @return array{blocks: array<string, array{hidden?: bool, unregistered?: bool, postTypes?: string[], roles?: string[]}>}
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

			$post_types   = $this->sanitize_slugs( $rule['postTypes'] ?? null );
			$unregistered = ! empty( $rule['unregistered'] );
			$hidden       = $unregistered || ! empty( $rule['hidden'] )
				// Legacy: post types alone meant "hidden in these post types".
				|| ( $post_types && ! array_key_exists( 'hidden', $rule ) );

			// Post types and roles only scope restrictions; without one there's nothing to store.
			if ( ! $hidden ) {
				continue;
			}

			$clean['hidden'] = true;

			if ( $unregistered ) {
				$clean['unregistered'] = true;
			}

			if ( $post_types ) {
				$clean['postTypes'] = $post_types;
			}

			$roles = $this->sanitize_slugs( $rule['roles'] ?? null );
			if ( $roles ) {
				$clean['roles'] = $roles;
			}

			$blocks[ $block_name ] = $clean;
		}

		ksort( $blocks );

		return array( 'blocks' => $blocks );
	}

	/**
	 * Sanitize a list of slugs (post types or roles): strings only,
	 * `sanitize_key`, no empties, no duplicates.
	 *
	 * @param mixed $slugs Raw value.
	 *
	 * @return string[]
	 */
	private function sanitize_slugs( $slugs ): array {
		if ( ! is_array( $slugs ) ) {
			return array();
		}

		return array_values(
			array_unique(
				array_filter( array_map( 'sanitize_key', array_filter( $slugs, 'is_string' ) ) )
			)
		);
	}

	/**
	 * Blocks hidden from the inserter in every context for the current user:
	 * hidden (or unregistered) with no post type scope.
	 *
	 * @return string[]
	 */
	public function hidden_everywhere(): array {
		return $this->blocks_where( static fn( $rule ) => ! empty( $rule['hidden'] ) && empty( $rule['postTypes'] ) );
	}

	/**
	 * Blocks hidden from the inserter when editing a given post type.
	 *
	 * @param string $post_type Post type slug.
	 *
	 * @return string[]
	 */
	public function hidden_in( string $post_type ): array {
		return $this->blocks_where(
			static fn( $rule ) => ! empty( $rule['hidden'] ) && self::scope_includes( $rule, $post_type )
		);
	}

	/**
	 * Blocks to unregister on an editor screen.
	 *
	 * @param string|null $post_type Post type being edited, or null where the
	 *                               screen isn't tied to one (Site Editor,
	 *                               widgets): then only unscoped rules apply.
	 *
	 * @return string[]
	 */
	public function unregistered_in( ?string $post_type ): array {
		return $this->blocks_where(
			static fn( $rule ) => ! empty( $rule['unregistered'] )
				&& ( null === $post_type ? empty( $rule['postTypes'] ) : self::scope_includes( $rule, $post_type ) )
		);
	}

	/**
	 * Post-type-scoped rules grouped by post type: post type => block names.
	 * Includes scoped unregistered blocks, which the Site Editor hides instead
	 * (it switches post types without reloading, so it can't unregister per
	 * post type).
	 *
	 * @return array<string, string[]>
	 */
	public function scoped_hidden_by_post_type(): array {
		$by_post_type = array();

		foreach ( $this->rules_for_current_user() as $block_name => $rule ) {
			if ( empty( $rule['hidden'] ) ) {
				continue;
			}
			foreach ( $rule['postTypes'] ?? array() as $post_type ) {
				$by_post_type[ $post_type ][] = $block_name;
			}
		}

		return $by_post_type;
	}

	/**
	 * Whether a rule's post type scope covers a post type. No scope means all.
	 *
	 * @param array  $rule      Sanitized rule.
	 * @param string $post_type Post type slug.
	 *
	 * @return bool
	 */
	private static function scope_includes( array $rule, string $post_type ): bool {
		return empty( $rule['postTypes'] ) || in_array( $post_type, $rule['postTypes'], true );
	}

	/**
	 * The policy that is actually enforced: the saved policy, passed through
	 * the `blockpolicy_settings` filter so code can add to or override it.
	 *
	 * The settings page reads and writes get()/save() directly, so rules added
	 * by the filter are enforced but never shown in, or saved from, the UI.
	 *
	 * @return array{blocks: array<string, array{hidden?: bool, unregistered?: bool, postTypes?: string[], roles?: string[]}>}
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
		 *                         `hidden` (bool), `unregistered` (bool),
		 *                         `postTypes` (string[], where those apply; empty
		 *                         means all) and `roles` (string[], who they
		 *                         apply to; empty means everyone).
		 * }
		 */
		$filtered = apply_filters( 'blockpolicy_settings', $saved );

		return is_array( $filtered ) ? $this->sanitize( $filtered ) : $saved;
	}

	/**
	 * Whether a rule applies to a user, based on its `roles`.
	 *
	 * @param array    $rule  Sanitized rule.
	 * @param string[] $roles The user's roles.
	 *
	 * @return bool
	 */
	public static function rule_applies_to( array $rule, array $roles ): bool {
		if ( empty( $rule['roles'] ) ) {
			return true;
		}

		return (bool) array_intersect( $rule['roles'], $roles );
	}

	/**
	 * Enforced rules that apply to the current user.
	 *
	 * @return array<string, array{hidden?: bool, unregistered?: bool, postTypes?: string[], roles?: string[]}>
	 */
	private function rules_for_current_user(): array {
		$roles = (array) wp_get_current_user()->roles;

		return array_filter(
			$this->effective()['blocks'],
			static fn( $rule ) => self::rule_applies_to( $rule, $roles )
		);
	}

	/**
	 * Names of blocks whose rule applies to the current user and matches a predicate.
	 *
	 * @param callable $predicate Receives a rule array, returns bool.
	 *
	 * @return string[]
	 */
	private function blocks_where( callable $predicate ): array {
		return array_keys( array_filter( $this->rules_for_current_user(), $predicate ) );
	}
}
