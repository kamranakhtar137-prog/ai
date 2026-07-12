<?php
/**
 * Demo product setup on activation.
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCBC_Demo {

	const DEMO_SKU = 'wcbc-demo-bed';

	/**
	 * Create demo product if it does not exist.
	 *
	 * @return int Product ID.
	 */
	public static function create_demo_product() {
		$existing = wc_get_product_id_by_sku( self::DEMO_SKU );
		if ( $existing ) {
			update_post_meta( $existing, WCBC_Config::ENABLED_KEY, 'yes' );
			update_post_meta( $existing, WCBC_Config::META_KEY, WCBC_Config::get_default_config() );
			return $existing;
		}

		$product = new WC_Product_Simple();
		$product->set_name( 'Build Your Own Bed (Demo)' );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_sku( self::DEMO_SKU );
		$product->set_regular_price( '299.99' );
		$product->set_short_description( 'Demo product for the WooCommerce Bed Configurator plugin. Configure size, colour, headboard, base depth, and storage.' );
		$product->set_description( '<p>This demo product showcases the Happy Beds–style build-your-own-bed configurator for WooCommerce.</p><p>Activate the plugin, visit this product page, and use the accordion options to customize your bed with live preview images and dynamic pricing.</p>' );
		$product->set_manage_stock( false );
		$product->set_stock_status( 'instock' );

		$product_id = $product->save();

		if ( $product_id ) {
			update_post_meta( $product_id, WCBC_Config::ENABLED_KEY, 'yes' );
			update_post_meta( $product_id, WCBC_Config::META_KEY, WCBC_Config::get_default_config() );

			$thumb = WCBC_PLUGIN_DIR . 'demo-images/layers/base-beige.png';
			if ( file_exists( $thumb ) ) {
				self::set_product_image_from_file( $product_id, $thumb );
			}
		}

		return $product_id;
	}

	/**
	 * Upload file to media library and set as product image.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $file_path File path.
	 */
	private static function set_product_image_from_file( $product_id, $file_path ) {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$filename = basename( $file_path );
		$upload   = wp_upload_bits( $filename, null, file_get_contents( $file_path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( ! empty( $upload['error'] ) ) {
			return;
		}

		$filetype = wp_check_filetype( $filename, null );
		$attachment = array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $upload['file'], $product_id );
		if ( ! is_wp_error( $attach_id ) ) {
			$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			set_post_thumbnail( $product_id, $attach_id );
		}
	}
}
