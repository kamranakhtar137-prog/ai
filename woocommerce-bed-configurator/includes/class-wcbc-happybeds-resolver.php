<?php
/**
 * Build Happy Beds CDN layer URLs (new_configurator path scheme).
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCBC_HappyBeds_Resolver {

	const CDN = 'https://www.happybeds.co.uk/media/new_configurator/';

	/**
	 * Size slug → Happy Beds filename code.
	 *
	 * @var array<string,string>
	 */
	private static $size_codes = array(
		'small-single' => '2ft6',
		'single'       => '3ft',
		'small-double' => '4ft',
		'double'       => '4ft6',
		'king'         => '5ft',
		'super-king'   => '6ft',
	);

	/**
	 * Colour slug → fabric folder + numeric codes used in filenames.
	 *
	 * @var array<string,array{fabric:string,code:string,drawer:string}>
	 */
	private static $colour_meta = array(
		'beige-velvet'         => array( 'fabric' => 'velvet', 'code' => '30', 'drawer' => '190' ),
		'black-velvet'         => array( 'fabric' => 'velvet', 'code' => '10', 'drawer' => '110' ),
		'graphite-velvet'      => array( 'fabric' => 'velvet', 'code' => '20', 'drawer' => '120' ),
		'cream-cotton'         => array( 'fabric' => 'cotton', 'code' => '40', 'drawer' => '140' ),
		'midnight-blue-cotton' => array( 'fabric' => 'cotton', 'code' => '50', 'drawer' => '150' ),
	);

	/**
	 * CDN URL helper.
	 *
	 * @param string $relative Relative path under new_configurator.
	 * @return string
	 */
	public static function cdn_url( $relative ) {
		return self::CDN . ltrim( $relative, '/' );
	}

	/**
	 * Transparent placeholder used by Happy Beds.
	 *
	 * @return string
	 */
	public static function transparent_url() {
		return self::cdn_url( 'FFFFFF-0.png' );
	}

	/**
	 * Map headboard option id to Happy Beds style slug.
	 *
	 * @param string $headboard_id Headboard option id.
	 * @return string|null Null when no headboard.
	 */
	public static function headboard_style( $headboard_id ) {
		if ( false !== strpos( $headboard_id, 'no-headboard' ) ) {
			return null;
		}

		$map = array(
			'cornell-plain'    => 'cornell_plain',
			'cornell-lined'    => 'cornell_lined',
			'cornell-buttoned' => 'cornell_buttoned',
			'dudley-plain'     => 'dudley_plain',
			'victor-plain'     => 'victor_plain',
		);

		return isset( $map[ $headboard_id ] ) ? $map[ $headboard_id ] : 'cornell_plain';
	}

	/**
	 * Depth slug → Happy Beds depth code (e.g. 14-inch → 14i).
	 *
	 * @param string $depth Depth option id.
	 * @return string
	 */
	public static function depth_code( $depth ) {
		return str_replace( '-inch', 'i', sanitize_title( $depth ) );
	}

	/**
	 * Size slug → Happy Beds size code.
	 *
	 * @param string $size Size option id.
	 * @return string
	 */
	public static function size_code( $size ) {
		$size = sanitize_title( $size );
		return isset( self::$size_codes[ $size ] ) ? self::$size_codes[ $size ] : '4ft6';
	}

	/**
	 * Colour metadata with beige fallback.
	 *
	 * @param string $colour Colour option id.
	 * @return array{fabric:string,code:string,drawer:string}
	 */
	public static function colour_meta( $colour ) {
		$colour = sanitize_title( $colour );
		if ( isset( self::$colour_meta[ $colour ] ) ) {
			return self::$colour_meta[ $colour ];
		}
		return self::$colour_meta['beige-velvet'];
	}

	/**
	 * Build drawer layer relative paths for storage option.
	 *
	 * @param string $storage Storage option id.
	 * @param string $size_code Size code.
	 * @param string $depth_code Depth code.
	 * @param string $colour_code Colour numeric code.
	 * @param string $drawer_code Drawer reference code.
	 * @param string $fabric Fabric folder key.
	 * @return array{storage_1:?string,storage_2:?string,storage_3:?string,storage_back:?string}
	 */
	private static function storage_layers( $storage, $size_code, $depth_code, $colour_code, $drawer_code, $fabric ) {
		$empty = array(
			'storage_1'    => null,
			'storage_2'    => null,
			'storage_3'    => null,
			'storage_back' => null,
		);

		if ( in_array( $storage, array( 'no-drawers', 'ottoman' ), true ) ) {
			return $empty;
		}

		$folder = 'velvet' === $fabric ? 'drawers_velvet' : 'drawers_cotton';
		$suffix = $depth_code . $colour_code;

		if ( '2-drawers' === $storage || 'end-drawer' === $storage ) {
			$empty['storage_back'] = sprintf(
				'%s/reference_drawer_normal_back_%s_%s_drawer_normal_front_%s.png',
				$folder,
				$drawer_code,
				$size_code,
				$suffix
			);
			$empty['storage_2']    = sprintf(
				'%s/reference_drawer_normal_front_%s_%s_drawer_normal_front_%s.png',
				$folder,
				$drawer_code,
				$size_code,
				$suffix
			);
			return $empty;
		}

		if ( '4-drawers' === $storage ) {
			$empty['storage_back'] = sprintf(
				'%s/reference_drawer_normal_back_%s_%s_drawer_normal_front_%s.png',
				$folder,
				$drawer_code,
				$size_code,
				$suffix
			);
			$empty['storage_1'] = sprintf(
				'%s/reference_drawer_normal_front_%s_%s_drawer_normal_front_%s.png',
				$folder,
				$drawer_code,
				$size_code,
				$suffix
			);
			$empty['storage_2'] = sprintf(
				'%s/reference_drawer_jumbo_front_%s_%s_drawer_jumbo_front_%s.png',
				$folder,
				$drawer_code,
				$size_code,
				$suffix
			);
			$empty['storage_3'] = sprintf(
				'%s/reference_drawer_jumbo_back_%s_%s_drawer_jumbo_front_%s.png',
				$folder,
				$drawer_code,
				$size_code,
				$suffix
			);
		}

		return $empty;
	}

	/**
	 * Build all preview layer URLs from selections.
	 *
	 * @param array<string,string> $selections Selections.
	 * @param array<string,string> $defaults Defaults.
	 * @return array<string,string>
	 */
	public static function build_layers( $selections, $defaults = array() ) {
		$pick = static function ( $key ) use ( $selections, $defaults ) {
			if ( ! empty( $selections[ $key ] ) ) {
				return sanitize_title( $selections[ $key ] );
			}
			return ! empty( $defaults[ $key ] ) ? sanitize_title( $defaults[ $key ] ) : '';
		};

		$size       = $pick( 'size' ) ?: 'small-double';
		$colour     = $pick( 'colour' ) ?: 'beige-velvet';
		$headboard  = $pick( 'headboard' ) ?: 'cornell-lined';
		$base_depth = $pick( 'base_depth' ) ?: '14-inch';
		$storage    = $pick( 'storage' ) ?: 'no-drawers';

		$size_code  = self::size_code( $size );
		$depth_code = self::depth_code( $base_depth );
		$meta       = self::colour_meta( $colour );
		$hb_style   = self::headboard_style( $headboard );
		$dc_suffix  = $depth_code . $meta['code'];

		$layers = array(
			'shadow'       => self::cdn_url( 'new_shadow/shadow_wrk_' . $size_code . '.jpg' ),
			'legs'         => self::cdn_url( 'legs/bedding_legs_' . $size_code . '.png' ),
			'storage_back' => self::transparent_url(),
			'base'         => self::cdn_url(
				sprintf(
					'bases/%s/bedbase_%s_%s_%s.png',
					$meta['fabric'],
					$size_code,
					$depth_code,
					$meta['code']
				)
			),
			'headboard'    => self::transparent_url(),
			'storage_1'    => self::transparent_url(),
			'storage_2'    => self::transparent_url(),
			'storage_3'    => self::transparent_url(),
		);

		if ( $hb_style ) {
			$hb_folder = 'velvet' === $meta['fabric'] ? 'headboards_velvet' : 'headboards_cotton';
			$layers['headboard'] = self::cdn_url(
				sprintf(
					'%s/%s_%s_%s.png',
					$hb_folder,
					$hb_style,
					$size_code,
					$dc_suffix
				)
			);
		}

		$storage_paths = self::storage_layers( $storage, $size_code, $depth_code, $meta['code'], $meta['drawer'], $meta['fabric'] );
		foreach ( $storage_paths as $layer => $relative ) {
			if ( $relative ) {
				$layers[ $layer ] = self::cdn_url( $relative );
			}
		}

		return $layers;
	}

	/**
	 * Export mapping for frontend JS.
	 *
	 * @return array<string,mixed>
	 */
	public static function js_config() {
		return array(
			'cdn'         => self::CDN,
			'sizeCodes'   => self::$size_codes,
			'colourMeta'  => self::$colour_meta,
			'headboards'  => array(
				'cornell-plain'    => 'cornell_plain',
				'cornell-lined'    => 'cornell_lined',
				'cornell-buttoned' => 'cornell_buttoned',
				'dudley-plain'     => 'dudley_plain',
				'victor-plain'     => 'victor_plain',
				'no-headboard'     => null,
			),
			'transparent' => 'FFFFFF-0.png',
		);
	}
}
