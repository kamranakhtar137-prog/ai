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
	 * Image layers rendered in preview stack.
	 *
	 * @return string[]
	 */
	public static function get_layers() {
		return array( 'shadow', 'legs', 'storage_back', 'base', 'headboard', 'storage_1', 'storage_2', 'storage_3' );
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

		$hb_layers = WCBC_HappyBeds_Resolver::build_layers( $selection_defaults, $selection_defaults );

		return array(
			'base_price' => 299.99,
			'layers'     => $hb_layers,
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
				array(
					'id'       => 'colour',
					'label'    => 'Colour',
					'icon'     => 'colour',
					'required' => true,
					'options'  => array(
						self::opt( 'beige-velvet', 'Beige', 'Velvet', 0, $base . 'swatches/colour/beige-velvet.png', array(
							'base'      => $base . 'layers/base-beige.png',
							'headboard' => $base . 'layers/headboard-cornell.png',
						) ),
						self::opt( 'black-velvet', 'Black', 'Velvet', 15, $base . 'swatches/colour/black-velvet.png', array(
							'base'      => $base . 'layers/base-black.png',
							'headboard' => $base . 'layers/base-black.png',
						) ),
						self::opt( 'graphite-velvet', 'Graphite', 'Velvet', 10, $base . 'swatches/colour/graphite-velvet.png', array(
							'base'      => $base . 'layers/base-black.png',
							'headboard' => $base . 'layers/base-black.png',
						) ),
						self::opt( 'cream-cotton', 'Cream', 'Cotton', 0, $base . 'swatches/colour/cream-cotton.png', array(
							'base'      => $base . 'layers/base-beige.png',
							'headboard' => $base . 'layers/headboard-cornell.png',
						) ),
						self::opt( 'midnight-blue-cotton', 'Midnight Blue', 'Cotton', 12, $base . 'swatches/colour/midnight-blue-cotton.png', array(
							'base'      => $base . 'layers/base-black.png',
							'headboard' => $base . 'layers/base-black.png',
						) ),
					),
				),
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
		$defaults = self::get_default_config();
		$config['layers']     = ! empty( $config['layers'] ) ? $config['layers'] : $defaults['layers'];
		$config['defaults']   = ! empty( $config['defaults'] ) ? $config['defaults'] : $defaults['defaults'];
		$config['base_price'] = isset( $config['base_price'] ) ? (float) $config['base_price'] : $defaults['base_price'];
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
