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
		$base     = self::layer_base();

		$size       = self::pick( $selections, $defaults, 'size' );
		$colour     = self::pick( $selections, $defaults, 'colour' );
		$headboard  = self::pick( $selections, $defaults, 'headboard' );
		$base_depth = self::pick( $selections, $defaults, 'base_depth' );
		$storage    = self::pick( $selections, $defaults, 'storage' );

		if ( ! $size ) {
			$size = 'small-double';
		}
		if ( ! $colour ) {
			$colour = 'beige-velvet';
		}
		if ( ! $base_depth ) {
			$base_depth = '14-inch';
		}
		if ( ! $storage ) {
			$storage = 'no-drawers';
		}

		$shape         = self::headboard_shape( $headboard );
		$drawer_suffix = self::has_drawers( $storage ) ? '-drawers' : '';
		$base_path     = sprintf( 'base/%s/%s/%s%s.png', $size, $colour, $base_depth, $drawer_suffix );
		$legs_path     = sprintf( 'legs/%s.png', $size );

		if ( ! file_exists( WCBC_PLUGIN_DIR . 'demo-images/layers/' . $base_path ) ) {
			$base_path = self::has_drawers( $storage ) ? 'base-beige-2drawers.png' : 'base-beige.png';
		}
		if ( ! file_exists( WCBC_PLUGIN_DIR . 'demo-images/layers/' . $legs_path ) ) {
			$legs_path = 'transparent.png';
		}

		$headboard_path = 'transparent.png';
		if ( 'none' !== $shape ) {
			$headboard_path = sprintf( 'headboard/%s/%s/%s.png', $size, $shape, $colour );
			if ( ! file_exists( WCBC_PLUGIN_DIR . 'demo-images/layers/' . $headboard_path ) ) {
				$headboard_path = 'headboard-cornell.png';
			}
		}

		return array(
			'shadow'       => $base . 'shadow-only.png',
			'legs'         => $base . $legs_path,
			'storage_back' => $base . 'transparent.png',
			'base'         => $base . $base_path,
			'headboard'    => $base . $headboard_path,
			'storage_1'    => $base . 'transparent.png',
			'storage_2'    => $base . 'transparent.png',
			'storage_3'    => $base . 'transparent.png',
		);
	}
}
