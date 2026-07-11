<?php
/**
 * Build preview layer URLs from customer selections.
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCBC_Layer_Builder {

	/**
	 * Plugin layer base URL.
	 *
	 * @return string
	 */
	private static function layer_base() {
		return WCBC_PLUGIN_URL . 'demo-images/layers/';
	}

	/**
	 * Happy Beds imported layer base URL.
	 *
	 * @return string
	 */
	private static function happybeds_base() {
		return WCBC_PLUGIN_URL . 'demo-images/happybeds-layers/';
	}

	/**
	 * Happy Beds imported layer directory.
	 *
	 * @return string
	 */
	private static function happybeds_dir() {
		return WCBC_PLUGIN_DIR . 'demo-images/happybeds-layers/';
	}

	/**
	 * Whether imported Happy Beds manifest exists.
	 *
	 * @return bool
	 */
	public static function has_happybeds_manifest() {
		return file_exists( self::happybeds_dir() . 'manifest.json' );
	}

	/**
	 * Map plugin size id to Happy Beds API size slug.
	 *
	 * @param string $size Size id.
	 * @return string
	 */
	private static function happybeds_size( $size ) {
		$map = array(
			'small-single' => 'small-single',
			'single'       => 'single',
			'small-double' => 'small-double',
			'double'       => 'double',
			'king'         => 'king',
			'super-king'   => 'super-king',
		);
		return isset( $map[ $size ] ) ? $map[ $size ] : 'small-double';
	}

	/**
	 * Drawer state for Happy Beds API (1 = closed, 0 = ottoman open).
	 *
	 * @param string $storage Storage id.
	 * @param array<string,string> $selections Selections.
	 * @return int
	 */
	private static function drawer_state( $storage, $selections ) {
		if ( 'ottoman' !== $storage ) {
			return 1;
		}
		if ( isset( $selections['drawers_open'] ) && '1' === (string) $selections['drawers_open'] ) {
			return 0;
		}
		return 1;
	}

	/**
	 * Build layers from imported Happy Beds manifest.
	 *
	 * @param array<string,mixed>  $config Config.
	 * @param array<string,string> $selections Selections.
	 * @return array<string,string>|null
	 */
	private static function build_from_manifest( $config, $selections ) {
		$path = self::happybeds_dir() . 'manifest.json';
		$json = json_decode( (string) file_get_contents( $path ), true );
		if ( empty( $json['combinations'] ) ) {
			return null;
		}

		$defaults = isset( $config['defaults'] ) ? $config['defaults'] : array();
		$size       = self::pick( $selections, $defaults, 'size' );
		$colour     = self::pick( $selections, $defaults, 'colour' );
		$headboard  = self::pick( $selections, $defaults, 'headboard' );
		$base_depth = self::pick( $selections, $defaults, 'base_depth' );
		$storage    = self::pick( $selections, $defaults, 'storage' );

		$key = implode(
			'|',
			array(
				self::happybeds_size( $size ),
				$colour,
				$headboard,
				$base_depth,
				$storage,
				(string) self::drawer_state( $storage, $selections ),
			)
		);

		if ( empty( $json['combinations'][ $key ] ) ) {
			return null;
		}

		$base_url = self::happybeds_base();
		$layers   = array();
		foreach ( $json['combinations'][ $key ] as $layer => $relative ) {
			if ( ! $relative || false !== strpos( $relative, 'FFFFFF-0' ) ) {
				$layers[ $layer ] = self::layer_base() . 'transparent.png';
				continue;
			}
			$layers[ $layer ] = $base_url . ltrim( $relative, '/' );
		}

		return $layers;
	}

	/**
	 * Resolve a selection with fallback to config defaults.
	 *
	 * @param array<string,string> $selections Selections.
	 * @param array<string,string> $defaults Defaults.
	 * @param string               $key Key.
	 * @return string
	 */
	private static function pick( $selections, $defaults, $key ) {
		if ( ! empty( $selections[ $key ] ) ) {
			return sanitize_title( $selections[ $key ] );
		}
		return ! empty( $defaults[ $key ] ) ? sanitize_title( $defaults[ $key ] ) : '';
	}

	/**
	 * Map headboard option id to shape slug.
	 *
	 * @param string $headboard_id Headboard option id.
	 * @return string
	 */
	private static function headboard_shape( $headboard_id ) {
		if ( false !== strpos( $headboard_id, 'no-headboard' ) ) {
			return 'none';
		}
		if ( false !== strpos( $headboard_id, 'dudley' ) ) {
			return 'dudley';
		}
		if ( false !== strpos( $headboard_id, 'victor' ) ) {
			return 'victor';
		}
		return 'cornell';
	}

	/**
	 * Whether storage option uses drawer base image.
	 *
	 * @param string $storage_id Storage option id.
	 * @return bool
	 */
	private static function has_drawers( $storage_id ) {
		return in_array( $storage_id, array( '2-drawers', '4-drawers', 'end-drawer' ), true );
	}

	/**
	 * Build all preview layer URLs.
	 *
	 * @param array<string,mixed>  $config Product config.
	 * @param array<string,string> $selections Current selections.
	 * @return array<string,string>
	 */
	public static function build( $config, $selections ) {
		$defaults = isset( $config['defaults'] ) ? $config['defaults'] : array();

		if ( self::has_happybeds_manifest() ) {
			$imported = self::build_from_manifest( $config, $selections );
			if ( $imported ) {
				return $imported;
			}
		}

		// Live Happy Beds CDN layers (same URLs as happybeds.co.uk build-your-own-bed).
		return WCBC_HappyBeds_Resolver::build_layers( $selections, $defaults );
	}
}
