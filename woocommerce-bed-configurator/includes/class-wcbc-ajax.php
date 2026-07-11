<?php
/**
 * AJAX handlers.
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCBC_Ajax {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_wcbc_calculate_price', array( __CLASS__, 'calculate_price' ) );
		add_action( 'wp_ajax_nopriv_wcbc_calculate_price', array( __CLASS__, 'calculate_price' ) );
	}

	/**
	 * Calculate price and layers via AJAX.
	 */
	public static function calculate_price() {
		check_ajax_referer( 'wcbc_configurator', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$selections = isset( $_POST['selections'] ) ? (array) wp_unslash( $_POST['selections'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
		$selections = array_map( 'sanitize_text_field', $selections );

		if ( isset( $_POST['drawers_open'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$selections['drawers_open'] = sanitize_text_field( wp_unslash( $_POST['drawers_open'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( ! $product_id || ! WCBC_Frontend::is_enabled( $product_id ) ) {
			wp_send_json_error( array( 'message' => 'Invalid product' ), 400 );
		}

		$config = WCBC_Config::get_product_config( $product_id );
		$calc   = WCBC_Config::calculate( $config, $selections );

		wp_send_json_success(
			array(
				'price'       => $calc['price'],
				'price_html'  => wc_price( $calc['price'] ),
				'layers'      => $calc['layers'],
				'labels'      => $calc['labels'],
				'selections'  => isset( $calc['selections'] ) ? $calc['selections'] : array(),
				'formatted'   => number_format( $calc['price'], 2, '.', '' ),
			)
		);
	}
}
