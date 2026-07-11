<?php
/**
 * Plugin Name: WooCommerce Bed Configurator
 * Plugin URI: https://github.com/example/woocommerce-bed-configurator
 * Description: Build-your-own-bed product configurator for WooCommerce with layered preview images, accordion options, and dynamic pricing.
 * Version: 1.0.11
 * Author: Cursor
 * Author URI: https://cursor.com
 * Text Domain: wc-bed-configurator
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCBC_VERSION', '1.0.11' );
define( 'WCBC_PLUGIN_FILE', __FILE__ );
define( 'WCBC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCBC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCBC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check WooCommerce is active.
 */
function wcbc_check_woocommerce() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>';
				esc_html_e( 'WooCommerce Bed Configurator requires WooCommerce to be installed and active.', 'wc-bed-configurator' );
				echo '</p></div>';
			}
		);
		return false;
	}
	return true;
}

require_once WCBC_PLUGIN_DIR . 'includes/class-wcbc-config.php';
require_once WCBC_PLUGIN_DIR . 'includes/class-wcbc-happybeds-resolver.php';
require_once WCBC_PLUGIN_DIR . 'includes/class-wcbc-layer-builder.php';
require_once WCBC_PLUGIN_DIR . 'includes/class-wcbc-loader.php';

/**
 * Initialize plugin.
 */
function wcbc_init() {
	if ( ! wcbc_check_woocommerce() ) {
		return;
	}
	WCBC_Loader::instance();
}
add_action( 'plugins_loaded', 'wcbc_init', 20 );

/**
 * Activation hook — defer demo product setup until WooCommerce is fully loaded.
 */
function wcbc_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( WCBC_PLUGIN_BASENAME );
		wp_die(
			esc_html__( 'WooCommerce Bed Configurator requires WooCommerce.', 'wc-bed-configurator' ),
			esc_html__( 'Plugin Activation Error', 'wc-bed-configurator' ),
			array( 'back_link' => true )
		);
	}

	update_option( 'wcbc_pending_demo_setup', '1', false );
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wcbc_activate' );

/**
 * Create demo product after WooCommerce has booted.
 */
function wcbc_run_pending_setup() {
	if ( '1' !== get_option( 'wcbc_pending_demo_setup' ) ) {
		return;
	}

	if ( ! function_exists( 'wc_get_product_id_by_sku' ) || ! class_exists( 'WC_Product_Simple' ) ) {
		return;
	}

	require_once WCBC_PLUGIN_DIR . 'includes/class-wcbc-demo.php';
	WCBC_Demo::create_demo_product();
	delete_option( 'wcbc_pending_demo_setup' );
}
add_action( 'woocommerce_init', 'wcbc_run_pending_setup', 5 );
add_action( 'admin_init', 'wcbc_run_pending_setup', 20 );

/**
 * Deactivation hook.
 */
function wcbc_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wcbc_deactivate' );

/**
 * Declare HPOS compatibility.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
