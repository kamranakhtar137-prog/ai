<?php
/**
 * Frontend display.
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCBC_Frontend {

	/**
	 * Cached template args per request.
	 *
	 * @var array<string,mixed>|null
	 */
	private static $template_args = null;

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets_early' ), 20 );
		add_action( 'woocommerce_before_single_product', array( __CLASS__, 'setup_product_layout' ), 1 );
		add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'render_layout' ), 5 );
		add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'render_hidden_fields' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( __CLASS__, 'add_to_cart_text' ), 10, 2 );
	}

	/**
	 * Custom add-to-cart button label.
	 *
	 * @param string     $text Button text.
	 * @param WC_Product $product Product.
	 * @return string
	 */
	public static function add_to_cart_text( $text, $product ) {
		if ( self::is_enabled( $product->get_id() ) ) {
			return __( 'Add To Basket', 'wc-bed-configurator' );
		}
		return $text;
	}

	/**
	 * Add body class on configurator products.
	 *
	 * @param string[] $classes Classes.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		if ( is_product() ) {
			global $product;
			if ( $product && self::is_enabled( $product->get_id() ) ) {
				$classes[] = 'wcbc-configurator-active';
				$classes[] = 'product-build-your-own-bed';
				$classes[] = 'wcbc-has-configurator';
			}
		}
		return $classes;
	}

	/**
	 * Reorder product page for configurator layout.
	 */
	public static function setup_product_layout() {
		global $product;
		if ( ! $product || ! self::is_enabled( $product->get_id() ) ) {
			return;
		}

		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

		add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 45 );
		add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 46 );
	}

	/**
	 * Check if configurator enabled for product.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function is_enabled( $product_id ) {
		return get_post_meta( $product_id, WCBC_Config::ENABLED_KEY, true ) === 'yes';
	}

	/**
	 * Build shared template args.
	 *
	 * @return array<string,mixed>
	 */
	private static function get_template_args() {
		if ( null !== self::$template_args ) {
			return self::$template_args;
		}

		global $product;
		$product_id = $product->get_id();
		$config     = WCBC_Config::get_product_config( $product_id );

		if ( empty( $config['base_price'] ) || $config['base_price'] <= 0 ) {
			$config['base_price'] = (float) $product->get_regular_price();
		}

		$selections = $config['defaults'];
		$calc       = WCBC_Config::calculate( $config, $selections );

		self::enqueue_assets( $product_id, $config );

		self::$template_args = array(
			'product'    => $product,
			'config'     => $config,
			'selections' => $selections,
			'calc'       => $calc,
			'plugin_url' => WCBC_PLUGIN_URL,
		);

		return self::$template_args;
	}

	/**
	 * Enqueue on product pages before template renders.
	 */
	public static function enqueue_assets_early() {
		if ( ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( get_queried_object_id() );
		}

		if ( ! $product || ! self::is_enabled( $product->get_id() ) ) {
			return;
		}

		$config = WCBC_Config::get_product_config( $product->get_id() );
		self::enqueue_assets( $product->get_id(), $config );
	}

	/**
	 * Enqueue assets and data.
	 *
	 * @param int                 $product_id Product ID.
	 * @param array<string,mixed> $config Config.
	 */
	private static function enqueue_assets( $product_id, $config ) {
		$image_mode = wcbc_get_image_mode();
		$layer_media = WCBC_Config::layer_urls_from_media( WCBC_Config::get_layer_media( $product_id ) );

		wp_enqueue_style( 'wcbc-configurator' );
		wp_enqueue_script( 'wcbc-accordion' );
		wp_enqueue_script( 'wcbc-configurator' );
		wp_localize_script(
			'wcbc-configurator',
			'wcbcData',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'wcbc_configurator' ),
				'productId'    => $product_id,
				'config'       => $config,
				'layers'       => WCBC_Config::get_layers(),
				'layerBase'    => WCBC_PLUGIN_URL . 'demo-images/layers/',
				'happyBeds'    => WCBC_HappyBeds_Resolver::js_config( $config ),
				'imageMode'    => $image_mode,
				'imageSource'  => WCBC_Config::get_image_source( $product_id ),
				'layerMedia'   => $layer_media,
				'useHappyBeds' => in_array( $image_mode, array( 'happybeds-cdn', 'happybeds-proxy' ), true ),
				'layerProxy'   => admin_url( 'admin-ajax.php?action=wcbc_layer_image&path=' ),
				'importToken'  => current_user_can( 'manage_woocommerce' ) ? WCBC_Cache_API::import_token() : '',
				'siteUrl'      => home_url( '/' ),
				'currency'     => get_woocommerce_currency_symbol(),
				'i18n'      => array(
					'optionsAvailable' => __( '%d options available', 'wc-bed-configurator' ),
					'now'              => __( 'Now', 'wc-bed-configurator' ),
					'addToBasket'      => __( 'Add To Basket', 'wc-bed-configurator' ),
					'drawers'          => __( 'Drawers', 'wc-bed-configurator' ),
					'closed'           => __( 'Closed', 'wc-bed-configurator' ),
					'open'             => __( 'Open', 'wc-bed-configurator' ),
					'chooseShape'      => __( 'Choose Shape', 'wc-bed-configurator' ),
					'chooseFabric'     => __( 'Choose Fabric', 'wc-bed-configurator' ),
					'chooseStyle'      => __( 'Choose Style', 'wc-bed-configurator' ),
				),
			)
		);
	}

	/**
	 * Render unified 50/50 configurator layout.
	 */
	public static function render_layout() {
		global $product;
		if ( ! $product || ! self::is_enabled( $product->get_id() ) ) {
			return;
		}

		$args = self::get_template_args();

		echo '<div class="wcbc-layout" id="wcbc-layout">';

		echo '<div class="wcbc-layout__col wcbc-layout__col--media">';
		$args['part'] = 'media';
		wc_get_template( 'configurator.php', $args, '', WCBC_PLUGIN_DIR . 'templates/' );
		echo '</div>';

		echo '<div class="wcbc-layout__col wcbc-layout__col--options">';
		echo '<div class="wcbc-product-title-wrap">';
		echo '<h1 class="product_title entry-title wcbc-product-title">' . esc_html( $product->get_name() ) . '</h1>';
		echo '</div>';
		$args['part'] = 'options';
		wc_get_template( 'configurator.php', $args, '', WCBC_PLUGIN_DIR . 'templates/' );
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Hidden fields for cart submission.
	 */
	public static function render_hidden_fields() {
		global $product;
		if ( ! $product || ! self::is_enabled( $product->get_id() ) ) {
			return;
		}
		$config = WCBC_Config::get_product_config( $product->get_id() );
		echo '<div id="wcbc-hidden-fields">';
		foreach ( $config['groups'] as $group ) {
			$gid = esc_attr( $group['id'] );
			$val = isset( $config['defaults'][ $gid ] ) ? $config['defaults'][ $gid ] : '';
			printf(
				'<input type="hidden" name="wcbc_selections[%1$s]" id="wcbc_sel_%1$s" value="%2$s" />',
				$gid,
				esc_attr( $val )
			);
		}
		echo '<input type="hidden" name="wcbc_configured" value="1" />';
		echo '</div>';
	}
}
