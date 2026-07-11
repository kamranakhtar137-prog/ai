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
		$storage_id = class_exists( 'WCBC_HappyBeds_Resolver' ) ? WCBC_HappyBeds_Resolver::normalize_storage( $storage_id ) : $storage_id;
		return in_array( $storage_id, array( '2-drawers', '4-drawers', 'end-drawer' ), true );
	}

	/**
	 * Build demo layer URLs bundled with the plugin.
	 *
	 * @param array<string,string> $selections Selections.
	 * @param array<string,string> $defaults Defaults.
	 * @return array<string,string>
	 */
	public static function build_demo_layers( $selections, $defaults = array() ) {
		$base     = self::layer_base();
		$size     = self::pick( $selections, $defaults, 'size' );
		$colour   = self::pick( $selections, $defaults, 'colour' );
		$headboard = self::pick( $selections, $defaults, 'headboard' );
		$depth    = self::pick( $selections, $defaults, 'base_depth' );
		$storage  = self::pick( $selections, $defaults, 'storage' );
		$shape    = self::headboard_shape( $headboard );
		$suffix   = self::has_drawers( $storage ) ? '-drawers' : '';

		if ( ! $size ) {
			$size = 'double';
		}
		if ( ! $colour ) {
			$colour = 'beige-velvet';
		}
		if ( ! $depth ) {
			$depth = '14-inch';
		}

		$layers = array(
			'shadow'       => $base . 'shadow-only.png',
			'legs'         => $base . 'legs/' . $size . '.png',
			'storage_back' => $base . 'transparent.png',
			'base'         => $base . 'base/' . $size . '/' . $colour . '/' . $depth . $suffix . '.png',
			'headboard'    => $base . 'transparent.png',
			'storage_1'    => $base . 'transparent.png',
			'storage_2'    => $base . 'transparent.png',
			'storage_3'    => $base . 'transparent.png',
		);

		if ( 'none' !== $shape ) {
			$layers['headboard'] = $base . 'headboard/' . $size . '/' . $shape . '/' . $colour . '.png';
		}

		return $layers;
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
		$source   = isset( $config['image_source'] ) ? $config['image_source'] : 'auto';
		$layers   = array();

		if ( 'media' === $source ) {
			$layers = self::build_from_product_media( $config );
		} elseif ( 'happybeds' === $source ) {
			$mode   = WCBC_Layer_Serve::cache_has_files() ? 'happybeds-proxy' : 'happybeds-cdn';
			$layers = WCBC_HappyBeds_Resolver::build_layers( $selections, $defaults, $mode, $config );
		} else {
			$mode = function_exists( 'wcbc_get_image_mode' ) ? wcbc_get_image_mode() : 'demo';

			if ( 'demo' === $mode ) {
				$layers = self::build_demo_layers( $selections, $defaults );
			} elseif ( self::has_happybeds_manifest() ) {
				$imported = self::build_from_manifest( $config, $selections );
				$layers   = $imported ? $imported : WCBC_HappyBeds_Resolver::build_layers( $selections, $defaults, $mode, $config );
			} else {
				$layers = WCBC_HappyBeds_Resolver::build_layers( $selections, $defaults, $mode, $config );
			}

			if ( 'hybrid' === $source ) {
				$layers = self::merge_product_media( $layers, $config );
			}
		}

		if ( self::should_apply_option_layer_overrides( $config ) ) {
			return self::apply_option_layer_overrides( $layers, $config, $selections );
		}

		return $layers;
	}

	/**
	 * Whether option-level layer URL overrides should be merged.
	 *
	 * @param array<string,mixed> $config Product config.
	 * @return bool
	 */
	private static function should_apply_option_layer_overrides( $config ) {
		$source = isset( $config['image_source'] ) ? $config['image_source'] : 'auto';
		return in_array( $source, array( 'hybrid', 'media' ), true );
	}

	/**
	 * Layers that may be replaced by a single Media Library upload in hybrid mode.
	 *
	 * @return string[]
	 */
	private static function static_override_layers() {
		return WCBC_Config::static_override_layers();
	}

	/**
	 * Layers whose image file changes per customer selection.
	 *
	 * @return string[]
	 */
	private static function variation_driven_layers() {
		return WCBC_Config::variation_driven_layers();
	}

	/**
	 * Build layers purely from product media library attachments.
	 *
	 * @param array<string,mixed> $config Product config.
	 * @return array<string,string>
	 */
	private static function build_from_product_media( $config ) {
		$transparent = WCBC_PLUGIN_URL . 'demo-images/layers/transparent.png';
		$layers      = array_fill_keys( WCBC_Config::get_layers(), $transparent );
		$attachment_ids = isset( $config['layer_media'] ) && is_array( $config['layer_media'] ) ? $config['layer_media'] : array();
		$media_layers   = WCBC_Config::layer_urls_from_media( $attachment_ids );

		foreach ( $media_layers as $layer => $url ) {
			if ( $url ) {
				$layers[ $layer ] = $url;
			}
		}

		return $layers;
	}

	/**
	 * Replace individual layers with product media attachments when set.
	 *
	 * @param array<string,string> $layers Current layers.
	 * @param array<string,mixed>  $config Product config.
	 * @return array<string,string>
	 */
	private static function merge_product_media( $layers, $config ) {
		$attachment_ids = isset( $config['layer_media'] ) && is_array( $config['layer_media'] ) ? $config['layer_media'] : array();
		$media_layers   = WCBC_Config::layer_urls_from_media( $attachment_ids );
		$allowed        = array_flip( self::static_override_layers() );

		foreach ( $media_layers as $layer => $url ) {
			if ( $url && isset( $allowed[ $layer ] ) ) {
				$layers[ $layer ] = $url;
			}
		}

		return $layers;
	}

	/**
	 * Merge layer URLs contributed by the currently selected options.
	 *
	 * @param array<string,string> $layers Layer URLs.
	 * @param array<string,mixed>  $config Config.
	 * @param array<string,string> $selections Selections.
	 * @return array<string,string>
	 */
	private static function apply_option_layer_overrides( $layers, $config, $selections ) {
		if ( empty( $config['groups'] ) ) {
			return $layers;
		}

		$defaults            = isset( $config['defaults'] ) ? $config['defaults'] : array();
		$valid_layers        = array_flip( WCBC_Config::get_layers() );
		$source              = isset( $config['image_source'] ) ? $config['image_source'] : 'auto';
		$variation_driven    = array_flip( self::variation_driven_layers() );
		$allow_variation_url = ( 'media' === $source );

		foreach ( $config['groups'] as $group ) {
			$gid    = $group['id'];
			$sel_id = self::pick( $selections, $defaults, $gid );
			$option = null;
			foreach ( $group['options'] as $opt ) {
				if ( $opt['id'] === $sel_id ) {
					$option = $opt;
					break;
				}
			}
			if ( ! $option || empty( $option['layers'] ) || ! is_array( $option['layers'] ) ) {
				continue;
			}
			foreach ( $option['layers'] as $layer_key => $url ) {
				if ( ! isset( $valid_layers[ $layer_key ] ) || ! $url ) {
					continue;
				}
				if ( isset( $variation_driven[ $layer_key ] ) && ! $allow_variation_url ) {
					continue;
				}
				if ( is_string( $url ) && false !== strpos( $url, 'demo-images/layers' ) ) {
					continue;
				}
				$layers[ $layer_key ] = esc_url_raw( $url );
			}
		}

		return $layers;
	}
}
