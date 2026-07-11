<?php
/**
 * Default configurator schema and helpers.
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCBC_Config {

	/**
	 * Meta key for product config.
	 */
	const META_KEY = '_wcbc_config';

	/**
	 * Meta key to enable configurator on product.
	 */
	const ENABLED_KEY = '_wcbc_enabled';

	/**
	 * Product layer attachment IDs keyed by layer slug.
	 */
	const LAYER_MEDIA_KEY = '_wcbc_layer_media';

	/**
	 * Image source: auto, happybeds, media, hybrid.
	 */
	const IMAGE_SOURCE_KEY = '_wcbc_image_source';

	/**
	 * Human labels for preview layers.
	 *
	 * @return array<string,string>
	 */
	public static function get_layer_labels() {
		return array(
			'shadow'       => __( 'Shadow', 'wc-bed-configurator' ),
			'legs'         => __( 'Legs', 'wc-bed-configurator' ),
			'headboard'    => __( 'Headboard', 'wc-bed-configurator' ),
			'storage_back' => __( 'Storage back', 'wc-bed-configurator' ),
			'base'         => __( 'Base', 'wc-bed-configurator' ),
			'storage_2'    => __( 'Storage drawer back', 'wc-bed-configurator' ),
			'storage_3'    => __( 'Storage drawer front', 'wc-bed-configurator' ),
			'storage_1'    => __( 'Storage extra', 'wc-bed-configurator' ),
		);
	}

	/**
	 * Get saved layer attachment IDs for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array<string,int>
	 */
	public static function get_layer_media( $product_id ) {
		$raw = get_post_meta( $product_id, self::LAYER_MEDIA_KEY, true );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( self::get_layers() as $layer ) {
			if ( ! empty( $raw[ $layer ] ) ) {
				$out[ $layer ] = absint( $raw[ $layer ] );
			}
		}
		return $out;
	}

	/**
	 * Resolve layer attachment IDs to public URLs.
	 *
	 * @param array<string,int> $attachment_ids Attachment IDs keyed by layer.
	 * @return array<string,string>
	 */
	public static function layer_urls_from_media( $attachment_ids ) {
		$layers = array();
		foreach ( self::get_layers() as $layer ) {
			$layers[ $layer ] = '';
			if ( empty( $attachment_ids[ $layer ] ) ) {
				continue;
			}
			$url = wp_get_attachment_image_url( (int) $attachment_ids[ $layer ], 'full' );
			if ( $url ) {
				$layers[ $layer ] = $url;
			}
		}
		return $layers;
	}

	/**
	 * Product image source mode.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	public static function get_image_source( $product_id ) {
		$source = get_post_meta( $product_id, self::IMAGE_SOURCE_KEY, true );
		if ( in_array( $source, array( 'auto', 'happybeds', 'media', 'hybrid' ), true ) ) {
			return $source;
		}
		return 'auto';
	}

	/**
	 * Image layers rendered in preview stack.
	 *
	 * @return string[]
	 */
	public static function get_layers() {
		// Match Happy Beds DOM stacking order.
		return array( 'shadow', 'legs', 'headboard', 'storage_back', 'base', 'storage_2', 'storage_3', 'storage_1' );
	}

	/**
	 * Build default demo configuration.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_default_config() {
		$base = WCBC_PLUGIN_URL . 'demo-images/';

		$selection_defaults = array(
			'size'       => 'double',
			'colour'     => 'beige-velvet',
			'headboard'  => 'cornell-lined',
			'base_depth' => '14-inch',
			'storage'    => '2-drawers',
		);

		return array(
			'base_price' => 299.99,
			'layers'     => array(),
			'groups'     => array(
				array(
					'id'       => 'size',
					'label'    => 'Size',
					'icon'     => 'size',
					'required' => true,
					'options'  => array(
						self::opt( 'small-single', 'Small Single', '2ft 6', 0, $base . 'swatches/size/small-single.png', array( 'size' => '2ft6' ) ),
						self::opt( 'single', 'Single', '3ft', 20, $base . 'swatches/size/single.png', array( 'size' => '3ft' ) ),
						self::opt( 'small-double', 'Small Double', '4ft', 40, $base . 'swatches/size/small-double.png', array( 'size' => '4ft' ) ),
						self::opt( 'double', 'Double', '4ft 6', 60, $base . 'swatches/size/double.png', array( 'size' => '4ft6' ) ),
						self::opt( 'king', 'King Size', '5ft', 80, $base . 'swatches/size/king.png', array( 'size' => '5ft' ) ),
						self::opt( 'super-king', 'Super Kingsize', '6ft', 100, $base . 'swatches/size/super-king.png', array( 'size' => '6ft' ) ),
					),
				),
				WCBC_Colour_Registry::colour_group(),
				array(
					'id'          => 'headboard',
					'label'       => 'Headboard',
					'icon'        => 'headboard',
					'required'    => true,
					'filter_type' => 'shape',
					'filters'     => array(
						array( 'id' => 'cornell', 'label' => 'Cornell', 'filter' => 'cornell' ),
						array( 'id' => 'dudley', 'label' => 'Dudley', 'filter' => 'dudley' ),
						array( 'id' => 'victor', 'label' => 'Victor', 'filter' => 'victor' ),
						array( 'id' => 'none', 'label' => 'No Headboard', 'filter' => 'none' ),
					),
					'options'     => array(
						self::opt( 'cornell-plain', 'Cornell Plain', '', 0, $base . 'swatches/headboard/cornell-plain.png', array( 'headboard' => $base . 'layers/headboard-cornell.png', 'shape' => 'cornell' ) ),
						self::opt( 'cornell-lined', 'Cornell Lined', '', 25, $base . 'swatches/headboard/cornell-lined.png', array( 'headboard' => $base . 'layers/headboard-cornell.png', 'shape' => 'cornell' ) ),
						self::opt( 'cornell-buttoned', 'Cornell Buttoned', '', 35, $base . 'swatches/headboard/cornell-buttoned.png', array( 'headboard' => $base . 'layers/headboard-cornell.png', 'shape' => 'cornell' ) ),
						self::opt( 'dudley-plain', 'Dudley Plain', '', 20, $base . 'swatches/headboard/dudley-plain.png', array( 'headboard' => $base . 'layers/headboard-cornell.png', 'shape' => 'dudley' ) ),
						self::opt( 'victor-plain', 'Victor Plain', '', 30, $base . 'swatches/headboard/victor-plain.png', array( 'headboard' => $base . 'layers/headboard-cornell.png', 'shape' => 'victor' ) ),
						self::opt( 'no-headboard', 'No Headboard', '', -50, $base . 'swatches/headboard/no-headboard.png', array( 'headboard' => $base . 'layers/headboard-none.png', 'shape' => 'none' ) ),
					),
				),
				array(
					'id'       => 'base_depth',
					'label'    => 'Base Depth',
					'icon'     => 'depth',
					'required' => true,
					'options'  => array(
						self::opt( '6-inch', '6 Inch', '', -30, $base . 'swatches/depth/6-inch.png' ),
						self::opt( '10-inch', '10 Inch', '', 0, $base . 'swatches/depth/10-inch.png' ),
						self::opt( '14-inch', '14 Inch', 'Standard', 20, $base . 'swatches/depth/14-inch.png', array(), true ),
					),
				),
				array(
					'id'       => 'storage',
					'label'    => 'Storage Options',
					'icon'     => 'storage',
					'required' => true,
					'options'  => array(
						self::opt( 'no-drawers', 'No Drawers', '', 0, $base . 'swatches/storage/no-drawers.png', array( 'base' => $base . 'layers/base-beige.png' ) ),
						self::opt( 'ottoman', 'Ottoman', '', 80, $base . 'swatches/storage/ottoman.png', array( 'base' => $base . 'layers/base-beige.png' ) ),
						self::opt( '2-drawers', '2 Drawers', '', 50, $base . 'swatches/storage/2-drawers.png', array( 'base' => $base . 'layers/base-beige-2drawers.png' ) ),
						self::opt( '4-drawers', '4 Drawers', '', 90, $base . 'swatches/storage/4-drawers.png', array( 'base' => $base . 'layers/base-beige-2drawers.png' ) ),
						self::opt( 'end-drawer', 'End Drawer', '', 40, $base . 'swatches/storage/end-drawer.png', array( 'base' => $base . 'layers/base-beige-2drawers.png' ) ),
					),
				),
			),
			'defaults'   => $selection_defaults,
		);
	}

	/**
	 * Helper to build option array.
	 *
	 * @param string               $id Option id.
	 * @param string               $label Label.
	 * @param string               $sublabel Sublabel.
	 * @param float                $price Price modifier.
	 * @param string               $image Swatch image.
	 * @param array<string,string> $layers Layer overrides.
	 * @param bool                 $badge Show badge.
	 * @return array<string,mixed>
	 */
	private static function opt( $id, $label, $sublabel, $price, $image, $layers = array(), $badge = false ) {
		return array(
			'id'       => $id,
			'label'    => $label,
			'sublabel' => $sublabel,
			'price'    => (float) $price,
			'image'    => $image,
			'layers'   => $layers,
			'badge'    => $badge ? 'Standard Size' : '',
		);
	}

	/**
	 * Get product config merged with defaults.
	 *
	 * @param int $product_id Product ID.
	 * @return array<string,mixed>
	 */
	public static function get_product_config( $product_id ) {
		$config = get_post_meta( $product_id, self::META_KEY, true );
		if ( ! is_array( $config ) || empty( $config['groups'] ) ) {
			$config = self::get_default_config();
		}
		$config = self::merge_colour_group( $config );
		$defaults = self::get_default_config();
		$config['product_id']   = (int) $product_id;
		$config['layer_media']  = self::get_layer_media( $product_id );
		$config['image_source'] = self::get_image_source( $product_id );
		$config['layers']       = WCBC_Layer_Builder::build( $config, isset( $config['defaults'] ) ? $config['defaults'] : $defaults['defaults'] );
		$config['defaults']     = ! empty( $config['defaults'] ) ? $config['defaults'] : $defaults['defaults'];
		$config['base_price']   = isset( $config['base_price'] ) ? (float) $config['base_price'] : $defaults['base_price'];
		return $config;
	}

	/**
	 * Always use the plugin colour registry (10 Velvet + 13 Linen).
	 *
	 * @param array<string,mixed> $config Config.
	 * @return array<string,mixed>
	 */
	private static function merge_colour_group( $config ) {
		if ( ! class_exists( 'WCBC_Colour_Registry' ) ) {
			return $config;
		}

		$colour_group = WCBC_Colour_Registry::colour_group();
		$merged       = false;

		if ( ! empty( $config['groups'] ) ) {
			foreach ( $config['groups'] as $index => $group ) {
				if ( 'colour' === $group['id'] ) {
					$config['groups'][ $index ] = $colour_group;
					$merged                     = true;
					break;
				}
			}
		}

		if ( ! $merged ) {
			if ( empty( $config['groups'] ) ) {
				$config['groups'] = array();
			}
			array_splice( $config['groups'], 1, 0, array( $colour_group ) );
		}

		return $config;
	}

	/**
	 * Find option by group and id.
	 *
	 * @param array<string,mixed> $config Config.
	 * @param string              $group_id Group id.
	 * @param string              $option_id Option id.
	 * @return array<string,mixed>|null
	 */
	public static function find_option( $config, $group_id, $option_id ) {
		foreach ( $config['groups'] as $group ) {
			if ( $group['id'] !== $group_id ) {
				continue;
			}
			foreach ( $group['options'] as $option ) {
				if ( $option['id'] === $option_id ) {
					return $option;
				}
			}
		}
		return null;
	}

	/**
	 * Calculate price and layers from selections.
	 *
	 * @param array<string,mixed> $config Config.
	 * @param array<string,string> $selections Selections keyed by group id.
	 * @return array{price:float,layers:array<string,string>,labels:array<string,string>}
	 */
	public static function calculate( $config, $selections ) {
		$price  = (float) $config['base_price'];
		$labels = array();

		foreach ( $config['groups'] as $group ) {
			$gid    = $group['id'];
			$sel_id = isset( $selections[ $gid ] ) ? $selections[ $gid ] : ( isset( $config['defaults'][ $gid ] ) ? $config['defaults'][ $gid ] : '' );
			$option = self::find_option( $config, $gid, $sel_id );
			if ( ! $option ) {
				continue;
			}
			$price += (float) $option['price'];
			$labels[ $gid ] = trim( $option['label'] . ( $option['sublabel'] ? ' ' . $option['sublabel'] : '' ) );
		}

		$layers = WCBC_Layer_Builder::build( $config, $selections );

		return array(
			'price'  => max( 0, $price ),
			'layers' => $layers,
			'labels' => $labels,
		);
	}
}
