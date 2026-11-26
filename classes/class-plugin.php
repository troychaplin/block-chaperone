<?php
/**
 * Plugin bootstrap.
 *
 * @package BlockChaperone
 */

namespace BlockChaperone;

/**
 * Wires up the plugin's modules.
 */
class Plugin {

	/**
	 * Absolute path to the plugin root.
	 *
	 * @var string
	 */
	private string $plugin_dir;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_dir Absolute path to the plugin root.
	 */
	public function __construct( string $plugin_dir ) {
		$this->plugin_dir = $plugin_dir;
	}

	/**
	 * Instantiate and initialise every module.
	 */
	public function init(): void {
		$catalog = new Block_Catalog();
		$store   = new Policy_Store();

		$modules = array(
			new Allowed_Blocks( $store ),
			new Enqueues( $this->plugin_dir . '/build', $store ),
			new Settings_Page(),
			new Rest_Controller( $catalog, $store ),
		);

		foreach ( $modules as $module ) {
			$module->init();
		}
	}
}
