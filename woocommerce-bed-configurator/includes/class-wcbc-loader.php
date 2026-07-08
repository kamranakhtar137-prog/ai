<?php
/**
 * Plugin loader.
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCBC_Loader {

	/**
	 * Singleton instance.
	 *
	 * @var WCBC_Loader|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Load dependencies.
	 */
	private function includes() {
		require_once WCBC_PLUGIN_DIR . 'includes/class-wcbc-admin.php';
		require_once WCBC_PLUGIN_DIR . 'includes/class-wcbc-frontend.php';
		require_once WCBC_PLUGIN_DIR . 'includes/class-wcbc-cart.php';
		require_once WCBC_PLUGIN_DIR . 'includes/class-wcbc-ajax.php';
	}

	/**
	 * Register hooks.
	 */
	private function hooks() {
		WCBC_Admin::init();
		WCBC_Frontend::init();
		WCBC_Cart::init();
		WCBC_Ajax::init();

		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register shared assets.
	 */
	public function register_assets() {
		wp_register_style(
			'wcbc-configurator',
			WCBC_PLUGIN_URL . 'assets/css/configurator.css',
			array(),
			WCBC_VERSION
		);
		wp_register_script(
			'wcbc-accordion',
			WCBC_PLUGIN_URL . 'assets/js/accordion.js',
			array(),
			WCBC_VERSION,
			true
		);
		wp_register_script(
			'wcbc-configurator',
			WCBC_PLUGIN_URL . 'assets/js/configurator.js',
			array( 'jquery', 'wcbc-accordion' ),
			WCBC_VERSION,
			true
		);
	}
}
