<?php
/**
 * Catalog of blocks that a policy can be applied to.
 *
 * @package BlockPolicy
 */

namespace BlockPolicy;

use WP_Block_Editor_Context;
use WP_Block_Type;
use WP_Block_Type_Registry;

/**
 * Builds the list of top-level blocks shown in the settings table, plus the
 * post types a block can be restricted in.
 *
 * Child blocks (any block declaring `parent` or `ancestor`) are left out: they
 * can only be inserted inside their parent, so they follow its policy.
 */
class Block_Catalog {

	/**
	 * Blocks whose policy can't be changed. Hiding or unregistering the default
	 * block breaks typing and the block appender.
	 *
	 * @var string[]
	 */
	public const PROTECTED_BLOCKS = array( 'core/paragraph' );

	/**
	 * Core post types that use the block editor internally but aren't content
	 * authors insert blocks into.
	 *
	 * @var string[]
	 */
	private const EXCLUDED_POST_TYPES = array(
		'wp_navigation',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_font_family',
		'wp_font_face',
	);

	/**
	 * Display name used for the `core` namespace.
	 */
	private const CORE_SOURCE = 'WordPress';

	/**
	 * Memoised plugin directory slug => plugin name map.
	 *
	 * @var array<string, string>|null
	 */
	private ?array $plugin_names = null;

	/**
	 * Whether a block can have its policy changed.
	 *
	 * @param string $block_name Block name.
	 *
	 * @return bool
	 */
	public static function is_protected( string $block_name ): bool {
		return in_array( $block_name, self::PROTECTED_BLOCKS, true );
	}

	/**
	 * Whether a registered block belongs in the catalog (top level, insertable).
	 *
	 * @param WP_Block_Type $block_type Block type.
	 *
	 * @return bool
	 */
	public static function is_listable( WP_Block_Type $block_type ): bool {
		if ( ! empty( $block_type->parent ) || ! empty( $block_type->ancestor ) ) {
			return false;
		}

		return (bool) ( $block_type->supports['inserter'] ?? true );
	}

	/**
	 * Top-level blocks as rows for the settings table, sorted by title.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_blocks(): array {
		$category_labels = $this->get_category_labels();
		$rows            = array();

		foreach ( WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $block_type ) {
			if ( ! self::is_listable( $block_type ) ) {
				continue;
			}

			$category = (string) ( $block_type->category ?? '' );
			$source   = $this->resolve_source( $block_type );

			$rows[] = array(
				'name'          => $name,
				'title'         => $block_type->title ? $block_type->title : $name,
				'description'   => (string) $block_type->description,
				'category'      => $category,
				'categoryLabel' => $category_labels[ $category ] ?? ( '' !== $category ? $category : __( 'Uncategorized', 'blockpolicy' ) ),
				'source'        => $source['label'],
				'sourceType'    => $source['type'],
				'icon'          => is_string( $block_type->icon ) ? $block_type->icon : null,
				'protected'     => self::is_protected( $name ),
			);
		}

		usort(
			$rows,
			static fn( $a, $b ) => strcasecmp( $a['title'], $b['title'] )
		);

		return $rows;
	}

	/**
	 * Post types that use the block editor and appear in the admin UI.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public function get_post_types(): array {
		$post_types = array();

		foreach ( get_post_types( array( 'show_ui' => true ), 'objects' ) as $post_type ) {
			if ( in_array( $post_type->name, self::EXCLUDED_POST_TYPES, true ) || ! use_block_editor_for_post_type( $post_type->name ) ) {
				continue;
			}

			$post_types[] = array(
				'value' => $post_type->name,
				'label' => $post_type->labels->name,
			);
		}

		usort(
			$post_types,
			static fn( $a, $b ) => strcasecmp( $a['label'], $b['label'] )
		);

		return $post_types;
	}

	/**
	 * User roles a block's restrictions can be limited to, in the site's
	 * role order (Administrator first).
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public function get_roles(): array {
		$roles = array();

		foreach ( wp_roles()->get_names() as $slug => $name ) {
			$roles[] = array(
				'value' => $slug,
				'label' => translate_user_role( $name ),
			);
		}

		return $roles;
	}

	/**
	 * Slugs of the post types returned by get_post_types().
	 *
	 * @return string[]
	 */
	public function get_post_type_slugs(): array {
		return array_column( $this->get_post_types(), 'value' );
	}

	/**
	 * Block category slug => label map.
	 *
	 * @return array<string, string>
	 */
	private function get_category_labels(): array {
		$labels = array();

		foreach ( get_block_categories( new WP_Block_Editor_Context() ) as $category ) {
			if ( isset( $category['slug'], $category['title'] ) ) {
				$labels[ $category['slug'] ] = $category['title'];
			}
		}

		return $labels;
	}

	/**
	 * Work out which plugin or theme registered a block.
	 *
	 * Core blocks are identified by namespace. Others are traced through the
	 * folder their registered scripts/styles load from; failing that, the
	 * namespace is matched against plugin folder names, then title-cased.
	 *
	 * @param WP_Block_Type $block_type Block type.
	 *
	 * @return array{label: string, type: string} Type is core, plugin, theme or unknown.
	 */
	private function resolve_source( WP_Block_Type $block_type ): array {
		$namespace = strtok( $block_type->name, '/' );

		if ( 'core' === $namespace ) {
			return array(
				'label' => self::CORE_SOURCE,
				'type'  => 'core',
			);
		}

		$from_assets = $this->resolve_source_from_assets( $block_type );
		if ( $from_assets ) {
			return $from_assets;
		}

		$plugin_names = $this->get_plugin_names();
		if ( isset( $plugin_names[ $namespace ] ) ) {
			return array(
				'label' => $plugin_names[ $namespace ],
				'type'  => 'plugin',
			);
		}

		return array(
			'label' => ucwords( str_replace( array( '-', '_' ), ' ', (string) $namespace ) ),
			'type'  => 'unknown',
		);
	}

	/**
	 * Resolve a block's source from the URL of any script or style it registered.
	 *
	 * @param WP_Block_Type $block_type Block type.
	 *
	 * @return array{label: string, type: string}|null
	 */
	private function resolve_source_from_assets( WP_Block_Type $block_type ): ?array {
		$candidates = array(
			array( wp_scripts(), $block_type->editor_script_handles ),
			array( wp_scripts(), $block_type->script_handles ),
			array( wp_scripts(), $block_type->view_script_handles ),
			array( wp_styles(), $block_type->editor_style_handles ),
			array( wp_styles(), $block_type->style_handles ),
		);

		$plugins_path = $this->url_path( plugins_url() );
		$themes_path  = $this->url_path( get_theme_root_uri() );

		foreach ( $candidates as list( $registry, $handles ) ) {
			foreach ( (array) $handles as $handle ) {
				$src = $registry->registered[ $handle ]->src ?? '';
				if ( ! is_string( $src ) || '' === $src ) {
					continue;
				}

				$path = $this->url_path( $src );

				$plugin_dir = $this->first_segment_after( $path, $plugins_path );
				if ( $plugin_dir ) {
					$plugin_names = $this->get_plugin_names();
					return array(
						'label' => $plugin_names[ $plugin_dir ] ?? $plugin_dir,
						'type'  => 'plugin',
					);
				}

				$theme_dir = $this->first_segment_after( $path, $themes_path );
				if ( $theme_dir ) {
					$theme = wp_get_theme( $theme_dir );
					return array(
						'label' => $theme->exists() ? $theme->get( 'Name' ) : $theme_dir,
						'type'  => 'theme',
					);
				}
			}
		}

		return null;
	}

	/**
	 * Path component of a (possibly relative) URL, with a trailing slash.
	 *
	 * @param string $url URL.
	 *
	 * @return string
	 */
	private function url_path( string $url ): string {
		$path = wp_parse_url( $url, PHP_URL_PATH );

		return trailingslashit( is_string( $path ) ? $path : '' );
	}

	/**
	 * First path segment of $path after $base, or null if $path isn't under $base.
	 *
	 * @param string $path Path to inspect.
	 * @param string $base Base path, with trailing slash.
	 *
	 * @return string|null
	 */
	private function first_segment_after( string $path, string $base ): ?string {
		if ( '/' === $base || ! str_starts_with( $path, $base ) ) {
			return null;
		}

		$segment = strtok( substr( $path, strlen( $base ) ), '/' );

		return ( false !== $segment && '' !== $segment ) ? $segment : null;
	}

	/**
	 * Installed plugin directory slug => plugin name.
	 *
	 * @return array<string, string>
	 */
	private function get_plugin_names(): array {
		if ( null !== $this->plugin_names ) {
			return $this->plugin_names;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->plugin_names = array();

		foreach ( get_plugins() as $plugin_file => $plugin_data ) {
			$slug = dirname( $plugin_file );
			if ( '.' !== $slug && ! empty( $plugin_data['Name'] ) ) {
				$this->plugin_names[ $slug ] = $plugin_data['Name'];
			}
		}

		return $this->plugin_names;
	}
}
