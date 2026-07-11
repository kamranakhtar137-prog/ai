<?php
/**
 * REST API to import Happy Beds layer images from the browser (same-origin on happybeds.co.uk).
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCBC_Cache_API {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'init', array( __CLASS__, 'handle_preflight' ) );
		add_filter( 'rest_pre_serve_request', array( __CLASS__, 'add_cors_headers' ), 10, 4 );
	}

	/**
	 * Handle CORS preflight for browser import from happybeds.co.uk.
	 */
	public static function handle_preflight() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! isset( $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] ) || 'OPTIONS' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( false === strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/wp-json/wcbc/v1/' ) ) {
			return;
		}

		self::send_cors_headers();
		status_header( 204 );
		exit;
	}

	/**
	 * Add CORS headers to wcbc REST responses.
	 *
	 * @param bool             $served Whether the request was served.
	 * @param WP_HTTP_Response $result Response.
	 * @param WP_REST_Request  $request Request.
	 * @param WP_REST_Server   $server Server.
	 * @return bool
	 */
	public static function add_cors_headers( $served, $result, $request, $server ) {
		unset( $server );
		if ( 0 === strpos( $request->get_route(), '/wcbc/v1/' ) ) {
			self::send_cors_headers();
		}
		return $served;
	}

	/**
	 * Send permissive CORS headers for layer import.
	 */
	private static function send_cors_headers() {
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Content-Type, X-WCBC-Import-Token, X-WCBC-Import-Nonce' );
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		register_rest_route(
			'wcbc/v1',
			'/cache-image',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'cache_image' ),
				'permission_callback' => array( __CLASS__, 'can_cache' ),
				'args'                => array(
					'path' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => array( 'WCBC_Layer_Serve', 'sanitize_relative_path' ),
					),
					'data' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);

		register_rest_route(
			'wcbc/v1',
			'/cache-status',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'cache_status' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Allow admins or cross-origin browser import with site token.
	 *
	 * WordPress nonces are tied to the logged-in user and fail from happybeds.co.uk,
	 * so external imports must use the persistent site token instead.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public static function can_cache( $request ) {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		$token = $request->get_header( 'x-wcbc-import-token' );
		if ( $token && hash_equals( self::import_token(), (string) $token ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Save a base64-encoded image into the Happy Beds cache.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function cache_image( $request ) {
		$relative = WCBC_Layer_Serve::sanitize_relative_path( $request->get_param( 'path' ) );
		if ( '' === $relative ) {
			return new WP_Error( 'wcbc_invalid_path', __( 'Invalid image path.', 'wc-bed-configurator' ), array( 'status' => 400 ) );
		}

		$data = (string) $request->get_param( 'data' );
		if ( preg_match( '#^data:image/[^;]+;base64,#', $data ) ) {
			$data = preg_replace( '#^data:image/[^;]+;base64,#', '', $data );
		}

		$binary = base64_decode( $data, true );
		if ( false === $binary || '' === $binary ) {
			return new WP_Error( 'wcbc_invalid_data', __( 'Invalid image data.', 'wc-bed-configurator' ), array( 'status' => 400 ) );
		}

		$cache_file = WCBC_Layer_Serve::cache_path( $relative );
		$cache_dir  = dirname( $cache_file );
		if ( ! wp_mkdir_p( $cache_dir ) ) {
			return new WP_Error( 'wcbc_cache_failed', __( 'Could not create cache directory.', 'wc-bed-configurator' ), array( 'status' => 500 ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $cache_file, $binary ) ) {
			return new WP_Error( 'wcbc_write_failed', __( 'Could not write cache file.', 'wc-bed-configurator' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'ok'   => true,
				'path' => $relative,
				'url'  => WCBC_Layer_Serve::proxy_url( $relative ),
			)
		);
	}

	/**
	 * Return cache stats for the import script.
	 *
	 * @return WP_REST_Response
	 */
	public static function cache_status() {
		return rest_ensure_response(
			array(
				'has_cache' => WCBC_Layer_Serve::cache_has_files(),
				'mode'      => wcbc_get_image_mode(),
			)
		);
	}

	/**
	 * Persistent site token for cross-origin Happy Beds import.
	 *
	 * @param bool $regenerate Whether to force a new token.
	 * @return string
	 */
	public static function import_token( $regenerate = false ) {
		$token = get_option( 'wcbc_import_token', '' );

		if ( $regenerate || ! is_string( $token ) || '' === $token ) {
			$token = wp_generate_password( 32, false, false );
			update_option( 'wcbc_import_token', $token, false );
		}

		return $token;
	}

	/**
	 * Build a ready-to-paste import script with site URL and token embedded.
	 *
	 * @return string
	 */
	public static function import_script() {
		$config = wp_json_encode(
			array(
				'site'  => untrailingslashit( home_url() ),
				'token' => self::import_token(),
			)
		);

		$script_path = WCBC_PLUGIN_DIR . 'scripts/import-happybeds-to-wp.js';
		$body        = is_readable( $script_path ) ? (string) file_get_contents( $script_path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$body        = preg_replace( '#/\*\*[\s\S]*?\*/\s*#', '', $body, 1 );

		return "window.wcbcImportConfig = {$config};\n" . trim( $body );
	}
}
