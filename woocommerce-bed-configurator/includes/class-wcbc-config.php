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
	 * Per-size and per-colour layer attachment IDs.
	 *
	 * Shape:
	 * [ 'colour' => [ size_id => [ colour_id => [ layer => attachment_id ] ] ],
	 *   'headboard' => [ size_id => [ style_id => [ colour_id => attachment_id ] ] ] ].
	 */
	const VARIATION_LAYER_MEDIA_KEY = '_wcbc_variation_layer_media';

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
			'legs'         => __( 'Bed Legs', 'wc-bed-configurator' ),
			'headboard'    => __( 'Bed Headboard', 'wc-bed-configurator' ),
			'storage_back' => __( 'Bed Storage Back', 'wc-bed-configurator' ),
			'base'         => __( 'Bed Base', 'wc-bed-configurator' ),
			'storage_1'    => __( 'Bed Storage 1', 'wc-bed-configurator' ),
			'storage_2'    => __( 'Bed Storage 2', 'wc-bed-configurator' ),
			'storage_3'    => __( 'Bed Storage 3', 'wc-bed-configurator' ),
			'storage_4'    => __( 'Bed Storage 4', 'wc-bed-configurator' ),
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
	 * Transparent fallback layer URL.
	 *
	 * @return string
	 */
	public static function transparent_layer_url() {
		return WCBC_PLUGIN_URL . 'demo-images/layers/transparent.png';
	}

	/**
	 * Get per-size base layers and per-size+colour fabric layers.
	 *
	 * Colour shape: colour[size_id][colour_id][layer] = attachment_id.
	 *
	 * @param int $product_id Product ID.
	 * @return array{size:array<string,array<string,mixed>>,colour:array<string,array<string,array<string,int>>>,headboard:array<string,array<string,array<string,int>>>}
	 */
	public static function get_variation_layer_media( $product_id ) {
		$raw = get_post_meta( $product_id, self::VARIATION_LAYER_MEDIA_KEY, true );
		$out = array(
			'size'      => array(),
			'colour'    => array(),
			'headboard' => array(),
		);

		if ( ! is_array( $raw ) ) {
			return $out;
		}

		// Legacy size-level headboard images (style only, no colour).
		if ( ! empty( $raw['size'] ) && is_array( $raw['size'] ) ) {
			foreach ( $raw['size'] as $size_id => $layers ) {
				$size_id = sanitize_title( (string) $size_id );
				if ( ! is_array( $layers ) || empty( $layers['headboard'] ) ) {
					continue;
				}
				if ( is_array( $layers['headboard'] ) ) {
					foreach ( $layers['headboard'] as $headboard_id => $attachment_id ) {
						$headboard_id = sanitize_title( (string) $headboard_id );
						if ( $headboard_id && $attachment_id ) {
							$out['size'][ $size_id ]['headboard'][ $headboard_id ] = absint( $attachment_id );
						}
					}
				}
			}
		}

		if ( ! empty( $raw['headboard'] ) && is_array( $raw['headboard'] ) ) {
			foreach ( $raw['headboard'] as $size_id => $styles ) {
				$size_id = sanitize_title( (string) $size_id );
				if ( ! is_array( $styles ) ) {
					continue;
				}
				foreach ( $styles as $style_id => $colours ) {
					$style_id = sanitize_title( (string) $style_id );
					if ( ! is_array( $colours ) ) {
						continue;
					}
					$clean = self::sanitize_headboard_colour_map( $colours );
					if ( $clean ) {
						$out['headboard'][ $size_id ][ $style_id ] = $clean;
					}
				}
			}
		}

		if ( empty( $raw['colour'] ) || ! is_array( $raw['colour'] ) ) {
			return $out;
		}

		foreach ( $raw['colour'] as $size_id => $colours ) {
			$size_id = sanitize_title( (string) $size_id );
			if ( ! is_array( $colours ) ) {
				continue;
			}

			// Legacy flat format: colour[colour_id][layer] (no size grouping).
			if ( self::is_flat_colour_layer_map( $colours ) ) {
				foreach ( array( 'small-single', 'single', 'small-double', 'double', 'king', 'super-king' ) as $legacy_size ) {
					if ( ! isset( $out['colour'][ $legacy_size ] ) ) {
						$out['colour'][ $legacy_size ] = array();
					}
					$out['colour'][ $legacy_size ][ $size_id ] = self::sanitize_colour_layer_map( $colours );
				}
				continue;
			}

			foreach ( $colours as $colour_id => $layers ) {
				$colour_id = sanitize_title( (string) $colour_id );
				if ( ! is_array( $layers ) ) {
					continue;
				}
				$clean = self::sanitize_colour_layer_map( $layers );
				if ( $clean ) {
					$out['colour'][ $size_id ][ $colour_id ] = $clean;
				}
			}
		}

		return $out;
	}

	/**
	 * Whether a stored colour branch is the legacy flat colour map.
	 *
	 * @param array<string,mixed> $map Stored map.
	 * @return bool
	 */
	private static function is_flat_colour_layer_map( $map ) {
		foreach ( array_keys( $map ) as $key ) {
			if ( in_array( $key, self::get_layers(), true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Keep only colour-scoped layer attachment IDs.
	 *
	 * @param array<string,mixed> $layers Layer map.
	 * @return array<string,int>
	 */
	private static function sanitize_colour_layer_map( $layers ) {
		$out = array();
		foreach ( self::colour_layer_slots() as $layer ) {
			if ( ! empty( $layers[ $layer ] ) ) {
				$out[ $layer ] = absint( $layers[ $layer ] );
			}
		}
		return $out;
	}

	/**
	 * Sanitize headboard style images keyed by colour id.
	 *
	 * @param array<string,mixed> $map Colour id => attachment id.
	 * @return array<string,int>
	 */
	private static function sanitize_headboard_colour_map( $map ) {
		$out = array();
		if ( ! is_array( $map ) ) {
			return $out;
		}
		foreach ( $map as $colour_id => $attachment_id ) {
			$colour_id = sanitize_title( (string) $colour_id );
			if ( $colour_id && $attachment_id ) {
				$out[ $colour_id ] = absint( $attachment_id );
			}
		}
		return $out;
	}

	/**
	 * Resolve headboard preview URL for size + style + colour.
	 *
	 * @param array<string,mixed> $media Full variation media map.
	 * @param string              $size_id Selected size id.
	 * @param string              $colour_id Selected colour id.
	 * @param string              $headboard_id Selected headboard option id.
	 * @return string Attachment URL or empty.
	 */
	private static function resolve_headboard_layer_url( $media, $size_id, $colour_id, $headboard_id ) {
		if ( ! $headboard_id || false !== strpos( $headboard_id, 'no-headboard' ) ) {
			return '';
		}

		$headboard_id = sanitize_title( $headboard_id );
		$size_id      = sanitize_title( $size_id );
		$colour_id    = sanitize_title( $colour_id );

		if ( ! empty( $media['headboard'][ $size_id ][ $headboard_id ][ $colour_id ] ) ) {
			$url = wp_get_attachment_image_url( (int) $media['headboard'][ $size_id ][ $headboard_id ][ $colour_id ], 'full' );
			if ( $url ) {
				return $url;
			}
		}

		// Legacy: one image per style at size level (no colour).
		if ( ! empty( $media['size'][ $size_id ]['headboard'][ $headboard_id ] ) ) {
			$url = wp_get_attachment_image_url( (int) $media['size'][ $size_id ]['headboard'][ $headboard_id ], 'full' );
			if ( $url ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * Sanitize posted variation layer media.
	 *
	 * @param array<string,mixed> $posted Posted form data.
	 * @return array{size:array<string,array<string,int>>,colour:array<string,array<string,array<string,int>>>}
	 */
	public static function sanitize_variation_layer_media_post( $posted ) {
		$out = array(
			'colour'    => array(),
			'headboard' => array(),
		);

		if ( ! is_array( $posted ) ) {
			return $out;
		}

		if ( ! empty( $posted['headboard'] ) && is_array( $posted['headboard'] ) ) {
			foreach ( $posted['headboard'] as $size_id => $styles ) {
				$size_id = sanitize_title( (string) $size_id );
				if ( ! is_array( $styles ) ) {
					continue;
				}
				foreach ( $styles as $style_id => $colours ) {
					$style_id = sanitize_title( (string) $style_id );
					if ( ! is_array( $colours ) ) {
						continue;
					}
					$clean = self::sanitize_headboard_colour_map( $colours );
					if ( $clean ) {
						$out['headboard'][ $size_id ][ $style_id ] = $clean;
					}
				}
			}
		}

		if ( ! empty( $posted['colour'] ) && is_array( $posted['colour'] ) ) {
			foreach ( $posted['colour'] as $size_id => $colours ) {
				$size_id = sanitize_title( (string) $size_id );
				if ( ! is_array( $colours ) ) {
					continue;
				}
				foreach ( $colours as $colour_id => $layers ) {
					$colour_id = sanitize_title( (string) $colour_id );
					if ( ! is_array( $layers ) ) {
						continue;
					}
					$clean = self::sanitize_colour_layer_map( $layers );
					if ( $clean ) {
						$out['colour'][ $size_id ][ $colour_id ] = $clean;
					}
				}
			}
		}

		return $out;
	}

	/**
	 * Resolve variation layer attachment IDs to URLs for frontend JS.
	 *
	 * @param int $product_id Product ID.
	 * @return array{size:array<string,array<string,string>>,colour:array<string,array<string,array<string,string>>>}
	 */
	public static function variation_layer_urls_for_js( $product_id ) {
		$raw = self::get_variation_layer_media( $product_id );
		$out = array(
			'headboardStyles' => array(),
			'sizeHeadboards'  => array(),
			'colour'          => array(),
		);

		foreach ( $raw['headboard'] as $size_id => $styles ) {
			foreach ( $styles as $style_id => $colours ) {
				foreach ( $colours as $colour_id => $attachment_id ) {
					$url = wp_get_attachment_image_url( (int) $attachment_id, 'full' );
					if ( $url ) {
						$out['headboardStyles'][ $size_id ][ $style_id ][ $colour_id ] = $url;
					}
				}
			}
		}

		foreach ( $raw['size'] as $size_id => $layers ) {
			if ( empty( $layers['headboard'] ) || ! is_array( $layers['headboard'] ) ) {
				continue;
			}
			foreach ( $layers['headboard'] as $headboard_id => $attachment_id ) {
				$url = wp_get_attachment_image_url( (int) $attachment_id, 'full' );
				if ( $url ) {
					$out['sizeHeadboards'][ $size_id ][ $headboard_id ] = $url;
				}
			}
		}

		foreach ( $raw['colour'] as $size_id => $colours ) {
			foreach ( $colours as $colour_id => $layers ) {
				foreach ( $layers as $layer => $attachment_id ) {
					$url = wp_get_attachment_image_url( (int) $attachment_id, 'full' );
					if ( $url ) {
						$out['colour'][ $size_id ][ $colour_id ][ $layer ] = $url;
					}
				}
			}
		}

		return $out;
	}

	/**
	 * Resolve preview layer URLs from size base set + size/colour fabric swaps.
	 *
	 * @param int                  $product_id Product ID.
	 * @param array<string,string> $selections Current selections.
	 * @param array<string,string> $defaults Default selections.
	 * @return array<string,string>
	 */
	public static function resolve_variation_layer_urls( $product_id, $selections, $defaults = array() ) {
		$media       = self::get_variation_layer_media( $product_id );
		$transparent = self::transparent_layer_url();
		$size_id     = ! empty( $selections['size'] ) ? sanitize_title( $selections['size'] ) : ( ! empty( $defaults['size'] ) ? sanitize_title( $defaults['size'] ) : '' );
		$colour_id   = ! empty( $selections['colour'] ) ? sanitize_title( $selections['colour'] ) : ( ! empty( $defaults['colour'] ) ? sanitize_title( $defaults['colour'] ) : '' );

		if ( class_exists( 'WCBC_Colour_Registry' ) ) {
			$colour_id = WCBC_Colour_Registry::resolve_slug( $colour_id );
		}

		$size_set = ( $size_id && ! empty( $media['colour'][ $size_id ][ $colour_id ] ) ) ? $media['colour'][ $size_id ][ $colour_id ] : array();

		$headboard_id = ! empty( $selections['headboard'] ) ? sanitize_title( $selections['headboard'] ) : ( ! empty( $defaults['headboard'] ) ? sanitize_title( $defaults['headboard'] ) : '' );

		$layers = array();
		foreach ( self::get_layers() as $layer ) {
			$layers[ $layer ] = $transparent;
		}

		foreach ( self::colour_layer_slots() as $layer ) {
			if ( ! empty( $size_set[ $layer ] ) ) {
				$url = wp_get_attachment_image_url( (int) $size_set[ $layer ], 'full' );
				if ( $url ) {
					$layers[ $layer ] = $url;
				}
			}
		}

		$headboard_url = self::resolve_headboard_layer_url( $media, $size_id, $colour_id, $headboard_id );
		if ( $headboard_url ) {
			$layers['headboard'] = $headboard_url;
		}

		if ( $headboard_id && false !== strpos( $headboard_id, 'no-headboard' ) ) {
			$layers['headboard'] = $transparent;
		}

		return $layers;
	}

	/**
	 * Size options from config for admin layer assignment.
	 *
	 * @param array<string,mixed> $config Config.
	 * @return array<int,array<string,string>>
	 */
	public static function size_options_for_admin( $config ) {
		$options = array();
		if ( empty( $config['groups'] ) ) {
			return $options;
		}
		foreach ( $config['groups'] as $group ) {
			if ( 'size' !== $group['id'] || empty( $group['options'] ) ) {
				continue;
			}
			foreach ( $group['options'] as $option ) {
				$options[] = array(
					'id'       => $option['id'],
					'label'    => $option['label'],
					'sublabel' => isset( $option['sublabel'] ) ? $option['sublabel'] : '',
				);
			}
		}
		return $options;
	}

	/**
	 * Colour options from config for admin layer assignment.
	 *
	 * @param array<string,mixed> $config Config.
	 * @return array<int,array<string,string>>
	 */
	public static function colour_options_for_admin( $config ) {
		$options = array();
		if ( empty( $config['groups'] ) ) {
			return $options;
		}
		foreach ( $config['groups'] as $group ) {
			if ( 'colour' !== $group['id'] || empty( $group['options'] ) ) {
				continue;
			}
			foreach ( $group['options'] as $option ) {
				$options[] = array(
					'id'       => $option['id'],
					'label'    => $option['label'],
					'sublabel' => isset( $option['sublabel'] ) ? $option['sublabel'] : '',
				);
			}
		}
		return $options;
	}

	/**
	 * Headboard style options from config for per-size layer assignment.
	 *
	 * @param array<string,mixed> $config Config.
	 * @return array<int,array<string,string>>
	 */
	public static function headboard_options_for_admin( $config ) {
		$options = array();
		$allowed = array_flip( self::customer_headboard_option_ids() );
		if ( empty( $config['groups'] ) ) {
			return $options;
		}
		foreach ( $config['groups'] as $group ) {
			if ( 'headboard' !== $group['id'] || empty( $group['options'] ) ) {
				continue;
			}
			foreach ( $group['options'] as $option ) {
				$option_id = ! empty( $option['id'] ) ? sanitize_title( $option['id'] ) : '';
				if ( ! $option_id || ! isset( $allowed[ $option_id ] ) ) {
					continue;
				}
				$options[] = array(
					'id'       => $option['id'],
					'label'    => $option['label'],
					'sublabel' => isset( $option['sublabel'] ) ? $option['sublabel'] : '',
				);
			}
		}
		return $options;
	}

	/**
	 * Customer-facing accordion groups (simplified for now).
	 *
	 * @return string[]
	 */
	public static function customer_visible_group_ids() {
		return array( 'size', 'colour', 'headboard' );
	}

	/**
	 * Headboard styles shown on the storefront and in per-size media pickers.
	 *
	 * @return string[]
	 */
	public static function customer_headboard_option_ids() {
		return array( 'cornell-plain', 'cornell-lined', 'cornell-buttoned' );
	}

	/**
	 * Limit headboard group to the three customer-facing styles (no shape filters).
	 *
	 * @param array<string,mixed> $group Headboard option group.
	 * @return array<string,mixed>
	 */
	private static function filter_headboard_group_for_storefront( $group ) {
		$allowed = array_flip( self::customer_headboard_option_ids() );
		$options = array();

		if ( ! empty( $group['options'] ) ) {
			foreach ( $group['options'] as $option ) {
				$option_id = ! empty( $option['id'] ) ? sanitize_title( $option['id'] ) : '';
				if ( $option_id && isset( $allowed[ $option_id ] ) ) {
					$options[] = $option;
				}
			}
		}

		$group['options'] = $options;
		unset( $group['filter_type'], $group['filters'] );

		return $group;
	}

	/**
	 * Filter config groups shown in the storefront accordion.
	 *
	 * @param array<string,mixed> $config Config.
	 * @return array<int,array<string,mixed>>
	 */
	public static function visible_groups( $config ) {
		$allowed = array_flip( self::customer_visible_group_ids() );
		$groups  = array();
		if ( empty( $config['groups'] ) ) {
			return $groups;
		}
		foreach ( $config['groups'] as $group ) {
			if ( empty( $group['id'] ) || ! isset( $allowed[ $group['id'] ] ) ) {
				continue;
			}
			if ( 'headboard' === $group['id'] ) {
				$group = self::filter_headboard_group_for_storefront( $group );
			}
			$groups[] = $group;
		}
		return $groups;
	}

	/**
	 * Product image source mode.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	public static function get_image_source( $product_id ) {
		$source = get_post_meta( $product_id, self::IMAGE_SOURCE_KEY, true );
		if ( in_array( $source, array( 'media', 'demo', 'auto', 'happybeds', 'hybrid' ), true ) ) {
			return $source;
		}
		return 'media';
	}

	/**
	 * Image layers rendered in preview stack.
	 *
	 * @return string[]
	 */
	public static function get_layers() {
		// Match Happy Beds DOM stacking order.
		return array( 'shadow', 'legs', 'headboard', 'storage_back', 'base', 'storage_1', 'storage_2', 'storage_3', 'storage_4' );
	}

	/**
	 * Primary preview layers (Happy Beds alt labels).
	 *
	 * @return string[]
	 */
	public static function core_preview_layers() {
		return array( 'legs', 'headboard', 'storage_back', 'base' );
	}

	/**
	 * Option groups that drive core layer image variations.
	 *
	 * @return string[]
	 */
	public static function core_variation_groups() {
		return array( 'size', 'colour', 'base_depth' );
	}

	/**
	 * Layers assigned per size (non-fabric structure).
	 *
	 * @return string[]
	 */
	public static function size_base_layer_slots() {
		return array();
	}

	/**
	 * Layers assigned per size (legacy alias).
	 *
	 * @return string[]
	 */
	public static function size_layer_slots() {
		return self::size_base_layer_slots();
	}

	/**
	 * Fabric layers that swap when colour changes (scoped to the selected size).
	 *
	 * @return string[]
	 */
	public static function colour_fabric_layer_slots() {
		return self::colour_layer_slots();
	}

	/**
	 * Layers assigned per size + colour in the Media Library admin.
	 *
	 * @return string[]
	 */
	public static function colour_layer_slots() {
		return array( 'legs', 'headboard', 'storage_back', 'base', 'storage_1', 'storage_2', 'storage_3', 'storage_4' );
	}

	/**
	 * Layers whose image file changes with customer selections (not one fixed upload).
	 *
	 * @return string[]
	 */
	public static function variation_driven_layers() {
		return self::colour_layer_slots();
	}

	/**
	 * Layers that may use a single manual Media Library override in hybrid mode.
	 *
	 * @return string[]
	 */
	public static function static_override_layers() {
		return array( 'shadow', 'legs' );
	}

	/**
	 * Build default demo configuration.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_default_config() {
		$base = WCBC_PLUGIN_URL . 'demo-images/';

		$selection_defaults = array(
			'size'       => 'small-single',
			'colour'     => 'light-silver-velvet',
			'headboard'  => 'cornell-lined',
			'base_depth' => '14-inch',
			'storage'    => '2-drawers-same-side',
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
					'id'       => 'headboard',
					'label'    => 'Headboard',
					'icon'     => 'headboard',
					'required' => true,
					'options'  => array(
						self::opt( 'cornell-plain', 'Cornell Plain', '', 0, $base . 'swatches/headboard/cornell-plain.png', array( 'shape' => 'cornell' ) ),
						self::opt( 'cornell-lined', 'Cornell Lined', '', 25, $base . 'swatches/headboard/cornell-lined.png', array( 'shape' => 'cornell' ) ),
						self::opt( 'cornell-buttoned', 'Cornell Buttoned', '', 35, $base . 'swatches/headboard/cornell-buttoned.png', array( 'shape' => 'cornell' ) ),
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
						self::opt( 'ottoman', 'Ottoman', '', 80, $base . 'swatches/storage/ottoman.png' ),
						self::opt( 'no-drawers', 'No Drawers', '', 0, $base . 'swatches/storage/no-drawers.png' ),
						self::opt( 'end-drawer', 'End Drawer', '', 40, $base . 'swatches/storage/end-drawer.png' ),
						self::opt( '2-drawers', '2 Drawers', '', 50, $base . 'swatches/storage/2-drawers.png' ),
						self::opt( '2-drawers-same-side', '2 Drawers Same Side', '', 50, $base . 'swatches/storage/2-drawers.png' ),
						self::opt( '4-drawers', '4 Drawers', '', 90, $base . 'swatches/storage/4-drawers.png' ),
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
		$config = self::sanitize_option_layers( $config );
		$defaults = self::get_default_config();
		$config['defaults']     = self::normalize_defaults( $config );
		$config['product_id']   = (int) $product_id;
		$config['image_source'] = self::get_image_source( $product_id );
		$config['layers']       = WCBC_Layer_Builder::build( $config, $config['defaults'] );
		unset( $config['layer_media'] );
		$config['base_price']   = isset( $config['base_price'] ) ? (float) $config['base_price'] : $defaults['base_price'];
		return $config;
	}

	/**
	 * Ensure each group default matches a real option id (single selection).
	 *
	 * @param array<string,mixed> $config Config.
	 * @return array<string,string>
	 */
	public static function normalize_defaults( $config ) {
		$plugin_defaults = self::get_default_config();
		$out             = ! empty( $config['defaults'] ) && is_array( $config['defaults'] )
			? $config['defaults']
			: $plugin_defaults['defaults'];

		if ( empty( $config['groups'] ) ) {
			return $out;
		}

		foreach ( $config['groups'] as $group ) {
			if ( empty( $group['id'] ) || empty( $group['options'] ) ) {
				continue;
			}

			$gid        = $group['id'];
			$valid_ids  = array();
			foreach ( $group['options'] as $option ) {
				if ( ! empty( $option['id'] ) ) {
					$valid_ids[] = sanitize_title( $option['id'] );
				}
			}

			if ( empty( $valid_ids ) ) {
				continue;
			}

			// Size and colour always default to the first listed option.
			if ( in_array( $gid, array( 'size', 'colour' ), true ) ) {
				$out[ $gid ] = $valid_ids[0];
				continue;
			}

			$current = isset( $out[ $gid ] ) ? sanitize_title( $out[ $gid ] ) : '';
			if ( ! in_array( $current, $valid_ids, true ) ) {
				$out[ $gid ] = $valid_ids[0];
			} else {
				$out[ $gid ] = $current;
			}
		}

		return $out;
	}

	/**
	 * Resolve the selected option id for a group.
	 *
	 * @param array<string,mixed> $group Option group.
	 * @param string              $selected Requested selection.
	 * @return string
	 */
	public static function resolve_group_selection( $group, $selected ) {
		if ( empty( $group['options'] ) ) {
			return sanitize_title( $selected );
		}

		$selected = sanitize_title( $selected );
		foreach ( $group['options'] as $option ) {
			if ( ! empty( $option['id'] ) && sanitize_title( $option['id'] ) === $selected ) {
				return sanitize_title( $option['id'] );
			}
		}

		return sanitize_title( $group['options'][0]['id'] );
	}

	/**
	 * Strip static demo layer URLs from options so Happy Beds paths stay dynamic.
	 *
	 * @param array<string,mixed> $config Config.
	 * @return array<string,mixed>
	 */
	private static function sanitize_option_layers( $config ) {
		if ( empty( $config['groups'] ) ) {
			return $config;
		}

		$meta_keys   = array( 'shape', 'fabric', 'hb_fabric', 'hb_code', 'hb_drawer', 'size' );
		$layer_keys  = array_flip( self::get_layers() );
		$clean_groups = array( 'colour', 'headboard', 'storage', 'size', 'base_depth' );

		foreach ( $config['groups'] as $gi => $group ) {
			if ( empty( $group['options'] ) || ! in_array( $group['id'], $clean_groups, true ) ) {
				continue;
			}
			foreach ( $group['options'] as $oi => $option ) {
				if ( empty( $option['layers'] ) || ! is_array( $option['layers'] ) ) {
					continue;
				}
				$clean = array();
				foreach ( $option['layers'] as $key => $value ) {
					if ( in_array( $key, $meta_keys, true ) ) {
						$clean[ $key ] = $value;
						continue;
					}
					if ( isset( $layer_keys[ $key ] ) && is_string( $value ) && false !== strpos( $value, 'demo-images/layers' ) ) {
						continue;
					}
					if ( isset( $layer_keys[ $key ] ) ) {
						$clean[ $key ] = $value;
					}
				}
				$config['groups'][ $gi ]['options'][ $oi ]['layers'] = $clean;
			}
		}

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
			$sel_id = self::resolve_group_selection( $group, $sel_id );
			$option = self::find_option( $config, $gid, $sel_id );
			if ( ! $option ) {
				continue;
			}
			$price += (float) $option['price'];
			$labels[ $gid ] = trim( $option['label'] . ( $option['sublabel'] ? ' ' . $option['sublabel'] : '' ) );
		}

		$normalized = array();
		foreach ( $config['groups'] as $group ) {
			$gid = $group['id'];
			$normalized[ $gid ] = isset( $selections[ $gid ] )
				? self::resolve_group_selection( $group, $selections[ $gid ] )
				: ( isset( $config['defaults'][ $gid ] ) ? $config['defaults'][ $gid ] : '' );
		}

		$layers = WCBC_Layer_Builder::build( $config, $normalized );

		return array(
			'price'       => max( 0, $price ),
			'layers'      => $layers,
			'labels'      => $labels,
			'selections'  => $normalized,
		);
	}
}
