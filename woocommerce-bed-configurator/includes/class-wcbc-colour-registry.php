<?php
/**
 * Happy Beds colour / fabric registry (Velvet + Linen).
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCBC_Colour_Registry {

	/**
	 * Default Happy Beds colour definitions.
	 *
	 * hb_fabric = folder under bases/ (velvet, linoso).
	 * hb_code   = numeric suffix in filenames e.g. bedbase_3ft_14i_36.png → 36
	 * hb_drawer = drawer reference segment for 4ft6 sizes e.g. 190
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function default_options() {
		$base = WCBC_PLUGIN_URL . 'demo-images/swatches/colour/';

		$velvet = array(
			array( 'id' => 'light-silver-velvet', 'label' => 'Light Silver', 'code' => '31', 'drawer' => '131' ),
			array( 'id' => 'asphalt-velvet', 'label' => 'Asphalt', 'code' => '32', 'drawer' => '132' ),
			array( 'id' => 'graphite-velvet', 'label' => 'Graphite', 'code' => '20', 'drawer' => '120' ),
			array( 'id' => 'black-velvet', 'label' => 'Black', 'code' => '10', 'drawer' => '110' ),
			array( 'id' => 'blue-marine-velvet', 'label' => 'Blue Marine', 'code' => '33', 'drawer' => '133' ),
			array( 'id' => 'emerald-velvet', 'label' => 'Emerald', 'code' => '34', 'drawer' => '134' ),
			array( 'id' => 'duck-egg-blue-velvet', 'label' => 'Duck Egg Blue', 'code' => '35', 'drawer' => '135' ),
			array( 'id' => 'pink-velvet', 'label' => 'Pink', 'code' => '37', 'drawer' => '137' ),
			array( 'id' => 'beige-velvet', 'label' => 'Beige', 'code' => '30', 'drawer' => '190' ),
			array( 'id' => 'mustard-velvet', 'label' => 'Mustard', 'code' => '36', 'drawer' => '136' ),
		);

		$linen = array(
			array( 'id' => 'black-linen', 'label' => 'Black', 'code' => '11', 'drawer' => '111' ),
			array( 'id' => 'charcoal-linen', 'label' => 'Charcoal', 'code' => '12', 'drawer' => '112' ),
			array( 'id' => 'chocolate-linen', 'label' => 'Chocolate', 'code' => '13', 'drawer' => '113' ),
			array( 'id' => 'cream-linen', 'label' => 'Cream', 'code' => '14', 'drawer' => '114' ),
			array( 'id' => 'duck-egg-blue-linen', 'label' => 'Duck Egg Blue', 'code' => '15', 'drawer' => '115' ),
			array( 'id' => 'lime-linen', 'label' => 'Lime', 'code' => '16', 'drawer' => '116' ),
			array( 'id' => 'midnight-blue-linen', 'label' => 'Midnight Blue', 'code' => '17', 'drawer' => '117' ),
			array( 'id' => 'orchid-linen', 'label' => 'Orchid', 'code' => '18', 'drawer' => '118' ),
			array( 'id' => 'plum-linen', 'label' => 'Plum', 'code' => '19', 'drawer' => '119' ),
			array( 'id' => 'red-linen', 'label' => 'Red', 'code' => '21', 'drawer' => '121' ),
			array( 'id' => 'slate-grey-linen', 'label' => 'Slate Grey', 'code' => '22', 'drawer' => '122' ),
			array( 'id' => 'white-linen', 'label' => 'White', 'code' => '23', 'drawer' => '123' ),
			array( 'id' => 'silver-grey-linen', 'label' => 'Silver Grey', 'code' => '24', 'drawer' => '124' ),
		);

		$options = array();

		foreach ( $velvet as $row ) {
			$options[] = self::build_option( $row, 'velvet', 'velvet', $base );
		}
		foreach ( $linen as $row ) {
			$options[] = self::build_option( $row, 'linen', 'linoso', $base );
		}

		return $options;
	}

	/**
	 * Build a colour option array for config JSON.
	 *
	 * @param array<string,string> $row Colour row.
	 * @param string               $fabric_group Filter group velvet|linen.
	 * @param string               $hb_fabric Happy Beds base folder.
	 * @param string               $swatch_base Swatch URL base.
	 * @return array<string,mixed>
	 */
	private static function build_option( $row, $fabric_group, $hb_fabric, $swatch_base ) {
		return array(
			'id'       => $row['id'],
			'label'    => $row['label'],
			'sublabel' => ucfirst( $fabric_group ),
			'price'    => 0.0,
			'image'    => $swatch_base . $row['id'] . '.png',
			'layers'   => array(
				'fabric'    => $fabric_group,
				'hb_fabric' => $hb_fabric,
				'hb_code'   => $row['code'],
				'hb_drawer' => $row['drawer'],
			),
			'badge'    => '',
		);
	}

	public static function colour_slugs() {
		$slugs = array();
		foreach ( self::default_options() as $option ) {
			if ( ! empty( $option['id'] ) ) {
				$slugs[] = $option['id'];
			}
		}
		return $slugs;
	}

	/**
	 * Colour group definition with Velvet / Linen filters.
	 *
	 * @return array<string,mixed>
	 */
	public static function colour_group() {
		return array(
			'id'          => 'colour',
			'label'       => 'Colour',
			'icon'        => 'colour',
			'required'    => true,
			'filter_type' => 'fabric',
			'filters'     => array(
				array( 'id' => 'velvet', 'label' => 'Velvet', 'filter' => 'velvet' ),
				array( 'id' => 'linen', 'label' => 'Linen', 'filter' => 'linen' ),
			),
			'options'     => self::default_options(),
		);
	}

	/**
	 * Build colourMeta map for resolver / JS from product config.
	 *
	 * @param array<string,mixed> $config Config.
	 * @return array<string,array{fabric:string,hb_fabric:string,code:string,drawer:string}>
	 */
	public static function meta_map_from_config( $config ) {
		$map = array();
		if ( empty( $config['groups'] ) ) {
			return $map;
		}
		foreach ( $config['groups'] as $group ) {
			if ( 'colour' !== $group['id'] || empty( $group['options'] ) ) {
				continue;
			}
			foreach ( $group['options'] as $option ) {
				$meta = self::meta_from_option( $option );
				if ( $meta ) {
					$map[ $option['id'] ] = $meta;
				}
			}
		}
		return $map;
	}

	/**
	 * Extract Happy Beds meta from a colour option.
	 *
	 * @param array<string,mixed> $option Option.
	 * @return array{fabric:string,hb_fabric:string,code:string,drawer:string}|null
	 */
	public static function meta_from_option( $option ) {
		$layers = isset( $option['layers'] ) && is_array( $option['layers'] ) ? $option['layers'] : array();
		$code   = isset( $layers['hb_code'] ) ? (string) $layers['hb_code'] : '';
		if ( '' === $code && isset( $layers['code'] ) ) {
			$code = (string) $layers['code'];
		}
		if ( '' === $code ) {
			return null;
		}

		$hb_fabric = isset( $layers['hb_fabric'] ) ? (string) $layers['hb_fabric'] : 'velvet';
		if ( isset( $layers['fabric'] ) && 'linen' === $layers['fabric'] && 'velvet' === $hb_fabric ) {
			$hb_fabric = 'linoso';
		}

		$drawer = isset( $layers['hb_drawer'] ) ? (string) $layers['hb_drawer'] : '';
		if ( '' === $drawer && isset( $layers['drawer'] ) ) {
			$drawer = (string) $layers['drawer'];
		}

		return array(
			'fabric'    => isset( $layers['fabric'] ) ? (string) $layers['fabric'] : $hb_fabric,
			'hb_fabric' => $hb_fabric,
			'code'      => $code,
			'drawer'    => $drawer,
		);
	}
}
