<?php
/**
 * Serve Happy Beds layer images from local cache with demo fallbacks.
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCBC_Layer_Serve {

	/**
	 * Relative cache directory under plugin demo-images.
	 */
	const CACHE_DIR = 'demo-images/happybeds-cache/';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_wcbc_layer_image', array( __CLASS__, 'serve' ) );
		add_action( 'wp_ajax_nopriv_wcbc_layer_image', array( __CLASS__, 'serve' ) );
	}

	/**
	 * Whether any cached Happy Beds files exist.
	 *
	 * @return bool
	 */
	public static function cache_has_files() {
		$dir = WCBC_PLUGIN_DIR . self::CACHE_DIR;
		if ( ! is_dir( $dir ) ) {
			return false;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && '.gitkeep' !== $file->getFilename() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build a same-origin proxy URL for a Happy Beds relative asset path.
	 *
	 * @param string $relative Relative path under new_configurator.
	 * @return string
	 */
	public static function proxy_url( $relative ) {
		return add_query_arg(
			array(
				'action' => 'wcbc_layer_image',
				'path'   => ltrim( (string) $relative, '/' ),
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Resolve absolute cache file path from a relative CDN path.
	 *
	 * @param string $relative Relative path.
	 * @return string
	 */
	public static function cache_path( $relative ) {
		$relative = self::sanitize_relative_path( $relative );
		return WCBC_PLUGIN_DIR . self::CACHE_DIR . $relative;
	}

	/**
	 * Sanitize a relative asset path.
	 *
	 * @param string $relative Relative path.
	 * @return string
	 */
	public static function sanitize_relative_path( $relative ) {
		$relative = str_replace( '\\', '/', (string) $relative );
		$relative = preg_replace( '#\.\.+/#', '', $relative );
		$relative = ltrim( $relative, '/' );

		if ( ! preg_match( '#^[a-zA-Z0-9_\-/]+\.(png|jpg|jpeg|webp)$#', $relative ) ) {
			return '';
		}

		return $relative;
	}

	/**
	 * Serve a cached layer image or fall back to demo assets.
	 */
	public static function serve() {
		$relative = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$relative = self::sanitize_relative_path( $relative );

		if ( '' === $relative ) {
			self::send_demo_fallback( 'transparent.png' );
		}

		$cache_file = self::cache_path( $relative );
		if ( is_readable( $cache_file ) ) {
			self::output_file( $cache_file );
		}

		$remote = WCBC_HappyBeds_Resolver::CDN . $relative;
		$response = wp_remote_get(
			$remote,
			array(
				'timeout'    => 15,
				'user-agent' => 'Mozilla/5.0 (compatible; WCBedConfigurator/1.0)',
				'headers'    => array(
					'Referer' => 'https://www.happybeds.co.uk/build-your-own-bed',
				),
			)
		);

		if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			if ( '' !== $body ) {
				self::maybe_write_cache( $relative, $body );
				self::output_bytes( $body, self::mime_for_path( $relative ) );
			}
		}

		self::send_transparent();
	}

	/**
	 * Stream a 1×1 transparent PNG when no cached asset exists.
	 */
	private static function send_transparent() {
		$demo_file = WCBC_PLUGIN_DIR . 'demo-images/layers/transparent.png';
		if ( is_readable( $demo_file ) ) {
			self::output_file( $demo_file );
		}

		status_header( 404 );
		exit;
	}

	/**
	 * Stream a demo fallback file.
	 *
	 * @param string $demo_relative Path relative to demo-images/layers/.
	 */
	private static function send_demo_fallback( $demo_relative ) {
		$demo_relative = ltrim( str_replace( '\\', '/', $demo_relative ), '/' );
		$demo_file     = WCBC_PLUGIN_DIR . 'demo-images/layers/' . $demo_relative;

		if ( is_readable( $demo_file ) ) {
			self::output_file( $demo_file );
		}

		status_header( 404 );
		exit;
	}

	/**
	 * Persist a fetched remote file into the local cache.
	 *
	 * @param string $relative Relative CDN path.
	 * @param string $body File bytes.
	 */
	private static function maybe_write_cache( $relative, $body ) {
		$cache_file = self::cache_path( $relative );
		$cache_dir  = dirname( $cache_file );

		if ( ! wp_mkdir_p( $cache_dir ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $cache_file, $body );
	}

	/**
	 * Output a file with cache headers.
	 *
	 * @param string $file Absolute file path.
	 */
	private static function output_file( $file ) {
		$mime = self::mime_for_path( $file );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$body = file_get_contents( $file );
		if ( false === $body ) {
			status_header( 500 );
			exit;
		}
		self::output_bytes( $body, $mime );
	}

	/**
	 * Output raw bytes.
	 *
	 * @param string $body File contents.
	 * @param string $mime MIME type.
	 */
	private static function output_bytes( $body, $mime ) {
		if ( ! headers_sent() ) {
			status_header( 200 );
			header( 'Content-Type: ' . $mime );
			header( 'Cache-Control: public, max-age=86400' );
			header( 'Content-Length: ' . strlen( $body ) );
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $body;
		exit;
	}

	/**
	 * MIME type from extension.
	 *
	 * @param string $path File path.
	 * @return string
	 */
	private static function mime_for_path( $path ) {
		$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		if ( 'jpg' === $ext || 'jpeg' === $ext ) {
			return 'image/jpeg';
		}
		if ( 'webp' === $ext ) {
			return 'image/webp';
		}
		return 'image/png';
	}
}

/**
 * Resolve preview image mode.
 *
 * Modes: demo, happybeds-cdn, happybeds-proxy, auto.
 *
 * @return string
 */
function wcbc_get_image_mode() {
	$mode = apply_filters( 'wcbc_image_mode', 'demo' );

	if ( 'auto' === $mode ) {
		return 'demo';
	}

	return $mode;
}
