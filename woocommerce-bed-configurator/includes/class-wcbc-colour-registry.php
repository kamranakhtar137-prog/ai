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

	const HB_SWATCH_BASE = 'https://www.happybeds.co.uk/media/configurator/fabric-colors/';

	/**
	 * Default Happy Beds colour definitions.
	 *
	 * hb_fabric = folder under bases/ (velvet, linoso).
	 * hb_code   = numeric suffix in filenames e.g. bedbase_3ft_14i_36.png → 36
	 * hb_drawer = drawer reference segment for 4ft6 sizes e.g. 190
	 *
	 * Linen options use Happy Beds "-cotton" slugs (displayed as Linen).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function default_options() {
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
			array( 'id' => 'black-cotton', 'label' => 'Black', 'code' => '11', 'drawer' => '111' ),
			array( 'id' => 'charcoal-cotton', 'label' => 'Charcoal', 'code' => '12', 'drawer' => '112' ),
			array( 'id' => 'chocolate-cotton', 'label' => 'Chocolate', 'code' => '13', 'drawer' => '113' ),
			array( 'id' => 'cream-cotton', 'label' => 'Cream', 'code' => '14', 'drawer' => '114' ),
			array( 'id' => 'duck-egg-blue-cotton', 'label' => 'Duck Egg Blue', 'code' => '15', 'drawer' => '115' ),
			array( 'id' => 'lime-cotton', 'label' => 'Lime', 'code' => '16', 'drawer' => '116' ),
			array( 'id' => 'midnight-blue-cotton', 'label' => 'Midnight Blue', 'code' => '17', 'drawer' => '117' ),
			array( 'id' => 'orchid-cotton', 'label' => 'Orchid', 'code' => '18', 'drawer' => '118' ),
			array( 'id' => 'plum-cotton', 'label' => 'Plum', 'code' => '19', 'drawer' => '119' ),
			array( 'id' => 'red-cotton', 'label' => 'Red', 'code' => '21', 'drawer' => '121' ),
			array( 'id' => 'slate-grey-cotton', 'label' => 'Slate Grey', 'code' => '22', 'drawer' => '122' ),
			array( 'id' => 'white-cotton', 'label' => 'White', 'code' => '23', 'drawer' => '123' ),
			array( 'id' => 'silver-grey-cotton', 'label' => 'Silver Grey', 'code' => '24', 'drawer' => '124' ),
		);

		$options = array();

		foreach ( $velvet as $row ) {
			$options[] = self::build_option( $row, 'velvet', 'velvet' );
		}
		foreach ( $linen as $row ) {
			$options[] = self::build_option( $row, 'linen', 'linoso' );
		}

		return $options;
	}

	/**
	 * Legacy slug aliases (old plugin ids → current Happy Beds ids).
	 *
	 * @return array<string,string>
	 */
	public static function slug_aliases() {
		return array(
			'black-linen'         => 'black-cotton',
			'charcoal-linen'      => 'charcoal-cotton',
			'chocolate-linen'     => 'chocolate-cotton',
			'cream-linen'         => 'cream-cotton',
			'duck-egg-blue-linen' => 'duck-egg-blue-cotton',
			'lime-linen'          => 'lime-cotton',
			'midnight-blue-linen' => 'midnight-blue-cotton',
			'orchid-linen'        => 'orchid-cotton',
			'plum-linen'          => 'plum-cotton',
			'red-linen'           => 'red-cotton',
			'slate-grey-linen'    => 'slate-grey-cotton',
			'white-linen'         => 'white-cotton',
			'silver-grey-linen'   => 'silver-grey-cotton',
		);
	}

	/**
	 * Resolve a colour slug to the canonical Happy Beds id.
	 *
	 * @param string $slug Colour slug.
	 * @return string
	 */
	public static function resolve_slug( $slug ) {
		$aliases = self::slug_aliases();
		return isset( $aliases[ $slug ] ) ? $aliases[ $slug ] : $slug;
	}

	/**
	 * Build a colour option array for config JSON.
	 *
	 * @param array<string,string> $row Colour row.
	 * @param string               $fabric_group Filter group velvet|linen.
	 * @param string               $hb_fabric Happy Beds base folder.
	 * @return array<string,mixed>
	 */
	private static function build_option( $row, $fabric_group, $hb_fabric ) {
		return array(
			'id'       => $row['id'],
			'label'    => $row['label'],
			'sublabel' => ucfirst( $fabric_group ),
			'price'    => 0.0,
			'image'    => self::HB_SWATCH_BASE . $row['id'] . '.png',
			'layers'   => array(
				'fabric'    => $fabric_group,
				'hb_fabric' => $hb_fabric,
				'hb_code'   => $row['code'],
				'hb_drawer' => $row['drawer'],
			),
			'badge'    => '',
		);
	}

	/**
	 * @return string[]
	 */
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
	 * Colour group — Velvet / Linen sections (matches Happy Beds DOM).
	 *
	 * @return array<string,mixed>
	 */
	public static function colour_group() {
		return array(
			'id'           => 'colour',
			'label'        => 'Colour',
			'icon'         => 'colour',
			'required'     => true,
			'filter_type'  => 'fabric',
			'display_mode' => 'sections',
			'options'      => self::default_options(),
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

		foreach ( self::slug_aliases() as $legacy => $canonical ) {
			if ( isset( $map[ $canonical ] ) && ! isset( $map[ $legacy ] ) ) {
				$map[ $legacy ] = $map[ $canonical ];
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
