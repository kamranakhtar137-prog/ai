<?php
/**
 * Cart and order line item handling.
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCBC_Cart {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_add_cart_item', array( __CLASS__, 'adjust_cart_item_price' ), 10, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'get_cart_item_from_session' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_cart_item_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'add_order_item_meta' ), 10, 4 );
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate_add_to_cart' ), 10, 3 );
	}

	/**
	 * Validate configurator selections on add to cart.
	 *
	 * @param bool $passed Passed.
	 * @param int  $product_id Product ID.
	 * @param int  $qty Quantity.
	 * @return bool
	 */
	public static function validate_add_to_cart( $passed, $product_id, $qty ) {
		if ( ! WCBC_Frontend::is_enabled( $product_id ) ) {
			return $passed;
		}
		if ( empty( $_POST['wcbc_selections'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wc_add_notice( __( 'Please configure your bed before adding to basket.', 'wc-bed-configurator' ), 'error' );
			return false;
		}
		return $passed;
	}

	/**
	 * Parse posted selections.
	 *
	 * @return array<string,string>
	 */
	private static function get_posted_selections() {
		if ( empty( $_POST['wcbc_selections'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return array();
		}
		$raw = wp_unslash( $_POST['wcbc_selections'] ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( is_array( $raw ) ) {
			return array_map( 'sanitize_text_field', $raw );
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? array_map( 'sanitize_text_field', $decoded ) : array();
	}

	/**
	 * Add configurator data to cart item.
	 *
	 * @param array<string,mixed> $cart_item_data Cart item data.
	 * @param int                 $product_id Product ID.
	 * @param int                 $variation_id Variation ID.
	 * @return array<string,mixed>
	 */
	public static function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		if ( ! WCBC_Frontend::is_enabled( $product_id ) ) {
			return $cart_item_data;
		}

		$selections = self::get_posted_selections();
		if ( empty( $selections ) ) {
			return $cart_item_data;
		}

		$config = WCBC_Config::get_product_config( $product_id );
		$calc   = WCBC_Config::calculate( $config, $selections );

		$unique = md5( wp_json_encode( $selections ) );

		$cart_item_data['wcbc'] = array(
			'selections' => $selections,
			'labels'     => $calc['labels'],
			'price'      => $calc['price'],
			'unique_key' => $unique,
		);

		// Keep separate cart lines per configuration.
		$cart_item_data['wcbc_unique_key'] = $unique;

		return $cart_item_data;
	}

	/**
	 * Set custom price on cart item.
	 *
	 * @param array<string,mixed> $cart_item Cart item.
	 * @param string              $cart_item_key Key.
	 * @return array<string,mixed>
	 */
	public static function adjust_cart_item_price( $cart_item, $cart_item_key ) {
		if ( isset( $cart_item['wcbc']['price'] ) ) {
			$cart_item['data']->set_price( $cart_item['wcbc']['price'] );
		}
		return $cart_item;
	}

	/**
	 * Restore price from session.
	 *
	 * @param array<string,mixed> $cart_item Cart item.
	 * @param array<string,mixed> $values Session values.
	 * @return array<string,mixed>
	 */
	public static function get_cart_item_from_session( $cart_item, $values ) {
		if ( isset( $values['wcbc'] ) ) {
			$cart_item['wcbc'] = $values['wcbc'];
			$cart_item['data']->set_price( $values['wcbc']['price'] );
		}
		return $cart_item;
	}

	/**
	 * Display configuration in cart/checkout.
	 *
	 * @param array<int,array<string,string>> $item_data Item data.
	 * @param array<string,mixed>             $cart_item Cart item.
	 * @return array<int,array<string,string>>
	 */
	public static function display_cart_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item['wcbc']['labels'] ) ) {
			return $item_data;
		}
		$group_labels = array(
			'size'       => __( 'Size', 'wc-bed-configurator' ),
			'colour'     => __( 'Colour', 'wc-bed-configurator' ),
			'headboard'  => __( 'Headboard', 'wc-bed-configurator' ),
			'base_depth' => __( 'Base Depth', 'wc-bed-configurator' ),
			'storage'    => __( 'Storage', 'wc-bed-configurator' ),
		);
		foreach ( $cart_item['wcbc']['labels'] as $key => $value ) {
			$item_data[] = array(
				'key'   => isset( $group_labels[ $key ] ) ? $group_labels[ $key ] : ucfirst( $key ),
				'value' => $value,
			);
		}
		return $item_data;
	}

	/**
	 * Persist meta on order line items.
	 *
	 * @param WC_Order_Item_Product $item Order item.
	 * @param string                $cart_item_key Key.
	 * @param array<string,mixed>   $values Values.
	 * @param WC_Order              $order Order.
	 */
	public static function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values['wcbc'] ) ) {
			return;
		}
		$item->add_meta_data( '_wcbc_selections', $values['wcbc']['selections'], true );
		$item->add_meta_data( '_wcbc_labels', $values['wcbc']['labels'], true );
		$item->add_meta_data( __( 'Bed configuration', 'wc-bed-configurator' ), wp_json_encode( $values['wcbc']['labels'] ) );
	}
}
