<?php
/**
 * REST API for the settings page.
 *
 * @package Block_Policy
 */

namespace Block_Policy;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Routes:
 *
 * - GET  /block-policy/v1/blocks    Block catalog + editor post types.
 * - GET  /block-policy/v1/settings  Saved policy.
 * - POST /block-policy/v1/settings  Replace the saved policy; returns the sanitized result.
 */
class Rest_Controller {

	/**
	 * REST namespace.
	 */
	public const REST_NAMESPACE = 'block-policy/v1';

	/**
	 * Block catalog.
	 *
	 * @var Block_Catalog
	 */
	private Block_Catalog $catalog;

	/**
	 * Policy store.
	 *
	 * @var Policy_Store
	 */
	private Policy_Store $store;

	/**
	 * Constructor.
	 *
	 * @param Block_Catalog $catalog Block catalog.
	 * @param Policy_Store  $store   Policy store.
	 */
	public function __construct( Block_Catalog $catalog, Policy_Store $store ) {
		$this->catalog = $catalog;
		$this->store   = $store;
	}

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/blocks',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_blocks' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Only users who can manage the settings page may read or write.
	 *
	 * @return true|WP_Error
	 */
	public function permissions_check() {
		if ( current_user_can( Settings_Page::CAPABILITY ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'You do not have permission to manage block policies.', 'block-policy' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * GET /blocks.
	 *
	 * @return WP_REST_Response
	 */
	public function get_blocks(): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'blocks'    => $this->catalog->get_blocks(),
				'postTypes' => $this->catalog->get_post_types(),
			)
		);
	}

	/**
	 * GET /settings.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings(): WP_REST_Response {
		return new WP_REST_Response( $this->for_response( $this->store->get() ) );
	}

	/**
	 * POST /settings.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_settings( WP_REST_Request $request ) {
		$policy = $request->get_json_params();

		if ( ! is_array( $policy ) ) {
			return new WP_Error(
				'block_policy_invalid_settings',
				__( 'Settings must be a JSON object.', 'block-policy' ),
				array( 'status' => 400 )
			);
		}

		return new WP_REST_Response( $this->for_response( $this->store->save( $policy ) ) );
	}

	/**
	 * Make sure an empty `blocks` map serializes as `{}` rather than `[]`.
	 *
	 * @param array $policy Sanitized policy.
	 *
	 * @return array
	 */
	private function for_response( array $policy ): array {
		$policy['blocks'] = (object) $policy['blocks'];

		return $policy;
	}
}
