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
		add_action( 'init', array( __CLASS__, 'ensure_import_token' ), 5 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'init', array( __CLASS__, 'handle_preflight' ) );
		add_filter( 'rest_pre_serve_request', array( __CLASS__, 'add_cors_headers' ), 10, 4 );
		add_filter( 'rest_authentication_errors', array( __CLASS__, 'allow_token_import_auth' ), 99 );
	}

	/**
	 * Create import token on first run (safe to call on every init).
	 */
	public static function ensure_import_token() {
		self::import_token( false );
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
					'token' => array(
						'required' => false,
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
	 * Allow cross-origin Happy Beds import when the site token is valid.
	 *
	 * Some hosts (TasteWP, security plugins) block anonymous REST before permission_callback runs.
	 *
	 * @param WP_Error|null|true $errors Existing auth errors.
	 * @return WP_Error|null|true
	 */
	public static function allow_token_import_auth( $errors ) {
		if ( ! self::is_cache_image_request() ) {
			return $errors;
		}

		$token = self::get_token_from_superglobals();
		if ( $token && hash_equals( self::import_token(), $token ) ) {
			return null;
		}

		return $errors;
	}

	/**
	 * Whether the current HTTP request targets cache-image.
	 *
	 * @return bool
	 */
	private static function is_cache_image_request() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		return false !== strpos( $uri, '/wp-json/wcbc/v1/cache-image' );
	}

	/**
	 * Read import token from superglobals (header or query) before REST body is parsed.
	 *
	 * @return string
	 */
	private static function get_token_from_superglobals() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! empty( $_SERVER['HTTP_X_WCBC_IMPORT_TOKEN'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WCBC_IMPORT_TOKEN'] ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! empty( $_GET['wcbc_import_token'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return sanitize_text_field( wp_unslash( $_GET['wcbc_import_token'] ) );
		}

		return '';
	}

	/**
	 * Extract import token from header, query string, or JSON body.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return string
	 */
	private static function get_request_token( $request ) {
		$token = self::get_token_from_superglobals();
		if ( $token ) {
			return $token;
		}

		$header = $request->get_header( 'x-wcbc-import-token' );
		if ( $header ) {
			return sanitize_text_field( (string) $header );
		}

		$body_token = $request->get_param( 'token' );
		if ( $body_token ) {
			return sanitize_text_field( (string) $body_token );
		}

		return '';
	}

	/**
	 * Allow admins or cross-origin browser import with site token.
	 *
	 * WordPress nonces are tied to the logged-in user and fail from happybeds.co.uk,
	 * so external imports must use the persistent site token instead.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public static function can_cache( $request ) {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		$token = self::get_request_token( $request );
		if ( $token && hash_equals( self::import_token(), $token ) ) {
			return true;
		}

		return new WP_Error(
			'wcbc_import_forbidden',
			__( 'Invalid or missing import token. Open your product in WordPress admin → Bed Configurator tab → copy a fresh import script (token changes when the plugin is re-activated).', 'wc-bed-configurator' ),
			array( 'status' => 401 )
		);
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
	 * Build a ready-to-paste QUICK import script (25 images, default combo per size).
	 *
	 * @return string
	 */
	public static function import_script() {
		return self::wrap_import_script( 'scripts/import-happybeds-to-wp.js' );
	}

	/**
	 * Build a ready-to-paste FULL import script (all variations for dynamic preview).
	 *
	 * @return string
	 */
	public static function import_all_script() {
		$config = WCBC_Config::get_default_config();
		$extra  = array(
			'colours'   => class_exists( 'WCBC_Colour_Registry' ) ? WCBC_Colour_Registry::colour_slugs() : array(),
			'happyBeds' => WCBC_HappyBeds_Resolver::js_config( $config ),
		);
		return self::wrap_import_script( 'scripts/import-all-happybeds-to-wp.js', $extra );
	}

	/**
	 * Core import: legs, headboard, storage back, base for all size × colour × base depth.
	 *
	 * @return string
	 */
	public static function import_core_script() {
		$config = WCBC_Config::get_default_config();
		$extra  = array(
			'colours'   => class_exists( 'WCBC_Colour_Registry' ) ? WCBC_Colour_Registry::colour_slugs() : array(),
			'happyBeds' => WCBC_HappyBeds_Resolver::js_config( $config ),
		);
		return self::wrap_import_script( 'scripts/import-core-layers-to-wp.js', $extra );
	}

	/**
	 * Embed site config into a script file body.
	 *
	 * @param string              $relative Relative path under plugin scripts/.
	 * @param array<string,mixed> $extra    Extra keys merged into wcbcImportConfig.
	 * @return string
	 */
	private static function wrap_import_script( $relative, $extra = array() ) {
		$payload = array_merge(
			array(
				'site'  => untrailingslashit( home_url() ),
				'token' => self::import_token(),
			),
			is_array( $extra ) ? $extra : array()
		);
		$config = wp_json_encode( $payload );

		$script_path = WCBC_PLUGIN_DIR . $relative;
		$body        = is_readable( $script_path ) ? (string) file_get_contents( $script_path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$body        = preg_replace( '#/\*\*[\s\S]*?\*/\s*#', '', $body, 1 );

		return "window.wcbcImportConfig = {$config};\n" . trim( $body );
	}
}
